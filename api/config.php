<?php
// api/config.php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo = DB::connect();
    
    // Upsert equivalent in SQLite
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES (?, ?)");
    $stmt->execute([$data['clave'], $data['valor']]);
    
    echo json_encode(['success' => true]);
}
