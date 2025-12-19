<!-- includes/sidebar.php -->
<aside class="sidebar">
    <div class="brand" style="margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between;">
        <h2 style="color: var(--primary-color);">PM celulares</h2>
    </div>
    
    <nav class="nav-menu">
        <style>
            .nav-link {
                display: flex;
                align-items: center;
                padding: 12px 15px;
                color: var(--text-color);
                text-decoration: none;
                border-radius: 8px;
                margin-bottom: 5px;
                transition: background 0.2s;
            }
            .nav-link:hover, .nav-link.active {
                background-color: rgba(37, 99, 235, 0.1);
                color: var(--primary-color);
            }
            .nav-link i {
                margin-right: 10px;
                width: 20px;
                text-align: center;
            }
        </style>

        <a href="index.php" class="nav-link" id="nav-dashboard">
            <i>📊</i> Dashboard
        </a>
        <a href="inventario.php" class="nav-link" id="nav-inventario">
            <i>📱</i> Inventario
        </a>
        <a href="ventas.php" class="nav-link" id="nav-ventas">
            <i>🛒</i> Ventas / POS
        </a>
        <a href="creditos.php" class="nav-link" id="nav-creditos">
            <i>💳</i> Créditos
        </a>
        <a href="clientes.php" class="nav-link" id="nav-clientes">
            <i>👥</i> Clientes
        </a>
        <a href="gastos.php" class="nav-link" id="nav-gastos">
            <i>💸</i> Gastos
        </a>
        <a href="reportes.php" class="nav-link" id="nav-reportes">
            <i>📈</i> Reportes
        </a>
        <a href="config.php" class="nav-link" id="nav-config">
            <i>⚙️</i> Configuración
        </a>
    </nav>
</aside>
