<!-- includes/header.php -->
<?php
// Obtener cotización global
require_once __DIR__ . '/../api/db.php';
try {
    $pdo = DB::connect();
    $pdo = DB::connect();
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('dolar_oficial', 'dolar_margen')");
    $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $base = $config['dolar_oficial'] ?? 1200;
    $margin = $config['dolar_margen'] ?? 0;
    
    $dolar = $base * (1 + ($margin / 100)); // Effective Rate
} catch (Exception $e) {
    $dolar = 0;
    $base = 0;
    $margin = 0;
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PM celulares</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
    <div class="overlay"></div>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button id="menu-toggle" class="menu-toggle">
                        ☰
                    </button>
                    <div>
                        <h1 id="page-title">Bienvenido</h1>
                        <p class="text-muted"><?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; align-items: center;">
                    <div class="dolar-badge" style="background: var(--card-bg); padding: 8px 15px; border-radius: 20px; border: 1px solid var(--success-color); color: var(--success-color); font-weight: bold;">
                        USD $ <span id="global-dolar"><?php echo number_format($dolar, 2); ?></span> ARS
                    </div>
                    
                    <button id="theme-toggle" class="btn" style="background: transparent; border: 1px solid var(--border-color);">
                        🌓
                    </button>
                    
                    <div class="user-profile">
                        <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                            A
                        </div>
                    </div>
                </div>
            </header>
