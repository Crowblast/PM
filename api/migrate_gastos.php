<?php
// api/migrate_gastos.php
require_once 'db.php';

try {
    $pdo = DB::connect();
    
    echo "Creando tabla 'gastos'...\n";
    $sql = "CREATE TABLE IF NOT EXISTS gastos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        descripcion TEXT NOT NULL,
        monto REAL NOT NULL,
        categoria TEXT,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Tabla 'gastos' creada o verificada.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
