<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../auth/login.php');
    exit;
}

// Get lecturer ID
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$lecturer = $result->fetch_assoc();
$lecturer_id = $lecturer['lecturer_id'];

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Course ID is required";
    header('Location: dashboard.php'); // Changed from manage_courses.php
    exit;
}

$course_id = $_GET['id'];

// Verify the course belongs to this lecturer
$stmt = $conn->prepare("
    SELECT course_id, course_name FROM internship_courses 
    WHERE course_id = ? AND lecturer_id = ?
");
$stmt->bind_param("ii", $course_id, $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "You don't have permission to delete this course";
    header('Location: dashboard.php'); // Changed from manage_courses.php
    exit;
}

$course = $result->fetch_assoc();

// Check if confirmation is provided
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete course enrollments
        $stmt = $conn->prepare("DELETE FROM student_courses WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        
        // Delete internship details related to this course
        $stmt = $conn->prepare("DELETE FROM internship_details WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        
        // Delete the course
        $stmt = $conn->prepare("DELETE FROM internship_courses WHERE course_id = ? AND lecturer_id = ?");
        $stmt->bind_param("ii", $course_id, $lecturer_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $_SESSION['success_message'] = "Course '{$course['course_name']}' has been deleted successfully";
        } else {
            throw new Exception("Failed to delete course");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting course: " . $e->getMessage();
    }
    
    header('Location: dashboard.php'); // Changed from manage_courses.php
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Course</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        h2 {
            color: #333;
            margin-top: 0;
        }
        
        .warning {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .buttons {
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin: 0 5px;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Delete Course</h2>
        
        <div class="warning">
            <p><strong>Warning:</strong> You are about to delete the course "<?php echo htmlspecialchars($course['course_name']); ?>".</p>
            <p>This will permanently remove all course data, student enrollments, and internship details related to this course.</p>
            <p>This action cannot be undone.</p>
        </div>
        
        <div class="buttons">
            <a href="delete_course.php?id=<?php echo $course_id; ?>&confirm=yes" class="btn btn-danger">Yes, Delete Course</a>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a> <!-- Changed from manage_courses.php -->
        </div>
    </div>
</body>
</html>