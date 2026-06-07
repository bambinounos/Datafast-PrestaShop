<?php
/**
 * Upgrade 2.6.0 — Links de Pago Datafast.
 *
 * Crea la tabla ps_datafast_paymentlinks y los valores por defecto necesarios
 * para la nueva funcionalidad de links de pago (cobro sin datáfono y sin
 * registro del cliente). Es idempotente (CREATE TABLE IF NOT EXISTS).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_6_0($module)
{
    $created = $module->createTablePaymentLinks();

    if (Configuration::get('DATAFAST_PAYLINK_EXPIRY_DAYS') === false) {
        Configuration::updateValue('DATAFAST_PAYLINK_EXPIRY_DAYS', 7);
    }
    if (Configuration::get('DATAFAST_PAYLINK_IVA_RATE') === false) {
        Configuration::updateValue('DATAFAST_PAYLINK_IVA_RATE', 0.15);
    }
    if (Configuration::get('DATAFAST_PAYLINK_CREATE_ORDER') === false) {
        Configuration::updateValue('DATAFAST_PAYLINK_CREATE_ORDER', 1);
    }

    // Registra los nuevos controladores públicos.
    $module->registerHook('paymentOptions');

    return (bool) $created;
}
