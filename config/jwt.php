<?php
class JWT {
    private $secret_key = "your_secret_key_here"; // Change this to a secure secret key
    
    public function generate_token($user_data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user_data['id'],
            'phone_number' => $user_data['phone_number'],
            'name' => $user_data['name'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours expiry
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret_key, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    public function validate_token($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $valid_signature = hash_hmac('sha256', $header . "." . $payload, $this->secret_key, true);
        $valid_signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($valid_signature));
        
        if ($signature !== $valid_signature_encoded) {
            return false;
        }
        
        $payload_data = json_decode(base64_decode($payload), true);
        if ($payload_data['exp'] < time()) {
            return false;
        }
        
        return $payload_data;
    }
}
?>
