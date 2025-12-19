<?php include 'includes/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Gestión de Inventario</h2>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="search-input" placeholder="🔍 Buscar producto..." style="margin-bottom: 0; width: 250px;">
        <button class="btn" style="background: var(--card-bg); border: 1px solid var(--border-color);" onclick="Inventario.openCategoryModal()">📂 Categorías</button>
        <button class="btn btn-primary" onclick="Inventario.openModal()">+ Nuevo Producto</button>
    </div>
</div>

<!-- Products Table -->
<div class="card table-container">
    <table id="products-table">
        <thead>
            <tr>
                <th>Img</th>
                <th>Código</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Stock</th>
                <th>Costo (USD)</th>
                <th>Precio (USD)</th>
                <th>Precio (ARS)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loaded via JS -->
        </tbody>
    </table>
</div>

<!-- Modal Form -->
<div id="product-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 500px; max-width: 90%; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-bottom: 20px;" id="modal-title">Nuevo Producto</h3>
        
        <form id="product-form">
            <input type="hidden" name="id" id="prod-id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Código</label>
                    <input type="text" name="codigo" required>
                </div>
                <div>
                    <label>Marca</label>
                    <input type="text" name="marca">
                </div>
            </div>

            <label>Nombre del Producto</label>
            <input type="text" name="nombre" required>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Stock Inicial</label>
                    <input type="number" name="stock" value="0" required>
                </div>
                <div>
                    <label>Categoría</label>
                    <select name="categoria_id" id="prod-categoria">
                        <option value="">General</option>
                        <!-- Loaded via JS -->
                    </select>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border-color); margin: 15px 0; padding-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="color: var(--primary-color); margin: 0;">Precios</h4>
                    <div style="background: var(--bg-color); padding: 4px; border-radius: 6px; display: flex; gap: 5px; border: 1px solid var(--border-color);">
                        <label style="margin: 0; padding: 4px 10px; cursor: pointer; border-radius: 4px; font-size: 0.9em; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="moneda_input" value="USD" checked onchange="Inventario.toggleCurrency()"> 🇺🇸 USD
                        </label>
                        <label style="margin: 0; padding: 4px 10px; cursor: pointer; border-radius: 4px; font-size: 0.9em; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="moneda_input" value="ARS" onchange="Inventario.toggleCurrency()"> 🇦🇷 ARS
                        </label>
                    </div>
                </div>

                <!-- Hidden inputs for submission (Always USD) -->
                <input type="hidden" name="costo_usd" id="hidden_costo">
                <input type="hidden" name="precio_usd" id="hidden_precio">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label id="label-costo">Costo (USD)</label>
                        <input type="number" step="0.01" id="ui_costo" required oninput="Inventario.calcPrices()">
                    </div>
                    <div>
                        <label id="label-precio">Precio Venta (USD)</label>
                        <input type="number" step="0.01" id="ui_precio" required oninput="Inventario.calcPrices()">
                    </div>
                </div>
                
                <div style="background: var(--bg-color); padding: 10px; border-radius: 6px; margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <small class="text-muted">Equivalente en <span id="equiv-currency">ARS</span></small>
                        <small class="text-muted">Cotización: <span id="modal-dolar"></span></small>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: bold;">
                        <span>Costo: <span id="equiv-costo">-</span></span>
                        <span>Venta: <span id="equiv-precio" style="color: var(--success-color); font-size: 1.1em;">-</span></span>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label>Imágen</label>
                <input type="file" name="imagen" accept="image/*">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="Inventario.closeModal()" style="background: transparent; border: 1px solid var(--border-color);">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Categories -->
<div id="category-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; max-width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Gestionar Categorías</h3>
            <button class="btn" style="padding: 5px;" onclick="document.getElementById('category-modal').style.display='none'">✕</button>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <input type="text" id="new-cat-name" placeholder="Nueva categoría..." style="margin-bottom: 0;">
            <button class="btn btn-primary" onclick="Inventario.addCategory()">Agregar</button>
        </div>

        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 6px;">
            <ul id="cat-list" style="list-style: none;">
                <!-- Loaded via JS -->
            </ul>
        </div>
    </div>
</div>

<script src="assets/js/inventario.js"></script>

<!-- Initialize -->
<script>
    document.getElementById('nav-inventario').classList.add('active');
</script>

</main>
</div>
</body>
</html>
