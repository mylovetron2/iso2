<?php
/**
 * Debug script - Test get công việc API
 */

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG: Get Công Việc API</h2>";

// Load dependencies
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/CongViecSuaChua.php';
require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';

echo "<h3>Step 1: Find existing công việc</h3>";
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT stt, nhanvien_stt, ngay_lam, noi_dung FROM congviec_suachua_iso LIMIT 5");
    $congviecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($congviecs) {
        echo "<p>✓ Tìm thấy " . count($congviecs) . " công việc:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>STT</th><th>Nhân viên</th><th>Ngày</th><th>Nội dung</th><th>Test</th></tr>";
        foreach ($congviecs as $cv) {
            echo "<tr>";
            echo "<td>{$cv['stt']}</td>";
            echo "<td>{$cv['nhanvien_stt']}</td>";
            echo "<td>{$cv['ngay_lam']}</td>";
            echo "<td>" . htmlspecialchars(mb_substr($cv['noi_dung'], 0, 30)) . "...</td>";
            echo "<td><a href='?test_stt={$cv['stt']}' target='_blank'>Test Get</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $testStt = isset($congviecs[0]['stt']) ? $congviecs[0]['stt'] : null;
    } else {
        echo "<p>⚠ Không có công việc nào trong database</p>";
        $testStt = null;
    }
} catch (Exception $e) {
    echo "<p>✗ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
    $testStt = null;
}

// Test với STT từ query string hoặc first record
$testStt = (int)($_GET['test_stt'] ?? $testStt ?? 0);

if ($testStt > 0) {
    echo "<hr>";
    echo "<h3>Step 2: Test Model find($testStt)</h3>";
    
    try {
        $model = new CongViecSuaChua();
        echo "<p>✓ Model created</p>";
        
        $result = $model->find($testStt);
        echo "<p>✓ find() executed</p>";
        
        if ($result === false) {
            echo "<p>✗ find() returned FALSE - Record not found</p>";
        } else {
            echo "<p>✓ find() returned data:</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    echo "<hr>";
    echo "<h3>Step 3: Test Controller get($testStt)</h3>";
    
    try {
        $controller = new CongViecSuaChuaController();
        echo "<p>✓ Controller created</p>";
        
        $result = $controller->get($testStt);
        echo "<p>✓ get() executed</p>";
        
        echo "<p>Result:</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        
        if ($result['success']) {
            echo "<p>✓ SUCCESS - Data ready for edit</p>";
        } else {
            echo "<p>✗ FAILED: " . htmlspecialchars($result['message']) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    echo "<hr>";
    echo "<h3>Step 4: Test Full API Call</h3>";
    
    $apiUrl = "/iso2/congviec_suachua.php?action=get&stt=" . $testStt;
    echo "<p>API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
    
    echo "<button onclick='testAPI()'>Test AJAX Call</button>";
    echo "<div id='apiResult' style='margin-top: 10px; padding: 10px; border: 1px solid #ccc;'></div>";
    
    echo "<script>
    async function testAPI() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.innerHTML = '<p>Loading...</p>';
        
        try {
            const response = await fetch('$apiUrl', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            resultDiv.innerHTML = '<p>HTTP Status: ' + response.status + '</p>';
            
            if (!response.ok) {
                const errorText = await response.text();
                resultDiv.innerHTML += '<p style=\"color: red;\">✗ HTTP Error!</p>';
                resultDiv.innerHTML += '<pre>' + errorText + '</pre>';
                return;
            }
            
            const responseText = await response.text();
            resultDiv.innerHTML += '<p>Response Text:</p>';
            resultDiv.innerHTML += '<pre>' + responseText + '</pre>';
            
            try {
                const data = JSON.parse(responseText);
                resultDiv.innerHTML += '<p style=\"color: green;\">✓ Valid JSON</p>';
                resultDiv.innerHTML += '<p>Parsed Data:</p>';
                resultDiv.innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (e) {
                resultDiv.innerHTML += '<p style=\"color: red;\">✗ Not valid JSON: ' + e.message + '</p>';
            }
        } catch (error) {
            resultDiv.innerHTML += '<p style=\"color: red;\">✗ Fetch error: ' + error.message + '</p>';
        }
    }
    </script>";
}

echo "<hr>";
echo "<p><strong>Hướng dẫn:</strong></p>";
echo "<ol>";
echo "<li>Kiểm tra danh sách công việc ở trên</li>";
echo "<li>Click 'Test Get' để test từng record</li>";
echo "<li>Xem kết quả Model find() và Controller get()</li>";
echo "<li>Click 'Test AJAX Call' để test API endpoint thực tế</li>";
echo "</ol>";
?>
