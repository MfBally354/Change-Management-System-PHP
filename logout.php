<?php
require_once 'auth/auth_check.php';

if (isLoggedIn()) {
    // Log audit
    require_once 'config/database.php';
    logAudit($_SESSION['user_id'], 'logout');
}

// Destroy session
session_destroy();

// Redirect ke login
header('Location: login.php');
exit;
