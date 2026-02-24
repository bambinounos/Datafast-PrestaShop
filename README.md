# Datafast - Módulo de Pagos para PrestaShop

Módulo de pagos de **Datafast** para PrestaShop. Permite procesar pagos con tarjeta de crédito/débito a través de la pasarela Datafast en tiendas PrestaShop.

## Compatibilidad

| Componente    | Versión soportada       |
|---------------|-------------------------|
| PHP           | >= 8.1                  |
| PrestaShop    | 1.7.x - 9.x            |
| Moneda        | USD                     |

## Funcionalidades

- Pago con tarjeta de crédito y débito (Visa, Mastercard, American Express, Diners Club, Discover)
- Tokenización de tarjetas para pagos recurrentes
- Tipos de crédito configurables (corriente, diferido con/sin interés, diferido plus)
- Reembolsos desde el backoffice
- Ambientes de prueba y producción
- Recuperación de transacciones
- Panel de transacciones en el backoffice

## Instalación

1. Copiar la carpeta `datafast` en el directorio `modules/` de PrestaShop
2. Acceder al backoffice > Módulos > Buscar "Datafast"
3. Instalar y configurar con las credenciales proporcionadas por Datafast

### Configuración requerida

- **Entity ID**: Proporcionado por Datafast
- **Authorization Bearer**: Token de autenticación
- **MID**: Merchant ID
- **TID**: Terminal ID
- **RISK**: Parámetro de riesgo
- **PROVEEDOR**: Identificador de proveedor
- **PREFIJO TRX**: Prefijo para identificar transacciones

## Changelog

### v2.0.0 (2026-02-24)
Actualización de compatibilidad para PHP 8.1+ y PrestaShop 9.x.

#### Cambios principales
- **PHP 8.1+**: Requisito mínimo actualizado de PHP 7.2 a PHP 8.1
- **PrestaShop 9.x**: Compatibilidad verificada con PrestaShop 9.0.3
- **Eliminada dependencia httpful**: Se removió `nategood/httpful` (abandonada). El módulo ya usaba cURL directamente
- **PHPUnit actualizado**: De ^8 a ^9 para compatibilidad con PHP 8.1

#### Fixes de tipos nullable (PHP 8.1 strict types)
- Todos los getters/setters de modelos (`PaymentResultDetails`, `PaymentCustomer`, `PaymentResult`, `PaymentResponse`, `PaymentCard`, `CardBrands`, `DatafastRequest`) actualizados con tipos nullable (`?string`, `?bool`, `?PaymentResult`, etc.)
- `PaymentResponse::paymentError()` corregido con parámetros default
- `DatafastRequest::setAmount()` acepta valores mixtos con cast interno a `(float)`

#### Fixes de null safety
- Valores de `Configuration::get()` en `Config.php` protegidos con `(string)($value ?? '')`
- Accesos a respuestas JSON de refund protegidos con `?? ''` (datafast.php y result.php)
- Valores de BD pasados a `round()` protegidos con `?? 0`
- `Tools::getValue('MD')` casteado a `(string)` antes de `trim()`

#### Fixes de rutas y constantes removidas
- Includes en `datafast.php` cambiados de rutas relativas a `__DIR__ . '/...'`
- `ajax-call.php`: Corregida ruta faltante de separador `/` en `dirname(__FILE__)`
- `api/ajax-test-call.php`: Include cambiado a usar `__DIR__`
- `_PS_BASE_URL_` (removida en PS 9) reemplazada por `$this->context->shop->getBaseURL(true)` y `Tools::getShopDomainSsl(true)`

#### Fixes de compatibilidad PrestaShop 9
- Removido `$this->registerHook('payment')` (hook legacy eliminado en PS 8+)
- Versión mínima PS actualizada a `1.7.0`
- `displayViewCustomerLink()`: Parámetros reordenados (required antes de optional)

#### Variables no inicializadas
- Inicializadas variables antes de su uso: `$arr`, `$txtOrden`, `$txtIdTrx`, `$data`, `$duplicates`, `$errorsTrxs`, `$success`, `$defaultTermType`, `$defaultInstallments`, `$response` en `requestRefund()`

### v1.1.6 (original)
- Versión original del módulo para PHP 7.2+ y PrestaShop 1.7+

## Soporte

- **Issues**: [GitHub Issues](https://github.com/bambinounos/Datafast-PrestaShop/issues)
- **Autor original**: Sismetic (notificaciones@sismetic.com)

## Licencia

Módulo propietario de Datafast / Sismetic.
