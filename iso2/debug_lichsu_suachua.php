<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Lịch sử Sửa chữa/Bảo dưỡng</h2>";

require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

if (!$conn) {
    die("❌ Database connection failed!");
}

echo "✅ Database connected<br><br>";

// 1. Check thietbi_iso table structure
echo "<h3>1. Check thietbi_iso structure:</h3>";
try {
    $stmt = $conn->query("DESCRIBE thietbi_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 2. Check sample data from thietbi_iso
echo "<h3>2. Sample data from thietbi_iso (with mamay):</h3>";
try {
    $stmt = $conn->query("SELECT stt, mavt, tenvt, somay, mamay FROM thietbi_iso WHERE mamay IS NOT NULL AND mamay != '' LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($samples)) {
        echo "⚠️ No records found with mamay value<br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>STT</th><th>Mã VT</th><th>Tên VT</th><th>Số máy</th><th>Mã máy</th></tr>";
        foreach ($samples as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['stt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mavt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tenvt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['somay']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mamay'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 3. Check hososcbd_iso table structure
echo "<h3>3. Check hososcbd_iso structure:</h3>";
try {
    $stmt = $conn->query("DESCRIBE hososcbd_iso");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 4. Check sample data from hososcbd_iso
echo "<h3>4. Sample data from hososcbd_iso (with mamay):</h3>";
try {
    $stmt = $conn->query("SELECT stt, hoso, phieu, mamay, ngaykt, honghoc, khacphuc FROM hososcbd_iso WHERE mamay IS NOT NULL AND mamay != '' LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($samples)) {
        echo "⚠️ No records found with mamay value in hososcbd_iso<br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>STT</th><th>Hồ sơ</th><th>Phiếu</th><th>Mã máy</th><th>Ngày KT</th><th>Hỏng hóc</th><th>Khắc phục</th></tr>";
        foreach ($samples as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['stt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['hoso'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['phieu'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($row['mamay'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['ngaykt'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['honghoc'] ?? '', 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars(substr($row['khacphuc'] ?? '', 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 5. Test matching between thietbi and hososcbd
echo "<h3>5. Test matching data:</h3>";
try {
    $stmt = $conn->query("
        SELECT t.stt as thietbi_stt, t.mavt, t.tenvt, t.mamay, 
               COUNT(h.stt) as count_lichsu
        FROM thietbi_iso t
        LEFT JOIN hososcbd_iso h ON t.mamay = h.mamay AND t.mamay IS NOT NULL AND t.mamay != ''
        WHERE t.mamay IS NOT NULL AND t.mamay != ''
        GROUP BY t.stt
        LIMIT 10
    ");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($matches)) {
        echo "⚠️ No matching data found<br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Thiết bị STT</th><th>Mã VT</th><th>Tên VT</th><th>Mã máy</th><th>Số lịch sử</th></tr>";
        foreach ($matches as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['thietbi_stt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mavt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tenvt']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mamay']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['count_lichsu']) . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 6. Test specific record (if provided)
if (isset($_GET['stt'])) {
    $stt = (int)$_GET['stt'];
    echo "<h3>6. Testing specific record (STT: $stt):</h3>";
    
    try {
        $stmt = $conn->prepare("SELECT * FROM thietbi_iso WHERE stt = ?");
        $stmt->execute([$stt]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            echo "❌ Thiết bị not found<br>";
        } else {
            echo "✅ Thiết bị found:<br>";
            echo "Mã máy: <strong>" . htmlspecialchars($item['mamay'] ?? 'NULL') . "</strong><br><br>";
            
            if (empty($item['mamay'])) {
                echo "⚠️ WARNING: mamay is empty!<br>";
            } else {
                // Query repair history
                $stmt2 = $conn->prepare("SELECT hoso, phieu, ngaykt, honghoc, khacphuc, noidung 
                                        FROM hososcbd_iso 
                                        WHERE mamay = ? 
                                        ORDER BY ngaykt DESC");
                $stmt2->execute([$item['mamay']]);
                $history = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                
                echo "Found <strong>" . count($history) . "</strong> repair history records<br><br>";
                
                if (!empty($history)) {
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr><th>Hồ sơ</th><th>Phiếu</th><th>Ngày KT</th><th>Hỏng hóc</th></tr>";
                    foreach ($history as $h) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($h['hoso'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($h['phieu'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($h['ngaykt'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars(substr($h['honghoc'] ?? '', 0, 100)) . "...</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<br><br><strong>Usage:</strong> Add ?stt=XXX to URL to test specific record";
?>
