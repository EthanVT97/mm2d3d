<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    // Try a simple query
    $query = "SELECT current_timestamp";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database connection successful!\n";
    echo "Current timestamp from database: " . print_r($result, true) . "\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
