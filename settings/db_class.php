<?php
/**
 * Database Connection Class (Settings Version)
 * 
 * Alternative database class located in settings folder.
 * This provides the same functionality as class/db_class.php but
 * can be used if you prefer to keep database configuration in settings.
 */

class db_class {
    protected $conn;
    private static $instance = null;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Singleton pattern for database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            // Use MySQLi for compatibility with existing code
            $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            // Set charset
            $this->conn->set_charset(DB_CHARSET ?? 'utf8mb4');
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
        } catch (Exception $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors
     */
    private function handleConnectionError($exception) {
        if (defined('APP_ENV') && APP_ENV === 'development') {
            die("Database connection failed: " . $exception->getMessage());
        } else {
            error_log("Database connection failed: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database connection (MySQLi object)
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a prepared statement (MySQLi style)
     */
    public function prepare($sql) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            return $stmt;
        } catch (Exception $e) {
            $this->handleQueryError($e, $sql);
            return false;
        }
    }
    
    /**
     * Execute a direct query
     */
    public function query($sql) {
        try {
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $this->conn->error);
            }
            return $result;
        } catch (Exception $e) {
            $this->handleQueryError($e, $sql);
            return false;
        }
    }
    
    /**
     * Escape string for SQL
     */
    public function escape_string($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Get last insert ID
     */
    public function insert_id() {
        return $this->conn->insert_id;
    }
    
    /**
     * Get affected rows
     */
    public function affected_rows() {
        return $this->conn->affected_rows;
    }
    
    /**
     * Get number of rows in result
     */
    public function num_rows($result) {
        return $result->num_rows;
    }
    
    /**
     * Fetch associative array
     */
    public function fetch_assoc($result) {
        return $result->fetch_assoc();
    }
    
    /**
     * Fetch all results as associative array
     */
    public function fetch_all($result, $result_type = MYSQLI_ASSOC) {
        return $result->fetch_all($result_type);
    }
    
    /**
     * Begin transaction
     */
    public function begin_transaction() {
        return $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * Check if connection is still alive
     */
    public function ping() {
        return $this->conn->ping();
    }
    
    /**
     * Get server info
     */
    public function get_server_info() {
        return $this->conn->server_info;
    }
    
    /**
     * Get client info
     */
    public function get_client_info() {
        return $this->conn->client_info;
    }
    
    /**
     * Handle query errors
     */
    private function handleQueryError($exception, $sql) {
        $error_msg = "Database query error: " . $exception->getMessage() . " | SQL: " . $sql;
        
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "<div style='color: red; font-family: monospace; padding: 10px; background: #f8f8f8; border: 1px solid #ddd; margin: 10px;'>";
            echo "<strong>Database Error:</strong><br>";
            echo htmlspecialchars($error_msg);
            echo "</div>";
        } else {
            error_log($error_msg);
        }
    }
    
    /**
     * Execute a simple select query and return results
     */
    public function select($table, $columns = '*', $where = '', $order = '', $limit = '') {
        $sql = "SELECT $columns FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        if (!empty($order)) {
            $sql .= " ORDER BY $order";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    /**
     * Execute a simple insert query
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($sql);
        
        if ($stmt) {
            $types = str_repeat('s', count($data)); // Assume all strings for simplicity
            $stmt->bind_param($types, ...array_values($data));
            
            if ($stmt->execute()) {
                $insert_id = $this->insert_id();
                $stmt->close();
                return $insert_id;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Execute a simple update query
     */
    public function update($table, $data, $where, $where_params = []) {
        $set_clause = [];
        foreach (array_keys($data) as $column) {
            $set_clause[] = "$column = ?";
        }
        $set_clause = implode(', ', $set_clause);
        
        $sql = "UPDATE $table SET $set_clause WHERE $where";
        $stmt = $this->prepare($sql);
        
        if ($stmt) {
            $params = array_merge(array_values($data), $where_params);
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $affected = $this->affected_rows();
                $stmt->close();
                return $affected;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Execute a simple delete query
     */
    public function delete($table, $where, $where_params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->prepare($sql);
        
        if ($stmt) {
            if (!empty($where_params)) {
                $types = str_repeat('s', count($where_params)); // Assume all strings for simplicity
                $stmt->bind_param($types, ...$where_params);
            }
            
            if ($stmt->execute()) {
                $affected = $this->affected_rows();
                $stmt->close();
                return $affected;
            }
            
            $stmt->close();
        }
        
        return false;
    }
    
    /**
     * Check if table exists
     */
    public function table_exists($table_name) {
        $sql = "SHOW TABLES LIKE ?";
        $stmt = $this->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('s', $table_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        }
        
        return false;
    }
    
    /**
     * Get table columns
     */
    public function get_table_columns($table_name) {
        $sql = "DESCRIBE $table_name";
        $result = $this->query($sql);
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
    
    /**
     * Close connection
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}

?>
