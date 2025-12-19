<?php
// api/reportes.php
require_once 'db.php';
header('Content-Type: application/json');

$pdo = DB::connect();
$type = $_GET['type'] ?? 'dia'; // dia, mes, anio
$date = $_GET['date'] ?? date('Y-m-d');

// Define filters based on type
$whereVentas = "";
$whereGastos = "";

if ($type === 'dia') {
    // SQLite: date(fecha)
    $whereVentas = "date(fecha) = date('$date')";
    $whereGastos = "date(fecha) = date('$date')";
} elseif ($type === 'mes') {
    // SQLite: strftime('%Y-%m', fecha)
    $targetMonth = date('Y-m', strtotime($date));
    $whereVentas = "strftime('%Y-%m', fecha) = '$targetMonth'";
    $whereGastos = "strftime('%Y-%m', fecha) = '$targetMonth'";
} elseif ($type === 'anio') {
    $targetYear = date('Y', strtotime($date));
    $whereVentas = "strftime('%Y', fecha) = '$targetYear'";
    $whereGastos = "strftime('%Y', fecha) = '$targetYear'";
}

try {
    // 0. Get Dolar Rate for estimations
    $stmtDolar = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'dolar_oficial'");
    $dolar = $stmtDolar->fetchColumn() ?: 1200;

    // 1. Total Sales & Breakdowns
    
    // a. Totals by Moneda de Pago (Collected)
    // We sum 'total_usd' if paid in USD, 'total_ars' if paid in ARS.
    // Note: 'total_ars' is always populated with the ARS equivalent even if paid in USD? 
    // Yes, usually we store both. But to know what was *physically* collected:
    
    // Collected USD (Only sales where moneda_pago = 'USD')
    $stmtUsdColl = $pdo->query("SELECT SUM(total_usd) FROM ventas WHERE $whereVentas AND moneda_pago = 'USD'");
    $collectedUSD = $stmtUsdColl->fetchColumn() ?: 0;

    // Collected ARS (Sales where moneda_pago = 'ARS')
    $stmtArsColl = $pdo->query("SELECT SUM(total_ars) FROM ventas WHERE $whereVentas AND moneda_pago = 'ARS'");
    $collectedARS = $stmtArsColl->fetchColumn() ?: 0;
    
    // Total Converted (ARS) logic:
    // = Collected ARS + (Collected USD * Current Dolar Rate)
    // This represents the theoretical ARS total if everything was converted today.
    $totalConvertedARS = $collectedARS + ($collectedUSD * $dolar);
    
    // Standard Totals (Transaction Value) - kept for compatibility/profit calc
    $stmtVentas = $pdo->query("SELECT SUM(total_ars) as total_ars, SUM(total_usd) as total_usd, COUNT(*) as count FROM ventas WHERE $whereVentas");
    $salesData = $stmtVentas->fetch(PDO::FETCH_ASSOC);
    $totalVentasARS = $salesData['total_ars'] ?: 0;
    $totalVentasUSD = $salesData['total_usd'] ?: 0;

    // b. Breakdown by Payment Method & Currency
    $methods = [];
    $stmtMethods = $pdo->query("SELECT tipo_pago, moneda_pago, COUNT(*) as count, SUM(total_ars) as sum_ars, SUM(total_usd) as sum_usd FROM ventas WHERE $whereVentas GROUP BY tipo_pago, moneda_pago");
    
    while($row = $stmtMethods->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['tipo_pago'];
        $curr = $row['moneda_pago'];
        
        if (!isset($methods[$type])) {
            $methods[$type] = ['count' => 0, 'ars' => 0, 'usd' => 0];
        }
        
        $methods[$type]['count'] += $row['count'];
        
        if ($curr === 'USD') {
            $methods[$type]['usd'] += $row['sum_usd'];
        } else {
            // Default ARS
            $methods[$type]['ars'] += $row['sum_ars'];
        }
    }
    
    // 2. Total Expenses
    $stmtGastos = $pdo->query("SELECT SUM(monto) FROM gastos WHERE $whereGastos");
    $totalGastosARS = $stmtGastos->fetchColumn() ?: 0;
    $totalGastosUSD = $totalGastosARS / $dolar; // Estimate USD from ARS

    // 3. Product Costs (To calculate Profit)
    $sqlCostos = "SELECT SUM(d.cantidad * p.costo_usd) as costo_total_usd
                  FROM detalle_ventas d
                  JOIN ventas v ON d.venta_id = v.id
                  LEFT JOIN productos p ON d.producto_id = p.id
                  WHERE $whereVentas AND d.producto_id IS NOT NULL";
    
    $totalCostoUSD = $pdo->query($sqlCostos)->fetchColumn() ?: 0;
    
    // Approximate ARS Cost
    $effectiveRate = ($totalVentasUSD > 0) ? ($totalVentasARS / $totalVentasUSD) : $dolar;
    $totalCostoARS = $totalCostoUSD * $effectiveRate;

    // 4. Profit
    $gananciaBrutaARS = $totalVentasARS - $totalCostoARS;
    $gananciaBrutaUSD = $totalVentasUSD - $totalCostoUSD;

    $gananciaNetaARS = $gananciaBrutaARS - $totalGastosARS;
    $gananciaNetaUSD = $gananciaBrutaUSD - $totalGastosUSD;

    echo json_encode([
        'periodo' => $type,
        'fecha' => $date,
        'operaciones' => $salesData['count'],
        
        // Detailed Collections
        'ingresos_fisicos_usd' => $collectedUSD,
        'ingresos_fisicos_ars' => $collectedARS,
        'total_convertido_ars' => $totalConvertedARS,
        'metodos_pago' => $methods,

        // Standard accounting totals
        'ventas_ars' => $totalVentasARS,
        'ventas_usd' => $totalVentasUSD,
        
        'costos_ars' => $totalCostoARS,
        'costos_usd' => $totalCostoUSD,
        
        'gastos_ars' => $totalGastosARS,
        'gastos_usd' => $totalGastosUSD,
        
        'ganancia_neta_ars' => $gananciaNetaARS,
        'ganancia_neta_usd' => $gananciaNetaUSD
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
