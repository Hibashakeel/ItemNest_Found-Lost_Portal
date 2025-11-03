<?php
require_once "Database.php";

class User extends Database {

    // --- Register user ---
    public function register($name, $email, $password) {
        $conn = $this->connect();

        // hash password for normal users
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $name, $email, $hashed);
        $res = $stmt->execute();
        $stmt->close();

        return $res;
    }

    // --- Login user/admin ---
    public function login($email, $password) {
        $conn = $this->connect();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // 🚫 Prevent blocked users from logging in
            if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
                return ['error' => 'blocked'];
            }

            // ✅ Case 1: Plain password (for admin)
            if ($user['password'] === $password) {
                return $user;
            }

            // ✅ Case 2: Hashed password (for registered users)
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }

        return false;
    }
}
?>
