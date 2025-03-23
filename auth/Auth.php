<?php
class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Login user
     * 
     * @param string $login_username
     * @param string $login_password
     * @return array|bool User data on success, false on failure
     */
    public function login($login_username, $login_password) {
        // Sử dụng tên biến rõ ràng để tránh xung đột
        error_log("Login attempt: username=$login_username");
        
        try {
            // Check if connection exists and is valid
            if (!$this->conn || $this->conn->connect_error) {
                error_log("Database connection error during login: " . ($this->conn ? $this->conn->connect_error : "No connection"));
                return false;
            }
            
            // Find user by username
            $query = "SELECT * FROM users WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Prepare statement failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("s", $login_username); // Sử dụng biến login_username
            $result = $stmt->execute();
            
            // Kiểm tra lỗi thực thi truy vấn
            if (!$result) {
                error_log("Execute statement failed: " . $stmt->error);
                return false;
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("User not found: $login_username");
                return false;
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($login_password, $user['password'])) {
                error_log("Password verified successfully for: $login_username");
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return $user;
            } else {
                error_log("Invalid password for user: $login_username");
                return false;
            }
        } catch (Exception $e) {
            error_log("Exception in Auth::login: " . $e->getMessage());
            return false;
        }
    }
    
    public function register($username, $password, $role, $userData = []) {
        // Check if username already exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Hash password before storing
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $isFirstLogin = ($role === 'student') ? 1 : 0;
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Insert into users table
            $stmt = $this->conn->prepare("INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $hashedPassword, $role, $isFirstLogin);
            $stmt->execute();
            
            $userId = $this->conn->insert_id;
            
            // Insert additional data based on role
            if ($role === 'lecturer' && !empty($userData)) {
                $stmt = $this->conn->prepare("INSERT INTO lecturers (user_id, first_name, last_name, email, department) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $userId, $userData['first_name'], $userData['last_name'], $userData['email'], $userData['department']);
                $stmt->execute();
            } else if ($role === 'student' && !empty($userData)) {
                $stmt = $this->conn->prepare("INSERT INTO students (user_id, student_code, first_name, last_name, phone, email, major, dob, class_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssss", $userId, $userData['student_code'], $userData['first_name'], $userData['last_name'], $userData['phone'], $userData['email'], $userData['major'], $userData['dob'], $userData['class_code']);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Verify old password
        $stmt = $this->conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($oldPassword, $user['password'])) {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password and first login flag
                $stmt = $this->conn->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE user_id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                
                if ($stmt->execute()) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
    }
}
?>