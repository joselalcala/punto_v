# Sprint 2 Backlog

## Objetivo

Cerrar huecos de seguridad y validacion antes de tocar el rediseño grande del flujo transaccional.

## Prioridad alta

- Recalcular subtotal, impuesto y total de venta en backend.
- Validar stock disponible antes de registrar una venta.
- Impedir que un usuario cree movimientos manuales con tipo `VENTA`.
- Filtrar exportaciones por usuario autenticado.
- Proteger la descarga de PDF de ventas para que solo el propietario pueda verla.
- Regenerar sesion despues del login.

## Prioridad media

- Validar arrays de detalle en compra y venta.
- Evitar descuentos de inventario si falta kardex o inventario inicializado.
- Revisar numeracion de comprobantes para escenarios concurrentes.
- Homologar codificacion de textos con caracteres dañados.

## Prioridad baja

- Revisar nombres de controladores con convencion PSR.
- Consolidar mensajes de error y logs funcionales.
- Preparar casos de prueba base para caja, venta e inventario.
