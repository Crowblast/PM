<?php include 'includes/header.php'; ?>

<h2>Configuración del Sistema</h2>

<div class="card" style="max-width: 500px; margin-top: 20px;">
    <h3>Variables Globales</h3>
    <form id="config-form" style="margin-top: 20px;">
        <div style="margin-bottom: 20px;">
            <label>Cotización Dólar Base (ARS)</label>
            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                <input type="number" step="0.01" name="dolar_oficial" id="dolar-input" value="<?php echo $base; ?>" required class="form-control" style="width: 150px;">
                <span class="text-muted">Base</span>
            </div>

            <label>Margen / Aumento (%)</label>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="number" step="0.01" name="dolar_margen" id="margen-input" value="<?php echo $margin; ?>" class="form-control" style="width: 150px;">
                <span class="text-muted">%</span>
            </div>
            
            <div style="background: var(--bg-color); padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <strong>Cotización Final (Sistema):</strong>
                <span style="font-size: 1.2em; color: var(--success-color); margin-left: 10px;">
                    $ <?php echo number_format($dolar, 2); ?>
                </span>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Configuración</button>
            <div style="margin-top: 10px;">
                <small class="text-muted">El sistema usará la "Cotización Final" para todos los cálculos.</small>
            </div>
        </div>
    </form>
</div>

<!-- Generic Message Modal -->
<div id="msg-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 300px; text-align: center;">
        <h3 id="msg-modal-title" style="margin-bottom: 10px;">Aviso</h3>
        <p id="msg-modal-text" class="text-muted" style="margin-bottom: 20px;"></p>
        <button class="btn btn-primary" id="msg-modal-btn">Aceptar</button>
    </div>
</div>

<script>
document.getElementById('nav-config').classList.add('active');

const showMessage = (msg, isError = false, callback) => {
    const m = document.getElementById('msg-modal');
    document.getElementById('msg-modal-title').innerText = isError ? 'Error' : 'Exito';
    document.getElementById('msg-modal-title').style.color = isError ? 'var(--danger-color)' : 'var(--success-color)';
    document.getElementById('msg-modal-text').innerText = msg;
    m.style.display = 'flex';
    
    document.getElementById('msg-modal-btn').onclick = () => {
        m.style.display = 'none';
        if (callback) callback();
    };
};

document.getElementById('config-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const base = document.getElementById('dolar-input').value;
    const margin = document.getElementById('margen-input').value;
    
    // Save Base
    await fetch('api/config.php', {
        method: 'POST',
        body: JSON.stringify({ clave: 'dolar_oficial', valor: base }),
        headers: {'Content-Type': 'application/json'}
    });

    // Save Margin
    const res = await fetch('api/config.php', {
        method: 'POST',
        body: JSON.stringify({ clave: 'dolar_margen', valor: margin }),
        headers: {'Content-Type': 'application/json'}
    });
    
    if (res.ok) {
        showMessage('Configuración actualizada correctamente', false, () => {
            location.reload();
        });
    } else {
        const txt = await res.text(); 
        showMessage('Error al actualizar: ' + txt, true);
    }
});
</script>

</main>
</div>
</body>
</html>
