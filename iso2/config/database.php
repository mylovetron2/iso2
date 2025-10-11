<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mapselli676e_iso2');
define('DB_USER', 'mapselli676e_iso2');
define('DB_PASS', 'cntt2019');
define('DB_CHARSET', 'utf8mb4');
function getDBConnection($debug = false) {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER, 
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            if ($debug) {
                echo '<div style="color:green;padding:8px;">Kết nối CSDL thành công!</div>';
            }
        } catch (PDOException $e) {
            if ($debug) {
                echo '<div style="color:red;padding:8px;">Kết nối CSDL thất bại: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            die("Connection failed: " . $e->getMessage());
        }
    }
    return $conn;
}
