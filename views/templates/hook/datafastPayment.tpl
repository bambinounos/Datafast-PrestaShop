<section>


    
    <script src="{$checkScript|escape:'html':'UTF-8'}"></script>
  
    <script type="text/javascript">

    function deleteToken(obj) {
        if (confirm("¿Deseas eliminar esta tarjeta?")) {
            let token = $(obj).parent().find('label .wpwl-wrapper-registration-registrationId input');
            let isChecked = token.checked;
            var test = '{$removetoken}'; 
             var removeurl = prestashop.urls.base_url  + 'modules/datafast/ajax-call.php';
            logFetch(removeurl + '?token=' + token.val()).then(response => {

                if (response == 'true') {
                    alert('Tarjeta eliminada exitosamente.');
                    $(obj).parent().remove();
                    if ($('input[name="registrationId"]').length == 0) {
                        $('button[data-action="show-initial-forms"]').click();
                    } else {
                        $('label .wpwl-wrapper-registration-registrationId input')[0].click()
                    }
                } else
                    alert('No se pudo eliminar la tarjeta.');
            });
        }
    }
   async function logFetch(url) {
        try {
            const response = await fetch(url, {
                method: 'POST'
            });
            return await response.text();
        }
        catch (err) {
            alert('Ocurrio un error cuando se intento elminar la tarjeta.');
            console.log('error', err);
        }
    } 

   function setInstallment(selObj){
            var isRegistration = (selObj.parentElement.parentElement.parentElement.parentElement.className+"").includes('wpwl-form-registrations');
            var form=isRegistration?'Registration':'Card';
            var objNumInstall = document.getElementById("numinstall"+form);
            var objCreditType = document.getElementById("termtype"+form);
            var res = selObj.value.split("|");
            objCreditType.value = res[0];
            objNumInstall.value = res[1]; 
                }
  

        var wpwlOptions = {
            onReady: function(onReady) {
              
             

                {if $customertoken == '1'}
                        var createRegistrationHtml = '<div class="customLabel">Desea guardar de manera segura sus datos?</div><div class="customInput">' +
                        '<input type="checkbox" name="createRegistration" /></div>';
                        $('form.wpwl-form-card').find('.wpwl-button').before(createRegistrationHtml);
                {/if}
            
            
 
                var tipocredito =   '<div class="wpwl-group installments-group  wpwl-clearfix">' +
                                    '<div class="wpwl-label ">'+
                                    '   Tipo de Crédito'+
                                    '</div>'+
                                    '<select id="cboInstallments" class="wpwl-control" onChange="javascript:setInstallment(this);">' +
                                    {section name=co loop=$termtypes}
                                            '<option value="{$termtypes[co].code|escape:'html':'UTF-8'}">{$termtypes[co].name|escape:'html':'UTF-8'}</option>'+
                                    {/section}
                                    '</div>';
                $('form.wpwl-form-card').find('.wpwl-button').before(tipocredito);
                $('form.wpwl-form-registrations').find('.wpwl-button').before(tipocredito);
                
                var termtype=(form)=> '<input type="hidden" id="termtype'+form+'" name="customParameters[SHOPPER_TIPOCREDITO]" value="{$defaultTermType}">';
                $('form.wpwl-form-card').find('.wpwl-button').before(termtype('Card'));
                $('form.wpwl-form-registrations').find('.wpwl-button').before(termtype('Registration'));

                var datafast= '<br/><br/><img src='+'"https://www.datafast.com.ec/images/verified.png" style='+'"display:block;margin:0 auto; width:100%;">';
                $('form.wpwl-form-card').find('.wpwl-button'). before(datafast);

             
                var installs =(form)=>  '<input type="hidden" id="numinstall'+form+'" name="recurring.numberOfInstallments" value="{$defaultInstallments}">';
                $('form.wpwl-form-card').find('.wpwl-button').before(installs('Card'));
                $('form.wpwl-form-registrations').find('.wpwl-button').before(installs('Registration'));

 
                var deleteButton = '<div id="deleteButton" onClick="deleteToken(this)" class="wpwl-icon ui-state-default ui-corner-all delete" type="button">' +
                        '<span class="ui-icon ui-icon-close"></span>' +
                  '</div>';
                $('form.wpwl-form-registrations').find('.wpwl-registration').after(deleteButton);
               
                $(".wpwl-button").on("click", function () {
                    var attr = $(this).attr("data-action");
                    if (attr == 'show-initial-forms')
                    {
                        $('.wpwl-form-registrations').fadeOut('slow');
                    }
                    else
                    {
                        var chkConditions = document.getElementById('conditions_to_approve[terms-and-conditions]').checked;
                        if (chkConditions == false)
                        {   
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
                    $(".wpwl-button-pay").addClass('wpwl-button-error').attr('disabled','disabled');
                    return false;
                } 
                return true;
            },
            locale: "es",
            maskCvv: true,
            brandDetection: true, 
            brandDetectionPriority: ["VISA","ALIA","MASTER","AMEX","DINERS","DISCOVER"], 
            labels: {
                cvv: "CVV"
                , cardHolder: "Nombre(Igual que en la tarjeta)"
            },
            registrations: {
                    requireCvv: {$requirecvv},
                    hideInitialPaymentForms: true
            }
        }

  				
					
 const waitUntilElementExistsSelector = (selector, callback) => {
            const element = document.querySelector(selector);
            if (element) {
                return callback(element);
            }
            setTimeout(() => waitUntilElementExistsSelector(selector, callback), 500);
        }

        const waitUntilElementExistsId = (elementId, callback) => {
            const element = document.getElementById(elementId);
            if (element) {
                return callback(element);
            }
            setTimeout(() => waitUntilElementExistsId(elementId, callback), 500);
        }

        waitUntilElementExistsSelector("input[data-module-name='datafast']", (datafastPayment) => {
            datafastPayment.addEventListener('change', function () {
                const paymentButton = document.querySelector("div.ps-shown-by-js > button:nth-child(1)");
                if (datafastPayment && datafastPayment.checked) {
                    paymentButton.style.display = "none";
                }
            });
        });


        waitUntilElementExistsSelector("input[data-module-name='ps_wirepayment']", (wirepayment) => {
            wirepayment.addEventListener('change', function () {
                const paymentButton = document.querySelector("div.ps-shown-by-js > button:nth-child(1)");
                if (wirepayment && wirepayment.checked) {
                    paymentButton.style.display = "block";
                }
            });
        });

					
					

    </script> 
    <form action="{$action|escape:'html':'UTF-8'}" class="paymentWidgets" id="datafastPaymentForm" data-brands="VISA MASTER AMEX DINERS DISCOVER ALIA">
    </form>
  <style>
        .wpwl-wrapper-registration-registrationId{
            width: 8.33333333% !important;
        }
        .wpwl-wrapper-registration-brand{
            width: 14.66666667% !important;
        }
        .wpwl-wrapper-registration-details{
            width: 56.33333333% !important;
        }
        #deleteButton{ 
            float: right !important;
            background-color: #d44950 !important;
            position: absolute;
            right: 3px;
            top: 33% !important;
            float: right !important;
        }
        .ui-icon-close {
            background-position: -80px -128px;
            background-color: #ffa7a7 !important;
            
}
</style>
</section>
