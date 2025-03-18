<?php
class Middleware {
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /183intership/index.php');
            exit;
        }
    }
    
    public static function requireLecturer() {
        self::requireLogin();
        
        if ($_SESSION['role'] !== 'lecturer') {
            header('Location: /183intership/403.php');
            exit;
        }
    }
    
    public static function requireStudent() {
        self::requireLogin();
        
        if ($_SESSION['role'] !== 'student') {
            header('Location: /183intership/403.php');
            exit;
        }
    }
    
    public static function requirePasswordChange() {
        self::requireLogin();
        
        if ($_SESSION['is_first_login'] && $_SESSION['role'] === 'student' && 
            basename($_SERVER['PHP_SELF']) !== 'change_password.php') {
            header('Location: /183intership/student/change_password.php');
            exit;
        }
    }
}
?>