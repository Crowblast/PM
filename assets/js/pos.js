// assets/js/pos.js

const POS = {
    cart: [],
    dolarRate: 0,
    currentMethod: 'EFECTIVO',
    clients: [],

    init: async () => {
        // Read from the sidebar input which has the effective rate
        const d = document.getElementById('sidebar-dolar-input');
        POS.dolarRate = d ? parseFloat(d.value) : 1200;

        // Initial totals update
        POS.updateTotals();

        POS.setupSearch();
        POS.loadClients();
    },

    loadClients: async () => {
        const res = await fetch('api/clientes.php');
        POS.clients = await res.json();
        const sel = document.getElementById('client-select');
        POS.clients.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.innerText = `${c.nombre} (${c.dni})`;
            sel.appendChild(opt);
        });
    },

    setupSearch: () => {
        const inp = document.getElementById('pos-search');
        const resBox = document.getElementById('search-results');

        inp.addEventListener('input', async (e) => {
            if (e.target.value.length < 2) {
                resBox.style.display = 'none';
                return;
            };

            const res = await fetch(`api/productos.php?q=${e.target.value}`);
            const prods = await res.json();

            resBox.innerHTML = '';
            resBox.style.display = prods.length ? 'block' : 'none';

            prods.forEach(p => {
                const div = document.createElement('div');
                div.style.padding = '10px';
                div.style.borderBottom = '1px solid var(--border-color)';
                div.style.cursor = 'pointer';
                div.innerHTML = `<strong>${p.nombre}</strong> <span class="text-muted">| Stock: ${p.stock} | $${p.precio_usd}</span>`;
                div.onclick = async () => {
                    // Visual feedback
                    div.style.backgroundColor = 'var(--primary-color)';
                    div.style.color = 'white';

                    await POS.addToCart(p);

                    inp.value = '';
                    resBox.style.display = 'none';
                };
                div.onmouseover = () => div.style.backgroundColor = 'var(--bg-color)';
                div.onmouseout = () => div.style.backgroundColor = 'transparent';
                resBox.appendChild(div);
            });
        });
    },

    addToCart: (prod) => {
        // If adding a custom item (no ID), just add it.
        // But if filtering standard items, check ID.
        if (prod.is_custom) {
            POS.cart.push({ ...prod, cantidad: 1 });
            POS.renderCart();
            return;
        }

        const existing = POS.cart.find(i => i.id === prod.id && !i.is_custom);
        if (existing) {
            if (existing.cantidad < prod.stock) {
                existing.cantidad++;
            } else {
                POS.showMessage('No hay suficiente stock', true);
            }
        } else {
            POS.cart.push({ ...prod, cantidad: 1 });
        }
        POS.renderCart();
    },

    addCustomItem: (desc, price, currency) => {
        // Convert to USD if needed
        let priceUsd = price;
        if (currency === 'ARS') {
            priceUsd = price / POS.dolarRate;
        }

        const item = {
            id: 'custom_' + Date.now(),
            nombre: desc,
            precio_usd: priceUsd,
            stock: 9999, // Infinite stock for custom items
            cantidad: 1,
            is_custom: true
        };
        POS.cart.push(item);
        POS.renderCart();
    },

    renderCart: () => {
        const tbody = document.getElementById('cart-body');
        const msg = document.getElementById('empty-cart-msg');

        tbody.innerHTML = '';
        if (POS.cart.length === 0) {
            msg.style.display = 'block';
            POS.updateTotals();
            return;
        }
        msg.style.display = 'none';

        POS.cart.forEach((item, idx) => {
            const usdSub = item.precio_usd * item.cantidad;
            const arsSub = usdSub * POS.dolarRate;

            tbody.innerHTML += `
                <tr>
                    <td>${item.nombre}</td>
                    <td>
                        <input type="number" min="1" max="${item.is_custom ? 9999 : item.stock}" value="${item.cantidad}" 
                        style="width: 60px; padding: 5px; margin: 0;"
                        onchange="POS.updateQty(${idx}, this.value)">
                    </td>
                    <td>$${item.precio_usd}</td>
                    <td>$${App.formatCurrency(arsSub)}</td>
                    <td><button class="btn" style="color: var(--danger-color); padding: 5px;" onclick="POS.remove(${idx})">x</button></td>
                </tr>
            `;
        });
        POS.updateTotals();
    },

    updateQty: (idx, val) => {
        val = parseInt(val);
        if (val > 0 && val <= POS.cart[idx].stock) {
            POS.cart[idx].cantidad = val;
            POS.renderCart();
        }
    },

    remove: (idx) => {
        POS.cart.splice(idx, 1);
        POS.renderCart();
    },

    updateTotals: () => {
        const totalUsd = POS.cart.reduce((acc, item) => acc + (item.precio_usd * item.cantidad), 0);
        const totalArs = totalUsd * POS.dolarRate;

        document.getElementById('total-usd').innerText = `$ ${totalUsd.toFixed(2)}`;
        document.getElementById('total-ars').innerText = App.formatCurrency(totalArs);
    },

    clearCart: () => {
        POS.cart = [];
        POS.renderCart();
    },

    updateGlobalRate: (val) => {
        const newRate = parseFloat(val);
        if (isNaN(newRate) || newRate <= 0) {
            const input = document.getElementById('sidebar-dolar-input');
            if (input) input.value = POS.dolarRate;
            return;
        }
        POS.dolarRate = newRate;
        POS.updateTotals();
    },

    openCheckout: () => {
        if (POS.cart.length === 0) return POS.showMessage('El carrito está vacío', true);
        document.getElementById('checkout-modal').style.display = 'flex';
        POS.payCurrency = 'ARS'; // Default
        document.getElementById('pay-currency-select').value = 'ARS';
        POS.toggleCurrency('ARS');
        POS.setMethod('EFECTIVO');

        // Reset inputs
        document.getElementById('payment-amount').value = '';
        document.getElementById('change-display').innerText = '$ 0.00';
    },

    updateRate: (val) => {
        const newRate = parseFloat(val);
        if (isNaN(newRate) || newRate <= 0) {
            // Revert if invalid
            document.getElementById('checkout-rate-input').value = POS.dolarRate;
            return;
        }
        POS.dolarRate = newRate;
        // Update both main screen totals and modal totals
        POS.updateTotals();
        POS.toggleCurrency(POS.payCurrency);
    },

    toggleCurrency: (curr) => {
        POS.payCurrency = curr;
        document.getElementById('pay-currency-label').innerText = curr;

        // Update Total Display in Checkout
        const totalUsd = POS.cart.reduce((acc, item) => acc + (item.precio_usd * item.cantidad), 0);
        const totalArs = totalUsd * POS.dolarRate;

        // Display correct total
        const finalTotal = curr === 'USD' ? totalUsd : totalArs;

        // Format
        if (curr === 'USD') {
            document.getElementById('checkout-total-display').innerText = `$ ${finalTotal.toFixed(2)}`;
        } else {
            document.getElementById('checkout-total-display').innerText = App.formatCurrency(finalTotal);
        }

        POS.calcChange();
        if (POS.currentMethod === 'CREDITO') POS.calcCredit();
    },

    setMethod: (m) => {
        POS.currentMethod = m;
        document.querySelectorAll('.method-btn').forEach(b => {
            b.style.backgroundColor = 'var(--bg-color)';
            b.style.color = 'var(--text-color)';
        });

        // Updated matching to include 'PERSONAL' or just 'CREDITO' check
        const activeBtn = Array.from(document.querySelectorAll('.method-btn')).find(b => b.innerText.toUpperCase().includes(m === 'EFECTIVO' ? 'EFECTIVO' : (m === 'TRANSFERENCIA' ? 'TRANSFERENCIA' : 'CRÉDITO')));
        if (activeBtn) {
            activeBtn.style.backgroundColor = 'var(--primary-color)';
            activeBtn.style.color = 'white';
        }

        document.getElementById('credit-options').style.display = m === 'CREDITO' ? 'block' : 'none';
        document.getElementById('cash-options').style.display = (m === 'EFECTIVO' || m === 'TRANSFERENCIA') ? 'block' : 'none';

        if (m === 'CREDITO') {
            POS.calcCredit(); // Auto calculate when switching to credit
        }
    },

    calcChange: () => {
        const totalUSD = parseFloat(document.getElementById('total-usd').innerText.replace('$ ', ''));
        const totalARS = parseFloat(document.getElementById('total-ars').innerText.replace(/[$. ]/g, '').replace(',', '.'));
        const total = POS.payCurrency === 'USD' ? totalUSD : totalARS;

        const pay = parseFloat(document.getElementById('payment-amount').value) || 0;
        const change = pay - total;

        const disp = document.getElementById('change-display');
        const changeText = POS.payCurrency === 'USD' ? `$ ${Math.abs(change).toFixed(2)}` : App.formatCurrency(Math.abs(change));

        if (change >= 0) {
            disp.style.color = 'var(--success-color)';
            disp.innerText = changeText;
        } else {
            disp.style.color = 'var(--danger-color)';
            disp.innerText = 'Falta: ' + changeText;
        }
    },

    calcCredit: () => {
        const isUSD = POS.payCurrency === 'USD';
        const totalOriginal = POS.cart.reduce((acc, i) => {
            const price = isUSD ? i.precio_usd : (i.precio_usd * POS.dolarRate);
            return acc + (price * i.cantidad);
        }, 0);

        const interest = parseFloat(document.getElementById('cred-interest').value) || 0;
        const installments = parseInt(document.getElementById('cred-installments').value);

        const totalFinanced = totalOriginal * (1 + (interest / 100));
        const installmentVal = totalFinanced / installments;

        const totalText = isUSD ? `$ ${totalFinanced.toFixed(2)}` : App.formatCurrency(totalFinanced);
        const instText = isUSD ? `$ ${installmentVal.toFixed(2)}` : App.formatCurrency(installmentVal);

        document.getElementById('cred-total-final').innerText = totalText;
        document.getElementById('cred-installment-val').innerText = instText;
    },

    generatePDF: (type = 'TICKET') => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const isUSD = POS.payCurrency === 'USD';

        // Header
        doc.setFontSize(18);
        doc.text("PM celulares", 14, 20);

        doc.setFontSize(10);
        doc.text(type === 'TICKET' ? "COMPROBANTE DE VENTA" : "PRESUPUESTO", 14, 28);
        doc.text("Fecha: " + new Date().toLocaleString(), 14, 34);

        // Client
        const clientSel = document.getElementById('client-select');
        const clientName = clientSel.options[clientSel.selectedIndex].text;
        doc.text("Cliente: " + clientName, 14, 40);

        // AutoTable
        // We show the currency selected for payment/quote
        // If USD, show USD prices. If ARS, show ARS prices.
        // Actually for Presupuesto (Quote), maybe we want to show both? Or just selected?
        // Let's use the current selected currency in the checkout modal (if open) or default ARS?
        // Wait, generatePDF('PRESUPUESTO') is called from main view, not modal. 
        // So for Quote, we don't know "payCurrency" yet unless we moved the button?
        // The button is in the main view.
        // Let's default Quotes to ARS for now, or add a prompt? 
        // Simplification: Quotes in ARS (Standard). 
        // IF we are in the modal (Ticket), use POS.payCurrency.

        const currency = type === 'TICKET' ? POS.payCurrency : 'ARS';
        const symbol = currency === 'USD' ? 'USD ' : 'ARS ';

        const tableData = POS.cart.map(item => {
            const price = currency === 'USD' ? item.precio_usd : (item.precio_usd * POS.dolarRate);
            const total = price * item.cantidad;
            return [
                item.nombre + (item.is_custom ? ' (Manual)' : ''),
                item.cantidad,
                currency === 'USD' ? `$ ${price.toFixed(2)}` : App.formatCurrency(price),
                currency === 'USD' ? `$ ${total.toFixed(2)}` : App.formatCurrency(total)
            ];
        });

        doc.autoTable({
            startY: 45,
            head: [['Producto', 'Cant', `Unitario (${currency})`, `Total (${currency})`]],
            body: tableData,
            theme: 'grid',
            headStyles: { fillColor: [37, 99, 235] }
        });

        // Totals
        const finalY = doc.lastAutoTable.finalY + 10;

        let totalText = '';
        if (currency === 'USD') {
            totalText = document.getElementById('total-usd').innerText;
        } else {
            totalText = document.getElementById('total-ars').innerText;
        }

        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text(`Total: ${totalText}`, 140, finalY);

        if (type === 'TICKET') {
            doc.setFontSize(10);
            const pay = parseFloat(document.getElementById('payment-amount').value) || 0;
            const change = document.getElementById('change-display').innerText; // Already formatted

            if (POS.currentMethod === 'EFECTIVO') {
                doc.setFont("helvetica", "normal");
                const payFmt = currency === 'USD' ? `$ ${pay.toFixed(2)}` : App.formatCurrency(pay);
                doc.text(`Abonado: ${payFmt}`, 140, finalY + 6);
                doc.text(`Vuelto: ${change.replace('Falta: ', '-')}`, 140, finalY + 12);
            } else if (POS.currentMethod === 'CREDITO') {
                doc.setFont("helvetica", "normal");
                const installments = document.getElementById('cred-installments').value;
                const totalFinanced = document.getElementById('cred-total-final').innerText;
                const modalCuota = document.getElementById('cred-installment-val').innerText;

                doc.text(`Método: Crédito Personal`, 140, finalY + 6);
                doc.text(`Plan: ${installments} Cuotas`, 140, finalY + 12);
                doc.text(`Total Financiado: ${totalFinanced}`, 140, finalY + 18);
                doc.text(`Valor Cuota: ${modalCuota}`, 140, finalY + 24);
            } else if (POS.currentMethod === 'TRANSFERENCIA') {
                doc.setFont("helvetica", "normal");
                doc.text(`Método: Transferencia Bancaria`, 140, finalY + 6);
            }
        }

        // Save
        doc.save(`pm_celulares_${type.toLowerCase()}_${Date.now()}.pdf`);
    },

    // Helpers for Modals
    showMessage: (msg, isError = false) => {
        const m = document.getElementById('msg-modal');
        document.getElementById('msg-modal-title').innerText = isError ? 'Error' : 'Aviso';
        document.getElementById('msg-modal-title').style.color = isError ? 'var(--danger-color)' : 'var(--text-color)';
        document.getElementById('msg-modal-text').innerText = msg;
        m.style.display = 'flex';
    },

    showConfirm: (msg, onYes) => {
        const m = document.getElementById('confirm-modal');
        document.getElementById('confirm-modal-text').innerText = msg;
        m.style.display = 'flex';

        document.getElementById('btn-confirm-yes').onclick = () => {
            m.style.display = 'none';
            onYes();
        };
        document.getElementById('btn-confirm-no').onclick = () => {
            m.style.display = 'none';
            location.reload(); // Default reload on No for this flow (post-sale)
        };
    },

    processSale: async () => {
        // Validation for change if cash
        if (POS.currentMethod === 'EFECTIVO') {
            const totalUSD = parseFloat(document.getElementById('total-usd').innerText.replace('$ ', ''));
            const totalARS = parseFloat(document.getElementById('total-ars').innerText.replace(/[$. ]/g, '').replace(',', '.'));
            const total = POS.payCurrency === 'USD' ? totalUSD : totalARS;

            const pay = parseFloat(document.getElementById('payment-amount').value) || 0;
            // if (pay < total && pay > 0) ... // Soft warning logic exists but commented out
        }

        const payload = {
            cliente_id: document.getElementById('client-select').value || null,
            total_usd: parseFloat(document.getElementById('total-usd').innerText.replace('$ ', '')),
            total_ars: parseFloat(document.getElementById('total-ars').innerText.replace(/[$. ]/g, '').replace(',', '.')),
            tipo_pago: POS.currentMethod,
            items: POS.cart,
            credito: null,
            moneda_pago: POS.payCurrency
        };

        if (POS.currentMethod === 'CREDITO') {
            if (!payload.cliente_id) return POS.showMessage('Debe seleccionar un cliente para vender a crédito', true);

            const interest = parseFloat(document.getElementById('cred-interest').value) || 0;
            const installments = parseInt(document.getElementById('cred-installments').value);
            const roughArs = POS.cart.reduce((acc, i) => acc + (i.precio_usd * i.cantidad * POS.dolarRate), 0);

            payload.total_ars = roughArs;
            const totalFinanced = roughArs * (1 + (interest / 100));

            payload.credito = {
                interes: interest,
                cuotas: installments,
                total_financiado: totalFinanced
            };
        }

        const res = await fetch('api/ventas.php', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: { 'Content-Type': 'application/json' }
        });

        const data = await res.json();
        if (data.success) {
            document.getElementById('checkout-modal').style.display = 'none'; // Close checkout
            POS.showConfirm('Venta registrada exitosamente. ¿Desea imprimir el comprobante?', () => {
                POS.generatePDF('TICKET');
                setTimeout(() => location.reload(), 1500);
            });
        } else {
            POS.showMessage('Error: ' + data.error, true);
        }
    }
};

document.addEventListener('DOMContentLoaded', POS.init);
