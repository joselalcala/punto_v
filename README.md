# Punto de Venta Base

Base Laravel para evolucionar un sistema de punto de venta propio. Incluye ventas, compras, caja, inventario, kardex, catalogos, configuracion de empresa, dashboard operativo, exportacion a Excel y comprobantes PDF.

## Estado actual

La base ya es funcional para demo, desarrollo local y presentación académica. Durante los sprints se reforzaron ventas, compras, caja, inventario, configuración operativa y vistas de seguimiento.

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL o MariaDB
- XAMPP u otro stack local equivalente

## Instalacion local

1. Clona o descarga el repositorio.
2. Instala dependencias:

```bash
composer install
```

3. Crea tu archivo `.env` a partir de `.env.example`.
4. Ajusta al menos estas variables:

```env
APP_NAME="Punto de Venta Base"
APP_URL=http://localhost
APP_TIMEZONE=America/Mexico_City

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=punto_v
DB_USERNAME=root
DB_PASSWORD=
```

5. Genera la llave de la aplicacion:

```bash
php artisan key:generate
```

6. Ejecuta migraciones y seeders:

```bash
php artisan migrate
php artisan db:seed
```

7. Crea el enlace a `storage`:

```bash
php artisan storage:link
```

8. Levanta la aplicacion:

```bash
php artisan serve
```

9. Si quieres procesar colas en desarrollo:

```bash
php artisan queue:listen
```

## Credenciales demo

- Usuario: `admin@example.com`
- Contrasena: `12345678`

Estas credenciales se cargan desde `database/seeders/UserSeeder.php`.

## Modulos incluidos

- Ventas
- Compras
- Caja
- Movimientos
- Inventario
- Kardex
- Productos, categorias, marcas y presentaciones
- Clientes y proveedores
- Usuarios, roles y perfil
- Configuracion de empresa
- Exportacion Excel y PDF
- Dashboard con métricas operativas
- Filtros por fecha y método de pago en compras y ventas

## Calidad actual

- `php artisan test` pasa con cobertura feature para login, ventas, compras, caja, dashboard, empresa, movimientos y categorías
- Los flujos críticos de venta y compra ya corren desde servicios transaccionales
- El sistema permite configurar comprobantes, métodos de pago, alertas de stock y ubicación de caja

## Resumen de sprints completados

- Sprint 1: limpieza de base, identidad y entorno
- Sprint 2: validación y seguridad de ventas/movimientos/exportaciones
- Sprint 3: servicios transaccionales de venta, compra e inventario
- Sprint 4: configuración general, comprobantes y caja por ubicación
- Sprint 5: dashboard y reportes operativos
- Sprint 6: cierre de calidad, pruebas y documentación

## Riesgos conocidos

- Falta ampliar cobertura de pruebas para más escenarios de autorización fina y errores de integración
- El frontend comercial aún puede modernizarse más adelante para mejorar velocidad de captura
- Para entornos reales faltaría endurecer correo, backups y despliegue productivo

## Documentación útil

- Backlog inicial: `docs/SPRINT_2_BACKLOG.md`
- Checklist de entrega: `docs/ENTREGA_CHECKLIST.md`
- Manual rápido de uso: `docs/MANUAL_USO.md`

## Licencia

Este proyecto se distribuye bajo licencia MIT. Revisa `LICENSE` para el texto completo.
