<?php
// api/reset_db.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $pdo = DB::connect();
    
    // Disable Foreign Key checks for truncation
    $pdo->exec("PRAGMA foreign_keys = OFF;");

    $tables = ['detalle_ventas', 'cuotas', 'creditos', 'ventas', 'gastos'];

    foreach ($tables as $table) {
        $pdo->exec("DELETE FROM $table;");
        $pdo->exec("UPDATE sqlite_sequence SET seq = 0 WHERE name = '$table';");
    }

    $pdo->exec("PRAGMA foreign_keys = ON;");

    echo json_encode(['success' => true, 'message' => 'Database transactions cleared.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
