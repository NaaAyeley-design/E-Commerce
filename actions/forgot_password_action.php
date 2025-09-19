<?php
require_once "../controllers/customer_controller.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    
    $result = forgot_password_ctr($email);
    
    echo $result; // will be handled by JS
}
?>

