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
│  Batch │ Setting │ Branch │ BranchTransfer │ User │ Profile     │
├─────────────────────────────────────────────────────────────────┤
│                        SERVICES                                 │
│  InventoryService (FIFO · Lotes · Reversiones) · SaleService    │
│  WorkshopJobService · InvoiceService · BranchTransferService    │
│  ReportService · BranchService                                  │
├─────────────────────────────────────────────────────────────────┤
│                     ELOQUENT MODELS                             │
│  Product (SoftDeletes) · Batch · InventoryMovement · Supplier   │
│  Sale · SaleProduct · ServiceOrder · WorkshopJob · JobType      │
│  Mechanic · JobProduct · Invoice · Setting · Branch             │
│  BranchTransfer · User                                          │
├─────────────────────────────────────────────────────────────────┤
│                    MySQL / MariaDB                              │
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

- Ingresos del día y del mes (ventas de mostrador + órdenes entregadas).
- Órdenes activas y pendientes de entrega.
- Últimas 5 ventas con detalle de productos y vendedor.
- Productos con stock bajo.
- Resumen de ventas del día por método de pago.
- Comisiones de los mecánicos en el mes: trabajos completados y monto.

### 🧾 Facturación

- Generación de facturas desde órdenes con todos sus trabajos completados.
- Consolidación: suma mano de obra + repuestos de todos los trabajos asociados.
- Al facturar, la orden se marca como `completed`.
- Numeración consecutiva (`FAC-000001`) y factura imprimible con los datos del taller.
- **Anulación con registro**: la factura anulada se conserva (fecha y usuario) y la orden puede facturarse de nuevo.

### 📈 Reportes (por mes y sucursal)

- Ingresos del mes (órdenes + ventas) y ganancia estimada (parte del taller en la mano de obra + margen de repuestos al costo real del lote).
- Ingresos por día, comisiones por mecánico, repuestos más vendidos y ventas por método de pago.
- Valor del inventario actual al costo de cada lote y a precio de venta.
- Selector para consultar los últimos 12 meses.

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
├── Console/Commands/      # inventory:check (conciliación stock vs lotes)
├── Exceptions/            # InsufficientStockException, BusinessRuleException
├── Http/Controllers/      # Controladores + Auth/
├── Http/Middleware/       # SetCurrentBranch, EnsureAdminUser
├── Models/                # Modelos Eloquent
├── Providers/
└── Services/              # Inventario FIFO, ventas, trabajos, facturas, transferencias, reportes
database/
├── migrations/
├── seeders/               # DatabaseSeeder (config + sede + admin), DemoSeeder (datos de demo)
resources/views/
├── components/            # Componentes Blade reutilizables
├── auth/                  # Login
├── branches/, branch-transfers/, users/, profile/  # Administración
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
├── PRODUCCION_Y_DEMO.md    # Despliegue, respaldos y guion de demo
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

5. **Poblar datos iniciales (configuración, sede principal y administrador):**
   ```bash
   # En .env define ADMIN_EMAIL y ADMIN_PASSWORD; si ADMIN_PASSWORD queda vacío,
   # el seeder genera una contraseña temporal y la muestra en consola.
   php artisan db:seed
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
   - Usuario: el `ADMIN_EMAIL` de tu `.env` (por defecto `admin@tallerflacos.com`) con la contraseña del paso 5.

### Datos de demostración

Para presentar el sistema con 60 días de operación simulada (dos sedes, ventas, órdenes, facturas y transferencias), usa **una base de datos aparte** y sigue [`docs/PRODUCCION_Y_DEMO.md`](docs/PRODUCCION_Y_DEMO.md):

```bash
php artisan migrate:fresh --seed --seeder=DemoSeeder   # borra la base configurada en .env
```

La misma guía incluye el checklist de puesta en producción y copias de seguridad.

Para instalar en una máquina Windows con XAMPP (el caso del cliente) sigue [`docs/INSTALACION_LOCAL.md`](docs/INSTALACION_LOCAL.md). En cualquier máquina, `php artisan taller:verificar` revisa que el entorno esté bien configurado.

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
| 📄 [INSTALACION_LOCAL.md](docs/INSTALACION_LOCAL.md) | Instalar en Windows con XAMPP: php.ini, MySQL seguro, Apache, respaldos, actualización y problemas conocidos entre máquinas |
| 📄 [PRODUCCION_Y_DEMO.md](docs/PRODUCCION_Y_DEMO.md) | Puesta en producción, respaldos, guion de demo y preguntas para el cliente |
| 📄 [DESIGN.md](DESIGN.md) | Contrato de interfaz: colores, estados, tipografía, tablas, formularios y componentes |
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

Todas las rutas están protegidas por el middleware `auth` y cada registro se valida contra la sucursal activa. Las marcadas como **admin** requieren el rol administrador.

| Módulo | Ruta Base | Tipo |
|:---|:---|:---|
| Dashboard | `/dashboard` | Custom |
| Proveedores | `/suppliers` | Resource (eliminar/activar: admin) |
| Productos | `/products` | Resource (eliminar/restaurar: admin) |
| Mecánicos | `/mechanics` | Resource (eliminar: admin) |
| Tipos de Trabajo | `/job-types` | Resource — admin |
| Órdenes de Servicio | `/service-orders` | Resource |
| Trabajos | `/jobs`, `/service-orders/{id}/jobs` | Custom |
| Ventas | `/sales` | index, create, show (anular: admin) |
| Facturas | `/invoices` | index, show, generar (anular: admin) |
| Inventario | `/inventory` | index, compras (ajustes: admin) |
| Lotes | `/batches/{id}` | PATCH (corrección) — admin |
| Reportes | `/reports?month=AAAA-MM` | Custom |
| Usuarios, Sucursales, Transferencias, Configuración | `/users`, `/branches`, `/branch-transfers`, `/settings` | admin |
| Perfil | `/profile` | Cambio de contraseña |

---

## 👨‍💻 Contribución

Este proyecto es una herramienta de gestión privada. Para reportar errores o sugerir mejoras, por favor abre un **Issue** en el repositorio.

---

## 📄 Licencia

Distribuido bajo la Licencia **MIT**. Consulta el archivo `LICENSE` para más detalles.
