<?php include 'includes/header.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Reportes y Métricas</h3>
        <div style="display: flex; gap: 10px;">
            <select id="report-type" class="btn" style="background: var(--bg-color); border: 1px solid var(--border-color);">
                <option value="dia">Diario</option>
                <option value="mes">Mensual</option>
                <option value="anio">Anual</option>
            </select>
            <input type="date" id="report-date" class="btn" style="background: var(--bg-color); border: 1px solid var(--border-color);">
        </div>
    </div>

    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <!-- New Detailed Collections Cards -->
        <div class="card" style="border-left: 5px solid #8b5cf6;">
            <h4>Ingresos Reales (Caja)</h4>
            <div id="val-ingresos-detalles" style="font-size: 0.9em; margin-top: 5px;">
                Cargando...
            </div>
        </div>

        <div class="card" style="border-left: 5px solid var(--primary-color);">
            <h4>Ventas Totales (Contable)</h4>
            <div class="metric-value" id="val-ventas">$ 0.00</div>
            <small class="text-muted" id="val-ops">0 operaciones</small>
        </div>
        
        <div class="card" style="border-left: 5px solid var(--danger-color);">
            <h4>Gastos</h4>
            <div class="metric-value" id="val-gastos" style="color: var(--danger-color);">$ 0.00</div>
        </div>

        <div class="card" style="border-left: 5px solid var(--success-color);">
            <h4>Ganancia Neta</h4>
            <div class="metric-value" id="val-ganancia" style="color: var(--success-color);">$ 0.00</div>
            <small class="text-muted">Ventas - Costos - Gastos</small>
        </div>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h4>Desglose por Metodo</h4>
        <div id="breakdown-methods" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px;">
            <!-- JS populated -->
        </div>
    </div>
</div>

<script>
document.getElementById('nav-reportes').classList.add('active'); // Will add to sidebar below
document.getElementById('report-date').valueAsDate = new Date();

const RPT = {
    load: async () => {
        const type = document.getElementById('report-type').value;
        const date = document.getElementById('report-date').value;
        
        const res = await fetch(`api/reportes.php?type=${type}&date=${date}`);
        const data = await res.json();
        
        const fmt = (ars, usd) => `
            <div>${App.formatCurrency(ars)} <small class="text-muted">ARS</small></div>
            <div style="font-size: 0.6em; opacity: 0.8;">$ ${parseFloat(usd).toFixed(2)} USD</div>
        `;

        // Ingresos Reales
        document.getElementById('val-ingresos-detalles').innerHTML = `
            <div style="color: #10b981;">USD: $ ${parseFloat(data.ingresos_fisicos_usd).toFixed(2)}</div>
            <div style="color: #3b82f6;">ARS: ${App.formatCurrency(data.ingresos_fisicos_ars)}</div>
            <div style="border-top: 1px solid #333; margin-top: 5px; padding-top: 2px; font-weight: bold;">
                Total (~ARS): ${App.formatCurrency(data.total_convertido_ars)}
            </div>
        `;

        document.getElementById('val-ventas').innerHTML = fmt(data.ventas_ars, data.ventas_usd);
        document.getElementById('val-ops').innerText = `${data.operaciones} operaciones`;
        
        document.getElementById('val-gastos').innerHTML = fmt(data.gastos_ars, data.gastos_usd);
        // Costos hidden in UI grid now to save space, but data available if needed.
        
        document.getElementById('val-ganancia').innerHTML = fmt(data.ganancia_neta_ars, data.ganancia_neta_usd);
        
        // Breakdown Methods
        const methodsDiv = document.getElementById('breakdown-methods');
        methodsDiv.innerHTML = '';
        if (data.metodos_pago) {
            for (const [method, vals] of Object.entries(data.metodos_pago)) {
                let details = '';
                if (vals.ars > 0) details += `<div>ARS: ${App.formatCurrency(vals.ars)}</div>`;
                if (vals.usd > 0) details += `<div style="color: green;">USD: $ ${parseFloat(vals.usd).toFixed(2)}</div>`;
                
                if (details === '') details = '<small class="text-muted">Sin movimientos</small>';

                methodsDiv.innerHTML += `
                    <div style="background: var(--bg-color); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); flex: 1; min-width: 150px;">
                        <strong>${method}</strong>
                        <div><small class="text-muted">${vals.count} op</small></div>
                        <div style="margin-top: 5px; font-weight: bold;">
                           ${details}
                        </div>
                    </div>
                `;
            }
        }
    }
};

document.getElementById('report-type').addEventListener('change', RPT.load);
document.getElementById('report-date').addEventListener('change', RPT.load);

// Init
RPT.load();
</script>

</main>
</div>
</body>
</html>
