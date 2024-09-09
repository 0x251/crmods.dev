<?php

class RegisterController {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public function registerController($email, $password) {
        $email = $this->sanitizeInput($email);

        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            exit(json_encode(array('success' => false, 'message' => 'Email already exists')));
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare('INSERT INTO users (email, password, api_key) VALUES (:email, :password, :api_key)');
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':api_key', bin2hex(random_bytes(18)));
        $stmt->execute();

        $_SESSION['user_id'] = $this->db->lastInsertId();
        $_SESSION['username'] = "UNKNOWN";
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;

        exit(json_encode(array('success' => true, 'message' => 'Registration successful')));
    }
}