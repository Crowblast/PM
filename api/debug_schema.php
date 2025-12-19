<?php
// api/debug_schema.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $pdo = DB::connect();
    
    // List tables
    $query = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $query->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [];
    foreach ($tables as $t) {
        // Count rows
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            $result[$t] = [
                'exists' => true,
                'rows' => $count
            ];
            
            // Get columns (Pragma)
            $cols = $pdo->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC);
            $result[$t]['columns'] = array_column($cols, 'name');
            
        } catch (Exception $e) {
            $result[$t] = ['error' => $e->getMessage()];
        }
    }
    
    echo json_encode(['success' => true, 'tables' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
