<?php
require_once "../controllers/customer_controller.php";

$result = logout_customer_ctr();

if ($result === "success") {
    header("Location: /ecommerce-authent/views/login.php?message=logged_out");
    exit();
} else {
    header("Location: /ecommerce-authent/views/login.php?error=logout_failed");
    exit();
}
?>

