<?php
// admin_logout.php
require_once 'config/database.php';
require_once 'includes/helpers.php';

logout_user();
header('Location: admin_login.php');
exit;
