<?php
declare(strict_types=1);

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "1. Starting debug...<br>";

try {
    echo "2. Including constants...<br>";
    require_once __DIR__ . '/config/constants.php';
    
    echo "3. Constants loaded. Session status: " . session_status() . "<br>";
    echo "4. Logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "<br>";
    
    if (isLoggedIn()) {
        echo "5. User ID: " . $_SESSION['user_id'] . "<br>";
    }
    
    echo "6. Including UserProfileController...<br>";
    require_once __DIR__ . '/controllers/UserProfileController.php';
    
    echo "7. Creating controller instance...<br>";
    $controller = new UserProfileController();
    
    echo "8. Controller created successfully!<br>";
    
    $action = $_GET['action'] ?? 'view';
    echo "9. Action: $action<br>";
    
    echo "10. About to call controller method...<br>";
    
    switch ($action) {
        case 'view':
            echo "11. Calling view()...<br>";
            $controller->view();
            break;
            
        case 'edit':
            echo "11. Calling edit()...<br>";
            $controller->edit();
            break;
            
        case 'update':
            echo "11. Calling update()...<br>";
            $controller->update();
            break;
            
        case 'change_password':
            echo "11. Calling changePassword()...<br>";
            $controller->changePassword();
            break;
            
        default:
            echo "11. Default: Calling view()...<br>";
            $controller->view();
            break;
    }
    
    echo "12. Method completed successfully!<br>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR!</h2>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<h2 style='color: red;'>ERROR!</h2>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
