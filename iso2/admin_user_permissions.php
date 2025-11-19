<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// Route truy cập phân quyền user từ gốc domain
include __DIR__ . '/views/admin/user_permissions.php';
