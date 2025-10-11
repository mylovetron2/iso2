<?php
require_once '../config/constants.php';
require_once '../includes/auth.php';

logout();
header('Location: views/auth/login.php');
exit;
