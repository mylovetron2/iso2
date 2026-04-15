<?php
/**
 * Xuất PDF Thống kê Kế hoạch Kiểm định Thiết bị 2026
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
requireAuth();

try {
    $db = getDBConnection();
    
    $nam = 2026; // Năm cố định
    $search = $_GET['search'] ?? '';
    $quy = $_GET['quy'] ?? ''; // Lọc theo quý
    $loaitb = $_GET['loaitb'] ?? '';
    $bophan = $_GET['bophan'] ?? '';
    
    // Hàm chuyển quý sang danh sách tháng
    function getMonthsInQuarter($quy) {
        switch((int)$quy) {
            case 1: return [1, 2, 3];
            case 2: return [4, 5, 6];
            case 3: return [7, 8, 9];
            case 4: return [10, 11, 12];
            default: return [];
        }
    }
    
    // Lấy danh sách thiết bị với kế hoạch
    $sql = "SELECT t.*, 
            GROUP_CONCAT(DISTINCT k.thang_thuchien ORDER BY k.thang_thuchien) as planned_months,
            GROUP_CONCAT(DISTINCT k.thang_dot2 ORDER BY k.thang_dot2) as planned_months_dot2,
            MIN(CAST(k.thang_thuchien AS UNSIGNED)) as first_month,
            MAX(k.donvi_thuchien) as donvi_thuchien,
            GROUP_CONCAT(DISTINCT MONTH(h.ngayhc) ORDER BY h.ngayhc) as inspected_months,
            COUNT(DISTINCT h.stt) as inspection_count
            FROM thietbihckd_iso t
            LEFT JOIN kehoach_kiemdinh_2026_iso k ON t.stt = k.stt AND k.nam_kehoach = 2026
            LEFT JOIN hosohckd_iso h ON (t.mavattu = h.tenmay OR t.somay = h.tenmay) 
                AND YEAR(h.ngayhc) = 2026
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (t.tenthietbi LIKE :search1 OR t.somay LIKE :search2 OR t.tenviettat LIKE :search3)";
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
        $params[':search3'] = '%' . $search . '%';
    }
    
    if (!empty($loaitb)) {
        $sql .= " AND t.loaitb = :loaitb";
        $params[':loaitb'] = $loaitb;
    }
    
    if (!empty($bophan)) {
        $sql .= " AND t.bophansh = :bophan";
        $params[':bophan'] = $bophan;
    }
    
    $sql .= " GROUP BY t.stt ORDER BY first_month ASC, t.loaitb, t.tenthietbi";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $allEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính thống kê
    $statistics = [
        'da_hoan_thanh' => [],
        'chua_hoan_thanh' => [],
        'truoc_han' => [],
        'sau_han' => []
    ];
    
    $totalEquipmentWithPlan = 0;
    $totalEquipmentInQuarter = 0;
    $totalMonths = 0;
    $completedMonths = 0;
    
    foreach ($allEquipment as $equipment) {
        $plannedMonths = array_merge(
            $equipment['planned_months'] ? explode(',', $equipment['planned_months']) : [],
            $equipment['planned_months_dot2'] ? explode(',', $equipment['planned_months_dot2']) : []
        );
        $plannedMonths = array_map('trim', $plannedMonths);
        $plannedMonths = array_unique($plannedMonths);
        $plannedMonths = array_filter($plannedMonths, function($m) { return !empty($m); });
        
        if (!empty($plannedMonths)) {
            $totalEquipmentWithPlan++;
        }
        
        $inspectedMonths = $equipment['inspected_months'] ? explode(',', $equipment['inspected_months']) : [];
        $inspectedMonths = array_map('trim', $inspectedMonths);
        
        if (!empty($quy) && is_numeric($quy)) {
            if (empty($plannedMonths)) {
                continue;
            }
            
            $monthsInQuarter = getMonthsInQuarter((int)$quy);
            
            $hasInspectedInQuarter = false;
            foreach ($monthsInQuarter as $m) {
                if (in_array((string)$m, $inspectedMonths)) {
                    $hasInspectedInQuarter = true;
                    break;
                }
            }
            
            $hasPlannedInQuarter = false;
            foreach ($monthsInQuarter as $m) {
                if (in_array((string)$m, $plannedMonths)) {
                    $hasPlannedInQuarter = true;
                    break;
                }
            }
            
            if ($hasInspectedInQuarter) {
                $totalEquipmentInQuarter++;
                
                if ($hasPlannedInQuarter) {
                    $statistics['da_hoan_thanh'][] = $equipment;
                } else {
                    $statistics['truoc_han'][] = $equipment;
                }
            } else {
                if ($hasPlannedInQuarter) {
                    $totalEquipmentInQuarter++;
                    
                    $hasCompletedAfter = false;
                    foreach ($inspectedMonths as $inspMonth) {
                        $inspMonthInt = (int)$inspMonth;
                        if ($inspMonthInt > $monthsInQuarter[2]) {
                            $hasCompletedAfter = true;
                            break;
                        }
                    }
                    
                    if ($hasCompletedAfter) {
                        $statistics['sau_han'][] = $equipment;
                    } else {
                        $statistics['chua_hoan_thanh'][] = $equipment;
                    }
                }
            }
        } else {
            if (!empty($inspectedMonths)) {
                if (!empty($plannedMonths)) {
                    $statistics['da_hoan_thanh'][] = $equipment;
                    foreach ($plannedMonths as $month) {
                        if (in_array($month, $inspectedMonths)) {
                            $completedMonths++;
                        }
                    }
                }
            } else {
                if (!empty($plannedMonths)) {
                    $statistics['chua_hoan_thanh'][] = $equipment;
                }
            }
        }
        
        if (!empty($plannedMonths)) {
            $totalMonths += count($plannedMonths);
        }
    }
    
    $tyLeHoanThanh = 0;
    if (!empty($quy)) {
        $completed = count($statistics['da_hoan_thanh']) + count($statistics['truoc_han']) + count($statistics['sau_han']);
        $tyLeHoanThanh = $totalEquipmentInQuarter > 0 ? round(($completed / $totalEquipmentInQuarter) * 100, 2) : 0;
    } else {
        $tyLeHoanThanh = $totalMonths > 0 ? round(($completedMonths / $totalMonths) * 100, 2) : 0;
    }
    
    $summary = [
        'total_plans' => $totalEquipmentWithPlan,
        'da_hoan_thanh' => count($statistics['da_hoan_thanh'] ?? []),
        'truoc_han' => count($statistics['truoc_han'] ?? []),
        'sau_han' => count($statistics['sau_han'] ?? []),
        'chua_hoan_thanh' => count($statistics['chua_hoan_thanh'] ?? []),
        'tyle_hoan_thanh' => $tyLeHoanThanh,
        'selected_quy' => $quy,
        'total_months' => $totalMonths,
        'completed_months' => $completedMonths
    ];
    
    // Tạo PDF với TCPDF
    require_once __DIR__ . '/libs/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('ISO System');
    $pdf->SetAuthor('ISO System');
    $pdf->SetTitle('Báo cáo Thống kê Kiểm định Thiết bị 2026');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 10);
    
    ob_start();
    ?>
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="font-size: 16pt; font-weight: bold; color: #1e40af; margin: 0;">BÁO CÁO THỐNG KÊ KIỂM ĐỊNH THIẾT BỊ 2026</h2>
        <p style="font-size: 10pt; font-style: italic; margin: 10px 0;">Ngày xuất: <?php echo date('d/m/Y H:i'); ?></p>
        <?php if (!empty($quy)): ?>
            <p style="font-size: 11pt; font-weight: bold; color: #2563eb;">Quý <?php echo $quy; ?></p>
        <?php endif; ?>
        <?php if (!empty($loaitb)): ?>
            <p style="font-size: 10pt; color: #4b5563;">Loại thiết bị: <?php echo htmlspecialchars($loaitb); ?></p>
        <?php endif; ?>
        <?php if (!empty($bophan)): ?>
            <p style="font-size: 10pt; color: #4b5563;">Bộ phận: <?php echo htmlspecialchars($bophan); ?></p>
        <?php endif; ?>
    </div>

    <div style="border: 2px solid #2563eb; padding: 10px; margin: 15px 0; background-color: #eff6ff;">
        <h3 style="margin-top: 0; color: #1e40af; font-size: 13pt;">TỔNG QUAN</h3>
        <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
            <span style="font-weight: bold;">Tổng số thiết bị có kế hoạch:</span>
            <span style="font-weight: bold; color: #1e40af;"><?php echo $summary['total_plans']; ?> thiết bị</span>
        </div>
        
        <?php if (!empty($summary['selected_quy'])): ?>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Thiết bị kiểm định trong quý:</span>
                <span style="font-weight: bold; color: #1e40af;"><?php echo $totalEquipmentInQuarter; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Hoàn thành đúng hạn:</span>
                <span style="font-weight: bold; color: #16a34a;"><?php echo $summary['da_hoan_thanh']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Hoàn thành trước hạn:</span>
                <span style="font-weight: bold; color: #0d9488;"><?php echo $summary['truoc_han']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Hoàn thành sau hạn:</span>
                <span style="font-weight: bold; color: #0891b2;"><?php echo $summary['sau_han']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Chưa hoàn thành:</span>
                <span style="font-weight: bold; color: #dc2626;"><?php echo $summary['chua_hoan_thanh']; ?> thiết bị</span>
            </div>
        <?php else: ?>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Đã hoàn thành:</span>
                <span style="font-weight: bold; color: #16a34a;"><?php echo $summary['da_hoan_thanh']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Chưa hoàn thành:</span>
                <span style="font-weight: bold; color: #dc2626;"><?php echo $summary['chua_hoan_thanh']; ?> thiết bị</span>
            </div>
        <?php endif; ?>
        
        <div style="border-bottom: none; margin-top: 10px; background-color: white; padding: 8px;">
            <span style="font-weight: bold; font-size: 13pt;">TỶ LỆ HOÀN THÀNH:</span>
            <span style="font-size: 16pt; font-weight: bold; color: #16a34a;"><?php echo $summary['tyle_hoan_thanh']; ?>%</span>
            <?php if (empty($summary['selected_quy'])): ?>
                <span style="font-size: 9pt; color: #6b7280;">
                    (<?php echo $summary['completed_months']; ?> / <?php echo $summary['total_months']; ?> tháng)
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $html_summary = ob_get_clean();
    $pdf->writeHTML($html_summary, true, false, true, false, '');
    
    // Vẽ biểu đồ tròn (Pie Chart)
    $pdf->Ln(5);
    
    $pdf->SetFont('dejavusans', 'B', 14);
    $pdf->SetTextColor(30, 64, 175);
    $pdf->Cell(0, 8, 'BIỂU ĐỒ PHÂN BỔ TRẠNG THÁI', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Tính toán dữ liệu cho biểu đồ
    if (!empty($summary['selected_quy'])) {
        // Khi chọn quý: 4 trạng thái
        $chartData = [
            ['label' => 'Đúng hạn', 'value' => $summary['da_hoan_thanh'], 'color' => [22, 163, 74]],
            ['label' => 'Trước hạn', 'value' => $summary['truoc_han'], 'color' => [13, 148, 136]],
            ['label' => 'Sau hạn', 'value' => $summary['sau_han'], 'color' => [8, 145, 178]],
            ['label' => 'Chưa hoàn thành', 'value' => $summary['chua_hoan_thanh'], 'color' => [220, 38, 38]]
        ];
        $total = $totalEquipmentInQuarter;
    } else {
        // Khi không chọn quý: 2 trạng thái
        $chartData = [
            ['label' => 'Đã hoàn thành', 'value' => $summary['da_hoan_thanh'], 'color' => [22, 163, 74]],
            ['label' => 'Chưa hoàn thành', 'value' => $summary['chua_hoan_thanh'], 'color' => [220, 38, 38]]
        ];
        $total = $summary['total_plans'];
    }
    
    if ($total > 0) {
        // Vẽ biểu đồ tròn đơn giản
        $centerX = 105; // Giữa trang A4 (210mm / 2)
        $centerY = $pdf->GetY() + 40;
        $radius = 35;
        
        $startAngle = 0;
        foreach ($chartData as $data) {
            if ($data['value'] > 0) {
                $angle = ($data['value'] / $total) * 360;
                $endAngle = $startAngle + $angle;
                
                // Vẽ slice
                $pdf->SetFillColor($data['color'][0], $data['color'][1], $data['color'][2]);
                $pdf->PieSector($centerX, $centerY, $radius, $startAngle, $endAngle, 'FD', false, 0, 2);
                
                $startAngle = $endAngle;
            }
        }
        
        // Vẽ chú thích (legend)
        $pdf->SetY($centerY + $radius + 10);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        $legendY = $pdf->GetY();
        $legendX = 40;
        
        foreach ($chartData as $data) {
            if ($data['value'] > 0) {
                $percentage = round(($data['value'] / $total) * 100, 1);
                
                // Vẽ ô màu
                $pdf->SetFillColor($data['color'][0], $data['color'][1], $data['color'][2]);
                $pdf->Rect($legendX, $legendY, 5, 5, 'F');
                
                // Vẽ text
                $pdf->SetXY($legendX + 7, $legendY - 1);
                $pdf->Cell(80, 6, $data['label'] . ': ' . $data['value'] . ' (' . $percentage . '%)', 0, 1, 'L');
                
                $legendY += 7;
            }
        }
    }
    
    // Danh sách thiết bị chi tiết
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', 'B', 12);
    $pdf->Cell(0, 8, 'DANH SÁCH CHI TIẾT', 0, 1, 'L');
    $pdf->Ln(3);
    
    function outputEquipmentList($pdf, $title, $equipmentList, $color) {
        if (empty($equipmentList)) {
            return;
        }
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Cell(0, 7, $title . ' (' . count($equipmentList) . ' thiet bi)', 1, 1, 'L', true);
        
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(10, 6, 'STT', 1, 0, 'C', true);
        $pdf->Cell(95, 6, 'Tên thiết bị', 1, 0, 'C', true);
        $pdf->Cell(35, 6, 'Số máy', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Bộ phận', 1, 1, 'C', true);
        
        $pdf->SetFont('dejavusans', '', 8);
        $index = 1;
        foreach ($equipmentList as $eq) {
            // Xử lý tên thiết bị: bỏ "G/K" nếu có
            $tenThietBi = str_replace('G/K', '', $eq['tenthietbi']);
            $tenThietBi = trim($tenThietBi); // Loại bỏ khoảng trắng thừa
            
            $pdf->Cell(10, 5, $index++, 1, 0, 'C');
            $pdf->Cell(95, 5, mb_substr($tenThietBi, 0, 60), 1, 0, 'L');
            $pdf->Cell(35, 5, $eq['somay'], 1, 0, 'C');
            $pdf->Cell(40, 5, $eq['bophansh'], 1, 1, 'L');
            
            if ($pdf->GetY() > 260) {
                $pdf->AddPage();
                $pdf->SetFont('dejavusans', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(10, 6, 'STT', 1, 0, 'C', true);
                $pdf->Cell(95, 6, 'Tên thiết bị', 1, 0, 'C', true);
                $pdf->Cell(35, 6, 'Số máy', 1, 0, 'C', true);
                $pdf->Cell(40, 6, 'Bộ phận', 1, 1, 'C', true);
                $pdf->SetFont('dejavusans', '', 8);
            }
        }
        
        $pdf->Ln(5);
    }
    
    if (!empty($summary['selected_quy'])) {
        outputEquipmentList($pdf, 'DA HOAN THANH DUNG HAN', $statistics['da_hoan_thanh'], [22, 163, 74]);
        outputEquipmentList($pdf, 'DA HOAN THANH TRUOC HAN', $statistics['truoc_han'], [13, 148, 136]);
        outputEquipmentList($pdf, 'DA HOAN THANH SAU HAN', $statistics['sau_han'], [8, 145, 178]);
        outputEquipmentList($pdf, 'CHUA HOAN THANH', $statistics['chua_hoan_thanh'], [220, 38, 38]);
    } else {
        outputEquipmentList($pdf, 'DA HOAN THANH', $statistics['da_hoan_thanh'], [22, 163, 74]);
        outputEquipmentList($pdf, 'CHUA HOAN THANH', $statistics['chua_hoan_thanh'], [220, 38, 38]);
    }
    
    $filename = 'ThongKe_KiemDinh_2026';
    if (!empty($quy)) {
        $filename .= '_Quy' . $quy;
    }
    $filename .= '_' . date('Ymd_His') . '.pdf';
    
    $pdf->Output($filename, 'I');
    
} catch (Exception $e) {
    error_log("Error in kehoach_thietbi_2026_thongke_pdf.php: " . $e->getMessage());
    die('Có lỗi xảy ra khi tạo PDF: ' . $e->getMessage());
}
