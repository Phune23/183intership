<?php /** @noinspection MultiAssignmentUsageInspection */

/**
 * SimpleXLSX class 0.8.15
 *
 * MS Excel 2007 workbooks reader
 *
 * @author Sergey Shuchkin <sergey.shuchkin@gmail.com>
 * @see https://github.com/shuchkin/simplexlsx
 * @license MIT
 */

/** Examples
 *
 * $xlsx = new SimpleXLSX('book.xlsx');
 * // or $xlsx = SimpleXLSX::parse('book.xlsx');
 *
 * print_r( $xlsx->rows() );
 * print_r( $xlsx->rowsEx() );
 *
 * $xlsx->toHTML();
 *
 */

class SimpleXLSX {
    // Main Class
    protected $sheetData = [];
    protected $sheetNames = [];
    protected $sheetFiles = [];
    protected $styles = [];
    protected $hyperlinks = [];
    protected $package = [
        'filename' => '',
        'mtime' => 0,
        'size' => 0,
        'comment' => '',
        'entries' => []
    ];
    protected $sharedstrings = [];
    protected $error = false;
    protected $debug = false;

    // XML schemas
    const SCHEMA_OFFICEDOCUMENT = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
    const SCHEMA_RELATIONSHIP = 'http://schemas.openxmlformats.org/package/2006/relationships';
    const SCHEMA_SHAREDSTRINGS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings';
    const SCHEMA_WORKSHEETRELATION = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';

    // Basic constructor - pass file path
    public function __construct($filename, $is_data = false, $debug = false) {
        $this->debug = $debug;
        $this->_unzip($filename, $is_data);
        $this->_parse();
    }

    // Static method to parse XLSX
    public static function parse($filename, $is_data = false, $debug = false) {
        return new self($filename, $is_data, $debug);
    }

    // Get all rows from worksheet
    public function rows($worksheet_index = 0) {
        if (isset($this->sheetData[$worksheet_index])) {
            $rows = [];
            foreach ($this->sheetData[$worksheet_index] as $r) {
                $rows[] = $r;
            }
            return $rows;
        }
        return [];
    }
    
    // Get sheet names
    public function sheetNames() {
        return $this->sheetNames;
    }
    
    // Get sheet count
    public function sheetsCount() {
        return count($this->sheetNames);
    }
    
    // Get last error message
    public function error() {
        return $this->error;
    }
    
    // Parse error (static)
    public static function parseError() {
        return self::$lastError;
    }
    
    // Internal: Extract the XML data from the XLSX
    protected function _unzip($filename, $is_data = false) {
        // Use ZipArchive for XLSX (Office Open XML)
        if ($is_data) {
            $this->package['filename'] = 'in_memory.xlsx';
            $this->package['mtime'] = time();
            $this->package['size'] = strlen($filename);
            
            $zip_data = $filename;
        } else {
            $this->package['filename'] = $filename;
            $this->package['mtime'] = filemtime($filename);
            $this->package['size'] = filesize($filename);
            
            $zip_data = file_get_contents($filename);
        }
        
        // Parse the XML using temporary files
        $tmpdir = sys_get_temp_dir() . '/xlsx_' . uniqid();
        mkdir($tmpdir);
        
        file_put_contents($tmpdir . '/xlsx_package.zip', $zip_data);
        
        $zip = new ZipArchive();
        if ($zip->open($tmpdir . '/xlsx_package.zip') === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                $this->package['entries'][$entry] = $i;
            }
            
            // First, get the workbook.xml
            if (isset($this->package['entries']['xl/workbook.xml'])) {
                $workbook_xml = $zip->getFromName('xl/workbook.xml');
                $workbookDOM = new DOMDocument();
                $workbookDOM->loadXML($workbook_xml);
                
                // Get shared strings
                if (isset($this->package['entries']['xl/sharedStrings.xml'])) {
                    $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
                    $sharedDOM = new DOMDocument();
                    $sharedDOM->loadXML($shared_xml);
                    
                    $strings = $sharedDOM->getElementsByTagName('t');
                    foreach ($strings as $string) {
                        $this->sharedstrings[] = $string->nodeValue;
                    }
                }
                
                // Get sheets
                $sheets = $workbookDOM->getElementsByTagName('sheet');
                foreach ($sheets as $sheet) {
                    $this->sheetNames[] = $sheet->getAttribute('name');
                    $sheet_id = $sheet->getAttribute('sheetId');
                    $this->sheetFiles[] = "xl/worksheets/sheet{$sheet_id}.xml";
                }
                
                // Parse worksheets
                foreach ($this->sheetFiles as $index => $sheetFile) {
                    if (isset($this->package['entries'][$sheetFile])) {
                        $sheet_xml = $zip->getFromName($sheetFile);
                        $this->_parseSheet($sheet_xml, $index);
                    }
                }
            }
            
            $zip->close();
        } else {
            $this->error = 'Failed to open XLSX file';
        }
        
        // Clean up
        unlink($tmpdir . '/xlsx_package.zip');
        rmdir($tmpdir);
    }
    
    // Parse individual worksheet
    protected function _parseSheet($sheet_xml, $sheet_index) {
        $sheetDOM = new DOMDocument();
        $sheetDOM->loadXML($sheet_xml);
        
        $rows = $sheetDOM->getElementsByTagName('row');
        $data = [];
        
        foreach ($rows as $row) {
            $rowIndex = (int) $row->getAttribute('r') - 1;
            $data[$rowIndex] = [];
            
            $cells = $row->getElementsByTagName('c');
            foreach ($cells as $cell) {
                $cellIndex = $this->_getColumnIndex($cell->getAttribute('r'));
                
                // Get value based on type
                $value = '';
                if ($cell->hasAttribute('t') && $cell->getAttribute('t') == 's') {
                    // Shared string
                    $valueElements = $cell->getElementsByTagName('v');
                    if ($valueElements->length > 0) {
                        $index = (int) $valueElements->item(0)->nodeValue;
                        $value = isset($this->sharedstrings[$index]) ? $this->sharedstrings[$index] : '';
                    }
                } else {
                    // Other types (numbers, etc.)
                    $valueElements = $cell->getElementsByTagName('v');
                    if ($valueElements->length > 0) {
                        $value = $valueElements->item(0)->nodeValue;
                    }
                }
                
                $data[$rowIndex][$cellIndex] = $value;
            }
            
            // Fill in missing cells
            ksort($data[$rowIndex]);
            $max = max(array_keys($data[$rowIndex]));
            for ($i = 0; $i <= $max; $i++) {
                if (!isset($data[$rowIndex][$i])) {
                    $data[$rowIndex][$i] = '';
                }
            }
        }
        
        // Make sure rows are sorted
        ksort($data);
        
        // Re-index for sequential rows
        $finalData = [];
        foreach ($data as $rowData) {
            $finalData[] = array_values($rowData);
        }
        
        $this->sheetData[$sheet_index] = $finalData;
    }
    
    // Convert column reference to index (A -> 0, B -> 1, AA -> 26, etc)
    protected function _getColumnIndex($cell) {
        $cell = preg_replace('/[^A-Z]/', '', strtoupper($cell));
        $index = 0;
        $length = strlen($cell);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($cell[$i]) - 64);
        }
        
        return $index - 1;
    }
    
    // Parse all required XML files
    protected function _parse() {
        if (!count($this->sheetData)) {
            $this->error = 'No worksheet data found';
            return false;
        }
        return true;
    }
    
    // Static error tracking
    protected static $lastError = '';
    public static function setError($error) {
        self::$lastError = $error;
    }
}
