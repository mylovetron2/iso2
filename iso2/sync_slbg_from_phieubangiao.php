<?php
/**
 * Đồng bộ lại trường slbg trong hososcbd_iso từ phieubangiao_iso
 * Sửa các thiết bị có slbg = 0 hoặc NULL nhưng đã được bàn giao
 */

require_once __DIR__ . '/config/constants.php';

try {
    $db = getDbConnection();
    
    echo "<h2>Đồng bộ lại slbg trong hososcbd_iso</h2>";
    
    // Lấy tất cả thiết bị đã bàn giao (bg = 1)
    $sql = "SELECT h.stt, h.hoso, h.phieu, h.mavt, h.somay, h.bg, h.slbg, h.maql
            FROM hososcbd_iso h
            WHERE h.bg = 1
            ORDER BY h.phieu, h.stt";
    
    $stmt = $db->query($sql);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tìm thấy <strong>" . count($devices) . "</strong> thiết bị đã bàn giao (bg=1)</p>";
    
    $updatedCount = 0;
    $errorCount = 0;
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr style='background-color: #f0f0f0;'>
            <th>STT</th>
            <th>Phiếu YC</th>
            <th>Mã VT</th>
            <th>Số máy</th>
            <th>SLBG cũ</th>
            <th>SLBG mới</th>
            <th>Trạng thái</th>
          </tr>";
    
    foreach ($devices as $device) {
        $phieu = $device['phieu'];
        $stt = $device['stt'];
        $currentSlbg = (int)($device['slbg'] ?? 0);
        
        // Tìm phiếu bàn giao của thiết bị này
        $sqlBG = "SELECT pbg.sophieu 
                  FROM phieubangiao_iso pbg
                  INNER JOIN phieubangiao_thietbi_iso pbt ON pbg.sophieu = pbt.sophieu
                  WHERE pbt.hososcbd_stt = :stt
                  ORDER BY pbg.stt DESC
                  LIMIT 1";
        
        $stmtBG = $db->prepare($sqlBG);
        $stmtBG->execute([':stt' => $stt]);
        $phieuBG = $stmtBG->fetch(PDO::FETCH_ASSOC);
        
        if ($phieuBG) {
            // Parse slbg từ số phiếu (ví dụ: "1984-1" => 1)
            $sophieu = $phieuBG['sophieu'];
            $newSlbg = 0;
            
            if (preg_match('/-(\d+)$/', $sophieu, $matches)) {
                $newSlbg = (int)$matches[1];
            }
            
            $status = "";
            $rowColor = "";
            
            if ($newSlbg > 0 && $currentSlbg != $newSlbg) {
                // Cần cập nhật
                $updateSQL = "UPDATE hososcbd_iso SET slbg = :slbg WHERE stt = :stt";
                $updateStmt = $db->prepare($updateSQL);
                
                if ($updateStmt->execute([':slbg' => $newSlbg, ':stt' => $stt])) {
                    $status = "✓ Đã cập nhật";
                    $rowColor = "background-color: #d4edda;"; // Xanh lá nhạt
                    $updatedCount++;
                } else {
                    $status = "✗ Lỗi cập nhật";
                    $rowColor = "background-color: #f8d7da;"; // Đỏ nhạt
                    $errorCount++;
                }
            } elseif ($currentSlbg == $newSlbg) {
                $status = "Đã đúng";
                $rowColor = "background-color: #fff;"; // Trắng
            } else {
                $status = "Không parse được";
                $rowColor = "background-color: #fff3cd;"; // Vàng nhạt
            }
            
            echo "<tr style='$rowColor'>";
            echo "<td>" . htmlspecialchars($stt) . "</td>";
            echo "<td>" . htmlspecialchars($phieu) . " → " . htmlspecialchars($sophieu) . "</td>";
            echo "<td>" . htmlspecialchars($device['mavt']) . "</td>";
            echo "<td>" . htmlspecialchars($device['somay'] ?? '-') . "</td>";
            echo "<td>" . ($currentSlbg ?: "<em>NULL</em>") . "</td>";
            echo "<td><strong>" . $newSlbg . "</strong></td>";
            echo "<td>" . $status . "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<hr style='margin: 20px 0;'>";
    echo "<h3>Kết quả:</h3>";
    echo "<ul>";
    echo "<li style='color: green;'>✓ Đã cập nhật: <strong>$updatedCount</strong> bản ghi</li>";
    echo "<li style='color: red;'>✗ Lỗi: <strong>$errorCount</strong> bản ghi</li>";
    echo "</ul>";
    
    if ($updatedCount > 0) {
        echo "<p style='color: green; font-weight: bold;'>Đồng bộ thành công! Trường slbg đã được cập nhật.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}
