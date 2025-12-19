<?php
// api/creditos.php
require_once 'db.php';
header('Content-Type: application/json');

$pdo = DB::connect();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        if (isset($_GET['id'])) {
            // Devuelve las cuotas de un credito especifico
            $stmt = $pdo->prepare("SELECT * FROM cuotas WHERE credito_id = ? ORDER BY numero_cuota ASC");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetchAll());
        } else {
            // Lista todos los creditos activos
            $sql = "SELECT cr.*, c.nombre as cliente_nombre, v.fecha as fecha_venta 
                    FROM creditos cr
                    JOIN ventas v ON cr.venta_id = v.id
                    JOIN clientes c ON v.cliente_id = c.id
                    ORDER BY cr.saldo_restante DESC";
            echo json_encode($pdo->query($sql)->fetchAll());
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input['action'] === 'pagar_cuota') {
        $creditoId = $input['credito_id'];
        $montoPago = floatval($input['monto_pago']);
        
        try {
            $pdo->beginTransaction();

            // Get all pending quotas ordered by number
            $stmtQuotas = $pdo->prepare("SELECT * FROM cuotas WHERE credito_id = ? AND estado = 'PENDIENTE' ORDER BY numero_cuota ASC");
            $stmtQuotas->execute([$creditoId]);
            $quotas = $stmtQuotas->fetchAll(PDO::FETCH_ASSOC);
            
            $remainingPayment = $montoPago;
            
            foreach ($quotas as $quota) {
                if ($remainingPayment <= 0.01) break;

                $amountToCover = floatval($quota['monto']);
                
                if ($remainingPayment >= $amountToCover) {
                    // Fully Pay this quota
                    $pd = $amountToCover;
                    $pdo->prepare("UPDATE cuotas SET estado = 'PAGADO', fecha_pago = CURRENT_TIMESTAMP, monto = 0 WHERE id = ?")->execute([$quota['id']]);
                    $remainingPayment -= $amountToCover;
                } else {
                    // Partially Pay this quota
                    $pd = $remainingPayment;
                    $pdo->prepare("UPDATE cuotas SET monto = monto - ? WHERE id = ?")->execute([$remainingPayment, $quota['id']]);
                    $remainingPayment = 0;
                }
            }
            
            // Update Credit Balance
            $totalPaid = $montoPago - $remainingPayment; // Actual amount applied
            $pdo->prepare("UPDATE creditos SET saldo_restante = saldo_restante - ? WHERE id = ?")->execute([$totalPaid, $creditoId]);

            // Check if Credit is Finished
            $stmtCheck = $pdo->prepare("SELECT saldo_restante FROM creditos WHERE id = ?");
            $stmtCheck->execute([$creditoId]);
            if ($stmtCheck->fetchColumn() <= 0.1) {
                 $pdo->prepare("UPDATE creditos SET estado = 'FINALIZADO', saldo_restante = 0 WHERE id = ?")->execute([$creditoId]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
