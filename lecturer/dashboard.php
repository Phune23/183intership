<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../index.php');
    exit;
}

// Get lecturer details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$lecturer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get courses created by this lecturer
$stmt = $conn->prepare("SELECT * FROM internship_courses WHERE lecturer_id = ?");
$stmt->bind_param("i", $lecturer['lecturer_id']);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lecturer Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .container {
            width: 80%;
            margin: 20px auto;
        }
        
        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        nav ul {
            list-style: none;
            display: flex;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
        }
        
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .btn-edit {
            background-color: #2196f3;
        }
        
        .btn-delete {
            background-color: #f44336;
        }

        .message-card {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            padding-left: 50px;
        }
        
        .message-card:before {
            font-family: Arial, sans-serif;
            font-size: 24px;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .message-success:before {
            content: "✓";
            color: #28a745;
        }
        
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .message-error:before {
            content: "!";
            color: #dc3545;
        }
    </style>
</head>
<body>
    <header>
        <h2>Lecturer Dashboard</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_course.php">Create Course</a></li>
                <li><a href="import_students.php">Import Students</a></li>
                <li><a href="import_students_csv.php">Import Students (CSV)</a></li>
                <li><a href="cv_generator.php">CV Generator</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="message-card message-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="message-card message-error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card">
            <h3>Welcome, <?php echo htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']); ?></h3>
            <p>Email: <?php echo htmlspecialchars($lecturer['email']); ?></p>
            <p>Department: <?php echo htmlspecialchars($lecturer['department']); ?></p>
        </div>
        
        <div class="card">
            <h3>Your Internship Courses</h3>
            <?php if ($courses->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['description']); ?></td>
                            <td>
                                <a href="view_course.php?id=<?php echo $course['course_id']; ?>" class="btn">View</a>
                                <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-edit">Edit</a>
                                <a href="delete_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>No courses found. <a href="create_course.php" class="btn">Create a Course</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>