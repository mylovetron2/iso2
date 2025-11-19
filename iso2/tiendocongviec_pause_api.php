<?php
// Xử lý AJAX tạm dừng cho tiến độ công việc
require_once __DIR__ . '/controllers/TiendocongviecProfessionalController.php';
$controller = new TiendocongviecProfessionalController();
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action === 'list') {
    $work_id = isset($_GET['work_id']) ? (int)$_GET['work_id'] : 0;
    $controller->ajaxListPause($work_id);
} elseif ($action === 'add') {
    $controller->ajaxAddPause($_POST);
} elseif ($action === 'update') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $data = $_POST;
    unset($data['id']);
    $controller->ajaxUpdatePause($id, $data);
} elseif ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $controller->ajaxDeletePause($id);
} else {
    echo json_encode(['success'=>false, 'msg'=>'Invalid action']);
}
