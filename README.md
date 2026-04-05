# MotoTaller "El Flaco" — Sistema de Gestión para Talleres de Motos 🏍️🔧

![Laravel 12](https://img.shields.io/badge/Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP 8.2+](https://img.shields.io/badge/PHP_8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS 4](https://img.shields.io/badge/Tailwind_CSS_4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)
![Vite 7](https://img.shields.io/badge/Vite_7-646CFF?style=for-the-badge&logo=vite&logoColor=white)

Sistema integral de gestión administrativa y operativa diseñado para talleres mecánicos de motocicletas. Controla inventario con trazabilidad por lotes (FIFO/PEPS), órdenes de servicio, ventas POS, facturación, mecánicos y reportes — todo desde una interfaz premium con modo oscuro nativo.

---

## 📐 Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND                                 │
│  Blade Components · Alpine.js · Tailwind CSS 4 · Vite 7        │
├─────────────────────────────────────────────────────────────────┤
│                       CONTROLLERS                               │
│  Dashboard │ Sales │ ServiceOrder │ WorkshopJob │ Inventory     │
│  Product │ Supplier │ Mechanic │ JobType │ Invoice │ Report     │
│  Batch │ Setting                                                │
├─────────────────────────────────────────────────────────────────┤
│                        SERVICES                                 │
│           InventoryService (FIFO · Lotes · Reversiones)         │
├─────────────────────────────────────────────────────────────────┤
│                     ELOQUENT MODELS (15)                        │
│  Product (SoftDeletes) · Batch · InventoryMovement · Supplier   │
│  Sale · SaleProduct · ServiceOrder · WorkshopJob · JobType      │
│  Mechanic · JobProduct · Invoice · ProductPurchase · Setting    │
│  User                                                           │
├─────────────────────────────────────────────────────────────────┤
│                    MySQL / MariaDB                              │
│                    20 migraciones                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## ✨ Características Principales

### 📦 Inventario Inteligente con FIFO/PEPS y Lotes

| Característica | Detalle |
|:---|:---|
| **Trazabilidad por Lotes** | Cada compra crea un `Batch` independiente con su precio de costo, precio de venta, proveedor y stock remanente. |
| **Descuento FIFO** | Al vender, `InventoryService::deductStock()` consume stock del lote más antiguo primero. Si un lote se agota, continúa con el siguiente. |
| **Precio por Lote** | El precio unitario de cada línea de venta refleja el precio real del lote consumido, no un promedio global. |
| **Reversión de Ventas** | `reverseStockFromSale()` restaura stock lote por lote en orden LIFO, garantizando consistencia contable. |
| **Corrección de Lotes** | `BatchController` permite editar proveedor, precios y cantidades de un lote sin romper la trazabilidad. |
| **Alertas de Stock Bajo** | Banner visual en el Dashboard cuando productos alcanzan su `min_stock`. Toast automático al crear una venta que deja stock crítico. |
| **SoftDeletes en Productos** | Los productos eliminados lógicamente se conservan para mantener el historial de ventas y movimientos. |

### 🛒 Ventas POS (Punto de Venta)

- **Multi-producto**: Agrega múltiples productos en una sola transacción.
- **Descuento FIFO automático**: El stock se descuenta por lotes y cada línea de venta hereda el precio del lote correspondiente.
- **Métodos de pago**: Efectivo, Nequi, Daviplata, Transferencia, Tarjeta, Otro.
- **Anulación segura**: Revierte el stock lote por lote — nunca se pierde la traza de inventario.
- **Protección de datos**: Las ventas no se pueden eliminar (`destroy` retorna 403), solo anular.
- **Filtros avanzados**: Búsqueda por ID, cliente o producto; filtro por método de pago, estado y rango de fechas.

### 🔧 Órdenes de Servicio y Trabajos de Taller

- **Órdenes de Servicio** (`ServiceOrder`): Registran cliente, teléfono, vehículo, descripción y estado (`pending` → `in_progress` → `completed` / `cancelled`).
- **Trabajos** (`WorkshopJob`): Se vinculan a una orden de servicio. Cada trabajo tiene un tipo, mecánico asignado, costo de mano de obra y productos utilizados.
- **Trabajo Individual**: Crea automáticamente una orden de servicio al registrar un trabajo suelto desde el módulo de trabajos.
- **Productos en Trabajos**: Se pueden añadir repuestos a cada trabajo, descontando stock con trazabilidad.
- **División de Ganancias**: El modelo `JobType` calcula automáticamente la distribución mecánico/taller según reglas configurables:
  - **Porcentaje**: `mechanic_percentage` + `workshop_percentage` sobre la mano de obra.
  - **Monto fijo**: `fixed_mechanic_amount` + `fixed_workshop_amount`.

### 📊 Dashboard en Tiempo Real

- Ganancias del día y del mes (ventas + órdenes completadas).
- Órdenes activas y pendientes de entrega.
- Últimas 5 ventas con detalle de productos y vendedor.
- Productos con stock bajo.
- Resumen de ventas del día por método de pago.
- Performance de mecánicos activos: trabajos completados y ganancias del mes.

### 🧾 Facturación

- Generación automática de facturas desde órdenes de servicio completadas.
- Consolidación: suma mano de obra + repuestos de todos los trabajos asociados.
- Al facturar, la orden se marca como `completed`.
- Número de factura único auto-generado (`FAC-XXXX`).

### 📈 Reportes

- Ingresos totales y utilidad estimada del taller.
- Trabajos del mes en curso.
- Valorización del inventario (a costo y a precio de venta).
- Conteo de productos con stock bajo.
- Listado de mecánicos activos.

### ⚙️ Configuración del Taller

Parámetros editables desde la interfaz:

| Clave | Descripción |
|:---|:---|
| `workshop_name` | Nombre del taller |
| `workshop_nit` | NIT / Identificación fiscal |
| `workshop_phone` | Teléfono de contacto |
| `workshop_email` | Correo electrónico |
| `workshop_address` | Dirección física |
| `tax_percentage` | Porcentaje de impuesto |
| `footer_text_invoice` | Texto pie de página en facturas |

### 🎨 Interfaz Premium

- **Paleta Navy/Slate** con diseño sofisticado y profesional.
- **Modo Oscuro Nativo** con toggle persistente.
- **Componentes Blade reutilizables**: `<x-button>`, `<x-card>`, `<x-badge>`, `<x-table>`, `<x-input>`, `<x-search-filter>`, `<x-page-header>`, `<x-sidebar>`, `<x-sidebar-link>`, `<x-theme-toggle>`.
- **Micro-animaciones** y transiciones fluidas.
- **Tipografía Figtree** para legibilidad óptima.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|:---|:---|
| **Backend** | Laravel 12 (PHP 8.2+) |
| **Frontend** | Blade Components + Alpine.js 3 |
| **Estilos** | Tailwind CSS 4 + `@tailwindcss/forms` |
| **Bundler** | Vite 7 (`laravel-vite-plugin` + `@tailwindcss/vite`) |
| **Base de Datos** | MySQL / MariaDB |
| **Autenticación** | Laravel Breeze (Tema Premium customizado) |
| **Testing** | Pest PHP 3 |
| **Linting** | Laravel Pint |
| **Dev Logs** | Laravel Pail |

---

## 🗂️ Estructura del Proyecto

```
app/
├── Exceptions/            # InsufficientStockException
├── Http/Controllers/      # 14 controladores + Auth/
├── Models/                # 15 modelos Eloquent
├── Providers/
├── Services/              # InventoryService (FIFO)
└── View/
database/
├── migrations/            # 20 migraciones
├── seeders/               # AdminSeeder, SettingSeeder, SupplierSeeder
resources/views/
├── components/            # 11 componentes Blade reutilizables
├── auth/                  # Login, registro (Breeze)
├── dashboard.blade.php    # Panel principal
├── inventory/             # Movimientos, compras, ajustes
├── invoices/              # Facturas
├── job-types/             # Tipos de trabajo
├── jobs/                  # Trabajos de taller
├── mechanics/             # Mecánicos
├── products/              # Catálogo de productos
├── reports/               # Reportes
├── sales/                 # Ventas POS
├── service-orders/        # Órdenes de servicio
├── settings/              # Configuración del taller
├── suppliers/             # Proveedores
└── welcome.blade.php      # Landing page
docs/
├── IEEE_830_SRS.md         # Especificación de Requerimientos
├── guia_tecnica.md         # Guía de Arquitectura
├── explicacion_modelos.md  # Modelo de Datos y Eloquent
└── requisitos.md           # Requisitos detallados
```

---

## ⚙️ Instalación

### Requisitos Previos

- PHP ≥ 8.2
- Composer
- Node.js ≥ 18 & NPM
- MySQL ≥ 8.0 o MariaDB ≥ 10.6

### Paso a Paso

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/AndresCebay-ADSO/taller-mecanico-elflaco.git
   cd taller-mecanico-elflaco
   ```

2. **Instalar dependencias:**
   ```bash
   composer install
   npm install
   ```

3. **Configurar entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edita `.env` con tus credenciales de base de datos:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=taller_el_flaco
   DB_USERNAME=root
   DB_PASSWORD=tu_password
   ```

4. **Crear la base de datos y ejecutar migraciones:**
   ```bash
   php artisan migrate
   ```

5. **Poblar datos iniciales (usuario admin + configuración + proveedores):**
   ```bash
   php artisan db:seed --class=AdminSeeder
   php artisan db:seed --class=SettingSeeder
   php artisan db:seed --class=SupplierSeeder
   ```

6. **Levantar el servidor de desarrollo:**
   ```bash
   # Opción A: Comando combinado (server + queue + vite)
   composer dev

   # Opción B: Dos terminales separadas
   php artisan serve       # Terminal 1
   npm run dev             # Terminal 2
   ```

7. **Acceder al sistema:**
   - URL: `http://localhost:8000`
   - Usuario: `admin@tallerflacos.com`
   - Contraseña: `password`

### Script de Setup Rápido

El proyecto incluye un script de Composer para setup automático:

```bash
composer setup
```

> Ejecuta: `composer install` → copia `.env` → genera key → migra la DB → `npm install` → `npm run build`.

---

## 📂 Documentación Técnica

Documentación detallada disponible en la carpeta `/docs`:

| Documento | Contenido |
|:---|:---|
| 📄 [IEEE_830_SRS.md](docs/IEEE_830_SRS.md) | Especificación de Requerimientos del Sistema |
| 📄 [guia_tecnica.md](docs/guia_tecnica.md) | Arquitectura: Resource Controllers, Route Model Binding, Validación |
| 📄 [explicacion_modelos.md](docs/explicacion_modelos.md) | Modelo de Datos y relaciones Eloquent |
| 📄 [requisitos.md](docs/requisitos.md) | Requisitos funcionales y no funcionales detallados |

---

## 🧪 Testing

```bash
# Ejecutar tests con Pest
php artisan test

# O usando el script de Composer
composer test
```

---

## 🗺️ Mapa de Rutas

Todas las rutas están protegidas por el middleware `auth`.

| Módulo | Ruta Base | Tipo |
|:---|:---|:---|
| Dashboard | `/dashboard` | Custom |
| Proveedores | `/suppliers` | Resource |
| Productos | `/products` | Resource |
| Mecánicos | `/mechanics` | Resource |
| Tipos de Trabajo | `/job-types` | Resource |
| Órdenes de Servicio | `/service-orders` | Resource |
| Trabajos | `/jobs` | Resource (parcial) |
| Ventas | `/sales` | Resource |
| Facturas | `/invoices` | Resource |
| Inventario | `/inventory` | Custom (index, purchase, adjustment) |
| Lotes | `/batches/{id}` | PATCH (corrección) |
| Reportes | `/reports` | Custom |
| Configuración | `/settings` | Custom (GET, PUT) |

---

## 👨‍💻 Contribución

Este proyecto es una herramienta de gestión privada. Para reportar errores o sugerir mejoras, por favor abre un **Issue** en el repositorio.

---

## 📄 Licencia

Distribuido bajo la Licencia **MIT**. Consulta el archivo `LICENSE` para más detalles.
