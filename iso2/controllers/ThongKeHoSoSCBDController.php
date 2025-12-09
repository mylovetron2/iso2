<?php
declare(strict_types=1);

class ThongKeHoSoSCBDController {
    
    /**
     * Calculate business days between two dates (excluding Saturdays and Sundays)
     */
    private function getBusinessDays($startDate, $endDate): int {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        if ($start > $end) {
            return 0;
        }
        
        $businessDays = 0;
        $current = clone $start;
        
        while ($current <= $end) {
            $dayOfWeek = (int)$current->format('N'); // 1 (Monday) to 7 (Sunday)
            if ($dayOfWeek < 6) { // Monday to Friday (1-5)
                $businessDays++;
            }
            $current->modify('+1 day');
        }
        
        return $businessDays;
    }
    
    /**
     * Get statistics for records where ngaykt >= ngayth + X days (excluding weekends)
     */
    public function getStatistics(int $minDays = 30, int $year = null): array {
        global $conn;
        
        if ($year === null) {
            $year = (int)date('Y');
        }
        
        $sql = "SELECT 
                    h.*,
                    t.tenvt as tentb,
                    t.model as mahieu,
                    d.tendv
                FROM hososcbd_iso h
                LEFT JOIN thietbi_iso t ON h.mavt = t.mavt AND h.somay = t.somay
                LEFT JOIN donvi_iso d ON h.madv = d.madv
                WHERE h.ngayth IS NOT NULL 
                    AND h.ngaykt IS NOT NULL
                    AND YEAR(h.ngayth) = ?
                ORDER BY h.ngaykt DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$year]);
        $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter by business days and calculate so_ngay_tre
        $records = [];
        foreach ($allRecords as $record) {
            $businessDays = $this->getBusinessDays($record['ngayth'], $record['ngaykt']);
            if ($businessDays >= $minDays) {
                $record['so_ngay_tre'] = $businessDays;
                $records[] = $record;
            }
        }
        
        // Sort by so_ngay_tre descending
        usort($records, function($a, $b) {
            return $b['so_ngay_tre'] - $a['so_ngay_tre'];
        });
        
        // Calculate summary statistics
        $summary = [
            'total' => count($records),
            'avg_days' => 0,
            'max_days' => 0,
            'min_days' => 0
        ];
        
        if ($summary['total'] > 0) {
            $days = array_column($records, 'so_ngay_tre');
            $summary['avg_days'] = round(array_sum($days) / count($days), 1);
            $summary['max_days'] = max($days);
            $summary['min_days'] = min($days);
        }
        
        return [
            'records' => $records,
            'summary' => $summary
        ];
    }
    
    /**
     * Display statistics page
     */
    public function index(): void {
        $minDays = isset($_GET['min_days']) ? (int)$_GET['min_days'] : 30;
        if ($minDays < 1) $minDays = 30;
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        $statistics = $this->getStatistics($minDays, $year);
        require_once __DIR__ . '/../views/thongke_hososcbd/index.php';
    }
    
    /**
     * Export to PDF
     */
    public function exportPdf(): void {
        $minDays = isset($_GET['min_days']) ? (int)$_GET['min_days'] : 30;
        if ($minDays < 1) $minDays = 30;
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        $statistics = $this->getStatistics($minDays, $year);
        
        // Use TCPDF library
        require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false); // Landscape
        
        // Set document information
        $pdf->SetCreator('ISO System');
        $pdf->SetAuthor('ISO System');
        $pdf->SetTitle('Báo cáo Hồ sơ SCBD trễ hạn');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('dejavusans', '', 9);
        
        // Output header and summary HTML
        ob_start();
        require __DIR__ . '/../views/thongke_hososcbd/export_pdf.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('Bao_cao_SCBD_tre_han_' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }
}
