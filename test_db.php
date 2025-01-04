<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $dsn = "pgsql:host=aws-0-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require";
    $username = "postgres.jaubdheyosmukdxvctbq";
    $password = "admin123";

    echo "Attempting to connect to database...\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Connected successfully!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT current_timestamp");
    $result = $stmt->fetch();
    echo "Current timestamp from DB: " . print_r($result, true) . "\n";
    
    // Check if tables exist
    $tables_query = "
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
    ";
    $stmt = $pdo->query($tables_query);
    $tables = $stmt->fetchAll();
    
    echo "\nExisting tables:\n";
    foreach ($tables as $table) {
        echo "- " . $table['table_name'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed:\n";
    echo "Error message: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
