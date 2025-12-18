<?php
declare(strict_types=1);

class ThongKeThietBiChuaKDController {
    
    /**
     * Get statistics of devices not yet inspected in a year, grouped by department
     * Logic: Check hosohckd_iso table for inspection records
     * Mapping: thietbi_iso.mamay = hosohckd_iso.tenmay (same as kehoach_iso.mahieu = hosohckd_iso.tenmay)
     */
    public function getStatistics(int $year = null, string $madv = ''): array {
        global $conn;
        
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        // Build WHERE clause for madv filter
        $madvFilter = '';
        if (!empty($madv)) {
            $madvFilter = 'AND d.madv = ' . $conn->quote($madv);
        }
        
        // Get all devices grouped by department with inspection counts
        $sql = "SELECT 
                    d.madv,
                    d.tendv,
                    COUNT(DISTINCT th.mavattu) as total_devices,
                    COUNT(DISTINCT CASE 
                        WHEN h.stt IS NOT NULL 
                        THEN h.tenmay
                    END) as inspected_devices,
                    COUNT(DISTINCT th.mavattu) - 
                    COUNT(DISTINCT CASE 
                        WHEN h.stt IS NOT NULL 
                        THEN h.tenmay
                    END) as not_inspected_devices
                FROM donvi_iso d
                INNER JOIN thietbihckd_iso th ON d.madv = th.bophansh
                INNER JOIN kehoach_iso k ON th.mavattu = k.mahieu AND k.namkh = {$year}
                LEFT JOIN hosohckd_iso h ON th.mavattu = h.tenmay
                    AND h.namkh = {$year}
                WHERE th.mavattu IS NOT NULL 
                    AND th.mavattu != '' 
                    $madvFilter
                GROUP BY d.madv, d.tendv
                HAVING total_devices > 0
                ORDER BY not_inspected_devices DESC, d.tendv ASC";
        
        $stmt = $conn->query($sql);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get detailed not-inspected devices for each department
        foreach ($departments as &$dept) {
            $madv_escaped = $conn->quote($dept['madv']);
            $detailSql = "SELECT 
                            th.tenthietbi,
                            th.somay,
                            th.hangsx,
                            th.mavattu
                        FROM thietbihckd_iso th
                        INNER JOIN kehoach_iso k ON th.mavattu = k.mahieu AND k.namkh = {$year}
                        WHERE th.bophansh = $madv_escaped
                            AND th.mavattu IS NOT NULL
                            AND th.mavattu != ''
                            AND NOT EXISTS (
                                SELECT 1 FROM hosohckd_iso h 
                                WHERE h.tenmay = th.mavattu
                                    AND h.namkh = {$year}
                            )
                        ORDER BY th.tenthietbi ASC, th.somay ASC";
            
            $stmt = $conn->query($detailSql);
            $dept['devices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Calculate summary statistics
        $summary = [
            'total_departments' => count($departments),
            'total_devices' => array_sum(array_column($departments, 'total_devices')),
            'inspected_devices' => array_sum(array_column($departments, 'inspected_devices')),
            'not_inspected_devices' => array_sum(array_column($departments, 'not_inspected_devices'))
        ];
        
        return [
            'departments' => $departments,
            'summary' => $summary
        ];
    }
    
    /**
     * Display statistics page
     */
    public function index(): void {
        global $conn;
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $madv = isset($_GET['madv']) ? trim($_GET['madv']) : '';
        
        // Get list of departments for dropdown
        $deptSql = "SELECT DISTINCT madv, tendv FROM donvi_iso ORDER BY tendv ASC";
        $deptStmt = $conn->query($deptSql);
        $departments_list = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $statistics = $this->getStatistics($year, $madv);
        require_once __DIR__ . '/../views/thongke_thietbi_chuakd/index.php';
    }
    
    /**
     * Export to PDF
     */
    public function exportPdf(): void {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $madv = isset($_GET['madv']) ? trim($_GET['madv']) : '';
        
        $statistics = $this->getStatistics($year, $madv);
        
        // Use TCPDF library
        require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
        
        // Create new PDF document
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('ISO 2.0');
        $pdf->SetAuthor('Phòng Kỹ Thuật');
        $pdf->SetTitle('Thiết bị chưa Kiểm định');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Output HTML content
        ob_start();
        require_once __DIR__ . '/../views/thongke_thietbi_chuakd/export_pdf.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('thietbi_chua_kiemdinh_' . $year . '.pdf', 'D');
        exit;
    }
}
