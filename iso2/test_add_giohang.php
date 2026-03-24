<?php
/**
 * Test script để debug lỗi thêm giỏ hàng
 */

session_start();

// Fake login nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
    $_SESSION['username'] = 'test_user';
}

require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 DEBUG: Test Thêm Giỏ Hàng</h2>";
echo "<hr>";

try {
    $db = getDBConnection();
    echo "✅ Database connected: " . get_class($db) . "<br><br>";
    
    // Check bảng cart_vattu_thanh_ly có tồn tại không
    echo "<h3>1. Check bảng cart_vattu_thanh_ly:</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'cart_vattu_thanh_ly'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ Bảng cart_vattu_thanh_ly đã tồn tại<br><br>";
        
        // Xem cấu trúc bảng
        echo "<h3>2. Cấu trúc bảng cart_vattu_thanh_ly:</h3>";
        echo "<pre>";
        $stmt = $db->query("DESCRIBE cart_vattu_thanh_ly");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
        echo "</pre>";
        
    } else {
        echo "❌ Bảng cart_vattu_thanh_ly CHƯA TỒN TẠI!<br>";
        echo "👉 Cần chạy file SQL: setup_giohang_phieudathang.sql<br><br>";
    }
    
    // Lấy 1 vật tư để test
    echo "<h3>3. Lấy vật tư để test:</h3>";
    $stmt = $db->query("SELECT stt, mavattu, ten_tiengviet FROM vattu_thanh_ly_iso LIMIT 1");
    $vattu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vattu) {
        echo "✅ Vật tư test: STT={$vattu['stt']}, Mã={$vattu['mavattu']}, Tên={$vattu['ten_tiengviet']}<br><br>";
        
        if ($tableExists) {
            // Test INSERT vào giỏ hàng
            echo "<h3>4. Test INSERT vào giỏ hàng:</h3>";
            
            $user_id = $_SESSION['user_id'];
            $vattu_stt = $vattu['stt'];
            $so_luong = 5;
            
            echo "Thử INSERT: user_id={$user_id}, vattu_stt={$vattu_stt}, so_luong={$so_luong}<br>";
            
            try {
                // Check xem đã có trong giỏ chưa
                $stmt = $db->prepare("SELECT id, so_luong FROM cart_vattu_thanh_ly WHERE user_id = ? AND vattu_stt = ?");
                $stmt->execute([$user_id, $vattu_stt]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    echo "⚠️ Vật tư này đã có trong giỏ (ID={$existing['id']}, SL={$existing['so_luong']})<br>";
                    echo "→ Sẽ UPDATE số lượng<br>";
                    
                    $new_qty = $existing['so_luong'] + $so_luong;
                    $stmt = $db->prepare("UPDATE cart_vattu_thanh_ly SET so_luong = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_qty, $existing['id']]);
                    
                    echo "✅ UPDATE thành công! Số lượng mới: {$new_qty}<br>";
                    
                } else {
                    echo "→ Vật tư chưa có trong giỏ, sẽ INSERT<br>";
                    
                    $stmt = $db->prepare("INSERT INTO cart_vattu_thanh_ly (user_id, vattu_stt, so_luong, ghi_chu) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $vattu_stt, $so_luong, 'Test từ debug script']);
                    
                    $inserted_id = $db->lastInsertId();
                    echo "✅ INSERT thành công! ID mới: {$inserted_id}<br>";
                }
                
                // Đếm tổng items
                $stmt = $db->prepare("SELECT COUNT(*) FROM cart_vattu_thanh_ly WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $count = $stmt->fetchColumn();
                
                echo "<br>📊 Tổng items trong giỏ: <strong>{$count}</strong><br>";
                
            } catch (PDOException $e) {
                echo "<br>❌ LỖI INSERT: " . $e->getMessage() . "<br>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        }
        
    } else {
        echo "❌ Không tìm thấy vật tư nào trong database<br>";
    }
    
    // Check data trong giỏ
    if ($tableExists) {
        echo "<h3>5. Dữ liệu hiện tại trong giỏ hàng:</h3>";
        $stmt = $db->prepare("
            SELECT 
                c.*,
                v.mavattu,
                v.ten_tiengviet
            FROM cart_vattu_thanh_ly c
            LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($items) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Mã VT</th><th>Tên VT</th><th>SL</th><th>Ghi chú</th><th>Ngày thêm</th></tr>";
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['mavattu']}</td>";
                echo "<td>{$item['ten_tiengviet']}</td>";
                echo "<td>{$item['so_luong']}</td>";
                echo "<td>{$item['ghi_chu']}</td>";
                echo "<td>{$item['ngay_them']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Giỏ hàng trống<br>";
        }
    }
    
    // Test AJAX endpoint
    echo "<h3>6. Test AJAX Endpoint:</h3>";
    echo "<button onclick=\"testAjax()\">Test AJAX thêm giỏ hàng</button>";
    echo "<div id='ajax-result' style='margin-top: 10px; padding: 10px; border: 1px solid #ccc;'></div>";
    
    echo "<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>";
    echo "<script>
    function testAjax() {
        const resultDiv = document.getElementById('ajax-result');
        resultDiv.innerHTML = '⏳ Đang test...';
        
        $.ajax({
            url: 'giohang.php?action=add',
            method: 'POST',
            data: {
                vattu_stt: {$vattu['stt']},
                so_luong: 1
            },
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    resultDiv.innerHTML = '✅ Success: ' + response.message + '<br>Cart count: ' + response.cart_count;
                    resultDiv.style.backgroundColor = '#d4edda';
                } else {
                    resultDiv.innerHTML = '❌ Failed: ' + response.message;
                    resultDiv.style.backgroundColor = '#f8d7da';
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.log('XHR:', xhr);
                resultDiv.innerHTML = '❌ AJAX Error: ' + error + '<br>Status: ' + xhr.status + '<br>Response: ' + xhr.responseText;
                resultDiv.style.backgroundColor = '#f8d7da';
            }
        });
    }
    </script>";
    
    echo "<hr>";
    echo "<h3>📋 Checklist:</h3>";
    echo "<ul>";
    echo "<li>" . ($tableExists ? "✅" : "❌") . " Bảng cart_vattu_thanh_ly tồn tại</li>";
    echo "<li>" . ($vattu ? "✅" : "❌") . " Có vật tư để test</li>";
    echo "<li>❓ Test AJAX endpoint (nhấn nút bên trên)</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>🔧 Hướng dẫn fix:</h3>";
    if (!$tableExists) {
        echo "<ol>";
        echo "<li>Vào phpMyAdmin</li>";
        echo "<li>Chọn database: diavatly_db</li>";
        echo "<li>Tab SQL</li>";
        echo "<li>Chạy file: <strong>setup_giohang_phieudathang.sql</strong></li>";
        echo "</ol>";
    }

} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
