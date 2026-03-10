<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

// Custom PDF class with footer
class MYPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-25);
        $this->SetFont('dejavusans', '', 12);
        $html = '<div style="width: 100%; text-align: left;">BM.25.02<br/>01/01/2024</div>';
        $this->writeHTML($html, true, false, true, false, '');
    }
}

// Create new PDF document
$pdf = new MYPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('ISO System');
$pdf->SetAuthor('ISO System');
$pdf->SetTitle('Phiếu yêu cầu dịch vụ');
$pdf->SetSubject('Phiếu yêu cầu dịch vụ - ' . $summary['phieu']);

// Remove default header
$pdf->setPrintHeader(false);

// Set margins
$pdf->SetMargins(15, 15, 25);
$pdf->SetAutoPageBreak(TRUE, 25);

// Set font
$pdf->SetFont('dejavusans', '', 12);

// Add a page
$pdf->AddPage();

// Helper function for text display
function displayTextPdf($text) {
    return !empty($text) ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : '';
}

// Header section with title
$html = '<div style="width: 100%;">
    <table nobr="true" style="width: 100%; border: none;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 35%; vertical-align: top; font-size: 10pt; border: none;">
                XN Địa vật lý GK<br/>
                Xưởng SCTBĐVL
            </td>
            <td style="width: 65%; text-align: left; vertical-align: top; border: none;">
                <div style="text-align: center; font-size: 12pt; font-weight: bold;">PHIẾU YÊU CẦU DỊCH VỤ</div>
                <br/>
                <strong>Số hồ sơ: ' . displayTextPdf($summary['phieu']) . '</strong>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <strong>Ngày, Датa: ' . ($summary['ngayyc'] ? date('d/m/Y', strtotime($summary['ngayyc'])) : '') . '</strong>
            </td>
        </tr>
    </table>
</div>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Basic info section
$html = '<div style="width: 100%;">
    <table nobr="true" style="width: 100%; border: none;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%; border: none;">
                1. Người yêu cầu/bàn giao TB, Сдал: <strong>' . displayTextPdf($summary['ngyeucau']) . '</strong>
            </td>
            <td style="width: 40%; border: none;">
                Ký tên (Сдал /Подпись): .........
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                &nbsp;&nbsp;&nbsp;&nbsp;Đơn vị, Подр: <strong>' . displayTextPdf($summary['tendv']) . '</strong>
            </td>
            <td style="border: none;">
                Điện thoại liên lạc (Tel): <strong>' . displayTextPdf($summary['dienthoai']) . '</strong>
            </td>
        </tr>
        <tr>
            <td style="border: none;">
                2. Người nhận thiết bị, Принял: <strong>' . displayTextPdf($summary['ngnhyeucau']) . '</strong>
            </td>
            <td style="border: none;">
                Ký tên (Принял /Подпись): .........
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border: none;">
                3. Nội dung:
            </td>
        </tr>
    </table>
</div>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(2);

// Equipment table header
$html = '<table cellpadding="4" border="1" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr style="background-color: #f0f0f0; font-weight: bold; text-align: center;">
            <th style="width: 5%; border: 1px solid #000;">STT<br/>П/П</th>
            <th style="width: 23%; border: 1px solid #000;">Tên thiết bị - Model<br/>Наим-е оборудования</th>
            <th style="width: 13%; border: 1px solid #000;">Số của thiết bị - Serial<br/>Номер</th>
            <th style="width: 22%; border: 1px solid #000;">Mô tả chi tiết tình trạng kỹ thuật của thiết bị trước khi đưa về Xưởng<br/>Тех. состояние</th>
            <th style="width: 14%; border: 1px solid #000;">Nội dung yêu cầu<br/>Требование</th>
            <th style="width: 13%; border: 1px solid #000;">Thiết bị từ đâu đưa về Xưởng<br/>Оборудование</th>
        </tr>
    </thead>
    <tbody>';

// Equipment table rows
$counter = 1;
foreach ($devices as $device) {
    $tenmay = displayTextPdf($device['tenvt'] ?? $device['mavt']);
    if (!empty($device['model'])) {
        $tenmay .= ' - ' . displayTextPdf($device['model']);
    }
    
    $html .= '<tr>
            <td style="width: 5%; text-align: center; border: 1px solid #000;">' . $counter . '</td>
            <td style="width: 23%; text-align: center; border: 1px solid #000;">' . $tenmay . '</td>
            <td style="width: 13%; text-align: center; border: 1px solid #000;">' . displayTextPdf($device['somay']) . '</td>
            <td style="width: 22%; text-align: center; border: 1px solid #000;">' . nl2br(displayTextPdf($device['ttktbefore'] ?? '')) . '</td>
            <td style="width: 14%; text-align: center; border: 1px solid #000;">' . displayTextPdf($device['cv'] ?? '') . '</td>
            <td style="width: 13%; text-align: center; border: 1px solid #000;">' . displayTextPdf($device['vitrimaybd'] ?? '') . '</td>
        </tr>';
    $counter++;
}

$html .= '</tbody>
</table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

// Note section
$html = '<p style="font-size: 9pt; font-style: italic;">
    <strong>Ghi chú:</strong> Cột "Nội dung yêu cầu" được ghi như sau:<br/>
    <strong>BD:</strong> Yêu cầu bảo dưỡng thiết bị / <strong>SC:</strong> Yêu cầu sửa chữa thiết bị bị hỏng / <strong>KT:</strong> Yêu cầu kiểm tra sự hoạt động của thiết bị mà không cần bảo dưỡng (VD như: KT để nghiệm thu TB mới, KT tình trạng của thiết bị đã được BD trước đây nhưng chưa thả đo trong giếng khoan, v.v.).
</p>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(2);

// Additional requirements
$html = '<p><strong>4. Các yêu cầu khác (nếu có):</strong> ' . nl2br(displayTextPdf($summary['ycthemkh'])) . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');

// Production service info
$lo = $devices[0]['lo'] ?? '';
$html = '<p><strong>5. Phục vụ sản xuất cho Lô/ Dịch vụ ngoài:</strong> ' . displayTextPdf($lo) . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');

$mo = $devices[0]['mo'] ?? '';
$gieng = $devices[0]['gieng'] ?? '';
$html = '<p><strong>Tên mỏ:</strong> ' . displayTextPdf($mo) . ' &nbsp;&nbsp;&nbsp; <strong>Tên giếng:</strong> ' . displayTextPdf($gieng) . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');

// Workshop review
$xemxetxuong = $devices[0]['xemxetxuong'] ?? '';
$html = '<p><strong>6. Xem xét của lãnh đạo Xưởng (nếu có):</strong> ' . nl2br(displayTextPdf($xemxetxuong)) . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(5);

// Signature section
$html = '<p><strong>Lãnh đạo Xưởng / Trưởng nhóm</strong> <i>(ký ghi rõ họ tên)</i></p>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$filename = $summary['phieu'] . '-YCDV.pdf';
$pdf->Output($filename, 'I');
exit;
