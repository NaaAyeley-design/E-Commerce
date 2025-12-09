<?php
class db_connection {
    public $db = null;

    public function __construct(){
        // connect to your dbforlab database
        $this->db = mysqli_connect("localhost", "root", "", "dbforlab");

        if(mysqli_connect_errno()){
            die("Database connection failed: " . mysqli_connect_error());
        }
    }
}
?>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Hello from index.php"; // simple test
?>

