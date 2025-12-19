<?php
// api/install.php
require_once 'db.php';

try {
    $pdo = DB::connect();

    // Tabla de Configuración
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
        clave TEXT PRIMARY KEY,
        valor TEXT
    )");
    
    // Insertar cotización inicial si no existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracion WHERE clave = 'dolar_oficial'");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO configuracion (clave, valor) VALUES ('dolar_oficial', '1200')");
    }

    // Tabla Categorías
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL
    )");

    // Tabla Productos
    $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        codigo TEXT,
        nombre TEXT NOT NULL,
        marca TEXT,
        categoria_id INTEGER,
        imagen TEXT,
        costo_usd REAL DEFAULT 0,
        precio_usd REAL DEFAULT 0,
        stock INTEGER DEFAULT 0,
        FOREIGN KEY(categoria_id) REFERENCES categorias(id)
    )");

    // Tabla Clientes
    $pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        dni TEXT UNIQUE,
        nombre TEXT NOT NULL,
        telefono TEXT,
        direccion TEXT
    )");

    // Tabla Ventas
    $pdo->exec("CREATE TABLE IF NOT EXISTS ventas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente_id INTEGER,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_usd REAL,
        total_ars REAL,
        tipo_pago TEXT,
        FOREIGN KEY(cliente_id) REFERENCES clientes(id)
    )");

    // Tabla Detalle Venta
    $pdo->exec("CREATE TABLE IF NOT EXISTS detalle_ventas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        venta_id INTEGER,
        producto_id INTEGER,
        cantidad INTEGER,
        precio_unitario_usd REAL,
        precio_unitario_ars REAL,
        FOREIGN KEY(venta_id) REFERENCES ventas(id),
        FOREIGN KEY(producto_id) REFERENCES productos(id)
    )");

    // Tabla Créditos
    $pdo->exec("CREATE TABLE IF NOT EXISTS creditos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        venta_id INTEGER,
        monto_financiado REAL,
        interes_aplicado REAL,
        total_a_pagar REAL,
        saldo_restante REAL,
        estado TEXT DEFAULT 'ACTIVO',
        FOREIGN KEY(venta_id) REFERENCES ventas(id)
    )");

    // Tabla Cuotas
    $pdo->exec("CREATE TABLE IF NOT EXISTS cuotas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        credito_id INTEGER,
        numero_cuota INTEGER,
        fecha_vencimiento DATE,
        monto REAL,
        fecha_pago DATETIME,
        estado TEXT DEFAULT 'PENDIENTE',
        FOREIGN KEY(credito_id) REFERENCES creditos(id)
    )");

    echo "Base de datos instalada correctamente.";

} catch (Exception $e) {
    echo "Error al instalar base de datos: " . $e->getMessage();
}
