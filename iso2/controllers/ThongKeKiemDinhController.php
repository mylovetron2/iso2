<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/KeHoach.php';
require_once __DIR__ . '/../models/HoSoHCKD.php';

class ThongKeKiemDinhController {
    private KeHoach $keHoachModel;
    private HoSoHCKD $hoSoHCKDModel;
    
    public function __construct() {
        $this->keHoachModel = new KeHoach();
        $this->hoSoHCKDModel = new HoSoHCKD();
    }
    
    /**
     * Calculate date range for plan validation
     * Returns [start_date, end_date]
     */
    private function calculateDateRange(int $thang, int $namkh, int $planCount): array {
        if ($planCount == 1) {
            // Single plan: 3 consecutive months (thang-2, thang-1, thang)
            $startMonth = max(1, $thang - 2);
            $endMonth = $thang;
            
            $startDate = date('Y-m-d', strtotime("{$namkh}-{$startMonth}-01"));
            $endDate = date('Y-m-t', strtotime("{$namkh}-{$endMonth}-01")); // Last day of month
        } else {
            // Multiple plans: month ±15 days
            $midDate = date('Y-m-15', strtotime("{$namkh}-{$thang}-01"));
            $startDate = date('Y-m-d', strtotime($midDate . ' -15 days'));
            $endDate = date('Y-m-d', strtotime($midDate . ' +15 days'));
        }
        
        return [$startDate, $endDate];
    }
    
    /**
     * Classify inspection status for a device
     */
    private function classifyInspection(array $plan, array $inspections, int $planCount): array {
        $somay = $plan['somay'];
        $thang = (int)$plan['thang'];
        $namkh = (int)$plan['namkh'];
        
        [$startDate, $endDate] = $this->calculateDateRange($thang, $namkh, $planCount);
        
        $result = [
            'plan' => $plan,
            'inspection' => null,
            'status' => 'chua_kiem_dinh', // Default: not inspected
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'plan_count' => $planCount
        ];
        
        // Find matching inspection
        // Match by mahieu (from kehoach_iso) = tenmay (from hosohckd_iso)
        $mahieu = $plan['mahieu'];
        $matchingInspection = null;
        foreach ($inspections as $inspection) {
            if ($inspection['tenmay'] == $mahieu && $inspection['namkh'] == $namkh) {
                $matchingInspection = $inspection;
                break;
            }
        }
        
        if (!$matchingInspection || empty($matchingInspection['ngayhc'])) {
            return $result; // Not inspected
        }
        
        $result['inspection'] = $matchingInspection;
        $ngayhc = $matchingInspection['ngayhc'];
        
        // Classify based on inspection date
        if ($ngayhc < $startDate) {
            $result['status'] = 'truoc_han';
        } elseif ($ngayhc > $endDate) {
            $result['status'] = 'sau_han';
        } else {
            $result['status'] = 'dung_ke_hoach';
        }
        
        return $result;
    }
    
    /**
     * Get statistics for a specific year
     */
    public function getStatistics(int $namkh): array {
        $plans = $this->keHoachModel->getByYear($namkh);
        $inspections = $this->hoSoHCKDModel->getByYear($namkh);
        
        $statistics = [
            'dung_ke_hoach' => [],
            'chua_kiem_dinh' => [],
            'truoc_han' => [],
            'sau_han' => []
        ];
        
        // Group plans by device to count
        $devicePlanCounts = [];
        foreach ($plans as $plan) {
            $somay = $plan['somay'];
            if (!isset($devicePlanCounts[$somay])) {
                $devicePlanCounts[$somay] = 0;
            }
            $devicePlanCounts[$somay]++;
        }
        
        // Process each plan
        foreach ($plans as $plan) {
            $somay = $plan['somay'];
            $planCount = $devicePlanCounts[$somay];
            
            $result = $this->classifyInspection($plan, $inspections, $planCount);
            $status = $result['status'];
            
            $statistics[$status][] = $result;
        }
        
        // Add summary counts
        $totalCompleted = count($statistics['dung_ke_hoach']) + count($statistics['truoc_han']) + count($statistics['sau_han']);
        $summary = [
            'total_plans' => count($plans),
            'dung_ke_hoach' => count($statistics['dung_ke_hoach']),
            'chua_kiem_dinh' => count($statistics['chua_kiem_dinh']),
            'truoc_han' => count($statistics['truoc_han']),
            'sau_han' => count($statistics['sau_han']),
            'tyle_hoan_thanh' => count($plans) > 0 
                ? round(($totalCompleted / count($plans)) * 100, 2)
                : 0
        ];
        
        return [
            'summary' => $summary,
            'details' => $statistics,
            'namkh' => $namkh
        ];
    }
    
    /**
     * Display statistics view
     */
    public function index(): void {
        $namkh = isset($_GET['namkh']) ? (int)$_GET['namkh'] : (int)date('Y');
        $availableYears = $this->keHoachModel->getDistinctYears();
        
        if (empty($availableYears)) {
            $availableYears = [(int)date('Y')];
        }
        
        $statistics = $this->getStatistics($namkh);
        
        require_once __DIR__ . '/../views/thongke_kiemdinh/index.php';
    }
    
    /**
     * Export statistics to Word document
     */
    public function exportWord(): void {
        $namkh = isset($_GET['namkh']) ? (int)$_GET['namkh'] : (int)date('Y');
        $statistics = $this->getStatistics($namkh);
        
        // Set headers for Word download
        header('Content-Type: application/vnd.ms-word');
        header('Content-Disposition: attachment;filename="Bao_cao_kiem_dinh_' . $namkh . '.doc"');
        header('Cache-Control: max-age=0');
        
        // Generate Word HTML content
        require_once __DIR__ . '/../views/thongke_kiemdinh/export_word.php';
        exit;
    }
    
    /**
     * Export statistics to PDF document
     */
    public function exportPdf(): void {
        $namkh = isset($_GET['namkh']) ? (int)$_GET['namkh'] : (int)date('Y');
        $statistics = $this->getStatistics($namkh);
        $year = $namkh;
        
        // Use TCPDF library
        require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('ISO System');
        $pdf->SetAuthor('ISO System');
        $pdf->SetTitle('Báo cáo Thống kê Kiểm định ' . $namkh);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Output header and summary HTML
        ob_start();
        ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size: 16pt; font-weight: bold; color: #1e40af; margin: 0;">BÁO CÁO THỐNG KÊ KIỂM ĐỊNH THIẾT BỊ</h2>
            <p style="font-size: 12pt; font-style: italic; margin: 10px 0;">Năm <?php echo $namkh; ?></p>
        </div>

        <div style="border: 2px solid #2563eb; padding: 10px; margin: 15px 0; background-color: #eff6ff;">
            <h3 style="margin-top: 0; color: #1e40af; font-size: 13pt;">TỔNG QUAN</h3>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Tổng số kế hoạch:</span>
                <span style="font-weight: bold; color: #1e40af;"><?php echo $statistics['summary']['total_plans']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Đúng kế hoạch:</span>
                <span style="font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['dung_ke_hoach']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Chưa kiểm định:</span>
                <span style="font-weight: bold; color: #dc2626;"><?php echo $statistics['summary']['chua_kiem_dinh']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Trước hạn:</span>
                <span style="font-weight: bold; color: #0d9488;"><?php echo $statistics['summary']['truoc_han']; ?> thiết bị</span>
            </div>
            <div style="padding: 5px 0; border-bottom: 1px solid #ddd;">
                <span style="font-weight: bold;">Sau hạn:</span>
                <span style="font-weight: bold; color: #0891b2;"><?php echo $statistics['summary']['sau_han']; ?> thiết bị</span>
            </div>
            <div style="border-bottom: none; margin-top: 10px; background-color: white; padding: 8px;">
                <span style="font-weight: bold; font-size: 13pt;">TỶ LỆ HOÀN THÀNH:</span>
                <span style="font-size: 16pt; font-weight: bold; color: #16a34a;"><?php echo $statistics['summary']['tyle_hoan_thanh']; ?>%</span>
            </div>
        </div>
        <?php
        $html_summary = ob_get_clean();
        $pdf->writeHTML($html_summary, true, false, true, false, '');
        
        // Draw Pie Chart
        $total = $statistics['summary']['total_plans'];
        if ($total > 0) {
            $pdf->Ln(5);
            
            // Title
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->SetTextColor(30, 64, 175);
            $pdf->Cell(0, 8, 'BIỂU ĐỒ PHÂN BỔ TRẠNG THÁI', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Calculate data
            $data = [
                $statistics['summary']['dung_ke_hoach'],
                $statistics['summary']['chua_kiem_dinh'],
                $statistics['summary']['truoc_han'],
                $statistics['summary']['sau_han']
            ];
            $colors = [
                [22, 163, 74],    // Green
                [220, 38, 38],    // Red
                [13, 148, 136],   // Teal
                [8, 145, 178]     // Cyan
            ];
            $labels = [
                'Đúng kế hoạch: ' . $data[0] . ' (' . round(($data[0]/$total)*100, 1) . '%)',
                'Chưa kiểm định: ' . $data[1] . ' (' . round(($data[1]/$total)*100, 1) . '%)',
                'Trước hạn: ' . $data[2] . ' (' . round(($data[2]/$total)*100, 1) . '%)',
                'Sau hạn: ' . $data[3] . ' (' . round(($data[3]/$total)*100, 1) . '%)'
            ];
            
            // Draw pie chart without labels first
            $xc = 105;  // Center X
            $yc = $pdf->GetY() + 45;   // Center Y
            $r = 35;    // Radius
            
            $startAngle = 0;
            $labelPositions = [];
            
            for ($i = 0; $i < count($data); $i++) {
                if ($data[$i] <= 0) continue;
                
                $angle = ($data[$i] / $total) * 360;
                $endAngle = $startAngle + $angle;
                $percentage = round(($data[$i] / $total) * 100, 1);
                
                // Draw sector
                $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $pdf->PieSector($xc, $yc, $r, $startAngle, $endAngle, 'F', false, 0, 2);
                
                // Store label info
                if ($percentage >= 5) {
                    $labelPositions[] = [
                        'start' => $startAngle,
                        'end' => $endAngle,
                        'percentage' => $percentage
                    ];
                }
                
                $startAngle = $endAngle;
            }
            
            // Now draw all labels
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetTextColor(255, 255, 255);
            
            foreach ($labelPositions as $pos) {
                $midAngle = ($pos['start'] + $pos['end']) / 2;
                
                // TCPDF coordinate system: 0° = East (right), counter-clockwise
                // But Y-axis increases downward, making it appear clockwise
                // Need to invert the angle: use negative angle
                $angleRad = deg2rad(-$midAngle);
                
                $labelRadius = $r * 0.7;
                $labelX = $xc + $labelRadius * cos($angleRad);
                $labelY = $yc + $labelRadius * sin($angleRad);
                
                $text = $pos['percentage'] . '%';
                $textWidth = $pdf->GetStringWidth($text);
                
                // Position text centered at the calculated point
                $pdf->Text($labelX - ($textWidth / 2), $labelY + 1.5, $text);
            }
            
            // Draw legend
            $pdf->SetFont('dejavusans', '', 10);
            $legendY = $yc + $r + 10;
            
            foreach ($labels as $i => $label) {
                // Color box
                $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $pdf->Rect(30, $legendY + ($i * 10), 6, 6, 'F');
                
                // Label
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY(38, $legendY + ($i * 10) - 1);
                $pdf->Cell(0, 8, $labels[$i], 0, 1, 'L');
            }
        }
        
        // Add new page for device lists
        $pdf->AddPage();
        
        // Generate device lists HTML content
        ob_start();
        require __DIR__ . '/../views/thongke_kiemdinh/export_pdf.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $pdf->Output('Bao_cao_kiem_dinh_' . $namkh . '.pdf', 'D');
        exit;
    }
}
