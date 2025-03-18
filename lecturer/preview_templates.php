<?php
session_start();
require_once '../config/db.php';
require_once '../libs/tcpdf/TCPDF-6.4.4/TCPDF-6.4.4/tcpdf.php';

// Check if user is logged in and is a lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: ../index.php');
    exit;
}

// Define available templates
$templates = [
    'modern' => 'Modern (Blue)',
    'professional' => 'Professional (Gray)',
    'creative' => 'Creative (Green)',
    'minimalist' => 'Minimalist (White)'
];

// Set default template or get from request
$template = isset($_GET['template']) ? $_GET['template'] : 'modern';
if (!array_key_exists($template, $templates)) {
    $template = 'modern';
}

// Sample student data for preview
$sample_student = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'phone' => '+84 123 456 789',
    'student_code' => 'SV12345',
    'class_code' => 'IT123'
];

// Sample internship data
$sample_internship = [
    'company_name' => 'Tech Solutions Ltd.',
    'position' => 'Software Development Intern',
    'skills_required' => 'PHP, JavaScript, HTML/CSS, SQL'
];

// Generate sample CV content
$cv_content = generateCVPreview($sample_student, $sample_internship, $template);

// Function to generate HTML for CV preview based on template
function generateCVPreview($student, $internship, $template) {
    $fullName = $student['first_name'] . ' ' . $student['last_name'];
    $email = $student['email'];
    $phone = $student['phone'];
    $studentCode = $student['student_code'];
    $classCode = $student['class_code'];
    $internshipCompany = $internship['company_name'];
    $position = $internship['position'];
    $skills = $internship['skills_required'];
    
    // Split skills into array for better formatting
    $skillsArray = array_map('trim', explode(',', $skills));
    
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
    
    // Generate HTML content for preview
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
        University/College: Example University
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
        January 2025 - March 2025<br>
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
        <span class="section-title">E-commerce Website:</span> Developed a responsive online store using PHP and MySQL<br>
        <span class="section-title">Mobile Application:</span> Created a schedule management app for students
    </div>
    
    <h2>CERTIFICATIONS</h2>
    <div class="section">
        Web Development Fundamentals Certificate<br>
        Database Design and SQL Certificate
    </div>
    
    <h2>REFERENCES</h2>
    <div class="section">
        Available upon request
    </div>
    ';
    
    return $html;
}

// Generate PDF function (if we want to add a PDF download option)
function generatePDFPreview($student, $internship, $template) {
    // PDF generation logic similar to the main file
    // This would be used if we add a "Download PDF" option to the preview
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CV Template Preview</title>
    <style>
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
            text-decoration: none;
            color: #333;
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
        
        .cv-preview {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            background-color: #fff;
        }
        
        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn-back {
            background-color: #6c757d;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <header>
        <h2>CV Template Preview</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cv_generator.php">CV Generator</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div class="sidebar">
            <div class="card">
                <h3 class="section-title">Template Selection</h3>
                <p>Select a template to preview how your CV will look with different designs.</p>
                
                <div class="template-selector">
                    <?php foreach($templates as $key => $name): ?>
                        <a href="?template=<?php echo $key; ?>" class="template-option <?php echo $key; ?> <?php echo ($template == $key) ? 'selected' : ''; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="actions">
                    <a href="cv_generator.php" class="btn btn-back">Back to CV Generator</a>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="card">
                <h3 class="section-title">Preview: <?php echo $templates[$template]; ?> Template</h3>
                <p>This is a sample CV showing how the selected template will look with student data.</p>
                
                <div class="cv-preview">
                    <?php echo $cv_content; ?>
                </div>
                
                <div class="actions">
                    <button class="btn" onclick="printPreview()">Print Preview</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Print the CV preview
        function printPreview() {
            const cvContent = document.querySelector('.cv-preview').innerHTML;
            const w = window.open();
            w.document.write('<html><head><title>CV Template Preview</title>');
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