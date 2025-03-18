<?php
class ExcelParser {
    private $rows = [];
    private $error = null;
    
    public function __construct($file) {
        // Determine if file is Excel
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            $this->parseCSV($file);
        } else {
            // For Excel files, convert to CSV first using COM (Windows only)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && class_exists('COM')) {
                try {
                    $this->convertExcelToCSV($file);
                } catch (Exception $e) {
                    $this->error = "Error converting Excel file: " . $e->getMessage();
                }
            } else {
                $this->error = "Excel parsing requires Windows with COM enabled or CSV format";
            }
        }
    }
    
    private function parseCSV($file) {
        try {
            if (($handle = fopen($file, 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                    $this->rows[] = $data;
                }
                fclose($handle);
            } else {
                $this->error = "Could not open the file";
            }
        } catch (Exception $e) {
            $this->error = "Error parsing CSV: " . $e->getMessage();
        }
    }
    
    private function convertExcelToCSV($file) {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            $tempCsv = $tempFile . '.csv';
            
            // Create COM objects for Excel
            $excel = new COM("Excel.Application");
            $excel->Visible = false;
            $excel->DisplayAlerts = false;
            
            // Open Excel file
            $workbook = $excel->Workbooks->Open(realpath($file));
            $worksheet = $workbook->Worksheets(1);
            
            // Save as CSV
            $workbook->SaveAs($tempCsv, 6); // 6 = CSV format
            $workbook->Close();
            $excel->Quit();
            
            // Free resources
            unset($worksheet);
            unset($workbook);
            unset($excel);
            
            // Parse the CSV
            $this->parseCSV($tempCsv);
            
            // Clean up
            @unlink($tempFile);
            @unlink($tempCsv);
            
        } catch (Exception $e) {
            $this->error = "Error converting Excel file: " . $e->getMessage();
        }
    }
    
    public function getRows() {
        return $this->rows;
    }
    
    public function hasError() {
        return !is_null($this->error);
    }
    
    public function getError() {
        return $this->error;
    }
}