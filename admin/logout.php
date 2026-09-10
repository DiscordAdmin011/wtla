<?php
require_once dirname(__DIR__) . '/includes/functions.php';
logout();
header('Location: ' . url('admin/login.php'));
exit;
