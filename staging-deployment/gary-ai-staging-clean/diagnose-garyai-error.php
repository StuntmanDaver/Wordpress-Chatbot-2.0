<?php
/**
 * Gary AI Undefined Constant Diagnostic Script
 * 
 * This script meticulously investigates the "Undefined constant 'garyAI'" error
 * by examining the exact file content, line numbers, and potential causes.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For standalone testing
    define('ABSPATH', dirname(__FILE__) . '/');
}

class GaryAIConstantDiagnostic {
    
    private $plugin_file;
    private $errors = [];
    private $warnings = [];
    
    public function __construct() {
        $this->plugin_file = __DIR__ . '/gary-ai.php';
    }
    
    /**
     * Run comprehensive diagnostic
     */
    public function runDiagnostic() {
        echo "<h2>Gary AI Undefined Constant 'garyAI' Diagnostic</h2>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        $this->checkFileExists();
        $this->examineLineContent();
        $this->searchForGaryAIReferences();
        $this->checkForHiddenCharacters();
        $this->analyzeMethodContent();
        $this->checkForVersionMismatch();
        $this->displaySummary();
        
        echo "</div>\n";
    }
    
    /**
     * Check if plugin file exists
     */
    private function checkFileExists() {
        echo "<h3>1. File Existence Check</h3>\n";
        
        if (file_exists($this->plugin_file)) {
            echo "<span style='color: green;'>✓ Plugin file exists: {$this->plugin_file}</span><br>\n";
            
            $size = filesize($this->plugin_file);
            $modified = date('Y-m-d H:i:s', filemtime($this->plugin_file));
            echo "<span style='color: blue;'>File size: {$size} bytes</span><br>\n";
            echo "<span style='color: blue;'>Last modified: {$modified}</span><br>\n";
        } else {
            $this->errors[] = "Plugin file not found: {$this->plugin_file}";
            echo "<span style='color: red;'>✗ Plugin file not found: {$this->plugin_file}</span><br>\n";
            return;
        }
    }
    
    /**
     * Examine exact line 237 content
     */
    private function examineLineContent() {
        echo "<h3>2. Line 237 Content Analysis</h3>\n";
        
        $lines = file($this->plugin_file, FILE_IGNORE_NEW_LINES);
        
        if (!$lines) {
            $this->errors[] = "Could not read plugin file";
            echo "<span style='color: red;'>✗ Could not read plugin file</span><br>\n";
            return;
        }
        
        $total_lines = count($lines);
        echo "<span style='color: blue;'>Total lines in file: {$total_lines}</span><br>\n";
        
        if (isset($lines[236])) { // Line 237 (0-indexed)
            $line_237 = $lines[236];
            echo "<span style='color: green;'>✓ Line 237 content:</span><br>\n";
            echo "<code style='background: #fff; padding: 5px; border: 1px solid #ddd;'>" . htmlspecialchars($line_237) . "</code><br>\n";
            
            // Check for garyAI in this line
            if (strpos($line_237, 'garyAI') !== false) {
                $this->warnings[] = "Found 'garyAI' reference on line 237";
                echo "<span style='color: orange;'>⚠ Found 'garyAI' reference on line 237</span><br>\n";
            } else {
                echo "<span style='color: blue;'>No 'garyAI' reference found on line 237</span><br>\n";
            }
        } else {
            $this->errors[] = "Line 237 does not exist in file";
            echo "<span style='color: red;'>✗ Line 237 does not exist in file (only {$total_lines} lines)</span><br>\n";
        }
        
        // Show context around line 237
        echo "<h4>Context around line 237:</h4>\n";
        for ($i = 234; $i <= 240; $i++) {
            if (isset($lines[$i - 1])) {
                $line_content = htmlspecialchars($lines[$i - 1]);
                $marker = ($i == 237) ? " <-- ERROR LINE" : "";
                echo "<code style='background: #fff; padding: 2px; border: 1px solid #ddd; display: block;'>{$i}: {$line_content}{$marker}</code>\n";
            }
        }
    }
    
    /**
     * Search for all garyAI references in file
     */
    private function searchForGaryAIReferences() {
        echo "<h3>3. Search for 'garyAI' References</h3>\n";
        
        $content = file_get_contents($this->plugin_file);
        $lines = file($this->plugin_file, FILE_IGNORE_NEW_LINES);
        
        $found_references = [];
        
        foreach ($lines as $line_num => $line_content) {
            if (strpos($line_content, 'garyAI') !== false) {
                $found_references[] = [
                    'line' => $line_num + 1,
                    'content' => $line_content
                ];
            }
        }
        
        if (empty($found_references)) {
            echo "<span style='color: blue;'>No 'garyAI' references found in main plugin file</span><br>\n";
        } else {
            echo "<span style='color: orange;'>Found " . count($found_references) . " 'garyAI' references:</span><br>\n";
            foreach ($found_references as $ref) {
                echo "<code style='background: #fff; padding: 2px; border: 1px solid #ddd; display: block;'>Line {$ref['line']}: " . htmlspecialchars($ref['content']) . "</code>\n";
            }
        }
    }
    
    /**
     * Check for hidden characters or encoding issues
     */
    private function checkForHiddenCharacters() {
        echo "<h3>4. Hidden Characters Analysis</h3>\n";
        
        $lines = file($this->plugin_file, FILE_IGNORE_NEW_LINES);
        
        if (isset($lines[236])) { // Line 237
            $line_237 = $lines[236];
            $byte_content = '';
            
            for ($i = 0; $i < strlen($line_237); $i++) {
                $char = $line_237[$i];
                $ord = ord($char);
                
                if ($ord < 32 || $ord > 126) {
                    $byte_content .= "[{$ord}]";
                } else {
                    $byte_content .= $char;
                }
            }
            
            echo "<span style='color: blue;'>Line 237 with hidden characters revealed:</span><br>\n";
            echo "<code style='background: #fff; padding: 5px; border: 1px solid #ddd;'>" . htmlspecialchars($byte_content) . "</code><br>\n";
        }
    }
    
    /**
     * Analyze addWidgetContainer method content
     */
    private function analyzeMethodContent() {
        echo "<h3>5. addWidgetContainer Method Analysis</h3>\n";
        
        $content = file_get_contents($this->plugin_file);
        
        // Find addWidgetContainer method
        $method_pattern = '/public function addWidgetContainer\(\)\s*\{([^}]+)\}/s';
        
        if (preg_match($method_pattern, $content, $matches)) {
            $method_content = $matches[1];
            echo "<span style='color: green;'>✓ Found addWidgetContainer method</span><br>\n";
            echo "<h4>Method content:</h4>\n";
            echo "<code style='background: #fff; padding: 10px; border: 1px solid #ddd; display: block; white-space: pre-wrap;'>" . htmlspecialchars($method_content) . "</code>\n";
            
            // Check for garyAI in method
            if (strpos($method_content, 'garyAI') !== false) {
                $this->warnings[] = "Found 'garyAI' reference in addWidgetContainer method";
                echo "<span style='color: orange;'>⚠ Found 'garyAI' reference in addWidgetContainer method</span><br>\n";
            } else {
                echo "<span style='color: blue;'>No 'garyAI' reference found in addWidgetContainer method</span><br>\n";
            }
        } else {
            $this->errors[] = "Could not find addWidgetContainer method";
            echo "<span style='color: red;'>✗ Could not find addWidgetContainer method</span><br>\n";
        }
    }
    
    /**
     * Check for potential version mismatch
     */
    private function checkForVersionMismatch() {
        echo "<h3>6. Version Mismatch Analysis</h3>\n";
        
        $content = file_get_contents($this->plugin_file);
        
        // Check plugin version
        if (preg_match('/Version:\s*([0-9.]+)/', $content, $matches)) {
            $version = $matches[1];
            echo "<span style='color: blue;'>Plugin version in header: {$version}</span><br>\n";
        }
        
        // Check GARY_AI_VERSION constant
        if (preg_match('/define\(\'GARY_AI_VERSION\',\s*\'([^\']+)\'\)/', $content, $matches)) {
            $constant_version = $matches[1];
            echo "<span style='color: blue;'>GARY_AI_VERSION constant: {$constant_version}</span><br>\n";
        }
        
        // Check file hash for integrity
        $file_hash = md5($content);
        echo "<span style='color: blue;'>File MD5 hash: {$file_hash}</span><br>\n";
        
        // Check if this might be a cached version issue
        echo "<span style='color: orange;'>⚠ Potential causes of line number mismatch:</span><br>\n";
        echo "- Server file differs from local file<br>\n";
        echo "- PHP opcode cache (OPcache) serving old version<br>\n";
        echo "- WordPress object cache serving cached content<br>\n";
        echo "- File was modified after error occurred<br>\n";
    }
    
    /**
     * Display diagnostic summary
     */
    private function displaySummary() {
        echo "<h3>7. Diagnostic Summary</h3>\n";
        
        if (!empty($this->errors)) {
            echo "<h4 style='color: red;'>Errors Found:</h4>\n";
            foreach ($this->errors as $error) {
                echo "<span style='color: red;'>✗ {$error}</span><br>\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "<h4 style='color: orange;'>Warnings:</h4>\n";
            foreach ($this->warnings as $warning) {
                echo "<span style='color: orange;'>⚠ {$warning}</span><br>\n";
            }
        }
        
        echo "<h4>Recommendations:</h4>\n";
        echo "1. Clear all caches (WordPress, server, browser)<br>\n";
        echo "2. Verify the server file matches the local file<br>\n";
        echo "3. Check for any template files or includes that might contain 'garyAI'<br>\n";
        echo "4. Examine the exact error context on the server<br>\n";
        echo "5. Consider adding debug logging to pinpoint the exact source<br>\n";
    }
}

// Run diagnostic if accessed directly
if (!defined('ABSPATH') || (defined('WP_DEBUG') && WP_DEBUG)) {
    $diagnostic = new GaryAIConstantDiagnostic();
    $diagnostic->runDiagnostic();
}
?>
