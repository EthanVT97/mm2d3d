<?php
echo "Testing Authentication System\n";
echo "===========================\n\n";

$base_url = "http://localhost:8000/api/auth";

// Function to make API requests
function makeRequest($endpoint, $method = 'POST', $data = null) {
    global $base_url;
    
    $ch = curl_init($base_url . $endpoint);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'response' => json_decode($response, true)
    ];
}

// Test Registration
echo "1. Testing Registration\n";
echo "----------------------\n";

$register_data = [
    'phone_number' => '09123456789',
    'name' => 'Test User',
    'password' => 'test123'
];

$register_response = makeRequest('/register.php', 'POST', $register_data);
echo "Status Code: " . $register_response['status_code'] . "\n";
echo "Response: " . json_encode($register_response['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test Login
echo "2. Testing Login\n";
echo "---------------\n";

$login_data = [
    'phone_number' => '09123456789',
    'password' => 'test123'
];

$login_response = makeRequest('/login.php', 'POST', $login_data);
echo "Status Code: " . $login_response['status_code'] . "\n";
echo "Response: " . json_encode($login_response['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test Invalid Login
echo "3. Testing Invalid Login\n";
echo "----------------------\n";

$invalid_login_data = [
    'phone_number' => '09123456789',
    'password' => 'wrongpassword'
];

$invalid_login_response = makeRequest('/login.php', 'POST', $invalid_login_data);
echo "Status Code: " . $invalid_login_response['status_code'] . "\n";
echo "Response: " . json_encode($invalid_login_response['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test Invalid Phone Number Format
echo "4. Testing Invalid Phone Number Format\n";
echo "---------------------------------\n";

$invalid_phone_data = [
    'phone_number' => '1234567890',
    'name' => 'Test User',
    'password' => 'test123'
];

$invalid_phone_response = makeRequest('/register.php', 'POST', $invalid_phone_data);
echo "Status Code: " . $invalid_phone_response['status_code'] . "\n";
echo "Response: " . json_encode($invalid_phone_response['response'], JSON_PRETTY_PRINT) . "\n\n";

// Test Weak Password
echo "5. Testing Weak Password\n";
echo "----------------------\n";

$weak_password_data = [
    'phone_number' => '09123456789',
    'name' => 'Test User',
    'password' => '123'
];

$weak_password_response = makeRequest('/register.php', 'POST', $weak_password_data);
echo "Status Code: " . $weak_password_response['status_code'] . "\n";
echo "Response: " . json_encode($weak_password_response['response'], JSON_PRETTY_PRINT) . "\n\n";

// Store token if login was successful
if ($login_response['status_code'] === 200 && isset($login_response['response']['data']['token'])) {
    $token = $login_response['response']['data']['token'];
    echo "Authentication token received: " . substr($token, 0, 20) . "...\n";
    echo "You can use this token for authenticated requests by adding the header:\n";
    echo "Authorization: Bearer " . $token . "\n";
}
?>
