<?php
session_start();
require '../config/db.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../auth/login.php');
    exit;
}

$success = '';
$error = '';
$students_imported = 0;
$students_failed = 0;

// Get available courses taught by this lecturer
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_code, c.course_name 
    FROM internship_courses c
    JOIN lecturers l ON c.lecturer_id = l.lecturer_id
    WHERE l.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Pre-select course if provided in URL
$selected_course = isset($_GET['course_id']) ? $_GET['course_id'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $course_id = $_POST['course_id'];
    
    // Validate course selection
    if (empty($course_id)) {
        $error = "Please select a course";
    } else {
        $file = $_FILES['csv_file'];
        
        // Check file extension
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($fileExt != 'csv') {
            $error = "Please upload a CSV file (.csv)";
        } else if ($file['size'] > 5000000) { // 5MB max
            $error = "File is too large. Maximum size is 5MB.";
        } else if ($file['error'] !== 0) {
            $error = "Error uploading file. Code: " . $file['error'];
        } else {
            // Open the CSV file - FIX: Add escape parameter
            if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
                // Skip header row - FIX: Add escape parameter
                fgetcsv($handle, 0, ',', '"', '\\');
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Process each row - FIX: Add escape parameter
                    while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                        if (empty($data[0])) continue; // Skip empty rows
                        
                        // Map the columns to match your file structure
                        // MaSV, HoLotSV, TenSV, MaLop, EMAIL, DIENTHOAI
                        $student_code = trim($data[0]); // MaSV
                        $last_name = trim($data[1] ?? ''); // HoLotSV
                        $first_name = trim($data[2] ?? ''); // TenSV
                        $class_code = trim($data[3] ?? ''); // MaLop
                        $email = trim($data[4] ?? ''); // EMAIL
                        $phone = trim($data[5] ?? ''); // DIENTHOAI
                        
                        // Skip row if required fields are missing
                        if (empty($student_code) || empty($last_name) || empty($first_name) || empty($email)) {
                            $students_failed++;
                            continue;
                        }
                        
                        // Check if student exists
                        $stmt = $conn->prepare("SELECT s.student_id, s.user_id 
                                              FROM students s 
                                              WHERE s.student_code = ?");
                        $stmt->bind_param("s", $student_code);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            // Update existing student
                            $student = $result->fetch_assoc();
                            $student_id = $student['student_id'];
                            
                            $stmt = $conn->prepare("
                                UPDATE students 
                                SET last_name = ?, first_name = ?, phone = ?, 
                                    email = ?, class_code = ?
                                WHERE student_id = ?
                            ");
                            $stmt->bind_param("sssssi", 
                                $last_name, $first_name, $phone, 
                                $email, $class_code, 
                                $student_id
                            );
                            $stmt->execute();
                        } else {
                            // Create new user and student
                            $password = password_hash($student_code, PASSWORD_DEFAULT);
                            
                            $stmt = $conn->prepare("
                                INSERT INTO users (username, password, role, is_first_login)
                                VALUES (?, ?, 'student', 1)
                            ");
                            $stmt->bind_param("ss", $student_code, $password);
                            $stmt->execute();
                            $user_id = $conn->insert_id;
                            
                            // Then create student profile
                            $stmt = $conn->prepare("
                                INSERT INTO students (user_id, student_code, last_name, first_name, phone, email, class_code)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->bind_param("issssss", 
                                $user_id, $student_code, $last_name, $first_name, 
                                $phone, $email, $class_code
                            );
                            $stmt->execute();
                            $student_id = $conn->insert_id;
                        }
                        
                        // Enroll student in course (if not already enrolled)
                        $stmt = $conn->prepare("
                            INSERT IGNORE INTO student_courses (student_id, course_id, status) 
                            VALUES (?, ?, 'active')
                        ");
                        $stmt->bind_param("ii", $student_id, $course_id);
                        $stmt->execute();
                        
                        $students_imported++;
                    }
                    
                    // Close file and commit transaction
                    fclose($handle);
                    $conn->commit();
                    
                    // Enhanced success message
                    if ($students_failed > 0) {
                        $success = "<strong>Import Summary:</strong><br>
                                   ✅ Successfully imported: $students_imported students<br>
                                   ❌ Failed to import: $students_failed students<br><br>
                                   Students have been enrolled in the selected course.";
                    } else {
                        $success = "<strong>Import Successful!</strong><br>
                                   ✅ Successfully imported all $students_imported students<br>
                                   Students have been enrolled in the selected course.";
                    }
                    
                } catch (Exception $e) {
                    // Error handling - rollback and report
                    if (isset($handle) && $handle) {
                        fclose($handle);
                    }
                    $conn->rollback();
                    $error = "<strong>Import Failed</strong><br>Error: " . $e->getMessage();
                }
            } else {
                $error = "<strong>File Error</strong><br>Failed to open the CSV file";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Import Students from CSV</title>
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
        
        select, input[type="file"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
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
        
        /* Enhanced message styling */
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            padding-left: 50px;
        }
        
        .message:before {
            font-family: Arial, sans-serif;
            font-size: 24px;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .success:before {
            content: "✓";
            color: #28a745;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .error:before {
            content: "!";
            color: #dc3545;
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
        
        .instructions {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
        }

        .download-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .step-by-step {
            counter-reset: step-counter;
            list-style-type: none;
            padding-left: 0;
        }
        
        .step-by-step li {
            position: relative;
            padding-left: 45px;
            margin-bottom: 15px;
        }
        
        .step-by-step li:before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            top: -2px;
            background-color: #2196F3;
            color: white;
            font-weight: bold;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navigation">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
        
        <h2>Import Students from CSV</h2>
        
        <div class="instructions">
            <p><strong>Instructions:</strong></p>
            <ul>
                <li>Upload a CSV file with the following columns:</li>
                <li><strong>MaSV, HoLotSV, TenSV, MaLop, EMAIL, DIENTHOAI</strong></li>
                <li>The first row should contain headers and will be skipped</li>
                <li>Students will be automatically enrolled in the selected course</li>
                <li>New students will have accounts created with their MaSV as username and password</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (empty($courses)): ?>
            <div class="message error">You don't have any courses yet. Please create a course first.</div>
            <a href="create_course.php" class="button">Create a new course</a>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="course_id">Select Course:</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                <?php echo ($selected_course == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo $course['course_code'] . ' - ' . $course['course_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="csv_file">CSV File:</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit">Import Students</button>
            </form>

            <div class="download-section">
                <h3>How to Save Your Excel File as CSV</h3>
                <ol class="step-by-step">
                    <li>Open your Excel file</li>
                    <li>Click "File" → "Save As"</li>
                    <li>Choose "CSV (Comma delimited) (*.csv)" from the dropdown</li>
                    <li>Click Save</li>
                    <li>Click "Yes" if you get a warning about features</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>