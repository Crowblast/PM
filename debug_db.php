<?php
require_once 'api/db.php';

try {
    $pdo = DB::connect();
    echo "Connected to DB\n";
    
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found:\n";
    foreach ($tables as $t) {
        echo "- $t\n";
        // Show row count
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "  Rows: $count\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
