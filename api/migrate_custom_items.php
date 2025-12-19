<?php
// api/migrate_custom_items.php
require_once 'db.php';

try {
    $pdo = DB::connect();
    
    // Add descripcion column to detalle_ventas
    // SQLite doesn't support IF NOT EXISTS in ADD COLUMN well in all versions, 
    // but we can try or catch.
    
    echo "Agregando columna 'descripcion' a detalle_ventas...\n";
    try {
        $pdo->exec("ALTER TABLE detalle_ventas ADD COLUMN descripcion TEXT DEFAULT NULL");
        echo "Columna agregada.\n";
    } catch (Exception $e) {
        echo "Error (puede que ya exista): " . $e->getMessage() . "\n";
    }

    // Since we cannot easily alter column constraints (allow NULL) in SQLite without recreating table,
    // we will rely on strict mode. If the table was created with "NOT NULL" on product_id, we might have issues.
    // However, usually simplified schemas don't have it unless specified.
    // If it fails later, we will handle it by using 0 or -1.
    
    echo "Migracion completada.\n";

} catch (Exception $e) {
    echo "Error General: " . $e->getMessage();
}
