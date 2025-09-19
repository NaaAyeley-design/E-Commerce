<?php
// Create a test user in your existing shoppn database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Test User</h2>";

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "shoppn";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to shoppn database<br>";

// Test user data
$test_email = "test@example.com";
$test_password = password_hash("password123", PASSWORD_BCRYPT);
$test_name = "Test User";
$test_country = "USA";
$test_city = "New York";
$test_contact = "1234567890";
$user_role = 2; // Customer role

// Check if user already exists
$check_sql = "SELECT customer_id FROM customer WHERE customer_email = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Test user already exists<br>";
} else {
    // Insert test user
    $insert_sql = "INSERT INTO customer (customer_name, customer_email, customer_pass, customer_country, customer_city, customer_contact, user_role) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssssi", $test_name, $test_email, $test_password, $test_country, $test_city, $test_contact, $user_role);
    
    if ($stmt->execute()) {
        echo "✅ Test user created successfully<br>";
    } else {
        echo "❌ Error creating test user: " . $stmt->error . "<br>";
    }
}

// Show test credentials
echo "<br><h3>Test Credentials:</h3>";
echo "<strong>Email:</strong> test@example.com<br>";
echo "<strong>Password:</strong> password123<br>";

$conn->close();

echo "<br><h3>Ready to test!</h3>";
echo "<p><a href='views/login.php'>Go to Login Page</a></p>";
echo "<p><a href='views/dashboard.php'>Go to Dashboard (requires login)</a></p>";
?>

