<?php

class LoginController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
       
    }
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
    
    public function loginController($email, $password) {
        $email = $this->sanitizeInput($email);

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            exit(json_encode(array('success' => true, 'message' => 'Login successful')));
        } else {
            exit(json_encode(array('success' => false, 'message' => 'Invalid email or password')));
        }
    }
}
