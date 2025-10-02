<?php
/**
 * Legacy Login Action (Deprecated)
 * 
 * This file is kept for backward compatibility.
 * New login requests should use process_login.php
 */

// Include core settings and user controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../controller/user_controller.php';

// Redirect to new login action
header('Location: ' . BASE_URL . '/actions/process_login.php');
exit;
?>

