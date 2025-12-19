<?php
// api/categorias.php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $pdo = DB::connect();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Listar categorías
        $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre ASC");
        echo json_encode($stmt->fetchAll());

    } elseif ($method === 'POST') {
        // Crear nueva categoría
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre es requerido']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt->execute([trim($data['nombre'])]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

    } elseif ($method === 'DELETE') {
        // Eliminar categoría
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }

        // Verificar si la categoría está en uso
        $check = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $check->execute([$id]);
        
        if ($check->fetchColumn() > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['error' => 'No se puede eliminar: Hay productos asociados a esta categoría']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
