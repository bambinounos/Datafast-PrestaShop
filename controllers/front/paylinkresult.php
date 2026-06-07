<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use datafast\payment\model\Constants;
use datafast\payment\PaymentService;
use datafast\payment\datafast\payment\Config;
use datafast\payment\model\DatafastPaymentLink;

/**
 * Procesa el resultado del pago de un link (sin login).
 * Consulta el estado en Datafast, verifica el monto, crea un pedido invitado
 * y marca el link como pagado de forma idempotente.
 */
class datafastPaylinkresultModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $token = trim((string) Tools::getValue('t'));
        $link = DatafastPaymentLink::getByToken($token);

        if (!$link) {
            $this->failWith('Este link de pago no es válido.');

            return;
        }

        // Idempotencia: si ya está pagado, no creamos un segundo pedido.
        if ($link['status'] === DatafastPaymentLink::STATUS_PAID) {
            $this->redirectAlreadyPaid($link);

            return;
        }

        try {
            $request = (new Config())->getDatafastRequest();

            $checkOutId = Tools::getValue('id');
            $resourcePathUri = str_replace('{id}', $checkOutId, $request->getResourcePathUri());
            $request->setResourcePathUri($resourcePathUri);

            $paymentService = new PaymentService();
            $paymentResp = $paymentService->processPayment($request);
            $objResponse = json_decode($paymentResp, true);

            if (!is_array($objResponse) || !isset($objResponse['result']['code'])) {
                $this->safeLog('error', 'Respuesta inválida de Datafast (paylink). Response: ' . ($paymentResp ?: '(vacío)'));
                $this->failWith('No se pudo confirmar el pago. Por favor intenta nuevamente.');

                return;
            }

            $resultCode = $objResponse['result']['code'];
            $resultDescription = $objResponse['result']['description'] ?? '';
            $extended = $objResponse['resultDetails']['ExtendedDescription'] ?? $resultDescription;
            $accepted = $this->validateTransaction($resultCode);

            // Verificación de monto: debe coincidir con el del link (fuente de verdad).
            $refunded = false;
            if ($accepted && isset($objResponse['amount'])
                && number_format((float) $link['amount'], 2, '.', '') != $objResponse['amount']) {
                $this->safeLog('error', 'Monto no coincide en paylink ' . $token
                    . ' esperado=' . $link['amount'] . ' recibido=' . $objResponse['amount']);
                $request->setAmount($objResponse['amount']);
                $request->setTransactionId($objResponse['id'] ?? '');
                $paymentService->requestRefund($request);
                $refunded = true;
                $extended = 'El monto del pago no coincide con el link. La transacción fue reversada.';
            }

            if ($accepted && !$refunded) {
                $createOrder = (bool) Configuration::get('DATAFAST_PAYLINK_CREATE_ORDER');
                $order = null;
                if ($createOrder) {
                    try {
                        $order = $this->createGuestOrder($link, $objResponse);
                    } catch (\Throwable $e) {
                        $this->safeLog('error', 'Error creando pedido invitado (paylink): ' . $e->getMessage(), $e->getTrace());
                        $order = null;
                    }
                }

                $idOrder = $order['id_order'] ?? null;
                $idCart = $order['id_cart'] ?? null;
                $customerId = isset($order['customer']) ? (string) $order['customer']->id : 'PAYLINK';

                DatafastPaymentLink::markPaid($token, $idOrder, $idCart, $objResponse['id'] ?? '');
                $this->recordTransaction($idCart ?? 0, $customerId, true, $objResponse);

                if ($order && !empty($order['id_order'])) {
                    Tools::redirect('index.php?controller=order-confirmation'
                        . '&id_cart=' . (int) $order['id_cart']
                        . '&id_module=' . (int) $this->module->id
                        . '&id_order=' . (int) $order['id_order']
                        . '&key=' . $order['customer']->secure_key);

                    return;
                }

                // Modo solo-registro (o falló la creación del pedido): mostrar comprobante.
                $this->renderSuccess($link, $objResponse);

                return;
            }

            // Rechazado o reversado.
            $this->recordTransaction(0, 'PAYLINK', false, $objResponse);
            $this->failWith($extended ?: 'El pago no fue aprobado.');
        } catch (\Throwable $e) {
            $this->safeLog('error', 'Error crítico en paylinkresult: ' . $e->getMessage(), $e->getTrace());
            $this->failWith('Ocurrió un error inesperado al procesar tu pago. Por favor intenta nuevamente.');
        }
    }

    private function failWith(string $message): void
    {
        Context::getContext()->cookie->errorMessage = $message;
        Tools::redirect(Context::getContext()->link->getModuleLink('datafast', 'error', []));
    }

    private function redirectAlreadyPaid(array $link): void
    {
        if (!empty($link['id_order']) && !empty($link['id_cart'])) {
            $order = new Order((int) $link['id_order']);
            if (Validate::isLoadedObject($order)) {
                $customer = new Customer((int) $order->id_customer);
                Tools::redirect('index.php?controller=order-confirmation'
                    . '&id_cart=' . (int) $link['id_cart']
                    . '&id_module=' . (int) $this->module->id
                    . '&id_order=' . (int) $order->id
                    . '&key=' . $customer->secure_key);

                return;
            }
        }
        $this->context->smarty->assign([
            'paylink_state' => 'message',
            'paylink_message' => 'Este link de pago ya fue pagado. ¡Gracias!',
            'shop_name' => $this->context->shop->name,
        ]);
        $this->setTemplate('module:datafast/views/templates/front/paylink.tpl');
    }

    /**
     * Crea el pedido invitado (cliente is_guest + dirección + carrito + validateOrder).
     *
     * @return array{id_order:int,id_cart:int,customer:Customer}|null
     */
    private function createGuestOrder(array $link, array $objResponse): ?array
    {
        $parts = preg_split('/\s+/', trim((string) $link['payer_name']), 2);
        $firstName = ($parts[0] ?? '') !== '' ? $parts[0] : 'Cliente';
        $lastName = (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : 'POS';
        $email = $link['payer_email'] ?: ('paylink+' . $link['token'] . '@datafast.local');

        $idCustomer = (int) Customer::customerExists($email, true);
        $customer = $idCustomer > 0 ? new Customer($idCustomer) : new Customer();
        if ($idCustomer <= 0) {
            $customer->is_guest = 1;
            $customer->firstname = $firstName;
            $customer->lastname = $lastName;
            $customer->email = $email;
            $customer->passwd = Tools::hash(Tools::passwdGen());
            $customer->id_default_group = (int) Configuration::get('PS_GUEST_GROUP');
            $customer->id_shop = (int) $this->context->shop->id;
            $customer->add();
        }
        if (!Validate::isLoadedObject($customer)) {
            return null;
        }
        $this->context->customer = $customer;
        $this->context->cookie->id_customer = (int) $customer->id;

        $idCountry = (int) Country::getByIso('EC');
        if ($idCountry <= 0) {
            $idCountry = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        }
        $address = new Address();
        $address->id_customer = (int) $customer->id;
        $address->id_country = $idCountry;
        $address->alias = 'POS';
        $address->lastname = $lastName;
        $address->firstname = $firstName;
        $address->address1 = ($link['reference'] !== '' && $link['reference'] !== null) ? $link['reference'] : 'Punto de venta';
        $address->city = 'N/A';
        $address->phone = ($link['payer_phone'] !== '' && $link['payer_phone'] !== null) ? $link['payer_phone'] : '0000000000';
        $address->vat_number = $link['payer_dni'];
        $address->add();
        $idAddress = (int) $address->id;

        $cart = new Cart();
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = $idAddress;
        $cart->id_address_invoice = $idAddress;
        $cart->id_lang = (int) $this->context->language->id;
        $idCurrency = (int) Currency::getIdByIsoCode('USD');
        $cart->id_currency = $idCurrency > 0 ? $idCurrency : (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_shop = (int) $this->context->shop->id;
        $cart->id_carrier = 0;
        $cart->add();
        $this->context->cart = $cart;
        $this->context->cookie->id_cart = (int) $cart->id;

        $added = false;
        if ($link['link_type'] === DatafastPaymentLink::TYPE_CATALOG && !empty($link['product_refs'])) {
            $refs = json_decode($link['product_refs'], true);
            if (is_array($refs)) {
                foreach ($refs as $ref) {
                    $idProduct = (int) ($ref['id_product'] ?? 0);
                    $qty = (int) ($ref['qty'] ?? 1);
                    if ($idProduct > 0 && $qty > 0) {
                        $cart->updateQty($qty, $idProduct, 0);
                        $added = true;
                    }
                }
            }
        }

        if (!$added) {
            $genericId = (int) $this->module->getOrCreateGenericProductId();
            if ($genericId <= 0) {
                return null;
            }
            $this->applyFixedPrice($genericId, (int) $customer->id, (int) $cart->id, round((float) $link['amount'], 2));
            $cart->updateQty(1, $genericId, 0);
        }
        $cart->update();

        $total = (float) $link['amount'];
        $this->module->validateOrder(
            (int) $cart->id,
            (int) Configuration::get('PS_OS_PAYMENT'),
            $total,
            $this->module->displayName,
            'Pago por link de pago Datafast',
            ['transaction_id' => ($objResponse['id'] ?? '')],
            (int) $cart->id_currency,
            false,
            $customer->secure_key
        );

        $idOrder = (int) $this->module->currentOrder;
        if ($idOrder <= 0) {
            return null;
        }

        return ['id_order' => $idOrder, 'id_cart' => (int) $cart->id, 'customer' => $customer];
    }

    /**
     * Fija el precio (sin impuesto) del producto genérico para este carrito,
     * de modo que el total de la orden = monto del link.
     */
    private function applyFixedPrice(int $idProduct, int $idCustomer, int $idCart, float $price): void
    {
        $sp = new SpecificPrice();
        $sp->id_product = $idProduct;
        $sp->id_product_attribute = 0;
        $sp->id_shop = 0;
        $sp->id_shop_group = 0;
        $sp->id_currency = 0;
        $sp->id_country = 0;
        $sp->id_group = 0;
        $sp->id_customer = $idCustomer;
        $sp->id_cart = $idCart;
        $sp->price = $price;
        $sp->from_quantity = 1;
        $sp->reduction = 0;
        $sp->reduction_tax = 0;
        $sp->reduction_type = 'amount';
        $sp->from = '0000-00-00 00:00:00';
        $sp->to = '0000-00-00 00:00:00';
        $sp->add();
    }

    private function renderSuccess(array $link, array $objResponse): void
    {
        $this->context->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'amount' => $objResponse['amount'] ?? number_format((float) $link['amount'], 2),
            'brand' => $objResponse['paymentBrand'] ?? '',
            'auth_code' => $objResponse['resultDetails']['AuthCode'] ?? '',
            'reference' => $link['reference'],
            'card_holder' => $objResponse['card']['holder'] ?? '',
        ]);
        $this->setTemplate('module:datafast/views/templates/front/paylinkSuccess.tpl');
    }

    private function validateTransaction(string $resultCode): bool
    {
        $testMode = Configuration::get('DATAFAST_DEV', null);
        if (in_array($resultCode, Constants::TRANSACTION_APPROVED_TEST) && $testMode) {
            return true;
        }
        if ($resultCode == Constants::TRANSACTION_APPROVED_PROD && !$testMode) {
            return true;
        }

        return false;
    }

    /**
     * Inserta el registro de la transacción en ps_datafast_transactions
     * (misma estructura que controllers/front/result.php::addTransaction).
     */
    private function recordTransaction($cartId, $customerId, bool $accepted, array $paymentResponse): void
    {
        try {
            $checkout_id = $paymentResponse['id'] ?? null;
            $transaction_id = $paymentResponse['id'] ?? null;
            $result_code = $paymentResponse['result']['code'] ?? '';
            $result_description = $paymentResponse['result']['description'] ?? '';
            $extended_description = $paymentResponse['resultDetails']['ExtendedDescription'] ?? '';
            if ($extended_description == '') {
                $extended_description = $result_description;
            }
            $payment_type = $paymentResponse['paymentType'] ?? '';
            $payment_brand = $paymentResponse['paymentBrand'] ?? '';
            $amount = $paymentResponse['amount'] ?? '';
            $merchant_transactionId = $paymentResponse['merchantTransactionId'] ?? '';
            $response = $paymentResponse['resultDetails']['Response'] ?? '';
            $acquirer_response = $paymentResponse['resultDetails']['AcquirerResponse'] ?? '';
            $auth_code = $paymentResponse['resultDetails']['AuthCode'] ?? null;
            $acquirer_code = $paymentResponse['resultDetails']['AcquirerCode'] ?? null;
            $batch_no = $paymentResponse['resultDetails']['BatchNo'] ?? null;
            $interest = $paymentResponse['resultDetails']['Interest'] ?? null;
            $total_amount = $paymentResponse['resultDetails']['TotalAmount'] ?? null;
            $reference_no = $paymentResponse['resultDetails']['ReferenceNo'] ?? null;
            $bin = $paymentResponse['card']['bin'] ?? null;
            $last_4_Digits = $paymentResponse['card']['last4Digits'] ?? null;
            $email = $paymentResponse['customer']['email'] ?? null;
            $shopper_mid = $paymentResponse['customParameters']['SHOPPER_MID'] ?? null;
            $shopper_tid = $paymentResponse['customParameters']['SHOPPER_TID'] ?? null;
            $timestamp = $paymentResponse['timestamp'] ?? '';
            $response_json = json_encode($paymentResponse);
            $status = $accepted ? '1' : '0';

            $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_transactions
                (`cart_id`, `customer_id`, `transaction_id`, `checkout_id`, `payment_type`,
                 `payment_brand`, `amount`, `merchant_transactionId`, `result_code`,
                 `result_description`, `extended_description`, `acquirer_response`, `response`,
                 `auth_code`, `acquirer_code`, `batch_no`, `interest`, `total_amount`,
                 `reference_no`, `bin`, `last_4_Digits`, `email`, `shopper_mid`, `shopper_tid`,
                 `timestamp`, `response_json`, `status`, `updated_at`)
                VALUES (
                    ' . (int) $cartId . ',
                    \'' . pSQL((string) $customerId) . '\',
                    \'' . pSQL((string) $transaction_id) . '\',
                    \'' . pSQL((string) $checkout_id) . '\',
                    \'' . pSQL($payment_type) . '\',
                    \'' . pSQL($payment_brand) . '\',
                    \'' . pSQL((string) $amount) . '\',
                    \'' . pSQL($merchant_transactionId) . '\',
                    \'' . pSQL($result_code) . '\',
                    \'' . pSQL($result_description) . '\',
                    \'' . pSQL($extended_description) . '\',
                    \'' . pSQL($acquirer_response) . '\',
                    \'' . pSQL($response) . '\',
                    \'' . pSQL((string) $auth_code) . '\',
                    \'' . pSQL((string) $acquirer_code) . '\',
                    \'' . pSQL((string) $batch_no) . '\',
                    \'' . pSQL((string) $interest) . '\',
                    \'' . pSQL((string) $total_amount) . '\',
                    \'' . pSQL((string) $reference_no) . '\',
                    \'' . pSQL((string) $bin) . '\',
                    \'' . pSQL((string) $last_4_Digits) . '\',
                    \'' . pSQL((string) $email) . '\',
                    \'' . pSQL((string) $shopper_mid) . '\',
                    \'' . pSQL((string) $shopper_tid) . '\',
                    \'' . pSQL($timestamp) . '\',
                    \'' . pSQL($response_json) . '\',
                    \'' . pSQL($status) . '\',
                    \'' . date('Y-m-d H:i:s') . '\'
                )';
            Db::getInstance()->execute($query);
        } catch (\Exception $e) {
            $this->safeLog('error', 'Error al guardar transacción (paylink): ' . $e->getMessage());
        }
    }

    private function getLogger(): ?Logger
    {
        try {
            $logFolder = Constants::LOGGER_FOLDER;
            if (!file_exists($logFolder)) {
                mkdir($logFolder, 0777, true);
            }
            $logger = new Logger('PaylinkResultController');
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
}
