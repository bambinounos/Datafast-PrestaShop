<?php


namespace datafast\payment\model;

/**
 * Acceso a datos para los Links de Pago de Datafast.
 *
 * Sigue el patrón de SQL crudo + pSQL() usado en el resto del módulo
 * (controllers/front/result.php, datafast.php). Las clases del core de
 * PrestaShop se referencian con barra invertida inicial por estar dentro
 * de un namespace.
 */
class DatafastPaymentLink
{
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_AMOUNT = 'amount';
    const TYPE_CATALOG = 'catalog';

    const DEFAULT_EXPIRY_DAYS = 7;

    /**
     * Crea un link de pago y retorna el token generado (64 hex / 256 bits).
     *
     * @param array $data amount, amount_iva0, amount_ivaimp, amount_iva,
     *                    reference, description, currency, link_type,
     *                    product_refs (string JSON), expires_at (opcional)
     */
    public static function createLink(array $data): string
    {
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');

        $expiryDays = (int) \Configuration::get('DATAFAST_PAYLINK_EXPIRY_DAYS');
        if ($expiryDays <= 0) {
            $expiryDays = self::DEFAULT_EXPIRY_DAYS;
        }
        $expiresAt = (!empty($data['expires_at']))
            ? $data['expires_at']
            : date('Y-m-d H:i:s', strtotime('+' . $expiryDays . ' days'));

        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'datafast_paymentlinks`
            (`token`, `reference`, `description`, `amount`, `amount_iva0`,
             `amount_ivaimp`, `amount_iva`, `currency`, `link_type`,
             `product_refs`, `status`, `expires_at`, `created_at`, `updated_at`)
            VALUES (
                \'' . pSQL($token) . '\',
                \'' . pSQL($data['reference'] ?? '') . '\',
                \'' . pSQL($data['description'] ?? '') . '\',
                ' . (float) ($data['amount'] ?? 0) . ',
                ' . (float) ($data['amount_iva0'] ?? 0) . ',
                ' . (float) ($data['amount_ivaimp'] ?? 0) . ',
                ' . (float) ($data['amount_iva'] ?? 0) . ',
                \'' . pSQL($data['currency'] ?? 'USD') . '\',
                \'' . pSQL($data['link_type'] ?? self::TYPE_AMOUNT) . '\',
                \'' . pSQL($data['product_refs'] ?? '') . '\',
                \'' . self::STATUS_PENDING . '\',
                \'' . pSQL($expiresAt) . '\',
                \'' . pSQL($now) . '\',
                \'' . pSQL($now) . '\'
            )';
        \Db::getInstance()->execute($sql);

        return $token;
    }

    /**
     * @return array|null fila completa o null si no existe.
     */
    public static function getByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'datafast_paymentlinks`
                WHERE `token` = \'' . pSQL($token) . '\'';
        $row = \Db::getInstance()->getRow($sql);

        return $row ? $row : null;
    }

    /**
     * Indica si un link puede usarse para pagar (pendiente y no expirado).
     */
    public static function isUsable(array $link): bool
    {
        if (($link['status'] ?? '') !== self::STATUS_PENDING) {
            return false;
        }

        return !self::isExpired($link);
    }

    public static function isExpired(array $link): bool
    {
        $expiresAt = $link['expires_at'] ?? null;

        return $expiresAt && strtotime($expiresAt) < time();
    }

    /**
     * Guarda los datos del pagador ingresados en la página pública.
     */
    public static function savePayer(string $token, array $payer): void
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'datafast_paymentlinks` SET
                `payer_name` = \'' . pSQL($payer['name'] ?? '') . '\',
                `payer_email` = \'' . pSQL($payer['email'] ?? '') . '\',
                `payer_dni` = \'' . pSQL($payer['dni'] ?? '') . '\',
                `payer_phone` = \'' . pSQL($payer['phone'] ?? '') . '\',
                `updated_at` = \'' . date('Y-m-d H:i:s') . '\'
                WHERE `token` = \'' . pSQL($token) . '\'';
        \Db::getInstance()->execute($sql);
    }

    public static function saveCheckoutId(string $token, string $checkoutId): void
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'datafast_paymentlinks` SET
                `checkout_id` = \'' . pSQL($checkoutId) . '\',
                `updated_at` = \'' . date('Y-m-d H:i:s') . '\'
                WHERE `token` = \'' . pSQL($token) . '\'';
        \Db::getInstance()->execute($sql);
    }

    /**
     * Marca el link como pagado de forma idempotente (solo si está pendiente).
     *
     * @return bool true si esta llamada fue la que efectivamente lo marcó.
     */
    public static function markPaid(string $token, ?int $idOrder, ?int $idCart, ?string $idTransaction): bool
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'datafast_paymentlinks` SET
                `status` = \'' . self::STATUS_PAID . '\',
                `id_order` = ' . ($idOrder ? (int) $idOrder : 'NULL') . ',
                `id_cart` = ' . ($idCart ? (int) $idCart : 'NULL') . ',
                `id_transaction` = \'' . pSQL((string) $idTransaction) . '\',
                `paid_at` = \'' . date('Y-m-d H:i:s') . '\',
                `updated_at` = \'' . date('Y-m-d H:i:s') . '\'
                WHERE `token` = \'' . pSQL($token) . '\'
                AND `status` = \'' . self::STATUS_PENDING . '\'';
        \Db::getInstance()->execute($sql);

        return (int) \Db::getInstance()->Affected_Rows() > 0;
    }

    public static function markStatus(string $token, string $status): void
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'datafast_paymentlinks` SET
                `status` = \'' . pSQL($status) . '\',
                `updated_at` = \'' . date('Y-m-d H:i:s') . '\'
                WHERE `token` = \'' . pSQL($token) . '\'';
        \Db::getInstance()->execute($sql);
    }

    /**
     * @return array filas de links ordenadas de la más reciente a la más antigua.
     */
    public static function listLinks(int $limit = 200): array
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'datafast_paymentlinks`
                ORDER BY `id_paymentlink` DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $rows = \Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }
}
