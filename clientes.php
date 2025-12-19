<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Gestión de Clientes</h2>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="search-cli" placeholder="🔍 Buscar por Nombre o DNI..." style="margin-bottom: 0; width: 250px;">
        <button class="btn btn-primary" onclick="Clientes.openModal()">+ Nuevo Cliente</button>
    </div>
</div>

<div class="card table-container">
    <table id="clientes-table">
        <thead>
            <tr>
                <th>DNI</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Cliente -->
<div id="client-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px;">
        <h3 id="modal-title" style="margin-bottom: 20px;">Nuevo Cliente</h3>
        <form id="client-form">
            <input type="hidden" name="id" id="cli-id">
            
            <label>DNI</label>
            <input type="text" name="dni" required>
            
            <label>Nombre Completo</label>
            <input type="text" name="nombre" required>
            
            <label>Teléfono</label>
            <input type="text" name="telefono">
            
            <label>Dirección</label>
            <input type="text" name="direccion">

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn" onclick="document.getElementById('client-modal').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const Clientes = {
    init: () => {
        Clientes.load();
        document.getElementById('search-cli').addEventListener('input', (e) => Clientes.load(e.target.value));
        document.getElementById('client-form').addEventListener('submit', Clientes.save);
    },

    load: async (q = '') => {
        const res = await fetch(`api/clientes.php${q ? '?q='+q : ''}`);
        const data = await res.json();
        const tbody = document.querySelector('#clientes-table tbody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No hay clientes registrados.</td></tr>';
            return;
        }

        data.forEach(c => {
            tbody.innerHTML += `
                <tr>
                    <td>${c.dni}</td>
                    <td><b>${c.nombre}</b></td>
                    <td>${c.telefono}</td>
                    <td>${c.direccion}</td>
                    <td>
                        <button class="btn" style="padding: 5px;" onclick='Clientes.edit(${JSON.stringify(c)})'>✏️</button>
                    </td>
                </tr>
            `;
        });
    },

    openModal: () => {
        document.getElementById('client-form').reset();
        document.getElementById('cli-id').value = '';
        document.getElementById('modal-title').innerText = 'Nuevo Cliente';
        document.getElementById('client-modal').style.display = 'flex';
    },

    edit: (c) => {
        Clientes.openModal();
        document.getElementById('modal-title').innerText = 'Editar Cliente';
        const f = document.getElementById('client-form');
        document.getElementById('cli-id').value = c.id;
        f.dni.value = c.dni;
        f.nombre.value = c.nombre;
        f.telefono.value = c.telefono;
        f.direccion.value = c.direccion;
    },

    save: async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        const res = await fetch('api/clientes.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {'Content-Type': 'application/json'}
        });
        
        const result = await res.json();

        if (result.success) {
            document.getElementById('client-modal').style.display = 'none';
            Clientes.load();
        } else {
            alert('Error al guardar: ' + (result.error || 'Desconocido'));
        }
    }
};
document.addEventListener('DOMContentLoaded', Clientes.init);
document.getElementById('nav-clientes').classList.add('active');
</script>

</main>
</div>
</body>
</html>
