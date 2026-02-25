<section id="order-summary-content" class="page-content page-order-confirmation">
    {if $status == 'ok'}
        <img src="{$this_path|escape:'html':'UTF-8'}/views/img/logo.png" width="230" height="60"/>
        <div class="box order-confirmation">
            <p class="alert alert-success">{l s='Su pedido en %s está completo.' sprintf=[$shop_name] mod='datafast'}</p>


            <table>
                <tbody>
                <tr>
                    <td>{l s='Tarjeta' mod='datafast'}&nbsp;</td>
                    <td>&nbsp;{$datafastBrand|escape:'html':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{l s='Nombre' mod='datafast'}&nbsp;</td>
                    <td>&nbsp;{$datafastCardHolder|escape:'html':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{l s='Monto' mod='datafast'}&nbsp;</td>
                    <td>&nbsp;{$datafastAmount|escape:'html':'UTF-8'}</td>
                </tr>
                <tr>
                    <td>{l s='Autorización' mod='datafast'}&nbsp;</td>
                    <td>&nbsp;{$datafastAuth|escape:'html':'UTF-8'}</td>
                </tr>

                </tbody>
            </table>

            <br/>
            <p><strong>{l s='Su pedido se encuentra realizado.' mod='datafast'}</strong></p>
            <p>
                {l s='Para cualquier consulta o para más información, contacte con nuestro' mod='datafast'}
                <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='atención al cliente' mod='datafast'}</a>.
            </p>
        </div>
    {else}
        <p class="alert alert-warning">{l s='Se produjo un error al procesar el pago.' mod='datafast'}</p>
        <div class="box order-confirmation">
            {if !empty($message)}
                <p>{$message|escape:'html':'UTF-8'}</p>
            {/if}
            <p>
                {l s='Para cualquier consulta o para más información, contacte con nuestro' mod='datafast'}
                <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='atención al cliente' mod='datafast'}</a>.
            </p>
        </div>
    {/if}
</section>