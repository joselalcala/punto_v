# Checklist de Entrega

## Antes de presentar

- Ejecutar `composer install`
- Configurar `.env`
- Ejecutar `php artisan key:generate`
- Ejecutar `php artisan migrate --seed`
- Ejecutar `php artisan storage:link`
- Levantar colas con `php artisan queue:listen` si se quieren probar exportaciones y correos
- Confirmar acceso con `admin@example.com / 12345678`

## Validaciones rápidas

- Iniciar sesión correctamente
- Abrir una caja
- Registrar una venta
- Registrar una compra
- Ver movimientos de caja
- Probar exportación Excel de ventas
- Probar PDF de comprobante de venta
- Revisar dashboard con métricas

## Comandos de calidad

- Ejecutar `php artisan test`
- Revisar logs en `storage/logs/laravel.log` si aparece algún error

## Riesgos conocidos

- El proyecto ya está mucho más estable, pero aún conviene ampliar cobertura de pruebas de reportes y autorizaciones finas
- La UI sigue dependiendo de jQuery/Bootstrap Select en varias vistas del flujo comercial
- Si se usarán correos reales, hace falta configurar proveedor SMTP en `.env`
