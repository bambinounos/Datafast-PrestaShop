{*
* Comprobante de pago aprobado por link (modo solo-registro / respaldo).
*}
<head>
    {block name='head'}
        {include file='_partials/head.tpl'}
    {/block}
</head>

<body>
{hook h='displayAfterBodyOpeningTag'}
<main>
    <header id="header">
        {block name='header'}
            {include file='_partials/header.tpl'}
        {/block}
    </header>

    <section id="wrapper">
        <div class="container">
            <section id="main">
                <section id="content" class="page-content card card-block">

                    <article class="alert alert-success" role="alert">
                        <h3>¡Pago aprobado!</h3>
                        <p>Tu pago se procesó correctamente. Gracias por tu compra.</p>
                    </article>

                    <ul class="list-unstyled">
                        <li><strong>Monto:</strong> ${$amount|escape:'html':'UTF-8'}</li>
                        {if $brand}<li><strong>Tarjeta:</strong> {$brand|escape:'html':'UTF-8'}</li>{/if}
                        {if $card_holder}<li><strong>Titular:</strong> {$card_holder|escape:'html':'UTF-8'}</li>{/if}
                        {if $auth_code}<li><strong>Código de autorización:</strong> {$auth_code|escape:'html':'UTF-8'}</li>{/if}
                        {if $reference}<li><strong>Referencia:</strong> {$reference|escape:'html':'UTF-8'}</li>{/if}
                    </ul>

                </section>
            </section>
        </div>
    </section>

    <footer id="footer">
        {block name="footer"}
            {include file="_partials/footer.tpl"}
        {/block}
    </footer>
    {block name='javascript_bottom'}
        {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
    {/block}
    {hook h='displayBeforeBodyClosingTag'}
</main>

</body>
