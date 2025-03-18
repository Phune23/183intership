<?php
// filepath: c:\laragon\www\183intership\setup_pdf_library.php
// Script to download and setup TCPDF library

echo "<h2>Setting up PDF Library</h2>";

// Create libraries directory if it doesn't exist
if (!is_dir('libs/tcpdf')) {
    if (!mkdir('libs/tcpdf', 0777, true)) {
        die("<p style='color:red'>Failed to create directory for PDF library</p>");
    }
}

// Download TCPDF from GitHub
$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.5.0.zip';
$zip_file = 'libs/tcpdf/tcpdf.zip';

echo "<p>Downloading TCPDF library...</p>";

// Use cURL to download the file
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tcpdf_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
$data = curl_exec($ch);
curl_close($ch);

file_put_contents($zip_file, $data);

echo "<p>Extracting library files...</p>";

// Extract the zip file
$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo('libs/tcpdf/');
    $zip->close();
    echo "<p style='color:green'>✓ TCPDF library installed successfully!</p>";
    
    // Rename the extracted folder to a simpler name
    rename('libs/tcpdf/TCPDF-6.5.0', 'libs/tcpdf/tcpdf');
    unlink($zip_file); // Remove the zip file
} else {
    echo "<p style='color:red'>✗ Failed to extract TCPDF library</p>";
}

echo "<p><a href='index.php'>Return to home page</a></p>";
?>