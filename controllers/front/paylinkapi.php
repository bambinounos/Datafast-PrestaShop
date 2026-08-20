<?php

use datafast\payment\model\DatafastPaymentLink;

/**
 * Controlador API REST / JSON para interacción con sistemas externos (ej. Dolibarr).
 * Permite:
 * - Crear un link de pago con o sin datos del pagador preestablecidos.
 * - Consultar el estado de un link de pago por su token.
 */
class datafastPaylinkapiModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ssl = true;
    public $ajax = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json; charset=utf-8');

        // Verificación de autenticación por API Key
        if (!$this->checkApiKey()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado. Clave API inválida o ausente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $action = trim((string) Tools::getValue('action'));

        switch ($action) {
            case 'create':
                $this->handleCreateLink();
                break;

            case 'status':
                $this->handleGetStatus();
                break;

            case 'ping':
                echo json_encode([
                    'success' => true,
                    'module' => 'datafast',
                    'version' => $this->module->version,
                    'timestamp' => time()
                ], JSON_UNESCAPED_UNICODE);
                exit;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Acción no especificada o desconocida. Acciones soportadas: create, status, ping.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }

    /**
     * Valida la clave API provista en cabecera HTTP o parámetro.
     */
    protected function checkApiKey(): bool
    {
        $configuredKey = trim((string) Configuration::get('DATAFAST_PAYLINK_API_KEY'));
        if ($configuredKey === '') {
            $configuredKey = trim((string) Configuration::getGlobalValue('DATAFAST_PAYLINK_API_KEY'));
        }

        if ($configuredKey === '') {
            return false;
        }

        $providedKey = '';

        // 1. Revisar getallheaders() / apache_request_headers()
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $headerName => $headerValue) {
                    if (strcasecmp((string) $headerName, 'X-Datafast-Api-Key') === 0) {
                        $providedKey = trim((string) $headerValue);
                        break;
                    }
                    if (strcasecmp((string) $headerName, 'Authorization') === 0 && stripos((string) $headerValue, 'Bearer ') === 0) {
                        $providedKey = trim(substr((string) $headerValue, 7));
                        break;
                    }
                }
            }
        }

        // 2. Revisar variables de servidor $_SERVER
        if ($providedKey === '') {
            if (!empty($_SERVER['HTTP_X_DATAFAST_API_KEY'])) {
                $providedKey = trim((string) $_SERVER['HTTP_X_DATAFAST_API_KEY']);
            } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
                $auth = trim((string) $_SERVER['HTTP_AUTHORIZATION']);
                if (stripos($auth, 'Bearer ') === 0) {
                    $providedKey = trim(substr($auth, 7));
                } else {
                    $providedKey = $auth;
                }
            } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $auth = trim((string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
                if (stripos($auth, 'Bearer ') === 0) {
                    $providedKey = trim(substr($auth, 7));
                } else {
                    $providedKey = $auth;
                }
            }
        }

        // 3. Respaldo por parámetro GET / POST / JSON body
        if ($providedKey === '') {
            $providedKey = trim((string) Tools::getValue('api_key'));
        }
        if ($providedKey === '') {
            return false;
        }

        // 4. Comparar con Configuration::get y getGlobalValue
        $configuredKey = trim((string) Configuration::get('DATAFAST_PAYLINK_API_KEY'));
        if ($configuredKey !== '' && hash_equals($configuredKey, $providedKey)) {
            return true;
        }

        $globalKey = trim((string) Configuration::getGlobalValue('DATAFAST_PAYLINK_API_KEY'));
        if ($globalKey !== '' && hash_equals($globalKey, $providedKey)) {
            return true;
        }

        // 5. Consulta directa a la base de datos para mitigar desajustes de multitienda / caché
        try {
            $sql = 'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` = "DATAFAST_PAYLINK_API_KEY"';
            $rows = Db::getInstance()->executeS($sql);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $dbKey = trim((string) ($row['value'] ?? ''));
                    if ($dbKey !== '' && hash_equals($dbKey, $providedKey)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Continuar
        }

        return false;
    }

    /**
     * Procesa la creación de un nuevo link de pago.
     */
    protected function handleCreateLink(): void
    {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);

        $amount = (float) str_replace(',', '.', (string) Tools::getValue('amount', $jsonData['amount'] ?? 0));
        if ($amount <= 0) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'El monto debe ser un valor numérico mayor a cero.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $taxMode = (string) Tools::getValue('tax_mode', $jsonData['tax_mode'] ?? 'iva');
        $reference = trim((string) Tools::getValue('reference', $jsonData['reference'] ?? ''));
        $description = trim((string) Tools::getValue('description', $jsonData['description'] ?? ''));
        $payerName = trim((string) Tools::getValue('payer_name', $jsonData['payer_name'] ?? ''));
        $payerEmail = trim((string) Tools::getValue('payer_email', $jsonData['payer_email'] ?? ''));
        $payerDni = trim((string) Tools::getValue('payer_dni', $jsonData['payer_dni'] ?? ''));
        $payerPhone = trim((string) Tools::getValue('payer_phone', $jsonData['payer_phone'] ?? ''));
        $expiryDays = (int) Tools::getValue('expiry_days', $jsonData['expiry_days'] ?? 0);

        // Desglose de IVA
        $ivaRate = (float) Configuration::get('DATAFAST_PAYLINK_IVA_RATE');
        if ($ivaRate <= 0) {
            $ivaRate = 0.15;
        }

        if ($taxMode === 'no_iva') {
            $amountIva0 = $amount;
            $amountIvaimp = 0.0;
            $amountIva = 0.0;
        } else {
            $amountIvaimp = round($amount / (1.0 + $ivaRate), 2);
            $amountIva = round($amount - $amountIvaimp, 2);
            $amountIva0 = 0.0;
        }

        $linkData = [
            'amount' => $amount,
            'amount_iva0' => $amountIva0,
            'amount_ivaimp' => $amountIvaimp,
            'amount_iva' => $amountIva,
            'reference' => $reference,
            'description' => $description,
            'currency' => 'USD',
            'link_type' => DatafastPaymentLink::TYPE_AMOUNT,
            'payer_name' => $payerName !== '' ? $payerName : null,
            'payer_email' => $payerEmail !== '' ? $payerEmail : null,
            'payer_dni' => $payerDni !== '' ? $payerDni : null,
            'payer_phone' => $payerPhone !== '' ? $payerPhone : null,
        ];

        if ($expiryDays > 0) {
            $linkData['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . $expiryDays . ' days'));
        }

        try {
            $token = DatafastPaymentLink::createLink($linkData);
            $link = DatafastPaymentLink::getByToken($token);

            $publicUrl = $this->context->link->getModuleLink('datafast', 'paylink', ['t' => $token], true);

            $waText = 'Estimado/a cliente, le compartimos el enlace seguro para realizar el pago'
                . ($reference !== '' ? ' de ' . $reference : '')
                . ' por $' . number_format($amount, 2) . ': ' . $publicUrl;

            $waUrl = 'https://wa.me/?text=' . rawurlencode($waText);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'url' => $publicUrl,
                'wa_url' => $waUrl,
                'wa_text' => $waText,
                'amount' => number_format($amount, 2, '.', ''),
                'reference' => $reference,
                'status' => DatafastPaymentLink::STATUS_PENDING,
                'expires_at' => $link['expires_at'] ?? null,
                'created_at' => $link['created_at'] ?? date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno al generar el link de pago: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Consulta el estado actual de un link de pago.
     */
    protected function handleGetStatus(): void
    {
        $token = trim((string) Tools::getValue('token'));
        if ($token === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Debe especificar el token del link de pago.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $link = DatafastPaymentLink::getByToken($token);
        if (!$link) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'El link de pago solicitado no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $isExpired = DatafastPaymentLink::isExpired($link);
        $status = $link['status'];
        if ($status === DatafastPaymentLink::STATUS_PENDING && $isExpired) {
            $status = DatafastPaymentLink::STATUS_EXPIRED;
        }

        echo json_encode([
            'success' => true,
            'token' => $token,
            'status' => $status,
            'amount' => number_format((float) $link['amount'], 2, '.', ''),
            'reference' => $link['reference'] ?? '',
            'payer_name' => $link['payer_name'] ?? '',
            'payer_email' => $link['payer_email'] ?? '',
            'id_order' => !empty($link['id_order']) ? (int) $link['id_order'] : null,
            'id_transaction' => $link['id_transaction'] ?? '',
            'paid_at' => $link['paid_at'] ?? null,
            'expires_at' => $link['expires_at'] ?? null,
            'created_at' => $link['created_at'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
