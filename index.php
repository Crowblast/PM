<?php include 'includes/header.php'; 

// Initialize defaults
$totalVentas = 0;
$countVentas = 0;
$stockBajo = 0;
$creditosActivos = 0;

// Fetch Dashboard Metrics
try {
    $pdo = DB::connect();
    
    // Ventas Hoy
    $hoy = date('Y-m-d');
    $stmtVentas = $pdo->query("SELECT SUM(total_ars) FROM ventas WHERE date(fecha) = date('now', 'localtime')"); // SQLite specific date
    $totalVentas = $stmtVentas->fetchColumn() ?: 0;
    
    // Conteo Ventas Hoy
    $countVentas = $pdo->query("SELECT COUNT(*) FROM ventas WHERE date(fecha) = date('now', 'localtime')")->fetchColumn();

    // Stock Bajo (< 5)
    $stockBajo = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock < 5")->fetchColumn();

    // Creditos Activos (Clientes con deuda)
    // Fix: creditos links to ventas, ventas links to clientes
    $sqlCreditos = "SELECT COUNT(DISTINCT v.cliente_id) 
                    FROM creditos cr
                    JOIN ventas v ON cr.venta_id = v.id
                    WHERE cr.saldo_restante > 0";
    $creditosActivos = $pdo->query($sqlCreditos)->fetchColumn();

} catch (Exception $e) {
    echo "Error cargando métricas: " . $e->getMessage();
}
?>

<!-- Dashboard Content -->
<div class="dashboard-grid">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>

    <div class="card">
        <h3>Ventas del Día</h3>
        <div class="metric-value"><?php echo '$ ' . number_format($totalVentas, 0, ',', '.'); ?></div>
        <small class="text-muted"><?php echo $countVentas; ?> operacion(es) hoy</small>
    </div>

    <div class="card" onclick="window.location.href='config.php'" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
        <h3>Dólar del día</h3>
        <div class="metric-value">$ <?php echo number_format($dolar, 0, ',', '.'); ?></div>
        <small class="text-muted">Click para cambiar cotización</small>
    </div>

    <div class="card">
        <h3>Inventario</h3>
        <div class="metric-value" style="<?php echo $stockBajo > 0 ? 'color: var(--danger-color);' : ''; ?>">
            <?php echo $stockBajo; ?>
        </div>
        <small class="text-muted">Productos en stock bajo (< 5)</small>
    </div>
    
    <div class="card">
        <h3>Deudores Activos</h3>
        <div class="metric-value"><?php echo $creditosActivos; ?></div>
        <small class="text-muted">Clientes con cuotas pendientes</small>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Accesos Directos</h3>
    <div style="display: flex; gap: 10px; margin-top: 15px;">
        <a href="ventas.php" class="btn btn-primary">Nueva Venta</a>
        <a href="inventario.php" class="btn" style="border: 1px solid var(--border-color);">Agregar Producto</a>
        <a href="creditos.php" class="btn" style="border: 1px solid var(--border-color);">Ver Cobranzas</a>
    </div>
</div>

</main>
</div> <!-- End app-container -->
</body>
<script>
    document.getElementById('nav-dashboard').classList.add('active');
</script>
</html>
