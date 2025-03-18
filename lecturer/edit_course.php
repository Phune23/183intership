<?php
session_start();
require '../config/db.php';
// Remove the functions.php requirement for now
// require_once '../includes/functions.php';

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

$success = '';
$error = '';
$course = null;

// Check if course ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_courses.php');
    exit;
}

$course_id = $_GET['id'];

// Verify the course belongs to this lecturer
$stmt = $conn->prepare("
    SELECT * FROM internship_courses 
    WHERE course_id = ? AND lecturer_id = ?
");
$stmt->bind_param("ii", $course_id, $lecturer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_courses.php');
    exit;
}

$course = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    
    if (empty($course_code) || empty($course_name)) {
        $error = "Course code and name are required";
    } else {
        // Check if course code is unique (except for this course)
        $stmt = $conn->prepare("
            SELECT course_id FROM internship_courses 
            WHERE course_code = ? AND course_id != ?
        ");
        $stmt->bind_param("si", $course_code, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Course code already exists";
        } else {
            // Update course
            $stmt = $conn->prepare("
                UPDATE internship_courses 
                SET course_code = ?, course_name = ?, description = ? 
                WHERE course_id = ? AND lecturer_id = ?
            ");
            $stmt->bind_param("sssii", $course_code, $course_name, $description, $course_id, $lecturer_id);
            
            if ($stmt->execute()) {
                $success = "Course updated successfully!";
                // Refresh course data
                $stmt = $conn->prepare("SELECT * FROM internship_courses WHERE course_id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $course = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Error updating course: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .navigation {
            margin-bottom: 20px;
        }
        
        .navigation a {
            display: inline-block;
            margin-right: 15px;
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
        
        <h2>Edit Course</h2>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($course): ?>
        <form method="post">
            <div class="form-group">
                <label for="course_code">Course Code:</label>
                <input type="text" id="course_code" name="course_code" required value="<?php echo htmlspecialchars($course['course_code']); ?>">
            </div>
            
            <div class="form-group">
                <label for="course_name">Course Name:</label>
                <input type="text" id="course_name" name="course_name" required value="<?php echo htmlspecialchars($course['course_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($course['description']); ?></textarea>
            </div>
            
            <button type="submit">Update Course</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>