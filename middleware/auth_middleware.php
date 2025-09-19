<?php
require_once "../controllers/customer_controller.php";

function require_auth() {
    if (!is_logged_in()) {
        header("Location: /ecommerce-authent/views/login.php?error=login_required");
        exit();
    }
}

function require_admin() {
    require_auth();
    if ($_SESSION['user_role'] != 1) {
        header("Location: /ecommerce-authent/views/dashboard.php?error=access_denied");
        exit();
    }
}

function check_remember_me() {
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if (count($parts) === 2) {
            $customer_id = $parts[0];
            $email_hash = $parts[1];
            
            $customer = new customer_class();
            $customer_data = $customer->get_customer_by_id($customer_id);
            
            if ($customer_data && hash('sha256', $customer_data['customer_email']) === $email_hash) {
                session_start();
                $_SESSION['customer_id'] = $customer_data['customer_id'];
                $_SESSION['customer_name'] = $customer_data['customer_name'];
                $_SESSION['customer_email'] = $customer_data['customer_email'];
                $_SESSION['user_role'] = $customer_data['user_role'];
                $_SESSION['logged_in'] = true;
            }
        }
    }
}

// Auto-check remember me on every page load
check_remember_me();
?>

