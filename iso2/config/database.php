<?php
declare(strict_types=1);

define('DB_HOST', 'diavatly.com');
define('DB_USER', 'diavatly_master');
define('DB_PASS', '12345678');
define('DB_NAME', 'diavatly_db');
define('DB_PORT', '3306');
define('DB_CHARSET', 'latin1');

// define('DB_HOST', 'localhost');
// define('DB_USER', 'mapselli676e_iso2');
// define('DB_PASS', 'cntt2019@cntt2025');
// define('DB_NAME', 'mapselli676e_iso2');
// define('DB_PORT', '3306');
// define('DB_CHARSET', 'latin1');


function getDBConnection(bool $debug = false): PDO {
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
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES latin1"
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
