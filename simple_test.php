<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded extensions:\n";
print_r(get_loaded_extensions());

echo "\nChecking PDO drivers:\n";
print_r(PDO::getAvailableDrivers());

echo "\nTesting PostgreSQL connection:\n";
try {
    $dsn = "pgsql:host=aws-0-ap-southeast-1.pooler.supabase.com;port=6543;dbname=postgres";
    echo "DSN: $dsn\n";
    
    $pdo = new PDO($dsn, "postgres.jaubdheyosmukdxvctbq", "admin123");
    echo "Connected successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
