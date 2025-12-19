<?php
// api/migrate_moneda_pago.php
require_once 'db.php';

try {
    $pdo = DB::connect();
    
    // Check if column exists
    $cols = $pdo->query("PRAGMA table_info(ventas)")->fetchAll(PDO::FETCH_ASSOC);
    $hasCol = false;
    foreach ($cols as $col) {
        if ($col['name'] === 'moneda_pago') {
            $hasCol = true;
            break;
        }
    }

    if (!$hasCol) {
        echo "Agregando columna 'moneda_pago' a ventas...\n";
        $pdo->exec("ALTER TABLE ventas ADD COLUMN moneda_pago TEXT DEFAULT 'ARS'");
        echo "Columna agregada.\n";
    } else {
        echo "La columna 'moneda_pago' ya existe.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
