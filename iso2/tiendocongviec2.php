<?php
// Set UTF-8 encoding
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

// DEBUG: Hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Tiến Độ Công Việc (Refactored)
 * File: tiendocongviec2.php
 * Mục tiêu: Chuyển toàn bộ xử lý logic sang controller, file này chỉ nhận biến và render view.
 */

require_once 'controllers/TiendocongviecProfessionalController.php';

// Khởi tạo controller
$controller = new TiendocongviecProfessionalController();

// Lấy dữ liệu từ controller (controller tự lấy từ $_GET)
$data = $controller->index();

// Truyền biến ra view
extract($data);

// Render view (chỉ nhận biến, không xử lý logic)
require 'views/tiendocongviec_professional/index.php';
