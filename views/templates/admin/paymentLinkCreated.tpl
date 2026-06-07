{*
* Panel de confirmación tras crear un link de pago.
*}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-check"></i> Link de pago creado
    </div>
    <div class="alert alert-success">
        Se generó el link de pago por <strong>${$created_amount|escape:'html':'UTF-8'}</strong>{if $created_reference} — {$created_reference|escape:'html':'UTF-8'}{/if}.
        Cópialo y envíalo a tu cliente por WhatsApp, Telegram o correo.
    </div>

    <div class="form-group">
        <label class="control-label">URL del link</label>
        <div class="input-group">
            <input type="text" id="datafast_created_url" class="form-control" readonly
                   value="{$created_url|escape:'html':'UTF-8'}"
                   onclick="this.select();">
            <span class="input-group-btn">
                <button type="button" class="btn btn-default" onclick="datafastCopyCreatedUrl();">
                    <i class="icon-copy"></i> Copiar
                </button>
            </span>
        </div>
    </div>

    <a href="{$created_wa_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-success">
        <i class="icon-whatsapp"></i> Enviar por WhatsApp
    </a>

    {literal}
    <script type="text/javascript">
        function datafastCopyCreatedUrl() {
            var input = document.getElementById('datafast_created_url');
            input.select();
            input.setSelectionRange(0, 99999);
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value);
                } else {
                    document.execCommand('copy');
                }
                alert('Link copiado al portapapeles.');
            } catch (e) {
                document.execCommand('copy');
                alert('Link copiado.');
            }
        }
    </script>
    {/literal}
</div>
