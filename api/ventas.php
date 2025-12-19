<?php
// api/ventas.php
require_once 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pdo = DB::connect();

try {
    $pdo->beginTransaction();

    // 1. Insertar Venta
    $sql = "INSERT INTO ventas (cliente_id, fecha, total_usd, total_ars, tipo_pago, moneda_pago) VALUES (?, datetime('now', 'localtime'), ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // credito logic handles its own insert if 'credito' is present (linking back to venta_id).

    $stmt->execute([
        $input['cliente_id'],
        $input['total_usd'],
        $input['total_ars'],
        $input['tipo_pago'],
        $input['moneda_pago'] ?? 'ARS'
    ]);$ventaId = $pdo->lastInsertId();

    // 2. Procesar Items
    $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario_usd, precio_unitario_ars) VALUES (?, ?, ?, ?, ?)";
    $stmtDetalle = $pdo->prepare($sqlDetalle);

    $sqlUpdateStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmtStock = $pdo->prepare($sqlUpdateStock);

    foreach ($input['items'] as $item) {
        $isCustom = isset($item['is_custom']) && $item['is_custom'];
        $prodId = $isCustom ? null : $item['id'];
        
        // Registrar Detalle
        // If it's custom, we use provided description, otherwise getting it via JOIN usually, 
        // but here we just store the name from frontend as description for simplicity or keep null.
        $desc = $isCustom ? $item['nombre'] : null;

        // If your database 'producto_id' allows null, pass null. 
        // If NOT, we might need a "Custom Product" placeholder ID = 0. 
        // We act as if NULL is allowed as per plan.
        
        $sqlInsertDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario_usd, precio_unitario_ars, descripcion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtDet = $pdo->prepare($sqlInsertDetalle);
        
        $stmtDet->execute([
            $ventaId,
            $prodId,
            $item['cantidad'],
            $item['precio_usd'],
            $item['precio_usd'] * ($input['total_ars'] / $input['total_usd']), // Pro-rated ARS
            $desc
        ]);

        // Descontar Stock ONLY for real products
        if (!$isCustom) {
            $stmtStock->execute([$item['cantidad'], $item['id']]);
            
            // Verificar stock negativo
            $check = $pdo->query("SELECT stock FROM productos WHERE id = " . $item['id'])->fetchColumn();
            if ($check < 0) {
                throw new Exception("Stock insuficiente para el producto ID: " . $item['id']);
            }
        }
    }

    // 3. Procesar Crédito (si aplica)
    if ($input['tipo_pago'] === 'CREDITO' && !empty($input['credito'])) {
        $cred = $input['credito'];
        
        $sqlCredito = "INSERT INTO creditos (venta_id, monto_financiado, interes_aplicado, total_a_pagar, saldo_restante, estado) 
                       VALUES (?, ?, ?, ?, ?, 'ACTIVO')";
        $stmtCredito = $pdo->prepare($sqlCredito);
        $stmtCredito->execute([
            $ventaId,
            $input['total_ars'], // El monto base financiado
            $cred['interes'],
            $cred['total_financiado'],
            $cred['total_financiado'] // Saldo inicial es el total
        ]);
        $creditoId = $pdo->lastInsertId();

        // Generar Cuotas
        $montoCuota = $cred['total_financiado'] / $cred['cuotas'];
        $fecha = new DateTime();
        
        $sqlCuota = "INSERT INTO cuotas (credito_id, numero_cuota, fecha_vencimiento, monto, estado) VALUES (?, ?, ?, ?, 'PENDIENTE')";
        $stmtCuota = $pdo->prepare($sqlCuota);

        for ($i = 1; $i <= $cred['cuotas']; $i++) {
            $fecha->modify('+1 month'); // Vencimiento al mes siguiente
            $stmtCuota->execute([
                $creditoId,
                $i,
                $fecha->format('Y-m-d'),
                $montoCuota
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
