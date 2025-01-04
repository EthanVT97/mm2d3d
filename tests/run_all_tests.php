<?php
echo "Running All Tests...\n\n";

echo "=== Database Tests ===\n";
include_once 'database_test.php';

echo "\n=== API Tests ===\n";
include_once 'api_test.php';

echo "\n=== Frontend Tests ===\n";
echo "Please open http://localhost:8000/tests/test_runner.html in your browser to run frontend tests.\n";
?>
