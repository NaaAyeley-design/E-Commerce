<?php
require_once "customer_class.php";

function register_customer_ctr($name, $email, $password, $country, $city, $contact) {
    $customer = new customer_class();

    // Check if email already exists
    if ($customer->email_exists($email)) {
        return "Email already registered.";
    }

    $inserted = $customer->add_customer($name, $email, $password, $country, $city, $contact, 2);

    return $inserted ? "success" : "Registration failed.";
}
?>
