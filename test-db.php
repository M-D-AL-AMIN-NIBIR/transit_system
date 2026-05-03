<?php
/**
 * Database Connection Test Script
 * Run this to verify your database connection is working
 */

echo "<h2>Transit System - Database Connection Test</h2>";

try {
    // Load database configuration
    $config = require __DIR__ . '/config/database.php';
    
    echo "<h3>Configuration:</h3>";
    echo "<pre>";
    echo "Host: {$config['host']}\n";
    echo "Database: {$config['dbname']}\n";
    echo "Username: {$config['username']}\n";
    echo "Password: " . (empty($config['password']) ? '(empty)' : '***') . "\n";
    echo "</pre>";
    
    // Attempt connection
    echo "<h3>Connecting to database...</h3>";
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green; font-weight: bold;'>✓ Database connected successfully!</p>";
    
    // Test query
    echo "<h3>Running test query...</h3>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✓ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            // Get row count for each table
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $count = $countStmt->fetchColumn();
            echo "<li>{$table} ({$count} rows)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ No tables found. Please import the database schema.</p>";
    }
    
    // Test specific tables
    $requiredTables = ['users', 'passes', 'vehicles', 'routes', 'payments'];
    $missingTables = array_diff($requiredTables, $tables);
    
    if (!empty($missingTables)) {
        echo "<h3 style='color: red;'>⚠ Missing Required Tables:</h3>";
        echo "<ul>";
        foreach ($missingTables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
        echo "<p>Please import <code>sql/database_schema.sql</code> into your database.</p>";
    } else {
        echo "<p style='color: green;'>✓ All required tables present!</p>";
    }
    
    // Check admin user
    if (in_array('users', $tables)) {
        echo "<h3>Checking admin users...</h3>";
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount > 0) {
            echo "<p style='color: green;'>✓ Found {$adminCount} admin user(s)</p>";
        } else {
            echo "<p style='color: orange;'>⚠ No admin users found. Create one through the admin registration process.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>✗ Connection Failed!</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Common issues
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Ensure MySQL is running in XAMPP Control Panel</li>";
    echo "<li>Verify database '{$config['dbname']}' exists (create it in phpMyAdmin)</li>";
    echo "<li>Check username/password in config/database.php</li>";
    echo "<li>Ensure MySQL port is 3306 (default)</li>";
    echo "</ul>";
}

// Display PHP info relevant to PDO
echo "<h3>PDO Support:</h3>";
echo "<ul>";
echo "<li>PDO Enabled: " . (extension_loaded('pdo') ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . "</li>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . "</li>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a> | <a href='test-db.php'>Refresh Test</a></p>";
