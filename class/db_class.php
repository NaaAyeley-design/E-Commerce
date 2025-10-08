<?php
/**
 * Database Connection Class
 * 
 * Handles database connections and provides common database operations.
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
            // Use configuration constants from db_cred.php
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = array_merge(DB_OPTIONS, [
                PDO::ATTR_PERSISTENT => DB_PERSISTENT,
                PDO::ATTR_TIMEOUT => DB_TIMEOUT
            ]);
            
            $this->conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors
     */
    private function handleConnectionError($exception) {
        if (APP_ENV === 'development') {
            die("Database connection failed: " . $exception->getMessage());
        } else {
            error_log("Database connection failed: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->handleQueryError($e, $sql);
            return false;
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchRow($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Get row count
     */
    public function rowCount($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
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
     * Handle query errors
     */
    private function handleQueryError($exception, $sql) {
        $error_msg = "Database query error: " . $exception->getMessage() . " | SQL: " . $sql;
        
        if (APP_ENV === 'development') {
            echo "<div style='color: red; font-family: monospace;'>$error_msg</div>";
        } else {
            error_log($error_msg);
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->conn = null;
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}

?>
