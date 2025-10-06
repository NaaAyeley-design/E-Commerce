<?php
// Include database credentials
require_once __DIR__ . '/db_cred.php';

class db_connection {
    protected $conn;

    function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
}

?>
