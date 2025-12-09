<?php
/**
 * User Controller
 * 
 * Handles all user-related operations including authentication,
 * registration, profile management, and user sessions.
 */

// Load core settings and general controller dependencies
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/general_controller.php';
require_once __DIR__ . '/../class/user_class.php';

/**
 * Register new customer or designer/producer
 * 
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $password User's password
 * @param string $country User's country
 * @param string $city User's city
 * @param string $contact User's contact number
 * @param int $user_role User role (2 = Customer, 3 = Designer/Producer)
 * @param string $business_name Optional business name for designers
 * @param string $bio Optional bio/description for designers
 * @return string "success" on success, error message on failure
 */
function register_user_ctr($name, $email, $password, $country, $city, $contact, $user_role = 2, $business_name = '', $bio = '')
{
    try {
        // Validate user role - only allow 2 (customer) or 3 (designer)
        // Role 1 (admin) cannot be set through registration
        if ($user_role !== 2 && $user_role !== 3) {
            $user_role = 2; // Default to customer if invalid
        }
        
        // Input validation rules
        $validation_rules = [
            'name' => ['required' => true, 'max_length' => 100],
            'email' => ['required' => true, 'email' => true],
            'password' => ['required' => true, 'min_length' => 6],
            'country' => ['required' => true, 'max_length' => 50],
            'city' => ['required' => true, 'max_length' => 50],
            'contact' => ['required' => true]
        ];

        $data = compact('name', 'email', 'password', 'country', 'city', 'contact');
        $errors = validate_form($data, $validation_rules);

        if (!empty($errors)) {
            return implode(' ', array_values($errors));
        }

        $password_validation = validate_password($password);
        if ($password_validation !== true) {
            return $password_validation;
        }

        $user = new user_class();

        if ($user->email_exists($email)) {
            return "Email already registered.";
        }

        // Add customer/designer with role and optional designer fields
        $inserted = $user->add_customer($name, $email, $password, $country, $city, $contact, $user_role, $business_name, $bio);

        if ($inserted) {
            // Skip logging and email for now to prevent output interference
            // log_activity('user_registered', "New user registered: $email (Role: $user_role)");
            // send_email_notification($email, 'Welcome to ' . APP_NAME, 'Thank you for registering!');
            
            return "success";
        }

        return "Registration failed. Please try again.";

    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return "An error occurred during registration.";
    }
}

/**
 * Login customer
 */
function login_user_ctr($email, $password, $remember = false)
{
    try {
        if (empty($email) || empty($password)) {
            return "Email and password are required.";
        }

        if (!validate_email($email)) {
            return "Please enter a valid email address.";
        }

        $user = new user_class();
        $customer_data = $user->login_customer($email, $password);
        
        // Check if database connection failed
        if ($customer_data === false && $user->getConnection() === null) {
            error_log("Login failed: Database connection not available");
            return "Database connection error. Please check if MySQL is running in XAMPP.";
        }

        if ($customer_data) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $customer_data['customer_id'];
            $_SESSION['user_name'] = $customer_data['customer_name'];
            $_SESSION['user_email'] = $customer_data['customer_email'];
            $_SESSION['user_role'] = $customer_data['user_role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();

            // Legacy session keys for backward compatibility
            $_SESSION['customer_id'] = $customer_data['customer_id'];
            $_SESSION['customer_name'] = $customer_data['customer_name'];
            $_SESSION['customer_email'] = $customer_data['customer_email'];

            // Set remember me cookie
            if ($remember) {
                $cookie_value = base64_encode(
                    $customer_data['customer_id'] . ':' . hash('sha256', $customer_data['customer_email'])
                );
                setcookie('remember_token', $cookie_value, time() + (REMEMBER_TOKEN_LIFETIME ?? 86400 * 30), '/');
            }

            log_activity('user_login', "User logged in: $email", $customer_data['customer_id']);
            return "success";
        }

        log_activity('login_failed', "Failed login attempt for: $email");
        return "Invalid email or password.";

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return "An error occurred during login.";
    }
}

/**
 * Logout customer
 */
function logout_user_ctr()
{
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

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
        
        // Clear remember token cookie
        setcookie('remember_token', '', time() - 3600, '/');
        
        // Destroy the session
        session_destroy();

        return "success";

    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return "Logout failed.";
    }
}

/**
 * Get current customer data
 */
function get_current_customer()
{
    if (!is_logged_in()) {
        return false;
    }

    try {
        $user = new user_class();
        return $user->get_customer_by_id($_SESSION['user_id']);
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update customer profile
 */
function update_customer_ctr($customer_id, $name, $email, $country, $city, $contact)
{
    try {
        $validation_rules = [
            'name' => ['required' => true, 'max_length' => 100],
            'email' => ['required' => true, 'email' => true],
            'country' => ['required' => true, 'max_length' => 50],
            'city' => ['required' => true, 'max_length' => 50],
            'contact' => ['required' => true]
        ];

        $data = compact('name', 'email', 'country', 'city', 'contact');
        $errors = validate_form($data, $validation_rules);

        if (!empty($errors)) {
            return implode(' ', array_values($errors));
        }

        $user = new user_class();

        if ($email !== ($_SESSION['customer_email'] ?? '') && $user->email_exists($email)) {
            return "Email already registered.";
        }

        $updated = $user->update_customer($customer_id, $name, $email, $country, $city, $contact);

        if ($updated) {
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;

            log_activity('profile_updated', "Profile updated", $customer_id);
            return "success";
        }

        return "Update failed. Please try again.";

    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return "An error occurred while updating profile.";
    }
}

/**
 * Check remember me cookie and auto-login
 */
function check_remember_me()
{
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
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    $_SESSION['customer_id'] = $customer_data['customer_id'];
                    $_SESSION['customer_name'] = $customer_data['customer_name'];
                    $_SESSION['customer_email'] = $customer_data['customer_email'];
                    $_SESSION['user_role'] = $customer_data['user_role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();

                    log_activity('auto_login', "Auto-login via remember token", $customer_data['customer_id']);
                    return true;
                }
            }

        } catch (Exception $e) {
            error_log("Remember me error: " . $e->getMessage());
        }

        setcookie('remember_token', '', time() - 3600, '/');
    }

    return false;
}

/**
 * Get all users (admin only)
 */
function get_all_users_ctr($admin_user_id) {
    try {
        // Verify admin access
        if (!is_admin()) {
            return "Admin access required.";
        }
        
        $user = new user_class();
        $users = $user->get_all_customers();
        
        // get_all_customers now returns an array (empty array if no users, or array of users)
        // It never returns false anymore - it returns [] on error
        if (!is_array($users)) {
            error_log("get_all_users_ctr: get_all_customers returned non-array: " . gettype($users));
            return "Failed to retrieve users. Invalid data type returned.";
        }
        
        return $users;
        
    } catch (Exception $e) {
        error_log("Get all users error: " . $e->getMessage());
        error_log("Get all users trace: " . $e->getTraceAsString());
        return "An error occurred while retrieving users: " . $e->getMessage();
    }
}

/**
 * Toggle user status (admin only)
 */
function toggle_user_status_ctr($target_user_id, $admin_user_id) {
    try {
        // Verify admin access
        if (!is_admin()) {
            return "Admin access required.";
        }
        
        // Prevent self-modification
        if ($target_user_id == $admin_user_id) {
            return "Cannot modify your own account.";
        }
        
        $user = new user_class();
        
        // Get current user status
        $current_user = $user->get_customer_by_id($target_user_id);
        if (!$current_user) {
            return "User not found.";
        }
        
        // Toggle status
        $new_status = $current_user['is_active'] ? 0 : 1;
        $result = $user->update_customer_status($target_user_id, $new_status);
        
        if ($result) {
            return "success";
        } else {
            return "Failed to update user status.";
        }
        
    } catch (Exception $e) {
        error_log("Toggle user status error: " . $e->getMessage());
        return "An error occurred while updating user status.";
    }
}

// Note: check_remember_me() is no longer auto-executed on every page load
// to prevent database connection delays. It should be called explicitly
// only when needed (e.g., in login pages or authentication checks).
?>