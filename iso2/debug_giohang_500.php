<?php
/**
 * DEBUG GIOHANG.PHP - Tìm lỗi 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug giohang.php - Lỗi 500</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #d33; border-bottom: 3px solid #d33; padding-bottom: 10px; }
        .test { margin: 15px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 400px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .check { color: #28a745; font-weight: bold; }
        .cross { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 DEBUG GIOHANG.PHP - Lỗi 500</h1>
        <p><strong>Thời gian:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        $errors = [];
        $steps = [];
        
        // ============================================
        // 1. Check file exists
        // ============================================
        echo '<h2>1. Kiểm tra file tồn tại</h2>';
        
        $gioHangPath = __DIR__ . '/giohang.php';
        if (file_exists($gioHangPath)) {
            echo '<div class="test success"><span class="check">✅</span> File giohang.php tồn tại</div>';
            echo '<div class="test info">Path: <code>' . $gioHangPath . '</code></div>';
            $steps[] = 'File exists';
        } else {
            echo '<div class="test error"><span class="cross">❌</span> File giohang.php KHÔNG tồn tại</div>';
            $errors[] = 'giohang.php not found';
        }
        
        // ============================================
        // 2. Check controller exists
        // ============================================
        echo '<h2>2. Kiểm tra GioHangController</h2>';
        
        $controllerPath = __DIR__ . '/controllers/GioHangController.php';
        if (file_exists($controllerPath)) {
            echo '<div class="test success"><span class="check">✅</span> GioHangController.php tồn tại</div>';
            $steps[] = 'Controller file exists';
            
            // Check if file is readable
            if (is_readable($controllerPath)) {
                echo '<div class="test success"><span class="check">✅</span> File readable</div>';
                $steps[] = 'Controller readable';
            } else {
                echo '<div class="test error"><span class="cross">❌</span> File không đọc được (permission issue)</div>';
                $errors[] = 'Controller not readable';
            }
        } else {
            echo '<div class="test error"><span class="cross">❌</span> GioHangController.php KHÔNG tồn tại</div>';
            $errors[] = 'GioHangController.php not found';
        }
        
        // ============================================
        // 3. Check database connection
        // ============================================
        echo '<h2>3. Kiểm tra Database</h2>';
        
        try {
            require_once __DIR__ . '/config/database.php';
            
            if (isset($conn) && $conn instanceof mysqli) {
                echo '<div class="test success"><span class="check">✅</span> Database kết nối OK</div>';
                $steps[] = 'Database connected';
                
                // Check if cart table exists
                $result = $conn->query("SHOW TABLES LIKE 'cart_vattu_thanh_ly'");
                if ($result && $result->num_rows > 0) {
                    echo '<div class="test success"><span class="check">✅</span> Bảng cart_vattu_thanh_ly tồn tại</div>';
                    $steps[] = 'Cart table exists';
                } else {
                    echo '<div class="test error"><span class="cross">❌</span> Bảng cart_vattu_thanh_ly CHƯA TẠO</div>';
                    echo '<div class="test warning">⚠️ Bạn cần chạy SQL: <code>setup_giohang_phieudathang.sql</code></div>';
                    $errors[] = 'Cart table not created yet - run SQL first';
                }
                
                // Check vattu_thanh_ly_iso table
                $result = $conn->query("SHOW TABLES LIKE 'vattu_thanh_ly_iso'");
                if ($result && $result->num_rows > 0) {
                    echo '<div class="test success"><span class="check">✅</span> Bảng vattu_thanh_ly_iso tồn tại</div>';
                    $steps[] = 'Vattu table exists';
                } else {
                    echo '<div class="test error"><span class="cross">❌</span> Bảng vattu_thanh_ly_iso KHÔNG tồn tại</div>';
                    $errors[] = 'vattu_thanh_ly_iso table not found';
                }
            } else {
                echo '<div class="test error"><span class="cross">❌</span> Không kết nối được database</div>';
                $errors[] = 'Database connection failed';
            }
        } catch (Exception $e) {
            echo '<div class="test error"><span class="cross">❌</span> Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
            $errors[] = 'Database exception: ' . $e->getMessage();
        }
        
        // ============================================
        // 4. Try to load giohang.php and catch error
        // ============================================
        echo '<h2>4. Thử load giohang.php (với error catching)</h2>';
        
        if (file_exists($gioHangPath)) {
            echo '<div class="test info">Đang thử load giohang.php...</div>';
            
            // Capture output and errors
            ob_start();
            $loadError = null;
            
            try {
                // Read the file content first
                $content = file_get_contents($gioHangPath);
                
                // Check for obvious issues
                if (strpos($content, 'require_once') !== false || strpos($content, 'include') !== false) {
                    echo '<div class="test info">File có sử dụng require/include</div>';
                }
                
                // Try to actually include it
                set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$loadError) {
                    $loadError = "[$errno] $errstr in $errfile:$errline";
                });
                
                // Don't actually include it, just check what it would do
                echo '<div class="test warning">⚠️ Không thể test include trực tiếp (sẽ chạy code)</div>';
                echo '<div class="test info">Xem nội dung file:</div>';
                
                $lines = explode("\n", $content);
                echo '<pre>';
                foreach (array_slice($lines, 0, 30) as $i => $line) {
                    echo sprintf('%3d: %s' . "\n", $i + 1, htmlspecialchars($line));
                }
                if (count($lines) > 30) {
                    echo '... (' . (count($lines) - 30) . ' dòng còn lại)' . "\n";
                }
                echo '</pre>';
                
                restore_error_handler();
                
            } catch (Exception $e) {
                $loadError = $e->getMessage();
            } catch (Error $e) {
                $loadError = $e->getMessage();
            }
            
            $output = ob_get_clean();
            
            if ($loadError) {
                echo '<div class="test error"><span class="cross">❌</span> Lỗi khi load: ' . htmlspecialchars($loadError) . '</div>';
                $errors[] = 'Load error: ' . $loadError;
            }
            
            if ($output) {
                echo $output;
            }
        }
        
        // ============================================
        // 5. Check PHP error log
        // ============================================
        echo '<h2>5. Kiểm tra PHP Error Log</h2>';
        
        $logFiles = [
            __DIR__ . '/error.log',
            __DIR__ . '/php_error.log',
            __DIR__ . '/logs/error.log',
            __DIR__ . '/logs/php_error.log',
            ini_get('error_log')
        ];
        
        $foundLog = false;
        foreach ($logFiles as $logFile) {
            if ($logFile && file_exists($logFile) && is_readable($logFile)) {
                $foundLog = true;
                echo '<div class="test success"><span class="check">✅</span> Tìm thấy log: <code>' . basename($logFile) . '</code></div>';
                
                // Read last 30 lines
                $lines = @file($logFile);
                if ($lines) {
                    $recent = array_slice($lines, -30);
                    echo '<div class="test info">';
                    echo '<strong>30 dòng cuối cùng:</strong><br>';
                    echo '<pre style="max-height: 300px; overflow-y: auto;">';
                    foreach ($recent as $line) {
                        // Highlight errors related to giohang
                        if (stripos($line, 'giohang') !== false || stripos($line, 'cart') !== false) {
                            echo '<strong style="background: yellow;">' . htmlspecialchars($line) . '</strong>';
                        } else {
                            echo htmlspecialchars($line);
                        }
                    }
                    echo '</pre>';
                    echo '</div>';
                }
                break;
            }
        }
        
        if (!$foundLog) {
            echo '<div class="test warning"><span class="warning">⚠️</span> Không tìm thấy error log</div>';
            echo '<div class="test info">Kiểm tra:<br>';
            echo '1. File .htaccess có log settings không?<br>';
            echo '2. php.ini có enable error_log không?<br>';
            echo '3. Quyền ghi file log?</div>';
        }
        
        // ============================================
        // 6. Check config/constants.php
        // ============================================
        echo '<h2>6. Kiểm tra config/constants.php</h2>';
        
        $constantsPath = __DIR__ . '/config/constants.php';
        if (file_exists($constantsPath)) {
            echo '<div class="test success"><span class="check">✅</span> constants.php tồn tại</div>';
            
            // Try to load it
            try {
                ob_start();
                require_once $constantsPath;
                ob_end_clean();
                echo '<div class="test success"><span class="check">✅</span> Load constants.php OK</div>';
                $steps[] = 'Constants loaded';
            } catch (Exception $e) {
                echo '<div class="test error"><span class="cross">❌</span> Lỗi load constants: ' . htmlspecialchars($e->getMessage()) . '</div>';
                $errors[] = 'Constants load error: ' . $e->getMessage();
            }
        } else {
            echo '<div class="test error"><span class="cross">❌</span> constants.php KHÔNG tồn tại</div>';
            $errors[] = 'constants.php not found';
        }
        
        // ============================================
        // 7. Test accessing giohang.php via curl/file_get_contents
        // ============================================
        echo '<h2>7. Test HTTP Request đến giohang.php</h2>';
        
        $url = 'https://diavatly.cloud/iso2/giohang.php';
        echo '<div class="test info">URL: <code>' . $url . '</code></div>';
        
        // Try with file_get_contents first
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'timeout' => 5
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        echo '<div class="test info">Đang gửi request...</div>';
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            echo '<div class="test error"><span class="cross">❌</span> Request thất bại</div>';
            if ($error) {
                echo '<pre>' . htmlspecialchars($error['message']) . '</pre>';
            }
            $errors[] = 'HTTP request failed';
        } else {
            echo '<div class="test warning"><span class="warning">⚠️</span> Nhận được response</div>';
            
            // Check HTTP response code
            if (isset($http_response_header)) {
                echo '<div class="test info"><strong>HTTP Headers:</strong><br><pre>';
                foreach ($http_response_header as $header) {
                    echo htmlspecialchars($header) . "\n";
                    if (strpos($header, '500') !== false) {
                        echo '<strong style="color: red;">^ LỖI 500 INTERNAL SERVER ERROR</strong>' . "\n";
                    }
                }
                echo '</pre></div>';
            }
            
            // Show response (truncated)
            echo '<div class="test info"><strong>Response body (200 chars đầu):</strong><br>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 200)) . '...</pre>';
            echo '</div>';
        }
        
        // ============================================
        // SUMMARY
        // ============================================
        echo '<h2>📊 TÓM TẮT</h2>';
        
        if (empty($errors)) {
            echo '<div class="test success">';
            echo '<h3>✅ Các bước đã OK:</h3>';
            echo '<ul>';
            foreach ($steps as $step) {
                echo '<li>' . htmlspecialchars($step) . '</li>';
            }
            echo '</ul>';
            echo '<p><strong>Nhưng vẫn bị lỗi 500 → Cần xem PHP error log chi tiết</strong></p>';
            echo '</div>';
        } else {
            echo '<div class="test error">';
            echo '<h3>❌ CÓ ' . count($errors) . ' VẤN ĐỀ:</h3>';
            echo '<ol>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ol>';
            echo '</div>';
        }
        
        // ============================================
        // GIẢI PHÁP
        // ============================================
        echo '<h2>💡 GIẢI PHÁP</h2>';
        
        // Check if main issue is missing cart table
        $missingTable = false;
        foreach ($errors as $error) {
            if (strpos($error, 'Cart table not created') !== false) {
                $missingTable = true;
                break;
            }
        }
        
        if ($missingTable) {
            echo '<div class="test warning">';
            echo '<h3>⚠️ CHƯA CHẠY SQL</h3>';
            echo '<p>Bạn cần chạy SQL để tạo bảng trước:</p>';
            echo '<ol>';
            echo '<li>Mở phpMyAdmin</li>';
            echo '<li>Chọn database: <code>diavatly_db</code></li>';
            echo '<li>Vào tab SQL</li>';
            echo '<li>Copy nội dung file: <code>setup_giohang_phieudathang.sql</code></li>';
            echo '<li>Paste và click "Go"</li>';
            echo '</ol>';
            echo '<p><a href="setup_giohang_phieudathang.sql" download style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">📥 Download SQL File</a></p>';
            echo '</div>';
        } else {
            echo '<div class="test info">';
            echo '<h3>🔍 Các bước debug tiếp:</h3>';
            echo '<ol>';
            echo '<li><strong>Kiểm tra PHP error log</strong> (xem phần 5 phía trên)</li>';
            echo '<li><strong>Enable error display</strong> tạm thời trong giohang.php (thêm vào đầu file):';
            echo '<pre>error_reporting(E_ALL);\nini_set(\'display_errors\', 1);</pre></li>';
            echo '<li><strong>Kiểm tra .htaccess</strong> có block gì không</li>';
            echo '<li><strong>Kiểm tra permissions</strong>: giohang.php phải readable</li>';
            echo '<li><strong>Test trực tiếp</strong>: Truy cập giohang.php?action=index</li>';
            echo '</ol>';
            echo '</div>';
        }
        
        ?>
        
        <hr>
        <p style="text-align: center; color: #666;">
            <small>Debug script: <code>debug_giohang_500.php</code> | <?php echo date('Y-m-d H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
