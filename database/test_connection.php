<?php
include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    if ($db) {
        echo "✓ Database connection successful!\n";
        
        // Test query execution
        $test_query = "SELECT version();";
        $stmt = $db->query($test_query);
        $version = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✓ Query execution successful!\n";
        echo "Database version: " . $version['version'] . "\n";
        
        // Test prepared statements
        $test_prepared = "SELECT :test_value AS test";
        $stmt = $db->prepare($test_prepared);
        $test_value = "Prepared statement working";
        $stmt->bindParam(':test_value', $test_value);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✓ Prepared statements working!\n";
        
        // Test transaction support
        $db->beginTransaction();
        $db->commit();
        
        echo "✓ Transaction support confirmed!\n";
        
    } else {
        echo "✗ Database connection failed!\n";
    }
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
