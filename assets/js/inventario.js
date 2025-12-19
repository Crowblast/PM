// assets/js/inventario.js

const Inventario = {
    dolarRate: 0,

    init: async () => {
        // Obtener valor del dolar desde el header (mejor que fetch extra si ya esta ahi)
        const dolarEl = document.getElementById('global-dolar');
        if (dolarEl) {
            // remove commas/formatting to parse
            Inventario.dolarRate = parseFloat(dolarEl.innerText.replace(',', ''));
            document.getElementById('modal-dolar').innerText = dolarEl.innerText;
        }

        await Inventario.loadProducts();
        await Inventario.loadCategoriesList();

        // Listeners
        document.getElementById('product-form').addEventListener('submit', Inventario.saveProduct);
        document.getElementById('search-input').addEventListener('input', (e) => Inventario.loadProducts(e.target.value));
    },

    loadProducts: async (query = '') => {
        const url = query ? `api/productos.php?q=${query}` : 'api/productos.php';
        const res = await fetch(url);
        const products = await res.json();

        const tbody = document.querySelector('#products-table tbody');
        tbody.innerHTML = '';

        products.forEach(p => {
            const tr = document.createElement('tr');

            const imgHtml = p.imagen
                ? `<img src="${p.imagen}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">`
                : '<span style="font-size: 20px;">📱</span>';

            tr.innerHTML = `
                <td>${imgHtml}</td>
                <td>${p.codigo || '-'}</td>
                <td><strong>${p.nombre}</strong></td>
                <td>${p.marca || ''}</td>
                <td style="color: ${p.stock < 5 ? 'var(--danger-color)' : 'inherit'}">${p.stock}</td>
                <td>$${p.costo_usd}</td>
                <td style="font-weight: bold;">$${p.precio_usd}</td>
                <td style="color: var(--success-color);">$${App.formatCurrency(p.precio_usd * Inventario.dolarRate)}</td>
                <td>
                    <button class="btn" style="padding: 5px 10px; font-size: 0.8em;" onclick='Inventario.editProduct(${JSON.stringify(p)})'>✏️</button>
                    <button class="btn" style="padding: 5px 10px; font-size: 0.8em; color: var(--danger-color);" onclick="Inventario.deleteProduct(${p.id})">🗑️</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    },

    toggleCurrency: () => {
        const mode = document.querySelector('input[name="moneda_input"]:checked').value;
        const uiCosto = document.getElementById('ui_costo');
        const uiPrecio = document.getElementById('ui_precio');

        // Update Labels
        document.getElementById('label-costo').innerText = `Costo (${mode})`;
        document.getElementById('label-precio').innerText = `Precio Elemento (${mode})`;
        document.getElementById('equiv-currency').innerText = mode === 'USD' ? 'ARS' : 'USD';

        // Convert existing UI values if we are switching
        // Logic: Hidden values are always USD.
        // If switching to ARS: ui = hidden * rate
        // If switching to USD: ui = hidden
        const hiddenCosto = parseFloat(document.getElementById('hidden_costo').value) || 0;
        const hiddenPrecio = parseFloat(document.getElementById('hidden_precio').value) || 0;

        if (mode === 'ARS') {
            uiCosto.value = (hiddenCosto * Inventario.dolarRate).toFixed(2);
            uiPrecio.value = (hiddenPrecio * Inventario.dolarRate).toFixed(2);
        } else {
            uiCosto.value = hiddenCosto.toFixed(2);
            uiPrecio.value = hiddenPrecio.toFixed(2);
        }

        Inventario.calcPrices();
    },

    calcPrices: () => {
        const mode = document.querySelector('input[name="moneda_input"]:checked').value;
        const uiCostoVal = parseFloat(document.getElementById('ui_costo').value) || 0;
        const uiPrecioVal = parseFloat(document.getElementById('ui_precio').value) || 0;

        const hiddenCosto = document.getElementById('hidden_costo');
        const hiddenPrecio = document.getElementById('hidden_precio');

        let usdCosto = 0, usdPrecio = 0;
        let equivCosto = 0, equivPrecio = 0;

        if (mode === 'USD') {
            usdCosto = uiCostoVal;
            usdPrecio = uiPrecioVal;
            equivCosto = uiCostoVal * Inventario.dolarRate;
            equivPrecio = uiPrecioVal * Inventario.dolarRate;
        } else {
            // ARS Input -> Convert back to USD for storage
            usdCosto = uiCostoVal / Inventario.dolarRate;
            usdPrecio = uiPrecioVal / Inventario.dolarRate;
            equivCosto = usdCosto; // Equivalent is USD in this case? No, Equivalent Display is the OTHER one.
            // Wait, logic check:
            // Input USD -> Equiv is ARS
            // Input ARS -> Equiv is USD
        }

        // Update Hidden (Always USD)
        hiddenCosto.value = usdCosto; // Store precise val
        hiddenPrecio.value = usdPrecio;

        // Update Equivalent Display
        const equivSymbol = mode === 'USD' ? '$' : 'US$';
        const displayVal = (val) => {
            if (mode === 'USD') return App.formatCurrency(val); // ARS formatted
            return val.toFixed(2); // USD simple
        };

        if (mode === 'USD') {
            // Equiv is ARS
            document.getElementById('equiv-costo').innerText = App.formatCurrency(equivCosto);
            document.getElementById('equiv-precio').innerText = App.formatCurrency(equivPrecio);
        } else {
            // Equiv is USD
            document.getElementById('equiv-costo').innerText = 'US$ ' + usdCosto.toFixed(2);
            document.getElementById('equiv-precio').innerText = 'US$ ' + usdPrecio.toFixed(2);
        }
    },

    openModal: () => {
        document.getElementById('product-form').reset();
        document.getElementById('prod-id').value = '';
        document.getElementById('modal-title').innerText = 'Nuevo Producto';

        // Default to USD
        document.querySelector('input[name="moneda_input"][value="USD"]').checked = true;
        Inventario.toggleCurrency(); // Reset UI

        document.getElementById('product-modal').style.display = 'flex';
    },

    closeModal: () => {
        document.getElementById('product-modal').style.display = 'none';
    },

    openCategoryModal: async () => {
        document.getElementById('category-modal').style.display = 'flex';
        await Inventario.loadCategoriesList();
    },

    loadCategoriesList: async () => {
        const res = await fetch('api/categorias.php');
        const categories = await res.json();

        // Populate modal list
        const list = document.getElementById('cat-list');
        list.innerHTML = '';

        // Populate select for product form
        const select = document.getElementById('prod-categoria');
        select.innerHTML = '<option value="">General</option>';

        categories.forEach(c => {
            // Modal List Item
            const li = document.createElement('li');
            li.style.padding = '10px';
            li.style.borderBottom = '1px solid var(--border-color)';
            li.style.display = 'flex';
            li.style.justifyContent = 'space-between';
            li.innerHTML = `
                <span>${c.nombre}</span>
                <button style="border: none; background: transparent; color: var(--danger-color); cursor: pointer;" onclick="Inventario.deleteCategory(${c.id})">🗑️</button>
            `;
            list.appendChild(li);

            // Select Option
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.innerText = c.nombre;
            select.appendChild(opt);
        });
    },

    addCategory: async () => {
        const input = document.getElementById('new-cat-name');
        const name = input.value.trim();
        if (!name) return;

        const res = await fetch('api/categorias.php', {
            method: 'POST',
            body: JSON.stringify({ nombre: name }),
            headers: { 'Content-Type': 'application/json' }
        });

        if (res.ok) {
            input.value = '';
            await Inventario.loadCategoriesList();
        } else {
            alert('Error al crear categoría');
        }
    },

    deleteCategory: async (id) => {
        if (!confirm('¿Borrar categoría? Solo se puede si no tiene productos.')) return;

        const res = await fetch(`api/categorias.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();

        if (data.success) {
            await Inventario.loadCategoriesList();
        } else {
            alert(data.error || 'Error al eliminar');
        }
    },

    editProduct: (product) => {
        Inventario.openModal();
        document.getElementById('modal-title').innerText = 'Editar Producto';
        const form = document.getElementById('product-form');

        // Populate form
        document.getElementById('prod-id').value = product.id;
        form.codigo.value = product.codigo;
        form.nombre.value = product.nombre;
        form.marca.value = product.marca;
        form.stock.value = product.stock;
        form.categoria_id.value = product.categoria_id || "";

        // Populate HIDDEN values
        document.getElementById('hidden_costo').value = product.costo_usd;
        document.getElementById('hidden_precio').value = product.precio_usd;

        // Populate visible values (Default USD)
        // Since toggleCurrency uses values from hidden if available, we just call it
        document.querySelector('input[name="moneda_input"][value="USD"]').checked = true;
        Inventario.toggleCurrency();
    },

    saveProduct: async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const res = await fetch('api/productos.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                Inventario.closeModal();
                Inventario.loadProducts();
            } else {
                alert('Error al guardar: ' + (data.error || 'Desconocido'));
            }
        } catch (err) {
            console.error(err);
            alert('Error de red');
        }
    },

    deleteProduct: async (id) => {
        if (!confirm('¿Seguro de eliminar este producto?')) return;

        await fetch('api/productos.php', {
            method: 'DELETE',
            body: `id=${id}`, // simple form urlencoded
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        Inventario.loadProducts();
    }
};

document.addEventListener('DOMContentLoaded', Inventario.init);
