<?php
/**
 * User Controller
 * 
 * Handles all user-related operations including authentication,
 * registration, profile management, and user sessions.
 */

// Include core settings and general controller
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/general_controller.php';

/**
 * Register new customer
 */
function register_user_ctr($name, $email, $password, $country, $city, $contact) {
    try {
        // Rate limiting
        check_action_rate_limit('register', 3, 300); // 3 attempts per 5 minutes
        
        // Validate input
        $validation_rules = [
            'name' => ['required' => true, 'name' => true, 'max_length' => 100],
            'email' => ['required' => true, 'email' => true],
            'password' => ['required' => true, 'min_length' => 6],
            'country' => ['required' => true, 'max_length' => 50],
            'city' => ['required' => true, 'max_length' => 50],
            'contact' => ['required' => true, 'phone' => true]
        ];
        
        $data = compact('name', 'email', 'password', 'country', 'city', 'contact');
        $errors = validate_form($data, $validation_rules);
        
        if (!empty($errors)) {
            return implode(' ', array_values($errors));
        }
        
        // Additional password validation
        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            return $password_validation;
        }
        
        $user = new user_class();
        
        // Check if email already exists
        if ($user->email_exists($email)) {
            return "Email already registered.";
        }
        
        // Create user account
        $inserted = $user->add_customer($name, $email, $password, $country, $city, $contact, 2);
        
        if ($inserted) {
            // Log successful registration
            log_activity('user_registered', "New user registered: $email");
            
            // Send welcome email (placeholder)
            send_email_notification($email, 'Welcome to ' . APP_NAME, 'Thank you for registering!');
            
            return "success";
        } else {
            return "Registration failed. Please try again.";
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return "An error occurred during registration. Please try again.";
    }
}

/**
 * Login customer
 */
function login_user_ctr($email, $password, $remember = false) {
    try {
        // Rate limiting
        check_action_rate_limit('login', 5, 300); // 5 attempts per 5 minutes
        
        // Validate input
        if (empty($email) || empty($password)) {
            return "Email and password are required.";
        }
        
        if (!validate_email($email)) {
            return "Please enter a valid email address.";
        }
        
        $user = new user_class();
        $customer_data = $user->login_customer($email, $password);
        
        if ($customer_data) {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Store customer data in session (using consistent naming)
            $_SESSION['user_id'] = $customer_data['customer_id'];
            $_SESSION['user_name'] = $customer_data['customer_name'];
            $_SESSION['user_email'] = $customer_data['customer_email'];
            $_SESSION['user_role'] = $customer_data['user_role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            // Keep legacy session variables for backward compatibility
            $_SESSION['customer_id'] = $customer_data['customer_id'];
            $_SESSION['customer_name'] = $customer_data['customer_name'];
            $_SESSION['customer_email'] = $customer_data['customer_email'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $cookie_value = base64_encode($customer_data['customer_id'] . ':' . hash('sha256', $customer_data['customer_email']));
                setcookie('remember_token', $cookie_value, time() + REMEMBER_TOKEN_LIFETIME, '/');
            }
            
            // Log successful login
            log_activity('user_login', "User logged in: $email", $customer_data['customer_id']);
            
            return "success";
        } else {
            // Log failed login attempt
            log_activity('login_failed', "Failed login attempt for: $email");
            return "Invalid email or password.";
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return "An error occurred during login. Please try again.";
    }
}

/**
 * Logout customer
 */
function logout_user_ctr() {
    try {
        $customer_id = $_SESSION['customer_id'] ?? null;
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log logout
        if ($customer_id) {
            log_activity('user_logout', "User logged out", $customer_id);
        }
        
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
        
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return "An error occurred during logout.";
    }
}


/**
 * Get current customer data
 */
function get_current_customer() {
    if (!is_logged_in()) {
        return false;
    }
    
    try {
        $user = new user_class();
        return $user->get_customer_by_id($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Error getting current customer: " . $e->getMessage());
        return false;
    }
}

/**
 * Update customer profile
 */
function update_customer_ctr($customer_id, $name, $email, $country, $city, $contact) {
    try {
        // Validate input
        $validation_rules = [
            'name' => ['required' => true, 'name' => true, 'max_length' => 100],
            'email' => ['required' => true, 'email' => true],
            'country' => ['required' => true, 'max_length' => 50],
            'city' => ['required' => true, 'max_length' => 50],
            'contact' => ['required' => true, 'phone' => true]
        ];
        
        $data = compact('name', 'email', 'country', 'city', 'contact');
        $errors = validate_form($data, $validation_rules);
        
        if (!empty($errors)) {
            return implode(' ', array_values($errors));
        }
        
        $user = new user_class();
        
        // Check if email is being changed and if it already exists
        if ($email !== $_SESSION['customer_email']) {
            if ($user->email_exists($email)) {
                return "Email already registered.";
            }
        }
        
        $updated = $user->update_customer($customer_id, $name, $email, $country, $city, $contact);
        
        if ($updated) {
            // Update session data
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            
            // Log profile update
            log_activity('profile_updated', "Profile updated", $customer_id);
            
            return "success";
        } else {
            return "Update failed. Please try again.";
        }
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return "An error occurred while updating profile.";
    }
}

/**
 * Change customer password
 */
function change_password_ctr($customer_id, $current_password, $new_password) {
    try {
        // Validate input
        if (empty($current_password) || empty($new_password)) {
            return "Current password and new password are required.";
        }
        
        // Validate new password
        $password_validation = validate_password($new_password);
        if ($password_validation !== true) {
            return $password_validation;
        }
        
        $user = new user_class();
        
        // Verify current password
        $current_hash = $user->get_password_hash($customer_id);
        if (!$current_hash || !password_verify($current_password, $current_hash)) {
            return "Current password is incorrect.";
        }
        
        // Check if new password is different from current
        if (password_verify($new_password, $current_hash)) {
            return "New password must be different from current password.";
        }
        
        $changed = $user->change_password($customer_id, $new_password);
        
        if ($changed) {
            // Log password change
            log_activity('password_changed', "Password changed", $customer_id);
            
            return "success";
        } else {
            return "Password change failed. Please try again.";
        }
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        return "An error occurred while changing password.";
    }
}

/**
 * Handle forgot password request
 */
function forgot_password_ctr($email) {
    try {
        // Rate limiting
        check_action_rate_limit('forgot_password', 3, 600); // 3 attempts per 10 minutes
        
        // Validate email
        if (!validate_email($email)) {
            return "Please enter a valid email address.";
        }
        
        $user = new user_class();
        
        // Check if email exists
        $customer_data = $user->get_customer_by_email($email);
        if (!$customer_data) {
            // Don't reveal if email exists or not for security
            return "success";
        }
        
        // Create reset token
        $token = $user->create_reset_token($customer_data['customer_id']);
        if (!$token) {
            return "Failed to create reset token. Please try again.";
        }
        
        // Send reset email (placeholder)
        $reset_link = BASE_URL . "/view/user/reset_password.php?token=$token";
        $subject = "Password Reset - " . APP_NAME;
        $message = "Click the following link to reset your password: $reset_link";
        
        send_email_notification($email, $subject, $message);
        
        // Log password reset request
        log_activity('password_reset_requested', "Password reset requested for: $email", $customer_data['customer_id']);
        
        return "success";
        
    } catch (Exception $e) {
        error_log("Forgot password error: " . $e->getMessage());
        return "An error occurred. Please try again.";
    }
}

/**
 * Reset password with token
 */
function reset_password_ctr($token, $new_password) {
    try {
        // Validate input
        if (empty($token) || empty($new_password)) {
            return "Invalid request.";
        }
        
        // Validate new password
        $password_validation = validate_password($new_password);
        if ($password_validation !== true) {
            return $password_validation;
        }
        
        $user = new user_class();
        
        // Verify token
        $token_data = $user->verify_reset_token($token);
        if (!$token_data) {
            return "Invalid or expired reset token.";
        }
        
        // Change password
        $changed = $user->change_password($token_data['customer_id'], $new_password);
        
        if ($changed) {
            // Delete the used token
            $user->delete_reset_token($token);
            
            // Log password reset
            log_activity('password_reset', "Password reset completed", $token_data['customer_id']);
            
            return "success";
        } else {
            return "Password reset failed. Please try again.";
        }
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return "An error occurred while resetting password.";
    }
}

/**
 * Check remember me cookie and auto-login
 */
function check_remember_me() {
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        try {
            $token = $_COOKIE['remember_token'];
            $decoded = base64_decode($token);
            $parts = explode(':', $decoded);
            
            if (count($parts) === 2) {
                $customer_id = $parts[0];
                $email_hash = $parts[1];
                
                $user = new user_class();
                $customer_data = $user->get_customer_by_id($customer_id);
                
                if ($customer_data && hash('sha256', $customer_data['customer_email']) === $email_hash) {
                    // Start session
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    // Set session data
                    $_SESSION['customer_id'] = $customer_data['customer_id'];
                    $_SESSION['customer_name'] = $customer_data['customer_name'];
                    $_SESSION['customer_email'] = $customer_data['customer_email'];
                    $_SESSION['user_role'] = $customer_data['user_role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Log auto-login
                    log_activity('auto_login', "Auto-login via remember token", $customer_data['customer_id']);
                    
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("Remember me error: " . $e->getMessage());
        }
        
        // Clear invalid cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

/**
 * Get user dashboard data
 */
function get_user_dashboard_data($customer_id) {
    try {
        $user = new user_class();
        $customer_data = $user->get_customer_by_id($customer_id);
        
        if (!$customer_data) {
            return false;
        }
        
        // Get user statistics (placeholder for future implementation)
        $stats = [
            'orders' => 0,
            'total_spent' => 0.00,
            'wishlist_items' => 0,
            'reviews' => 0
        ];
        
        return [
            'customer' => $customer_data,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Dashboard data error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user avatar/image
 */
function update_user_avatar_ctr($customer_id, $image_file) {
    try {
        // Validate file upload
        $upload_result = handle_file_upload($image_file, 'avatars/', ['jpg', 'jpeg', 'png']);
        
        if (!$upload_result['success']) {
            return $upload_result['message'];
        }
        
        $user = new user_class();
        $updated = $user->update_customer_image($customer_id, $upload_result['filepath']);
        
        if ($updated) {
            // Log avatar update
            log_activity('avatar_updated', "Avatar updated", $customer_id);
            
            return [
                'success' => true,
                'message' => 'Avatar updated successfully.',
                'image_url' => $upload_result['url']
            ];
        } else {
            // Delete uploaded file if database update failed
            delete_file($upload_result['filepath']);
            return "Failed to update avatar. Please try again.";
        }
        
    } catch (Exception $e) {
        error_log("Avatar update error: " . $e->getMessage());
        return "An error occurred while updating avatar.";
    }
}

// Auto-check remember me on every page load
check_remember_me();

?>
