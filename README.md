# Sistema de Gestión de Taller Mecánico "El Flaco"

Este es un sistema integral desarrollado en Laravel para la gestión administrativa y operativa de un taller mecánico. El sistema permite el control de inventarios, órdenes de servicio, gestión de mecánicos y facturación.

## 🚀 Características

- **Gestión de Inventario**: Control de stock de productos, proveedores y movimientos (compras/ventas).
- **Órdenes de Servicio**: Registro detallado de la entrada de vehículos, descripción del problema y seguimiento del estado.
- **Trabajos del Taller**: Asignación de tareas específicas a mecánicos con cálculo de costos por porcentaje o monto fijo.
- **Venta Directa**: Módulo de venta de productos al mostrador.
- **Facturación**: Generación de facturas vinculadas a las órdenes de servicio completadas.

## 🛠️ Tecnologías Utilizadas

- **Framework**: [Laravel 11](https://laravel.com)
- **Lenguaje**: PHP 8.2+
- **Base de Datos**: MySQL / MariaDB
- **Frontend**: Blade Components & Tailwind CSS (en desarrollo)

## 📁 Documentación

La documentación técnica detallada se encuentra en la carpeta `/docs`:

- [Explicación de Modelos y Relaciones](docs/explicacion_modelos.md): Detalles sobre la arquitectura de la base de datos y lógica de Eloquent.

## ⚙️ Instalación

1. Clonar el repositorio.
2. Ejecutar `composer install`.
3. Copiar el archivo `.env.example` a `.env` y configurar las credenciales de la base de datos.
4. Ejecutar `php artisan key:generate`.
5. Ejecutar las migraciones: `php artisan migrate`.
6. (Opcional) Ejecutar seeders si están disponibles: `php artisan db:seed`.
7. Iniciar el servidor local: `php artisan serve`.

## 📄 Licencia

Este proyecto está bajo la licencia MIT.
