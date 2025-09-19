<?php
require_once "../classes/customer_class.php";

function register_customer_ctr($name, $email, $password, $country, $city, $contact) {
    $customer = new customer_class();

    // Check if email already exists
    if ($customer->email_exists($email)) {
        return "Email already registered.";
    }

    $inserted = $customer->add_customer($name, $email, $password, $country, $city, $contact, 2);

    return $inserted ? "success" : "Registration failed.";
}

function login_customer_ctr($email, $password, $remember = false) {
    $customer = new customer_class();
    
    $customer_data = $customer->login_customer($email, $password);
    
    if ($customer_data) {
        // Start session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store customer data in session
        $_SESSION['customer_id'] = $customer_data['customer_id'];
        $_SESSION['customer_name'] = $customer_data['customer_name'];
        $_SESSION['customer_email'] = $customer_data['customer_email'];
        $_SESSION['user_role'] = $customer_data['user_role'];
        $_SESSION['logged_in'] = true;
        
        // Set remember me cookie if requested
        if ($remember) {
            $cookie_value = base64_encode($customer_data['customer_id'] . ':' . hash('sha256', $customer_data['customer_email']));
            setcookie('remember_token', $cookie_value, time() + (30 * 24 * 60 * 60), '/'); // 30 days
        }
        
        return "success";
    } else {
        return "Invalid email or password.";
    }
}

function logout_customer_ctr() {
    session_start();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Clear remember me cookie
    setcookie('remember_token', '', time() - 3600, '/');
    
    // Destroy the session
    session_destroy();
    
    return "success";
}

function is_logged_in() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function get_current_customer() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (is_logged_in()) {
        $customer = new customer_class();
        return $customer->get_customer_by_id($_SESSION['customer_id']);
    }
    return false;
}

function update_customer_ctr($customer_id, $name, $email, $country, $city, $contact) {
    $customer = new customer_class();
    
    // Check if email is being changed and if it already exists
    if ($email !== $_SESSION['customer_email']) {
        if ($customer->email_exists($email)) {
            return "Email already registered.";
        }
    }
    
    $updated = $customer->update_customer($customer_id, $name, $email, $country, $city, $contact);
    
    if ($updated) {
        // Update session data
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;
        return "success";
    } else {
        return "Update failed.";
    }
}

function change_password_ctr($customer_id, $current_password, $new_password) {
    $customer = new customer_class();
    
    // Verify current password
    $customer_data = $customer->get_customer_by_id($customer_id);
    if (!$customer_data) {
        return "Customer not found.";
    }
    
    // Get the hashed password from database
    $sql = "SELECT customer_pass FROM customer WHERE customer_id = ?";
    $stmt = $customer->conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!password_verify($current_password, $data['customer_pass'])) {
        return "Current password is incorrect.";
    }
    
    $changed = $customer->change_password($customer_id, $new_password);
    
    return $changed ? "success" : "Password change failed.";
}

function forgot_password_ctr($email) {
    $customer = new customer_class();
    
    // Check if email exists
    $customer_data = $customer->get_customer_by_email($email);
    if (!$customer_data) {
        return "Email not found in our system.";
    }
    
    // Create reset token
    $token = $customer->create_reset_token($customer_data['customer_id']);
    if (!$token) {
        return "Failed to create reset token. Please try again.";
    }
    
    // In a real application, you would send an email here
    // For demo purposes, we'll just return success
    // The reset link would be: /ecommerce-authent/views/reset_password.php?token=$token
    
    return "success";
}

function reset_password_ctr($token, $new_password) {
    $customer = new customer_class();
    
    // Verify token
    $token_data = $customer->verify_reset_token($token);
    if (!$token_data) {
        return "Invalid or expired reset token.";
    }
    
    // Change password
    $changed = $customer->change_password($token_data['customer_id'], $new_password);
    
    if ($changed) {
        // Delete the used token
        $customer->delete_reset_token($token);
        return "success";
    } else {
        return "Password reset failed. Please try again.";
    }
}
?>
