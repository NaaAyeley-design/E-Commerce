<?php
require_once("db_connection.php");

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
}
?>
