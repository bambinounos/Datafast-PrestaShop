# Guía rápida — Links de Pago Datafast

**Para el personal de tienda.** Cómo cobrar con tarjeta **sin datáfono**, enviando un link por WhatsApp, Telegram o correo. El cliente **no necesita registrarse** en la tienda.

---

## 1. ¿Qué es un link de pago?

Un enlace que le envías al cliente. Él lo abre, paga con su tarjeta y listo. Sirve para vender en el local, por teléfono o por redes sociales sin necesidad de un datáfono físico.

---

## 2. Generar un link (paso a paso)

1. Entra al **panel de administración** de PrestaShop.
2. Ve a **Módulos → Datafast → Configurar**.
3. Baja hasta la sección **“Links de Pago — Cobra sin datáfono”**.
4. Elige el **tipo de cobro**:
   - **Monto libre** → escribe el monto (ej. `25.00`) y elige si **incluye IVA** o es **sin IVA**.
   - **Productos del catálogo** → selecciona los productos y sus cantidades (el sistema suma el total y descuenta stock).
5. *(Opcional)* Escribe una **Referencia** (ej. “Venta mostrador #45”) y una **Descripción** que verá el cliente.
6. *(Opcional)* Ajusta **“Vence en (días)”** — por defecto 7.
7. Clic en **“Generar link”**.

---

## 3. Enviar el link

Después de generarlo aparece un recuadro con la **URL**:

- Clic en **“Copiar”** y pégalo en tu chat, **o**
- Clic en **“Enviar por WhatsApp”** (abre WhatsApp con el mensaje ya escrito).

> 💡 Cada link es para **un solo cobro**. Genera uno por cada venta.

---

## 4. ¿Qué hace el cliente?

1. Abre el link en su celular o computadora (**sin registrarse**).
2. Ve el monto y completa: **nombre, correo, cédula/RUC y teléfono**.
3. Ingresa los datos de su **tarjeta** y paga.
4. Ve la **confirmación** del pago.

---

## 5. ¿Cómo sé si pagó?

En la sección **“Links de Pago generados”** cada link muestra su **estado**:

| Estado | Significado |
|--------|-------------|
| **Pendiente** | Aún no paga. |
| **Pagado** ✅ | Pago exitoso. Se creó el pedido (verás el número de pedido). |
| **Expirado** | Pasó la fecha de vencimiento sin pagar. |
| **Cancelado** | Tú lo cancelaste. |

Cada pago aprobado crea un **pedido** normal en el menú **Pedidos**, igual que una venta online.

---

## 6. Preguntas frecuentes

- **El cliente dice que el link expiró** → genera uno nuevo.
- **Envié un link por error y quiero anularlo** → clic en el ícono **cancelar (✕)** junto al link *Pendiente*.
- **¿Puedo reenviar el mismo link?** → Sí, mientras esté **Pendiente** y no haya expirado. Un link **Pagado** no se puede volver a usar.
- **¿Cómo hago un reembolso?** → Desde el panel de **transacciones** de Datafast, igual que en una venta normal del botón.
- **El cliente dice que pagó pero no veo el pedido** → Revisa el estado del link. Si dice **Pagado** con número de pedido, está en **Pedidos**. Si quedó **Pendiente**, el pago no se completó: pídele al cliente que vuelva a intentar.

---

## 7. Recomendaciones

- ✅ **Verifica el monto** antes de generar y enviar el link.
- ✅ Envía el link **solo al cliente** que corresponde.
- ⚠️ Si el módulo está en **modo de pruebas**, los pagos **no son reales**. Para cobrar de verdad, el administrador debe ponerlo en **producción**.
- ⚠️ Nunca pidas al cliente los datos de su tarjeta por chat o teléfono. Él los ingresa **solo** en la página segura del link.
