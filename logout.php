<?php
require_once __DIR__ . '/config/bootstrap.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_regenerate_id(true);
header('Location: login.php');
exit;
