<?php include 'includes/header.php'; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; height: calc(100vh - 100px);">
    
    <!-- LEFT: Product Search & List -->
    <div class="card" style="display: flex; flex-direction: column; overflow: hidden;">
        <div style="padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
            <div style="position: relative;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="pos-search" placeholder="🔍 Buscar producto (Código o Nombre)..." autocomplete="off" style="font-size: 1.1em; padding: 12px; flex: 1;">
                    <button class="btn" onclick="document.getElementById('custom-item-modal').style.display='flex'" style="border: 1px solid var(--border-color); white-space: nowrap;">+ Manual</button>
                </div>
                <div id="search-results" style="position: absolute; top: 100%; left: 0; width: 100%; background: var(--card-bg); border: 1px solid var(--border-color); display: none; max-height: 300px; overflow-y: auto; z-index: 10;"></div>
            </div>
        </div>
        
        <div style="flex: 1; overflow-y: auto; padding-top: 10px;">
            <table class="pos-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th width="80">Cant</th>
                        <th>$ Unit (USD)</th>
                        <th>Subtotal (ARS)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <!-- Cart Items -->
                </tbody>
            </table>
            <div id="empty-cart-msg" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i>🛒</i> El carrito está vacío
            </div>
        </div>
    </div>

    <!-- RIGHT: Totals & Checkout -->
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="margin-bottom: 20px;">Resumen de Venta</h3>
            
            <div style="background: var(--bg-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span class="text-muted">Cotización (ARS)</span>
                    <input type="number" id="sidebar-dolar-input" value="<?php echo $dolar; ?>" 
                           style="width: 100px; text-align: right; padding: 5px; border: 1px solid var(--border-color); border-radius: 4px;"
                           onchange="POS.updateGlobalRate(this.value)">
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span class="text-muted">Total USD</span>
                    <strong id="total-usd">$ 0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.5em; color: var(--primary-color);">
                    <span>Total ARS</span>
                    <strong id="total-ars">$ 0.00</strong>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Cliente (Opcional)</label>
                <select id="client-select" style="width: 100%;">
                    <option value="">Consumidor Final</option>
                </select>
                <small><a href="clientes.php">Gestionar Clientes</a></small>
            </div>
        </div>

        <div style="display: grid; gap: 10px;">
            <button class="btn" style="border: 1px solid var(--border-color); background: transparent;" onclick="POS.clearCart()">Cancelar</button>
            <button class="btn" style="background: #6366f1; color: white;" onclick="POS.generatePDF('PRESUPUESTO')">🖨️ Presupuesto</button>
            <button class="btn btn-primary" style="padding: 15px; font-size: 1.1em;" onclick="POS.openCheckout()">✅ Cobrar</button>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkout-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 600px; max-height: 90vh; overflow-y: auto;">
        <h3>Finalizar Venta</h3>
        <p class="text-muted" style="margin-bottom: 20px;">Seleccione método de pago</p>

        <!-- Payment Currency Selection -->
        <div style="margin-bottom: 20px; text-align: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;">
            <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 10px;">
                <label style="font-weight: bold;">Moneda de Pago:</label>
                <select id="pay-currency-select" onchange="POS.toggleCurrency(this.value)" style="padding: 5px 10px; font-size: 1em; border-radius: 4px; border: 1px solid var(--border-color);">
                    <option value="ARS">Pesos (ARS)</option>
                    <option value="USD">Dólares (USD)</option>
                </select>
            </div>
            <div id="checkout-total-display" style="font-size: 2em; font-weight: bold; margin-top: 10px; color: var(--text-color);">
                $ 0.00
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn btn-primary method-btn" onclick="POS.setMethod('EFECTIVO')">💵 Efectivo</button>
            <button class="btn method-btn" style="background: var(--bg-color); border: 1px solid var(--border-color);" onclick="POS.setMethod('TRANSFERENCIA')">🏦 Transferencia</button>
            <button class="btn method-btn" style="background: var(--bg-color); border: 1px solid var(--border-color);" onclick="POS.setMethod('CREDITO')">💳 Crédito Personal</button>
        </div>

        <!-- Credit Options -->
        <div id="credit-options" style="display: none; background: var(--bg-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-bottom: 10px;">Configurar Financiación</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Recargo (%)</label>
                    <input type="number" id="cred-interest" value="25" onchange="POS.calcCredit()">
                </div>
                <div>
                    <label>Cuotas</label>
                    <select id="cred-installments" onchange="POS.calcCredit()">
                        <option value="1">1 pago (30 días)</option>
                        <option value="3" selected>3 cuotas mensuales</option>
                        <option value="6">6 cuotas mensuales</option>
                        <option value="12">12 cuotas mensuales</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Total Financiado:</span>
                    <strong id="cred-total-final">$ 0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; color: var(--primary-color);">
                    <span>Valor Cuota:</span>
                    <strong id="cred-installment-val">$ 0.00</strong>
                </div>
            </div>
        </div>

        <!-- Cash Payment / Change Calculator -->
        <div id="cash-options" style="background: var(--bg-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Monto Abonado (<span id="pay-currency-label">ARS</span>)</label>
                    <input type="number" id="payment-amount" placeholder="0.00" oninput="POS.calcChange()" style="font-size: 1.2em;">
                </div>
                <div>
                    <label>Su Vuelto</label>
                    <div id="change-display" style="font-size: 1.5em; font-weight: bold; color: var(--success-color); padding-top: 10px;">$ 0.00</div>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button class="btn" onclick="document.getElementById('checkout-modal').style.display='none'">Volver</button>
            <button class="btn btn-primary" onclick="POS.processSale()">Confirmar Venta</button>
        </div>
    </div>
</div>

<!-- Custom Item Modal -->
<div id="custom-item-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px;">
        <h3>Agregar Item Manual</h3>
        <form id="custom-item-form" style="display: grid; gap: 10px; margin-top: 15px;">
            <div>
                <label>Descripción</label>
                <input type="text" id="custom-desc" required placeholder="Ej. Servicio Técnico">
            </div>
            <div>
                <label>Precio</label>
                <input type="number" id="custom-price" step="0.01" required placeholder="0.00">
            </div>
            <div>
                <label>Moneda</label>
                <select id="custom-currency">
                    <option value="USD">USD (Dólares)</option>
                    <option value="ARS">ARS (Pesos)</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="button" class="btn" onclick="document.getElementById('custom-item-modal').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agregar</button>
            </div>
        </form>
    </div>
</div>

<!-- Generic Message Modal -->
<div id="msg-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div class="card" style="width: 300px; text-align: center;">
        <h3 id="msg-modal-title" style="margin-bottom: 10px;">Aviso</h3>
        <p id="msg-modal-text" class="text-muted" style="margin-bottom: 20px;"></p>
        <button class="btn btn-primary" onclick="document.getElementById('msg-modal').style.display='none'">Aceptar</button>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2100; align-items: center; justify-content: center;">
    <div class="card" style="width: 350px; text-align: center;">
        <h3 style="margin-bottom: 15px;">Confirmación</h3>
        <p id="confirm-modal-text" class="text-muted" style="margin-bottom: 25px;"></p>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button class="btn" id="btn-confirm-no" style="min-width: 80px;">No</button>
            <button class="btn btn-primary" id="btn-confirm-yes" style="min-width: 80px;">Sí</button>
        </div>
    </div>
</div>

<!-- Libraries for PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<script src="assets/js/pos.js"></script>
<script>
    document.getElementById('nav-ventas').classList.add('active');
</script>
</main>
</div>
</body>
</html>
