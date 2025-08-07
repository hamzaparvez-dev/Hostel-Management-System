<?php
/**
 * Setup Script for Nav Purush Boys Hostel Management System
 * Run this script to configure the system
 */

// Include configuration
require_once 'config.php';
require_once 'includes/dbconn.php';

class SystemSetup {
    private $conn;
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run complete system setup
     */
    public function runSetup() {
        echo "<h1>Nav Purush Boys Hostel Management System - Setup</h1>";
        echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>";
        
        $this->checkSystemRequirements();
        $this->checkDatabaseConnection();
        $this->checkDatabaseSchema();
        $this->createDirectories();
        $this->setPermissions();
        $this->createDefaultData();
        $this->generateSummary();
        
        echo "</div>";
    }
    
    /**
     * Check system requirements
     */
    private function checkSystemRequirements() {
        echo "<h2>1. System Requirements Check</h2>";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $this->success[] = "PHP Version: " . PHP_VERSION . " ✓";
        } else {
            $this->errors[] = "PHP Version: " . PHP_VERSION . " (Required: 8.0+) ✗";
        }
        
        // Check required extensions
        $requiredExtensions = ['oci8', 'pdo', 'json', 'curl', 'gd', 'mbstring', 'openssl'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->success[] = "Extension $ext: Loaded ✓";
            } else {
                $this->errors[] = "Extension $ext: Not loaded ✗";
            }
        }
        
        // Check writable directories
        $writableDirs = ['uploads', 'logs', 'reports'];
        foreach ($writableDirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            if (is_writable($dir)) {
                $this->success[] = "Directory $dir: Writable ✓";
            } else {
                $this->errors[] = "Directory $dir: Not writable ✗";
            }
        }
        
        $this->displayResults();
    }
    
    /**
     * Check database connection
     */
    private function checkDatabaseConnection() {
        echo "<h2>2. Database Connection Check</h2>";
        
        if ($this->conn) {
            $this->success[] = "Oracle Database Connection: Successful ✓";
            
            // Test query
            $sql = "SELECT 1 FROM DUAL";
            $stmt = executeQuery($this->conn, $sql);
            if ($stmt) {
                $this->success[] = "Database Query Test: Successful ✓";
            } else {
                $this->errors[] = "Database Query Test: Failed ✗";
            }
        } else {
            $this->errors[] = "Oracle Database Connection: Failed ✗";
        }
        
        $this->displayResults();
    }
    
    /**
     * Check database schema
     */
    private function checkDatabaseSchema() {
        echo "<h2>3. Database Schema Check</h2>";
        
        $requiredTables = [
            'admin', 'student_registration', 'rooms', 'fee_payments',
            'visitor_log', 'mess_activities', 'courses', 'states'
        ];
        
        foreach ($requiredTables as $table) {
            $sql = "SELECT COUNT(*) as count FROM user_tables WHERE table_name = :table_name";
            $params = array(':table_name' => strtoupper($table));
            $stmt = executeQuery($this->conn, $sql, $params);
            $result = fetchRow($stmt);
            
            if ($result && $result['COUNT'] > 0) {
                $this->success[] = "Table $table: Exists ✓";
            } else {
                $this->errors[] = "Table $table: Missing ✗";
            }
        }
        
        // Check sequences
        $requiredSequences = [
            'admin_seq', 'student_reg_seq', 'rooms_seq', 'fee_payments_seq',
            'visitor_log_seq', 'mess_activities_seq', 'courses_seq', 'states_seq'
        ];
        
        foreach ($requiredSequences as $sequence) {
            $sql = "SELECT COUNT(*) as count FROM user_sequences WHERE sequence_name = :sequence_name";
            $params = array(':sequence_name' => strtoupper($sequence));
            $stmt = executeQuery($this->conn, $sql, $params);
            $result = fetchRow($stmt);
            
            if ($result && $result['COUNT'] > 0) {
                $this->success[] = "Sequence $sequence: Exists ✓";
            } else {
                $this->errors[] = "Sequence $sequence: Missing ✗";
            }
        }
        
        $this->displayResults();
    }
    
    /**
     * Create required directories
     */
    private function createDirectories() {
        echo "<h2>4. Directory Setup</h2>";
        
        $directories = [
            'uploads' => 0755,
            'logs' => 0755,
            'reports' => 0755,
            'backups' => 0755,
            'temp' => 0755
        ];
        
        foreach ($directories as $dir => $permissions) {
            if (!file_exists($dir)) {
                if (mkdir($dir, $permissions, true)) {
                    $this->success[] = "Created directory: $dir ✓";
                } else {
                    $this->errors[] = "Failed to create directory: $dir ✗";
                }
            } else {
                $this->success[] = "Directory exists: $dir ✓";
            }
        }
        
        $this->displayResults();
    }
    
    /**
     * Set file permissions
     */
    private function setPermissions() {
        echo "<h2>5. Permission Setup</h2>";
        
        $files = [
            'config.php' => 0644,
            'includes/dbconn.php' => 0644
        ];
        
        foreach ($files as $file => $permissions) {
            if (file_exists($file)) {
                if (chmod($file, $permissions)) {
                    $this->success[] = "Set permissions for $file ✓";
                } else {
                    $this->warnings[] = "Could not set permissions for $file";
                }
            }
        }
        
        $this->displayResults();
    }
    
    /**
     * Create default data
     */
    private function createDefaultData() {
        echo "<h2>6. Default Data Setup</h2>";
        
        // Check if admin exists
        $sql = "SELECT COUNT(*) as count FROM admin";
        $stmt = executeQuery($this->conn, $sql);
        $result = fetchRow($stmt);
        
        if ($result['COUNT'] == 0) {
            // Create default admin
            $adminData = array(
                'username' => 'admin',
                'email' => 'admin@navpurushhostel.com',
                'password' => md5('admin123'),
                'role' => 'super_admin',
                'status' => 1
            );
            
            $adminModel = new AdminModel($this->conn);
            if ($adminModel->insert($adminData)) {
                $this->success[] = "Created default admin account ✓";
            } else {
                $this->errors[] = "Failed to create default admin account ✗";
            }
        } else {
            $this->success[] = "Admin account already exists ✓";
        }
        
        // Check if courses exist
        $sql = "SELECT COUNT(*) as count FROM courses";
        $stmt = executeQuery($this->conn, $sql);
        $result = fetchRow($stmt);
        
        if ($result['COUNT'] == 0) {
            $this->warnings[] = "No courses found. Please add courses through admin panel.";
        } else {
            $this->success[] = "Courses data exists ✓";
        }
        
        // Check if states exist
        $sql = "SELECT COUNT(*) as count FROM states";
        $stmt = executeQuery($this->conn, $sql);
        $result = fetchRow($stmt);
        
        if ($result['COUNT'] == 0) {
            $this->warnings[] = "No states found. Please add states through admin panel.";
        } else {
            $this->success[] = "States data exists ✓";
        }
        
        $this->displayResults();
    }
    
    /**
     * Generate setup summary
     */
    private function generateSummary() {
        echo "<h2>7. Setup Summary</h2>";
        
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        
        if (empty($this->errors)) {
            echo "<h3 style='color: green;'>✓ Setup Completed Successfully!</h3>";
            echo "<p><strong>Default Admin Credentials:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Email:</strong> admin@navpurushhostel.com</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li>Login to admin panel</li>";
            echo "<li>Add courses and states</li>";
            echo "<li>Configure room details</li>";
            echo "<li>Set up fee structure</li>";
            echo "<li>Test all modules</li>";
            echo "</ol>";
        } else {
            echo "<h3 style='color: red;'>✗ Setup Failed!</h3>";
            echo "<p>Please fix the following errors before proceeding:</p>";
            echo "<ul>";
            foreach ($this->errors as $error) {
                echo "<li style='color: red;'>$error</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($this->warnings)) {
            echo "<p><strong>Warnings:</strong></p>";
            echo "<ul>";
            foreach ($this->warnings as $warning) {
                echo "<li style='color: orange;'>$warning</li>";
            }
            echo "</ul>";
        }
        
        echo "</div>";
        
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
        echo "</div>";
    }
    
    /**
     * Display results
     */
    private function displayResults() {
        if (!empty($this->success)) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            foreach ($this->success as $message) {
                echo "<p style='margin: 5px 0; color: #155724;'>$message</p>";
            }
            echo "</div>";
        }
        
        if (!empty($this->warnings)) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            foreach ($this->warnings as $message) {
                echo "<p style='margin: 5px 0; color: #856404;'>$message</p>";
            }
            echo "</div>";
        }
        
        if (!empty($this->errors)) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            foreach ($this->errors as $message) {
                echo "<p style='margin: 5px 0; color: #721c24;'>$message</p>";
            }
            echo "</div>";
        }
        
        // Clear arrays for next section
        $this->success = [];
        $this->warnings = [];
        $this->errors = [];
    }
}

// Run setup if accessed directly
if (basename($_SERVER['PHP_SELF']) == 'setup.php') {
    $setup = new SystemSetup($conn);
    $setup->runSetup();
}
?> 