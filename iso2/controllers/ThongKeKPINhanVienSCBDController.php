<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class ThongKeKPINhanVienSCBDController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    public function index(): void
    {
        $fromDate = $_GET['from'] ?? date('Y-m-01');
        $toDate = $_GET['to'] ?? date('Y-m-d');
        $keyword = trim((string)($_GET['q'] ?? ''));

        if (isset($_GET['export']) && $_GET['export'] === 'excel') {
            $this->exportExcel($fromDate, $toDate, $keyword);
            return;
        }

        $rows = $this->getReportData($fromDate, $toDate, $keyword);
        $detailMap = $this->getDetailData($fromDate, $toDate, $keyword);

        $totalAssigned = 0;
        $totalQualified = 0;
        foreach ($rows as $row) {
            $totalAssigned += (int)$row['tong_thiet_bi_duoc_yeu_cau'];
            $totalQualified += (int)$row['so_thiet_bi_dat_tieu_chi'];
        }

        $overallRate = $totalAssigned > 0
            ? round(($totalQualified * 100.0) / $totalAssigned, 2)
            : 0.0;

        require __DIR__ . '/../views/hososcbd/thongke_kpi_nhanvien.php';
    }

    private function exportExcel(string $fromDate, string $toDate, string $keyword = ''): void
    {
        try {
            $rows = $this->getReportData($fromDate, $toDate, $keyword);
            $detailMap = $this->getDetailData($fromDate, $toDate, $keyword);

            require_once __DIR__ . '/../vendor/autoload.php';

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Thong ke KPI nhan vien');

            $lastCol = 'F';

            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue('A1', 'THỐNG KÊ KPI THEO NHÂN VIÊN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells("A2:{$lastCol}2");
            $sheet->setCellValue('A2', 'Từ ' . date('d/m/Y', strtotime($fromDate)) . ' đến ' . date('d/m/Y', strtotime($toDate)));
            $sheet->getStyle('A2')->getFont()->setBold(true);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ];
            $thinBorder = [
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ];

            // ===== Bang tong hop =====
            $row = 4;
            $sheet->setCellValue("A{$row}", 'STT');
            $sheet->setCellValue("B{$row}", 'Nhân viên');
            $sheet->setCellValue("C{$row}", 'Tổng thiết bị được giao');
            $sheet->setCellValue("D{$row}", 'Đạt tiêu chí KPI');
            $sheet->setCellValue("E{$row}", 'Chưa đạt');
            $sheet->setCellValue("F{$row}", 'Tỷ lệ (%)');
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray($headerStyle);

            $row++;
            $stt = 1;
            foreach ($rows as $r) {
                $assigned = (int)$r['tong_thiet_bi_duoc_yeu_cau'];
                $qualified = (int)$r['so_thiet_bi_dat_tieu_chi'];
                $notQualified = max(0, $assigned - $qualified);
                $rate = isset($r['ty_le_phan_tram']) ? (float)$r['ty_le_phan_tram'] : 0.0;

                $sheet->setCellValue("A{$row}", $stt++);
                $sheet->setCellValue("B{$row}", (string)$r['nhan_vien']);
                $sheet->setCellValue("C{$row}", $assigned);
                $sheet->setCellValue("D{$row}", $qualified);
                $sheet->setCellValue("E{$row}", $notQualified);
                $sheet->setCellValue("F{$row}", $rate);
                $row++;
            }
            $summaryLastRow = $row - 1;
            if ($summaryLastRow >= 5) {
                $sheet->getStyle("A5:{$lastCol}{$summaryLastRow}")->applyFromArray($thinBorder);
                $sheet->getStyle("A5:A{$summaryLastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C5:F{$summaryLastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            // ===== Bang chi tiet =====
            $row += 2;
            $detailLastCol = 'K';
            $sheet->mergeCells("A{$row}:{$detailLastCol}{$row}");
            $sheet->setCellValue("A{$row}", 'CHI TIẾT THIẾT BỊ THEO NHÂN VIÊN');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            $detailHeaders = ['Phiếu', 'Hồ sơ', 'Mã VT', 'Số máy', 'Ngày YC', 'Ngày TH', 'Ngày KT', 'Số giờ định mức', 'Số giờ thực hiện', 'KPI', 'Kết quả'];

            foreach ($detailMap as $employeeName => $details) {
                $sheet->mergeCells("A{$row}:{$detailLastCol}{$row}");
                $sheet->setCellValue("A{$row}", 'Nhân viên: ' . $employeeName);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9E1F2');
                $row++;

                $col = 'A';
                foreach ($detailHeaders as $header) {
                    $sheet->setCellValue($col . $row, $header);
                    $col++;
                }
                $sheet->getStyle("A{$row}:{$detailLastCol}{$row}")->applyFromArray($headerStyle);
                $row++;

                $detailStartRow = $row;
                foreach ($details as $d) {
                    $isQualified = ((int)($d['dat_tieu_chi'] ?? 0)) === 1;
                    $kpiStatus = (string)($d['ket_luan_kpi'] ?? 'chua_du_du_lieu');
                    if ($kpiStatus === 'dat') {
                        $kpiStatus = 'Đạt';
                    } elseif ($kpiStatus === 'chua_du_du_lieu') {
                        $kpiStatus = 'chưa gán KPI';
                    }
                    $sheet->setCellValue("A{$row}", (string)($d['phieu'] ?? ''));
                    $sheet->setCellValue("B{$row}", (string)($d['hoso'] ?? ''));
                    $sheet->setCellValue("C{$row}", (string)($d['mavt'] ?? ''));
                    $sheet->setCellValue("D{$row}", (string)($d['somay'] ?? ''));
                    $sheet->setCellValue("E{$row}", (string)($d['ngayyc'] ?? ''));
                    $sheet->setCellValue("F{$row}", (string)($d['ngayth'] ?? ''));
                    $sheet->setCellValue("G{$row}", (string)($d['ngaykt'] ?? ''));
                    $sheet->setCellValue("H{$row}", $d['dinh_muc_so_gio'] !== null ? (float)$d['dinh_muc_so_gio'] : '');
                    $sheet->setCellValue("I{$row}", $d['gio_thuc_te'] !== null ? (float)$d['gio_thuc_te'] : '');
                    $sheet->setCellValue("J{$row}", $kpiStatus);
                    $sheet->setCellValue("K{$row}", $isQualified ? 'Đạt' : 'Chưa đạt');
                    $row++;
                }
                $detailLastRow = $row - 1;
                if ($detailLastRow >= $detailStartRow) {
                    $sheet->getStyle("A{$detailStartRow}:{$detailLastCol}{$detailLastRow}")->applyFromArray($thinBorder);
                    $sheet->getStyle("A{$detailStartRow}:D{$detailLastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E{$detailStartRow}:K{$detailLastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }

                $row++;
            }

            foreach (['A' => 12, 'B' => 25, 'C' => 12, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 16, 'I' => 16, 'J' => 16, 'K' => 12] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            $filename = 'thong-ke-kpi-nhan-vien-' . $fromDate . '-den-' . $toDate . '.xlsx';

            if (ob_get_length()) {
                ob_end_clean();
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Exception $e) {
            error_log('Error in ThongKeKPINhanVienSCBDController::exportExcel: ' . $e->getMessage());
            die('Có lỗi xảy ra khi xuất Excel: ' . $e->getMessage());
        }
    }

    private function getReportData(string $fromDate, string $toDate, string $keyword = ''): array
    {
        $sql = "SELECT
                    nv.hoten AS nhan_vien,
                    COUNT(DISTINCT h.stt) AS tong_thiet_bi_duoc_yeu_cau,
                    COUNT(DISTINCT CASE
                        WHEN h.ngaykt IS NOT NULL
                             AND h.ngaykt <> '0000-00-00'
                             AND COALESCE(vk.ket_luan_kpi, '') = 'dat'
                        THEN h.stt
                    END) AS so_thiet_bi_dat_tieu_chi,
                    ROUND(
                        COUNT(DISTINCT CASE
                            WHEN h.ngaykt IS NOT NULL
                                 AND h.ngaykt <> '0000-00-00'
                                 AND COALESCE(vk.ket_luan_kpi, '') = 'dat'
                            THEN h.stt
                        END) * 100.0 / NULLIF(COUNT(DISTINCT h.stt), 0),
                        2
                    ) AS ty_le_phan_tram
                FROM
                    (SELECT DISTINCT mahoso, hoten
                     FROM ngthuchien_iso
                     WHERE hoten IS NOT NULL AND hoten <> '') nv
                INNER JOIN hososcbd_iso h
                    ON h.hoso = nv.mahoso
                LEFT JOIN view_hososcbd_kpi_ketluan vk
                    ON vk.hososcbd_stt = h.stt
                                WHERE h.ngayth BETWEEN :from_date_th AND :to_date_th
                                    AND h.ngaykt BETWEEN :from_date_kt AND :to_date_kt
                  AND h.cv IN ('BD', 'KT')";

        $params = [
                        ':from_date_th' => $fromDate,
                        ':to_date_th' => $toDate,
                        ':from_date_kt' => $fromDate,
                        ':to_date_kt' => $toDate,
        ];

        if ($keyword !== '') {
            $sql .= " AND nv.hoten LIKE :keyword";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $sql .= " GROUP BY nv.hoten
                  ORDER BY ty_le_phan_tram DESC, tong_thiet_bi_duoc_yeu_cau DESC, nv.hoten ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDetailData(string $fromDate, string $toDate, string $keyword = ''): array
    {
        $sql = "SELECT
                    nv.hoten AS nhan_vien,
                    h.stt AS hososcbd_stt,
                    h.phieu,
                    h.hoso,
                    h.mavt,
                    h.somay,
                    h.ngayyc,
                    h.ngayth,
                    h.ngaykt,
                    vk.dinh_muc_so_gio,
                    vk.gio_thuc_te,
                    COALESCE(vk.ket_luan_kpi, 'chua_du_du_lieu') AS ket_luan_kpi,
                    CASE
                        WHEN h.ngaykt IS NOT NULL
                             AND h.ngaykt <> '0000-00-00'
                             AND COALESCE(vk.ket_luan_kpi, '') = 'dat'
                        THEN 1
                        ELSE 0
                    END AS dat_tieu_chi
                FROM
                    (SELECT DISTINCT mahoso, hoten
                     FROM ngthuchien_iso
                     WHERE hoten IS NOT NULL AND hoten <> '') nv
                INNER JOIN hososcbd_iso h
                    ON h.hoso = nv.mahoso
                LEFT JOIN view_hososcbd_kpi_ketluan vk
                    ON vk.hososcbd_stt = h.stt
                                WHERE h.ngayth BETWEEN :from_date_th AND :to_date_th
                                    AND h.ngaykt BETWEEN :from_date_kt AND :to_date_kt
                  AND h.cv IN ('BD', 'KT')";

        $params = [
                        ':from_date_th' => $fromDate,
                        ':to_date_th' => $toDate,
                        ':from_date_kt' => $fromDate,
                        ':to_date_kt' => $toDate,
        ];

        if ($keyword !== '') {
            $sql .= " AND nv.hoten LIKE :keyword";
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $sql .= " ORDER BY nv.hoten ASC, h.ngayyc DESC, h.phieu DESC, h.stt DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($items as $item) {
            $name = (string)$item['nhan_vien'];
            if (!isset($map[$name])) {
                $map[$name] = [];
            }
            $map[$name][] = $item;
        }

        return $map;
    }
}
