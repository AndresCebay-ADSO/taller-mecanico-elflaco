# Explicación de los Modelos Implementados

Se han implementado y configurado los modelos de Eloquent para el sistema del taller mecánico. A continuación, se detalla qué se hizo y por qué.

## Estructura General

Cada modelo se ha configurado con:
1. **`$fillable`**: Para permitir la asignación masiva de datos de forma segura, definiendo exactamente qué campos pueden ser llenados desde un formulario o API.
2. **Relaciones**: Se han definido métodos como `hasMany`, `belongsTo`, etc., para permitir que Laravel maneje automáticamente las conexiones entre tablas (ej: obtener todos los productos de un proveedor).
3. **`$casts`**: Para asegurar que campos como fechas (`date`, `datetime`) y booleanos sean tratados correctamente por PHP.

---

## Detalle de Modelos y Relaciones

### 1. Gestión de Inventario
- **`Supplier` (Proveedor)**:
    - *Relaciones*: Un proveedor tiene muchos productos y muchas compras de productos.
- **`Product` (Producto)**:
    - *Relaciones*: Pertenece a un proveedor. Tiene muchos registros en ventas, trabajos y movimientos de inventario.
- **`InventoryMovement` (Movimiento de Inventario)**:
    - *Por qué*: Registra entradas y salidas de stock (compras, ventas, ajustes). Está vinculado a un producto y opcionalmente a un proveedor.
- **`ProductPurchase` (Compra de Producto)**:
    - *Por qué*: Registra específicamente cuando se compra stock a un proveedor.

### 2. Personal y Servicios
- **`Mechanic` (Mecánico)**:
    - *Relaciones*: Un mecánico puede tener asignados muchos trabajos del taller (`workshopJobs`).
- **`JobType` (Tipo de Trabajo)**:
    - *Por qué*: Define si un trabajo es por porcentaje o monto fijo. Controla si se permiten productos o mano de obra personalizada.
- **`ServiceOrder` (Orden de Servicio)**:
    - *Por qué*: Es la "cabecera" que agrupa varios trabajos para un mismo vehículo y cliente. Tiene muchas órdenes de trabajo y facturas.

### 3. Operaciones del Taller
- **`WorkshopJob` (Trabajo del Taller)**:
    - *Nota*: Se cambió el nombre de `Job` a `WorkshopJob` para ser más descriptivo y evitar conflictos con el sistema interno de "Jobs" de Laravel.
    - *Relaciones*: Vincula una `ServiceOrder`, un `JobType` y un `Mechanic`. Tiene muchos productos usados (`jobProducts`).
- **`JobProduct` (Producto en Trabajo)**:
    - *Por qué*: Tabla pivote que registra qué productos y en qué cantidad se usaron en un trabajo específico.

### 4. Ventas y Facturación
- **`Sale` (Venta)**:
    - *Por qué*: Registra ventas directas de productos (mostrador). Tiene muchos `saleProducts`.
- **`SaleProduct` (Producto en Venta)**:
    - *Por qué*: Detalle de los productos incluidos en una venta.
- **`Invoice` (Factura)**:
    - *Por qué*: Documento final de cobro vinculado a una `ServiceOrder`.

---

## ¿Por qué este diseño?
- **Escalabilidad**: Al definir las relaciones ahora, será muy fácil hacer consultas como `$product->supplier->name` o `$mechanic->workshopJobs()->where('status', 'pending')->get()`.
- **Seguridad**: El uso de `$fillable` previene ataques de asignación masiva donde un usuario podría intentar cambiar campos sensibles (como IDs) enviando datos extra en una petición.
- **Claridad**: Renombrar `Job` a `WorkshopJob` previene errores comunes en Laravel donde la clase `Job` se confunde con tareas en segundo plano.
