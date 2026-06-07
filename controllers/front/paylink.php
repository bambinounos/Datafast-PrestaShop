<?php

use datafast\payment\PaymentService;
use datafast\payment\datafast\payment\Config;
use datafast\payment\datafast\payment\model\Payment;
use datafast\payment\model\Amount;
use datafast\payment\model\CartInfo;
use datafast\payment\model\CustomerInfo;
use datafast\payment\model\ProductInfo;
use datafast\payment\model\DatafastPaymentLink;

/**
 * Página pública de pago por link (sin login / sin registro del cliente).
 * Muestra un formulario mínimo del pagador y luego el widget COPYandPAY.
 */
class datafastPaylinkModuleFrontController extends ModuleFrontController
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
            $this->renderMessage('Este link de pago no es válido o no existe.');
            return;
        }
        if ($link['status'] === DatafastPaymentLink::STATUS_PAID) {
            $this->renderMessage('Este link de pago ya fue pagado. ¡Gracias por tu compra!');
            return;
        }
        if ($link['status'] === DatafastPaymentLink::STATUS_CANCELLED) {
            $this->renderMessage('Este link de pago fue cancelado por el comercio.');
            return;
        }
        if (DatafastPaymentLink::isExpired($link)) {
            DatafastPaymentLink::markStatus($token, DatafastPaymentLink::STATUS_EXPIRED);
            $this->renderMessage('Este link de pago expiró. Solicita uno nuevo al comercio.');
            return;
        }

        $this->context->smarty->assign([
            'paylink_state' => 'form',
            'paylink_token' => $token,
            'paylink_amount' => number_format((float) $link['amount'], 2),
            'paylink_reference' => $link['reference'],
            'paylink_description' => $link['description'],
            'paylink_form_action' => $this->context->link->getModuleLink('datafast', 'paylink', ['t' => $token], true),
            'shop_name' => $this->context->shop->name,
            'paylink_error' => '',
            'payer_name' => Tools::getValue('payer_name', $link['payer_name']),
            'payer_email' => Tools::getValue('payer_email', $link['payer_email']),
            'payer_dni' => Tools::getValue('payer_dni', $link['payer_dni']),
            'payer_phone' => Tools::getValue('payer_phone', $link['payer_phone']),
        ]);

        if (Tools::isSubmit('submitDatafastPayer')) {
            $payer = [
                'name' => trim((string) Tools::getValue('payer_name')),
                'email' => trim((string) Tools::getValue('payer_email')),
                'dni' => trim((string) Tools::getValue('payer_dni')),
                'phone' => trim((string) Tools::getValue('payer_phone')),
            ];
            $error = $this->validatePayer($payer);
            if ($error !== '') {
                $this->context->smarty->assign('paylink_error', $error);
            } else {
                DatafastPaymentLink::savePayer($token, $payer);
                $checkoutId = $this->buildCheckout($link, $payer);
                if (empty($checkoutId)) {
                    $this->context->smarty->assign('paylink_error', 'No se pudo iniciar el pago. Por favor intenta nuevamente en unos minutos.');
                } else {
                    DatafastPaymentLink::saveCheckoutId($token, $checkoutId);
                    $this->assignWidget($link, $token, $checkoutId);
                }
            }
        }

        $this->setTemplate('module:datafast/views/templates/front/paylink.tpl');
    }

    private function renderMessage(string $message): void
    {
        $this->context->smarty->assign([
            'paylink_state' => 'message',
            'paylink_message' => $message,
            'shop_name' => $this->context->shop->name,
        ]);
        $this->setTemplate('module:datafast/views/templates/front/paylink.tpl');
    }

    private function validatePayer(array $payer): string
    {
        if (mb_strlen($payer['name']) < 3) {
            return 'Ingresa tu nombre completo.';
        }
        if (!Validate::isEmail($payer['email'])) {
            return 'Ingresa un correo electrónico válido.';
        }
        $dni = preg_replace('/\D/', '', $payer['dni']);
        if (strlen($dni) < 10) {
            return 'Ingresa tu cédula o RUC (mínimo 10 dígitos).';
        }
        if (mb_strlen($payer['phone']) < 7) {
            return 'Ingresa un número de teléfono válido.';
        }

        return '';
    }

    /**
     * Construye el checkout en Datafast reutilizando PaymentService/Config/modelos,
     * sin depender de un cliente PrestaShop logueado.
     *
     * @return string checkoutId o cadena vacía si falla.
     */
    private function buildCheckout(array $link, array $payer): string
    {
        try {
            $request = (new Config())->getDatafastRequest();

            $amount = new Amount();
            $amount->setTotal((float) $link['amount']);
            $amount->setSubtotalIVA((float) $link['amount_ivaimp']);
            $amount->setSubtotalIVA0((float) $link['amount_iva0']);

            $parts = preg_split('/\s+/', trim($payer['name']), 2);
            $given = $parts[0] ?? 'Cliente';
            $surname = (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : $given;
            $reference = ($link['reference'] !== '' && $link['reference'] !== null) ? $link['reference'] : 'Punto de venta';

            $customerInfo = new CustomerInfo(
                $given,
                '',
                $surname,
                Tools::getRemoteAddr(),
                'PL' . (int) $link['id_paymentlink'],
                (string) $link['token'],
                $payer['email'],
                preg_replace('/\D/', '', $payer['dni']),
                $payer['phone'],
                $reference,
                'EC',
                $reference,
                'EC',
                '000000'
            );

            $payment = new Payment();
            $payment->setRequest($request);
            $payment->setAmount($amount);
            $payment->setProductInfo([$this->buildProductInfo($link)]);
            $payment->setCustomerInfo($customerInfo);
            $payment->setRegistrations([]);
            $payment->setCartInfo(new CartInfo(0));

            return (string) (new PaymentService())->requestCheckoutId($payment);
        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('[Datafast] paylink buildCheckout error: ' . $e->getMessage(), 3);

            return '';
        }
    }

    /**
     * @return ProductInfo[]
     */
    private function buildProductInfo(array $link): array
    {
        $items = [];
        if ($link['link_type'] === DatafastPaymentLink::TYPE_CATALOG && !empty($link['product_refs'])) {
            $refs = json_decode($link['product_refs'], true);
            if (is_array($refs)) {
                $idLang = (int) $this->context->language->id;
                foreach ($refs as $ref) {
                    $idProduct = (int) ($ref['id_product'] ?? 0);
                    $qty = (int) ($ref['qty'] ?? 1);
                    if ($idProduct <= 0) {
                        continue;
                    }
                    $product = new Product($idProduct, false, $idLang);
                    $name = is_array($product->name) ? (string) reset($product->name) : (string) $product->name;
                    if ($name === '') {
                        $name = 'Producto #' . $idProduct;
                    }
                    $price = (float) Product::getPriceStatic($idProduct, true, 0, 2);
                    $items[] = new ProductInfo($name, $name, $price, $qty > 0 ? $qty : 1);
                }
            }
        }

        if (count($items) === 0) {
            $name = ($link['reference'] !== '' && $link['reference'] !== null) ? $link['reference'] : 'Pago';
            $desc = ($link['description'] !== '' && $link['description'] !== null) ? $link['description'] : 'Pago por link Datafast';
            $items[] = new ProductInfo($name, $desc, (float) $link['amount'], 1);
        }

        return $items;
    }

    private function assignWidget(array $link, string $token, string $checkoutId): void
    {
        $request = (new Config())->getDatafastRequest();

        $style = (Configuration::get('DATAFAST_STYLE') == '1') ? 'card' : 'plain';
        $requirecvv = (Configuration::get('DATAFAST_CVV') == '1') ? 'true' : 'false';

        $termtypes = [];
        $defaultTermType = '';
        $defaultInstallments = '';
        if (class_exists('DatafastInstallments')) {
            $rows = DatafastInstallments::getsInstallmentsTermTypeConfiguration();
            if ($rows) {
                $i = 0;
                foreach ($rows as $row) {
                    $termtypes[$i]['name'] = $row['name'];
                    $termtypes[$i]['code'] = $row['code'];
                    if ($i === 0) {
                        $defaultTermType = $row['codeTermType'];
                        $defaultInstallments = $row['installments'];
                    }
                    ++$i;
                }
            }
        }

        $this->context->smarty->assign([
            'paylink_state' => 'widget',
            'checkScript' => $request->getCheckoutScript() . $checkoutId,
            'action' => $this->context->link->getModuleLink('datafast', 'paylinkresult', ['t' => $token], true),
            'style' => $style,
            'requirecvv' => $requirecvv,
            'customertoken' => 'false',
            'termtypes' => $termtypes,
            'defaultTermType' => $defaultTermType,
            'defaultInstallments' => $defaultInstallments,
            'removetoken' => '',
        ]);
    }
}
