{*
* Página pública de pago por link Datafast (sin login).
* Estados: message | form | widget
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

                    <h3 class="h3">Pago en línea — Datafast</h3>

                    {if $paylink_state == 'message'}

                        <article class="alert alert-info mt-2" role="alert">
                            {$paylink_message|escape:'html':'UTF-8'}
                        </article>

                    {elseif $paylink_state == 'widget'}

                        <div class="card card-block mb-3" style="padding:15px;">
                            <p class="h4">Total a pagar: <strong>${$paylink_amount|escape:'html':'UTF-8'}</strong></p>
                            {if $paylink_reference}<p class="text-muted">{$paylink_reference|escape:'html':'UTF-8'}</p>{/if}
                            {if $paylink_description}<p>{$paylink_description|escape:'html':'UTF-8'}</p>{/if}
                        </div>

                        <div class="form-group" style="margin:15px 0;">
                            <label>
                                <input type="checkbox" id="conditions_to_approve[terms-and-conditions]" value="1">
                                Acepto los términos y condiciones de la compra.
                            </label>
                        </div>

                        <section>
                            <script type="text/javascript">
                                function setInstallment(selObj) {
                                    var isRegistration = (selObj.parentElement.parentElement.parentElement.parentElement.className + "").includes('wpwl-form-registrations');
                                    var form = isRegistration ? 'Registration' : 'Card';
                                    var objNumInstall = document.getElementById("numinstall" + form);
                                    var objCreditType = document.getElementById("termtype" + form);
                                    var res = selObj.value.split("|");
                                    if (objCreditType) objCreditType.value = res[0];
                                    if (objNumInstall) objNumInstall.value = res[1];
                                }

                                var wpwlOptions = {
                                    onReady: function (onReady) {

                                        var tipocredito = '<div class="wpwl-group installments-group  wpwl-clearfix">' +
                                            '<div class="wpwl-label ">' +
                                            '   Tipo de Crédito' +
                                            '</div>' +
                                            '<select id="cboInstallments" class="wpwl-control" onChange="javascript:setInstallment(this);">' +
                                            {section name=co loop=$termtypes}
                                                '<option value="{$termtypes[co].code|escape:'html':'UTF-8'}">{$termtypes[co].name|escape:'html':'UTF-8'}</option>' +
                                            {/section}
                                            '</select></div>';
                                        {if $termtypes}
                                        $('form.wpwl-form-card').find('.wpwl-button').before(tipocredito);
                                        {/if}

                                        var termtype = (form) => '<input type="hidden" id="termtype' + form + '" name="customParameters[SHOPPER_TIPOCREDITO]" value="{$defaultTermType}">';
                                        $('form.wpwl-form-card').find('.wpwl-button').before(termtype('Card'));

                                        var datafast = '<br/><br/><img src=' + '"https://www.datafast.com.ec/images/verified.png" style=' + '"display:block;margin:0 auto; width:100%;">';
                                        $('form.wpwl-form-card').find('.wpwl-button').before(datafast);

                                        var installs = (form) => '<input type="hidden" id="numinstall' + form + '" name="recurring.numberOfInstallments" value="{$defaultInstallments}">';
                                        $('form.wpwl-form-card').find('.wpwl-button').before(installs('Card'));

                                        $(".wpwl-button").on("click", function () {
                                            var attr = $(this).attr("data-action");
                                            if (attr != 'show-initial-forms') {
                                                var chk = document.getElementById('conditions_to_approve[terms-and-conditions]');
                                                if (chk && chk.checked == false) {
                                                    alert('Por favor, acepte los términos y condiciones.');
                                                    return false;
                                                }
                                            }
                                        });
                                    },
                                    style: "{$style}",
                                    onBeforeSubmitCard: function (e) {
                                        const holder = $('.wpwl-control-cardHolder').val();
                                        if (holder.trim().length < 2) {
                                            $('.wpwl-control-cardHolder').addClass('wpwl-has-error').after('<div class="wpwl-hint wpwl-hint-cardHolderError">Nombre del titular de la tarjeta no válido</div>');
                                            $(".wpwl-button-pay").addClass('wpwl-button-error').attr('disabled', 'disabled');
                                            return false;
                                        }
                                        return true;
                                    },
                                    locale: "es",
                                    maskCvv: true,
                                    brandDetection: true,
                                    brandDetectionPriority: ["VISA", "ALIA", "MASTER", "AMEX", "DINERS", "DISCOVER"],
                                    labels: {
                                        cvv: "CVV",
                                        cardHolder: "Nombre(Igual que en la tarjeta)"
                                    },
                                    registrations: {
                                        requireCvv: {$requirecvv},
                                        hideInitialPaymentForms: true
                                    }
                                }
                            </script>
                            <script defer src="{$checkScript|escape:'html':'UTF-8'}"></script>
                            <form action="{$action|escape:'html':'UTF-8'}" class="paymentWidgets" id="datafastPaymentForm" data-brands="VISA MASTER AMEX DINERS DISCOVER ALIA">
                            </form>
                        </section>

                    {else}

                        <div class="card card-block mb-3" style="padding:15px;">
                            <p class="h4">Total a pagar: <strong>${$paylink_amount|escape:'html':'UTF-8'}</strong></p>
                            {if $paylink_reference}<p class="text-muted">{$paylink_reference|escape:'html':'UTF-8'}</p>{/if}
                            {if $paylink_description}<p>{$paylink_description|escape:'html':'UTF-8'}</p>{/if}
                        </div>

                        {if $paylink_error}
                            <article class="alert alert-danger" role="alert">{$paylink_error|escape:'html':'UTF-8'}</article>
                        {/if}

                        <p>Completa tus datos para continuar al pago seguro con tu tarjeta:</p>

                        <form method="post" action="{$paylink_form_action|escape:'html':'UTF-8'}">
                            <input type="hidden" name="submitDatafastPayer" value="1">

                            <div class="form-group">
                                <label class="form-control-label">Nombre completo</label>
                                <input type="text" name="payer_name" class="form-control" required
                                       value="{$payer_name|escape:'html':'UTF-8'}">
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">Correo electrónico</label>
                                <input type="email" name="payer_email" class="form-control" required
                                       value="{$payer_email|escape:'html':'UTF-8'}">
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">Cédula / RUC</label>
                                <input type="text" name="payer_dni" class="form-control" required
                                       value="{$payer_dni|escape:'html':'UTF-8'}">
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">Teléfono</label>
                                <input type="text" name="payer_phone" class="form-control" required
                                       value="{$payer_phone|escape:'html':'UTF-8'}">
                            </div>

                            <button type="submit" class="btn btn-primary">Continuar al pago</button>
                        </form>

                    {/if}

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
