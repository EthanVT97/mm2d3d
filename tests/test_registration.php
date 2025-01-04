<?php
echo "Testing Registration System\n";
echo "=========================\n\n";

$base_url = "http://localhost:8000/api/auth";

function makeRequest($endpoint, $method = 'POST', $data = null) {
    global $base_url;
    
    $ch = curl_init($base_url . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'response' => json_decode($response, true)
    ];
}

function runTest($test_name, $data, $expected_status) {
    echo "\nTest: $test_name\n";
    echo "--------------------------------\n";
    echo "Request Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    $response = makeRequest('/register.php', 'POST', $data);
    
    echo "Status Code: " . $response['status_code'] . "\n";
    echo "Response: " . json_encode($response['response'], JSON_PRETTY_PRINT) . "\n";
    
    if ($response['status_code'] === $expected_status) {
        echo "✅ Test Passed\n";
    } else {
        echo "❌ Test Failed (Expected: $expected_status, Got: {$response['status_code']})\n";
    }
    
    return $response;
}

// Test 1: Valid Registration
$test1 = runTest(
    "Valid Registration",
    [
        'phone_number' => '09123456789',
        'name' => 'Test User',
        'password' => 'test123'
    ],
    201
);

// Test 2: Invalid Phone Number Format
$test2 = runTest(
    "Invalid Phone Number Format",
    [
        'phone_number' => '1234567890',
        'name' => 'Test User',
        'password' => 'test123'
    ],
    400
);

// Test 3: Weak Password
$test3 = runTest(
    "Weak Password",
    [
        'phone_number' => '09123456789',
        'name' => 'Test User',
        'password' => '123'
    ],
    400
);

// Test 4: Missing Required Fields
$test4 = runTest(
    "Missing Required Fields",
    [
        'phone_number' => '09123456789',
        'name' => 'Test User'
    ],
    400
);

// Test 5: Duplicate Registration
if ($test1['status_code'] === 201) {
    $test5 = runTest(
        "Duplicate Registration",
        [
            'phone_number' => '09123456789',
            'name' => 'Another User',
            'password' => 'test123'
        ],
        400
    );
}

// Test 6: Valid Myanmar Phone Number Formats
$myanmar_numbers = [
    '09123456789',
    '09978456123',
    '+959123456789',
    '959123456789'
];

echo "\nTesting Various Myanmar Phone Number Formats\n";
echo "----------------------------------------\n";

foreach ($myanmar_numbers as $number) {
    runTest(
        "Myanmar Number Format: $number",
        [
            'phone_number' => $number,
            'name' => "Test User $number",
            'password' => 'test123'
        ],
        201
    );
}

// Test 7: Special Characters in Name
$test7 = runTest(
    "Name with Special Characters",
    [
        'phone_number' => '09111222333',
        'name' => 'မောင်မောင် (အောင်မြင်)',
        'password' => 'test123'
    ],
    201
);

// If registration successful, try logging in
if ($test1['status_code'] === 201) {
    echo "\nTesting Login with Registered Account\n";
    echo "-----------------------------------\n";
    
    $login_response = makeRequest('/login.php', 'POST', [
        'phone_number' => '09123456789',
        'password' => 'test123'
    ]);
    
    echo "Login Status Code: " . $login_response['status_code'] . "\n";
    echo "Login Response: " . json_encode($login_response['response'], JSON_PRETTY_PRINT) . "\n";
    
    if ($login_response['status_code'] === 200 && isset($login_response['response']['data']['token'])) {
        echo "✅ Login Test Passed - Token Received\n";
        echo "Token: " . substr($login_response['response']['data']['token'], 0, 20) . "...\n";
    } else {
        echo "❌ Login Test Failed\n";
    }
}

echo "\nTest Summary\n";
echo "============\n";
echo "Total Tests Run: 7 + " . count($myanmar_numbers) . " phone number format tests\n";
?>
