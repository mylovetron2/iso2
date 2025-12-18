<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISO System');
$pdf->SetAuthor('ISO System');
$pdf->SetTitle('Danh sách Hồ sơ SCBĐ');
$pdf->SetSubject('Hồ sơ Sửa chữa Bảo dưỡng');

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

function getStatusText($item) {
    if ($item['bg'] == 1) {
        return 'Đã BG';
    } elseif (!empty($item['ngaykt'])) {
        return 'Hoàn thành';
    } elseif (!empty($item['ngayth'])) {
        return 'Đang làm';
    } else {
        return 'Chưa TH';
    }
}

// Title
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'DANH SÁCH HỒ SƠ SỬA CHỮA BẢO DƯỠNG', 0, 1, 'C');
$pdf->Ln(2);

// Filter info
$pdf->SetFont('dejavusans', '', 9);
$filterText = 'Bộ lọc: ';
$filters = [];
if (!empty($_GET['search'])) $filters[] = 'Tìm kiếm: ' . $_GET['search'];
if (!empty($_GET['madv'])) {
    $dvName = '-';
    foreach ($donViList as $dv) {
        if ($dv['madv'] == $_GET['madv']) {
            $dvName = $dv['tendv'];
            break;
        }
    }
    $filters[] = 'Đơn vị: ' . $dvName;
}
if (!empty($_GET['trangthai'])) {
    $ttMap = [
        'chuath' => 'Chưa thực hiện',
        'danglam' => 'Đang làm',
        'hoanthanh' => 'Hoàn thành',
        'chuabg' => 'Chưa bàn giao',
        'dabg' => 'Đã bàn giao'
    ];
    $filters[] = 'Trạng thái: ' . ($ttMap[$_GET['trangthai']] ?? $_GET['trangthai']);
}
if (!empty($_GET['from_date'])) {
    $filters[] = 'Từ ngày: ' . date('d/m/Y', strtotime($_GET['from_date']));
}
if (!empty($_GET['to_date'])) {
    $filters[] = 'Đến ngày: ' . date('d/m/Y', strtotime($_GET['to_date']));
}
if (empty($filters)) {
    $filterText .= 'Tất cả';
} else {
    $filterText .= implode(' | ', $filters);
}
$pdf->Cell(0, 6, $filterText, 0, 1, 'L');
$pdf->Ln(3);

// Table header using TCPDF Cell for precise alignment
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->SetFillColor(200, 220, 255);

// Column widths
$w = array(10, 22, 30, 30, 25, 35, 25, 25, 25, 30);

// Header
$pdf->Cell($w[0], 7, 'STT', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, 'Phiếu', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Mã VT', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, 'Số máy', 1, 0, 'C', true);
$pdf->Cell($w[4], 7, 'Ngày YC', 1, 0, 'C', true);
$pdf->Cell($w[5], 7, 'Đơn vị', 1, 0, 'C', true);
$pdf->Cell($w[6], 7, 'Nhóm SC', 1, 0, 'C', true);
$pdf->Cell($w[7], 7, 'Ngày TH', 1, 0, 'C', true);
$pdf->Cell($w[8], 7, 'Ngày KT', 1, 0, 'C', true);
$pdf->Cell($w[9], 7, 'TT', 1, 1, 'C', true);

// Data rows
$pdf->SetFont('dejavusans', '', 11);
if (empty($items)) {
    $pdf->Cell(array_sum($w), 10, 'Không có dữ liệu', 1, 1, 'C');
} else {
    $stt = 1;
    foreach ($items as $item) {
        $status = getStatusText($item);
        
        // Set status background color
        if ($status == 'Đã BG') {
            $fillColor = array(212, 237, 218);
        } elseif ($status == 'Hoàn thành') {
            $fillColor = array(204, 229, 255);
        } elseif ($status == 'Đang làm') {
            $fillColor = array(255, 243, 205);
        } else {
            $fillColor = array(248, 215, 218);
        }
        
        $pdf->Cell($w[0], 6, $stt++, 1, 0, 'C');
        $pdf->Cell($w[1], 6, displayTextPdf($item['phieu']), 1, 0, 'L');
        $pdf->Cell($w[2], 6, displayTextPdf($item['mavt']), 1, 0, 'L');
        $pdf->Cell($w[3], 6, displayTextPdf($item['somay']), 1, 0, 'L');
        $pdf->Cell($w[4], 6, $item['ngayyc'] ? date('d/m/Y', strtotime($item['ngayyc'])) : '-', 1, 0, 'C');
        $pdf->Cell($w[5], 6, displayTextPdf($item['madv']), 1, 0, 'L');
        $pdf->Cell($w[6], 6, displayTextPdf($item['nhomsc']), 1, 0, 'L');
        $pdf->Cell($w[7], 6, $item['ngayth'] ? date('d/m/Y', strtotime($item['ngayth'])) : '-', 1, 0, 'C');
        $pdf->Cell($w[8], 6, $item['ngaykt'] ? date('d/m/Y', strtotime($item['ngaykt'])) : '-', 1, 0, 'C');
        
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $pdf->Cell($w[9], 6, $status, 1, 1, 'C', true);
        $pdf->SetFillColor(255, 255, 255);
    }
}

$pdf->Ln(3);
$pdf->SetFont('dejavusans', 'I', 8);
$pdf->Cell(0, 5, 'Tổng số: ' . count($items) . ' hồ sơ | Xuất lúc: ' . date('d/m/Y H:i:s'), 0, 1, 'R');

// Output PDF
$filename = 'HoSoSCBD_DanhSach_' . date('YmdHis') . '.pdf';
$pdf->Output($filename, 'I');
