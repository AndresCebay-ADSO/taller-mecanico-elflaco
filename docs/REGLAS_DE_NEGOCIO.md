# Reglas de negocio de MotoTaller El Flaco

Cómo se comporta el sistema y por qué, módulo por módulo. Sirve para explicarle el sistema al taller y para
recordar qué es intencional. Las marcadas **(decidido)** salieron de la revisión de `docs/AUDITORIA_DECISIONES.md`;
las que todavía están por decidir aparecen al final.

Actualizado: 2026-11-14 (v1.1.0 en preparación).

## Usuarios y permisos

- Hay dos tipos de usuario: **administrador** y **empleado**. Hoy solo la dueña usa el sistema, como administradora.
- Solo el administrador puede hacer lo que cambia plata, inventario o permisos:
  - anular ventas y facturas;
  - hacer ajustes de inventario y corregir lotes;
  - desactivar o restaurar productos, desactivar proveedores, borrar mecánicos;
  - borrar movimientos de caja y reabrir una caja cerrada;
  - manejar usuarios, tipos de trabajo, configuración, sucursales y transferencias;
  - importar datos desde Excel.
- El empleado puede vender, crear órdenes y trabajos, facturar, registrar compras, usar la caja, editar productos
  (con precios) y ver Reportes completos (ver pendientes 7, 9 y 14).
- Un usuario desactivado no puede entrar; si estaba adentro, lo saca en su siguiente clic.
- Cambiar la contraseña cierra las otras sesiones abiertas de ese usuario.
- Contraseña olvidada: `php artisan taller:restablecer-clave correo@ejemplo.com` genera una nueva.
- Siempre debe quedar al menos una sucursal activa. Los menús de Sucursales y Transferencias se dejan aunque hoy
  haya una sola.

## Ventas de mostrador

- Métodos de pago: Efectivo, Nequi, Daviplata, Transferencia, Tarjeta, Otro.
- No deja vender más unidades de las que hay.
- El stock sale de los lotes más antiguos primero (FIFO). El precio es el del producto **(decidido, 4)**.
- **Con la caja de hoy cerrada no se registran ventas en efectivo (decidido, 1).** Se anotan en el cuaderno, la plata
  se deja en el cajón y se registran al otro día. Como la caja de mañana arranca con lo que se contó al cerrar,
  cuadra sola. Los otros métodos sí se aceptan. El aviso sale apenas se abre «Nueva venta». Si hace falta
  registrarla el mismo día, se reabre la caja.
- Anular una venta (administrador) devuelve las unidades a los mismos lotes. La venta queda guardada como
  «Anulada» y deja de sumar en caja y reportes.
- **Anular en efectivo una venta o factura de un día anterior pregunta si se le devolvió la plata al cliente
  (decidido, 2).** «Sí» saca la plata de la caja de hoy como «Devolución a cliente», ligada a ese documento.
  «No» es para corregir un error de digitación y no toca la caja. Si la caja de hoy ya está cerrada, no deja
  devolver: hay que reabrirla o anular sin devolver plata. Una venta del mismo día no pregunta: al anularla deja de
  contar en la caja sola.
- Después de una venta, si un producto queda en su stock mínimo o menos, sale un aviso de stock bajo.

## Órdenes de servicio y trabajos

- Estados: **Pendiente → En progreso → Completada**. Desde Pendiente o En progreso se puede **Cancelar**. No se
  devuelve a un estado anterior.
- Al agregar el primer trabajo, la orden pasa sola a «En progreso».
- **Los repuestos se descuentan del inventario apenas se agrega el trabajo**, no cuando se termina.
- Algunos tipos de trabajo no permiten agregar repuestos.
- No se pueden agregar trabajos a una orden completada o cancelada.
- **Una orden solo se completa generando su factura (decidido, 12).** «Completada» no aparece en «Editar orden».
  Para facturar, todos los trabajos tienen que estar completados y debe haber al menos uno.
- Cancelar una orden devuelve al inventario los repuestos de sus trabajos. No se puede si ya tiene trabajos
  completados: hay que facturarla o eliminar esos trabajos primero.
- Eliminar un trabajo devuelve sus repuestos. No se puede si la orden tiene una factura vigente.
- Una orden solo se puede eliminar si no tiene trabajos; si los tiene, se cancela.
- «Trabajo individual» crea por debajo una orden con ese único trabajo.
- Comisión del mecánico, según el tipo de trabajo:
  - **por porcentaje:** la mano de obra se reparte entre mecánico y taller con los porcentajes del tipo;
  - **monto fijo:** el mecánico y el taller reciben los montos fijos del tipo.
- El mecánico gana su comisión cuando su trabajo queda completado.

## Facturas (comprobantes de servicio)

- Se numeran FAC-000001, FAC-000002… sin huecos. Una factura anulada conserva su número.
- Valor = mano de obra + repuestos de los trabajos. Guarda cómo pagó el cliente.
- **Con la caja de hoy cerrada no se generan facturas en efectivo (decidido, 1).** Mismo manejo que las ventas.
- **Anular una factura (administrador) la deja guardada como «Anulada» y la orden vuelve a «En progreso»
  (decidido, 11).** Ahí se pueden agregar, eliminar o terminar trabajos y volver a facturar con el siguiente número.
  Si era en efectivo de un día anterior, pregunta por la devolución igual que una venta **(decidido, 2)**.
- **Una factura no cambia después de emitida (decidido, 11).** Guarda una copia de sus líneas (trabajos, mecánicos y
  repuestos con sus precios) y siempre se imprime con ella, aunque la orden se corrija después.
- El IVA está guardado en Configuración (19 %), pero **no se aplica** a ningún valor.

## Caja diaria

- Una caja por sucursal y por día, no por persona.
- **Base:** el sistema sugiere lo que quedó en el cajón al cerrar el último día (lo contado menos lo retirado). Se
  puede cambiar a mano.
- **Efectivo que debe haber** = base + ventas y facturas en efectivo + ingresos extra en efectivo − gastos, pagos a
  mecánicos, compras y retiros en efectivo.
- Lo pagado por Nequi, transferencia, etc. se muestra aparte: no pasa por el cajón.
- Tipos de movimiento: Gasto, Pago a mecánico, Retiro, Ingreso extra, Compra de repuestos y Devolución a cliente.
  La compra se registra desde Inventario → Registrar compra, si se pagó con plata de la caja. La devolución solo
  nace al anular una venta o factura; no se puede registrar a mano.
- **Cerrar:** se cuenta el efectivo, se indica cuánto se retira (no más de lo contado) y el sistema dice si sobra o
  falta.
- Una caja cerrada no acepta cambios: ni movimientos, ni compras pagadas con ella, ni ventas o facturas en efectivo.
  Solo el administrador la reabre.
- No se registra caja de días que no han llegado.
- Si se corrige el costo o la cantidad de una compra pagada con la caja, la salida de caja se ajusta sola. Si ese día
  ya se cerró, pide reabrirlo.
- **Comisiones por pagar:** lo ganado por cada mecánico en trabajos completados desde el primer día que se usó la
  caja, menos lo que se le ha pagado desde la caja.
- Anular una venta o factura en efectivo de hoy con la caja ya cerrada avisa que hay que reabrirla y cerrarla de
  nuevo.
- **Si la base de un día no coincide con lo que quedó al cerrar el día anterior, se avisa (decidido, 3).** Pasa al
  reabrir y volver a cerrar un día cuando el siguiente ya se empezó, o al cerrar tarde. El aviso sale en la caja de
  los dos días hasta que cuadre, con un botón para usar la cifra nueva. La base nunca cambia sola; si el día
  siguiente ya se cerró, hay que reabrirlo para corregirla.

## Inventario

- El stock de un producto es la suma de lo que queda en sus lotes. Cada compra crea un lote con su propio costo.
- **Un solo precio de venta por producto (decidido, 4).** Al registrar una compra con otro precio, todas las unidades
  pasan a ese precio. Cambiar el precio en «Editar producto» también cambia el de todas las unidades.
- Cada lote conserva su costo, así la ganancia sale con lo que costó cada unidad.
- El precio de compra del producto es el costo de la última compra. Solo se usa en los ajustes positivos y en las
  unidades que vuelven sin lote de origen (ver pendiente 6).
- Una compra exige un proveedor activo y asociado al producto.
- **Ajustes** (administrador), con motivo ajuste, daño o pérdida:
  - si suman, crean un lote al costo del producto;
  - si restan, sacan de los lotes más antiguos.
- **Corregir lote** (administrador): solo lotes de compra. La cantidad solo se corrige si no se ha vendido nada de
  ese lote. El precio de venta no se cambia ahí, sino en «Editar producto».
- Stock bajo: cuando el stock llega al mínimo o menos, sale en el inicio y se puede filtrar en Productos.
- Un producto solo se desactiva con stock en cero (primero se saca con un ajuste). Se puede restaurar.
- **Transferencias** (administrador): Pendiente → En tránsito → Completada o Cancelada. El stock se mueve al
  completarla. En la sucursal de destino el producto se busca por código, o por nombre y categoría; si no existe, se
  crea (ver pendiente 8).

## Reportes e inicio

- **Ingresos = ventas + facturas vigentes, por su fecha (decidido, 13).** Así «Ingresos», «Cobrado por método» y la
  caja siempre dan lo mismo. Una factura anulada deja de sumar. Una orden vieja completada sin factura no suma hasta
  que se le genere.
- **Ganancia estimada** = parte del taller en la mano de obra + margen de repuestos (precio cobrado menos costo real
  del lote).
- **Comisiones** de mecánicos: por trabajos completados en el periodo.
- «Órdenes facturadas» cuenta las facturas vigentes del mes; «recibidas», las órdenes creadas en el mes.
- Valor del inventario: al costo de cada lote y al precio de venta.

## Respaldos y actualizaciones

- Respaldo automático todos los días a las 7:00 p. m.: un .zip con la base de datos y el logo.
- Se copia a la carpeta de Google Drive y se guarda una copia en el portátil durante 30 días.
- Si el último respaldo tiene más de 3 días, el inicio se lo avisa al administrador.
- Los respaldos van sin cifrar (ver pendiente 15).
- Para restaurar se usa `restaurar.bat`, con el sistema sin uso (no lo pone en mantenimiento).
- Para actualizar se usa `actualizar.bat`: hace un respaldo, baja la versión nueva y aplica los cambios de la base.
- Importar desde Excel (administrador): primero se revisa y después se confirma. Máximo 5.000 filas por hoja. El
  stock entra como primer lote.

## Pendientes por decidir

Detalle en `docs/AUDITORIA_DECISIONES.md`.

- **6.** Cambiar el precio de compra en el producto no cambia el costo de los lotes.
- **7 y 14.** Los empleados editan precios, ven costos y ven Reportes completos.
- **8.** Cómo se encuentra el producto de destino en una transferencia.
- **9.** Los empleados pueden eliminar órdenes sin trabajos.
- **10.** «Mano de obra variable» y «total fijo por porcentaje» de los tipos de trabajo no hacen nada.
- **15.** ¿Cifrar los respaldos de Google Drive?
