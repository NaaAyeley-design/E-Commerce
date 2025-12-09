<?php
/**
 * Validation Functions
 * 
 * Contains all validation functions for user input, forms, and data.
 */

/**
 * Validate email address
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validate_password($password, $min_length = 6) {
    if (strlen($password) < $min_length) {
        return "Password must be at least {$min_length} characters long.";
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter.";
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter.";
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number.";
    }
    
    return true; // Password is valid
}

/**
 * Validate phone number
 */
function validate_phone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if phone number has at least 10 digits
    if (strlen($phone) < 10) {
        return false;
    }
    
    return true;
}

/**
 * Validate name (letters, spaces, hyphens, apostrophes only)
 */
function validate_name($name, $min_length = 2, $max_length = 50) {
    $name = trim($name);
    
    if (strlen($name) < $min_length || strlen($name) > $max_length) {
        return false;
    }
    
    // Allow letters, spaces, hyphens, and apostrophes
    return preg_match("/^[a-zA-Z\s\-']+$/", $name);
}

/**
 * Validate required field
 */
function validate_required($value, $field_name = 'Field') {
    if (empty(trim($value))) {
        return "{$field_name} is required.";
    }
    return true;
}

/**
 * Validate string length
 */
function validate_length($value, $min = 0, $max = 255, $field_name = 'Field') {
    $length = strlen(trim($value));
    
    if ($length < $min) {
        return "{$field_name} must be at least {$min} characters long.";
    }
    
    if ($length > $max) {
        return "{$field_name} must be no more than {$max} characters long.";
    }
    
    return true;
}

/**
 * Validate numeric value
 */
function validate_numeric($value, $min = null, $max = null, $field_name = 'Value') {
    if (!is_numeric($value)) {
        return "{$field_name} must be a valid number.";
    }
    
    $value = (float)$value;
    
    if ($min !== null && $value < $min) {
        return "{$field_name} must be at least {$min}.";
    }
    
    if ($max !== null && $value > $max) {
        return "{$field_name} must be no more than {$max}.";
    }
    
    return true;
}

/**
 * Validate URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate date format
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate file upload
 */
function validate_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return "No file was uploaded.";
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "File upload error occurred.";
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $max_mb = $max_size / 1024 / 1024;
        return "File size must be less than {$max_mb}MB.";
    }
    
    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        $allowed_str = implode(', ', $allowed_types);
        return "File type not allowed. Allowed types: {$allowed_str}";
    }
    
    // Check if it's actually an image (for image uploads)
    if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return "File is not a valid image.";
        }
    }
    
    return true;
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token from form submission
 * Validates token from POST data and exits with error if invalid
 */
function validate_form_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    
    if (empty($token)) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token is required.']);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
    }
    
    if (!validate_csrf_token($token)) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate multiple fields at once
 */
function validate_form($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $field_rules) {
        $value = isset($data[$field]) ? $data[$field] : '';
        
        foreach ($field_rules as $rule => $params) {
            switch ($rule) {
                case 'required':
                    if ($params && empty(trim($value))) {
                        $errors[$field] = ucfirst($field) . ' is required.';
                        break 2; // Skip other rules if required fails
                    }
                    break;
                    
                case 'email':
                    if ($params && !empty($value) && !validate_email($value)) {
                        $errors[$field] = 'Please enter a valid email address.';
                    }
                    break;
                    
                case 'min_length':
                    if (!empty($value) && strlen($value) < $params) {
                        $errors[$field] = ucfirst($field) . " must be at least {$params} characters long.";
                    }
                    break;
                    
                case 'max_length':
                    if (!empty($value) && strlen($value) > $params) {
                        $errors[$field] = ucfirst($field) . " must be no more than {$params} characters long.";
                    }
                    break;
                    
                case 'phone':
                    if ($params && !empty($value) && !validate_phone($value)) {
                        $errors[$field] = 'Please enter a valid phone number.';
                    }
                    break;
                    
                case 'name':
                    if ($params && !empty($value) && !validate_name($value)) {
                        $errors[$field] = 'Please enter a valid name (letters, spaces, hyphens, and apostrophes only).';
                    }
                    break;
            }
        }
    }
    
    return $errors;
}

?>
