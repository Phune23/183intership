<?php
// filepath: c:\laragon\www\183intership\create_companies_table.php
// Include database configuration file
require_once 'config/db.php';

echo "<h2>Adding Companies Table to Database</h2>";

// First, check if the companies table already exists
$result = $conn->query("SHOW TABLES LIKE 'companies'");

if ($result->num_rows > 0) {
    echo "<p style='color: orange'>The companies table already exists. No changes needed.</p>";
} else {
    // Create the companies table
    $sql_create_companies = "
    CREATE TABLE IF NOT EXISTS `companies` (
        `company_id` int NOT NULL AUTO_INCREMENT,
        `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `company_address` text COLLATE utf8mb4_unicode_ci,
        `company_contact` varchar(100) COLLATE utf8mb4_unicode_ci,
        `company_email` varchar(255) COLLATE utf8mb4_unicode_ci,
        `company_phone` varchar(20) COLLATE utf8mb4_unicode_ci,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`company_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($sql_create_companies)) {
        echo "<p style='color: green'>✓ Companies table created successfully!</p>";
    } else {
        echo "<p style='color: red'>✗ Error creating companies table: " . $conn->error . "</p>";
        exit;
    }
    
    // Now check if company_id column exists in internship_details table
    $result = $conn->query("SHOW COLUMNS FROM internship_details LIKE 'company_id'");
    
    if ($result->num_rows == 0) {
        // Add company_id column to internship_details table
        $sql_alter_internship_details = "
        ALTER TABLE internship_details 
        ADD COLUMN company_id INT NULL,
        ADD CONSTRAINT fk_internship_company 
        FOREIGN KEY (company_id) REFERENCES companies(company_id) 
        ON DELETE SET NULL;
        ";
        
        if ($conn->query($sql_alter_internship_details)) {
            echo "<p style='color: green'>✓ Added company_id column to internship_details table</p>";
            
            // Now migrate existing company names to the new companies table
            echo "<p>Migrating existing company data...</p>";
            
            // Get unique company names from internship_details
            $result = $conn->query("SELECT DISTINCT company_name FROM internship_details WHERE company_name IS NOT NULL AND company_name != ''");
            
            if ($result->num_rows > 0) {
                // Begin a transaction
                $conn->begin_transaction();
                
                try {
                    // Insert each unique company into the companies table
                    $insert_stmt = $conn->prepare("INSERT INTO companies (company_name) VALUES (?)");
                    $update_stmt = $conn->prepare("UPDATE internship_details SET company_id = ? WHERE company_name = ?");
                    
                    $companies_added = 0;
                    
                    while ($row = $result->fetch_assoc()) {
                        $company_name = $row['company_name'];
                        
                        // Insert the company
                        $insert_stmt->bind_param("s", $company_name);
                        $insert_stmt->execute();
                        $company_id = $conn->insert_id;
                        
                        // Update internship_details records
                        $update_stmt->bind_param("is", $company_id, $company_name);
                        $update_stmt->execute();
                        
                        $companies_added++;
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    echo "<p style='color: green'>✓ Migrated $companies_added companies to the new table</p>";
                    
                } catch (Exception $e) {
                    // Roll back in case of error
                    $conn->rollback();
                    echo "<p style='color: red'>✗ Error migrating company data: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color: blue'>No existing company data to migrate.</p>";
            }
            
        } else {
            echo "<p style='color: red'>✗ Error adding company_id column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange'>The company_id column already exists in internship_details table.</p>";
    }
}

// Update the db_test.php file to include companies table
$db_test_file = file_get_contents('db_test.php');
if (strpos($db_test_file, "'companies'") === false) {
    $db_test_file = str_replace(
        "$tables = array(\n        'users', \n        'lecturers', \n        'students', \n        'internship_courses', \n        'student_courses', \n        'internship_details'",
        "$tables = array(\n        'users', \n        'lecturers', \n        'students', \n        'internship_courses', \n        'student_courses', \n        'internship_details', \n        'companies'",
        $db_test_file
    );
    
    if (file_put_contents('db_test.php', $db_test_file)) {
        echo "<p style='color: green'>✓ Updated db_test.php to include companies table</p>";
    } else {
        echo "<p style='color: red'>✗ Could not update db_test.php</p>";
    }
}

echo "<h2>Companies Table Setup Complete!</h2>";

// Update the cv_generator.php file
echo "<p>Now updating the CV generator to work with the companies table...</p>";

// Fix the query in cv_generator.php
$cv_generator_path = 'lecturer/cv_generator.php';
if (file_exists($cv_generator_path)) {
    $cv_generator_content = file_get_contents($cv_generator_path);
    
    // Replace the problematic query with a fixed one that handles either format
    $old_query = "SELECT id.*, c.company_name
                FROM internship_details id
                LEFT JOIN companies c ON id.company_id = c.company_id";
    
    $new_query = "SELECT id.*, 
                CASE 
                    WHEN id.company_id IS NOT NULL THEN (SELECT company_name FROM companies WHERE company_id = id.company_id)
                    ELSE id.company_name
                END AS company_name";
    
    if (strpos($cv_generator_content, $old_query) !== false) {
        $cv_generator_content = str_replace($old_query, $new_query, $cv_generator_content);
        
        if (file_put_contents($cv_generator_path, $cv_generator_content)) {
            echo "<p style='color: green'>✓ Updated CV generator query</p>";
        } else {
            echo "<p style='color: red'>✗ Could not update CV generator file</p>";
        }
    } else {
        echo "<p style='color: orange'>CV generator query doesn't match expected pattern. Manual update may be required.</p>";
    }
}

echo "<p><a href='db_test.php'>Return to Database Test</a></p>";
?>