<?php
include_once 'jwt.php';

class AuthMiddleware {
    private $jwt;
    
    public function __construct() {
        $this->jwt = new JWT();
    }
    
    public function authenticate() {
        $headers = apache_request_headers();
        
        if(!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(array(
                "status" => "error",
                "message" => "No authorization token provided"
            ));
            exit();
        }
        
        $auth_header = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $auth_header);
        
        $user_data = $this->jwt->validate_token($token);
        
        if(!$user_data) {
            http_response_code(401);
            echo json_encode(array(
                "status" => "error",
                "message" => "Invalid or expired token"
            ));
            exit();
        }
        
        return $user_data;
    }
}
?>
