# Reglas de negocio de MotoTaller El Flaco

Cómo se comporta el sistema y por qué, módulo por módulo. Sirve para explicarle el sistema al taller y para
recordar qué es intencional. Las marcadas **(decidido, N)** salieron de la revisión de `docs/AUDITORIA_DECISIONES.md`.

Actualizado: 2026-11-15 (v1.2.0 y la revisión externa previa a la instalación).

## Usuarios y permisos

- Hay dos tipos de usuario: **administrador** y **empleado**. Hoy solo la dueña usa el sistema, como administradora.
- Solo el administrador puede hacer lo que cambia plata, inventario o permisos:
  - anular ventas y facturas;
  - **crear y editar productos (precios incluidos), registrar compras, ver costos, márgenes y la trazabilidad por
    lotes (decidido, 7);**
  - **eliminar órdenes (decidido, 9);**
  - **ver Reportes y las comisiones del mes en el inicio (decidido, 14);**
  - hacer ajustes de inventario y corregir lotes;
  - desactivar o restaurar productos, desactivar proveedores, borrar mecánicos;
  - borrar movimientos de caja y reabrir una caja cerrada;
  - manejar usuarios, tipos de trabajo, configuración, sucursales y transferencias;
  - importar datos desde Excel.
- El empleado puede vender, recibir motos (órdenes), crear y terminar trabajos, facturar, usar la caja (incluido
  pagar comisiones) y ver los productos con su precio de venta y stock. No ve costos ni ganancias: cobra al precio que
  pone la dueña.
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
- Eliminar un trabajo devuelve sus repuestos. No se puede si la orden tiene una factura vigente. Si la comisión de
  ese trabajo ya se le pagó al mecánico, la confirmación lo avisa: su saldo queda negativo y se descuenta de lo
  próximo que gane.
- Una orden solo se puede eliminar si no tiene trabajos **ni facturas** (aunque estén anuladas), y solo el
  administrador; si no, se cancela.
- «Trabajo individual» crea por debajo una orden con ese único trabajo.
- Comisión del mecánico, según el tipo de trabajo:
  - **por porcentaje:** la mano de obra se escribe en cada trabajo y se reparte entre mecánico y taller con los
    porcentajes del tipo;
  - **monto fijo:** el trabajo vale siempre lo mismo (no se escribe) y el mecánico y el taller reciben los montos
    fijos del tipo. Es la forma de tener servicios con precio fijo **(decidido, 10)**.
- El mecánico gana su comisión cuando su trabajo queda completado.

## Facturas (comprobantes de servicio)

- Se numeran FAC-000001, FAC-000002… sin huecos. Una factura anulada conserva su número.
- Valor = mano de obra + repuestos de los trabajos. Guarda cómo pagó el cliente.
- **Con la caja de hoy cerrada no se generan facturas en efectivo (decidido, 1).** Mismo manejo que las ventas.
- **Anular una factura (administrador) la deja guardada como «Anulada» y la orden vuelve a «En progreso»
  (decidido, 11).** Ahí se pueden agregar, eliminar o terminar trabajos y volver a facturar con el siguiente número.
  Si era en efectivo de un día anterior, pregunta por la devolución igual que una venta **(decidido, 2)**.
- **Una factura no cambia después de emitida (decidido, 11).** Guarda una copia de sus líneas (trabajos, mecánicos y
  repuestos con sus precios) y de los datos del cliente y la moto, y siempre se imprime con ella, aunque la orden se
  corrija después.
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
- No se registra caja de días que no han llegado, ni de días anteriores al primero en que se usó la caja.
- **Días sin cerrar:** se avisan los días abiertos en Caja y también los que tuvieron ventas o facturas en efectivo
  sin que nadie abriera la Caja. Hay que cerrarlos para que su efectivo pase a la base del día siguiente.
- Un día cerrado se ve con las cifras de su cierre. Si después se anuló algo de ese día, el aviso «después del cierre
  cambió» lo dice.
- Una venta o factura anulada **con devolución** sigue contando en la caja del día en que se pagó (la plata sí entró
  ese día); la devolución sale de la caja del día en que se devolvió.
- Si se corrige el costo o la cantidad de una compra pagada con la caja, la salida de caja se ajusta sola. Si ese día
  ya se cerró, pide reabrirlo.
- **Comisiones por pagar:** lo ganado por cada mecánico en trabajos completados desde el primer día que se usó la
  caja, menos lo que se le ha pagado desde la caja. A un mecánico desactivado que todavía tiene saldo también se le
  puede pagar.
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
- **El costo de referencia del producto es el costo de la última compra y cambia solo con las compras
  (decidido, 6).** Se pone al crear el producto; en «Editar producto» solo se ve. Si la última compra se registró
  con un costo equivocado, «Corregir lote» corrige el lote y también el costo de referencia. Se usa en los ajustes
  que suman unidades y en las unidades que vuelven sin lote de origen.
- Una compra exige un proveedor activo y asociado al producto.
- **Ajustes** (administrador), con motivo ajuste, daño o pérdida:
  - si suman, crean un lote al costo del producto;
  - si restan, sacan de los lotes más antiguos.
- **Corregir lote** (administrador): solo lotes de compra. La cantidad solo se corrige si no se ha vendido nada de
  ese lote. El precio de venta no se cambia ahí, sino en «Editar producto».
- Stock bajo: cuando el stock llega al mínimo o menos, sale en el inicio y se puede filtrar en Productos.
- Cada día, el respaldo revisa que el stock de cada producto coincida con sus lotes; si no, lo anota en el registro
  del respaldo y `taller:verificar` lo muestra.
- Un producto solo se desactiva con stock en cero (primero se saca con un ajuste). Se puede restaurar.
- **Transferencias** (administrador): Pendiente → En tránsito → Completada o Cancelada. El stock se mueve al
  completarla. En la sucursal de destino el producto se busca por código, o por nombre y categoría; si no existe, se
  crea con los mismos datos. Las unidades llegan con su costo y se venden al precio del producto en el destino
  **(decidido, 8: se deja así)**.

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
- Si el último respaldo tiene más de 3 días, el inicio se lo avisa al administrador. Lo revisa en el portátil **y en
  Google Drive**: si Drive deja de recibirlos (cerrado, sin internet), también avisa, porque si el portátil se pierde
  solo quedan los de la nube.
- Los respaldos van sin cifrar **(decidido, 15)**: una clave perdida dejaría inservibles todos los respaldos. Lo que
  los protege es la cuenta de Google donde quedan.
- Para restaurar se usa `restaurar.bat`: guarda primero un respaldo del estado actual, pone el sistema en
  mantenimiento, reemplaza toda la base por la del respaldo y lo vuelve a abrir. Para practicar sin tocar los datos
  reales hay un modo práctica (guía de instalación, §6).
- **Sesión:** dura 12 horas sin uso. Si vence con un formulario abierto, al guardar vuelve al formulario con lo escrito
  y un aviso para guardar de nuevo.
- Mientras el NIT, teléfono, correo o dirección del taller sigan siendo los de ejemplo, el inicio lo avisa: salen en
  todos los comprobantes.
- Para actualizar se usa `actualizar.bat`: hace un respaldo, baja la versión nueva y aplica los cambios de la base.
- Importar desde Excel (administrador): primero se revisa y después se confirma. Máximo 5.000 filas por hoja. El
  stock entra como primer lote.

## Decisiones de la revisión

Las 15 decisiones de la auditoría quedaron tomadas el 2026-11-14. El detalle y el porqué de cada una está en
`docs/AUDITORIA_DECISIONES.md`.
