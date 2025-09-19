<?php
require_once "../controllers/customer_controller.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $result = login_customer_ctr($email, $password, $remember);

    echo $result; // will be handled by JS
}
?>

