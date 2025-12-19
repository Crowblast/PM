<?php
// api/gastos.php
require_once 'db.php';
header('Content-Type: application/json');

$pdo = DB::connect();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM gastos ORDER BY fecha DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['descripcion'], $input['monto'])) {
        $stmt = $pdo->prepare("INSERT INTO gastos (descripcion, monto, categoria, fecha) VALUES (?, ?, ?, date('now', 'localtime'))");
        $stmt->execute([
            $input['descripcion'],
            $input['monto'], 
            $input['categoria'] ?? 'General'
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
    }
}
elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $pdo->prepare("DELETE FROM gastos WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    }
}
