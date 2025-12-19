<?php include 'includes/header.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Gestión de Gastos</h3>
        <button class="btn btn-primary" onclick="APP.openModal()">+ Nuevo Gasto</button>
    </div>

    <table class="pos-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <th>Monto (ARS)</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="gastos-list">
            <!-- JS populates this -->
        </tbody>
    </table>
</div>

<!-- Add Gasto Modal -->
<div id="gasto-modal" class="overlay" style="z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; position: relative;">
        <h3>Registrar Gasto</h3>
        <form id="gasto-form" style="margin-top: 15px; display: grid; gap: 10px;">
            <div>
                <label>Descripción</label>
                <input type="text" id="desc" required>
            </div>
            <div>
                <label>Monto (ARS)</label>
                <input type="number" id="monto" required step="0.01">
            </div>
            <div>
                <label>Categoría</label>
                <select id="cat">
                    <option value="General">General</option>
                    <option value="Alquiler">Alquiler</option>
                    <option value="Servicios">Servicios</option>
                    <option value="Sueldos">Sueldos</option>
                    <option value="Insumos">Insumos</option>
                    <option value="Otros">Otros</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn" onclick="APP.closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('nav-gastos').classList.add('active'); // Will add this ID to sidebar later

const APP = {
    init: () => {
        APP.loadGastos();
        
        document.getElementById('gasto-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                descripcion: document.getElementById('desc').value,
                monto: document.getElementById('monto').value,
                categoria: document.getElementById('cat').value
            };
            
            await fetch('api/gastos.php', {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: {'Content-Type': 'application/json'}
            });
            APP.closeModal();
            APP.loadGastos();
        });
    },

    loadGastos: async () => {
        const res = await fetch('api/gastos.php');
        const data = await res.json();
        const tbody = document.getElementById('gastos-list');
        tbody.innerHTML = '';
        
        data.forEach(g => {
            tbody.innerHTML += `
                <tr>
                    <td>${g.fecha}</td>
                    <td>${g.descripcion}</td>
                    <td><span class="badge">${g.categoria}</span></td>
                    <td>$ ${parseFloat(g.monto).toLocaleString('es-AR')}</td>
                    <td><button class="btn" style="color: var(--danger-color);" onclick="APP.delete(${g.id})">🗑️</button></td>
                </tr>
            `;
        });
    },

    delete: async (id) => {
        if(confirm('¿Seguro?')) {
            await fetch(`api/gastos.php?id=${id}`, { method: 'DELETE' });
            APP.loadGastos();
        }
    },

    openModal: () => {
        document.getElementById('gasto-modal').style.display = 'flex';
    },

    closeModal: () => {
        document.getElementById('gasto-modal').style.display = 'none';
        document.getElementById('gasto-form').reset();
    }
};

document.addEventListener('DOMContentLoaded', APP.init);
</script>

</main>
</div>
</body>
</html>
