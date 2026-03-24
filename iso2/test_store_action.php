<?php
/**
 * DEBUG STORE ACTION - Test insert phiếu
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/config/constants.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 DEBUG STORE ACTION</h1>";

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p class='success'>✅ Database connected</p>";
    
    // Check session
    echo "<h2>🔐 CHECK SESSION</h2>";
    echo "<pre>";
    echo "Session ID: " . session_id() . "\n";
    echo "Session data:\n";
    print_r($_SESSION);
    echo "</pre>";
    
    // Mock POST data
    $_POST = [
        'nguoi_giao' => 'Test User',
        'donvi_giao' => 'DVLTH',
        'ngay_giao' => date('Y-m-d'),
        'ghichu' => 'Test phiếu',
        'thietbi_id' => [9, 10], // From sample data
        'tinhtrang' => ['Tốt', 'Tốt'],
        'ghichu_thietbi' => ['', '']
    ];
    
    echo "<h2>📝 MOCK POST DATA</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    // Test INSERT master
    echo "<h2>💾 TEST INSERT MASTER</h2>";
    
    $nguoi_giao = $_POST['nguoi_giao'];
    $donvi_giao = $_POST['donvi_giao'];
    $ngay_giao = $_POST['ngay_giao'];
    $ghichu = $_POST['ghichu'];
    $tong_thietbi = count($_POST['thietbi_id']);
    
    // Try different session keys
    $created_by = null;
    if (isset($_SESSION['userid'])) {
        $created_by = $_SESSION['userid'];
        echo "<p class='success'>✅ Found \$_SESSION['userid'] = {$created_by}</p>";
    } elseif (isset($_SESSION['user_id'])) {
        $created_by = $_SESSION['user_id'];
        echo "<p class='success'>✅ Found \$_SESSION['user_id'] = {$created_by}</p>";
    } elseif (isset($_SESSION['id'])) {
        $created_by = $_SESSION['id'];
        echo "<p class='success'>✅ Found \$_SESSION['id'] = {$created_by}</p>";
    } else {
        echo "<p class='error'>⚠️ No user ID in session, using NULL</p>";
    }
    
    $sql = "INSERT INTO giao_nhan_thietbi_iso (
                nguoi_giao, donvi_giao, ngay_giao,
                ghichu,
                trangthai, tong_thietbi,
                created_by, created_at, updated_at
            ) VALUES (
                :nguoi_giao, :donvi_giao, :ngay_giao,
                :ghichu,
                'da_nhan', :tong_thietbi,
                :created_by, NOW(), NOW()
            )";
    
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $db->beginTransaction();
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':nguoi_giao' => $nguoi_giao,
        ':donvi_giao' => $donvi_giao,
        ':ngay_giao' => $ngay_giao,
        ':ghichu' => $ghichu,
        ':tong_thietbi' => $tong_thietbi,
        ':created_by' => $created_by
    ]);
    
    $phieu_id = (int)$db->lastInsertId();
    
    echo "<p class='success'>✅ Master inserted! Phieu ID: {$phieu_id}</p>";
    
    // Test INSERT chitiet
    echo "<h2>📦 TEST INSERT CHITIET</h2>";
    
    foreach ($_POST['thietbi_id'] as $index => $thietbi_id) {
        echo "<h3>Thiết bị #{$index} (ID: {$thietbi_id})</h3>";
        
        // Get thietbi info
        $stmtTB = $db->prepare("SELECT tenvt as ten_thiet_bi, somay as ky_ma_hieu FROM thietbi_iso WHERE stt = ?");
        $stmtTB->execute([$thietbi_id]);
        $thietbi = $stmtTB->fetch(PDO::FETCH_ASSOC);
        
        if (!$thietbi) {
            echo "<p class='error'>❌ Thiết bị không tồn tại!</p>";
            continue;
        }
        
        echo "<p>Tên: {$thietbi['ten_thiet_bi']}</p>";
        echo "<p>Ký mã hiệu: {$thietbi['ky_ma_hieu']}</p>";
        
        $sqlCT = "INSERT INTO giao_nhan_thietbi_chitiet (
                    phieu_id, thietbi_id, ten_thietbi, ky_ma_hieu,
                    soluong, tinhtrang, ghichu,
                    created_at, updated_at
                ) VALUES (
                    :phieu_id, :thietbi_id, :ten_thietbi, :ky_ma_hieu,
                    1, :tinhtrang, :ghichu,
                    NOW(), NOW()
                )";
        
        $stmtCT = $db->prepare($sqlCT);
        $stmtCT->execute([
            ':phieu_id' => $phieu_id,
            ':thietbi_id' => $thietbi_id,
            ':ten_thietbi' => $thietbi['ten_thiet_bi'],
            ':ky_ma_hieu' => $thietbi['ky_ma_hieu'],
            ':tinhtrang' => $_POST['tinhtrang'][$index] ?? '',
            ':ghichu' => $_POST['ghichu_thietbi'][$index] ?? ''
        ]);
        
        echo "<p class='success'>✅ Chitiet inserted!</p>";
    }
    
    $db->commit();
    
    echo "<h2>✅ SUCCESS!</h2>";
    echo "<p class='success'>Transaction committed successfully!</p>";
    echo "<p><a href='giaonhanthietbi.php?action=view&id={$phieu_id}'>→ View phiếu #{$phieu_id}</a></p>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
        echo "<p class='error'>⚠️ Transaction rolled back</p>";
    }
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
