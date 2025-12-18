<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

// Create new PDF document
$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISO System');
$pdf->SetAuthor('ISO System');
$pdf->SetTitle('Chi tiết Hồ sơ SCBĐ');
$pdf->SetSubject('Hồ sơ Sửa chữa Bảo dưỡng');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Set font
$pdf->SetFont('dejavusans', '', 9);

// Add a page
$pdf->AddPage();

// Helper function for text display
function displayTextPdf($text) {
    return !empty($text) ? htmlspecialchars($text) : '-';
}

// Title
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'CHI TIẾT HỒ SƠ SCBĐ', 0, 1, 'C');
$pdf->Ln(5);

// Record Info Box
$pdf->SetFillColor(220, 230, 241);
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(90, 8, 'Số phiếu: ' . displayTextPdf($item['phieu']), 1, 0, 'L', true);
$pdf->Cell(90, 8, 'Thiết bị: ' . displayTextPdf($item['mavt'] . ' - ' . $item['somay']), 1, 1, 'L', true);
$pdf->Ln(3);

// Section: Thông tin cơ bản
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'THÔNG TIN CƠ BẢN', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
$pdf->Ln(2);

$html = '<table cellpadding="4" style="border: 1px solid #ddd;">
    <tr>
        <td style="width: 30%; background-color: #f5f5f5; font-weight: bold;">Số phiếu:</td>
        <td style="width: 70%;">' . displayTextPdf($item['phieu']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ngày yêu cầu:</td>
        <td>' . ($item['ngayyc'] ? date('d/m/Y', strtotime($item['ngayyc'])) : '-') . '</td>
    </tr>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Section: Thông tin thiết bị
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetFillColor(46, 204, 113);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'THÔNG TIN THIẾT BỊ', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
$pdf->Ln(2);

$html = '<table cellpadding="4" style="border: 1px solid #ddd;">
    <tr>
        <td style="width: 30%; background-color: #f5f5f5; font-weight: bold;">Mã vật tư:</td>
        <td style="width: 70%;">' . displayTextPdf($item['mavt']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Số máy:</td>
        <td>' . displayTextPdf($item['somay']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Model:</td>
        <td>' . displayTextPdf($item['model']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Vị trí máy BD:</td>
        <td>' . displayTextPdf($item['vitrimaybd']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Lô:</td>
        <td>' . displayTextPdf($item['lo']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Giếng:</td>
        <td>' . displayTextPdf($item['gieng']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Mỏ:</td>
        <td>' . displayTextPdf($item['mo']) . '</td>
    </tr>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Section: Thông tin đơn vị & yêu cầu
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetFillColor(155, 89, 182);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'THÔNG TIN ĐƠN VỊ & YÊU CẦU', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
$pdf->Ln(2);

$html = '<table cellpadding="4" style="border: 1px solid #ddd;">
    <tr>
        <td style="width: 30%; background-color: #f5f5f5; font-weight: bold;">Đơn vị:</td>
        <td style="width: 70%;">' . displayTextPdf($item['madv']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Điện thoại:</td>
        <td>' . displayTextPdf($item['dienthoai'] ?? '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Người yêu cầu:</td>
        <td>' . displayTextPdf($item['ngyeucau'] ?? '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Người nhận yêu cầu:</td>
        <td>' . displayTextPdf($item['ngnhyeucau'] ?? '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Công việc:</td>
        <td>' . nl2br(displayTextPdf($item['cv'])) . '</td>
    </tr>';

if ($item['ycthemkh']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">YC thêm của KH:</td>
        <td>' . nl2br(displayTextPdf($item['ycthemkh'])) . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Section: Thông tin sửa chữa
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetFillColor(230, 126, 34);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'THÔNG TIN SỬA CHỮA', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
$pdf->Ln(2);

$html = '<table cellpadding="4" style="border: 1px solid #ddd;">
    <tr>
        <td style="width: 30%; background-color: #f5f5f5; font-weight: bold;">Nhóm SC:</td>
        <td style="width: 70%;">' . displayTextPdf($item['nhomsc']) . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ngày bắt đầu TT:</td>
        <td>' . ($item['ngaybdtt'] ? date('d/m/Y', strtotime($item['ngaybdtt'])) : '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ngày thực hiện:</td>
        <td>' . ($item['ngayth'] ? date('d/m/Y', strtotime($item['ngayth'])) : '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ngày kết thúc:</td>
        <td>' . ($item['ngaykt'] ? date('d/m/Y', strtotime($item['ngaykt'])) : '-') . '</td>
    </tr>
    <tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Hồ sơ:</td>
        <td>' . displayTextPdf($item['hoso'] ?? '-') . '</td>
    </tr>';

if ($item['ttktbefore']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">TT KT trước:</td>
        <td>' . nl2br(displayTextPdf($item['ttktbefore'])) . '</td>
    </tr>';
}

if ($item['honghoc']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Hỏng hóc:</td>
        <td>' . nl2br(displayTextPdf($item['honghoc'])) . '</td>
    </tr>';
}

if ($item['khacphuc']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Khắc phục:</td>
        <td>' . nl2br(displayTextPdf($item['khacphuc'])) . '</td>
    </tr>';
}

if ($item['ttktafter']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">TT KT sau SC/BĐ:</td>
        <td>' . nl2br(displayTextPdf($item['ttktafter'])) . '</td>
    </tr>';
}

if ($item['noidung']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Nội dung SC:</td>
        <td>' . nl2br(displayTextPdf($item['noidung'])) . '</td>
    </tr>';
}

if ($item['ketluan']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Kết luận:</td>
        <td>' . nl2br(displayTextPdf($item['ketluan'])) . '</td>
    </tr>';
}

if ($item['xemxetxuong']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Xem xét xưởng:</td>
        <td>' . nl2br(displayTextPdf($item['xemxetxuong'])) . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Section: Thiết bị đo SC (if any)
$hasTools = false;
for ($i = 0; $i <= 4; $i++) {
    $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
    $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
    if (!empty($item[$tbField]) || !empty($item[$serialField])) {
        $hasTools = true;
        break;
    }
}

if ($hasTools) {
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->SetFillColor(26, 188, 156);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, 'THIẾT BỊ ĐO SỬA CHỮA', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->Ln(2);

    $html = '<table cellpadding="4" border="1" style="border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f5f5f5; font-weight: bold;">
                <th style="width: 10%; text-align: center;">STT</th>
                <th style="width: 55%;">Thiết bị đo SC</th>
                <th style="width: 35%;">Serial</th>
            </tr>
        </thead>
        <tbody>';
    
    for ($i = 0; $i <= 4; $i++) {
        $tbField = $i == 0 ? 'tbdosc' : "tbdosc$i";
        $serialField = $i == 0 ? 'serialtbdosc' : "serialtbdosc$i";
        if (!empty($item[$tbField]) || !empty($item[$serialField])) {
            $html .= '<tr>
                <td style="text-align: center;">' . ($i + 1) . '</td>
                <td>' . displayTextPdf($item[$tbField] ?? '-') . '</td>
                <td>' . displayTextPdf($item[$serialField] ?? '-') . '</td>
            </tr>';
        }
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(3);
}

// Section: Bàn giao
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetFillColor(231, 76, 60);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 7, 'THÔNG TIN BÀN GIAO', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', '', 9);
$pdf->Ln(2);

$bgStatus = $item['bg'] == 1 ? 'Đã bàn giao' : 'Chưa bàn giao';
$html = '<table cellpadding="4" style="border: 1px solid #ddd;">
    <tr>
        <td style="width: 30%; background-color: #f5f5f5; font-weight: bold;">Trạng thái:</td>
        <td style="width: 70%;">' . $bgStatus . '</td>
    </tr>';

if ($item['ghichu']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ghi chú:</td>
        <td>' . nl2br(displayTextPdf($item['ghichu'])) . '</td>
    </tr>';
}

if ($item['ghichufinal']) {
    $html .= '<tr>
        <td style="background-color: #f5f5f5; font-weight: bold;">Ghi chú cuối:</td>
        <td>' . nl2br(displayTextPdf($item['ghichufinal'])) . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Footer
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'I', 8);
$pdf->Cell(0, 5, 'Xuất lúc: ' . date('d/m/Y H:i:s'), 0, 1, 'R');

// Output PDF
$filename = 'HoSoSCBD_' . $item['phieu'] . '_' . date('YmdHis') . '.pdf';
$pdf->Output($filename, 'I');
