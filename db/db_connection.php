<?php
class db_connection {
    protected $conn;

    function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "shoppn");
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
}

?>
