<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISO System');
$pdf->SetAuthor('ISO System');
$pdf->SetTitle('Danh sách Thiết bị Hỗ trợ');
$pdf->SetSubject('Thiết bị Hỗ trợ');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

// Set font
$pdf->SetFont('dejavusans', '', 8);

// Add a page
$pdf->AddPage();

// Helper function
function displayTextPdf($text) {
    return !empty($text) ? htmlspecialchars($text) : '-';
}

// Title
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'DANH SÁCH THIẾT BỊ HỖ TRỢ', 0, 1, 'C');
$pdf->Ln(2);

// Filter info
$pdf->SetFont('dejavusans', '', 9);
$filterText = 'Bộ lọc: ';
$filters = [];
if (!empty($_GET['search'])) $filters[] = 'Tìm kiếm: ' . $_GET['search'];
if (!empty($_GET['chusohuu'])) $filters[] = 'Chủ sở hữu: ' . $_GET['chusohuu'];
if (!empty($_GET['trangthai'])) {
    $ttMap = [
        'hoatdong' => 'Hoạt động',
        'hong' => 'Hỏng',
        'khongdung' => 'Không dùng'
    ];
    $filters[] = 'Trạng thái: ' . ($ttMap[$_GET['trangthai']] ?? $_GET['trangthai']);
}
if (empty($filters)) {
    $filterText .= 'Tất cả';
} else {
    $filterText .= implode(' | ', $filters);
}
$pdf->Cell(0, 6, $filterText, 0, 1, 'L');

// Stats
$pdf->SetFont('dejavusans', 'B', 9);
$statsText = sprintf(
    'Tổng số: %d | Còn hạn: %d | Sắp hết hạn: %d | Hết hạn: %d',
    $stats['total'] ?? 0,
    $stats['conhan'] ?? 0,
    $stats['saphethan'] ?? 0,
    $stats['hethan'] ?? 0
);
$pdf->Cell(0, 6, $statsText, 0, 1, 'L');
$pdf->Ln(3);

// Table header
$pdf->SetFont('dejavusans', 'B', 9);
$pdf->SetFillColor(200, 220, 255);

// Column widths - adjusted after removing columns
$w = array(10, 50, 60, 40, 50, 30, 25);

// Header
$pdf->Cell($w[0], 7, 'STT', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, 'Tên VT', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Tên thiết bị', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, 'Serial', 1, 0, 'C', true);
$pdf->Cell($w[4], 7, 'Chủ sở hữu', 1, 0, 'C', true);
$pdf->Cell($w[5], 7, 'Ngày KĐ', 1, 0, 'C', true);
$pdf->Cell($w[6], 7, 'Trạng thái', 1, 1, 'C', true);

// Table body
$pdf->SetFont('dejavusans', '', 9);

// Helper function to truncate text
function truncateText($text, $maxLength) {
    $text = trim($text);
    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        return mb_substr($text, 0, $maxLength - 2, 'UTF-8') . '..';
    }
    return $text;
}

$stt = 1;
foreach ($devices as $device) {
    // Determine status based on ngaykdtt
    $trangthaiText = '-';
    if (!empty($device['ngaykdtt']) && $device['ngaykdtt'] != '0000-00-00') {
        $kdDate = strtotime($device['ngaykdtt']);
        $today = strtotime(date('Y-m-d'));
        $diffDays = ($kdDate - $today) / (60 * 60 * 24);
        
        if ($diffDays > 30) {
            $trangthaiText = 'Còn hạn';
        } elseif ($diffDays > 0) {
            $trangthaiText = 'Sắp hết';
        } else {
            $trangthaiText = 'Hết hạn';
        }
    }
    
    $ngaykdtt = '-';
    if (!empty($device['ngaykdtt']) && $device['ngaykdtt'] != '0000-00-00') {
        $ngaykdtt = date('d/m/Y', strtotime($device['ngaykdtt']));
    }
    
    // Truncate long text for fixed columns
    $tenvt = truncateText($device['tenvt'] ?? '', 35);
    $tenthietbi = $device['tenthietbi'] ?? '';
    $serial = truncateText($device['serialnumber'] ?? '', 25);
    $chusohuu = $device['chusohuu'] ?? '';
    
    // Calculate required height for this row based on longest text
    $heightTenThietBi = $pdf->getStringHeight($w[2], $tenthietbi);
    $heightChuSoHuu = $pdf->getStringHeight($w[4], $chusohuu);
    $rowHeight = max(7, $heightTenThietBi, $heightChuSoHuu);
    
    // Save starting position
    $startY = $pdf->GetY();
    $startX = 10; // Left margin
    
    // Draw all cells with same height
    $pdf->MultiCell($w[0], $rowHeight, $stt, 1, 'C', false, 0, $startX, $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[1], $rowHeight, $tenvt, 1, 'L', false, 0, $startX + $w[0], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[2], $rowHeight, $tenthietbi, 1, 'L', false, 0, $startX + $w[0] + $w[1], $startY, true, 0, false, true, $rowHeight, 'T');
    $pdf->MultiCell($w[3], $rowHeight, $serial, 1, 'C', false, 0, $startX + $w[0] + $w[1] + $w[2], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[4], $rowHeight, $chusohuu, 1, 'L', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3], $startY, true, 0, false, true, $rowHeight, 'T');
    $pdf->MultiCell($w[5], $rowHeight, $ngaykdtt, 1, 'C', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[6], $rowHeight, $trangthaiText, 1, 'C', false, 1, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $startY, true, 0, false, true, $rowHeight, 'M');
    
    $stt++;
}

// Output PDF
$filename = 'DanhSachThietBiHoTro_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I'); // I = inline display in browser
exit;
