<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    if(!$conn) {
        die("Database connection failed\n");
    }
    
    echo "Connected to database successfully\n";
    
    $sql = file_get_contents(__DIR__ . '/schema_sqlserver.sql');
    
    // Split by GO statements
    $statements = array_filter(
        array_map('trim', preg_split('/\bGO\b/i', $sql)),
        function($stmt) { return !empty($stmt); }
    );
    
    echo "Found " . count($statements) . " SQL statements\n\n";
    
    $success = 0;
    $failed = 0;
    
    foreach($statements as $index => $statement) {
        try {
            $conn->exec($statement);
            $success++;
            echo "✓ Statement " . ($index + 1) . " executed\n";
        } catch(PDOException $e) {
            $failed++;
            echo "✗ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=================================\n";
    echo "Execution complete\n";
    echo "Success: $success\n";
    echo "Failed: $failed\n";
    echo "=================================\n";
    
} catch(Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
