<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Mock session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 5;
    $_SESSION['user_name'] = 'test_user';
}

echo "<h2>🔍 Debug Store Logic - Simple Test</h2><hr>";
echo "✅ Session: user_id = " . $_SESSION['user_id'] . "<br><br>";

// Include only database
require_once 'config/database.php';
require_once 'includes/ActivityLogger.php';

echo "<h3>Test 1: Database Connection</h3>";
try {
    $db = getDBConnection(true);
    echo "✅ Database connected<br><br>";
} catch (Exception $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

echo "<h3>Test 2: Get Cart Items</h3>";
try {
    $stmt = $db->prepare("SELECT 
        c.id,
        c.vattu_stt,
        c.so_luong,
        c.ghi_chu,
        v.mavattu,
        v.ten_tiengviet,
        v.dongia,
        v.dvt_tiengviet
    FROM cart_vattu_thanh_ly c
    LEFT JOIN vattu_thanh_ly_iso v ON c.vattu_stt = v.stt
    WHERE c.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        die("❌ Giỏ hàng trống! <a href='test_add_giohang.php'>Thêm vào giỏ</a>");
    }

    echo "✅ Cart có " . count($cartItems) . " items:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>STT</th><th>Mã VT</th><th>Tên</th><th>SL</th><th>Đơn giá</th></tr>";
    $tongTien = 0;
    foreach ($cartItems as $item) {
        $thanhTien = $item['so_luong'] * $item['dongia'];
        $tongTien += $thanhTien;
        echo "<tr>";
        echo "<td>{$item['id']}</td>";
        echo "<td>{$item['vattu_stt']}</td>";
        echo "<td>{$item['mavattu']}</td>";
        echo "<td>{$item['ten_tiengviet']}</td>";
        echo "<td>{$item['so_luong']}</td>";
        echo "<td>" . number_format($item['dongia']) . "</td>";
        echo "</tr>";
    }
    echo "<tr><td colspan='5' align='right'><b>Tổng tiền:</b></td><td><b>" . number_format($tongTien) . "</b></td></tr>";
    echo "</table><br>";

} catch (Exception $e) {
    die("❌ Error getting cart: " . $e->getMessage());
}

echo "<h3>Test 3: Mock POST Data</h3>";
$postData = [
    'nha_cung_cap' => 'NCC Test Simple',
    'so_hd_ncc' => 'HD-DEBUG-' . date('Ymd-His'),
    'ngay_du_kien_nhan' => date('Y-m-d', strtotime('+30 days')),
    'ghi_chu' => 'Test từ debug simple script'
];
echo "✅ POST data:<br><pre>" . print_r($postData, true) . "</pre>";

echo "<h3>Test 4: Validate Input</h3>";
$errors = [];
if (empty($postData['nha_cung_cap'])) {
    $errors[] = 'Nhà cung cấp không được để trống';
}
if (empty($postData['so_hd_ncc'])) {
    $errors[] = 'Số hợp đồng không được để trống';
}
if (empty($postData['ngay_du_kien_nhan'])) {
    $errors[] = 'Ngày dự kiến nhận không được để trống';
}

if (!empty($errors)) {
    echo "<div style='color: red;'>";
    echo "❌ Validation errors:<ul>";
    foreach ($errors as $err) {
        echo "<li>$err</li>";
    }
    echo "</ul></div>";
    die();
} else {
    echo "✅ Validation passed<br><br>";
}

echo "<h3>Test 5: Generate Ma Phieu</h3>";
try {
    $stmt = $db->prepare("SELECT ma_phieu FROM phieu_dat_hang WHERE ma_phieu LIKE ? ORDER BY id DESC LIMIT 1");
    $prefix = 'PDH-' . date('Ym') . '%';
    $stmt->execute([$prefix]);
    $lastPhieu = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastPhieu) {
        // Extract number: PDH-202603-0001 -> 0001
        preg_match('/-(\d+)$/', $lastPhieu['ma_phieu'], $matches);
        $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    $maPhieu = 'PDH-' . date('Ym') . '-' . str_pad((string)$newNumber, 4, '0', STR_PAD_LEFT);
    echo "✅ Ma phieu: <b>$maPhieu</b><br><br>";
} catch (Exception $e) {
    die("❌ Error generating ma_phieu: " . $e->getMessage());
}

echo "<h3>Test 6: BEGIN TRANSACTION</h3>";
try {
    $db->beginTransaction();
    echo "✅ Transaction started<br><br>";
} catch (Exception $e) {
    die("❌ Failed to begin transaction: " . $e->getMessage());
}

echo "<h3>Test 7: INSERT phieu_dat_hang</h3>";
try {
    $sql = "INSERT INTO phieu_dat_hang 
            (ma_phieu, nha_cung_cap, so_hd_ncc, ngay_du_kien_nhan, 
             trang_thai, nguoi_lap, ngay_lap, ghi_chu)
            VALUES 
            (:ma_phieu, :nha_cung_cap, :so_hd_ncc, :ngay_du_kien_nhan,
             'draft', :nguoi_lap, NOW(), :ghi_chu)";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':ma_phieu' => $maPhieu,
        ':nha_cung_cap' => $postData['nha_cung_cap'],
        ':so_hd_ncc' => $postData['so_hd_ncc'],
        ':ngay_du_kien_nhan' => $postData['ngay_du_kien_nhan'],
        ':nguoi_lap' => $_SESSION['user_id'],
        ':ghi_chu' => $postData['ghi_chu'] ?? ''
    ]);

    $phieuId = (int)$db->lastInsertId();
    echo "✅ INSERT phieu successful, ID = <b>$phieuId</b><br><br>";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Failed to insert phieu: " . $e->getMessage() . "<br>SQL: " . ($sql ?? 'N/A'));
}

echo "<h3>Test 8: INSERT phieu_dat_hang_chi_tiet</h3>";
try {
    $sql = "INSERT INTO phieu_dat_hang_chi_tiet 
            (phieu_id, vattu_stt, ten_tieng_viet, don_gia, so_luong_dat, don_vi, ghi_chu, thanh_tien)
            VALUES 
            (:phieu_id, :vattu_stt, :ten_tieng_viet, :don_gia, :so_luong_dat, :don_vi, :ghi_chu, :thanh_tien)";
    
    $stmt = $db->prepare($sql);
    
    $insertedCount = 0;
    foreach ($cartItems as $item) {
        $thanhTien = $item['so_luong'] * $item['dongia'];
        
        $result = $stmt->execute([
            ':phieu_id' => $phieuId,
            ':vattu_stt' => $item['vattu_stt'],
            ':ten_tieng_viet' => $item['ten_tiengviet'],
            ':don_gia' => $item['dongia'],
            ':so_luong_dat' => $item['so_luong'],
            ':don_vi' => $item['dvt_tiengviet'],
            ':ghi_chu' => $item['ghi_chu'] ?? '',
            ':thanh_tien' => $thanhTien
        ]);
        
        $insertedCount++;
    }
    
    echo "✅ INSERT chi tiet successful: <b>$insertedCount</b> items<br><br>";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Failed to insert chi tiet: " . $e->getMessage());
}

echo "<h3>Test 9: DELETE cart_vattu_thanh_ly</h3>";
try {
    $stmt = $db->prepare("DELETE FROM cart_vattu_thanh_ly WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $deletedCount = $stmt->rowCount();
    
    echo "✅ Cart cleared: <b>$deletedCount</b> items deleted<br><br>";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Failed to clear cart: " . $e->getMessage());
}

echo "<h3>Test 10: COMMIT Transaction</h3>";
try {
    $db->commit();
    echo "✅ Transaction committed successfully!<br><br>";
} catch (Exception $e) {
    $db->rollBack();
    die("❌ Failed to commit: " . $e->getMessage());
}

echo "<h3>✅ SUCCESS - Verify Database</h3>";
try {
    // Verify phieu
    $stmt = $db->prepare("SELECT * FROM phieu_dat_hang WHERE id = ?");
    $stmt->execute([$phieuId]);
    $phieu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h4>Phiếu đặt hàng:</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><td>{$phieu['id']}</td></tr>";
    echo "<tr><th>Mã phiếu</th><td><b>{$phieu['ma_phieu']}</b></td></tr>";
    echo "<tr><th>NCC</th><td>{$phieu['nha_cung_cap']}</td></tr>";
    echo "<tr><th>Số HĐ</th><td>{$phieu['so_hd_ncc']}</td></tr>";
    echo "<tr><th>Trạng thái</th><td><b>{$phieu['trang_thai']}</b></td></tr>";
    echo "<tr><th>Ngày lập</th><td>{$phieu['ngay_lap']}</td></tr>";
    echo "</table><br>";
    
    // Verify chi tiet
    $stmt = $db->prepare("SELECT 
        ct.*,
        v.mavattu
    FROM phieu_dat_hang_chi_tiet ct
    LEFT JOIN vattu_thanh_ly_iso v ON ct.vattu_stt = v.stt
    WHERE ct.phieu_id = ?");
    $stmt->execute([$phieuId]);
    $chiTiet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Chi tiết (" . count($chiTiet) . " items):</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>STT VT</th><th>Mã VT</th><th>Tên</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr>";
    foreach ($chiTiet as $ct) {
        echo "<tr>";
        echo "<td>{$ct['vattu_stt']}</td>";
        echo "<td>{$ct['mavattu']}</td>";
        echo "<td>{$ct['ten_tieng_viet']}</td>";
        echo "<td>{$ct['so_luong_dat']}</td>";
        echo "<td>" . number_format($ct['don_gia']) . "</td>";
        echo "<td>" . number_format($ct['thanh_tien']) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Verify cart cleared
    $stmt = $db->prepare("SELECT COUNT(*) FROM cart_vattu_thanh_ly WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = (int)$stmt->fetchColumn();
    
    if ($cartCount === 0) {
        echo "<p style='color: green; font-size: 18px;'>✅ <b>Cart đã được clear hoàn toàn!</b></p>";
    } else {
        echo "<p style='color: red;'>⚠️ Cart vẫn còn $cartCount items!</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='phieudathang.php?action=view&id=$phieuId'>📄 Xem phiếu vừa tạo</a></p>";
    echo "<p><a href='phieudathang.php?action=create&step=1'>🔙 Tạo phiếu mới</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error verifying: " . $e->getMessage() . "</p>";
}
