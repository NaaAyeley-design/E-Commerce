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
     * Constructor - Don't connect immediately (lazy loading)
     */
    public function __construct() {
        // Don't connect immediately - use lazy loading
        // Connection will be established on first use
        $this->conn = null;
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
            // Set connection timeout (MySQL timeout in seconds)
            $connection_timeout = 5; // 5 seconds for connection
            
            // Use configuration constants from db_cred.php
            // Add timeout to DSN for MySQL
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";connect_timeout=" . $connection_timeout;
            
            $options = array_merge(DB_OPTIONS, [
                PDO::ATTR_PERSISTENT => DB_PERSISTENT,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => $connection_timeout
            ]);
            
            // Try to connect with timeout
            $this->conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
            
            // Set query timeout (MySQL wait_timeout)
            $this->conn->exec("SET SESSION wait_timeout = 30");
            $this->conn->exec("SET SESSION interactive_timeout = 30");
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }
    
    /**
     * Handle connection errors
     */
    private function handleConnectionError($exception) {
        $error_msg = "Database connection failed: " . $exception->getMessage();
        $error_code = $exception->getCode();
        
        // Always log the error
        error_log($error_msg);
        error_log("PDO Error Code: " . $error_code);
        error_log("Attempted to connect to: " . DB_HOST . " / " . DB_NAME);
        
        // In development, provide more details
        if (APP_ENV === 'development') {
            error_log("DB_USERNAME: " . DB_USERNAME);
            error_log("DB_PASSWORD: " . (DB_PASSWORD ? '***SET***' : '***EMPTY***'));
            
            // Set conn to null but log helpful error messages
            $this->conn = null;
            
            // Common error solutions
            if ($error_code == 1049) {
                error_log("SOLUTION: Database '" . DB_NAME . "' does not exist. Create it in phpMyAdmin.");
            } elseif ($error_code == 1045) {
                error_log("SOLUTION: Access denied. Check username and password in settings/db_cred.php");
            } elseif ($error_code == 2002) {
                error_log("SOLUTION: Cannot connect to MySQL server. Make sure MySQL is running in XAMPP.");
            }
        } else {
            error_log("Database connection failed: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if (!$this->ensureConnection()) {
            if (APP_ENV === 'development') {
                error_log("getConnection() called but connection is null. Check error logs for connection failure details.");
            }
            return null;
        }
        return $this->conn;
    }
    
    /**
     * Ensure database connection is established (lazy loading)
     */
    private function ensureConnection() {
        if ($this->conn === null) {
            $this->connect();
            // If connection still failed, log detailed error
            if ($this->conn === null && APP_ENV === 'development') {
                error_log("ensureConnection() failed - connection is still null after connect() attempt");
                error_log("DB_HOST: " . DB_HOST);
                error_log("DB_NAME: " . DB_NAME);
                error_log("DB_USERNAME: " . DB_USERNAME);
                error_log("DB_PASSWORD: " . (DB_PASSWORD ? '***SET***' : '***EMPTY***'));
            }
        }
        return $this->conn !== null;
    }
    
    /**
     * Execute a prepared statement
     */
    public function execute($sql, $params = []) {
        // Lazy load connection only when needed
        if (!$this->ensureConnection()) {
            error_log("Failed to establish database connection for query: " . substr($sql, 0, 100));
            return false;
        }
        
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
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : false;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        if (!$this->ensureConnection()) {
            return false;
        }
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
        if (!$this->ensureConnection()) {
            return false;
        }
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->conn === null) {
            return false;
        }
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->conn === null) {
            return false;
        }
        return $this->conn->rollback();
    }
    
    /**
     * Handle query errors
     */
    private function handleQueryError($exception, $sql) {
        $error_msg = "Database query error: " . $exception->getMessage() . " | SQL: " . $sql;
        $error_code = $exception->getCode();
        
        // Always log errors, never output HTML (breaks JSON responses)
        error_log($error_msg);
        error_log("PDO Error Code: " . $error_code);
        
        // In development, also log the trace
        if (APP_ENV === 'development') {
            error_log("PDO Error Trace: " . $exception->getTraceAsString());
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
