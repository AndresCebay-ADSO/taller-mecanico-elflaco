# Puesta en producción y demo para el cliente

## 1. Qué se corrigió antes de producción

| Área | Cambio |
|------|--------|
| Inventario | Todo cambio de stock pasa por `InventoryService` y mantiene `products.stock = suma de lotes`. Ajustes, eliminación de trabajos, anulaciones y transferencias ya no descuadran los lotes FIFO. |
| Inventario | Comando `php artisan inventory:check [--fix]` para detectar y conciliar descuadres (programado cada noche a las 23:30, solo reporta). |
| Seguridad | Cada registro se valida contra la sucursal activa (antes se podía ver/anular/editar datos de otra sede cambiando el ID). |
| Seguridad | Rol administrador para: anular ventas y facturas, ajustes de inventario, corrección de lotes, eliminar productos/mecánicos/proveedores, usuarios, sucursales, transferencias, tipos de trabajo y configuración. |
| Seguridad | Usuarios desactivables (no pueden iniciar sesión y se cierra su sesión activa). La sucursal en sesión se revalida en cada petición. |
| Seguridad | Eliminado el formulario de factura manual (permitía montos arbitrarios). Dependencias actualizadas sin vulnerabilidades conocidas (`composer audit`). |
| Concurrencia | Anular venta y completar transferencia bloquean la fila: un doble clic ya no devuelve stock dos veces. Transacciones con reintento ante deadlock. |
| Datos | Columnas de dinero ampliadas (antes montos ≥ $1.000.000 fallaban). Zona horaria `America/Bogota` (antes las ventas después de las 7 p. m. quedaban con fecha del día siguiente). Límites de longitud en textos. |
| Facturación | Numeración consecutiva `FAC-000001`, solo con todos los trabajos completados, total igual a mano de obra + repuestos en factura, orden y dashboard. Factura imprimible con datos del taller. |
| Funcional | Gestión de usuarios, cambio de contraseña, selector de sucursal y menú de administración. Trabajos individuales quedan en la sucursal correcta. Errores de negocio se muestran como mensajes (no error 500). |

## 2. Checklist de despliegue

Requisitos: PHP 8.2+ (extensiones `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`), MySQL 8, Composer, Node 20+, HTTPS.

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env      # solo la primera vez
php artisan key:generate  # solo la primera vez
```

Valores obligatorios en `.env` del servidor:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio
APP_TIMEZONE=America/Bogota
LOG_STACK=daily
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...   # usuario de BD propio, no root
ADMIN_EMAIL=...  ADMIN_PASSWORD=...                  # contraseña fuerte
```

```bash
php artisan migrate --force
php artisan db:seed --force          # primera vez: configuración, sede principal y admin
php artisan inventory:check          # si hay datos previos: revisar y luego --fix
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Permisos de escritura para el usuario del servidor web en `storage/` y `bootstrap/cache/`.

Cron (tareas programadas):

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

> **Datos existentes:** los registros creados antes de este cambio guardaron las horas en UTC. Si ya había datos reales, revisar antes de salir; si solo eran pruebas, empezar con base limpia.

## 3. Copias de seguridad (obligatorio)

```cron
# Respaldo diario 2:00 a. m., conserva 14 días
0 2 * * * mysqldump --single-transaction -u USUARIO -p'CLAVE' taller_el_flaco | gzip > /respaldos/taller_$(date +\%F).sql.gz && find /respaldos -name 'taller_*.sql.gz' -mtime +14 -delete
```

- Copiar los respaldos fuera del servidor (Google Drive, S3, otro equipo).
- Probar una restauración al menos una vez antes de salir a producción.

## 4. Demo para el cliente

Usar **una base de datos separada** (`migrate:fresh` borra todo):

```bash
# en .env: DB_DATABASE=taller_demo  (y crear esa base en MySQL)
php artisan migrate:fresh --seed --seeder=DemoSeeder
php artisan serve
```

Genera 60 días de operación de un taller de motos con dos sedes: catálogo de repuestos con lotes de distinto costo, ventas diarias (Efectivo, Nequi, Daviplata…), órdenes en todos los estados, facturas, una transferencia completada y otras pendientes, ajustes por daño/pérdida y productos con stock bajo.

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | admin@elflaco.test | Demo2026! |
| Cajera Sede Principal | caja.principal@elflaco.test | Demo2026! |
| Cajero Sede Centro | caja.centro@elflaco.test | Demo2026! |
| Jefe de taller (ambas sedes) | jefe.taller@elflaco.test | Demo2026! |
| Usuario inactivo | exempleado@elflaco.test | Demo2026! (no entra) |

### Guion sugerido (15–20 min)

1. **Dashboard** como administrador: ventas de hoy y del mes, órdenes activas, alerta de stock bajo, ganancias por mecánico.
2. **Selector de sucursal** (menú lateral): cambiar a Sede Centro y ver cómo cambian las cifras.
3. **Órdenes**: abrir una orden *En progreso*, añadir un trabajo con repuestos, completarlo y **generar la factura**; imprimirla.
4. **Ventas directas**: registrar una venta con Nequi y ver el recibo.
5. **Trazabilidad**: mostrar lotes FIFO con costos distintos, compras, ventas, ajustes y transferencias.
6. **Inventario**: filtro *Solo stock bajo* y registrar una compra a proveedor.
7. **Reportes**: ingresos, ganancia del taller, valor del inventario.
8. **Administración**: crear un usuario cajero, desactivar uno, completar la transferencia *En tránsito*.
9. Cerrar sesión y entrar como **cajera**: no ve anular ventas, ajustes ni administración.

## 5. Preguntas para obtener feedback del cliente

1. **IVA**: ¿los precios incluyen IVA? ¿La factura debe discriminar el 19 %? (Hoy el porcentaje se configura pero no se aplica.)
2. **Factura electrónica DIAN**: ¿están obligados? El sistema emite comprobantes internos; la factura electrónica requiere integrarse con un proveedor tecnológico autorizado.
3. **Permisos**: ¿qué debe poder hacer un cajero y qué solo el dueño? (Ver tabla de la sección 1.)
4. **Órdenes canceladas**: si ya se usaron repuestos, ¿se devuelven al inventario o se cobran?
5. **Pagos de órdenes**: ¿necesitan registrar método de pago, abonos o saldo pendiente en las facturas de taller?
6. **Cierre de caja** diario por cajero y por método de pago: ¿lo necesitan?
7. **Comisiones**: ¿son correctos los porcentajes y montos fijos por tipo de trabajo?
8. **Clientes y motos**: ¿quieren historial por placa o por cliente?
9. **Impresión**: ¿impresora de tickets (80 mm) o carta?
10. **Sedes**: ¿cuántas sedes reales hay y quién autoriza las transferencias?
