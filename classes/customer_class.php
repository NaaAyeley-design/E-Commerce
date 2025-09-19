<?php
require_once("../db/db_connection.php");

class customer_class extends db_connection {

    public function add_customer($name, $email, $password, $country, $city, $contact, $role=2) {
        // Encrypt password
        $hashed_pass = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO customer 
                (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssi", $name, $email, $hashed_pass, $country, $city, $contact, $role);

        return $stmt->execute();
    }

    public function email_exists($email) {
        $sql = "SELECT customer_id FROM customer WHERE customer_email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function login_customer($email, $password) {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_pass, user_role 
                FROM customer WHERE customer_email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if (password_verify($password, $customer['customer_pass'])) {
                return $customer;
            }
        }
        return false;
    }

    public function get_customer_by_id($customer_id) {
        $sql = "SELECT customer_id, customer_name, customer_email, customer_country, 
                customer_city, customer_contact, customer_image, user_role 
                FROM customer WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function update_customer($customer_id, $name, $email, $country, $city, $contact) {
        $sql = "UPDATE customer SET customer_name = ?, customer_email = ?, 
                customer_country = ?, customer_city = ?, customer_contact = ? 
                WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $email, $country, $city, $contact, $customer_id);
        return $stmt->execute();
    }

    public function change_password($customer_id, $new_password) {
        $hashed_pass = password_hash($new_password, PASSWORD_BCRYPT);
        $sql = "UPDATE customer SET customer_pass = ? WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashed_pass, $customer_id);
        return $stmt->execute();
    }

    public function get_customer_by_email($email) {
        $sql = "SELECT customer_id, customer_name, customer_email FROM customer WHERE customer_email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function create_reset_token($customer_id) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "INSERT INTO password_resets (customer_id, token, expires_at) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $customer_id, $token, $expires);
        
        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function verify_reset_token($token) {
        $sql = "SELECT pr.customer_id, c.customer_email 
                FROM password_resets pr 
                JOIN customer c ON pr.customer_id = c.customer_id 
                WHERE pr.token = ? AND pr.expires_at > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function delete_reset_token($token) {
        $sql = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }
}
?>
