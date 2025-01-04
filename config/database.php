<?php
class Database {
    private $host = "aws-0-ap-southeast-1.pooler.supabase.com";
    private $port = "6543";
    private $database = "postgres";
    private $username = "postgres.jaubdheyosmukdxvctbq";
    private $password = "admin123";
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->database . 
                   ";sslmode=require";
                   
            echo "Attempting to connect with DSN: " . $dsn . "\n";
            
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            echo "Connection successful!\n";
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage() . "\n";
            echo "Error Code: " . $e->getCode() . "\n";
            echo "Error File: " . $e->getFile() . "\n";
            echo "Error Line: " . $e->getLine() . "\n";
            echo "Stack Trace: " . $e->getTraceAsString() . "\n";
        }

        return $this->conn;
    }
}
?>
