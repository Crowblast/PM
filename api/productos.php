<?php
// api/productos.php
require_once 'db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = DB::connect();

try {
    // Obtener cotización actual para cálculos auxiliares
    $stmtConfig = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'dolar_oficial'");
    // fetchColumn returns false on failure/empty. floatval(false) is 0.
    $val = $stmtConfig ? $stmtConfig->fetchColumn() : 0;
    $dolarVal = floatval($val ?: 1200);
} catch (Exception $e) {
    $dolarVal = 1200; // Fallback
}

if ($method === 'GET') {
    try {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch());
        } else {
            // Buscador
            $sql = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM productos p 
                    LEFT JOIN categorias c ON p.categoria_id = c.id";
            
            if (isset($_GET['q'])) {
                $q = $_GET['q'];
                $sql .= " WHERE p.nombre LIKE ? OR p.codigo LIKE ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(["%$q%", "%$q%"]);
            } else {
                $stmt = $pdo->query($sql);
            }
            
            $productos = $stmt->fetchAll();
            
            // Añadir campo calculado de precio ARS sugerido al vuelo
            foreach ($productos as &$p) {
                $p['precio_ars_calculado'] = $p['precio_usd'] * $dolarVal;
            }
            
            echo json_encode($productos);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'POST') {
    try {
        // Verificar si es una actualización (tiene ID) o creación
        $data = $_POST; // Usamos $_POST porque vendrá con Files probablemente
        
        // Manejo de Imagen
        $imagenUrl = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = uniqid() . '_' . basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadDir . $fileName);
            $imagenUrl = 'assets/img/' . $fileName;
        }

        if (!empty($data['id'])) {
            // UPDATE
            $sql = "UPDATE productos SET 
                    codigo=?, nombre=?, marca=?, categoria_id=?, 
                    costo_usd=?, precio_usd=?, stock=? 
                    WHERE id=?";
            $params = [
                $data['codigo'], $data['nombre'], $data['marca'], $data['categoria_id'],
                $data['costo_usd'], $data['precio_usd'], $data['stock'], 
                $data['id']
            ];
            
            if ($imagenUrl) {
                // Si subió nueva imagen, actualizar campo
                $sql = str_replace("stock=?", "stock=?, imagen=?", $sql);
                array_splice($params, 6, 0, $imagenUrl);
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
        } else {
            // INSERT
            $sql = "INSERT INTO productos (codigo, nombre, marca, categoria_id, costo_usd, precio_usd, stock, imagen) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['codigo'], $data['nombre'], $data['marca'], $data['categoria_id'] ?? null,
                $data['costo_usd'], $data['precio_usd'], $data['stock'], $imagenUrl
            ]);
        }

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'DELETE') {
    // Leer input raw para DELETE
    parse_str(file_get_contents("php://input"), $delData);
    $id = $delData['id'] ?? null;
    
    if ($id) {
        $pdo->prepare("DELETE FROM productos WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    }
}
