{*
* Generador de Links de Pago Datafast (cobro sin datáfono / sin registro del cliente).
*}
<div class="panel" id="datafast-paylink-panel">
    <div class="panel-heading">
        <i class="icon-link"></i> Links de Pago — Cobra sin datáfono
    </div>

    <p class="text-muted">
        Genera un link, envíalo por WhatsApp/Telegram y tu cliente paga con su tarjeta sin
        registrarse en la tienda. Al pagar se crea automáticamente un pedido.
    </p>

    <form method="POST" action="{$paylink_form_action|escape:'html':'UTF-8'}" id="datafast_paylink_form">
        <input type="hidden" name="submitCreatePaymentLink" value="1">
        <input type="hidden" name="paylink_product_refs" id="paylink_product_refs" value="">

        <div class="form-group">
            <label class="control-label col-lg-3">Tipo de link</label>
            <div class="col-lg-6">
                <label class="radio-inline">
                    <input type="radio" name="paylink_type" value="amount" checked onclick="datafastTogglePaylinkType();"> Monto libre
                </label>
                <label class="radio-inline">
                    <input type="radio" name="paylink_type" value="catalog" onclick="datafastTogglePaylinkType();"> Productos del catálogo
                </label>
            </div>
        </div>

        {* ---- Monto libre ---- *}
        <div id="paylink-amount-block">
            <div class="form-group">
                <label class="control-label col-lg-3">Monto (USD)</label>
                <div class="col-lg-3">
                    <input type="text" name="paylink_amount" class="form-control" placeholder="0.00">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">Impuesto</label>
                <div class="col-lg-3">
                    <select name="paylink_tax_mode" class="form-control">
                        <option value="iva">Monto incluye IVA ({$paylink_iva_rate_pct|intval}%)</option>
                        <option value="no_iva">Sin IVA (0%)</option>
                    </select>
                </div>
            </div>
        </div>

        {* ---- Catálogo ---- *}
        <div id="paylink-catalog-block" style="display:none;">
            <div class="form-group">
                <label class="control-label col-lg-3">Producto</label>
                <div class="col-lg-4">
                    <select id="paylink_product_select" class="form-control">
                        <option value="">-- Seleccione un producto --</option>
                        {foreach from=$paylink_products item=p}
                            <option value="{$p.id|intval}">{$p.name|escape:'html':'UTF-8'} (#{$p.id|intval})</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-2">
                    <input type="number" id="paylink_product_qty" class="form-control" min="1" value="1">
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-default" onclick="datafastAddProduct();">
                        <i class="icon-plus"></i> Agregar
                    </button>
                </div>
            </div>
            <div class="form-group">
                <div class="col-lg-offset-3 col-lg-7">
                    <table class="table" id="paylink_products_table" style="display:none;">
                        <thead>
                            <tr><th>Producto</th><th>Cantidad</th><th></th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <p class="help-block">El total se calcula con los precios actuales de la tienda.</p>
                </div>
            </div>
        </div>

        {* ---- Comunes ---- *}
        <div class="form-group">
            <label class="control-label col-lg-3">Referencia</label>
            <div class="col-lg-4">
                <input type="text" name="paylink_reference" class="form-control" maxlength="190" placeholder="Ej: Venta mostrador #123">
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Descripción (visible al cliente)</label>
            <div class="col-lg-6">
                <textarea name="paylink_description" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">Vence en (días)</label>
            <div class="col-lg-2">
                <input type="number" name="paylink_expiry_days" class="form-control" min="1" value="{$paylink_expiry_default|intval}">
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-primary pull-right">
                <i class="process-icon-new"></i> Generar link
            </button>
        </div>
    </form>
</div>

{literal}
<script type="text/javascript">
    var datafastPaylinkItems = [];

    function datafastTogglePaylinkType() {
        var type = document.querySelector('input[name="paylink_type"]:checked').value;
        document.getElementById('paylink-amount-block').style.display = (type === 'amount') ? '' : 'none';
        document.getElementById('paylink-catalog-block').style.display = (type === 'catalog') ? '' : 'none';
    }

    function datafastRenderProducts() {
        var tbody = document.querySelector('#paylink_products_table tbody');
        var table = document.getElementById('paylink_products_table');
        tbody.innerHTML = '';
        datafastPaylinkItems.forEach(function (item, idx) {
            var tr = document.createElement('tr');
            var tdName = document.createElement('td');
            tdName.textContent = item.name;
            var tdQty = document.createElement('td');
            tdQty.textContent = item.qty;
            var tdDel = document.createElement('td');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-default btn-xs';
            btn.innerHTML = '<i class="icon-trash"></i>';
            btn.onclick = function () {
                datafastPaylinkItems.splice(idx, 1);
                datafastRenderProducts();
            };
            tdDel.appendChild(btn);
            tr.appendChild(tdName);
            tr.appendChild(tdQty);
            tr.appendChild(tdDel);
            tbody.appendChild(tr);
        });
        table.style.display = datafastPaylinkItems.length ? '' : 'none';
        document.getElementById('paylink_product_refs').value = JSON.stringify(
            datafastPaylinkItems.map(function (i) { return { id_product: i.id_product, qty: i.qty }; })
        );
    }

    function datafastAddProduct() {
        var sel = document.getElementById('paylink_product_select');
        var qtyInput = document.getElementById('paylink_product_qty');
        var id = parseInt(sel.value, 10);
        var qty = parseInt(qtyInput.value, 10);
        if (!id) { alert('Seleccione un producto.'); return; }
        if (!qty || qty < 1) { qty = 1; }
        var name = sel.options[sel.selectedIndex].text;
        datafastPaylinkItems.push({ id_product: id, qty: qty, name: name });
        datafastRenderProducts();
        sel.value = '';
        qtyInput.value = 1;
    }

    document.getElementById('datafast_paylink_form').addEventListener('submit', function (e) {
        var type = document.querySelector('input[name="paylink_type"]:checked').value;
        if (type === 'catalog' && datafastPaylinkItems.length === 0) {
            e.preventDefault();
            alert('Agregue al menos un producto.');
        }
    });
</script>
{/literal}
