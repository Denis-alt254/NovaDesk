<?php
// config/Database.php

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    // Database configuration
    private string $host = 'localhost';
    private string $dbname = 'novadesk_db';
    private string $username = 'root';
    private string $password = ''; // Set your MySQL password here
    private string $charset = 'utf8mb4';

    /**
     * Private constructor prevents direct instantiation with 'new'
     */
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforce real SQL prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log error internally in production; prevent credential leaks
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database Connection Failed. Please try again later.");
        }
    }

    /**
     * Prevent cloning of the Singleton instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the Singleton instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton.");
    }

    /**
     * Returns the single instance of the Database class
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Returns the PDO connection instance
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }

    /**
     * Helper method to execute prepared queries directly
     */
    public function runQuery(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}