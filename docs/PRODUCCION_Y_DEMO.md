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
| Facturación | Anular una factura ya no la borra: queda como **anulada** (fecha y usuario) y la orden puede facturarse de nuevo con el siguiente número. |
| Órdenes | Cancelar una orden devuelve al inventario los repuestos de sus trabajos (si ya hay trabajos completados, se debe facturar). Cerrar una orden a mano exige todos sus trabajos completados. |
| Reportes | Cifras reales por mes y sucursal: ingresos, ganancia estimada al costo real de cada lote, ingresos por día, comisiones por mecánico, repuestos más vendidos, ventas por método de pago y valor del inventario. |
| Interfaz | "Añadir trabajo" en el detalle de la orden funciona; ventas y trabajos muestran el total en vivo con el precio FIFO real; accesos rápidos del dashboard; montos en formato colombiano; iconos incluidos en la app (ya no dependen de internet). |

## 2. Checklist de despliegue

> Instalación en Windows con XAMPP (máquina del cliente): ver [`INSTALACION_LOCAL.md`](INSTALACION_LOCAL.md), que incluye php.ini, seguridad de MySQL, Apache, respaldos en Windows y `php artisan taller:verificar`.

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
npm run build                                  # assets compilados (public/build no se versiona)
php artisan migrate:fresh --seed --seeder=DemoSeeder
php artisan serve
```

**Antes de la reunión**

- Ejecutar el seeder **la misma mañana de la demo**: los datos de "hoy" se generan hasta la hora en que se corre (si se genera días antes, el dashboard mostrará $0 hoy).
- Recorrer el guion una vez con la base recién generada; para repetir la demo desde cero, volver a correr el comando.
- El administrador entra en **Sede Principal** (la sede con más movimiento).
- Con `APP_DEBUG=true` cualquier error muestra el detalle técnico en pantalla; para la reunión puede usarse `APP_DEBUG=false`.
- Los iconos vienen incluidos en la app; solo las fuentes tipográficas usan internet (sin conexión se ve con la fuente del sistema).

Genera 60 días de operación de un taller de motos con dos sedes: catálogo de repuestos con lotes de distinto costo, ventas diarias (Efectivo, Nequi, Daviplata…), órdenes en todos los estados, facturas, una transferencia completada y otras pendientes, ajustes por daño/pérdida y productos con stock bajo.

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | admin@elflaco.test | Demo2026! |
| Cajera Sede Principal | caja.principal@elflaco.test | Demo2026! |
| Cajero Sede Centro | caja.centro@elflaco.test | Demo2026! |
| Jefe de taller (ambas sedes) | jefe.taller@elflaco.test | Demo2026! |
| Usuario inactivo | exempleado@elflaco.test | Demo2026! (no entra) |

### Guion sugerido (15–20 min)

1. **Dashboard** como administrador: ingresos de hoy y del mes, órdenes activas, alerta de stock bajo, comisiones por mecánico.
2. **Selector de sucursal** (menú lateral): cambiar a Sede Centro, ver cómo cambian las cifras y volver a Sede Principal.
3. **Órdenes**: abrir una orden *Pendiente*, **Añadir trabajo** con repuestos (el total y el reparto mecánico/taller se calculan en vivo), completarlo y **generar la factura**; imprimirla.
4. **Ventas directas** (acceso rápido *Nueva venta*): registrar una venta con Nequi viendo el total antes de cobrar; mostrar el recibo.
5. **Trazabilidad**: mostrar lotes FIFO con costos distintos, compras, ventas, ajustes y transferencias.
6. **Inventario**: filtro *Solo stock bajo*, detalle de un producto y registrar una compra a proveedor.
7. **Reportes**: ingresos del mes, ganancia estimada, ingresos por día (pasar el mouse por las barras), comisiones, más vendidos; cambiar al mes anterior con el selector.
8. **Administración**: crear un usuario cajero, desactivar uno, completar la transferencia *En tránsito*; anular una factura y mostrar que queda registrada como anulada.
9. Cerrar sesión y entrar como **cajera**: no ve anular ventas ni facturas, ajustes ni administración.

## 5. Preguntas para obtener feedback del cliente

1. **IVA**: ¿los precios incluyen IVA? ¿La factura debe discriminar el 19 %? (Hoy el porcentaje se configura pero no se aplica.)
2. **Factura electrónica DIAN**: ¿están obligados? El sistema emite comprobantes internos; la factura electrónica requiere integrarse con un proveedor tecnológico autorizado.
3. **Permisos**: ¿qué debe poder hacer un cajero y qué solo el dueño? (Ver tabla de la sección 1.)
4. **Órdenes canceladas**: hoy los repuestos vuelven al inventario y no se permite cancelar si ya hay trabajos completados. ¿Es así como trabajan o esos repuestos se cobran?
5. **Reportes**: ¿la cajera debe ver ganancias y comisiones, o solo el dueño?
6. **Pagos de órdenes**: ¿necesitan registrar método de pago, abonos o saldo pendiente en las facturas de taller?
7. **Cierre de caja** diario por cajero y por método de pago: ¿lo necesitan?
8. **Comisiones**: ¿son correctos los porcentajes y montos fijos por tipo de trabajo?
9. **Clientes y motos**: ¿quieren historial por placa o por cliente?
10. **Impresión**: ¿impresora de tickets (80 mm) o carta?
11. **Sedes**: ¿cuántas sedes reales hay y quién autoriza las transferencias?
