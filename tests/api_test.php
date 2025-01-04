<?php
class APITest {
    private $base_url;
    private $test_results = [];
    private $test_token;

    public function __construct() {
        $this->base_url = 'http://localhost/2ddd/api';
    }

    private function log($test_name, $result, $message = '') {
        $status = $result ? '✓ PASS' : '✗ FAIL';
        $log_entry = "[$status] $test_name" . ($message ? ": $message" : '');
        $this->test_results[] = ['name' => $test_name, 'status' => $status, 'message' => $message];
        echo $log_entry . "\n";
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null, $token = null) {
        $ch = curl_init();
        $url = $this->base_url . $endpoint;
        
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $http_code,
            'response' => json_decode($response, true)
        ];
    }

    public function testUserAPI() {
        // Test user registration
        $user_data = [
            'phone_number' => '123456789',
            'name' => 'Test User',
            'password' => 'test123'
        ];

        $response = $this->makeRequest('/agent/register.php', 'POST', $user_data);
        $this->log(
            'User Registration',
            $response['status'] === 201,
            json_encode($response['response'])
        );

        // Test user login
        $login_data = [
            'phone_number' => '123456789',
            'password' => 'test123'
        ];

        $response = $this->makeRequest('/agent/login.php', 'POST', $login_data);
        $this->log(
            'User Login',
            $response['status'] === 200 && isset($response['response']['token']),
            json_encode($response['response'])
        );

        if (isset($response['response']['token'])) {
            $this->test_token = $response['response']['token'];
        }
    }

    public function testTransactionAPI() {
        if (!$this->test_token) {
            $this->log('Transaction Tests', false, 'No authentication token available');
            return;
        }

        // Test deposit
        $deposit_data = [
            'amount' => 1000,
            'type' => 'deposit'
        ];

        $response = $this->makeRequest(
            '/agent/transactions.php',
            'POST',
            $deposit_data,
            $this->test_token
        );

        $this->log(
            'Deposit Transaction',
            $response['status'] === 201,
            json_encode($response['response'])
        );

        // Test transaction history
        $response = $this->makeRequest(
            '/agent/transactions.php',
            'GET',
            null,
            $this->test_token
        );

        $this->log(
            'Transaction History',
            $response['status'] === 200 && isset($response['response']['transactions']),
            json_encode($response['response'])
        );
    }

    public function testLotteryAPI() {
        if (!$this->test_token) {
            $this->log('Lottery Tests', false, 'No authentication token available');
            return;
        }

        // Test placing bet
        $bet_data = [
            'lottery_type' => '2D',
            'number' => '25',
            'amount' => 100
        ];

        $response = $this->makeRequest(
            '/agent/playbets.php',
            'POST',
            $bet_data,
            $this->test_token
        );

        $this->log(
            'Place Bet',
            $response['status'] === 201,
            json_encode($response['response'])
        );

        // Test bet history
        $response = $this->makeRequest(
            '/agent/playbets.php',
            'GET',
            null,
            $this->test_token
        );

        $this->log(
            'Bet History',
            $response['status'] === 200 && isset($response['response']['bets']),
            json_encode($response['response'])
        );
    }

    public function testAnalyticsAPI() {
        if (!$this->test_token) {
            $this->log('Analytics Tests', false, 'No authentication token available');
            return;
        }

        // Test lottery analytics
        $response = $this->makeRequest(
            '/agent/lottery_analytics.php',
            'GET',
            null,
            $this->test_token
        );

        $this->log(
            'Lottery Analytics',
            $response['status'] === 200 && isset($response['response']['analytics']),
            json_encode($response['response'])
        );

        // Test user behavior analytics
        $response = $this->makeRequest(
            '/agent/user_behavior.php',
            'GET',
            null,
            $this->test_token
        );

        $this->log(
            'User Behavior Analytics',
            $response['status'] === 200 && isset($response['response']['behavior']),
            json_encode($response['response'])
        );
    }

    public function runTests() {
        echo "Starting API Tests...\n";
        
        $this->testUserAPI();
        $this->testTransactionAPI();
        $this->testLotteryAPI();
        $this->testAnalyticsAPI();
        
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
$tester = new APITest();
$tester->runTests();
?>
