{*
* Listado de Links de Pago generados.
*}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list"></i> Links de Pago generados
    </div>

    <div class="table-responsive-row clearfix">
        <table class="table datafast">
            <thead>
                <tr class="nodrag nodrop">
                    <th><span class="title_box">Fecha</span></th>
                    <th><span class="title_box">Referencia</span></th>
                    <th><span class="title_box">Monto</span></th>
                    <th><span class="title_box">Estado</span></th>
                    <th><span class="title_box">Cliente</span></th>
                    <th><span class="title_box">Pedido</span></th>
                    <th><span class="title_box">Vence</span></th>
                    <th><span class="title_box">Link</span></th>
                    <th><span class="title_box">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
                {if $paylinks}
                    {foreach from=$paylinks item=link name=pl}
                        <tr class="{if $smarty.foreach.pl.index % 2}odd{else}even{/if}">
                            <td>{$link.created_at|escape:'html':'UTF-8'}</td>
                            <td>{$link.reference|escape:'html':'UTF-8'}</td>
                            <td>${$link.amount|escape:'html':'UTF-8'}</td>
                            <td>
                                {if $link.status == 'paid'}
                                    <span class="badge badge-success">{$link.status_label|escape:'html':'UTF-8'}</span>
                                {elseif $link.status == 'pending'}
                                    <span class="badge badge-warning">{$link.status_label|escape:'html':'UTF-8'}</span>
                                {else}
                                    <span class="badge badge-danger">{$link.status_label|escape:'html':'UTF-8'}</span>
                                {/if}
                            </td>
                            <td>{$link.payer_email|escape:'html':'UTF-8'}</td>
                            <td>
                                {if $link.id_order}
                                    <a href="{$link.href_order|escape:'html':'UTF-8'}">#{$link.id_order|intval}</a>
                                {else}-{/if}
                            </td>
                            <td>{$link.expires_at|escape:'html':'UTF-8'}</td>
                            <td>
                                <input type="text" class="form-control input-sm" readonly
                                       value="{$link.url|escape:'html':'UTF-8'}"
                                       onclick="this.select();" style="min-width:220px;">
                            </td>
                            <td class="text-right">
                                <div class="btn-group">
                                    <a href="{$link.wa_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener" class="btn btn-default btn-sm" title="Enviar por WhatsApp">
                                        <i class="icon-whatsapp"></i> WhatsApp
                                    </a>
                                    {if $link.is_pending}
                                        <a href="{$link.cancel_url|escape:'html':'UTF-8'}" class="btn btn-default btn-sm"
                                           onclick="return confirm('¿Cancelar este link de pago?');" title="Cancelar">
                                            <i class="icon-remove"></i>
                                        </a>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="9" class="text-center text-muted">Aún no has generado links de pago.</td></tr>
                {/if}
            </tbody>
        </table>
    </div>
</div>
