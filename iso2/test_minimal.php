<?php
/**
 * Minimal test - Check each step
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== MINIMAL TEST ===\n\n";

// Step 1: Database
echo "1. Testing database connection...\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "   ✓ Database connected\n\n";
} catch (Exception $e) {
    die("   ✗ Database error: " . $e->getMessage() . "\n");
}

// Step 2: Find STT
echo "2. Finding test STT...\n";
try {
    $stmt = $db->query("SELECT stt FROM congviec_suachua_iso LIMIT 1");
    $row = $stmt->fetch();
    if (!$row) {
        die("   ✗ No records found\n");
    }
    $testStt = (int)$row['stt'];
    echo "   ✓ Found STT: $testStt\n\n";
} catch (Exception $e) {
    die("   ✗ Query error: " . $e->getMessage() . "\n");
}

// Step 3: Load Model
echo "3. Loading CongViecSuaChua model...\n";
try {
    require_once __DIR__ . '/models/CongViecSuaChua.php';
    echo "   ✓ Model file loaded\n";
    
    $model = new CongViecSuaChua();
    echo "   ✓ Model instantiated\n\n";
} catch (Exception $e) {
    die("   ✗ Model error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

// Step 4: Test find()
echo "4. Testing find($testStt)...\n";
try {
    $result = $model->find($testStt);
    if ($result === false) {
        echo "   ⚠ find() returned FALSE\n\n";
    } else {
        echo "   ✓ find() returned data:\n";
        print_r($result);
        echo "\n";
    }
} catch (Exception $e) {
    die("   ✗ find() error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

// Step 5: Load other models for controller
echo "5. Loading other models...\n";
try {
    require_once __DIR__ . '/models/CapDoBaoCuong.php';
    echo "   ✓ CapDoBaoCuong loaded\n";
    
    require_once __DIR__ . '/models/ThietBiCapDoKPI.php';
    echo "   ✓ ThietBiCapDoKPI loaded\n";
    
    require_once __DIR__ . '/models/Resume.php';
    echo "   ✓ Resume loaded\n\n";
} catch (Exception $e) {
    die("   ✗ Model loading error: " . $e->getMessage() . "\n");
}

// Step 6: Load Controller
echo "6. Loading controller...\n";
try {
    require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';
    echo "   ✓ Controller file loaded\n";
    
    $controller = new CongViecSuaChuaController();
    echo "   ✓ Controller instantiated\n\n";
} catch (Exception $e) {
    die("   ✗ Controller error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

// Step 7: Test get()
echo "7. Testing controller->get($testStt)...\n";
try {
    $result = $controller->get($testStt);
    echo "   ✓ get() executed\n";
    echo "   Result:\n";
    print_r($result);
    echo "\n";
} catch (Exception $e) {
    die("   ✗ get() error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
}

// Step 8: Test JSON encode
echo "8. Testing JSON encode...\n";
try {
    $json = json_encode($result);
    if ($json === false) {
        die("   ✗ JSON encode failed: " . json_last_error_msg() . "\n");
    }
    echo "   ✓ JSON encoded successfully\n";
    echo "   JSON: $json\n\n";
} catch (Exception $e) {
    die("   ✗ JSON error: " . $e->getMessage() . "\n");
}

echo "=== ALL TESTS PASSED ===\n";
echo "\nYou can now test the API endpoint:\n";
echo "http://localhost/iso2/congviec_suachua.php?action=get&stt=$testStt\n";
