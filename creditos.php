<?php include 'includes/header.php'; ?>

<h2>Gestión de Créditos</h2>
<div class="card table-container">
    <table id="creditos-table">
        <thead>
            <tr>
                <th>ID Venta</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total Financiado</th>
                <th>Saldo Restante</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="creditos-body">
            <!-- JS Loads -->
        </tbody>
    </table>
</div>

<!-- Modal Detalle Cuotas -->
<div id="cuotas-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 600px; max-height: 90vh; overflow-y: auto;">
        <h3>Plan de Pagos</h3>
        <p id="modal-cli-name" class="text-muted"></p>
        
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="cuotas-body"></tbody>
        </table>

        <div style="margin-top: 20px; text-align: right;">
            <button class="btn" onclick="document.getElementById('cuotas-modal').style.display='none'">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Pago Custom -->
<div id="pago-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1100; align-items: center; justify-content: center;">
    <div class="card" style="width: 300px;">
        <h3>Registrar Pago</h3>
        <p class="text-muted">Ingrese el monto a abonar:</p>
        
        <input type="number" id="pago-input-monto" class="form-control" style="width: 100%; margin: 10px 0; padding: 10px; font-size: 1.2em;" step="0.01">
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
            <button class="btn" onclick="document.getElementById('pago-modal').style.display='none'">Cancelar</button>
            <button class="btn btn-primary" id="btn-confirm-pago">Confirmar</button>
        </div>
    </div>
</div>

<script>
document.getElementById('nav-creditos').classList.add('active');

const Creditos = {
    load: async () => {
        const res = await fetch('api/creditos.php');
        const data = await res.json();
        const tbody = document.getElementById('creditos-body');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">No hay créditos activos.</td></tr>';
            return;
        }

        data.forEach(c => {
            let estadoHtml = `<span style="color: var(--success-color); font-weight: bold;">${c.estado}</span>`;
            if (c.estado === 'MORA') estadoHtml = `<span style="color: var(--danger-color); font-weight: bold;">EN MORA</span>`;
            
            tbody.innerHTML += `
                <tr>
                    <td>#${c.venta_id}</td>
                    <td>${c.cliente_nombre}</td>
                    <td>${c.fecha_venta}</td>
                    <td>${App.formatCurrency(c.total_a_pagar)}</td>
                    <td>${App.formatCurrency(c.saldo_restante)}</td>
                    <td>${estadoHtml}</td>
                    <td>
                        <button class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8em;" 
                        onclick="Creditos.verCuotas(${c.id}, '${c.cliente_nombre}')">Ver Cuotas</button>
                    </td>
                </tr>
            `;
        });
    },

    verCuotas: async (creditoId, clienteName) => {
        document.getElementById('modal-cli-name').innerText = clienteName;
        document.getElementById('cuotas-modal').style.display = 'flex';
        
        const res = await fetch(`api/creditos.php?id=${creditoId}`);
        const cuotas = await res.json();
        
        const tbody = document.getElementById('cuotas-body');
        tbody.innerHTML = '';
        
        cuotas.forEach(cuota => {
            let accion = '-';
            let estadoStyles = 'color: var(--text-muted);';
            
            if (cuota.estado === 'PENDIENTE') {
                estadoStyles = 'color: var(--danger-color); font-weight: bold;';
                accion = `<button class="btn" style="background: var(--success-color); color: white; padding: 5px;" 
                          onclick="Creditos.pagarCuota(${cuota.id}, ${creditoId}, '${clienteName}', ${cuota.monto})">Pagar</button>`;
            } else {
                estadoStyles = 'color: var(--success-color);';
                accion = '<span style="font-size: 1.2em;">✅</span>';
            }

            tbody.innerHTML += `
                <tr>
                    <td>${cuota.numero_cuota}</td>
                    <td>${cuota.fecha_vencimiento}</td>
                    <td>${App.formatCurrency(cuota.monto)}</td>
                    <td style="${estadoStyles}">${cuota.estado}</td>
                    <td>${accion}</td>
                </tr>
            `;
        });
    },

    pagarCuota: (cuotaId, creditoId, cliName, montoOriginal) => {
        const modal = document.getElementById('pago-modal');
        const input = document.getElementById('pago-input-monto');
        const btn = document.getElementById('btn-confirm-pago');
        
        input.value = montoOriginal;
        modal.display = 'flex'; // Fix: modal.style.display
        document.getElementById('pago-modal').style.display = 'flex';
        input.focus();
        input.select();
        
        btn.onclick = async () => {
            const monto = parseFloat(input.value);
            if (isNaN(monto) || monto <= 0) return alert('Monto inválido');
            
            // Disable button to prevent double click
            btn.disabled = true;
            btn.innerText = "Procesando...";

            const res = await fetch('api/creditos.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    action: 'pagar_cuota', 
                    credito_id: creditoId, 
                    monto_pago: monto
                }),
                headers: {'Content-Type': 'application/json'}
            });
            
            const data = await res.json();
            btn.disabled = false;
            btn.innerText = "Confirmar";
            
            if (data.success) {
                document.getElementById('pago-modal').style.display = 'none';
                Creditos.verCuotas(creditoId, cliName);
                Creditos.load();
            } else {
                alert('Error al registrar pago: ' + (data.error || 'Desconocido'));
            }
        };
    }
};

document.addEventListener('DOMContentLoaded', Creditos.load);
</script>
</main>
</div>
</body>
</html>
