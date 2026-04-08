# Punto de Venta Base

Base Laravel para evolucionar un sistema de punto de venta propio. Incluye modulos de ventas, compras, caja, inventario, catalogos, roles, exportacion a Excel y comprobantes PDF.

## Estado actual

Esta base ya es util para demo y desarrollo local, pero aun requiere refactor en reglas de negocio criticas como venta, caja e inventario antes de usarse como producto final.

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

## Sprint 1 completado en esta base

- Identidad neutral del proyecto
- README actualizado
- Seeder demo mas generico
- Configuracion de zona horaria por entorno
- Carpeta `tests/Unit` creada para evitar fallo inicial de PHPUnit
- Backlog tecnico inicial para Sprint 2 en `docs/SPRINT_2_BACKLOG.md`

## Riesgos conocidos

- La venta aun confia demasiado en datos enviados por frontend.
- Caja, inventario y reportes necesitan refactor funcional.
- La cobertura de pruebas sigue siendo baja.

## Siguiente paso sugerido

Ejecutar el Sprint 2 para reforzar validacion, permisos y seguridad transaccional.

## Licencia

Este proyecto se distribuye bajo licencia MIT. Revisa `LICENSE` para el texto completo.
