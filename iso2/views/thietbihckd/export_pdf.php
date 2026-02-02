<?php
declare(strict_types=1);

// Start session manually
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Disable error display
error_reporting(0);
ini_set('display_errors', '0');

// Simple logged in check
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

// Load dependencies
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

requireAuth();

if (!hasPermission('thietbi.view')) {
    die('Không có quyền xuất file');
}

// Load TCPDF library
require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

// Load controller and model
require_once __DIR__ . '/../../models/ThietBiHCKD.php';
require_once __DIR__ . '/../../models/DonVi.php';

$model = new ThietBiHCKD();

// Get filter parameters
$search = $_GET['search'] ?? '';
$bophansh = $_GET['bophansh'] ?? '';
$loaitb = $_GET['loaitb'] ?? '';
$filter = $_GET['filter'] ?? '';

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(mavattu LIKE :search1 OR tenviettat LIKE :search2 OR tenthietbi LIKE :search3 OR somay LIKE :search4 OR hangsx LIKE :search5)";
    $params['search1'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
    $params['search4'] = "%$search%";
    $params['search5'] = "%$search%";
}

if ($bophansh !== '') {
    $conditions[] = "bophansh = :bophansh";
    $params['bophansh'] = $bophansh;
}

if ($loaitb !== '') {
    $conditions[] = "loaitb = :loaitb";
    $params['loaitb'] = $loaitb;
}

$filterType = '';
if ($filter === 'saphethan') {
    $filterType = 'saphethan';
} elseif ($filter === 'dahethan') {
    $filterType = 'dahethan';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get ALL items for export (no pagination - use limit 0 for no limit)
$items = $model->getAllWithLatestHC($where, $params, 0, 0, $filterType);

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISO System');
$pdf->SetAuthor('ISO System');
$pdf->SetTitle('Danh sách Thiết bị HC/KĐ');
$pdf->SetSubject('Thiết bị Hiệu Chuẩn/Kiểm Định');

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

// Helper function to truncate text
function truncateText($text, $maxLength) {
    $text = trim($text);
    if (mb_strlen($text, 'UTF-8') > $maxLength) {
        return mb_substr($text, 0, $maxLength - 2, 'UTF-8') . '..';
    }
    return $text;
}

// Title
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'DANH SÁCH THIẾT BỊ HIỆU CHUẨN/KIỂM ĐỊNH', 0, 1, 'C');
$pdf->Ln(2);

// Filter info
$pdf->SetFont('dejavusans', '', 9);
$filterText = 'Bộ lọc: ';
$filters = [];
if (!empty($search)) $filters[] = 'Tìm kiếm: ' . $search;
if (!empty($bophansh)) $filters[] = 'Bộ phận: ' . $bophansh;
if (!empty($loaitb)) $filters[] = 'Loại TB: ' . $loaitb;
if (!empty($filter)) {
    $filterMap = [
        'saphethan' => 'Sắp hết hạn (30 ngày)',
        'dahethan' => 'Đã hết hạn'
    ];
    $filters[] = 'Trạng thái: ' . ($filterMap[$filter] ?? $filter);
}
if (empty($filters)) {
    $filterText .= 'Tất cả';
} else {
    $filterText .= implode(' | ', $filters);
}
$pdf->Cell(0, 6, $filterText, 0, 1, 'L');

// Stats
$pdf->SetFont('dejavusans', 'B', 9);
$totalItems = count($items);
$conhan = 0;
$saphethan = 0;
$dahethan = 0;
$chuahc = 0;

foreach ($items as $item) {
    $ngayHCGanNhat = $item['ngayhc_latest'] ?? $item['ngayktnghiemthu'];
    
    if (!empty($ngayHCGanNhat) && !empty($item['thoihankd'])) {
        $ngayHC = new DateTime($ngayHCGanNhat);
        $ngayHetHan = clone $ngayHC;
        $ngayHetHan->modify('+' . (int)$item['thoihankd'] . ' months');
        $today = new DateTime();
        $diff = $today->diff($ngayHetHan);
        
        if ($ngayHetHan < $today) {
            $dahethan++;
        } elseif ($diff->days <= 30) {
            $saphethan++;
        } else {
            $conhan++;
        }
    } else {
        $chuahc++;
    }
}

$statsText = sprintf(
    'Tổng số: %d | Còn hạn: %d | Sắp hết hạn: %d | Đã hết hạn: %d | Chưa HC: %d',
    $totalItems,
    $conhan,
    $saphethan,
    $dahethan,
    $chuahc
);
$pdf->Cell(0, 6, $statsText, 0, 1, 'L');
$pdf->Ln(3);

// Table header
$pdf->SetFont('dejavusans', 'B', 8);
$pdf->SetFillColor(200, 220, 255);

// Column widths - adjusted for landscape A4
$w = array(10, 30, 35, 50, 30, 35, 30, 22, 25);

// Header
$pdf->Cell($w[0], 7, 'STT', 1, 0, 'C', true);
$pdf->Cell($w[1], 7, 'Mã VT', 1, 0, 'C', true);
$pdf->Cell($w[2], 7, 'Tên viết tắt', 1, 0, 'C', true);
$pdf->Cell($w[3], 7, 'Tên thiết bị', 1, 0, 'C', true);
$pdf->Cell($w[4], 7, 'Số máy', 1, 0, 'C', true);
$pdf->Cell($w[5], 7, 'Bộ phận', 1, 0, 'C', true);
$pdf->Cell($w[6], 7, 'Hãng SX', 1, 0, 'C', true);
$pdf->Cell($w[7], 7, 'Ngày HC', 1, 0, 'C', true);
$pdf->Cell($w[8], 7, 'Trạng thái', 1, 1, 'C', true);

// Table body
$pdf->SetFont('dejavusans', '', 8);

$stt = 1;
foreach ($items as $item) {
    // Calculate status
    $status = '-';
    $ngayHCGanNhat = $item['ngayhc_latest'] ?? $item['ngayktnghiemthu'];
    
    if (!empty($ngayHCGanNhat) && !empty($item['thoihankd'])) {
        $ngayHC = new DateTime($ngayHCGanNhat);
        $ngayHetHan = clone $ngayHC;
        $ngayHetHan->modify('+' . (int)$item['thoihankd'] . ' months');
        $today = new DateTime();
        $diff = $today->diff($ngayHetHan);
        
        if ($ngayHetHan < $today) {
            $status = 'Hết hạn';
        } elseif ($diff->days <= 30) {
            $status = 'Sắp hết hạn';
        } else {
            $status = 'Còn hạn';
        }
    } else {
        $status = 'Chưa HC';
    }
    
    // Format ngayhc
    $ngayhcText = '-';
    if (!empty($ngayHCGanNhat)) {
        $ngayhcText = date('d/m/Y', strtotime($ngayHCGanNhat));
    }
    
    // Truncate long text
    $mavattu = truncateText($item['mavattu'] ?? '', 20);
    $tenviettat = truncateText($item['tenviettat'] ?? '', 25);
    $tenthietbi = $item['tenthietbi'] ?? '';
    $somay = truncateText($item['somay'] ?? '', 20);
    $bophan = truncateText($item['tendv_bophan'] ?? $item['bophansh'] ?? '', 25);
    $hangsx = truncateText($item['hangsx'] ?? '', 20);
    
    // Calculate required height for this row
    $heightTenThietBi = $pdf->getStringHeight($w[3], $tenthietbi);
    $rowHeight = max(7, $heightTenThietBi);
    
    // Save starting position
    $startY = $pdf->GetY();
    $startX = 10; // Left margin
    
    // Draw all cells with same height
    $pdf->MultiCell($w[0], $rowHeight, $stt, 1, 'C', false, 0, $startX, $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[1], $rowHeight, $mavattu, 1, 'L', false, 0, $startX + $w[0], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[2], $rowHeight, $tenviettat, 1, 'L', false, 0, $startX + $w[0] + $w[1], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[3], $rowHeight, $tenthietbi, 1, 'L', false, 0, $startX + $w[0] + $w[1] + $w[2], $startY, true, 0, false, true, $rowHeight, 'T');
    $pdf->MultiCell($w[4], $rowHeight, $somay, 1, 'C', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[5], $rowHeight, $bophan, 1, 'L', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[6], $rowHeight, $hangsx, 1, 'L', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[7], $rowHeight, $ngayhcText, 1, 'C', false, 0, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $startY, true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell($w[8], $rowHeight, $status, 1, 'C', false, 1, $startX + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7], $startY, true, 0, false, true, $rowHeight, 'M');
    
    $stt++;
}

// Footer
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'I', 8);
$pdf->Cell(0, 5, 'Ngày xuất: ' . date('d/m/Y H:i:s'), 0, 1, 'R');

// Output PDF
$filename = 'danh_sach_thietbi_hckd_' . date('YmdHis') . '.pdf';
$pdf->Output($filename, 'D'); // D = force download
exit;
