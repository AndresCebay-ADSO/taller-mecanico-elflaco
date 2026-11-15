# 🔍 AUDITORÍA DE SOFTWARE — Taller Mecánico El Flaco

**Fecha:** 2026-05-03  
**Versión:** Laravel 12.x (laravel/laravel)  
**Base de Datos:** MySQL 8.x  
**Archivos auditados:** 70+ archivos entre controladores, modelos, servicios, migraciones, seeders, tests y configuración  

---

## Resumen Ejecutivo

**Nota de salud general: 7.2 / 10**

| Severidad | Cantidad |
|-----------|----------|
| 🔴 Crítico | 3 |
| 🟠 Alto | 5 |
| 🟡 Medio | 7 |
| 🟢 Bajo | 4 |
| ℹ️ Informativo | 4 |
| **Total** | **23** |

La aplicación tiene una arquitectura sólida con buen uso de transacciones, FIFO batch inventory, lockForUpdate, y validación de transiciones de estado a nivel de modelo. Sin embargo, se identificaron **problemas críticos** en la consistencia de tipos ENUM en migraciones (los tipos `transfer_in`/`transfer_out` no están registrados en el ENUM original), la lógica duplicada de stock en el modelo Product, y una **condición de carrera** en la cancelación de ventas. En seguridad, falta autorización basada en roles y la sucursal actual se almacena en sesión sin validación de pertenencia. La cobertura de tests es decente (10 archivos feature) pero tiene brechas significativas en ventas, transferencias HTTP, y autorización.

---

## Hallazgos por Severidad

---

### 🔴 C01: ENUM de `inventory_movements.movement_type` no incluye tipos de transferencia

- **Archivo:** [create_inventory_movements_table.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/database/migrations/2026_02_05_003839_create_inventory_movements_table.php) (línea 17)
- **Tipo:** Bug / Integridad de Datos
- **Problema:** La migración original define `movement_type` como `enum('purchase', 'sale', 'job_usage', 'adjustment', 'reversal')`. Sin embargo, `BranchTransferController` inserta movimientos con tipos `transfer_out` y `transfer_in` (líneas 129 y 164). En MySQL con strict mode habilitado (como está configurado), esto lanzará un error SQL al intentar completar una transferencia.
- **Impacto:** **Las transferencias entre sucursales fallarán con un error SQL en producción.** Es un bloqueo total de la funcionalidad de multi-sucursal.
- **Código problemático:**
```php
// Migration (ENUM restrictivo):
$table->enum('movement_type', ['purchase', 'sale', 'job_usage', 'adjustment', 'reversal']);

// BranchTransferController::completeTransfer() usa tipos no registrados:
InventoryMovement::create(['movement_type' => 'transfer_out', ...]);
InventoryMovement::create(['movement_type' => 'transfer_in', ...]);
```
- **Solución recomendada:**
```php
// Nueva migración: add_transfer_types_to_inventory_movements.php
Schema::table('inventory_movements', function (Blueprint $table) {
    $table->string('movement_type', 30)->change(); // Cambiar enum a string
});

// O agregar los valores al ENUM:
DB::statement("ALTER TABLE inventory_movements MODIFY movement_type 
    ENUM('purchase','sale','job_usage','adjustment','reversal','transfer_in','transfer_out')");
```

---

### 🔴 C02: Condición de carrera en `reverseStockFromSale()` — sin `lockForUpdate()`

- **Archivo:** [InventoryService.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Services/InventoryService.php) (líneas 121-172)
- **Tipo:** Bug / Condición de Carrera
- **Problema:** `reverseStockFromSale()` no usa transacciones internas ni `lockForUpdate()` en los productos ni lotes. Si dos solicitudes cancelan ventas concurrentemente que afectan el mismo producto, se puede generar un stock inconsistente. Además, `SaleController::cancel()` SÍ envuelve en `DB::transaction()` pero el método `reverseStockFromSale()` no bloquea filas individualmente.
- **Impacto:** Corrupción de datos de stock en escenarios de concurrencia (doble cancelación simultánea).
- **Código problemático:**
```php
public function reverseStockFromSale(int $saleId): void
{
    $movements = InventoryMovement::where('reference', "Venta #{$saleId}")...->get();
    
    foreach ($movements as $movement) {
        // ⚠️ No hay lockForUpdate() en product ni batch
        $product = Product::withTrashed()->find($movement->product_id);
        $batch = Batch::find($movement->batch_id);
        $batch->increment('remaining_stock', $quantityToRestore);
        $product->increment('stock', $quantityToRestore);
    }
}
```
- **Solución recomendada:**
```php
public function reverseStockFromSale(int $saleId): void
{
    if (DB::transactionLevel() === 0) {
        throw new RuntimeException('reverseStockFromSale debe ejecutarse dentro de una transaccion.');
    }

    $movements = InventoryMovement::where('reference', "Venta #{$saleId}")
        ->where('movement_type', 'sale')
        ->orderBy('id', 'desc')
        ->get();

    foreach ($movements as $movement) {
        $product = Product::withTrashed()
            ->whereKey($movement->product_id)
            ->lockForUpdate()
            ->first();

        if (!$product) { /* log y skip */ continue; }

        if ($product->trashed()) { $product->restore(); }

        $quantityToRestore = abs($movement->quantity);

        if ($movement->batch_id) {
            Batch::whereKey($movement->batch_id)
                ->lockForUpdate()
                ->first()
                ?->increment('remaining_stock', $quantityToRestore);
        }

        $product->increment('stock', $quantityToRestore);

        InventoryMovement::create([...]);
    }
}
```

---

### 🔴 C03: Stock duplicado en `deductStock()` — doble decremento de `product.stock`

- **Archivo:** [InventoryService.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Services/InventoryService.php) (líneas 49-116)
- **Tipo:** Bug / Lógica de Negocio
- **Problema:** En `SaleController::store()`, el producto se bloquea con `lockForUpdate()` y luego se llama a `$inventoryService->deductStock()`. Dentro de `deductStock()`, se vuelve a hacer `Product::whereKey($productId)->lockForUpdate()->firstOrFail()` y al final `$product->decrement('stock', $quantity)`. Sin embargo, `SaleController` ya verificó el stock contra la instancia bloqueada. **El problema real es que `deductStock()` hace su propio lock independiente.** Si el SaleController ya decrementa el stock (no lo hace directamente, delega completo), esto es correcto. Pero hay una inconsistencia sutil: `SaleController` hace lock en línea 161-164 y luego `deductStock` hace OTRO lock (línea 59-61). Ambas son la misma transacción, pero la primera lectura del SaleController no se usa para el decremento, creando un flujo confuso.
- **Impacto:** Aunque no genera un bug funcional (están en la misma transacción), el doble lock y doble lectura es ineficiente y confuso para mantenimiento. El stock del `Product` model se decrementa correctamente una sola vez en `deductStock()`.
- **Severidad ajustada:** Reclasificado a 🟠 Alto (no es bug activo pero es un riesgo de refactor futuro).

> [!NOTE]
> Tras re-análisis, este hallazgo se reclasifica a Alto en lugar de Crítico. El doble lock en la misma transacción no causa corrupción, pero sí confusión y riesgo de bugs en futuros cambios.

---

### 🟠 A01: Falta autorización (RBAC) — todos los usuarios autenticados pueden hacer todo

- **Archivo:** [web.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/routes/web.php) (línea 26)
- **Tipo:** Seguridad
- **Problema:** Todas las rutas solo requieren `auth` middleware. No existe middleware de roles, policies, ni gates. La tabla `users` tiene columna `is_admin` pero no se utiliza en ningún lugar del código. Cualquier usuario autenticado puede eliminar productos, cancelar ventas, modificar configuraciones, crear sucursales, y ejecutar transferencias.
- **Impacto:** Cualquier empleado con acceso al sistema tiene privilegios de administrador.
- **Código problemático:**
```php
// web.php — sin middleware de roles:
Route::middleware('auth')->group(function () {
    Route::resource('suppliers', SupplierController::class);
    Route::resource('sales', SaleController::class);
    Route::put('/settings', ...); // ¡Cualquiera puede cambiar configuración!
});
```
- **Solución recomendada:**
```php
// Crear middleware EnsureAdmin
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('branches', BranchController::class);
    Route::put('/settings', ...);
    Route::resource('job-types', JobTypeController::class);
});

// O usar Gates/Policies para control granular
```

---

### 🟠 A02: Sucursal actual almacenada en sesión sin validación de pertenencia

- **Archivo:** [BranchService.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Services/BranchService.php) (líneas 10-26)
- **Tipo:** Seguridad
- **Problema:** La sucursal se guarda en la sesión sin verificar que el usuario tenga acceso a esa sucursal. No hay relación `User -> Branch`. Un usuario podría cambiar a cualquier sucursal activa y operar en ella, incluyendo ver ventas, modificar inventario, y transferir stock.
- **Impacto:** Fuga de datos entre sucursales; un empleado de la Sede A puede operar como si estuviera en la Sede B.
- **Código problemático:**
```php
public function setCurrentBranch(int $branchId): void
{
    $branch = Branch::findOrFail($branchId); // solo verifica que exista
    Session::put('current_branch_id', $branch->id); // sin verificar acceso del usuario
}
```
- **Solución recomendada:**
```php
public function setCurrentBranch(int $branchId): void
{
    $user = auth()->user();
    $branch = Branch::where('id', $branchId)
        ->where('is_active', true)
        ->firstOrFail();
    
    // Verificar que el usuario tenga acceso a esta sucursal
    if (!$user->canAccessBranch($branch)) {
        abort(403, 'No tienes acceso a esta sucursal.');
    }
    
    Session::put('current_branch_id', $branch->id);
}
```

---

### 🟠 A03: `SaleController::update()` permite editar el monto total directamente

- **Archivo:** [SaleController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/SaleController.php) (líneas 243-252)
- **Tipo:** Seguridad / Lógica de Negocio
- **Problema:** El método `update()` permite que cualquier usuario autenticado modifique el `total_amount` de una venta completada a cualquier valor arbitrario. Esto rompe la trazabilidad financiera y la consistencia con los `sale_products`.
- **Impacto:** Un usuario puede reducir o aumentar el total de una venta sin afectar los productos vendidos, creando inconsistencia contable.
- **Código problemático:**
```php
public function update(Request $request, Sale $sale)
{
    $validated = $request->validate([
        'total_amount' => 'required|numeric|min:0', // ¡Cualquier monto!
    ]);
    $sale->update($validated);
}
```
- **Solución recomendada:**
```php
public function update(Request $request, Sale $sale)
{
    // Las ventas completadas no deben editarse. Solo permitir notas o recalcular.
    if ($sale->status === 'anulada') {
        return back()->with('error', 'No se puede editar una venta anulada.');
    }
    abort(403, 'Las ventas no pueden editarse. Use Anular para corregir errores.');
}
```

---

### 🟠 A04: Métodos legacy de stock en `Product` model — código muerto con riesgo de uso accidental

- **Archivo:** [Product.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Models/Product.php) (líneas 46-94)
- **Tipo:** Deuda Técnica / Riesgo
- **Problema:** `Product` tiene métodos `incrementStock()`, `reverseStock()`, y `decrementStock()` que operan directamente sobre el stock **sin FIFO, sin lotes, y sin lockForUpdate()**. Estos métodos fueron reemplazados por `InventoryService` pero siguen disponibles. Si algún desarrollador los usa por error, se rompe la trazabilidad de lotes.
- **Impacto:** Riesgo alto de regresión si alguien utiliza estos métodos pensando que son la API correcta.
- **Solución recomendada:**
```php
/**
 * @deprecated Usar InventoryService::deductStock() para operaciones con trazabilidad FIFO.
 * @throws \BadMethodCallException
 */
public function decrementStock($quantity, $reason = 'sale', $reference = null)
{
    throw new \BadMethodCallException(
        'decrementStock() está deprecado. Usar InventoryService::deductStock().'
    );
}
```

---

### 🟠 A05: `WorkshopJob::destroy()` elimina trabajo sin reversar stock de productos

- **Archivo:** [WorkshopJobController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/WorkshopJobController.php) (líneas 149-155)
- **Tipo:** Bug / Lógica de Negocio
- **Problema:** Cuando se elimina un `WorkshopJob`, la migración tiene `cascadeOnDelete()` en `job_products`, así que los registros de productos del trabajo se eliminan. Sin embargo, el stock consumido NO se restaura y los `InventoryMovement` correspondientes quedan huérfanos.
- **Impacto:** Pérdida permanente de stock y movimientos de inventario desconectados. Si un mecánico usó 3 filtros de aceite y luego se elimina el trabajo, esos 3 filtros desaparecen del sistema.
- **Código problemático:**
```php
public function destroy(WorkshopJob $job)
{
    $serviceOrder = $job->serviceOrder;
    $job->delete(); // Los job_products se eliminan en cascada, pero el stock NO se restaura
    return redirect()->route('service-orders.show', $serviceOrder);
}
```
- **Solución recomendada:**
```php
public function destroy(WorkshopJob $job)
{
    DB::transaction(function () use ($job) {
        // Restaurar stock de productos usados en este trabajo
        foreach ($job->jobProducts as $jp) {
            $product = Product::withTrashed()->lockForUpdate()->find($jp->product_id);
            if ($product) {
                $product->increment('stock', $jp->quantity);
                // Restaurar lote si es posible
            }
            InventoryMovement::create([
                'product_id' => $jp->product_id,
                'movement_type' => 'reversal',
                'quantity' => $jp->quantity,
                'unit_price' => $jp->unit_price,
                'reference' => "Eliminacion Trabajo #{$job->id}",
                'movement_date' => now(),
            ]);
        }
        $job->delete();
    });
    
    return redirect()->route('service-orders.show', $job->serviceOrder);
}
```

---

### 🟡 M01: `ReportController` usa cálculo de ganancia hardcodeado (25%)

- **Archivo:** [ReportController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/ReportController.php) (línea 17)
- **Tipo:** Lógica de Negocio
- **Problema:** `$workshopProfit = $totalIncome * 0.25` usa un porcentaje fijo del 25% en lugar de calcular la ganancia real basada en costos vs precios de venta.
- **Impacto:** Los reportes muestran ganancias incorrectas que no reflejan los márgenes reales del taller.
- **Solución recomendada:**
```php
$workshopProfit = $totalIncome - Product::sum(DB::raw('purchase_price * stock'));
// O mejor: calcular desde InventoryMovement la diferencia entre precios de compra y venta
```

---

### 🟡 M02: `ReportController` no filtra por sucursal

- **Archivo:** [ReportController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/ReportController.php) (líneas 16-23)
- **Tipo:** Lógica de Negocio
- **Problema:** Todas las consultas del reporte son globales (`Sale::where(...)`, `Product::count()`) sin filtrar por `branch_id`. En un sistema multi-sucursal, un reporte muestra datos mezclados de todas las sedes.
- **Impacto:** Reportes inexactos para operadores de una sucursal específica.
- **Solución recomendada:** Inyectar `BranchService` y aplicar `->forBranch($branchId)` como en los demás controladores.

---

### 🟡 M03: `DashboardController` tiene potencial N+1 en `lowStockProducts`

- **Archivo:** [DashboardController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/DashboardController.php) (líneas 66-67)
- **Tipo:** Rendimiento
- **Problema:** Se ejecutan dos consultas casi idénticas: `lowStockCount` (COUNT) y `lowStockProducts` (GET). Se puede combinar en una sola.
- **Impacto:** Consulta duplicada innecesaria en cada carga del dashboard.
- **Solución recomendada:**
```php
$lowStockProducts = Product::forBranch($branchId)->whereRaw('stock <= min_stock')->get();
$lowStockCount = $lowStockProducts->count();
```

---

### 🟡 M04: Batch tiene campos duplicados: `selling_price` y `sale_price`

- **Archivo:** [Batch.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Models/Batch.php) + migraciones
- **Tipo:** Deuda Técnica / Diseño de DB
- **Problema:** La tabla `batches` tiene dos columnas para el precio de venta: `selling_price` (original) y `sale_price` (agregado en migración posterior). El código los sincroniza manualmente en `BatchController` (`$batch->selling_price = $validated['sale_price']`), pero esta duplicación genera confusión y riesgo de desincronización.
- **Impacto:** Confusión para desarrolladores y riesgo de precios inconsistentes entre `selling_price` y `sale_price`.
- **Solución recomendada:** Crear migración para eliminar `selling_price` y usar solo `sale_price`. Actualizar `InventoryService::deductStock()` línea 84.

---

### 🟡 M05: `ServiceOrder::destroy()` elimina órdenes sin verificar trabajos asociados ni facturas

- **Archivo:** [ServiceOrderController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/ServiceOrderController.php) (líneas 161-168)
- **Tipo:** Lógica de Negocio / Integridad de datos
- **Problema:** Se puede eliminar una orden de servicio que tiene trabajos con productos consumidos y/o facturas generadas. La FK `workshop_jobs.service_order_id` tiene `nullOnDelete()`, así que los trabajos quedan huérfanos. Las facturas tienen `restrictOnDelete()` lo cual sí previene eliminación si hay factura, pero si solo hay trabajos sin factura, se pierden datos.
- **Impacto:** Pérdida de trazabilidad de trabajos y productos consumidos.
- **Solución recomendada:**
```php
public function destroy(ServiceOrder $serviceOrder)
{
    if ($serviceOrder->workshopJobs()->exists()) {
        return back()->with('error', 'No se puede eliminar una orden con trabajos asociados. Cancélela primero.');
    }
    $serviceOrder->delete();
    return redirect()->route('service-orders.index')->with('success', 'Orden eliminada.');
}
```

---

### 🟡 M06: `BranchTransferController::store()` verifica stock del producto global, no de la sucursal origen

- **Archivo:** [BranchTransferController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/BranchTransferController.php) (líneas 67-73)
- **Tipo:** Bug / Lógica de Negocio
- **Problema:** Al crear una transferencia, se busca `Product::findOrFail($validated['product_id'])` sin filtrar por `branch_id`. El stock verificado podría ser de un producto en otra sucursal.
- **Impacto:** Se pueden crear transferencias con stock de la sucursal incorrecta.
- **Código problemático:**
```php
$product = Product::findOrFail($validated['product_id']); // ⚠️ Sin filtro branch
if ($product->stock < $validated['quantity']) { ... }
```
- **Solución recomendada:**
```php
$product = Product::where('id', $validated['product_id'])
    ->where('branch_id', $validated['from_branch_id'])
    ->firstOrFail();
```

---

### 🟡 M07: `BatchController::update()` tiene error lógico en la segunda condición de cantidad

- **Archivo:** [BatchController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/BatchController.php) (línea 58)
- **Tipo:** Bug
- **Problema:** La línea 58 verifica `$batch->remaining_stock == $batch->quantity` **después** de que las líneas 36-37 ya modificaron ambos valores. Esto significa que la condición siempre será `true` en este punto, haciendo que el movement siempre se actualice con la nueva cantidad (comportamiento probablemente correcto por accidente).
- **Impacto:** Bajo, pero el código es confuso y frágil.
- **Solución recomendada:** Guardar los valores originales antes de modificarlos.

---

### 🟢 B01: `ProfileController` importado en `web.php` pero no usado

- **Archivo:** [web.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/routes/web.php) (línea 5)
- **Tipo:** Deuda Técnica
- **Problema:** `use App\Http\Controllers\ProfileController;` está importado pero nunca se usa. El controlador probablemente no existe (no se encontró en la carpeta de controladores).
- **Impacto:** Import muerto; confusión menor.
- **Solución recomendada:** Eliminar la línea 5.

---

### 🟢 B02: `ProductPurchase` model y tabla — funcionalidad legacy sin uso

- **Archivo:** [ProductPurchase.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Models/ProductPurchase.php) + migración
- **Tipo:** Deuda Técnica
- **Problema:** El modelo `ProductPurchase` y su tabla `product_purchases` no se usan en ningún controlador ni servicio. Las compras se registran mediante `InventoryService::registerPurchaseBatch()` que usa `Batch` + `InventoryMovement`. Solo `Supplier::productPurchases()` hace referencia al modelo.
- **Impacto:** Tabla y modelo muertos que confunden la arquitectura.
- **Solución recomendada:** Eliminar la tabla, modelo, y la relación en `Supplier`.

---

### 🟢 B03: `Setting` model referencia `SettingFactory` inexistente

- **Archivo:** [Setting.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Models/Setting.php) (línea 10)
- **Tipo:** Deuda Técnica
- **Problema:** `@use HasFactory<\Database\Factories\SettingFactory>` referencia una factory que no existe en `database/factories/`.
- **Impacto:** Error si se intenta usar `Setting::factory()` en tests.
- **Solución recomendada:** Crear la factory o eliminar el trait `HasFactory`.

---

### 🟢 B04: `orderByRaw('deleted_at IS NOT NULL ASC')` no es portable entre DBs

- **Archivo:** [ProductController.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/app/Http/Controllers/ProductController.php) (línea 48)
- **Tipo:** Deuda Técnica
- **Problema:** SQL crudo `deleted_at IS NOT NULL ASC` es específico de MySQL. Si los tests usan SQLite (como sugiere la config), esto podría fallar.
- **Impacto:** Bajo; podría causar issues en tests con SQLite.
- **Solución recomendada:**
```php
->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
```

---

### ℹ️ I01: `AdminSeeder` usa `env()` directamente en lugar de `config()`

- **Archivo:** [AdminSeeder.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/database/seeders/AdminSeeder.php) (líneas 17-18)
- **Tipo:** Buenas Prácticas
- **Problema:** `env()` no funciona correctamente cuando el config está cacheado (`php artisan config:cache`). En producción, `env('ADMIN_EMAIL')` devolvería `null`.
- **Impacto:** El seeder fallaría silenciosamente en producción con config cacheada.
- **Solución recomendada:** Mover las variables a `config/auth.php` y usar `config('auth.admin.email')`.

---

### ℹ️ I02: Falta `BranchSeeder` en `DatabaseSeeder`

- **Archivo:** [DatabaseSeeder.php](file:///c:/eliza-proyecto/taller-mecanico-elflaco/database/seeders/DatabaseSeeder.php) (línea 17)
- **Tipo:** Deuda Técnica
- **Problema:** `BranchSeeder` existe pero no se invoca en `DatabaseSeeder`. Un despliegue nuevo no tendrá sucursales, lo que puede causar que toda la aplicación opere sin sucursal (todos los `branch_id` serán `null`).
- **Impacto:** Configuración incompleta en despliegues nuevos.
- **Solución recomendada:** Añadir `BranchSeeder::class` al array de seeders.

---

### ℹ️ I03: Faltan indexes de rendimiento en tablas de alto tráfico

- **Archivo:** Varias migraciones
- **Tipo:** Rendimiento / Base de Datos
- **Problema:** Tablas que se consultan frecuentemente carecen de índices optimizados:
  - `batches`: Falta índice compuesto `(product_id, remaining_stock, purchased_at)` para consultas FIFO.
  - `inventory_movements`: Falta índice en `(product_id, movement_type)`.
  - `workshop_jobs`: Falta índice en `service_order_id` (no está definido como FK con índice).
  - `sale_products`: Falta índice en `sale_id`.
- **Impacto:** Degradación de rendimiento a medida que crece la base de datos.
- **Solución recomendada:** Crear migración con índices compuestos.

---

### ℹ️ I04: Faltan Form Request classes para la mayoría de controladores

- **Archivo:** Todos los controladores excepto `ProductController` (store)
- **Tipo:** Deuda Técnica
- **Problema:** Solo `StoreProductRequest` es un Form Request dedicado. Todos los demás controladores validan inline con `$request->validate()`. Esto mezcla validación con lógica de controlador y dificulta la reutilización y el testing.
- **Impacto:** Código menos limpio y más difícil de mantener.
- **Solución recomendada:** Crear Form Requests al menos para: `StoreSaleRequest`, `StoreServiceOrderRequest`, `StoreWorkshopJobRequest`, `StoreBranchTransferRequest`.

---

## Cobertura de Tests

| Funcionalidad | Cubierta | Archivo de Test | Notas |
|---|---|---|---|
| FIFO deducción de stock | ✅ Sí | `InventoryServiceTest` | Test de 2 lotes, bien hecho |
| Reversa de venta + restaurar soft-delete | ✅ Sí | `InventoryServiceTest` | Cubre caso de producto eliminado |
| Ajuste de inventario positivo | ✅ Sí | `InventoryAdjustmentTest` | HTTP test completo |
| Ajuste de inventario negativo (rechazo) | ✅ Sí | `InventoryAdjustmentTest` | Verifica error de validación |
| Factura duplicada (lock + DB unique) | ✅ Sí | `InvoiceGenerationTest` | Cubre idempotencia |
| Desactivación de mecánico con historial | ✅ Sí | `MechanicLifecycleTest` | Test HTTP |
| Transiciones de estado (modelo) | ✅ Sí | `ModelTransitionEnforcementTest` | ServiceOrder + WorkshopJob |
| Transiciones de estado (HTTP) | ✅ Sí | `ServiceOrderTransitionsTest` | Rechaza transiciones inválidas |
| Protección de stock en edit de producto | ✅ Sí | `ProductUpdateProtectionTest` | Verifica que stock no cambie |
| Seeders — admin y seguridad | ✅ Sí | `SeedersSecurityTest` | Verifica env variables |
| Multi-sucursal — filtros | ✅ Sí | `MultiBranchTest` | Products, Sales, Orders, Mechanics |
| Multi-sucursal — transferencias | ✅ Parcial | `MultiBranchTest` | Solo modelo, no HTTP completo |
| **Ventas — crear venta HTTP** | ❌ No | — | Sin test de flujo de venta |
| **Ventas — cancelar venta HTTP** | ❌ No | — | Flujo crítico sin test |
| **Transferencias — completar HTTP** | ❌ No | — | Sin test de `completeTransfer()` |
| **Concurrencia — ventas simultáneas** | ❌ No | — | Sin test de race condition |
| **Autorización / Roles** | ❌ No | — | No existe RBAC, no hay test |
| **Proveedor — CRUD HTTP** | ❌ No | — | Solo hay test manual |
| **Reportes** | ❌ No | — | Sin test del dashboard/reportes |
| **Configuración (Settings)** | ❌ No | — | Sin test de update settings |

---

## Deuda Técnica Acumulada

| # | Item | Esfuerzo | Ref |
|---|---|---|---|
| 1 | Métodos legacy de stock en `Product` model (`incrementStock`, `decrementStock`, `reverseStock`) | Bajo | A04 |
| 2 | `ProductPurchase` modelo y tabla sin uso | Bajo | B02 |
| 3 | `ProfileController` import muerto en `web.php` | Bajo | B01 |
| 4 | Duplicación `selling_price` / `sale_price` en `batches` | Medio | M04 |
| 5 | Falta Form Request classes (6+ controladores) | Medio | I04 |
| 6 | `Setting` referencia factory inexistente | Bajo | B03 |
| 7 | `ReportController` con lógica hardcodeada y sin filtro de sucursal | Medio | M01, M02 |
| 8 | `BranchSeeder` no invocado en `DatabaseSeeder` | Bajo | I02 |
| 9 | Falta sistema RBAC completo | Alto | A01 |
| 10 | Falta tests para flujos críticos (ventas, transferencias, cancelaciones) | Alto | Cobertura |
