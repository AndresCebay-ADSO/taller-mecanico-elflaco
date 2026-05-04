# Auditoria Tecnica del Software

## Resumen ejecutivo

El sistema ya tiene una base funcional y varias mejoras recientes importantes en inventario, facturacion y transiciones de estado. Sin embargo, todavia existen riesgos funcionales y de integridad de datos que conviene resolver antes de considerar el software completamente estable para produccion.

### Estado general

- **Funcionalidad base:** media-alta
- **Integridad de inventario:** media
- **Integridad contable y reportes:** media-baja
- **Seguridad operativa:** media-baja
- **Mantenibilidad:** media
- **Cobertura de pruebas:** baja-media

### Hallazgos totales

- **Criticos:** 3
- **Altos:** 6
- **Medios:** 8
- **Bajos / deuda tecnica:** 7

---

## Lo que ya esta bien resuelto

- El descuento FIFO para ventas y trabajos ya se centraliza en `InventoryService`.
- La generacion de factura ya usa transaccion y bloqueo de orden para evitar duplicados a nivel aplicacion.
- Las transiciones de estado de ordenes y trabajos ya tienen validacion en controladores y enforcement a nivel modelo.
- Ya existen pruebas puntuales para inventario, factura unica y transiciones.

---

## Hallazgos criticos

### 1. Credenciales administrativas inseguras por defecto

**Archivo:** `database/seeders/AdminSeeder.php`

**Problema**
- El seeder crea un administrador con correo fijo y contrasena publica: `admin@tallerflacos.com / password`.

**Impacto**
- Si se ejecuta en un entorno real, deja una credencial predecible y trivial de comprometer.

**Recomendacion**
- Tomar email y password desde variables de entorno.
- Obligar cambio de contrasena al primer acceso o documentar reemplazo inmediato tras instalacion.

### 2. Ajustes de inventario se registran con tipo incorrecto

**Archivo:** `app/Http/Controllers/InventoryController.php`

**Problema**
- Los ajustes positivos llaman `incrementStock()` del modelo `Product`, pero ese metodo registra el movimiento como `purchase`, no como `adjustment`.
- Los ajustes negativos validan `reason` como `adjustment`, `damage` o `loss`, pero el movimiento se guarda siempre como `adjustment` y no conserva el motivo real.

**Impacto**
- El historial de inventario queda contaminado.
- Las compras y ajustes se mezclan.
- Se pierde trazabilidad de averias, perdidas y ajustes manuales.

**Recomendacion**
- Mover ajustes a un flujo propio dentro de `InventoryService`.
- Registrar tipo correcto y notas/motivo real.
- Hacerlo dentro de transaccion.

### 3. El stock del producto puede alterarse directamente sin trazabilidad

**Archivo:** `app/Http/Controllers/ProductController.php`

**Problema**
- El metodo `update()` permite modificar `stock` directamente.
- Eso evita batch FIFO, movimientos de inventario y consistencia historica.

**Impacto**
- El stock puede dejar de coincidir con lotes y movimientos.
- Se rompe la trazabilidad del inventario.

**Recomendacion**
- Quitar `stock` del update normal del producto.
- Permitir cambios de stock solo desde compras, ajustes o reversas controladas.

---

## Hallazgos altos

### 4. La base de datos no garantiza una sola factura por orden

**Archivo:** `database/migrations/2026_02_05_061438_create_invoices_table.php`

**Problema**
- Aunque la aplicacion evita duplicados, `service_order_id` no es unico.

**Impacto**
- Si alguna insercion se hace fuera del flujo actual, pueden existir multiples facturas por orden.

**Recomendacion**
- Agregar indice unico a `invoices.service_order_id` si la regla de negocio es una factura por orden.

### 5. `DatabaseSeeder` crea un usuario de prueba que puede colarse en ambientes reales

**Archivo:** `database/seeders/DatabaseSeeder.php`

**Problema**
- Siempre crea `test@example.com`.

**Impacto**
- Puede terminar existiendo una cuenta no deseada en entornos reales.

**Recomendacion**
- Eliminarlo del seeder principal o condicionarlo a entornos locales.

### 6. Reportes financieros no representan la operacion real

**Archivo:** `app/Http/Controllers/ReportController.php`

**Problema**
- La utilidad del taller se calcula como `25%` del total de ventas.
- No incorpora trabajos, costos reales, productos por trabajo ni lotes.

**Impacto**
- El reporte financiero puede ser engañoso.
- El cliente podria tomar decisiones sobre datos incorrectos.

**Recomendacion**
- Redefinir el modulo de reportes con metricas reales del negocio.

### 7. Dashboard todavia tiene reglas de negocio ambiguas

**Archivo:** `app/Http/Controllers/DashboardController.php`

**Problema**
- `pendingOrders` cuenta ordenes `completed`, pero la vista parece hablar de pendientes de entrega.
- Las ventas usan `sale_date`; no esta documentado si esa es la fecha de negocio oficial para dashboard.

**Impacto**
- El dashboard puede mostrar cifras validas tecnicamente pero equivocadas para la operacion.

**Recomendacion**
- Definir con el cliente:
- que significa “pendiente”
- si el dashboard usa `sale_date` o `created_at`

### 8. No existe control de permisos por modulo o accion

**Archivos:** `routes/web.php`, controladores CRUD

**Problema**
- Todo el sistema depende solo de `auth`.

**Impacto**
- Si en el futuro aparece un segundo usuario, el sistema queda sin proteccion por roles.
- Aunque hoy haya un solo usuario, sigue siendo deuda tecnica estructural.

**Recomendacion**
- Implementar al menos una capa simple de roles o permisos, aunque sea solo `admin`.

### 9. Restaurar stock sobre productos eliminados logicamente puede generar stock “invisible”

**Archivo:** `app/Services/InventoryService.php`

**Problema**
- `reverseStockFromSale()` usa `withTrashed()` y puede devolver stock a un producto soft-deleted.

**Impacto**
- El stock vuelve a una entidad que no aparece en consultas normales.

**Recomendacion**
- Definir politica:
- o restaurar el producto
- o impedir reversa y alertar
- o permitirlo pero mostrarlo como pendiente de reactivacion

---

## Hallazgos medios

### 10. Listado de ventas tiene doble ordenamiento conflictivo

**Archivo:** `app/Http/Controllers/SaleController.php`

**Problema**
- `latest('sale_date')->latest()` mezcla dos criterios y puede producir orden no esperado.

**Recomendacion**
- Usar `orderByDesc('sale_date')->orderByDesc('created_at')`.

### 11. Ajustes de inventario no usan transaccion ni bloqueo explicito

**Archivo:** `app/Http/Controllers/InventoryController.php`

**Problema**
- `storeAdjustment()` modifica stock usando metodos del modelo sin encapsular transaccion.

**Impacto**
- Riesgo de inconsistencia si algo falla a mitad del flujo.

### 12. `SupplierController` usa `abort(404)` para ocultar acciones

**Archivo:** `app/Http/Controllers/SupplierController.php`

**Problema**
- `create()` y `edit()` responden 404 en lugar de usar una politica clara de acceso o de UI.

**Impacto**
- UX confusa y mantenimiento pobre.

### 13. Borrado de mecanicos puede perder referencia historica legible

**Archivo:** `app/Http/Controllers/MechanicController.php`

**Problema**
- Se elimina el mecanico sin verificar historial.
- `workshop_jobs.mechanic_id` queda en null por la FK.

**Impacto**
- Se pierde trazabilidad humana en trabajos historicos.

**Recomendacion**
- Desactivar en lugar de borrar si tiene historial.

### 14. Lotes se editan sin estrategia fuerte de concurrencia

**Archivo:** `app/Http/Controllers/BatchController.php`

**Problema**
- Hay transaccion, pero no bloqueo explicito sobre lote y producto.

**Impacto**
- Bajo escenarios concurrentes, el stock global del producto puede desalinearse.

### 15. `SettingController` no valida entradas

**Archivo:** `app/Http/Controllers/SettingController.php`

**Problema**
- Guarda configuracion libremente sin reglas de formato.

**Impacto**
- Se pueden persistir correos invalidos, impuestos no numericos o valores vacios.

### 16. Cobertura de pruebas todavia es limitada

**Carpeta:** `tests/`

**Problema**
- Hay pruebas para piezas criticas recientes, pero no para:
- dashboard
- reportes
- ajustes de inventario
- CRUD de productos
- seeders
- settings

### 17. No hay una capa consistente de Form Requests

**Archivos:** varios controladores

**Problema**
- Algunas validaciones viven en requests, otras en controladores.

**Impacto**
- Reglas duplicadas, mantenimiento mas dificil.

---

## Hallazgos bajos y deuda tecnica

### 18. `ProductPurchase` parece modelo legacy sin rol claro

**Archivo:** `app/Models/ProductPurchase.php`

**Problema**
- El flujo actual ya usa `Batch` como fuente principal de compras.

### 19. Convenciones de fechas y estados no estan documentadas

**Modulos afectados**
- ventas
- dashboard
- reportes
- ordenes

### 20. Persisten textos con problemas de codificacion

**Archivos:** vistas, mensajes y comentarios varios

**Problema**
- Hay caracteres mal codificados en varias cadenas.

### 21. `StoreProductRequest` autoriza siempre

**Archivo:** `app/Http/Requests/StoreProductRequest.php`

**Problema**
- `authorize()` devuelve `true` sin reglas de acceso.

### 22. `InvoiceController::index()` no pagina

**Archivo:** `app/Http/Controllers/InvoiceController.php`

**Problema**
- Carga todas las facturas con `Invoice::all()`.

### 23. `ReportController` y dashboard no tienen pruebas automatizadas

### 24. Seeds de configuracion contienen datos de ejemplo que podrian quedar en produccion

**Archivo:** `database/seeders/SettingSeeder.php`

### 25. Borrados y restauraciones no siguen una politica uniforme

**Archivos afectados**
- productos
- proveedores
- mecanicos
- tipos de trabajo
- facturas

---

## Plan integral de implementacion

## Fase 1. Integridad de inventario

### Objetivo
- Garantizar que el stock, los lotes y los movimientos siempre coincidan.

### Tareas
1. Rehacer `storeAdjustment()` usando `InventoryService`.
2. Crear metodos explicitos para:
- ajuste positivo
- ajuste negativo
- perdida
- dano
3. Prohibir editar `stock` desde `ProductController::update()`.
4. Revisar politica de reversa sobre productos soft-deleted.
5. Agregar pruebas para ajustes y casos borde de reversa.

### Resultado esperado
- Ningun cambio de stock ocurre fuera de flujos trazables.

---

## Fase 2. Integridad contable y documental

### Objetivo
- Asegurar que ventas, facturas y reportes no se contradigan.

### Tareas
1. Agregar restriccion unica para `invoices.service_order_id`.
2. Corregir numeracion de factura con estrategia robusta y documentada.
3. Revisar el calculo de `total_amount` en ventas y trabajos para confirmar que representa venta, ingreso o costo segun negocio.
4. Rediseñar `ReportController` con metricas reales.
5. Definir formalmente que fechas alimentan dashboard y reportes.

### Resultado esperado
- Facturas y reportes representan la operacion real del taller.

---

## Fase 3. Seguridad y salida controlada

### Objetivo
- Evitar cuentas inseguras y minimizar errores de despliegue.

### Tareas
1. Reescribir `AdminSeeder` para usar variables de entorno.
2. Eliminar usuario de prueba de `DatabaseSeeder` o limitarlo a local/testing.
3. Revisar `SettingSeeder` para separar datos demo de datos reales.
4. Añadir una politica minima de acceso, aunque solo exista `admin`.
5. Revisar configuracion de produccion:
- `APP_DEBUG=false`
- correo real
- backup
- manejo de errores

### Resultado esperado
- El software se puede instalar sin credenciales debiles ni datos basura.

---

## Fase 4. UX operativa y consistencia funcional

### Objetivo
- Alinear interfaz y reglas de negocio reales.

### Tareas
1. Definir con el cliente el significado de “pendiente” en ordenes.
2. Corregir dashboard segun esa definicion.
3. Corregir `SaleController` para ordenar ventas con criterios explicitos.
4. Cambiar `SupplierController` para evitar `abort(404)` artificial.
5. Definir politica uniforme de borrado:
- desactivar
- soft delete
- hard delete

### Resultado esperado
- La interfaz comunica exactamente lo que el negocio espera.

---

## Fase 5. Mantenibilidad y deuda tecnica

### Objetivo
- Bajar el costo de mantenimiento y el riesgo de regresiones.

### Tareas
1. Migrar validaciones repetidas a Form Requests.
2. Revisar si `ProductPurchase` sigue siendo necesario.
3. Estandarizar mensajes y codificacion UTF-8.
4. Agregar paginacion donde falte.
5. Documentar estados, fechas y politicas de negocio en `docs/`.

### Resultado esperado
- Proyecto mas limpio, mas predecible y mas facil de evolucionar.

---

## Fase 6. Pruebas

### Objetivo
- Cubrir los flujos operativos del sistema con pruebas minimas suficientes.

### Tareas
1. Pruebas de ajustes de inventario.
2. Pruebas de dashboard y reportes.
3. Pruebas de CRUD de productos y proveedores.
4. Pruebas de seeders criticos.
5. Pruebas de edge cases:
- factura sobre orden sin trabajos
- reversa con producto soft-deleted
- lote parcialmente consumido y luego editado

### Resultado esperado
- Menor riesgo de romper funcionalidades al corregir deuda tecnica.

---

## Priorizacion recomendada

### Inmediato
- `AdminSeeder`
- `DatabaseSeeder`
- `InventoryController::storeAdjustment()`
- `ProductController::update()` sobre stock
- restriccion unica en facturas

### Corto plazo
- `ReportController`
- `DashboardController`
- `SaleController` ordenamiento
- politica de borrado de mecanicos y proveedores

### Mediano plazo
- permisos
- refactor de validaciones
- limpieza de modelos legacy
- cobertura de pruebas adicional

---

## Veredicto tecnico actual

El software **es funcional**, pero **todavia no esta completamente endurecido**. Para un uso interno y controlado por una sola persona puede operar, pero conviene cerrar las fallas de inventario, seeders inseguros y consistencia contable antes de considerarlo “terminado” desde una perspectiva tecnica.
