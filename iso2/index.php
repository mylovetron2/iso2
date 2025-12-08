<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config/constants.php';
if (isLoggedIn()) {
    header('Location: hososcbd.php');
    exit;
} else {
    header('Location: views/auth/login.php');
    exit;
}
