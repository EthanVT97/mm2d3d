<?php
include_once '../config/database.php';

class DatabaseTest {
    private $db;
    private $test_results = [];

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function log($test_name, $result, $message = '') {
        $status = $result ? '✓ PASS' : '✗ FAIL';
        $log_entry = "[$status] $test_name" . ($message ? ": $message" : '');
        $this->test_results[] = ['name' => $test_name, 'status' => $status, 'message' => $message];
        echo $log_entry . "\n";
    }

    public function testConnection() {
        try {
            $stmt = $this->db->query("SELECT 1");
            $this->log('Database Connection', true);
        } catch (PDOException $e) {
            $this->log('Database Connection', false, $e->getMessage());
        }
    }

    public function testTables() {
        $tables = [
            'users', 'agents', 'transactions', 'playbets', 
            'results', 'user_sessions', 'user_preferences',
            'risk_assessments', 'alerts', 'user_analytics'
        ];

        foreach ($tables as $table) {
            try {
                $stmt = $this->db->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                $this->log("Table '$table' exists", true, "Row count: $count");
            } catch (PDOException $e) {
                $this->log("Table '$table' exists", false, $e->getMessage());
            }
        }
    }

    public function testIndexes() {
        $query = "
        SELECT 
            schemaname, tablename, indexname, indexdef
        FROM 
            pg_indexes 
        WHERE 
            schemaname = 'public'
        ORDER BY 
            tablename, indexname;";

        try {
            $stmt = $this->db->query($query);
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($indexes as $index) {
                $this->log(
                    "Index '{$index['indexname']}' on '{$index['tablename']}'",
                    true,
                    "Definition: {$index['indexdef']}"
                );
            }
        } catch (PDOException $e) {
            $this->log('Index Check', false, $e->getMessage());
        }
    }

    public function testCRUDOperations() {
        try {
            // Test user creation
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (phone_number, name, balance) 
                VALUES (:phone, :name, :balance)
                RETURNING id");
            $phone = '123456789';
            $name = 'Test User';
            $balance = 1000.00;
            
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':balance', $balance);
            $stmt->execute();
            
            $user_id = $stmt->fetchColumn();
            $this->log('User Creation', true, "Created user ID: $user_id");

            // Test user retrieval
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->log('User Retrieval', $user !== false);

            // Test user update
            $stmt = $this->db->prepare("
                UPDATE users 
                SET balance = balance + :amount 
                WHERE id = :id");
            $amount = 500.00;
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $this->log('User Update', $stmt->rowCount() > 0);

            // Test user deletion
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $this->log('User Deletion', $stmt->rowCount() > 0);

            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log('CRUD Operations', false, $e->getMessage());
        }
    }

    public function testTransactions() {
        try {
            $this->db->beginTransaction();

            // Create test user
            $stmt = $this->db->prepare("
                INSERT INTO users (phone_number, name, balance) 
                VALUES (:phone, :name, :balance)
                RETURNING id");
            $phone = '987654321';
            $name = 'Transaction Test User';
            $balance = 2000.00;
            
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':balance', $balance);
            $stmt->execute();
            
            $user_id = $stmt->fetchColumn();

            // Test deposit transaction
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_or_agent_id, type, amount) 
                VALUES (:user_id, :type, :amount)");
            $type = 'deposit';
            $amount = 1000.00;
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':amount', $amount);
            $stmt->execute();
            
            $this->log('Transaction Creation', true);

            // Update user balance
            $stmt = $this->db->prepare("
                UPDATE users 
                SET balance = balance + :amount 
                WHERE id = :id");
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $this->log('Balance Update', true);

            // Verify final balance
            $stmt = $this->db->prepare("
                SELECT balance 
                FROM users 
                WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            $final_balance = $stmt->fetchColumn();
            
            $expected_balance = $balance + $amount;
            $this->log(
                'Balance Verification',
                $final_balance == $expected_balance,
                "Expected: $expected_balance, Actual: $final_balance"
            );

            // Cleanup
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE user_or_agent_id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log('Transaction Test', false, $e->getMessage());
        }
    }

    public function runTests() {
        echo "Starting Database Tests...\n";
        
        $this->testConnection();
        $this->testTables();
        $this->testIndexes();
        $this->testCRUDOperations();
        $this->testTransactions();
        
        echo "\nTest Summary:\n";
        $total = count($this->test_results);
        $passed = count(array_filter($this->test_results, function($test) {
            return strpos($test['status'], 'PASS') !== false;
        }));
        
        echo "Total Tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: " . ($total - $passed) . "\n";
    }
}

// Run tests
$tester = new DatabaseTest();
$tester->runTests();
?>
