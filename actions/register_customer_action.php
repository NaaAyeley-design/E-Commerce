<?php
require_once "../controllers/customer_controller.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $contact = $_POST['contact'];

    $result = register_customer_ctr($name, $email, $password, $country, $city, $contact);

    echo $result; // will be handled by JS

}
?>
