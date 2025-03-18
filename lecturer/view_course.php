<?php
session_start();
require '../config/db.php';

// Check if user is logged in as lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../auth/login.php');
    exit;
}

// Initialize messages
$success = '';
$error = '';

// Get course ID from URL parameter
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verify the lecturer has access to this course
$stmt = $conn->prepare("
    SELECT c.* 
    FROM internship_courses c
    JOIN lecturers l ON c.lecturer_id = l.lecturer_id
    WHERE c.course_id = ? AND l.user_id = ?
");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If course doesn't exist or doesn't belong to this lecturer
if (!$course) {
    header('Location: dashboard.php');
    exit;
}

// Handle student deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_students') {
    if (isset($_POST['student_ids']) && is_array($_POST['student_ids']) && !empty($_POST['student_ids'])) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Prepare the delete statement
            $delete_stmt = $conn->prepare("
                DELETE FROM student_courses 
                WHERE student_id = ? AND course_id = ?
            ");
            
            $deleted_count = 0;
            
            foreach ($_POST['student_ids'] as $student_id) {
                $student_id = intval($student_id);
                $delete_stmt->bind_param("ii", $student_id, $course_id);
                $delete_stmt->execute();
                $deleted_count += $delete_stmt->affected_rows;
            }
            
            $conn->commit();
            
            if ($deleted_count > 0) {
                $success = $deleted_count . " student(s) removed from the course successfully.";
            } else {
                $error = "No students were removed. They may have already been deleted.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error removing students: " . $e->getMessage();
        }
    } else {
        $error = "No students selected for deletion.";
    }
}

// Get all students enrolled in this course
$stmt = $conn->prepare("
    SELECT s.student_id, s.student_code, s.first_name, s.last_name, s.email, s.class_code, 
           sc.status, IFNULL(id.status, 'not_submitted') AS internship_status
    FROM students s
    JOIN student_courses sc ON s.student_id = sc.student_id
    LEFT JOIN internship_details id ON s.student_id = id.student_id AND id.course_id = sc.course_id
    WHERE sc.course_id = ?
    ORDER BY s.last_name, s.first_name
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

$student_count = count($students);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($course['course_name']); ?> - Students</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .course-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-completed {
            background-color: #17a2b8;
        }
        .status-withdrawn {
            background-color: #dc3545;
        }
        .status-approved {
            background-color: #28a745;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status-rejected {
            background-color: #dc3545;
        }
        .status-not_submitted {
            background-color: #6c757d;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .checkbox-column {
            width: 30px;
        }
        .select-all-container {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="course-info">
            <h1><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h1>
            <p><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
        </div>
        
        <?php if ($success): ?>
            <div class="message message-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <h2>Enrolled Students (<?php echo $student_count; ?>)</h2>
        
        <?php if ($student_count > 0): ?>
            <form id="deleteStudentsForm" method="post" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete_students">
                
                <div class="select-all-container">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-danger">Remove Selected Students</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-column"></th>
                            <th>Student Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Course Status</th>
                            <th>Internship Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="checkbox-column">
                                    <input type="checkbox" name="student_ids[]" value="<?php echo $student['student_id']; ?>" class="student-checkbox">
                                </td>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_code'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($student['status']); ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($student['internship_status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $student['internship_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger" onclick="deleteStudent(<?php echo $student['student_id']; ?>)">Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php else: ?>
            <p>No students are enrolled in this course yet.</p>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="import_students.php?course_id=<?php echo $course_id; ?>" class="btn">Import Students</a>
        </div>
    </div>
    
    <script>
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            const selectAllChecked = document.getElementById('selectAll').checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllChecked;
            });
        }
        
        function confirmDelete() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Please select at least one student to remove.');
                return false;
            }
            
            return confirm('Are you sure you want to remove ' + checkboxes.length + ' student(s) from this course? This action cannot be undone.');
        }
        
        function deleteStudent(studentId) {
            if (confirm('Are you sure you want to remove this student from the course? This action cannot be undone.')) {
                // Uncheck all other checkboxes
                const checkboxes = document.querySelectorAll('.student-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = (checkbox.value == studentId);
                });
                
                // Submit the form
                document.getElementById('deleteStudentsForm').submit();
            }
        }
    </script>
</body>
</html>