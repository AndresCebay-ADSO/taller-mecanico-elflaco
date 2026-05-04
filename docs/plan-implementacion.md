# Plan de Implementación — Taller Mecánico El Flaco

**Fecha:** 2026-05-03  
**Versión auditada:** Laravel 12.x (`laravel/laravel`)  
**Base de datos:** MySQL 8.x  
**Auditor:** Auditoría técnica completa del codebase  

---

## Fase 1 — Crítico (Esta semana)

| # | Issue | Archivo | Esfuerzo | Rama Git |
|---|-------|---------|----------|----------|
| C01 | ENUM de `inventory_movements.movement_type` no incluye `transfer_in`/`transfer_out` | `database/migrations/` | Bajo | `fix/C01-enum-transfer-types` |
| C02 | `reverseStockFromSale()` sin `lockForUpdate()` — condición de carrera | `app/Services/InventoryService.php` | Medio | `fix/C02-reverse-stock-locking` |
| A05 | `WorkshopJob::destroy()` no restaura stock de productos consumidos | `app/Http/Controllers/WorkshopJobController.php` | Medio | `fix/A05-job-destroy-stock-reversal` |

### Detalle C01:
1. Crear nueva migración: `php artisan make:migration add_transfer_types_to_inventory_movements`
2. Cambiar columna `movement_type` de ENUM a `string(30)` o expandir el ENUM
3. Ejecutar `php artisan migrate` y verificar en staging
4. Test: crear transferencia entre sucursales y completarla

### Detalle C02:
1. Agregar verificación de transacción activa al inicio de `reverseStockFromSale()`
2. Agregar `lockForUpdate()` a las consultas de `Product` y `Batch` dentro del método
3. Test: crear test de cancelación de venta con verificación de stock

### Detalle A05:
1. Modificar `WorkshopJobController::destroy()` para restaurar stock por producto
2. Crear movimientos de tipo `reversal` por cada `job_product`
3. Envolver en `DB::transaction()`
4. Test: crear trabajo con productos, eliminarlo, verificar restauración de stock

---

## Fase 2 — Alto (Próximo sprint)

| # | Issue | Archivo | Esfuerzo | Rama Git |
|---|-------|---------|----------|----------|
| A01 | Implementar RBAC — middleware de roles y policies | `app/Http/Middleware/`, `routes/web.php` | Alto | `feat/A01-rbac-authorization` |
| A02 | Validar pertenencia de usuario a sucursal | `app/Services/BranchService.php`, `app/Models/User.php` | Medio | `feat/A02-branch-user-binding` |
| A03 | Deshabilitar edición directa de `total_amount` en ventas | `app/Http/Controllers/SaleController.php` | Bajo | `fix/A03-disable-sale-edit` |
| A04 | Marcar/eliminar métodos legacy de stock en `Product` | `app/Models/Product.php` | Bajo | `fix/A04-deprecate-legacy-stock` |
| M06 | Filtrar producto por sucursal origen en transferencias | `app/Http/Controllers/BranchTransferController.php` | Bajo | `fix/M06-transfer-branch-filter` |

### Detalle A01:
1. Crear middleware `EnsureAdmin` que verifique `$user->is_admin`
2. Registrar en `bootstrap/app.php` o `app/Http/Kernel.php`
3. Proteger rutas sensibles: settings, branches, job-types, branch-transfers
4. Considerar implementación de Spatie/laravel-permission para control granular
5. Test: verificar que usuario no-admin no puede acceder a rutas protegidas

### Detalle A02:
1. Crear tabla pivot `branch_user` (migración)
2. Agregar relación `User::branches()` y `Branch::users()`
3. Modificar `BranchService::setCurrentBranch()` para validar acceso
4. Modificar `BranchController::switch()` para verificar pertenencia
5. Test: verificar que usuario solo puede cambiar a sucursales autorizadas

---

## Fase 3 — Medio (Deuda técnica)

| # | Issue | Archivo | Esfuerzo | Rama Git |
|---|-------|---------|----------|----------|
| M01 | Cálculo real de ganancias en reportes | `app/Http/Controllers/ReportController.php` | Medio | `fix/M01-report-real-profit` |
| M02 | Filtrar reportes por sucursal activa | `app/Http/Controllers/ReportController.php` | Bajo | `fix/M02-report-branch-filter` |
| M03 | Optimizar consultas duplicadas en Dashboard | `app/Http/Controllers/DashboardController.php` | Bajo | `fix/M03-dashboard-query-optimization` |
| M04 | Unificar `selling_price`/`sale_price` en batches | `database/migrations/`, `app/Models/Batch.php` | Medio | `fix/M04-unify-batch-price` |
| M05 | Validar trabajos asociados antes de eliminar orden | `app/Http/Controllers/ServiceOrderController.php` | Bajo | `fix/M05-service-order-destroy-guard` |
| M07 | Fix lógica confusa en `BatchController::update()` | `app/Http/Controllers/BatchController.php` | Bajo | `fix/M07-batch-update-logic` |
| I03 | Agregar índices de rendimiento | `database/migrations/` | Bajo | `feat/I03-performance-indexes` |
| I04 | Crear Form Request classes para controladores | `app/Http/Requests/` | Medio | `feat/I04-form-requests` |

### Detalle M04 (Unificar precios):
1. Crear migración para copiar datos de `selling_price` a `sale_price` donde `sale_price` es null
2. Eliminar columna `selling_price` de `batches`
3. Actualizar `InventoryService::deductStock()` línea 84 para usar solo `sale_price`
4. Actualizar `BatchController::update()` para no sincronizar ambos campos
5. Actualizar `Batch` model: remover `selling_price` de fillable y casts

### Detalle I03 (Índices):
```php
// Nueva migración: add_performance_indexes
Schema::table('batches', function (Blueprint $table) {
    $table->index(['product_id', 'remaining_stock', 'purchased_at']);
});
Schema::table('inventory_movements', function (Blueprint $table) {
    $table->index(['product_id', 'movement_type']);
});
```

---

## Fase 4 — Bajo / Informativo (Backlog)

| # | Issue | Archivo | Esfuerzo | Rama Git |
|---|-------|---------|----------|----------|
| B01 | Eliminar import de `ProfileController` en web.php | `routes/web.php` | Bajo | `fix/B01-dead-import` |
| B02 | Eliminar `ProductPurchase` modelo y tabla legacy | `app/Models/`, `database/migrations/` | Bajo | `fix/B02-remove-product-purchase` |
| B03 | Crear `SettingFactory` o remover `HasFactory` trait | `app/Models/Setting.php` | Bajo | `fix/B03-setting-factory` |
| B04 | Hacer portable el `orderByRaw` en ProductController | `app/Http/Controllers/ProductController.php` | Bajo | `fix/B04-portable-orderby` |
| I01 | Usar `config()` en lugar de `env()` en seeders | `database/seeders/AdminSeeder.php` | Bajo | `fix/I01-config-vs-env` |
| I02 | Agregar `BranchSeeder` al `DatabaseSeeder` | `database/seeders/DatabaseSeeder.php` | Bajo | `fix/I02-branch-seeder` |

### Tests pendientes de crear:

| # | Test | Prioridad | Rama Git |
|---|------|-----------|----------|
| T01 | Crear venta completa (HTTP) con FIFO | Alta | `test/T01-sale-creation-flow` |
| T02 | Cancelar venta (HTTP) y verificar stock | Alta | `test/T02-sale-cancellation` |
| T03 | Completar transferencia entre sucursales (HTTP) | Alta | `test/T03-branch-transfer-complete` |
| T04 | Concurrencia en ventas simultáneas | Media | `test/T04-concurrent-sales` |
| T05 | Autorización de rutas administrativas | Media | `test/T05-rbac-authorization` |
| T06 | CRUD de proveedores (HTTP) | Baja | `test/T06-supplier-crud` |
| T07 | Reportes y dashboard (HTTP) | Baja | `test/T07-reports-dashboard` |
| T08 | Actualización de configuración | Baja | `test/T08-settings-update` |

---

## Reglas de implementación

- Cada issue requiere su propia rama: `fix/ID-descripcion` o `feat/ID-descripcion`
- No hacer rebase en ramas compartidas después de push — usar merge
- Todo fix debe incluir al menos un test
- PR requiere que pasen todos los tests existentes
- Antes de mergear, ejecutar: `php artisan test` completo
- Documentar cambios de migración en el commit message
- Para migraciones destructivas (eliminar columnas), crear migración de rollback

---

## Resumen de esfuerzo estimado

| Fase | Issues | Esfuerzo estimado |
|------|--------|-------------------|
| Fase 1 — Crítico | 3 | 2-3 días |
| Fase 2 — Alto | 5 | 1 semana |
| Fase 3 — Medio | 8 | 1-2 semanas |
| Fase 4 — Backlog | 6 + 8 tests | 3-5 días |
| **Total** | **22 issues + 8 tests** | **~4 semanas** |
