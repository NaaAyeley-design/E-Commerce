<?php
/**
 * Redirect to New Entry Point
 * 
 * This file redirects users from the old entry point to the new MVC structure.
 * After restructuring, the main entry point is now in public/index.php
 */

// Redirect to the main homepage
header('Location: public/index.php');
exit;
?>
