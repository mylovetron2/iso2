<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config/database.php';

// File lưu custom mapping rules
define('CUSTOM_RULES_FILE', __DIR__ . '/find_thietbi_custom_rules.json');

// Load custom rules
function loadCustomRules() {
    if (!file_exists(CUSTOM_RULES_FILE)) {
        return ['rules' => []];
    }
    $json = file_get_contents(CUSTOM_RULES_FILE);
    $data = json_decode($json, true);
    return $data ?: ['rules' => []];
}

// Save custom rules
function saveCustomRules($rules) {
    $json = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Ensure directory is writable
    $dir = dirname(CUSTOM_RULES_FILE);
    if (!is_writable($dir)) {
        error_log("Directory not writable: {$dir}");
        return false;
    }
    
    $result = file_put_contents(CUSTOM_RULES_FILE, $json);
    if ($result === false) {
        error_log("Failed to write custom rules to: " . CUSTOM_RULES_FILE);
        return false;
    }
    
    return true;
}

// Add custom rule
function addCustomRule($search_mavt, $search_sn, $db_mavt, $db_sn, $note = '') {
    $rules = loadCustomRules();
    
    // Check if rule already exists
    foreach ($rules['rules'] as $rule) {
        if ($rule['search_mavt'] === $search_mavt && 
            $rule['search_sn'] === $search_sn &&
            $rule['db_mavt'] === $db_mavt &&
            $rule['db_sn'] === $db_sn) {
            return false; // Already exists
        }
    }
    
    $rules['rules'][] = [
        'search_mavt' => $search_mavt,
        'search_sn' => $search_sn,
        'db_mavt' => $db_mavt,
        'db_sn' => $db_sn,
        'note' => $note,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return saveCustomRules($rules);
}

// Check custom rules for match
function checkCustomRules($search_mavt, $search_sn) {
    $rules = loadCustomRules();
    
    // Normalize S/N for comparison (remove leading zeros)
    $search_sn_normalized = ltrim($search_sn, '0') ?: '0';
    
    foreach ($rules['rules'] as $rule) {
        $rule_sn_normalized = empty($rule['search_sn']) ? '' : (ltrim($rule['search_sn'], '0') ?: '0');
        
        // Exact match
        if ($rule['search_mavt'] === $search_mavt && 
            ($rule['search_sn'] === '' || $rule['search_sn'] === $search_sn)) {
            return [
                'db_mavt' => $rule['db_mavt'],
                'db_sn' => $rule['db_sn'],
                'note' => $rule['note']
            ];
        }
        
        // Case-insensitive match with normalized S/N (ignore leading zeros)
        if (strtoupper($rule['search_mavt']) === strtoupper($search_mavt) && 
            ($rule['search_sn'] === '' || $rule_sn_normalized === $search_sn_normalized)) {
            return [
                'db_mavt' => $rule['db_mavt'],
                'db_sn' => $rule['db_sn'],
                'note' => $rule['note']
            ];
        }
        
        // Flexible S/N matching - check if numbers match (extract numeric part)
        if (strtoupper($rule['search_mavt']) === strtoupper($search_mavt) && !empty($rule['search_sn'])) {
            // Extract all numbers from both S/N
            preg_match_all('/\d+/', $rule['search_sn'], $rule_numbers);
            preg_match_all('/\d+/', $search_sn, $search_numbers);
            
            $rule_num = !empty($rule_numbers[0]) ? max(array_map('intval', $rule_numbers[0])) : null;
            $search_num = !empty($search_numbers[0]) ? max(array_map('intval', $search_numbers[0])) : null;
            
            if ($rule_num !== null && $search_num !== null && $rule_num === $search_num) {
                return [
                    'db_mavt' => $rule['db_mavt'],
                    'db_sn' => $rule['db_sn'],
                    'note' => $rule['note']
                ];
            }
        }
    }
    
    // Level 4: Smart Pattern Matching - detect prefix/suffix pattern from similar rules
    // Example: Rule "197" → "A197" will auto-apply to "199" → "A199"
    foreach ($rules['rules'] as $rule) {
        if (strtoupper($rule['search_mavt']) === strtoupper($search_mavt) && 
            !empty($rule['search_sn']) && !empty($search_sn) &&
            !empty($rule['db_sn'])) {
            
            // Extract numbers from rule's search_sn and db_sn
            preg_match_all('/\d+/', $rule['search_sn'], $rule_search_nums);
            preg_match_all('/\d+/', $rule['db_sn'], $rule_db_nums);
            preg_match_all('/\d+/', $search_sn, $search_nums);
            
            $rule_search_num = !empty($rule_search_nums[0]) ? $rule_search_nums[0][0] : null;
            $rule_db_num = !empty($rule_db_nums[0]) ? $rule_db_nums[0][0] : null;
            $search_num = !empty($search_nums[0]) ? $search_nums[0][0] : null;
            
            // If both rule's search_sn and db_sn contain numbers
            if ($rule_search_num !== null && $rule_db_num !== null && $search_num !== null) {
                // Check if the numeric parts are the same but db_sn has prefix/suffix
                if ($rule_search_num === $rule_db_num) {
                    // Detect prefix pattern (e.g., "197" vs "A197")
                    $prefix = '';
                    $suffix = '';
                    
                    if (strpos($rule['db_sn'], $rule_db_num) > 0) {
                        // Has prefix (e.g., "A197")
                        $prefix = substr($rule['db_sn'], 0, strpos($rule['db_sn'], $rule_db_num));
                    }
                    
                    $num_end_pos = strpos($rule['db_sn'], $rule_db_num) + strlen($rule_db_num);
                    if ($num_end_pos < strlen($rule['db_sn'])) {
                        // Has suffix (e.g., "197X")
                        $suffix = substr($rule['db_sn'], $num_end_pos);
                    }
                    
                    // Apply pattern to current search_sn
                    if (!empty($prefix) || !empty($suffix)) {
                        $transformed_sn = $prefix . $search_num . $suffix;
                        return [
                            'db_mavt' => $rule['db_mavt'],
                            'db_sn' => $transformed_sn,
                            'note' => '🔮 Auto-pattern từ rule: ' . $rule['search_sn'] . ' → ' . $rule['db_sn']
                        ];
                    }
                }
            }
        }
    }
    
    return null;
}

// Hàm chuyển đổi ký tự Cyrillic sang Latin
function transliterate_cyrillic_to_latin($text) {
    $cyrillic = [
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
        'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
        'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'
    ];
    
    $latin = [
        'A', 'B', 'V', 'G', 'D', 'E', 'YO', 'ZH', 'Z', 'I', 'I', 'K', 'L', 'M', 'N', 'O', 'P',
        'R', 'S', 'T', 'U', 'F', 'H', 'C', 'CH', 'SH', 'SCH', '', 'Y', '', 'E', 'YU', 'YA',
        'a', 'b', 'v', 'g', 'd', 'e', 'yo', 'zh', 'z', 'i', 'i', 'k', 'l', 'm', 'n', 'o', 'p',
        'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya'
    ];
    
    return str_replace($cyrillic, $latin, $text);
}

// Danh sách thiết bị cần tìm (mavt => somay)
$thietbi_list = [
    ['mavt' => 'GTET', 'somay' => '11533904'],
    ['mavt' => 'GTET', 'somay' => '11705762'],
    ['mavt' => 'GTET', 'somay' => '11705765'],
    ['mavt' => 'IDT', 'somay' => '11680456'],
    ['mavt' => 'IDT', 'somay' => '11680458'],
    ['mavt' => 'DSNT', 'somay' => '11534471'],
    ['mavt' => 'DSNT', 'somay' => '11534475'],
    ['mavt' => 'DSNT', 'somay' => '11660710'],
    ['mavt' => 'DSNT', 'somay' => '11660711'],
    ['mavt' => 'Giôn cứng x 4', 'somay' => 'INSITE'],
    ['mavt' => 'SP - CR - BR', 'somay' => 'INSITE'],
    ['mavt' => 'SDLT', 'somay' => '11537128'],
    ['mavt' => 'BSAT', 'somay' => '11310050'],
    ['mavt' => 'BSAT', 'somay' => '11603269'],
    ['mavt' => 'BHPT', 'somay' => '12009522'],
    ['mavt' => 'BHPT', 'somay' => '12225262'],
    ['mavt' => 'ICT', 'somay' => '11660551'],
    ['mavt' => 'ICT', 'somay' => '11660552'],
    ['mavt' => 'ACRT', 'somay' => '12068675'],
    ['mavt' => 'ACRT', 'somay' => '12068676'],
    ['mavt' => 'DLLT', 'somay' => '6'],
    ['mavt' => 'DLLT', 'somay' => '8'],
    ['mavt' => 'DLLT', 'somay' => '9'],
    ['mavt' => 'D4TG', 'somay' => '117'],
    ['mavt' => 'D4TG', 'somay' => '118'],
    ['mavt' => 'D4TG', 'somay' => '967'],
    ['mavt' => 'D4TG', 'somay' => '505'],
    ['mavt' => 'D2TS', 'somay' => '95'],
    ['mavt' => 'D2TS', 'somay' => '98'],
    ['mavt' => 'NGRT', 'somay' => '197'],
    ['mavt' => 'NGRT', 'somay' => '199'],
    ['mavt' => 'SDDT', 'somay' => '762'],
    ['mavt' => 'SDDT', 'somay' => '763'],
    ['mavt' => 'DSNT', 'somay' => '112'],
    ['mavt' => 'DSNT', 'somay' => '113'],
    ['mavt' => 'DSNT', 'somay' => '208'],
    ['mavt' => 'DSNT', 'somay' => '209'],
    ['mavt' => 'Giôn cứng x 4', 'somay' => 'DITS'],
    ['mavt' => 'SP - CR - BR', 'somay' => 'DITS'],
    ['mavt' => 'SDLT', 'somay' => '93'],
    ['mavt' => 'SDLT', 'somay' => '94'],
    ['mavt' => 'BCDT', 'somay' => '33'],
    ['mavt' => 'BCDT', 'somay' => '34'],
    ['mavt' => 'BCDT', 'somay' => '35'],
    ['mavt' => 'BCDT', 'somay' => '361'],
    ['mavt' => 'FIAC', 'somay' => '34'],
    ['mavt' => 'FIAC', 'somay' => '93'],
    ['mavt' => 'HRI', 'somay' => '143'],
    ['mavt' => 'HRI', 'somay' => '144'],
    ['mavt' => 'DLLT', 'somay' => '2'],
    ['mavt' => 'DLLT', 'somay' => '5'],
    ['mavt' => 'MSFL', 'somay' => '4'],
    ['mavt' => 'CAST-V', 'somay' => '26'],
    ['mavt' => 'CAST-V', 'somay' => '27'],
    ['mavt' => 'CAST-V', 'somay' => '113'],
    ['mavt' => 'CAST-V', 'somay' => '114'],
    ['mavt' => 'CAST-F', 'somay' => '703'],
    ['mavt' => 'CAST-F', 'somay' => '565'],
    ['mavt' => 'CAST-F', 'somay' => '874'],
    ['mavt' => 'CSNG', 'somay' => '28'],
    ['mavt' => 'CSNG', 'somay' => '29'],
    ['mavt' => 'CSNG', 'somay' => '90'],
    ['mavt' => 'CSNG', 'somay' => '91'],
    ['mavt' => 'Connector Sub 3 1/2', 'somay' => 'CONS1'],
    ['mavt' => 'Connector Sub 3 1/2', 'somay' => 'CONS2'],
    ['mavt' => 'DTD', 'somay' => '12633945'],
    ['mavt' => 'DTD', 'somay' => '584'],
    ['mavt' => 'Swivel- HDDS', 'somay' => 'SWIVEL-12457113'],
    ['mavt' => 'Swivel MCSA-D', 'somay' => 'SWIVEL2 10932066'],
    ['mavt' => 'DITS FLEX', 'somay' => 'FLEX1'],
    ['mavt' => 'DITS FLEX', 'somay' => 'FLEX3'],
    ['mavt' => 'DITS FLEX', 'somay' => 'FLEX4'],
    ['mavt' => 'IQ FLEX', 'somay' => 'FLEX-12166048'],
    ['mavt' => 'IQ FLEX', 'somay' => 'FLEX-12442403'],
    ['mavt' => 'CCL-IC', 'somay' => '0717'],
    ['mavt' => 'CCL-IC', 'somay' => '0718'],
    ['mavt' => 'CCL-IC', 'somay' => '1036'],
    ['mavt' => 'CCL-IC', 'somay' => '1037'],
    ['mavt' => 'NEC-IC', 'somay' => '1203'],
    ['mavt' => 'NEC-IC', 'somay' => '0801'],
    ['mavt' => 'NEC-IC', 'somay' => '0802'],
    ['mavt' => 'CNS - IC', 'somay' => '1203'],
    ['mavt' => 'CNS - IC', 'somay' => '0720'],
    ['mavt' => 'CNS - IC', 'somay' => '0721'],
    ['mavt' => 'REC-IC', 'somay' => '1108'],
    ['mavt' => 'REC-IC', 'somay' => '1109'],
    ['mavt' => 'REC-IC', 'somay' => '0803'],
    ['mavt' => 'REC-IC', 'somay' => '0804'],
    ['mavt' => 'BDS-IC', 'somay' => '1201'],
    ['mavt' => 'BDS-IC', 'somay' => '1202'],
    ['mavt' => 'BDS-IC', 'somay' => '1002'],
    ['mavt' => 'BDS-IC', 'somay' => '0728'],
    ['mavt' => 'BDS-IC', 'somay' => '0729'],
    ['mavt' => 'TGR-IC', 'somay' => '1001'],
    ['mavt' => 'TGR-IC', 'somay' => '1117'],
    ['mavt' => 'TGR-IC', 'somay' => '1120'],
    ['mavt' => 'Giôn cứng x 4', 'somay' => ''],
    ['mavt' => 'TTR-IC', 'somay' => '1004'],
    ['mavt' => 'TTR-IC', 'somay' => '1264'],
    ['mavt' => 'TTR-IC', 'somay' => '0726'],
    ['mavt' => 'TTR-IC', 'somay' => '0727'],
    ['mavt' => 'DLS-IC', 'somay' => '1002'],
    ['mavt' => 'DLS-IC', 'somay' => '1007'],
    ['mavt' => 'DLS-IC', 'somay' => '1008'],
    ['mavt' => 'DLS-IC', 'somay' => '0723'],
    ['mavt' => 'DLS-IC', 'somay' => '0724'],
    ['mavt' => 'HRAS-IC', 'somay' => '1009'],
    ['mavt' => 'HRAS-IC', 'somay' => '1013'],
    ['mavt' => 'LDS/MFS', 'somay' => '1119'],
    ['mavt' => 'LDS/MFS', 'somay' => '1120'],
    ['mavt' => 'LDS/MFS', 'somay' => '0716'],
    ['mavt' => 'LDS/MFS', 'somay' => '0717'],
    ['mavt' => 'D4GC-IC', 'somay' => '1203'],
    ['mavt' => 'D4GC-IC', 'somay' => '1204'],
    ['mavt' => 'D4GC-IC', 'somay' => '0725'],
    ['mavt' => 'D4GC-IC', 'somay' => '0726'],
    ['mavt' => 'TC4T-IC', 'somay' => '1036'],
    ['mavt' => 'TC4T-IC', 'somay' => '1035'],
    ['mavt' => 'Flex sub FJS-IC', 'somay' => '1005'],
    ['mavt' => 'Flex sub FJS-IC', 'somay' => '1006'],
    ['mavt' => 'HAS-IC', 'somay' => '1119'],
    ['mavt' => 'HAS-IC', 'somay' => '1120'],
    ['mavt' => 'HAS-IC', 'somay' => '0806'],
    ['mavt' => 'HAS-IC', 'somay' => '0812'],
    ['mavt' => 'HD-Khúc nối xoay XJT-IC', 'somay' => '1202'],
    ['mavt' => 'HD-Khúc nối xoay XJT-IC', 'somay' => '1203'],
    ['mavt' => 'JSCC', 'somay' => 'JSCC-0802'],
    ['mavt' => 'JSCC', 'somay' => '801'],
    ['mavt' => 'SGS-IB', 'somay' => 'SGS-IC-0805'],
    ['mavt' => 'SGS-IB', 'somay' => 'SGS-IC-0905'],
    ['mavt' => 'VSP- Súng hơi- IIC 150', 'somay' => 'N1368'],
    ['mavt' => 'VSP- Súng hơi- IIC 150', 'somay' => 'N1369'],
    ['mavt' => 'VSP- Súng hơi- IIC 150', 'somay' => 'N1370'],
    ['mavt' => 'VSP-Bộ nguồn nổ 3 súng', 'somay' => '147'],
    ['mavt' => 'VSP-Bộ nguồn nổ 3 súng', 'somay' => '114'],
    ['mavt' => 'VSP-Bộ nguồn nổ 3 súng', 'somay' => '721'],
    ['mavt' => 'VSP-Bộ nguồn nổ 3 súng', 'somay' => '473'],
    ['mavt' => 'GRT', 'somay' => 'No 31'],
    ['mavt' => 'GRT', 'somay' => 'No 32'],
    ['mavt' => 'TAS', 'somay' => 'No 90'],
    ['mavt' => 'TAS', 'somay' => 'No 91'],
    ['mavt' => 'TAS', 'somay' => 'TAS - 213'],
    ['mavt' => 'VRS', 'somay' => 'No: 71'],
    ['mavt' => 'VRS', 'somay' => 'No:73'],
    ['mavt' => 'ASR', 'somay' => 'No368'],
    ['mavt' => 'ASR', 'somay' => 'No369'],
    ['mavt' => 'ASR', 'somay' => 'No370'],
    ['mavt' => 'ASR', 'somay' => 'No371'],
    ['mavt' => 'ASR', 'somay' => 'No372'],
    ['mavt' => 'ASR', 'somay' => 'No373'],
    ['mavt' => 'ASR', 'somay' => 'No374'],
    ['mavt' => 'ASR', 'somay' => 'No375'],
    ['mavt' => 'VSP - Bộ định vị', 'somay' => 'No 1'],
    ['mavt' => 'VSP-Máy nén khí kép', 'somay' => 'SN 026-134273'],
    ['mavt' => 'VSP-Máy nén khí đơn', 'somay' => 'VSP001'],
    ['mavt' => 'VSP-Máy nén khí đơn', 'somay' => 'VSP002'],
    ['mavt' => 'iCCL', 'somay' => '6'],
    ['mavt' => 'iGS', 'somay' => '5'],
    ['mavt' => 'iCT', 'somay' => '7'],
    ['mavt' => 'iCT', 'somay' => '8'],
    ['mavt' => 'iRB-B', 'somay' => ''],
    ['mavt' => 'iRB', 'somay' => ''],
    ['mavt' => 'iRB-SP', 'somay' => ''],
    ['mavt' => 'cách điện - iIR', 'somay' => ''],
    ['mavt' => 'Swivel Signum', 'somay' => '5'],
    ['mavt' => 'iKS', 'somay' => '3'],
    ['mavt' => 'iKS', 'somay' => '6'],
    ['mavt' => 'Lệch tâm', 'somay' => '6'],
    ['mavt' => 'iDIU', 'somay' => '5'],
    ['mavt' => 'iDIL', 'somay' => '5'],
    ['mavt' => 'iBT', 'somay' => '6'],
    ['mavt' => 'iBT', 'somay' => '5'],
    ['mavt' => 'iTM', 'somay' => '6'],
    ['mavt' => 'iSL', 'somay' => '5'],
    ['mavt' => 'iCN', 'somay' => '5'],
    ['mavt' => 'Cable head Sig', 'somay' => '4'],
    ['mavt' => 'Đuôi máy Sig', 'somay' => ''],
    ['mavt' => 'iWS', 'somay' => '11'],
    ['mavt' => 'iDL', 'somay' => '1'],
    ['mavt' => 'Battery TBSB-BA', 'somay' => '221'],
    ['mavt' => 'TMG-BA', 'somay' => '139'],
    ['mavt' => 'TBCCL-A', 'somay' => '101'],
    ['mavt' => 'Cable head PEH-EFA', 'somay' => 'SN5126'],
    ['mavt' => 'Swivel SAH-TB', 'somay' => '51'],
    ['mavt' => 'Knuckle sub KAH-TB', 'somay' => 'SN125 & 125'],
    ['mavt' => 'Centralizer TCME-BA', 'somay' => '2 cái'],
    ['mavt' => 'Knuckle-Spacer TBKS', 'somay' => '34'],
    ['mavt' => 'Decentralizer Tile-CA', 'somay' => '58'],
    ['mavt' => 'Bottom Nose, Thrubit', 'somay' => 'SN56'],
    ['mavt' => 'TBLA-UC', 'somay' => 'ENP37'],
    ['mavt' => 'Trạm Signum', 'somay' => ''],
    ['mavt' => 'Trạm Thrubit', 'somay' => ''],
    ['mavt' => 'Trạm VSP', 'somay' => ''],
    ['mavt' => 'Trạm Excell', 'somay' => ''],
    ['mavt' => 'Trạm LOGIQ', 'somay' => 'LOGIQ-A-01 (HLS 03)'],
    ['mavt' => 'Trạm LOGIQ', 'somay' => 'LOGIQ-B-02 (HLS 04)'],
    ['mavt' => 'Trạm LOGIQ', 'somay' => 'LOGIQ-B-03 (HLS 5)'],
    ['mavt' => 'Trạm Huanding', 'somay' => 'HH-2530-1208105'],
    ['mavt' => 'Trạm Huanding', 'somay' => 'HD-1211102 (Baoji)'],
    ['mavt' => 'Trạm GR/CCL', 'somay' => '220295/1'],
    ['mavt' => 'ГК-60', 'somay' => '1001'],
    ['mavt' => 'ГК-60', 'somay' => '1002'],
    ['mavt' => 'ГК-60', 'somay' => '1003'],
    ['mavt' => 'ГК-60', 'somay' => '1004'],
    ['mavt' => 'БК3-60', 'somay' => '1001'],
    ['mavt' => 'БК3-60', 'somay' => '1002'],
    ['mavt' => 'БК3-60', 'somay' => '1003'],
    ['mavt' => 'БК3-60', 'somay' => '1004'],
    ['mavt' => 'ДЛ-60', 'somay' => '820'],
    ['mavt' => 'ДЛ-60', 'somay' => '822'],
    ['mavt' => 'ДЛ-60', 'somay' => '823'],
    ['mavt' => 'ДЛ-60', 'somay' => '824(918)'],
    ['mavt' => 'ДЛ-60', 'somay' => '825'],
    ['mavt' => 'ДЛ-60', 'somay' => '826'],
    ['mavt' => 'ДЛ-60', 'somay' => '827'],
    ['mavt' => 'ДЛ-60', 'somay' => '828'],
    ['mavt' => 'ДЛ-60', 'somay' => '829'],
    ['mavt' => 'ГК -76', 'somay' => '11'],
    ['mavt' => 'ГК -76', 'somay' => '1015'],
    ['mavt' => 'ГК -76', 'somay' => '1016'],
    ['mavt' => 'ГК -76', 'somay' => '1025'],
    ['mavt' => 'ГК -76', 'somay' => '1026'],
    ['mavt' => 'ГК -76', 'somay' => '1028'],
    ['mavt' => 'ГК -76', 'somay' => '1045'],
    ['mavt' => 'ГК -76', 'somay' => '1046'],
    ['mavt' => 'HHK-76', 'somay' => '7'],
    ['mavt' => 'HHK-76', 'somay' => '8'],
    ['mavt' => 'HHK-76', 'somay' => '1009'],
    ['mavt' => 'HHK-76', 'somay' => '1010'],
    ['mavt' => 'HHK-76', 'somay' => '1011'],
    ['mavt' => 'HHK-76', 'somay' => '1012'],
    ['mavt' => 'HHK-76', 'somay' => '1014'],
    ['mavt' => 'HHK-76', 'somay' => '1015'],
    ['mavt' => 'HHK-76', 'somay' => '1016'],
    ['mavt' => 'HHK-76', 'somay' => '1017'],
    ['mavt' => 'ГГК', 'somay' => '3'],
    ['mavt' => 'ГГК', 'somay' => '4'],
    ['mavt' => 'ГГК', 'somay' => '1007'],
    ['mavt' => 'ГГК', 'somay' => '1008'],
    ['mavt' => 'ГГК', 'somay' => '1009'],
    ['mavt' => 'ГГК', 'somay' => '1012'],
    ['mavt' => 'ИK-76', 'somay' => '1016'],
    ['mavt' => 'ИK-76', 'somay' => '1017'],
    ['mavt' => 'ИK-76', 'somay' => '1030'],
    ['mavt' => 'ИK-76', 'somay' => '1031'],
    ['mavt' => 'ИK-76', 'somay' => '1032'],
    ['mavt' => 'MБK', 'somay' => '1'],
    ['mavt' => 'MБK', 'somay' => '3'],
    ['mavt' => 'MБK', 'somay' => '1025'],
    ['mavt' => 'MБK', 'somay' => '1038'],
    ['mavt' => 'MБK', 'somay' => '1039'],
    ['mavt' => 'MБK', 'somay' => '1040'],
    ['mavt' => 'MБK', 'somay' => '1041'],
    ['mavt' => 'БК3-76', 'somay' => '1035'],
    ['mavt' => 'БК3-76', 'somay' => '1036'],
    ['mavt' => 'БК3-76', 'somay' => '1038'],
    ['mavt' => 'БК3-76', 'somay' => '1039'],
    ['mavt' => 'БК3-76', 'somay' => '1040'],
    ['mavt' => 'БК3-76', 'somay' => '1041'],
    ['mavt' => 'CKП-76', 'somay' => '1025'],
    ['mavt' => 'CKП-76', 'somay' => '1033'],
    ['mavt' => 'CKП-76', 'somay' => '1034'],
    ['mavt' => 'CKП-76', 'somay' => '1035'],
    ['mavt' => 'CKП-76', 'somay' => '1036'],
    ['mavt' => 'CKП-76', 'somay' => '1037'],
    ['mavt' => 'CKП-76', 'somay' => '1038'],
    ['mavt' => 'CKП-76', 'somay' => '1039'],
    ['mavt' => 'CKП-76', 'somay' => '1044'],
    ['mavt' => 'ДЛ-76', 'somay' => '554'],
    ['mavt' => 'ДЛ-76', 'somay' => '562'],
    ['mavt' => 'ДЛ-76', 'somay' => '563'],
    ['mavt' => 'ДЛ-76', 'somay' => '608'],
    ['mavt' => 'ДЛ-76', 'somay' => '610'],
    ['mavt' => 'ДЛ-76', 'somay' => '613(696)'],
    ['mavt' => 'ГСВ-90', 'somay' => '040'],
    ['mavt' => 'ГСВ-90', 'somay' => '041'],
    ['mavt' => 'ГК-A-90', 'somay' => '1004'],
    ['mavt' => 'ГК-A-90', 'somay' => '1005'],
    ['mavt' => 'ГК + ЛМ -A-90', 'somay' => '1001'],
    ['mavt' => 'ГК + ЛМ -A-90', 'somay' => '1002'],
    ['mavt' => 'ГК-NNK-A-90', 'somay' => '1145'],
    ['mavt' => 'ГК-NNK-A-90', 'somay' => '1146'],
    ['mavt' => 'ГК-NNK-A-90', 'somay' => '1147'],
    ['mavt' => 'ГК-NNK-A-90', 'somay' => '1148'],
    ['mavt' => 'БК-A-90', 'somay' => '1007'],
    ['mavt' => 'БК-A-90', 'somay' => '1009'],
    ['mavt' => 'БК-A-90', 'somay' => '1050'],
    ['mavt' => 'БК-A-90', 'somay' => '1068'],
    ['mavt' => 'БК-A-90', 'somay' => '1069'],
    ['mavt' => 'БК-A-90', 'somay' => '1076'],
    ['mavt' => 'ИK-A-90', 'somay' => '1050'],
    ['mavt' => 'ИK-A-90', 'somay' => '1051'],
    ['mavt' => 'ИK-A-90', 'somay' => '1069'],
    ['mavt' => 'ИK-A-90', 'somay' => '1070'],
    ['mavt' => 'ИФМ-А-90', 'somay' => '959'],
    ['mavt' => 'ИФМ-А-90', 'somay' => '960'],
    ['mavt' => 'ИФМ-А-90', 'somay' => '1059'],
    ['mavt' => 'ИФМ-А-90', 'somay' => '1067'],
    ['mavt' => 'AK-73', 'somay' => '11'],
    ['mavt' => 'AK-73', 'somay' => '1023'],
    ['mavt' => 'AK-73', 'somay' => '1043'],
    ['mavt' => 'AK-73', 'somay' => '1044'],
    ['mavt' => 'AK-73', 'somay' => '1045'],
    ['mavt' => 'AK-73', 'somay' => '1046'],
    ['mavt' => 'AK-73', 'somay' => '1055'],
    ['mavt' => 'AK-73', 'somay' => '1057'],
    ['mavt' => 'AK-73', 'somay' => '1058'],
    ['mavt' => 'AK-A-90', 'somay' => '1018'],
    ['mavt' => 'AK-A-90', 'somay' => '1019'],
    ['mavt' => 'AK-A-90', 'somay' => '1077'],
    ['mavt' => 'AK-A-90', 'somay' => '1087'],
    ['mavt' => 'AK-A-90', 'somay' => '1091'],
    ['mavt' => 'AK-A-90', 'somay' => '1106'],
    ['mavt' => 'МТМ (TP7V)', 'somay' => '1018'],
    ['mavt' => 'МТМ (TP7V)', 'somay' => '1034'],
    ['mavt' => 'МТМ (TP7V)', 'somay' => '1035'],
    ['mavt' => 'МТМ (TP7V)', 'somay' => '1036'],
    ['mavt' => 'МТМ (TP7V)', 'somay' => '1037'],
    ['mavt' => 'ТД (TTVF)', 'somay' => '2004'],
    ['mavt' => 'ТД (TTVF)', 'somay' => '2005'],
    ['mavt' => 'AЛM-76', 'somay' => '1002'],
    ['mavt' => 'AЛM-76', 'somay' => '1003'],
    ['mavt' => 'AЛM-76', 'somay' => '1004'],
    ['mavt' => 'AЛM-76', 'somay' => '1011'],
    ['mavt' => 'AЛM-76', 'somay' => '1012'],
    ['mavt' => 'AЛM-76', 'somay' => '1013'],
    ['mavt' => 'AЛM-76', 'somay' => '1014'],
    ['mavt' => 'AЛM-76', 'somay' => '1015'],
    ['mavt' => 'AЛM-76', 'somay' => '1016'],
    ['mavt' => 'ASPG', 'somay' => '1077'],
    ['mavt' => 'ASPG', 'somay' => '1078'],
    ['mavt' => 'CCL 3-1/8', 'somay' => 'J5ID3-05'],
    ['mavt' => 'CCL 3-1/8', 'somay' => 'J5ID3-06'],
    ['mavt' => 'CCL 3-1/8', 'somay' => 'J5ID3-08'],
    ['mavt' => 'CCL 3-1/8', 'somay' => 'J5ID3-11'],
    ['mavt' => 'CCL 2-3/4', 'somay' => 'JAEW8-01'],
    ['mavt' => 'CCL 2-3/4', 'somay' => 'JAEW8-02'],
];

try {
    $db = getDBConnection();
    
    // Handle save custom rule
    $save_message = null;
    if (!empty($_POST['save_rule'])) {
        $search_mavt = trim($_POST['rule_search_mavt'] ?? '');
        $search_sn = trim($_POST['rule_search_sn'] ?? '');
        $db_mavt = trim($_POST['rule_db_mavt'] ?? '');
        $db_sn = trim($_POST['rule_db_sn'] ?? '');
        $note = trim($_POST['rule_note'] ?? '');
        
        // Debug: Show what was received
        $debug_received = "📥 Giá trị nhận được:<br>";
        $debug_received .= "Search: <code>{$search_mavt}</code> + <code>{$search_sn}</code><br>";
        $debug_received .= "DB: <code>{$db_mavt}</code> + <code>{$db_sn}</code><br><br>";
        
        if (!empty($search_mavt) && !empty($db_mavt)) {
            // Validation: Warn if search = db (useless rule)
            if ($search_mavt === $db_mavt && $search_sn === $db_sn) {
                $save_message = $debug_received;
                $save_message .= "⚠ <strong>CẢNH BÁO:</strong> Rule này vô nghĩa!<br>";
                $save_message .= "Bạn đang map <code>{$search_mavt} + {$search_sn}</code> → <code>{$db_mavt} + {$db_sn}</code> (giống nhau hoàn toàn)<br>";
                $save_message .= "<strong style='color: #dc3545;'>❌ Rule phải map từ giá trị SAI → giá trị ĐÚNG trong database!</strong><br>";
                $save_message .= "VD: <code>HRAS + 11013</code> → <code>HRAS-IC + 1013</code>";
            } else {
                $rules_before = loadCustomRules();
                $count_before = count($rules_before['rules']);
                
                if (addCustomRule($search_mavt, $search_sn, $db_mavt, $db_sn, $note)) {
                    $rules_after = loadCustomRules();
                    $count_after = count($rules_after['rules']);
                    
                    if ($count_after > $count_before) {
                        $save_message = $debug_received;
                        $save_message .= "✓ <strong>Đã lưu thành công!</strong><br>";
                        $save_message .= "Rule: <code>{$search_mavt} + {$search_sn}</code> → <code>{$db_mavt} + {$db_sn}</code><br>";
                        $save_message .= "Total rules: {$count_after}";
                    } else {
                        $save_message = $debug_received;
                        $save_message .= "⚠ Rule có vẻ được lưu nhưng không tăng count. Check file: " . CUSTOM_RULES_FILE;
                    }
                } else {
                    $file_exists = file_exists(CUSTOM_RULES_FILE);
                    $dir_writable = is_writable(dirname(CUSTOM_RULES_FILE));
                    $save_message = $debug_received;
                    $save_message .= "⚠ <strong>Lỗi:</strong> Rule đã tồn tại HOẶC không thể ghi file.<br>";
                    $save_message .= "File: " . CUSTOM_RULES_FILE . "<br>";
                    $save_message .= "File exists: " . ($file_exists ? 'Yes' : 'No') . "<br>";
                    $save_message .= "Dir writable: " . ($dir_writable ? 'Yes' : 'No');
                }
            }
        }
    }
    
    // Handle delete custom rule
    if (!empty($_POST['delete_rule']) && isset($_POST['rule_index'])) {
        $rule_index = intval($_POST['rule_index']);
        $rules = loadCustomRules();
        if (isset($rules['rules'][$rule_index])) {
            $deleted_rule = $rules['rules'][$rule_index];
            array_splice($rules['rules'], $rule_index, 1);
            if (saveCustomRules($rules)) {
                $save_message = "🗑️ Đã xóa rule: {$deleted_rule['search_mavt']} → {$deleted_rule['db_mavt']}";
            } else {
                $save_message = "⚠ Lỗi khi xóa rule";
            }
        }
    }
    
    // Test query logic
    $test_result = null;
    if (!empty($_GET['test_search']) || !empty($_GET['test_db_mavt'])) {
        $test_search = trim($_GET['test_search'] ?? '');
        $test_sn = trim($_GET['test_sn'] ?? '');
        $test_db_mavt = trim($_GET['test_db_mavt'] ?? '');
        $test_db_sn = trim($_GET['test_db_sn'] ?? '');
        
        $test_result = [
            'search' => $test_search,
            'sn' => $test_sn,
            'db_mavt' => $test_db_mavt,
            'db_sn' => $test_db_sn,
            'found' => false,
            'match_type' => null,
            'custom_rule_match' => false,
            'debug' => []
        ];
        
        if (!empty($test_search) && !empty($test_sn)) {
            // Run search logic (same as main loop)
            $ten_thietbi_latin = transliterate_cyrillic_to_latin($test_search);
            preg_match_all('/\d+/', $ten_thietbi_latin, $matches);
            $extracted_numbers = $matches[0] ?? [];
            
            // S/N variants
            $sn_variants = [$test_sn];
            preg_match_all('/\d+/', $test_sn, $sn_numbers);
            $extracted_sn_number = null;
            
            if (!empty($sn_numbers[0])) {
                $longest = '';
                foreach ($sn_numbers[0] as $num) {
                    if (strlen($num) > strlen($longest)) {
                        $longest = $num;
                    }
                }
                if (strlen($longest) >= 3 || count($sn_numbers[0]) == 1) {
                    $extracted_sn_number = $longest;
                    $sn_variants[] = $extracted_sn_number;
                }
            }
            
            $numeric_sn = is_numeric($test_sn) ? $test_sn : $extracted_sn_number;
            if ($numeric_sn !== null) {
                $sn_num = (int)$numeric_sn;
                $sn_variants[] = (string)$sn_num;
                $sn_variants[] = str_pad($numeric_sn, 2, '0', STR_PAD_LEFT);
                $sn_variants[] = str_pad($numeric_sn, 3, '0', STR_PAD_LEFT);
                $sn_variants[] = str_pad($numeric_sn, 4, '0', STR_PAD_LEFT);
            }
            
            $sn_variants = array_unique($sn_variants);
            $test_result['debug']['sn_variants'] = $sn_variants;
            $test_result['debug']['latin'] = $ten_thietbi_latin;
            
            // Check custom rules first
            $custom_rule = checkCustomRules($test_search, $test_sn);
            if ($custom_rule) {
                $test_result['debug']['custom_rule'] = $custom_rule;
                
                // Search in database using custom rule mapping
                $custom_sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'custom-rule' as match_type
                              FROM thietbi_iso 
                              WHERE mavt = :custom_mavt";
                
                if (!empty($custom_rule['db_sn'])) {
                    $custom_sql .= " AND somay = :custom_sn";
                }
                $custom_sql .= " LIMIT 1";
                
                $stmt = $db->prepare($custom_sql);
                $stmt->bindValue(':custom_mavt', $custom_rule['db_mavt']);
                if (!empty($custom_rule['db_sn'])) {
                    $stmt->bindValue(':custom_sn', $custom_rule['db_sn']);
                }
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $test_result['found'] = true;
                    $test_result['match_type'] = 'custom-rule';
                    $test_result['result'] = $result;
                    $test_result['custom_rule_match'] = true;
                    $test_result['custom_rule_note'] = $custom_rule['note'];
                }
            }
            
            // Build S/N condition
            $sn_placeholders = [];
            $sn_params = [];
            foreach ($sn_variants as $idx => $variant) {
                $placeholder = ":somay{$idx}";
                $sn_placeholders[] = "somay = $placeholder";
                $sn_params[$placeholder] = $variant;
            }
            $sn_condition = '(' . implode(' OR ', $sn_placeholders) . ')';
            
            // Try exact match (if custom rule didn't find)
            if (!$test_result['found']) {
                $sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'exact' as match_type
                        FROM thietbi_iso 
                        WHERE $sn_condition
                        AND (
                            mavt = :ten1 OR mavt = :ten_latin1 OR
                            model = :ten2 OR model = :ten_latin2 OR
                            mamay = :ten3 OR mamay = :ten_latin3
                        )
                        LIMIT 1";
            
                $stmt = $db->prepare($sql);
                $params = array_merge($sn_params, [
                    ':ten1' => $test_search,
                    ':ten_latin1' => $ten_thietbi_latin,
                    ':ten2' => $test_search,
                    ':ten_latin2' => $ten_thietbi_latin,
                    ':ten3' => $test_search,
                    ':ten_latin3' => $ten_thietbi_latin
                ]);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $test_result['found'] = true;
                    $test_result['match_type'] = 'exact';
                    $test_result['result'] = $result;
                }
            }
        }
    }
    
    // Load all custom rules for display
    $all_custom_rules = loadCustomRules();
    
    $found_count = 0;
    $not_found_count = 0;
    $custom_rule_count = 0;
    $results = [];
    
    foreach ($thietbi_list as $item) {
        $ten_thietbi = trim($item['mavt']);  // Tên thiết bị từ dữ liệu
        $sn = trim($item['somay']);           // S/N từ dữ liệu
        
        // Chuyển đổi tên thiết bị sang Latin nếu có ký tự Cyrillic
        $ten_thietbi_latin = transliterate_cyrillic_to_latin($ten_thietbi);
        
        // Trích xuất số từ tên thiết bị (VD: BK3-76 -> [3, 76])
        preg_match_all('/\d+/', $ten_thietbi_latin, $matches);
        $extracted_numbers = $matches[0] ?? [];
        
        // Tạo các biến thể của S/N để tìm kiếm linh hoạt
        $sn_variants = [$sn];
        
        // Trích xuất số từ S/N - lấy số dài nhất (≥3 digits để tránh nhầm với năm/tháng)
        // VD: SWIVEL-12457113 -> 12457113
        // VD: SWIVEL2 10932066 -> 10932066
        // VD: GGK 04 -> 04
        preg_match_all('/\d+/', $sn, $sn_numbers);
        $extracted_sn_number = null;
        
        if (!empty($sn_numbers[0])) {
            // Lấy số dài nhất
            $longest = '';
            foreach ($sn_numbers[0] as $num) {
                if (strlen($num) > strlen($longest)) {
                    $longest = $num;
                }
            }
            // Chỉ lấy nếu số đủ dài (≥3 digits, hoặc nếu chỉ có 1 số thì lấy luôn)
            if (strlen($longest) >= 3 || count($sn_numbers[0]) == 1) {
                $extracted_sn_number = $longest;
                $sn_variants[] = $extracted_sn_number;
            }
        }
        
        // Nếu S/N là số thuần, thêm biến thể với/không có số 0 đứng đầu
        $numeric_sn = is_numeric($sn) ? $sn : $extracted_sn_number;
        if ($numeric_sn !== null) {
            $sn_num = (int)$numeric_sn;
            $sn_variants[] = (string)$sn_num; // Loại bỏ số 0 đầu (04 -> 4)
            $sn_variants[] = str_pad($numeric_sn, 2, '0', STR_PAD_LEFT); // Thêm 0 nếu cần (4 -> 04)
            $sn_variants[] = str_pad($numeric_sn, 3, '0', STR_PAD_LEFT); // 04 -> 004
            $sn_variants[] = str_pad($numeric_sn, 4, '0', STR_PAD_LEFT); // 04 -> 0004
        }
        
        // Loại bỏ duplicate
        $sn_variants = array_unique($sn_variants);
        
        // BƯỚC 0: Check Custom Rules trước (ưu tiên cao nhất)
        $result = null;
        $custom_rule_debug = null;
        $custom_rule = checkCustomRules($ten_thietbi, $sn);
        if ($custom_rule) {
            // Search in database using custom rule mapping
            $custom_sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'custom-rule' as match_type
                          FROM thietbi_iso 
                          WHERE mavt = :custom_mavt";
            
            if (!empty($custom_rule['db_sn'])) {
                $custom_sql .= " AND somay = :custom_sn";
            }
            $custom_sql .= " LIMIT 1";
            
            $stmt = $db->prepare($custom_sql);
            $stmt->bindValue(':custom_mavt', $custom_rule['db_mavt']);
            if (!empty($custom_rule['db_sn'])) {
                $stmt->bindValue(':custom_sn', $custom_rule['db_sn']);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $custom_rule_debug = [
                'matched_rule' => true,
                'db_mavt' => $custom_rule['db_mavt'],
                'db_sn' => $custom_rule['db_sn'],
                'note' => $custom_rule['note'],
                'found_in_db' => $result !== false
            ];
            
            if ($result) {
                $custom_rule_count++; // Track custom rule matches
            }
        }
        
        // Tạo placeholders động cho S/N variants
        $sn_placeholders = [];
        $sn_params = [];
        foreach ($sn_variants as $idx => $variant) {
            $placeholder = ":somay{$idx}";
            $sn_placeholders[] = "somay = $placeholder";
            $sn_params[$placeholder] = $variant;
        }
        $sn_condition = '(' . implode(' OR ', $sn_placeholders) . ')';
        
        // BƯỚC 1: Tìm kiếm EXACT MATCH (nếu custom rule chưa tìm thấy)
        if (!$result) {
            // Tên thiết bị so với: mavt, model, mamay
            // S/N so với: somay (với nhiều biến thể)
            $sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'exact' as match_type
                    FROM thietbi_iso 
                    WHERE $sn_condition
                    AND (
                        mavt = :ten1 OR mavt = :ten_latin1 OR
                        model = :ten2 OR model = :ten_latin2 OR
                        mamay = :ten3 OR mamay = :ten_latin3
                    )
                    LIMIT 1";
            
            $stmt = $db->prepare($sql);
            $params = array_merge($sn_params, [
                ':ten1' => $ten_thietbi,
                ':ten_latin1' => $ten_thietbi_latin,
                ':ten2' => $ten_thietbi,
                ':ten_latin2' => $ten_thietbi_latin,
                ':ten3' => $ten_thietbi,
                ':ten_latin3' => $ten_thietbi_latin
            ]);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // BƯỚC 2: Nếu không tìm thấy EXACT, thử FUZZY MATCH
        if (!$result && !empty($extracted_numbers)) {
            // Tìm theo Model (số cuối trong tên) + S/N
            // VD: BK3-76 -> Model có thể là 76
            $potential_model = end($extracted_numbers);
            
            $fuzzy_sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'fuzzy-model' as match_type
                         FROM thietbi_iso 
                         WHERE $sn_condition
                         AND (model = :potential_model OR model LIKE :model_like)
                         LIMIT 1";
            
            $stmt = $db->prepare($fuzzy_sql);
            $fuzzy_params = array_merge($sn_params, [
                ':potential_model' => $potential_model,
                ':model_like' => "%{$potential_model}%"
            ]);
            $stmt->execute($fuzzy_params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // BƯỚC 2.5: Nếu vẫn không tìm thấy, thử tìm theo mã thiết bị (tất cả phần chữ cái)
        // Điều này giúp tìm thấy khi model bị nhầm nhưng S/N và mã đúng
        // VD: AK-73 + 1023 -> tìm AK% + 1023 -> tìm thấy AK/76/1023
        // VD: BK3-76 + 1035 -> tìm BK% + 1035 -> tìm thấy BK35/76/1035
        // VD: Swivel- HDDS + 12457113 -> tìm %Swivel% OR %HDDS% + 12457113 -> tìm thấy HDDS/12457113
        // VD: Swivel MCSA-D + 10932066 -> tìm %Swivel% OR %MCSA-D% + 10932066 -> tìm thấy MCSA-D/10932066
        if (!$result) {
            // Trích xuất các code - kết hợp 2 pattern để bắt đầy đủ
            $code_parts = [];
            
            // Pattern 1: Mã có dấu gạch ngang hoặc số (MCSA-D, BK3, AK-73, D4GC-IC)
            preg_match_all('/[a-zA-Z][a-zA-Z0-9-]*/', $ten_thietbi_latin, $matches1);
            $code_parts = array_merge($code_parts, $matches1[0] ?? []);
            
            // Pattern 2: Các từ chữ cái thuần (Swivel, HDDS, GGK)
            preg_match_all('/[a-zA-Z]{2,}/', $ten_thietbi_latin, $matches2);
            $code_parts = array_merge($code_parts, $matches2[0] ?? []);
            
            // Mở rộng: với mỗi code có dấu gạch ngang hoặc hỗn hợp chữ-số, thêm các biến thể
            $expanded_parts = [];
            foreach ($code_parts as $part) {
                $expanded_parts[] = $part; // Giữ nguyên bản gốc
                
                // Nếu có dấu gạch ngang, tách ra
                if (strpos($part, '-') !== false) {
                    $sub_parts = explode('-', $part);
                    foreach ($sub_parts as $sub) {
                        if (strlen($sub) >= 2) {
                            $expanded_parts[] = $sub;
                        }
                    }
                }
                
                // Nếu bắt đầu bằng chữ và có số, extract prefix chữ-số đầu
                // VD: D4GC → D4, BK3 → BK, MCSA → MC (không cần vì MCSA toàn chữ)
                if (preg_match('/^([A-Z])(\d+)/', $part, $prefix_match)) {
                    $prefix = $prefix_match[1] . $prefix_match[2]; // D4
                    if (strlen($prefix) >= 2 && $prefix !== $part) {
                        $expanded_parts[] = $prefix;
                    }
                }
                
                // Với code ngắn toàn chữ (≤5 ký tự), thêm prefix để match các mã tương tự
                // VD: DLLT → DLL (để match DLLS, DLLT, DLLA...)
                // VD: HDDS → HDD (để match HDDS, HDDT...)
                if (preg_match('/^[A-Z]{3,5}$/', $part)) {
                    $len = strlen($part);
                    if ($len >= 3) {
                        $prefix = substr($part, 0, $len - 1); // Bỏ 1 ký tự cuối
                        if (!in_array($prefix, $expanded_parts)) {
                            $expanded_parts[] = $prefix;
                        }
                    }
                    if ($len >= 4) {
                        $prefix2 = substr($part, 0, $len - 2); // Bỏ 2 ký tự cuối
                        if (strlen($prefix2) >= 2 && !in_array($prefix2, $expanded_parts)) {
                            $expanded_parts[] = $prefix2;
                        }
                    }
                }
            }
            
            $code_parts = $expanded_parts;
            
            // Loại bỏ duplicate và lọc các phần có ít nhất 2 ký tự
            $code_parts = array_unique($code_parts);
            $code_parts = array_filter($code_parts, function($part) {
                return strlen($part) >= 2;
            });
            
            if (!empty($code_parts)) {
                $code_conditions = [];
                $code_params = $sn_params;
                $param_idx = 0;
                
                foreach ($code_parts as $part) {
                    $code_conditions[] = "mavt LIKE :code_mavt{$param_idx}";
                    $code_conditions[] = "mamay LIKE :code_mamay{$param_idx}";
                    $code_params[":code_mavt{$param_idx}"] = "%{$part}%";
                    $code_params[":code_mamay{$param_idx}"] = "%{$part}%";
                    $param_idx++;
                }
                
                $code_sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'fuzzy-code' as match_type
                            FROM thietbi_iso 
                            WHERE $sn_condition
                            AND (" . implode(' OR ', $code_conditions) . ")
                            LIMIT 1";
                
                $stmt = $db->prepare($code_sql);
                $stmt->execute($code_params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        // BƯỚC 3: Nếu vẫn không tìm thấy, thử tìm trong tenvt
        if (!$result) {
            // Tìm LIKE trong tên thiết bị
            // VD: BK3-76 -> tìm tenvt LIKE '%BK%76%'
            $search_parts = preg_split('/[^a-zA-Z0-9]+/', $ten_thietbi_latin, -1, PREG_SPLIT_NO_EMPTY);
            
            if (!empty($search_parts)) {
                $like_conditions = [];
                $like_params = $sn_params;
                foreach ($search_parts as $idx => $part) {
                    if (strlen($part) >= 2) { // Chỉ tìm các phần có ít nhất 2 ký tự
                        $like_conditions[] = "tenvt LIKE :part{$idx}";
                        $like_params[":part{$idx}"] = "%{$part}%";
                    }
                }
                
                if (!empty($like_conditions)) {
                    $like_sql = "SELECT stt, mavt, somay, model, mamay, tenvt, madv, 'fuzzy-name' as match_type
                                FROM thietbi_iso 
                                WHERE $sn_condition
                                AND (" . implode(' AND ', $like_conditions) . ")
                                LIMIT 1";
                    
                    $stmt = $db->prepare($like_sql);
                    $stmt->execute($like_params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
        } // End if (!$result) - close all fuzzy matching steps
        
        // Query ke_hoach_bao_duong_dinh_ky_iso to get ID (always run, regardless of device found or not)
        // Match BOTH ten_thietbi AND so_serial from search data (left side)
        $kehoach_id = '';
        try {
            $kehoach_sql = "SELECT id FROM ke_hoach_bao_duong_dinh_ky_iso 
                           WHERE ten_thietbi = :ten_thietbi 
                           AND so_serial = :somay 
                           LIMIT 1";
            $kehoach_stmt = $db->prepare($kehoach_sql);
            $kehoach_stmt->execute([
                ':ten_thietbi' => $ten_thietbi,
                ':somay' => $sn
            ]);
            $kehoach_row = $kehoach_stmt->fetch(PDO::FETCH_ASSOC);
            if ($kehoach_row) {
                $kehoach_id = $kehoach_row['id'];
            }
        } catch (PDOException $e) {
            // Silently handle if table doesn't exist or query fails
            error_log("Kehoach query error: " . $e->getMessage());
        }
        
        if ($result) {
            $found_count++;
            $match_type_label = [
                'exact' => '✓ Exact',
                'fuzzy-model' => '≈ Model Match',
                'fuzzy-code' => '≈ Code Match',
                'fuzzy-name' => '≈ Name Match',
                'custom-rule' => '🎯 Custom Rule'
            ];
            
            $results[] = [
                'mavt' => $ten_thietbi,
                'mavt_latin' => ($ten_thietbi !== $ten_thietbi_latin) ? $ten_thietbi_latin : '',
                'somay' => $sn,
                'stt' => $result['stt'],
                'tenvt' => $result['tenvt'],
                'madv' => $result['madv'],
                'mavt_db' => $result['mavt'],
                'model_db' => $result['model'] ?? '',
                'mamay_db' => $result['mamay'] ?? '',
                'somay_db' => $result['somay'],
                'sn_variants' => implode(', ', $sn_variants),
                'match_type' => $match_type_label[$result['match_type']] ?? '✓',
                'found' => true,
                'custom_rule_debug' => $custom_rule_debug,
                'kehoach_id' => $kehoach_id
            ];
        } else {
            $not_found_count++;
            $results[] = [
                'mavt' => $ten_thietbi,
                'mavt_latin' => ($ten_thietbi !== $ten_thietbi_latin) ? $ten_thietbi_latin : '',
                'somay' => $sn,
                'stt' => 'KHÔNG TÌM THẤY',
                'tenvt' => '',
                'madv' => '',
                'mavt_db' => '',
                'model_db' => '',
                'mamay_db' => '',
                'somay_db' => '',
                'sn_variants' => implode(', ', $sn_variants),
                'match_type' => 'X',
                'found' => false,
                'custom_rule_debug' => $custom_rule_debug,
                'kehoach_id' => $kehoach_id
            ];
        }
    }
    
    // Bắt đầu output HTML
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm STT thiết bị</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #e8e8e8; }
        .found { background-color: #d4edda; }
        .not-found { background-color: #f8d7da; }
        .summary { margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3; }
        small { color: #666; font-size: 11px; }
        td:first-child { text-align: center; font-weight: bold; }
        td:last-child { text-align: center; font-weight: bold; color: #2196F3; }
        .match-exact { color: #28a745; font-weight: bold; }
        .match-fuzzy-model { color: #ffc107; font-weight: bold; }
        .match-fuzzy-code { color: #ff9800; font-weight: bold; }
        .match-fuzzy-name { color: #17a2b8; font-weight: bold; }
        .match-notfound { color: #dc3545; font-weight: bold; }
        .sortable { cursor: pointer; user-select: none; position: relative; padding-right: 20px; }
        .sortable:hover { background-color: #45a049; }
        .sortable::after { content: '⇅'; position: absolute; right: 5px; opacity: 0.5; }
        .sortable.asc::after { content: '▲'; opacity: 1; }
        .sortable.desc::after { content: '▼'; opacity: 1; }
        
        /* Test Form Styles */
        .test-form { 
            background-color: #fff3cd; 
            border: 2px solid #ffc107; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 20px 0; 
        }
        .test-form h2 { 
            margin-top: 0; 
            color: #856404; 
            border-bottom: 2px solid #ffc107; 
            padding-bottom: 10px; 
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 15px; 
        }
        .form-group { 
            display: flex; 
            flex-direction: column; 
        }
        .form-group label { 
            font-weight: bold; 
            margin-bottom: 5px; 
            color: #333; 
        }
        .form-group input { 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            font-size: 14px; 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #2196F3; 
            box-shadow: 0 0 5px rgba(33, 150, 243, 0.3); 
        }
        .btn-test { 
            background-color: #2196F3; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: bold; 
        }
        .btn-test:hover { 
            background-color: #0b7dda; 
        }
        .btn-clear { 
            background-color: #6c757d; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
            margin-left: 10px; 
        }
        .btn-clear:hover { 
            background-color: #545b62; 
        }
        .test-result { 
            margin-top: 15px; 
            padding: 15px; 
            background-color: #f8f9fa; 
            border-radius: 4px; 
            border-left: 4px solid #28a745; 
        }
        .test-result.fail { 
            border-left-color: #dc3545; 
        }
        .debug-info { 
            background-color: #e9ecef; 
            padding: 10px; 
            border-radius: 4px; 
            margin-top: 10px; 
            font-size: 12px; 
            font-family: monospace; 
        }
        .btn-save-rule { 
            background-color: #28a745; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 13px; 
            margin-top: 10px; 
        }
        .btn-save-rule:hover { 
            background-color: #218838; 
        }
        .btn-delete-rule { 
            background-color: #dc3545; 
            color: white; 
            padding: 4px 10px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
            font-size: 11px; 
            float: right; 
            margin-left: 10px; 
        }
        .btn-delete-rule:hover { 
            background-color: #c82333; 
        }
        .save-message { 
            padding: 10px 15px; 
            margin: 15px 0; 
            background-color: #d4edda; 
            border-left: 4px solid #28a745; 
            border-radius: 4px; 
            color: #155724; 
        }
        .save-message.warning { 
            background-color: #fff3cd; 
            border-left-color: #ffc107; 
            color: #856404; 
        }
        .save-message.error { 
            background-color: #f8d7da; 
            border-left-color: #dc3545; 
            color: #721c24; 
        }
        .custom-rules-list { 
            background-color: #e7f3ff; 
            padding: 15px; 
            border-radius: 4px; 
            margin-top: 15px; 
            max-height: 300px; 
            overflow-y: auto; 
        }
        .custom-rules-list h3 { 
            margin-top: 0; 
            color: #0056b3; 
        }
        .rule-item { 
            background-color: white; 
            padding: 8px 12px; 
            margin: 5px 0; 
            border-radius: 4px; 
            border-left: 3px solid #2196F3; 
            font-size: 13px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
        }
        .rule-content { 
            flex: 1; 
        }
        .debug-file-info {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
        }
        .debug-file-info strong {
            color: #856404;
        }
        .rule-item strong { 
            color: #0056b3; 
        }
        .match-custom-rule { 
            color: #6f42c1; 
            font-weight: bold; 
        }
    </style>
</head>
<body>
    <h1>Kết quả tìm kiếm STT thiết bị</h1>
    
    <!-- Quick Add Custom Rule Form -->
    <div class="test-form" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
        <h2 style="color: white; margin-top: 0;">⚡ Quick Add Custom Rule</h2>
        <p style="color: #f0f0f0; font-size: 13px; margin-bottom: 15px;">
            <strong>Tạo nhanh custom rule:</strong> Mapping từ giá trị trong <strong>danh sách 370 items</strong> (có thể sai/typo) 
            → giá trị <strong>thực tế trong database</strong>
        </p>
        <form method="POST" action="" style="background: white; padding: 15px; border-radius: 8px;">
            <div style="background: #e7f3ff; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid #007bff;">
                <strong style="color: #0056b3;">💡 Ví dụ mapping:</strong><br>
                <div style="display: flex; align-items: center; margin-top: 8px; gap: 10px;">
                    <code style="background: #fff3cd; padding: 6px 10px; border-radius: 4px; font-weight: bold;">HRAS + 11013</code>
                    <span style="font-size: 20px; color: #28a745;">➜</span>
                    <code style="background: #d4edda; padding: 6px 10px; border-radius: 4px; font-weight: bold;">HRAS-IC + 1013</code>
                </div>
                <small style="color: #666; display: block; margin-top: 6px;">
                    Danh sách có "HRAS + 11013" → Database có "HRAS-IC + 1013"
                </small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="quick_search_mavt" style="color: #856404; font-weight: bold; background: #fff3cd; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                        📋 Từ Danh Sách (Search):
                    </label>
                    <input type="text" id="quick_search_mavt" name="rule_search_mavt" 
                           placeholder="VD: HRAS, DLLT, NGRT" 
                           required
                           style="border: 2px solid #ffc107;">
                    <small style="color: #856404;">Giá trị trong danh sách 370 items (có thể sai/typo)</small>
                </div>
                <div class="form-group">
                    <label for="quick_db_mavt" style="color: #155724; font-weight: bold; background: #d4edda; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                        🎯 Vào Database (Target):
                    </label>
                    <input type="text" id="quick_db_mavt" name="rule_db_mavt" 
                           placeholder="VD: HRAS-IC, DLLS, NGRT" 
                           required
                           style="border: 2px solid #28a745;">
                    <small style="color: #155724;">Giá trị thực tế trong database</small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="quick_search_sn" style="color: #856404; font-weight: bold; background: #fff3cd; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                        📋 S/N Danh Sách:
                    </label>
                    <input type="text" id="quick_search_sn" name="rule_search_sn" 
                           placeholder="VD: 11013, 197, 199"
                           style="border: 2px solid #ffc107;">
                    <small style="color: #856404;">S/N trong danh sách (VD: 11013)</small>
                </div>
                <div class="form-group">
                    <label for="quick_db_sn" style="color: #155724; font-weight: bold; background: #d4edda; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                        🎯 S/N Database:
                    </label>
                    <input type="text" id="quick_db_sn" name="rule_db_sn" 
                           placeholder="VD: 1013, A197, A199"
                           style="border: 2px solid #28a745;">
                    <small style="color: #155724;">S/N thực tế trong DB (VD: 1013)</small>
                </div>
            </div>
            <div class="form-group">
                <label for="quick_note">Ghi chú:</label>
                <input type="text" id="quick_note" name="rule_note" 
                       placeholder="VD: HRAS typo, NGRT pattern, manual mapping"
                       value="Quick manual add">
            </div>
            <button type="submit" name="save_rule" value="1" class="btn-save-rule" style="background: #667eea; width: 100%; padding: 12px;">
                ⚡ Lưu Rule: Danh Sách ➜ Database
            </button>
            <small style="display: block; margin-top: 8px; color: #dc3545; text-align: center; font-weight: bold;">
                ⚠️ CHÚ Ý: Bên TRÁI (màu vàng) = giá trị TÌM, Bên PHẢI (màu xanh) = giá trị MỤC TIÊU
            </small>
        </form>
    </div>
    
    <!-- Test Form -->
    <div class="test-form">
        <h2>🧪 Test & Tạo Custom Rule</h2>
        <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
            <strong>Hướng dẫn:</strong> Điền giá trị <strong style="color: #dc3545;">MỤC TIÊU</strong> (có trong DB) ở bên trái, 
            giá trị <strong style="color: #007bff;">TÌM KIẾM</strong> (từ danh sách) ở bên phải. Nếu tìm thấy → Lưu custom rule để map.
        </p>
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="test_db_mavt" style="color: #dc3545; font-weight: bold;">🎯 Mã VT Mục Tiêu (trong Database):</label>
                    <input type="text" id="test_db_mavt" name="test_db_mavt" 
                           placeholder="VD: HRAS-IC, DLLS, D4C" 
                           value="<?php echo htmlspecialchars($_GET['test_db_mavt'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="test_search" style="color: #007bff; font-weight: bold;">🔍 Mã VT Tìm Kiếm (từ danh sách):</label>
                    <input type="text" id="test_search" name="test_search" 
                           placeholder="VD: HRAS, DLLT, D4GC-IC" 
                           value="<?php echo htmlspecialchars($_GET['test_search'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="test_db_sn" style="color: #dc3545; font-weight: bold;">🎯 S/N Mục Tiêu (trong Database):</label>
                    <input type="text" id="test_db_sn" name="test_db_sn" 
                           placeholder="VD: 1013, 1204, 04" 
                           value="<?php echo htmlspecialchars($_GET['test_db_sn'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="test_sn" style="color: #007bff; font-weight: bold;">🔍 S/N Tìm Kiếm (từ danh sách):</label>
                    <input type="text" id="test_sn" name="test_sn" 
                           placeholder="VD: 11013, SWIVEL-12457113" 
                           value="<?php echo htmlspecialchars($_GET['test_sn'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <button type="submit" class="btn-test">🔍 Test Tìm Kiếm</button>
                <button type="button" class="btn-clear" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'">🗑️ Clear</button>
            </div>
        </form>
        
        <?php if ($test_result !== null): ?>
        <div class="test-result <?php echo $test_result['found'] ? '' : 'fail'; ?>">
            <strong>Kết quả Test:</strong><br>
            <strong>Input:</strong> "<?php echo htmlspecialchars($test_result['search']); ?>" + "<?php echo htmlspecialchars($test_result['sn']); ?>"<br>
            
            <?php if (!empty($test_result['db_mavt']) || !empty($test_result['db_sn'])): ?>
            <strong>Mong đợi tìm thấy:</strong> 
            <?php if (!empty($test_result['db_mavt'])): ?>
                mavt = "<?php echo htmlspecialchars($test_result['db_mavt']); ?>"
            <?php endif; ?>
            <?php if (!empty($test_result['db_sn'])): ?>
                somay = "<?php echo htmlspecialchars($test_result['db_sn']); ?>"
            <?php endif; ?>
            <br>
            <?php endif; ?>
            
            <?php if ($test_result['found']): ?>
                <span style="color: green; font-weight: bold;">✓ TÌM THẤY</span> 
                (Type: <strong><?php echo htmlspecialchars($test_result['match_type']); ?></strong>)
                <?php if (!empty($test_result['custom_rule_match'])): ?>
                    <span style="color: #6f42c1; font-weight: bold;">🎯 Custom Rule</span>
                    <?php if (!empty($test_result['custom_rule_note'])): ?>
                        <br><em>Note: <?php echo htmlspecialchars($test_result['custom_rule_note']); ?></em>
                    <?php endif; ?>
                <?php endif; ?>
                <br>
                <strong>Record:</strong> mavt=<?php echo htmlspecialchars($test_result['result']['mavt']); ?>, 
                somay=<?php echo htmlspecialchars($test_result['result']['somay']); ?>, 
                stt=<?php echo htmlspecialchars($test_result['result']['stt']); ?><br>
                <strong>Tên:</strong> <?php echo htmlspecialchars($test_result['result']['tenvt']); ?>
                
                <!-- Save as Custom Rule Form (only if not already a custom rule) -->
                <?php if ($test_result['match_type'] !== 'custom-rule'): ?>
                <?php
                // Use user-provided DB values (left side) if available, otherwise use search result
                $rule_db_mavt = !empty($test_result['db_mavt']) ? $test_result['db_mavt'] : $test_result['result']['mavt'];
                $rule_db_sn = !empty($test_result['db_sn']) ? $test_result['db_sn'] : $test_result['result']['somay'];
                ?>
                <form method="POST" action="" style="margin-top: 15px; padding-top: 15px; border-top: 2px dashed #28a745;">
                    <strong>💾 Lưu làm Custom Rule để tìm nhanh hơn lần sau:</strong>
                    
                    <!-- Manual Override Option -->
                    <div style="margin: 10px 0; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="manual_mode" onchange="toggleManualMode()">
                            <strong>✏️ Manual Mode</strong> - Tự nhập DB mavt/somay (override tất cả)
                        </label>
                        <small style="color: #856404; display: block; margin-top: 5px;">
                            Mặc định: Dùng giá trị <strong>bạn điền ở bên trái</strong> (DB fields). Tick để override manual.
                        </small>
                    </div>
                    
                    <!-- Preview box -->
                    <div style="background: #e7f3ff; padding: 12px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #007bff;">
                        <strong>🔍 Preview Rule sẽ lưu:</strong><br>
                        <code style="background: white; padding: 4px 8px; border-radius: 3px; display: inline-block; margin-top: 5px;">
                            <?php echo htmlspecialchars($test_result['search']); ?>
                            <?php if (!empty($test_result['sn'])): ?>
                                + <?php echo htmlspecialchars($test_result['sn']); ?>
                            <?php endif; ?>
                        </code>
                        <strong style="color: #007bff;"> → </strong>
                        <code id="preview_target" style="background: white; padding: 4px 8px; border-radius: 3px; display: inline-block;">
                            <?php echo htmlspecialchars($rule_db_mavt); ?>
                            <?php if (!empty($rule_db_sn)): ?>
                                + <?php echo htmlspecialchars($rule_db_sn); ?>
                            <?php endif; ?>
                        </code>
                    </div>
                    
                    <input type="hidden" name="rule_search_mavt" value="<?php echo htmlspecialchars($test_result['search']); ?>">
                    <input type="hidden" name="rule_search_sn" value="<?php echo htmlspecialchars($test_result['sn']); ?>">
                    
                    <!-- DB fields - toggle between hidden and visible -->
                    <div id="manual_inputs" style="display: none; background: #f8f9fa; padding: 12px; border-radius: 4px; margin: 10px 0;">
                        <div style="margin-bottom: 8px;">
                            <label><strong>DB Mã VT:</strong></label>
                            <input type="text" id="manual_db_mavt" 
                                   value="<?php echo htmlspecialchars($rule_db_mavt); ?>"
                                   onkeyup="updatePreview()"
                                   style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px;">
                        </div>
                        <div>
                            <label><strong>DB S/N:</strong></label>
                            <input type="text" id="manual_db_sn" 
                                   value="<?php echo htmlspecialchars($rule_db_sn); ?>"
                                   onkeyup="updatePreview()"
                                   style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <input type="hidden" id="hidden_db_mavt" name="rule_db_mavt" value="<?php echo htmlspecialchars($rule_db_mavt); ?>">
                    <input type="hidden" id="hidden_db_sn" name="rule_db_sn" value="<?php echo htmlspecialchars($rule_db_sn); ?>">
                    
                    <div class="form-group" style="margin-top: 10px;">
                        <label for="rule_note">Ghi chú (tùy chọn):</label>
                        <input type="text" id="rule_note" name="rule_note" 
                               placeholder="VD: DLLT typo → DLLS, <?php echo htmlspecialchars($test_result['match_type']); ?> match" 
                               value="Match type: <?php echo htmlspecialchars($test_result['match_type']); ?>"
                               style="width: 100%;">
                    </div>
                    <button type="submit" name="save_rule" value="1" class="btn-save-rule">
                        💾 Lưu Rule
                    </button>
                    <small style="display: block; margin-top: 8px; color: #666;">
                        Lần sau tìm "<?php echo htmlspecialchars($test_result['search']); ?>" sẽ map trực tiếp theo custom rule (bỏ qua fuzzy matching)
                    </small>
                    
                    <script>
                    function toggleManualMode() {
                        const checkbox = document.getElementById('manual_mode');
                        const manualInputs = document.getElementById('manual_inputs');
                        manualInputs.style.display = checkbox.checked ? 'block' : 'none';
                        updatePreview();
                    }
                    
                    function updatePreview() {
                        const manualMode = document.getElementById('manual_mode').checked;
                        const manualMavt = document.getElementById('manual_db_mavt').value;
                        const manualSn = document.getElementById('manual_db_sn').value;
                        const autoMavt = '<?php echo addslashes(htmlspecialchars($rule_db_mavt)); ?>';
                        const autoSn = '<?php echo addslashes(htmlspecialchars($rule_db_sn)); ?>';
                        
                        const mavt = manualMode ? manualMavt : autoMavt;
                        const sn = manualMode ? manualSn : autoSn;
                        
                        // Update hidden fields
                        document.getElementById('hidden_db_mavt').value = mavt;
                        document.getElementById('hidden_db_sn').value = sn;
                        
                        // Update preview
                        let previewText = mavt;
                        if (sn) previewText += ' + ' + sn;
                        document.getElementById('preview_target').textContent = previewText;
                    }
                    </script>
                </form>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: red; font-weight: bold;">✗ KHÔNG TÌM THẤY</span>
            <?php endif; ?>
            
            <?php if (!empty($test_result['debug'])): ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Latin: <?php echo htmlspecialchars($test_result['debug']['latin'] ?? ''); ?><br>
                S/N Variants: <?php echo htmlspecialchars(implode(', ', $test_result['debug']['sn_variants'] ?? [])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Debug File Info -->
        <div class="debug-file-info">
            <strong>🔧 Custom Rules File Debug Info:</strong><br>
            File: <code><?php echo CUSTOM_RULES_FILE; ?></code><br>
            File exists: <strong style="color: <?php echo file_exists(CUSTOM_RULES_FILE) ? 'green' : 'red'; ?>">
                <?php echo file_exists(CUSTOM_RULES_FILE) ? '✓ Yes' : '✗ No (file chưa được tạo)'; ?>
            </strong><br>
            <?php if (file_exists(CUSTOM_RULES_FILE)): ?>
            File size: <?php echo filesize(CUSTOM_RULES_FILE); ?> bytes<br>
            Last modified: <?php echo date('Y-m-d H:i:s', filemtime(CUSTOM_RULES_FILE)); ?><br>
            File writable: <strong style="color: <?php echo is_writable(CUSTOM_RULES_FILE) ? 'green' : 'red'; ?>">
                <?php echo is_writable(CUSTOM_RULES_FILE) ? '✓ Yes' : '✗ No'; ?>
            </strong><br>
            <?php endif; ?>
            Directory: <code><?php echo dirname(CUSTOM_RULES_FILE); ?></code><br>
            Directory writable: <strong style="color: <?php echo is_writable(dirname(CUSTOM_RULES_FILE)) ? 'green' : 'red'; ?>">
                <?php echo is_writable(dirname(CUSTOM_RULES_FILE)) ? '✓ Yes' : '✗ No'; ?>
            </strong><br>
            Total rules loaded: <strong><?php echo count($all_custom_rules['rules']); ?></strong>
            <br><br>
            <details>
                <summary style="cursor: pointer; color: #0056b3; font-weight: bold;">📄 Xem tất cả rules (Raw JSON)</summary>
                <pre style="background: white; padding: 10px; border-radius: 4px; margin-top: 10px; max-height: 400px; overflow-y: auto; font-size: 11px;"><?php 
                if (file_exists(CUSTOM_RULES_FILE)) {
                    echo htmlspecialchars(file_get_contents(CUSTOM_RULES_FILE)); 
                } else {
                    echo 'File không tồn tại';
                }
                ?></pre>
                <?php if (!empty($all_custom_rules['rules'])): ?>
                <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                    <strong>Rules chi tiết:</strong><br>
                    <?php foreach ($all_custom_rules['rules'] as $idx => $r): ?>
                        <div style="margin: 8px 0; padding: 8px; background: #f8f9fa; border-left: 3px solid #007bff; font-size: 12px;">
                            <strong>Rule #<?php echo $idx + 1; ?>:</strong><br>
                            &nbsp;&nbsp;Search: <code><?php echo htmlspecialchars($r['search_mavt']); ?></code>
                            <?php if (!empty($r['search_sn'])): ?>
                                + <code style="color: #e83e8c;"><?php echo htmlspecialchars($r['search_sn']); ?></code>
                            <?php else: ?>
                                <em style="color: #999;">(no S/N)</em>
                            <?php endif; ?>
                            <br>
                            &nbsp;&nbsp;DB: <code><?php echo htmlspecialchars($r['db_mavt']); ?></code>
                            <?php if (!empty($r['db_sn'])): ?>
                                + <code style="color: #28a745;"><?php echo htmlspecialchars($r['db_sn']); ?></code>
                            <?php else: ?>
                                <em style="color: #999;">(no S/N)</em>
                            <?php endif; ?>
                            <?php if (!empty($r['note'])): ?>
                                <br>&nbsp;&nbsp;Note: <em style="color: #666;"><?php echo htmlspecialchars($r['note']); ?></em>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </details>
        </div>
        
        <!-- Custom Rules List -->
        <?php if (!empty($all_custom_rules['rules'])): ?>
        <div class="custom-rules-list">
            <h3>📋 Custom Mapping Rules (<?php echo count($all_custom_rules['rules']); ?>)</h3>
            <?php 
            $total_rules = count($all_custom_rules['rules']);
            $reversed_rules = array_reverse($all_custom_rules['rules'], true);
            $display_count = 0;
            foreach ($reversed_rules as $original_index => $rule): 
                if ($display_count >= 10) break;
                $display_count++;
            ?>
            <div class="rule-item">
                <div class="rule-content">
                    <strong><?php echo htmlspecialchars($rule['search_mavt']); ?></strong>
                    <?php if (!empty($rule['search_sn'])): ?>
                        + <?php echo htmlspecialchars($rule['search_sn']); ?>
                    <?php endif; ?>
                    → 
                    <strong style="color: #28a745;"><?php echo htmlspecialchars($rule['db_mavt']); ?></strong>
                    <?php if (!empty($rule['db_sn'])): ?>
                        + <?php echo htmlspecialchars($rule['db_sn']); ?>
                    <?php endif; ?>
                    <?php if (!empty($rule['note'])): ?>
                        <em style="color: #666;">(<?php echo htmlspecialchars($rule['note']); ?>)</em>
                    <?php endif; ?>
                    <small style="color: #999;"><?php echo htmlspecialchars($rule['created_at'] ?? ''); ?></small>
                </div>
                <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Xác nhận xóa rule này?');">
                    <input type="hidden" name="delete_rule" value="1">
                    <input type="hidden" name="rule_index" value="<?php echo $original_index; ?>">
                    <button type="submit" class="btn-delete-rule" title="Xóa rule">🗑️</button>
                </form>
            </div>
            <?php endforeach; ?>
            <?php if (count($all_custom_rules['rules']) > 10): ?>
                <small style="color: #666;">Hiển thị 10 rules gần nhất / Tổng <?php echo count($all_custom_rules['rules']); ?> rules</small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($save_message): ?>
    <div class="save-message <?php 
        if (strpos($save_message, 'CẢNH BÁO') !== false) echo 'warning';
        elseif (strpos($save_message, 'Lỗi') !== false) echo 'error';
    ?>">
        <?php echo $save_message; // Allow HTML since it's server-generated ?>
    </div>
    <?php endif; ?>
    
    <div class="summary">
        <strong>Tổng kết:</strong><br>
        Tổng số thiết bị cần tìm: <?php echo count($thietbi_list); ?><br>
        Tìm thấy: <span style="color: green;"><?php echo $found_count; ?></span>
        <?php if ($custom_rule_count > 0): ?>
            <span style="color: #6f42c1; font-weight: bold;">(🎯 <?php echo $custom_rule_count; ?> qua Custom Rules)</span>
        <?php endif; ?>
        <br>
        Không tìm thấy: <span style="color: red;"><?php echo $not_found_count; ?></span><br>
        <?php if (!empty($all_custom_rules['rules'])): ?>
        <br>
        <span style="color: #0056b3;">📋 Đã load <?php echo count($all_custom_rules['rules']); ?> custom rules</span>
        <?php endif; ?>
        <br>
        <small><strong>Lưu ý:</strong> Tìm kiếm đa cấp linh hoạt:<br>
        • <strong>🎯 Custom Rule</strong>: Mapping được lưu thủ công (ưu tiên cao nhất)<br>
        &nbsp;&nbsp;→ <strong>🔮 Smart Pattern:</strong> Tự động áp dụng pattern prefix/suffix (VD: rule "197→A197" sẽ tự động match "199→A199")<br>
        • <strong>✓ Exact</strong>: Khớp chính xác (mavt/model/mamay)<br>
        • <strong>≈ Model Match</strong>: Khớp dựa vào model (trích xuất số từ tên TB)<br>
        • <strong>≈ Code Match</strong>: Khớp dựa vào mã TB (tìm tất cả code có trong tên: AK, BK3, MCSA-D, Swivel, HDDS...)<br>
        • <strong>≈ Name Match</strong>: Khớp tương đối (tìm từ tương đồng trong tên TB)<br>
        • S/N tự động trích xuất số: "SWIVEL-12457113" → [SWIVEL-12457113, 12457113], "GGK 04" → [GGK 04, 04, 4]<br>
        • S/N tự động so sánh nhiều biến thể: "4" = "04" = "004" = "0004"<br>
        • Tự động chuyển đổi Cyrillic → Latin (БК3 → BK3, ГГК → GGK)<br>
        <br>
        💡 <strong>Click vào header "Type"</strong> để sắp xếp kết quả theo loại khớp</small>
    </div>
    
    <table id="mainTable">
        <thead>
            <tr>
                <th rowspan="2">STT</th>
                <th colspan="3">Tìm kiếm</th>
                <th colspan="4">Database</th>
                <th rowspan="2">Tên TB</th>
                <th rowspan="2">Đơn vị</th>
                <th rowspan="2">STT DB</th>
                <th rowspan="2">ID KH</th>
            </tr>
            <tr>
                <th>Tên TB</th>
                <th>S/N</th>
                <th class="sortable" onclick="sortTable(2)">Type</th>
                <th>mavt</th>
                <th>model</th>
                <th>mamay</th>
                <th>somay</th>
            </tr>
        </thead>
        <tbody id="mainTableBody">
    <?php
    $index = 1;
    foreach ($results as $result):
        $row_class = $result['found'] ? 'found' : 'not-found';
    ?>
        <tr class="<?php echo $row_class; ?>">
            <td><?php echo $index; ?></td>
            <td>
                <?php echo htmlspecialchars($result['mavt']); ?>
                <?php if (!empty($result['mavt_latin'])): ?>
                    <br><small style="color: #666;">(<?php echo htmlspecialchars($result['mavt_latin']); ?>)</small>
                <?php endif; ?>
            </td>
            <td>
                <?php echo htmlspecialchars($result['somay']); ?>
                <?php if ($result['sn_variants'] !== $result['somay']): ?>
                    <br><small style="color: #999;" title="Các biến thể đã tìm">🔍 <?php echo htmlspecialchars($result['sn_variants']); ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align: center; font-size: 11px;">
                <?php 
                $match_class = 'match-notfound';
                if (strpos($result['match_type'], 'Custom') !== false) {
                    $match_class = 'match-custom-rule';
                } elseif (strpos($result['match_type'], 'Exact') !== false) {
                    $match_class = 'match-exact';
                } elseif (strpos($result['match_type'], 'Model') !== false) {
                    $match_class = 'match-fuzzy-model';
                } elseif (strpos($result['match_type'], 'Code') !== false) {
                    $match_class = 'match-fuzzy-code';
                } elseif (strpos($result['match_type'], 'Name') !== false) {
                    $match_class = 'match-fuzzy-name';
                }
                ?>
                <span class="<?php echo $match_class; ?>"><?php echo htmlspecialchars($result['match_type']); ?></span>
                <?php if (!empty($result['custom_rule_debug'])): ?>
                    <br><small style="font-size: 9px; color: #6c757d; background: #f8f9fa; padding: 2px 5px; border-radius: 3px; display: inline-block; margin-top: 3px;">
                        🔍 Rule: <?php echo htmlspecialchars($result['custom_rule_debug']['db_mavt']); ?>
                        <?php if (!empty($result['custom_rule_debug']['db_sn'])): ?>
                            +<?php echo htmlspecialchars($result['custom_rule_debug']['db_sn']); ?>
                        <?php endif; ?>
                        <?php if (!$result['custom_rule_debug']['found_in_db']): ?>
                            <br><span style="color: #dc3545;">⚠ Matched rule but NOT in DB!</span>
                        <?php endif; ?>
                    </small>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($result['mavt_db']); ?></td>
            <td><?php echo htmlspecialchars($result['model_db']); ?></td>
            <td><?php echo htmlspecialchars($result['mamay_db']); ?></td>
            <td><?php echo htmlspecialchars($result['somay_db']); ?></td>
            <td><?php echo htmlspecialchars($result['tenvt']); ?></td>
            <td><?php echo htmlspecialchars($result['madv']); ?></td>
            <td><strong><?php echo htmlspecialchars($result['stt']); ?></strong></td>
            <td><?php echo !empty($result['kehoach_id']) ? '<strong style="color: #28a745;">' . htmlspecialchars($result['kehoach_id']) . '</strong>' : '<span style="color: #999;">-</span>'; ?></td>
        </tr>
    <?php
        $index++;
    endforeach;
    ?>
        </tbody>
    </table>
    
    <h2 style="margin-top: 30px;">Danh sách không tìm thấy</h2>
    <p style="color: #666; font-size: 13px; margin-bottom: 10px;">
        <strong>Lưu ý:</strong> Các thiết bị dưới đây không tìm thấy trong database ngay cả khi so sánh:<br>
        • Tên thiết bị với: mavt, model, mamay (cả Cyrillic và Latin)<br>
        • S/N với: somay (tất cả biến thể 4, 04, 004)
    </p>
    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Mã VT</th>
                <th>S/N</th>
            </tr>
        </thead>
        <tbody>
    <?php
    $not_found_index = 1;
    foreach ($results as $result):
        if (!$result['found']):
    ?>
        <tr>
            <td><?php echo $not_found_index; ?></td>
            <td>
                <?php echo htmlspecialchars($result['mavt']); ?>
                <?php if (!empty($result['mavt_latin'])): ?>
                    <br><small style="color: #666;">(Latin: <?php echo htmlspecialchars($result['mavt_latin']); ?>)</small>
                <?php endif; ?>
            </td>
            <td>
                <?php echo htmlspecialchars($result['somay']); ?>
                <?php if ($result['sn_variants'] !== $result['somay']): ?>
                    <br><small style="color: #999;">Đã thử: <?php echo htmlspecialchars($result['sn_variants']); ?></small>
                <?php endif; ?>
            </td>
        </tr>
    <?php
            $not_found_index++;
        endif;
    endforeach;
    ?>
        </tbody>
    </table>
    
    <script>
    let sortDirection = 'asc';
    
    function getMatchTypePriority(matchType) {
        // Priority: Custom Rule=0, Exact=1, Model=2, Code=3, Name=4, Not found=5
        if (matchType.includes('Custom')) return 0;
        if (matchType.includes('Exact')) return 1;
        if (matchType.includes('Model')) return 2;
        if (matchType.includes('Code')) return 3;
        if (matchType.includes('Name')) return 4;
        return 5; // X - not found
    }
    
    function sortTable(columnIndex) {
        const table = document.getElementById('mainTableBody');
        const rows = Array.from(table.getElementsByTagName('tr'));
        
        // Toggle sort direction
        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        
        // Update header indicator
        const headers = document.querySelectorAll('.sortable');
        headers.forEach(h => {
            h.classList.remove('asc', 'desc');
        });
        headers[0].classList.add(sortDirection);
        
        // Sort rows
        rows.sort((a, b) => {
            // Get Type column text (columnIndex+1 because first column is STT)
            const cellA = a.cells[columnIndex + 1];
            const cellB = b.cells[columnIndex + 1];
            
            if (!cellA || !cellB) return 0;
            
            const textA = cellA.textContent.trim();
            const textB = cellB.textContent.trim();
            
            const priorityA = getMatchTypePriority(textA);
            const priorityB = getMatchTypePriority(textB);
            
            const comparison = priorityA - priorityB;
            return sortDirection === 'asc' ? comparison : -comparison;
        });
        
        // Re-append rows in new order and update STT
        rows.forEach((row, index) => {
            row.cells[0].textContent = index + 1; // Update STT
            table.appendChild(row);
        });
    }
    </script>
</body>
</html>
<?php
    
} catch (PDOException $e) {
    echo "<h1>Lỗi Database</h1>";
    echo "<p>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<h1>Lỗi</h1>";
    echo "<p>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
