
<?php
require_once __DIR__ . '/config/constants.php';
if (isLoggedIn()) {
    header('Location: projects.php');
    exit;
} else {
    header('Location: views/auth/login.php');
    exit;
}
