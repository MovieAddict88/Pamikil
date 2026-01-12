<?php
/**
 * Database Connection Class
 * 
 * Handles all database connections using PDO with prepared statements
 * to prevent SQL injection attacks.
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        
        $this->connect();
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database The database instance
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
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            if (DEBUG_MODE) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Unable to connect to the database. Please try again later.");
            }
        }
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO The PDO connection object
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query and return results
     * 
     * @param string $query The SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false Results array or false on failure
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | Query: " . $query);
            return false;
        }
    }
    
    /**
     * Execute a query and return a single row
     * 
     * @param string $query The SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false on failure
     */
    public function queryOne($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | Query: " . $query);
            return false;
        }
    }
    
    /**
     * Execute an INSERT, UPDATE, or DELETE query
     * 
     * @param string $query The SQL query
     * @param array $params Parameters for prepared statement
     * @return int|false Number of affected rows or false on failure
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute($params);
            return $result ? $stmt->rowCount() : false;
        } catch (PDOException $e) {
            error_log("Execute Error: " . $e->getMessage() . " | Query: " . $query);
            return false;
        }
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string|false The last inserted ID or false on failure
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $tableName Name of the table
     * @return bool True if table exists, false otherwise
     */
    public function tableExists($tableName) {
        $query = "SHOW TABLES LIKE ?";
        $result = $this->queryOne($query, [$tableName]);
        return $result !== false;
    }
    
    /**
     * Escape a string for use in SQL queries
     * Note: Prefer using prepared statements with execute() instead
     * 
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public function escape($value) {
        return $this->connection->quote($value);
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
