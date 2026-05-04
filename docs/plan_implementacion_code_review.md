# Plan de Implementacion del Code Review

## Objetivo

Este plan convierte los hallazgos del code review en un roadmap tecnico ejecutable, con prioridad por riesgo, pasos concretos, archivos probables a modificar, validaciones y criterio de cierre por fase.

La prioridad general es:

1. Integridad de inventario y concurrencia
2. Flujo de ordenes y facturacion
3. Seguridad y autorizacion
4. Rendimiento y consistencia de reportes
5. Deuda tecnica y mantenibilidad

---

## Fase 1. Integridad de inventario y concurrencia

### Objetivo

Evitar perdida de trazabilidad, errores de stock y duplicidad de operaciones criticas.

### Paso 1. Unificar descuento de stock para trabajos con FIFO

**Problema**
- `WorkshopJobController` descuenta stock con `Product::decrementStock()` en lugar de `InventoryService::deductStock()`.

**Archivos a revisar**
- `app/Http/Controllers/WorkshopJobController.php`
- `app/Services/InventoryService.php`
- `app/Exceptions/InsufficientStockException.php`
- `app/Models/Product.php`
- `app/Models/Batch.php`
- `app/Models/InventoryMovement.php`

**Acciones**
1. Reemplazar el uso de `decrementStock()` en `storeStandalone()`.
2. Reemplazar el uso de `decrementStock()` en `store()`.
3. Manejar `InsufficientStockException` con error legible para el usuario.
4. Verificar que los movimientos queden asociados a lotes cuando aplique.
5. Verificar que el total historico del producto usado en el trabajo siga siendo correcto.

**Validacion**
- Crear un trabajo con producto y confirmar:
- baja el stock correcto
- se consumen lotes en orden FIFO
- se generan movimientos de inventario
- falla correctamente si no hay stock suficiente

### Paso 2. Blindar la reversa de stock de ventas anuladas

**Problema**
- `InventoryService::reverseStockFromSale()` puede fallar si el producto ya no existe o esta eliminado logicamente.

**Archivos a revisar**
- `app/Services/InventoryService.php`
- `app/Models/Product.php`
- `app/Models/Sale.php`

**Acciones**
1. Agregar validacion cuando el producto no exista.
2. Decidir politica para productos eliminados logicamente:
- usar `withTrashed()`
- o saltar el movimiento con log de advertencia
3. Garantizar que la operacion no deje el sistema en estado parcial.

**Validacion**
- Anular una venta normal.
- Anular una venta con producto eliminado logicamente.
- Confirmar que no se rompe el flujo y que el stock queda consistente.

### Paso 3. Cerrar condiciones de carrera en ventas

**Problema**
- La venta protege parcialmente stock, pero hay riesgo de inconsistencias concurrentes entre productos, lotes y consumo.

**Archivos a revisar**
- `app/Http/Controllers/SaleController.php`
- `app/Services/InventoryService.php`
- `app/Models/Batch.php`

**Acciones**
1. Revisar que toda validacion y descuento ocurra dentro de la misma transaccion.
2. Bloquear filas necesarias antes de descontar stock.
3. Evitar que dos ventas simultaneas consuman el mismo lote.
4. Asegurar que la excepcion por stock insuficiente revierta toda la operacion.

**Validacion**
- Simular dos ventas simultaneas del mismo producto.
- Confirmar que no haya sobreventa.
- Confirmar que el stock final y los lotes coincidan.

### Paso 4. Proteger la generacion de facturas contra duplicados

**Problema**
- Dos solicitudes simultaneas podrian generar mas de una factura por la misma orden.

**Archivos a revisar**
- `app/Http/Controllers/InvoiceController.php`
- `app/Models/Invoice.php`
- `app/Models/ServiceOrder.php`
- `database/migrations/2026_02_05_061438_create_invoices_table.php`

**Acciones**
1. Mover la generacion de factura a una transaccion.
2. Bloquear la orden antes de verificar si ya existe factura.
3. Definir si debe existir una restriccion unica por `service_order_id`.
4. Mantener sincronizados `status` y `completed_at`.

**Validacion**
- Intentar generar dos facturas seguidas para la misma orden.
- Intentar generar dos facturas concurrentes.
- Confirmar que solo exista una.

### Criterio de cierre de Fase 1

- El inventario de ventas y trabajos usa FIFO.
- No hay crash por anulacion con productos borrados logicamente.
- No se duplica una factura por concurrencia.
- Todas las operaciones criticas revierten correctamente ante error.

---

## Fase 2. Reglas de negocio de ordenes y trabajos

### Objetivo

Evitar estados invalidos y datos incoherentes en ordenes, trabajos y facturas.

### Paso 5. Validar transiciones de estado

**Problema**
- Hoy una orden o trabajo puede pasar entre estados sin restricciones de negocio.

**Archivos a revisar**
- `app/Http/Controllers/ServiceOrderController.php`
- `app/Http/Controllers/WorkshopJobController.php`
- `app/Models/ServiceOrder.php`
- `app/Models/WorkshopJob.php`

**Acciones**
1. Definir mapa de transiciones permitidas.
2. Implementar la validacion en modelo o servicio de dominio.
3. Rechazar cambios invalidos desde controladores.
4. Revisar si cerrar una orden depende de que todos sus trabajos esten completos.

**Transiciones sugeridas**
- `pending -> in_progress`
- `pending -> cancelled`
- `in_progress -> completed`
- `in_progress -> cancelled`
- `completed` como estado terminal
- `cancelled` como estado terminal

**Validacion**
- Probar cambios validos e invalidos.
- Confirmar mensaje claro al usuario.

### Paso 6. Centralizar la logica de finalizacion

**Problema**
- El cierre de ordenes puede depender de varios puntos del sistema y quedar inconsistente.

**Archivos a revisar**
- `app/Http/Controllers/InvoiceController.php`
- `app/Http/Controllers/ServiceOrderController.php`
- `app/Http/Controllers/WorkshopJobController.php`
- `app/Models/ServiceOrder.php`

**Acciones**
1. Definir una sola regla de negocio para marcar la orden como completada.
2. Decidir si la factura cierra la orden o si la orden debe estar completa antes de facturar.
3. Asegurar actualizacion consistente de `status`, `started_at` y `completed_at`.

**Validacion**
- Crear orden nueva.
- Agregar trabajos.
- Completar trabajos.
- Facturar.
- Confirmar que las fechas y estados sean coherentes en todo el flujo.

### Criterio de cierre de Fase 2

- No se pueden hacer transiciones invalidas.
- El cierre de la orden sigue una sola regla clara.
- `completed_at` siempre refleja una finalizacion real.

---

## Fase 3. Seguridad y autorizacion

### Objetivo

Evitar que cualquier usuario autenticado acceda a funciones administrativas o sensibles.

### Paso 7. Definir roles base del sistema

**Roles sugeridos**
- `admin`
- `inventario`
- `ventas`
- `taller`

**Archivos a revisar**
- `app/Models/User.php`
- `routes/web.php`
- controladores con operaciones CRUD sensibles

**Acciones**
1. Elegir estrategia:
- policies nativas de Laravel
- gates simples
- paquete de roles y permisos si el proyecto lo requiere
2. Mapear permisos por modulo.
3. Proteger rutas y acciones sensibles.

### Paso 8. Aplicar policies o gates por modulo

**Modulos prioritarios**
- productos
- inventario
- ventas
- ordenes de servicio
- facturas
- configuraciones
- mecanicos
- tipos de trabajo

**Archivos probables**
- `app/Policies/*`
- `app/Providers/AuthServiceProvider.php` o equivalente
- `routes/web.php`
- controladores de modulo

**Acciones**
1. Crear reglas de acceso por accion.
2. Agregar `authorize()` en controladores donde corresponda.
3. Aplicar middleware `can:*` en rutas.

### Paso 9. Corregir acciones que responden con 404 por diseno

**Problema**
- Algunos controladores ocultan acciones con `abort(404)` en lugar de usar autorizacion real.

**Archivos a revisar**
- `app/Http/Controllers/SupplierController.php`
- cualquier otro controlador con esa tecnica

**Acciones**
1. Reemplazar `abort(404)` por autorizacion explicita.
2. Usar `403` o redireccion amigable segun el caso.

### Validacion de Fase 3

- Usuario admin puede operar modulos completos.
- Usuario de ventas no puede editar configuraciones ni inventario critico.
- Usuario de taller no puede generar cambios administrativos fuera de su alcance.
- Las rutas sensibles devuelven respuesta correcta ante acceso no autorizado.

### Criterio de cierre de Fase 3

- Ninguna operacion sensible queda protegida solo por `auth`.
- Los permisos son trazables y predecibles.

---

## Fase 4. Dashboard, reportes y rendimiento

### Objetivo

Asegurar que las metricas sean correctas y que la carga del dashboard no escale mal.

### Paso 10. Eliminar N+1 en dashboard

**Problema**
- La carga de mecanicos consulta trabajos por cada mecanico.

**Archivos a revisar**
- `app/Http/Controllers/DashboardController.php`
- `app/Models/Mechanic.php`
- `app/Models/WorkshopJob.php`

**Acciones**
1. Reemplazar el calculo por `withCount`, `withSum` o subconsultas.
2. Cargar solo columnas necesarias.
3. Revisar tambien ventas recientes y stock bajo.

**Validacion**
- Comparar cantidad de consultas antes y despues.
- Confirmar que las metricas no cambian incorrectamente.

### Paso 11. Estandarizar estados y fechas en reportes

**Problema**
- El sistema mezcla varias convenciones para fechas y estados.

**Archivos a revisar**
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/ReportController.php`
- `app/Models/Sale.php`
- `database/migrations/2026_02_05_003714_create_sales_table.php`

**Acciones**
1. Confirmar estados oficiales de ventas.
2. Usar siempre `sale_date` para ventas cuando el dato de negocio sea fecha de venta.
3. Usar `completed_at` cuando el dato de negocio sea cierre del servicio.
4. Documentar la convencion en `docs/`.

**Validacion**
- Comparar dashboard con ventas y ordenes reales del sistema.
- Revisar que los reportes del dia y del mes no difieran por columnas mal usadas.

### Paso 12. Agregar indices faltantes

**Archivos a revisar**
- migraciones existentes
- nueva migracion de indices

**Indices sugeridos**
- `sales.sale_date`
- `sales.payment_method`
- `inventory_movements.supplier_id`
- `inventory_movements.movement_date`
- `workshop_jobs.mechanic_id`
- `workshop_jobs.completed_at`

**Acciones**
1. Revisar indices ya existentes para no duplicar.
2. Crear una migracion incremental.
3. Medir impacto en filtros y dashboard.

### Criterio de cierre de Fase 4

- El dashboard refleja datos correctos.
- El numero de consultas se reduce de forma visible.
- Los filtros mas usados responden mas rapido.

---

## Fase 5. Deuda tecnica y mantenibilidad

### Objetivo

Reducir duplicacion y clarificar el modelo de datos para facilitar cambios futuros.

### Paso 13. Introducir Form Requests

**Archivos candidatos**
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/ServiceOrderController.php`
- `app/Http/Controllers/WorkshopJobController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Http/Controllers/InventoryController.php`

**Acciones**
1. Crear requests por flujo critico.
2. Mover reglas y mensajes desde controladores.
3. Reusar validaciones comunes.

### Paso 14. Revisar modelos legacy o duplicados

**Candidatos**
- `app/Models/ProductPurchase.php`
- logica actual basada en `Batch`

**Acciones**
1. Confirmar si `ProductPurchase` sigue usandose.
2. Si no se usa, marcarlo para retiro controlado.
3. Documentar la fuente oficial del historial de compras.

### Paso 15. Normalizar semantica de precios y costos

**Problema**
- Hay riesgo de mezclar precio de venta, costo de lote, costo historico y totales de linea.

**Archivos a revisar**
- `app/Models/Product.php`
- `app/Models/Batch.php`
- `app/Models/SaleProduct.php`
- `app/Models/JobProduct.php`
- `app/Services/InventoryService.php`

**Acciones**
1. Definir que campo representa cada concepto.
2. Evitar calculos financieros mezclando precio actual con costo historico.
3. Documentar la regla en `docs/guia_tecnica.md` si aplica.

### Paso 16. Fortalecer pruebas

**Pruebas prioritarias**
- venta con FIFO
- trabajo con productos usando FIFO
- anulacion de venta
- factura unica por orden
- transiciones de estado
- autorizacion por rol
- dashboard con metricas basicas

**Archivos candidatos**
- `tests/Feature/*`
- `tests/Unit/*`

### Criterio de cierre de Fase 5

- Menos validacion duplicada en controladores.
- Modelo de datos mas claro.
- Cobertura basica sobre dinero, inventario y permisos.

---

## Orden sugerido de ejecucion

1. FIFO en trabajos
2. Reversa segura de stock
3. Concurrencia en ventas
4. Factura unica por orden
5. Transiciones de estado
6. Cierre coherente de ordenes
7. Roles y permisos
8. Dashboard sin N+1
9. Estandarizacion de estados y fechas
10. Indices
11. Form Requests
12. Limpieza de modelos legacy
13. Normalizacion de costos y precios
14. Pruebas

---

## Sugerencia de ramas

- `fix/inventory-fifo-jobs`
- `fix/sales-stock-concurrency`
- `fix/invoice-unique-service-order`
- `feat/order-status-rules`
- `feat/authorization-policies`
- `perf/dashboard-queries`
- `refactor/form-requests-and-cleanup`

---

## Checklist ejecutivo

### Semana 1
- Corregir FIFO en trabajos
- Blindar anulacion de ventas
- Cerrar carrera en facturacion

### Semana 2
- Cerrar carrera en ventas
- Implementar transiciones de estado
- Centralizar finalizacion de ordenes

### Semana 3
- Implementar policies y permisos
- Corregir accesos con 404 artificial

### Semana 4
- Optimizar dashboard
- Agregar indices
- Estandarizar fechas y estados

### Semana 5
- Migrar validaciones a Form Requests
- Revisar modelos duplicados
- Agregar pruebas clave

---

## Definicion de terminado

Se considera completado este plan cuando:

- el inventario es consistente en ventas, trabajos y anulaciones
- la facturacion no duplica registros por concurrencia
- los permisos restringen correctamente acciones sensibles
- el dashboard usa datos correctos y sin consultas explosivas
- los flujos criticos tienen pruebas automatizadas basicas

---

## Fase 6. Multi-Sede

### Objetivo

Permitir la operacion independiente de multiples sucursales con inventarios separados, ventas, ordenes y mecanicos por sede, con capacidad de transferir productos entre sedes.

### Paso 17. Estructura de datos multi-sede

**Nuevas tablas**
- `branches` - Sedes del negocio (id, name, address, phone, email, is_active)
- `branch_transfers` - Transferencias entre sedes (from_branch_id, to_branch_id, product_id, quantity, status, completed_at)

**Tablas modificadas**
- `products.branch_id` - Inventario por sede
- `mechanics.branch_id` - Mecanicos fijos por sede
- `service_orders.branch_id` - Ordenes por sede
- `sales.branch_id` - Ventas por sede

**Archivos creados**
- `app/Models/Branch.php`
- `app/Models/BranchTransfer.php`
- `app/Services/BranchService.php`
- `app/Http/Middleware/SetCurrentBranch.php`
- `app/Http/Controllers/BranchController.php`
- `app/Http/Controllers/BranchTransferController.php`
- `database/seeders/BranchSeeder.php`
- `tests/Feature/MultiBranchTest.php`

### Paso 18. Scope por sede en controladores

**Patron implementado**
```php
public function index(Request $request, BranchService $branchService)
{
    $branchId = $branchService->getCurrentBranch()?->id;
    $query = Product::forBranch($branchId);
    // ...
}
```

**Controladores actualizados**
- `DashboardController` - Todas las metricas filtradas por sede
- `ProductController` - Listado, creacion y edicion por sede
- `MechanicController` - Listado y creacion por sede
- `ServiceOrderController` - Listado, creacion y detalle por sede
- `SaleController` - Listado, creacion y cancelacion por sede
- `WorkshopJobController` - Listado y creacion por sede (valida mecanico de misma sede)
- `InventoryController` - Movimientos y ajustes por sede
- `InvoiceController` - Listado por sede

### Paso 19. Flujo de transferencias entre sedes

**Estados de transferencia**
- `pending` → `in_transit` → `completed`
- `pending` → `cancelled`
- `in_transit` → `cancelled`

**Al completar transferencia**
1. Descontar stock del producto en sede origen
2. Crear producto en sede destino si no existe (por UPC) o sumar stock si existe
3. Registrar movimientos `transfer_out` y `transfer_in`
4. Actualizar estado a `completed` con `completed_at` y `completed_by`

### Paso 20. Selector de sede en UI

**Middleware `SetCurrentBranch`**
- Se ejecuta en cada request autenticado
- Si no hay sede en sesion, asigna la primera sede activa
- Comparte `$currentBranch` con todas las vistas via `view()->share()`

**Endpoint para cambiar sede**
- `POST /switch-branch` con `branch_id`
- Redirige al referer con mensaje de exito

### Criterio de cierre de Fase 6

- Cada sede opera con inventario independiente
- Las transferencias entre sedes actualizan stock correctamente
- El dashboard muestra solo datos de la sede activa
- Los mecanicos solo pueden asignarse a trabajos de su sede
- Las vistas filtran por sede activa automaticamente
- Tests automatizados cubren filtrado y transferencias

---

## Orden sugerido de ejecucion (actualizado)

1. FIFO en trabajos ✅
2. Reversa segura de stock ✅
3. Concurrencia en ventas ✅
4. Factura unica por orden ✅
5. Transiciones de estado ✅
6. Cierre coherente de ordenes ✅
7. Roles y permisos
8. Dashboard sin N+1
9. Estandarizacion de estados y fechas
10. Indices
11. Form Requests
12. Limpieza de modelos legacy
13. Normalizacion de costos y precios
14. Pruebas
15. **Multi-sede (implementado)** ✅

---

## Sugerencia de ramas (actualizada)

- `fix/inventory-fifo-jobs` ✅
- `fix/sales-stock-concurrency` ✅
- `fix/invoice-unique-service-order` ✅
- `feat/order-status-rules` ✅
- `feat/authorization-policies`
- `perf/dashboard-queries`
- `refactor/form-requests-and-cleanup`
- `**feat/multi-branch**` ✅
