# Datafast — Módulo de Pagos para PrestaShop

Módulo de pagos de la pasarela **Datafast** (Ecuador) para PrestaShop. Permite cobrar con
tarjeta de crédito/débito (Visa, Mastercard, Amex, Diners, Discover) mediante el widget
**COPYandPAY** de Datafast, con tokenización, tipos de crédito (corriente, diferido con/sin
interés, diferido plus), reembolsos desde el backoffice, panel de transacciones y
**links de pago** para cobrar sin datáfono y sin registro del cliente.

- Idioma del proyecto: **español** (README, commits, strings de UI y documentación).
- Licencia propietaria (Datafast / Sismetic). Sin framework de pruebas activo.

## Compatibilidad (verificada)

| Componente | Versión |
|---|---|
| PHP | >= 8.1 |
| PrestaShop | 1.7.6.0 – 9.x (verificado contra core 9.0.3) |
| Moneda | USD |
| Dependencias runtime | `ext-curl`, `ext-json`, `monolog/monolog` |

## Arquitectura

Módulo clásico de PrestaShop (`PaymentModule`), sin build system ni bundler. El flujo de
pago usa la API Dataweb de Datafast: se solicita un `checkoutId` al backend, el widget JS
(`paymentWidgets.js`) captura la tarjeta, y el controlador de resultado valida y crea el pedido.

### Mapa de archivos

| Ruta | Contenido |
|---|---|
| `datafast.php` | Clase principal `datafast extends PaymentModule` (~2500 líneas): instalación, hooks, configuración, backoffice (transacciones, reembolsos, tipos de crédito, links de pago), creación de tablas. |
| `controllers/front/` | Controladores públicos: `result.php` (resultado del checkout), `error.php`, `paylink.php` (página pública del link de pago, `$auth = false`), `paylinkresult.php` (crea pedido invitado), `ajaxcall.php` y `ajaxtest.php` (endpoints AJAX como `ModuleFrontController` por compatibilidad PS9). |
| `ajax-call.php` (raíz) | Endpoint AJAX legacy; `api/ajax-test-call.php` es su equivalente de prueba. |
| `src/classes/datafast/payment/` | Espacio de nombres `datafast\payment\` (PSR-4 en `composer.json`), pero la carga real usa `include_once` explícitos en `datafast.php`. Contiene `PaymentService.php` (cURL contra Dataweb), `Config.php` (lee `Configuration` y arma `DatafastRequest`), `datafastDB.php` y `model/` (DTOs: `DatafastRequest`, `PaymentResponse`, `DatafastPaymentLink`, `DatafastInstallments`, etc.). |
| `views/templates/admin/` | Plantillas Smarty del backoffice (configuración, grillas de transacciones y links de pago). |
| `views/templates/hook/` | `datafastPayment.tpl` (opción de pago en checkout) y `confirmation.tpl`. |
| `views/templates/front/` | `paylink.tpl`, `paylinkSuccess.tpl`, `error.tpl`. |
| `upgrade/` | Scripts de actualización `upgrade-X.Y.Z.php` (idempotentes). |
| `docs/` | Guías de usuario (p. ej. `GUIA-LINKS-DE-PAGO.md`). |
| `config.xml` | Metadatos del módulo leídos por PrestaShop (mantener versión sincronizada con `datafast.php`). `config_es.xml` es un remanente legacy (aún dice v1.1.6). |

### Tablas propias (prefijo `_DB_PREFIX_`, `datafast_`)

`datafast_transactions`, `datafast_installments`, `datafast_termtype`,
`datafast_customertoken`, `datafast_paymentlinks` — creadas en la instalación con
`CREATE TABLE IF NOT EXISTS`.

### Hooks registrados

`paymentOptions`, `paymentReturn`, `displayHeader`, `actionOrderStatusUpdate`.
`ensureHooksRegistered()` además fuerza filas en `ps_module_shop`, `ps_module_country`
y `ps_module_currency` (requisito de PS9 para dispatch de hooks de pago).

### Claves de configuración (`Configuration`)

- Credenciales Dataweb: `DATAFAST_DEV`, `DATAFAST_DEVURL`, `DATAFAST_PRODULR`
  (sic: el typo es histórico, no renombrar sin migración), `DATAFAST_BEARER_TOKEN`,
  `DATAFAST_ENTITY_ID`, `DATAFAST_MID`, `DATAFAST_TID`, `DATAFAST_RISK`,
  `DATAFAST_PROVEEDOR`, `DATAFAST_ECI`, `DATAFAST_PREFIJOTRX`.
- Links de pago: `DATAFAST_PAYLINK_EXPIRY_DAYS`, `DATAFAST_PAYLINK_IVA_RATE`,
  `DATAFAST_PAYLINK_CREATE_ORDER`, `DATAFAST_PAYLINK_GENERIC_PRODUCT`.

## Construcción y despliegue

No hay paso de compilación. El módulo se entrega como carpeta dentro de PrestaShop:

```bash
# Dependencias (monolog); vendor/ está en .gitignore
composer install

# Despliegue: la carpeta DEBE llamarse exactamente "datafast" dentro de modules/
cp -r . /ruta/a/prestashop/modules/datafast
# Luego: backoffice → Módulos → buscar "Datafast" → instalar
```

- Al versionar: actualizar la versión en **tres lugares** — constructor de `datafast.php`,
  `config.xml` y changelog del `README.md`; si hay cambio de esquema o configuración,
  agregar `upgrade/upgrade-X.Y.Z.php` idempotente.
- **Pruebas:** no existe suite de tests (`phpunit ^9` está en `require-dev` pero no hay
  carpeta `tests/`). La validación se hace instalando el módulo en un PrestaShop real
  (ambiente de prueba Datafast con `DATAFAST_DEV=1`) y verificando el flujo completo de
  pago, reembolso y links de pago. No afirmar que algo funciona sin probarlo en una
  instalación real.

## Convenciones de desarrollo

### Seguridad (obligatorio — auditoría v2.1.0)

- SQL: escapar SIEMPRE con `pSQL()`; cast `(int)` en IDs numéricos. Nunca
  `str_replace("'", "\\'")`.
- Smarty: escapar con `|escape:'html':'UTF-8'` toda variable de origen externo
  (API, URL, POST) en las plantillas.
- Mensajes HTML dinámicos en PHP: `htmlspecialchars()`.
- Los montos de links de pago se resuelven SIEMPRE del lado del servidor (token opaco;
  nada manipulable por URL).

### Compatibilidad PrestaShop 9

- El constructor del módulo no debe hacer I/O de archivos ni trabajo pesado.
- No usar APIs removidas: hook legacy `payment`, `_PS_BASE_URL_`
  (usar `$this->context->shop->getBaseURL(true)` / `Tools::getShopDomainSsl(true)`),
  `StockAvailable::setProductDependsOnStock()`, propiedad `$guestAllowed` en
  controladores (el acceso sin login se controla con `$auth = false`).
- Endpoints AJAX como `ModuleFrontController`, no scripts sueltos con `echo`
  (usar `$this->_html` / salidas controladas para evitar `headers already sent`).
- Traducciones: `$this->trans('Texto', [], 'Modules.Datafast.Admin')`, nada de strings
  hardcodeados en contextos de clase.

### Robustez del flujo de pago

Regla de oro: **el módulo nunca debe romper el checkout ni mostrar páginas en blanco**.

- `try/catch (\Exception + \Error)` en `postProcess()` de controladores de resultado,
  con redirección a página de error amigable.
- Validar `json_decode()` de respuestas de la API antes de acceder a keys.
- `hookPaymentOptions()` devuelve `[]` si falla (oculta la opción en vez de romper).
- `addTransaction()` con try/catch para que un fallo de BD no aborte el pago.
- Accesos a respuestas de API con `?? ''` / `?? 0`.
- Después de `redirectTo()`, `return` inmediato.

### Estilo

- PHP >= 8.1 con tipos nullable (`?string`, etc.) y null-safety explícita.
- Comentarios en español neutro (sin voseo). Documentar el *por qué*, no el *qué*.
- Logging con Monolog (`safeLog()` en `PaymentService`), nunca `var_dump`/`echo` en
  producción (hubo una release dedicada a limpiar código de diagnóstico).

### Commits y versiones

- Mensajes en español, estilo convencional con sufijo de versión:
  `fix: Descripción (vX.Y.Z)`, `feat: ...`, `release: ...`, `debug: ...`.
- Cada release sube la versión del módulo y actualiza el changelog detallado en README.

## Trampas conocidas

- `DATAFAST_PRODULR` contiene un typo histórico; es la clave real en producción.
- El nombre de clase `datafast` (minúscula) debe coincidir con el nombre de carpeta y
  archivo (`datafast.php`) — exigencia de PrestaShop.
- `config_es.xml` quedó desactualizado desde v1.1.6; el metadata vigente es `config.xml`.
- Los `include_once` explícitos en `datafast.php` son el mecanismo de carga efectivo;
  agregar una clase nueva requiere añadirla también ahí (el autoload PSR-4 no siempre
  está disponible en el contexto de PrestaShop).
- Los links de eliminación del backoffice usan event listeners con polyfill
  `confirm_link` (PS9); no volver a `<a onclick>`.
