<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test PHP</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 24px; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 15px 0; }
        ul { line-height: 2; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ PHP ĐANG HOẠT ĐỘNG</h1>
        
        <div class="info">
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        </div>
        
        <h3>🔍 Các bước kiểm tra tiếp:</h3>
        <ol>
            <li><a href="/iso2/debug_login_issue.php">▶ Chạy Debug Script</a></li>
            <li><a href="/iso2/fix_login_issue.html">▶ Xem hướng dẫn Fix</a></li>
            <li><a href="/iso2/views/auth/login.php">▶ Thử Login trực tiếp</a></li>
            <li><a href="/iso2/index.php">▶ Vào trang chủ</a></li>
        </ol>
        
        <hr>
        
        <h3>📋 File đã tạo để fix:</h3>
        <ul>
            <li><code>debug_login_issue.php</code> - Kiểm tra lỗi chi tiết</li>
            <li><code>fix_login_issue.html</code> - Hướng dẫn đầy đủ</li>
            <li><code>fix_header_temp.bat</code> - Comment tạm giỏ hàng</li>
            <li><code>restore_header.bat</code> - Khôi phục header</li>
            <li><code>FIX_LOGIN_ERROR.md</code> - Tài liệu fix</li>
        </ul>
        
        <hr>
        <p><small>File test này: <code>test_php.php</code></small></p>
    </div>
</body>
</html>
