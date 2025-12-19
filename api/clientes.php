<?php
// api/clientes.php
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = DB::connect();

if ($method === 'GET') {
    try {
        if (isset($_GET['q'])) {
            $q = $_GET['q'];
            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nombre LIKE ? OR dni LIKE ?");
            $stmt->execute(["%$q%", "%$q%"]);
            echo json_encode($stmt->fetchAll());
        } else {
            $stmt = $pdo->query("SELECT * FROM clientes ORDER BY nombre ASC LIMIT 50");
            echo json_encode($stmt->fetchAll());
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        if (!empty($data['id'])) {
            // Update
            $stmt = $pdo->prepare("UPDATE clientes SET dni=?, nombre=?, telefono=?, direccion=? WHERE id=?");
            $stmt->execute([$data['dni'], $data['nombre'], $data['telefono'], $data['direccion'], $data['id']]);
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO clientes (dni, nombre, telefono, direccion) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['dni'], $data['nombre'], $data['telefono'], $data['direccion']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
