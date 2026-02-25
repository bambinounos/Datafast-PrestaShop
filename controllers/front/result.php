<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use datafast\payment\model\Constants;
use datafast\payment\PaymentService;
use datafast\payment\datafast\payment\Config;
use datafast\payment\datafast\payment\model\Message;
use datafast\payment\datafast\payment\model\PaymentResponse;



class datafastResultModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {
        parent::initContent();

            if (!$this->isPaymentMethodValid()) {
                die($this->module->l('This payment method is not available.', 'datafast'));
            }

            $cart = $this->context->cart;
            if (!$cart || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
                $this->redirectTo('order', array('step' => 1));
                return;
            }

            $customer = new Customer($cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->redirectTo('order', array('step' => 1));
                return;
            }

            $secureKey = $customer->secure_key;

        try {
            $paymentService = new PaymentService();

            $config = new Config();
            $data = [];
            $data['DATAFAST_DEV']=Configuration::get('DATAFAST_DEV', null);
            $data['DATAFAST_BEARER_TOKEN']=Configuration::get('DATAFAST_BEARER_TOKEN', null);
            $data['DATAFAST_ENTITY_ID']=Configuration::get('DATAFAST_ENTITY_ID', null);
            $data['DATAFAST_MID']=Configuration::get('DATAFAST_MID', null);
            $data['DATAFAST_TID']=Configuration::get('DATAFAST_TID', null);
            $data['DATAFAST_RISK']=Configuration::get('DATAFAST_RISK', null);
            $data['DATAFAST_PROVEEDOR']=Configuration::get('DATAFAST_PROVEEDOR', null);
            $data['DATAFAST_ECI']=Configuration::get('DATAFAST_ECI', null);
			$data['DATAFAST_PREFIJOTRX']=Configuration::get('DATAFAST_PREFIJOTRX', null);
            $data['DATAFAST_PRODULR']=Configuration::get('DATAFAST_PRODULR', null);
            $data['DATAFAST_DEVURL']=Configuration::get('DATAFAST_DEVURL', null);

            $request = $config->getDatafastRequest($data);


            $cart_id= $this->context->cart->id;
            $order_id = (int)Tools::getValue('order_id');
            $payment_id = trim((string)Tools::getValue('MD'));


            $checkOutId = Tools::getValue('id');
            $resourcePathUri = str_replace("{id}", $checkOutId, $request->getResourcePathUri());

            $this->safeLog('debug',"resourcePathUri--->" . $resourcePathUri);
            $request->setResourcePathUri($resourcePathUri);

			$paymentResp = $paymentService->processPayment($request);

            // Validar respuesta de la API antes de procesarla
            $objResponse = json_decode($paymentResp, true);

            if (!is_array($objResponse) || !isset($objResponse['result']['code'])) {
                $this->safeLog('error',"Respuesta inválida de Datafast. Response: " . ($paymentResp ?: '(vacío)'));
                $errorMsg = 'No se pudo obtener una respuesta válida del servidor de pagos. Por favor intente nuevamente.';
                Context::getContext()->cookie->errorMessage = $errorMsg;
                Tools::redirect(Context::getContext()->link->getModuleLink('datafast', 'error', array()));
                return;
            }

            $resultCode = $objResponse["result"]["code"];
            $resultdescription = $objResponse['result']['description'] ?? '';
            $accepted = $this->validateTransaction($resultCode);

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $module_name = $this->module->displayName;

            $this->addTransaction($cart_id,$customer->id,$total,$accepted,"",$objResponse);

			$datafastExtendedDescripcion = $objResponse['resultDetails']['ExtendedDescription'] ?? '';
			if ($datafastExtendedDescripcion == "")
            {
                $datafastExtendedDescripcion = $resultdescription;
            }
            $refunded=false;
            if(isset($objResponse["amount"]) && number_format($total, 2,'.','') != $objResponse["amount"])
            {
                $request->setAmount( $objResponse['amount']);
                $request->setTransactionId( $objResponse['id']);
                $refundResp = $paymentService->requestRefund($request);
                $objResponseRefund = json_decode($refundResp, true);

                if (!is_array($objResponseRefund)) {
                    $this->safeLog('error',"Respuesta inválida en refund. Response: " . ($refundResp ?: '(vacío)'));
                    $objResponseRefund = [];
                }

                $refund_id= $objResponseRefund['id'] ?? '';
                $refund_referencedId= $objResponseRefund['referencedId'] ?? '';
                $refund_paymentType= $objResponseRefund['paymentType'] ?? '';
                $refund_amount= $objResponseRefund['amount'] ?? '';
                $refund_currency= $objResponseRefund['currency'] ?? '';
                $refund_descriptor= $objResponseRefund['descriptor'] ?? '';
                $refund_merchantTransactionId= $objResponseRefund['merchantTransactionId'] ?? '';
                $refund_resultCode= $objResponseRefund['result']['code'] ?? '';
                $refund_description= $objResponseRefund['result']['description'] ?? '';
                $refund_ExtendedDescription= $objResponseRefund['resultDetails']['ExtendedDescription'] ?? '';
                if ($refund_ExtendedDescription == "")
                {
                    $refund_ExtendedDescription = $refund_description;
                }
                $refund_clearingInstituteName= $objResponseRefund['resultDetails']['clearingInstituteName'] ?? '';
                $refund_ConnectorTxID1= $objResponseRefund['resultDetails']['ConnectorTxID1'] ?? '';
                $refund_ReferenceNbr = $objResponseRefund['resultDetails']['ReferenceNo'] ?? '';
                $refund_BatchNo = $objResponseRefund['resultDetails']['BatchNo'] ?? '';
                $refund_response = $objResponseRefund['resultDetails']['Response'] ?? '';
                $refund_authcode = $objResponseRefund['resultDetails']['AuthCode'] ?? '';
                $refund_acquirercode = $objResponseRefund['resultDetails']['AcquirerCode'] ?? '';
                $refund_totalamount = $objResponseRefund['resultDetails']['TotalAmount'] ?? '';
                $refund_interest = $objResponseRefund['resultDetails']['Interest'] ?? '';
                $refund_timestamp= $objResponseRefund['timestamp'] ?? '';
                $refund_status = "1";
                $refund_resp= $refundResp;


                $accepted = $this->validateTransactionRefund($refund_resultCode);

                if ($accepted==1)
                {

                        $query = 'UPDATE ' . _DB_PREFIX_ . 'datafast_transactions
                                    SET     status = 2
                                    WHERE   transaction_id = \'' . pSQL($objResponse['id']).'\'
                                    AND     status       =   1';
                        Db::getInstance()->execute($query);
                }
                else
                {
                        $messagerefundDatafast = '<div class="conf confirm alert alert-danger">Registro no reversado. Mensaje del banco: '.htmlspecialchars($refund_ExtendedDescription, ENT_QUOTES, 'UTF-8').'.</div>';
                }


                                $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_transactions
                                (`cart_id`,
                                `customer_id`,
                                `transaction_id`,
                                `payment_type`,
                                `amount`,
                                `merchant_transactionId`,
                                `result_code`,
                                `result_description`,
                                `extended_description`,
                                `reference_no`,
                                `batch_no`,
                                `response`,
                                `auth_code`,
                                `acquirer_code`,
                                `total_amount`,
                                `interest`,
                                `timestamp`,
                                `response_json`,
                                `status`,
                                `updated_at`)
                                    VALUES (
                                    ' . (int)$cart_id . ',
                                    \'' . (int)$customer->id . '\',
                                    \'' . pSQL($refund_id) . '\',
                                    \'' . pSQL($refund_paymentType) . '\',
                                    \'' . pSQL($refund_amount) . '\',
                                    \'' . pSQL($refund_merchantTransactionId) . '\',
                                    \'' . pSQL($refund_resultCode) . '\',
                                    \'' . pSQL($refund_description) . '\',
                                    \'' . pSQL($refund_ExtendedDescription) . '\',
                                    \'' . pSQL($refund_ReferenceNbr) . '\',
                                    \'' . pSQL($refund_BatchNo) . '\',
                                    \'' . pSQL($refund_response) . '\',
                                    \'' . pSQL($refund_authcode) . '\',
                                    \'' . pSQL($refund_acquirercode) . '\',
                                    \'' . pSQL($refund_totalamount) . '\',
                                    \'' . pSQL($refund_interest) . '\',
                                    \'' . pSQL($refund_timestamp) . '\',
                                    \'' . pSQL($refund_resp) . '\',
                                    \'' . pSQL($refund_status) . '\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                            )';


                Db::getInstance()->execute($query);
                $refunded=true;
                $datafastExtendedDescripcion = "Los valores del carrito de compra fueron cambiados. Se procederá a anular la transacción para que pueda repetir su pago.";
            }
            if ($accepted && !$refunded) {

                $payment_status = Configuration::get('PS_OS_PAYMENT', null);
                $message = $this->l('El pago se registró correctamente');

                $datafastBrand = $objResponse['paymentBrand'] ?? '';
                $datafastAmount = $objResponse['amount'] ?? '';
                $datafastAuth = $objResponse['resultDetails']['AuthCode'] ?? '';
                $datafastCardHolder = $objResponse['card']['holder'] ?? '';


				Context::getContext()->cookie->datafastBrand = $datafastBrand;
                Context::getContext()->cookie->datafastAmount = $datafastAmount;
                Context::getContext()->cookie->datafastAuth = $datafastAuth;
                Context::getContext()->cookie->datafastCardHolder = $datafastCardHolder;
				Context::getContext()->cookie->datafastExtendedDescripcion = $datafastExtendedDescripcion;

                $this->module->validateOrder((int)$cart_id, $payment_status, $total, $module_name, $message, array(), (int)$cart->id_currency, false, $secureKey);

                $this->redirectTo('order-confirmation', array(
                    'id_cart' => (int)$cart_id,
                    'id_module' => (int)$this->module->id,
                    'id_order' => (int)$this->module->currentOrder,
                    'key' => $customer->secure_key
                ));
            } else {

                $logInfo = "Error en el pago  de:  " . $total . ", customer id: " . $customer->id . " cart id: " . $cart->id . " order id" . $this->module->currentOrder . " Detalle: " . $datafastExtendedDescripcion;
                $this->safeLog('info',$logInfo);

                Context::getContext()->cookie->errorMessage = $datafastExtendedDescripcion;
                Tools::redirect(Context::getContext()->link->getModuleLink('datafast', 'error', array()));
            }

        } catch (\Exception $e) {
            $this->safeLog('error',"Error crítico en el proceso de pago: " . $e->getMessage(), $e->getTrace());
            Context::getContext()->cookie->errorMessage = 'Ocurrió un error inesperado al procesar su pago. Por favor intente nuevamente.';
            Tools::redirect(Context::getContext()->link->getModuleLink('datafast', 'error', array()));
        } catch (\Error $e) {
            $this->safeLog('error',"Error fatal en el proceso de pago: " . $e->getMessage(), $e->getTrace());
            Context::getContext()->cookie->errorMessage = 'Ocurrió un error inesperado al procesar su pago. Por favor intente nuevamente.';
            Tools::redirect(Context::getContext()->link->getModuleLink('datafast', 'error', array()));
        }

    }

    private function isPaymentMethodValid()
    {
        if (!$this->module->active) {
            return false;
        }

        if (method_exists('Module', 'getPaymentModules')) {
            foreach (Module::getPaymentModules() as $module) {
                if (isset($module['name']) && $module['name'] === $this->module->name) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    private function redirectTo($controller, array $params = array())
    {
        $query_string = !empty($params) ? http_build_query($params) : '';

        Tools::redirect('index.php?controller=' . $controller . '&' . $query_string);

    }

    /**
     * @return Logger
     */
    private function getLogger(): ?Logger
    {
        try {
            $logFolder = Constants::LOGGER_FOLDER;
            if (!file_exists($logFolder)) {
                mkdir($logFolder, 0777, true);
            }
            $logger = new Logger('PaymentFrontController');
            $logger->pushHandler(new StreamHandler(Constants::LOGGER_FILE, Logger::DEBUG));
            return $logger;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            $logger = $this->getLogger();
            if ($logger) {
                $logger->$level($message, $context);
            }
        } catch (\Throwable $e) {
            // Monolog no disponible
        }
        try {
            \PrestaShopLogger::addLog('[Datafast] ' . $message, $level === 'error' ? 3 : 1);
        } catch (\Throwable $e) {
            // PrestaShopLogger no disponible
        }
    }

    private function validateTransaction(string $resultCode): bool
    {

        $testMode = Configuration::get('DATAFAST_DEV', null);
        $validTransaction = false;

        if (in_array($resultCode, Constants::TRANSACTION_APPROVED_TEST) && $testMode) {
            $validTransaction = true;
        }
        if ($resultCode == Constants::TRANSACTION_APPROVED_PROD && !$testMode) {
            $validTransaction = true;
        }
        return $validTransaction;
    }

    private function validateTransactionRefund(string $resultCode): bool
    {

        $testMode = Configuration::get('DATAFAST_DEV', null);
        $validTransaction = false;

        if (in_array($resultCode, Constants::TRANSACTION_APPROVED_TEST) && $testMode) {
            $validTransaction = true;
        }
        if ($resultCode == Constants::TRANSACTION_APPROVED_PROD && !$testMode) {
            $validTransaction = true;
        }
        return $validTransaction;
    }


    private function addTransaction($cart_id,$customer_id,$amount,$accepted,$paymentRequest,$paymentResponse)
    {
        try {
            $resultCode = $paymentResponse["result"]["code"] ?? '';
            $message = "";

            $transaction_id = '';
            $amount = 0;
            $transaction_id = '';
            $payment_type = '';
            $payment_brand = '';
            $merchant_transactionId= '';
            $result_code = '';
            $extended_description= '';
            $acquirer_response = '';
            $batch_no = '';
            $total_amount = '';
            $reference_no = '';
            $bin = '';
            $last_4_Digits = '';
            $email = '';
            $shopper_mid= '';
            $shopper_tid = '';
            $timestamp = '';
            $response = '';

            $checkout_id = isset( $paymentResponse['id'] )? $paymentResponse['id']:null;
            $result_code = $paymentResponse['result']['code'] ?? '';
            $result_description= $paymentResponse['result']['description'] ?? '';
            $request_json = json_encode($paymentRequest);
            $response_json = json_encode($paymentResponse);
            $timestamp = $paymentResponse['timestamp'] ?? '';

            $status = "0";


                $amount = $paymentResponse['amount'] ?? '';

                $transaction_id = isset( $paymentResponse['id'] )? $paymentResponse['id']:null;
                $payment_type = $paymentResponse['paymentType'] ?? '';
                $payment_brand = $paymentResponse['paymentBrand'] ?? '';
                $amount = $paymentResponse['amount'] ?? '';
                $merchant_transactionId= $paymentResponse['merchantTransactionId'] ?? '';

                $extended_description= $paymentResponse['resultDetails']['ExtendedDescription'] ?? '';
                if ($extended_description == "")
                {
                 $extended_description = $result_description;
                }
                $response = $paymentResponse['resultDetails']['Response'] ?? '';
                $acquirer_response = $paymentResponse['resultDetails']['AcquirerResponse'] ?? '';
                $auth_code = isset( $paymentResponse['resultDetails']['AuthCode'])? $paymentResponse['resultDetails']['AuthCode']:null;
                $acquirer_code = isset( $paymentResponse['resultDetails']['AcquirerCode'])?$paymentResponse['resultDetails']['AcquirerCode']:null;
                $batch_no = isset($paymentResponse['resultDetails']['BatchNo'])?$paymentResponse['resultDetails']['BatchNo']:null ;
                $interest = isset($paymentResponse['resultDetails']['Interest'])?$paymentResponse['resultDetails']['Interest']:null ;
                $total_amount = isset($paymentResponse['resultDetails']['TotalAmount'])?$paymentResponse['resultDetails']['TotalAmount']:null ;
                $reference_no = isset($paymentResponse['resultDetails']['ReferenceNo'])?$paymentResponse['resultDetails']['ReferenceNo']:null ;
                $bin = isset($paymentResponse['card']['bin'])?$paymentResponse['card']['bin']:null ;
                $last_4_Digits = isset($paymentResponse['card']['last4Digits'])?$paymentResponse['card']['last4Digits']:null ;
                $email = isset($paymentResponse['customer']['email'])?$paymentResponse['customer']['email']:null  ;
                $shopper_mid = isset($paymentResponse['customParameters']['SHOPPER_MID'])?$paymentResponse['customParameters']['SHOPPER_MID']:null  ;
                $shopper_tid = isset( $paymentResponse['customParameters']['SHOPPER_TID'])? $paymentResponse['customParameters']['SHOPPER_TID']:null;


            if ($accepted) {

                $registrationId = $paymentResponse['registrationId'] ?? '';
                $status = "1";

                if ($registrationId != "")
                {

                    $sqlCount = '
                    SELECT COUNT(tkn.id)    AS CountToken
                    FROM `' . _DB_PREFIX_ . 'datafast_customertoken` tkn
                    WHERE tkn.`token` = \'' . pSQL($registrationId) . '\'';
                    $countToken = Db::getInstance()->executeS($sqlCount);
                        $countTkn = $countToken[0]['CountToken'];


                    if ($countTkn == 0)
                    {
                        $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_customertoken
                        (`customer_id`,
                        `token`,
                        `status`,
                        `updated_at`)
                            VALUES (
                                    \'' . pSQL($customer_id) . '\',
                                    \'' . pSQL($registrationId) . '\',
                                    \'1\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                                    )';
                        Db::getInstance()->execute($query);
                    }
                }
            }

            $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_transactions
                                 (`cart_id`,
                                 `customer_id`,
                                 `transaction_id`,
                                 `checkout_id`,
                                 `payment_type`,
                                 `payment_brand`,
                                 `amount`,
                                 `merchant_transactionId`,
                                 `result_code`,
                                 `result_description`,
                                 `extended_description`,
                                 `acquirer_response`,
                                 `response`,
                                 `auth_code`,
                                 `acquirer_code`,
                                 `batch_no`,
                                 `interest`,
                                 `total_amount`,
                                 `reference_no`,
                                 `bin`,
                                 `last_4_Digits`,
                                 `email`,
                                 `shopper_mid`,
                                 `shopper_tid`,
                                 `timestamp`,
                                 `request_json`,
                                 `response_json`,
                                 `status`,
                                 `updated_at`)
                                     VALUES (
                                        ' . (int)$cart_id . ',
                                        \'' . pSQL($customer_id) . '\',
                                        \'' . pSQL($transaction_id) . '\',
                                        \'' . pSQL($checkout_id) . '\',
                                        \'' . pSQL($payment_type) . '\',
                                        \'' . pSQL($payment_brand) . '\',
                                        \'' . pSQL($amount) . '\',
                                        \'' . pSQL($merchant_transactionId) . '\',
                                        \'' . pSQL($result_code) . '\',
                                        \'' . pSQL($result_description) . '\',
                                        \'' . pSQL($extended_description) . '\',
                                        \'' . pSQL($acquirer_response) . '\',
                                        \'' . pSQL($response) . '\',
                                        \'' . pSQL($auth_code) . '\',
                                        \'' . pSQL($acquirer_code) . '\',
                                        \'' . pSQL($batch_no) . '\',
                                        \'' . pSQL($interest) . '\',
                                        \'' . pSQL($total_amount) . '\',
                                        \'' . pSQL($reference_no) . '\',
                                        \'' . pSQL($bin) . '\',
                                        \'' . pSQL($last_4_Digits) . '\',
                                        \'' . pSQL($email) . '\',
                                        \'' . pSQL($shopper_mid) . '\',
                                        \'' . pSQL($shopper_tid) . '\',
                                        \'' . pSQL($timestamp) . '\',
                                        \'' . pSQL($request_json) . '\',
                                        \'' . pSQL($response_json) . '\',
                                        \'' . pSQL($status) . '\',
                                        \'' . date('Y-m-d H:i:s') . '\'
                             )';
            Db::getInstance()->execute($query);
        } catch (\Exception $e) {
            $this->safeLog('error',"Error al guardar transacción: " . $e->getMessage(), $e->getTrace());
        }
    }

    private function getDetailErrorMessage(string $resultCode): string
    {
        return Message::getClientMessageDescription($resultCode);
    }
}
