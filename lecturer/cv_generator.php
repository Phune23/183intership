<?php
// filepath: c:\laragon\www\183intership\lecturer\cv_generator.php
session_start();
require '../config/db.php';
require_once '../libs/tcpdf/TCPDF-6.4.4/TCPDF-6.4.4/tcpdf.php'; // Include TCPDF library

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

// Get all courses for this lecturer
$stmt = $conn->prepare("SELECT course_id, course_code, course_name FROM internship_courses WHERE lecturer_id = ?");
$stmt->bind_param("i", $lecturer['lecturer_id']);
$stmt->execute();
$courses = $stmt->get_result();
$stmt->close();

// Initialize variables
$students = [];
$selected_course = null;
$generated_cv = '';
$error = '';
$success = '';
$template = isset($_POST['template']) ? $_POST['template'] : 'modern';

// Define available templates
$templates = [
    'modern' => 'Modern (Blue)',
    'professional' => 'Professional (Gray)',
    'creative' => 'Creative (Green)',
    'minimalist' => 'Minimalist (White)'
];

// Handle course selection for student list
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    
    // Get the course details
    $stmt = $conn->prepare("SELECT * FROM internship_courses WHERE course_id = ? AND lecturer_id = ?");
    $stmt->bind_param("ii", $course_id, $lecturer['lecturer_id']);
    $stmt->execute();
    $selected_course = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($selected_course) {
        // Get students in this course
        $stmt = $conn->prepare("
            SELECT s.* 
            FROM students s
            JOIN student_courses sc ON s.student_id = sc.student_id
            WHERE sc.course_id = ?
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $students_result = $stmt->get_result();
        
        while ($student = $students_result->fetch_assoc()) {
            $students[] = $student;
        }
        $stmt->close();
    }
}

// Handle PDF generation and download
if (isset($_POST['generate_pdf']) && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $template = $_POST['template'];
    
    // Get student details
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($student) {
        // Get internship details if available
        $stmt = $conn->prepare("
            SELECT id.*, 
            CASE 
                WHEN id.company_id IS NOT NULL THEN (SELECT company_name FROM companies WHERE company_id = id.company_id)
                ELSE id.company_name
            END AS company_name
            FROM internship_details id
            WHERE id.student_id = ?
            ORDER BY id.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $internship = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Generate PDF
        generatePDF($student, $internship, $template);
        exit;
    }
}

// Handle CV generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_cv'])) {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $template = isset($_POST['template']) ? $_POST['template'] : 'modern';
    
    if ($student_id > 0) {
        // Get student details
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($student) {
            // Get internship details if available
            $stmt = $conn->prepare("
                SELECT id.*, 
                CASE 
                    WHEN id.company_id IS NOT NULL THEN (SELECT company_name FROM companies WHERE company_id = id.company_id)
                    ELSE id.company_name
                END AS company_name
                FROM internship_details id
                WHERE id.student_id = ?
                ORDER BY id.created_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $internship = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            try {
                // Generate CV content
                $generated_cv = generateCVWithAI($student, $internship ?? null);
                $success = "CV generated successfully for " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                
            } catch (Exception $e) {
                $error = "Error generating CV: " . $e->getMessage();
            }
        } else {
            $error = "Student not found";
        }
    } else {
        $error = "Please select a student";
    }
}

// Function to generate CV with AI (Template-based)
function generateCVWithAI($student, $internship = null) {
    // In a real implementation, you would call an AI API here
    // This is a simplified example that generates a template
    
    $fullName = $student['first_name'] . ' ' . $student['last_name'];
    $email = $student['email'];
    $phone = $student['phone'] ?? 'N/A';
    $studentCode = $student['student_code'] ?? 'N/A';
    $classCode = $student['class_code'] ?? 'N/A';
    
    // Handle company name for either database structure
    $internshipCompany = 'N/A';
    if ($internship) {
        if (isset($internship['company_name'])) {
            $internshipCompany = $internship['company_name'];
        } elseif (isset($internship['company'])) {
            $internshipCompany = $internship['company'];
        }
    }
    
    $position = $internship ? ($internship['position'] ?? 'Student') : 'Student';
    $skills = $internship ? ($internship['skills_required'] ?? 'Computer skills, Communication skills') : 'Computer skills, Communication skills';
    
    // Build CV template
    $cv = <<<EOT
# CURRICULUM VITAE

## Personal Information
- **Full Name:** $fullName
- **Email:** $email
- **Phone:** $phone
- **Student ID:** $studentCode
- **Class:** $classCode

## Education
- Bachelor's Degree in Computer Science (In progress)
- University/College: [University Name]

## Skills
- $skills
- Microsoft Office Suite
- Problem-solving skills
- Team collaboration

## Experience
- **Position:** $position
- **Company:** $internshipCompany
- **Period:** [Start Date] - [End Date]
- **Responsibilities:**
  - Assisted in software development projects
  - Contributed to team meetings and brainstorming sessions
  - Completed tasks as assigned by supervisor

## Languages
- Vietnamese (Native)
- English (Professional working proficiency)

## Projects
- [Project Title]: Brief description of project and your contribution
- [Project Title]: Brief description of project and your contribution

## Certifications
- [List any relevant certifications]

## References
- Available upon request
EOT;

    return $cv;
}

// Function to generate and download PDF
function generatePDF($student, $internship = null, $template = 'modern') {
    $fullName = $student['first_name'] . ' ' . $student['last_name'];
    $email = $student['email'];
    $phone = $student['phone'] ?? 'N/A';
    $studentCode = $student['student_code'] ?? 'N/A';
    $classCode = $student['class_code'] ?? 'N/A';
    
    // Handle company name for either database structure
    $internshipCompany = 'N/A';
    if ($internship) {
        if (isset($internship['company_name'])) {
            $internshipCompany = $internship['company_name'];
        } elseif (isset($internship['company'])) {
            $internshipCompany = $internship['company'];
        }
    }
    
    $position = $internship ? ($internship['position'] ?? 'Student') : 'Student';
    $skills = $internship ? ($internship['skills_required'] ?? 'Computer skills, Communication skills') : 'Computer skills, Communication skills';
    
    // Split skills into array for better formatting
    $skillsArray = array_map('trim', explode(',', $skills));
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('CV Generator');
    $pdf->SetAuthor($fullName);
    $pdf->SetTitle('CV - ' . $fullName);
    $pdf->SetSubject('Curriculum Vitae');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Create CSS styles based on template selection
    $styles = '';
    switch($template) {
        case 'modern':
            $styles = '
                h1 { color: #2c3e50; font-size: 24pt; }
                h2 { color: #3498db; border-bottom: 1px solid #3498db; font-size: 14pt; padding-bottom: 5px; margin-top: 15px; }
                .section { margin-bottom: 10px; }
                .contact-info { color: #7f8c8d; font-size: 10pt; }
                .skill-item { background-color: #edf2f7; padding: 3px 7px; border-radius: 3px; display: inline-block; margin: 2px; }
                .section-title { font-weight: bold; }
            ';
            break;
        case 'professional':
            $styles = '
                h1 { color: #333333; font-size: 24pt; border-bottom: 2px solid #666666; }
                h2 { color: #666666; font-size: 14pt; border-bottom: 1px solid #cccccc; padding-bottom: 5px; margin-top: 15px; }
                .contact-info { color: #333333; font-size: 10pt; }
                .skill-item { background-color: #f5f5f5; padding: 3px 7px; border: 1px solid #dddddd; border-radius: 3px; display: inline-block; margin: 2px; }
                .section-title { font-weight: bold; }
            ';
            break;
        case 'creative':
            $styles = '
                h1 { color: #27ae60; font-size: 24pt; }
                h2 { color: #16a085; border-bottom: 2px dotted #16a085; font-size: 14pt; padding-bottom: 5px; margin-top: 15px; }
                .contact-info { color: #2c3e50; font-size: 10pt; }
                .skill-item { background-color: #e8f8f5; padding: 3px 7px; border-radius: 10px; display: inline-block; margin: 2px; }
                .section-title { font-weight: bold; color: #16a085; }
            ';
            break;
        case 'minimalist':
            $styles = '
                h1 { color: #000000; font-size: 24pt; }
                h2 { color: #000000; font-size: 14pt; letter-spacing: 2px; border-bottom: 1px solid #000000; padding-bottom: 5px; margin-top: 15px; }
                .contact-info { color: #333333; font-size: 10pt; }
                .skill-item { padding: 2px 0; display: inline-block; margin-right: 15px; }
                .section-title { font-weight: bold; }
            ';
            break;
    }
    
    // Generate HTML content for PDF
    $html = '
    <style>
        ' . $styles . '
        body { font-family: Helvetica, Arial, sans-serif; }
    </style>
    
    <h1>' . $fullName . '</h1>
    
    <div class="contact-info">
        ' . $email . ' | ' . $phone . ' | Student ID: ' . $studentCode . ' | Class: ' . $classCode . '
    </div>
    
    <h2>EDUCATION</h2>
    <div class="section">
        <span class="section-title">Bachelor\'s Degree in Computer Science</span> (In progress)<br>
        University/College: [University Name]
    </div>
    
    <h2>SKILLS</h2>
    <div class="section">';
    
    // Add skills
    foreach($skillsArray as $skill) {
        $html .= '<span class="skill-item">' . trim($skill) . '</span> ';
    }
    
    $html .= '
        <span class="skill-item">Microsoft Office Suite</span>
        <span class="skill-item">Problem-solving</span>
        <span class="skill-item">Team collaboration</span>
    </div>
    
    <h2>EXPERIENCE</h2>
    <div class="section">
        <span class="section-title">' . $position . '</span><br>
        ' . $internshipCompany . '<br>
        [Start Date] - [End Date]<br>
        <ul>
            <li>Assisted in software development projects</li>
            <li>Contributed to team meetings and brainstorming sessions</li>
            <li>Completed tasks as assigned by supervisor</li>
        </ul>
    </div>
    
    <h2>LANGUAGES</h2>
    <div class="section">
        Vietnamese (Native)<br>
        English (Professional working proficiency)
    </div>
    
    <h2>PROJECTS</h2>
    <div class="section">
        <span class="section-title">[Project Title]:</span> Brief description of project and your contribution<br>
        <span class="section-title">[Project Title]:</span> Brief description of project and your contribution
    </div>
    
    <h2>CERTIFICATIONS</h2>
    <div class="section">
        [List any relevant certifications]
    </div>
    
    <h2>REFERENCES</h2>
    <div class="section">
        Available upon request
    </div>
    ';
    
    // Print content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('CV_' . str_replace(' ', '_', $fullName) . '.pdf', 'D');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CV Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .container {
            width: 90%;
            margin: 20px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            flex-wrap: wrap;
        }
        
        nav {
            flex: 1;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        nav ul li {
            margin: 5px 10px 5px 0;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            padding: 5px;
            white-space: nowrap;
            display: block;
        }
        
        nav ul li a:hover {
            background-color: #444;
            border-radius: 3px;
        }
        
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .sidebar {
            flex: 1;
            min-width: 300px;
        }
        
        .main-content {
            flex: 3;
            min-width: 500px;
        }
        
        .btn {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #45a049;
        }
        
        select, input {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .student-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .student-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .student-item:hover {
            background-color: #f5f5f5;
        }
        
        .student-item.selected {
            background-color: #e3f2fd;
        }
        
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
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
        
        .cv-preview {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            background-color: #fff;
            min-height: 400px;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-primary {
            background-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
        
        .section-title {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .template-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .template-option {
            border: 2px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            width: 100px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .template-option:hover {
            border-color: #aaa;
        }
        
        .template-option.selected {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .template-option.modern {
            border-left: 5px solid #3498db;
        }
        
        .template-option.professional {
            border-left: 5px solid #666666;
        }
        
        .template-option.creative {
            border-left: 5px solid #27ae60;
        }
        
        .template-option.minimalist {
            border-left: 5px solid #000000;
        }
        
        /* Improved responsive adjustments */
        @media (max-width: 900px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px;
            }
            
            nav {
                margin-top: 10px;
                width: 100%;
            }
            
            nav ul {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            
            nav ul li {
                margin: 5px 10px 5px 0;
            }
        }
        
        @media (max-width: 576px) {
            nav ul {
                flex-direction: column;
            }
            
            nav ul li {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <h2>CV Generator</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create_course.php">Create Course</a></li>
                <li><a href="import_students.php">Import Students</a></li>
                <li><a href="cv_generator.php">CV Generator</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div class="sidebar">
            <div class="card">
                <h3 class="section-title">Student Selection</h3>
                
                <form action="" method="get">
                    <label for="course_id">Select Course:</label>
                    <select name="course_id" id="course_id" onchange="this.form.submit()">
                        <option value="">-- Select a course --</option>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
                
                <?php if ($selected_course): ?>
                    <form action="" method="post" id="generateForm">
                        <label>Select Student:</label>
                        <div class="student-list">
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <div class="student-item" onclick="selectStudent(<?php echo $student['student_id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                        <?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name'] . ' (' . $student['student_code'] . ')'); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="student-item">No students found in this course</div>
                            <?php endif; ?>
                        </div>
                        
                        <label>Select Template:</label>
                        <div class="template-selector">
                            <?php foreach($templates as $key => $name): ?>
                                <div class="template-option <?php echo $key; ?> <?php echo ($template == $key) ? 'selected' : ''; ?>" 
                                     onclick="selectTemplate('<?php echo $key; ?>', '<?php echo $name; ?>')">
                                    <?php echo $name; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Add preview templates button -->
                            <a href="preview_templates.php<?php echo isset($_POST['template']) ? '?template='.$_POST['template'] : ''; ?>" 
                               class="btn btn-info" style="margin-left: 15px;">
                                Preview Templates
                            </a>
                        </div>
                        
                        <input type="hidden" name="student_id" id="student_id" value="">
                        <input type="hidden" name="template" id="template" value="<?php echo $template; ?>">
                        <input type="hidden" name="generate_cv" value="1">
                        <button type="submit" class="btn" id="generateBtn" disabled>Generate CV</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="main-content">
            <?php if ($error): ?>
                <div class="message message-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message message-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h3 class="section-title">CV Preview</h3>
                <div id="selected-student"></div>
                <div id="selected-template-display"></div>
                
                <div class="cv-preview">
                    <?php echo nl2br(htmlspecialchars($generated_cv)); ?>
                </div>
                
                <?php if ($generated_cv): ?>
                    <div class="actions">
                        <button class="btn" onclick="copyToClipboard()">Copy to Clipboard</button>
                        <button class="btn btn-secondary" onclick="downloadAsTextFile()">Download as Text</button>
                        <button class="btn btn-primary" onclick="downloadAsPDF()">Download as PDF</button>
                        <button class="btn" onclick="printCV()">Print</button>
                    </div>
                    
                    <form id="pdfForm" action="" method="post" style="display: none;">
                        <input type="hidden" name="student_id" value="<?php echo isset($_POST['student_id']) ? $_POST['student_id'] : ''; ?>">
                        <input type="hidden" name="template" value="<?php echo $template; ?>">
                        <input type="hidden" name="generate_pdf" value="1">
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Select student and update UI
        function selectStudent(studentId, studentName) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('selected-student').innerHTML = '<p><strong>Selected student:</strong> ' + studentName + '</p>';
            document.getElementById('generateBtn').disabled = false;
            
            // Update visual selection
            const items = document.querySelectorAll('.student-item');
            items.forEach(item => item.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Update PDF form
            const pdfForm = document.getElementById('pdfForm');
            if (pdfForm) {
                pdfForm.querySelector('input[name="student_id"]').value = studentId;
            }
        }
        
        // Select template
        function selectTemplate(templateKey, templateName) {
            document.getElementById('template').value = templateKey;
            document.getElementById('selected-template-display').innerHTML = '<p><strong>Selected template:</strong> ' + templateName + '</p>';
            
            // Update visual selection
            const templates = document.querySelectorAll('.template-option');
            templates.forEach(item => item.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
        }
        
        // Copy CV content to clipboard
        function copyToClipboard() {
            const cvContent = document.querySelector('.cv-preview').innerText;
            navigator.clipboard.writeText(cvContent).then(() => {
                alert('CV copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy. Please select the text and copy manually.');
            });
        }
        
        // Download CV as text file
        function downloadAsTextFile() {
            const cvContent = document.querySelector('.cv-preview').innerText;
            const blob = new Blob([cvContent], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'cv.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        // Download CV as PDF
        function downloadAsPDF() {
            document.getElementById('pdfForm').submit();
        }
        
        // Print the CV
        function printCV() {
            const cvContent = document.querySelector('.cv-preview').innerHTML;
            const w = window.open();
            w.document.write('<html><head><title>CV</title>');
            w.document.write('<style>body { font-family: Arial, sans-serif; line-height: 1.6; }</style>');
            w.document.write('</head><body>');
            w.document.write(cvContent);
            w.document.write('</body></html>');
            w.document.close();
            w.print();
        }
    </script>
</body>
</html>