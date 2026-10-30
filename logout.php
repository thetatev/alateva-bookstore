<?php
// logout.php
require_once 'config/database.php';
require_once 'includes/helpers.php';

logout_user();
header('Location: index.php');
exit;
