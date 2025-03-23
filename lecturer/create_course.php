<?php
// filepath: lecturer/create_course.php
session_start();
require '../config/db.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../auth/login.php');
    exit;
}

$lecturer_id = 0;
$user_id = $_SESSION['user_id'];

// Xác định tên cột ID trong bảng users
$id_column_check = $conn->query("SHOW COLUMNS FROM users");
$id_column = 'user_id'; // Mặc định là user_id
while ($column = $id_column_check->fetch_assoc()) {
    if ($column['Field'] == 'id') {
        $id_column = 'id';
        break;
    }
}

// Debug ID column
error_log("Using ID column: " . $id_column . " with value: " . $user_id);

// Kiểm tra và tạo bảng lecturers nếu chưa tồn tại
$conn->query("CREATE TABLE IF NOT EXISTS lecturers (
    lecturer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(100),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Kiểm tra và tạo bảng internship_courses nếu chưa tồn tại
$conn->query("CREATE TABLE IF NOT EXISTS internship_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    lecturer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(lecturer_id)
)");

// Get lecturer information from users table using the correct ID column
$query = "SELECT username, first_name, last_name, email, department FROM users WHERE $id_column = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$stmt->close();

// Debug user data
if (!$user_data) {
    echo "<div style='color: red; padding: 10px; background: #ffeeee; border: 1px solid red; margin: 10px 0;'>";
    echo "User data not found with $id_column = $user_id. Check the database structure.";
    echo "</div>";
    
    // Display available users for debugging
    echo "<div style='padding: 10px; background: #eeeeff; border: 1px solid blue; margin: 10px 0;'>";
    echo "<h3>Available users in the database:</h3>";
    $users_check = $conn->query("SELECT * FROM users LIMIT 5");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    
    // Get column names
    $columns = $users_check->fetch_fields();
    foreach ($columns as $column) {
        echo "<th>{$column->name}</th>";
    }
    echo "</tr>";
    
    // Reset pointer
    $users_check->data_seek(0);
    
    // Output data rows
    while ($row = $users_check->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
}

// Get lecturer ID from database or create new lecturer record if not exists
$stmt = $conn->prepare("SELECT lecturer_id FROM lecturers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $lecturer = $result->fetch_assoc();
    $lecturer_id = $lecturer['lecturer_id'];
} else {
    // Lecturer doesn't exist in lecturers table, create a new record
    $first_name = $user_data['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? '';
    $username = $user_data['username'] ?? '';
    $email = $user_data['email'] ?? '';
    $department = $user_data['department'] ?? '';
    
    // Debug information before insert
    error_log("Creating new lecturer record for user_id: $user_id");
    error_log("User data: " . json_encode($user_data));
    
    $insert_stmt = $conn->prepare("INSERT INTO lecturers (user_id, first_name, last_name, email, department) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("issss", $user_id, $first_name, $last_name, $email, $department);
    
    if ($insert_stmt->execute()) {
        $lecturer_id = $conn->insert_id;
        error_log("New lecturer_id: $lecturer_id");
    } else {
        die("Error creating lecturer record: " . $insert_stmt->error);
    }
    $insert_stmt->close();
}
$stmt->close();

// Kiểm tra chắc chắn đã có lecturer_id hợp lệ
if ($lecturer_id <= 0) {
    die("Could not determine lecturer ID. Please contact system administrator.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $description = $_POST['description'] ?? '';
    
    // Check if course code already exists
    $stmt = $conn->prepare("SELECT course_id FROM internship_courses WHERE course_code = ?");
    $stmt->bind_param("s", $course_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Course code already exists. Please use a different code.";
    } else {
        // Insert new course
        $stmt = $conn->prepare("INSERT INTO internship_courses (course_code, course_name, description, lecturer_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $course_code, $course_name, $description, $lecturer_id);
        
        if ($stmt->execute()) {
            $success = "Course created successfully!";
        } else {
            $error = "Failed to create course: " . $stmt->error;
        }
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Internship Course</title>
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
        
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
            
        <h2>Create New Internship Course</h2>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="course_code">Course Code:</label>
                <input type="text" id="course_code" name="course_code" required>
            </div>
            
            <div class="form-group">
                <label for="course_name">Course Name:</label>
                <input type="text" id="course_name" name="course_name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"></textarea>
            </div>
            
            <button type="submit">Create Course</button>
        </form>
        
        <div class="debug-info">
            <p><strong>Lecturer Info:</strong> ID: <?php echo $lecturer_id; ?></p>
            <p><strong>User ID:</strong> <?php echo $user_id; ?> (using column: <?php echo $id_column; ?>)</p>
            <?php if ($user_data): ?>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></p>
            <?php else: ?>
                <p style="color: red;">User data not found!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>