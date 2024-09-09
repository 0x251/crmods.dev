<?php

class SettingsController {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function changeUsername($newUsername) {
        $newUsername = filter_var($newUsername, FILTER_SANITIZE_STRING);

        if (strlen($newUsername) > 18) {
            $this->sendResponse(false, 'Username must be 18 characters or less');
        }

        if ($this->usernameExists($newUsername)) {
            $this->sendResponse(false, 'Username is already taken');
        }

        $user = $this->getUser($_SESSION['user_id']);

        if ($user['changes'] <= 0 && strtotime($user['username_changed']) > time()) {
            $this->sendResponse(false, 'You can only change your username 2 times every 30 days please wait.');
        }

        if (strtotime($user['username_changed']) < time()) {
            $this->resetUsernameChangeLimit($_SESSION['user_id']);
        }

        $this->updateUsername($newUsername, $_SESSION['user_id'], $user['changes']);

        $_SESSION['username'] = $newUsername;

        $this->sendResponse(true, 'Username updated successfully');
    }

    private function sendResponse($success, $message) {
        exit(json_encode(array('success' => $success, 'message' => $message)));
    }

    private function usernameExists($username) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    private function getUser($userId) {
        $stmt = $this->pdo->prepare('SELECT changes, username_changed FROM users WHERE id = :id');
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function updateUsername($newUsername, $userId, $changes) {
        $stmt = $this->pdo->prepare('UPDATE users SET username = :username, changes = changes - 1 WHERE id = :id');
        $stmt->bindParam(':username', $newUsername);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        if ($changes - 1 == 0) {
            $stmt = $this->pdo->prepare('UPDATE users SET username_changed = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = :id');
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        }

        $stmt = $this->pdo->prepare('UPDATE `mod-list` SET author = :newUsername WHERE user_id = :userId');
        $stmt->bindParam(':newUsername', $newUsername);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    }

    private function resetUsernameChangeLimit($userId) {
        $stmt = $this->pdo->prepare('UPDATE users SET username_changed = DATE_ADD(NOW(), INTERVAL 30 DAY), changes = 2 WHERE id = :id');
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }

    public function usernameExistsPull($username) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            exit(json_encode(array('success' => false, 'message' => 'Username is already taken')));
        } else {
            exit(json_encode(array('success' => true, 'message' => 'Username is available')));
        }
    }

    public function generateApiKey() {
        $apiKey = bin2hex(random_bytes(18));
        $stmt = $this->pdo->prepare('UPDATE users SET api_key = :api_key WHERE id = :id');
        $stmt->bindParam(':api_key', $apiKey);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        exit(json_encode(array('success' => true, 'apiKey' => $apiKey)));
    }

    public function getApiKey() {
        $stmt = $this->pdo->prepare('SELECT api_key FROM users WHERE id = :id');
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $apiKey = $stmt->fetchColumn();
        exit(json_encode(array('success' => true, 'apiKey' => $apiKey)));
    }
}