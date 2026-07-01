<?php
/**
 * Import nhân viên + hồ sơ + policy vật tư từ Excel
 * Yêu cầu:
 * - Có bảng: bhld_nhanvien, bhld_nhanvien_hoso, bhld_nhanvien_vattu_dm, bhld_dmvattu
 * - Có composer package phpoffice/phpspreadsheet (cho .xlsx)
 * 
 * Cách dùng:
 * 1) Truy cập script bằng trình duyệt
 * 2) Chọn file Excel theo mẫu cột
 * 3) Nhập mapb và (tuỳ chọn) dinhmuc chung
 * 4) Bấm Import
 */

require_once 'config_bhld.php';

@ini_set('display_errors', '1');
@error_reporting(E_ALL);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function vn_norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a',
        'â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e',
        'ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o',
        'ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u',
        'ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d'
    ];
    $s = strtr($s, $map);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

function to_nullable_string($v) {
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

function to_non_negative_int_or_null($v) {
    $v = trim((string)$v);
    if ($v === '') return null;
    if (!is_numeric($v)) return null;
    $n = (int)$v;
    return $n < 0 ? null : $n;
}

function load_rows_from_file($tmpPath, $originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $rows = [];

    if ($ext === 'csv') {
        if (($fp = fopen($tmpPath, 'r')) === false) {
            throw new Exception('Không mở được file CSV');
        }
        while (($data = fgetcsv($fp, 0, ',')) !== false) {
            $rows[] = $data;
        }
        fclose($fp);
        return $rows;
    }

    if ($ext === 'xlsx' || $ext === 'xls') {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new Exception('Thiếu vendor/autoload.php. Cài thư viện: composer require phpoffice/phpspreadsheet');
        }
        require_once $autoload;
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpPath);
        $spreadsheet = $reader->load($tmpPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        return $rows;
    }

    throw new Exception('Định dạng file không hỗ trợ. Chỉ nhận .xlsx, .xls, .csv');
}

function ensure_equipment_by_aliases($conn) {
    $result = mysqli_query($conn, "SELECT mavt, tenvt, dvt FROM bhld_dmvattu");
    if (!$result) throw new Exception('Không đọc được danh mục vật tư: ' . mysqli_error($conn));

    $equip = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $equip[] = $r;
    }

    $aliasMap = [
        'giay' => ['giay bao ho', 'giay', 'ung'],
        'quan_ao' => ['quan ao', 'ao quan', 'bao ho quan ao'],
        'mu' => ['mu bao ho', 'mu'],
        'kinh' => ['kinh bao ho', 'kinh'],
        'gang_tay' => ['gang tay bao ho', 'gang tay'],
        'khau_trang' => ['khau trang'],
        'ao_mua' => ['ao mua'],
        'phin_loc' => ['phin loc khi doc', 'phin loc', 'phin loc doc'],
        'ao_phao' => ['ao phao cuu sinh', 'ao phao'],
        'nut_tai' => ['nut bit tai chong on', 'nut tai chong on', 'nut bit tai'],
        'gang_tay_han' => ['gang tay da tho han', 'gang tay han', 'gang tay da han'],
    ];

    $preferredName = [
        'giay' => 'Giày bảo hộ',
        'quan_ao' => 'Quần áo bảo hộ',
        'mu' => 'Mũ bảo hộ',
        'kinh' => 'Kính bảo hộ',
        'gang_tay' => 'Găng tay bảo hộ',
        'khau_trang' => 'Khẩu trang',
        'ao_mua' => 'Áo mưa',
        'phin_loc' => 'Phin lọc khí độc',
        'ao_phao' => 'Áo phao cứu sinh',
        'nut_tai' => 'Nút bịt tai chống ồn',
        'gang_tay_han' => 'Găng tay da thợ hàn',
    ];

    $preferredDvt = [
        'giay' => 'đôi',
        'quan_ao' => 'bộ',
        'mu' => 'cái',
        'kinh' => 'cái',
        'gang_tay' => 'đôi',
        'khau_trang' => 'cái',
        'ao_mua' => 'cái',
        'phin_loc' => 'cái',
        'ao_phao' => 'cái',
        'nut_tai' => 'đôi',
        'gang_tay_han' => 'đôi',
    ];

    $findByAlias = function($aliases) use ($equip) {
        foreach ($equip as $e) {
            $name = vn_norm($e['tenvt']);
            foreach ($aliases as $a) {
                if (strpos($name, vn_norm($a)) !== false) {
                    return (int)$e['mavt'];
                }
            }
        }
        return null;
    };

    $resolve = [];
    foreach ($aliasMap as $key => $aliases) {
        $mavt = $findByAlias($aliases);
        if ($mavt === null) {
            $q = mysqli_query($conn, "SELECT IFNULL(MAX(mavt),100) + 1 AS next_id FROM bhld_dmvattu");
            if (!$q) throw new Exception('Không lấy được mã vật tư mới: ' . mysqli_error($conn));
            $next = (int)mysqli_fetch_assoc($q)['next_id'];

            $ten = mysqli_real_escape_string($conn, $preferredName[$key]);
            $dvt = mysqli_real_escape_string($conn, $preferredDvt[$key]);
            $sqlIns = "INSERT INTO bhld_dmvattu (mavt, tenvt, dvt, ghichu) VALUES ($next, '$ten', '$dvt', 'Tạo tự động khi import')";
            if (!mysqli_query($conn, $sqlIns)) {
                throw new Exception('Không tạo được vật tư ' . $preferredName[$key] . ': ' . mysqli_error($conn));
            }
            $mavt = $next;
            $equip[] = ['mavt' => $mavt, 'tenvt' => $preferredName[$key], 'dvt' => $preferredDvt[$key]];
        }
        $resolve[$key] = $mavt;
    }

    return $resolve;
}

$report = [
    'total_rows' => 0,
    'imported' => 0,
    'skipped' => 0,
    'errors' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Vui lòng chọn file Excel hợp lệ');
        }

        $mapb = trim((string)($_POST['mapb'] ?? ''));
        $dinhmuc = trim((string)($_POST['dinhmuc'] ?? ''));
        $sourceMadm = $dinhmuc !== '' ? $dinhmuc : 'IMPORT_EXCEL';

        if ($mapb === '') {
            throw new Exception('Thiếu mapb');
        }

        $rows = load_rows_from_file($_FILES['excel_file']['tmp_name'], $_FILES['excel_file']['name']);
        if (count($rows) < 2) {
            throw new Exception('File không có dữ liệu');
        }

        // Bỏ dòng header: tìm dòng có chữ "Danh số"
        $startIdx = 1;
        foreach ($rows as $i => $r) {
            $line = vn_norm(implode(' ', array_map(fn($x) => (string)$x, $r)));
            if (strpos($line, 'danh so') !== false && strpos($line, 'ho va ten') !== false) {
                $startIdx = $i + 1;
                break;
            }
        }

        mysqli_begin_transaction($conn);

        // mapping cột (0-based)
        // 0 STT | 1 Danh số | 2 Họ và tên | 3 Chức danh
        // 4 Giày size | 5 Giày loại | 6 Giày dmtg
        // 7 Quần áo size | 8 Quần áo dmtg
        // 9 Mũ màu | 10 Mũ dmtg
        // 11 Kính | 12 Găng tay | 13 Khẩu trang | 14 Áo mưa | 15 Phin lọc khí độc
        // 16 Áo phao cứu sinh | 17 Nút bịt tai chống ồn | 18 Găng tay da thợ hàn

        $mavtMap = ensure_equipment_by_aliases($conn);

        // Prepared statements
        $stEmp = mysqli_prepare($conn, "
            INSERT INTO bhld_nhanvien (manv, tennhanvien, mapb, dinhmuc)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                tennhanvien = VALUES(tennhanvien),
                mapb = VALUES(mapb),
                dinhmuc = VALUES(dinhmuc)
        ");
        if (!$stEmp) throw new Exception('Lỗi prepare nhân viên: ' . mysqli_error($conn));

        $stProfile = mysqli_prepare($conn, "
            INSERT INTO bhld_nhanvien_hoso (manv, giay_size, giay_loai, quanao_size, mu_mau, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                giay_size = VALUES(giay_size),
                giay_loai = VALUES(giay_loai),
                quanao_size = VALUES(quanao_size),
                mu_mau = VALUES(mu_mau),
                ghi_chu = VALUES(ghi_chu)
        ");
        if (!$stProfile) throw new Exception('Lỗi prepare hồ sơ: ' . mysqli_error($conn));

        $stPolicy = mysqli_prepare($conn, "
            INSERT INTO bhld_nhanvien_vattu_dm (manv, mavt, dmuc_thang, so_luong, active, source_madm, ghi_chu)
            VALUES (?, ?, ?, 1, 1, ?, NULL)
            ON DUPLICATE KEY UPDATE
                dmuc_thang = VALUES(dmuc_thang),
                so_luong = VALUES(so_luong),
                active = VALUES(active),
                source_madm = VALUES(source_madm),
                ghi_chu = VALUES(ghi_chu)
        ");
        if (!$stPolicy) throw new Exception('Lỗi prepare policy: ' . mysqli_error($conn));

        for ($i = $startIdx; $i < count($rows); $i++) {
            $r = $rows[$i];

            $manv = to_nullable_string($r[1] ?? '');
            $ten = to_nullable_string($r[2] ?? '');

            if ($manv === null || $ten === null) {
                $report['skipped']++;
                continue;
            }

            $report['total_rows']++;

            $chucDanh = to_nullable_string($r[3] ?? '');

            $giaySize = to_nullable_string($r[4] ?? '');
            $giayLoai = to_nullable_string($r[5] ?? '');
            $giayDm = to_non_negative_int_or_null($r[6] ?? '');

            $quanaoSize = to_nullable_string($r[7] ?? '');
            $quanaoDm = to_non_negative_int_or_null($r[8] ?? '');

            $muMau = to_nullable_string($r[9] ?? '');
            $muDm = to_non_negative_int_or_null($r[10] ?? '');

            $kinhDm = to_non_negative_int_or_null($r[11] ?? '');
            $gangTayDm = to_non_negative_int_or_null($r[12] ?? '');
            $khauTrangDm = to_non_negative_int_or_null($r[13] ?? '');
            $aoMuaDm = to_non_negative_int_or_null($r[14] ?? '');
            $phinLocDm = to_non_negative_int_or_null($r[15] ?? '');
            $aoPhaoDm = to_non_negative_int_or_null($r[16] ?? '');
            $nutTaiDm = to_non_negative_int_or_null($r[17] ?? '');
            $gangTayHanDm = to_non_negative_int_or_null($r[18] ?? '');

            $dm = $dinhmuc !== '' ? $dinhmuc : null;

            mysqli_stmt_bind_param($stEmp, 'ssss', $manv, $ten, $mapb, $dm);
            if (!mysqli_stmt_execute($stEmp)) {
                throw new Exception("Lỗi upsert nhân viên dòng " . ($i + 1) . ": " . mysqli_stmt_error($stEmp));
            }

            $ghiChu = $chucDanh ? ('Chức danh: ' . $chucDanh) : null;
            mysqli_stmt_bind_param($stProfile, 'ssssss', $manv, $giaySize, $giayLoai, $quanaoSize, $muMau, $ghiChu);
            if (!mysqli_stmt_execute($stProfile)) {
                throw new Exception("Lỗi upsert hồ sơ dòng " . ($i + 1) . ": " . mysqli_stmt_error($stProfile));
            }

            $policyCols = [
                ['key' => 'giay', 'dm' => $giayDm],
                ['key' => 'quan_ao', 'dm' => $quanaoDm],
                ['key' => 'mu', 'dm' => $muDm],
                ['key' => 'kinh', 'dm' => $kinhDm],
                ['key' => 'gang_tay', 'dm' => $gangTayDm],
                ['key' => 'khau_trang', 'dm' => $khauTrangDm],
                ['key' => 'ao_mua', 'dm' => $aoMuaDm],
                ['key' => 'phin_loc', 'dm' => $phinLocDm],
                ['key' => 'ao_phao', 'dm' => $aoPhaoDm],
                ['key' => 'nut_tai', 'dm' => $nutTaiDm],
                ['key' => 'gang_tay_han', 'dm' => $gangTayHanDm],
            ];

            foreach ($policyCols as $pc) {
                if ($pc['dm'] === null || $pc['dm'] <= 0) continue;
                $mavt = (int)$mavtMap[$pc['key']];
                $dmuc = (int)$pc['dm'];
                mysqli_stmt_bind_param($stPolicy, 'siis', $manv, $mavt, $dmuc, $sourceMadm);
                if (!mysqli_stmt_execute($stPolicy)) {
                    throw new Exception("Lỗi upsert policy dòng " . ($i + 1) . ": " . mysqli_stmt_error($stPolicy));
                }
            }

            $report['imported']++;
        }

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $report['errors'][] = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Import Excel BHLD</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; max-width: 760px; }
    .row { margin-bottom: 10px; }
    label { display: inline-block; min-width: 120px; font-weight: 600; }
    input[type=text], input[type=file] { width: 460px; max-width: 100%; padding: 6px; }
    button { padding: 8px 14px; cursor: pointer; }
    .ok { color: #0a7a0a; }
    .err { color: #b00020; }
  </style>
</head>
<body>
  <div class="card">
    <h3>Import nhân viên từ Excel</h3>
    <p>Mẫu cột: STT, Danh số, Họ và tên, Chức danh, Giày, Quần áo, Mũ, Kính, Găng tay, Khẩu trang, Áo mưa, Phin lọc khí độc, Áo phao cứu sinh, Nút bịt tai chống ồn, Găng tay da thợ hàn.</p>

    <form method="post" enctype="multipart/form-data">
      <div class="row">
        <label>File Excel</label>
        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required>
      </div>
      <div class="row">
        <label>Mã phòng ban</label>
        <input type="text" name="mapb" value="<?php echo h($_POST['mapb'] ?? 'PB01'); ?>" required>
      </div>
      <div class="row">
        <label>Mã định mức</label>
        <input type="text" name="dinhmuc" value="<?php echo h($_POST['dinhmuc'] ?? ''); ?>" placeholder="Tuỳ chọn, ví dụ DM001">
      </div>
      <div class="row">
        <button type="submit">Import</button>
      </div>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
      <hr>
      <div class="ok">Tổng dòng dữ liệu: <?php echo (int)$report['total_rows']; ?></div>
      <div class="ok">Import thành công: <?php echo (int)$report['imported']; ?></div>
      <div>Bỏ qua: <?php echo (int)$report['skipped']; ?></div>
      <?php if (!empty($report['errors'])): ?>
        <div class="err">
          <?php foreach ($report['errors'] as $er): ?>
            <div>- <?php echo h($er); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>