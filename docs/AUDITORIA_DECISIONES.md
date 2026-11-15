# Auditoría v1.0.x: decisiones de negocio por revisar

Durante la revisión módulo por módulo aparecen comportamientos que no son errores sino reglas del negocio
(o lo parecen). No se cambian solos: se anotan aquí y al final se decide con el taller si se quedan o cambian.

Estado: **pendiente de revisar** salvo que diga otra cosa.

## Caja (módulo 2)

1. **Vender después de cerrar la caja está permitido.** La venta o el comprobante en efectivo se registra y sale
   un aviso para reabrir y volver a cerrar la caja. En cambio, una compra pagada con la caja de un día cerrado
   se rechaza. ¿Bloquear también las ventas en efectivo con la caja cerrada, o dejar el aviso?
   **Decidido (2026-11-14): bloquear el efectivo.** Con la caja de hoy cerrada no se registran ventas ni facturas en
   efectivo: se anotan en el cuaderno, la plata se queda en el cajón y se registran al otro día (la caja de mañana
   arranca con lo contado al cierre, así que cuadra sola). Nequi, transferencia y demás métodos sí se aceptan. El aviso
   sale apenas se abre «Nueva venta» o el detalle de la orden. Si hace falta registrarla el mismo día, se reabre la caja.
   Anular una venta de hoy con la caja cerrada sigue mostrando el aviso de reabrir (ver 2).
2. **Anular una venta de un día anterior no registra la devolución en la caja.** Solo avisa que, si se le
   devolvió la plata al cliente, se anote como gasto. El sistema no puede saber si fue una devolución real
   o la corrección de un error de digitación. ¿Agregar un tipo de salida «Devolución a cliente»?
   **Decidido (2026-11-14): preguntar al anular.** Al anular una venta o factura en efectivo de un día anterior, el
   diálogo pregunta si se devolvió la plata. «Sí» crea en la caja de hoy una salida «Devolución a cliente» ligada al
   documento (una sola por documento); si la caja de hoy está cerrada, no anula y pide reabrirla. «No» anula sin
   tocar la caja (error de digitación). Se eligió por trazabilidad: la devolución solo nace de una anulación, que es
   de administrador, así que nadie puede registrar una devolución suelta en la caja.
3. **Reabrir y volver a cerrar un día no corrige la base del día siguiente** si ese día ya se había empezado
   a usar. La base se puede editar a mano.
   **Decidido (2026-11-14): avisar.** Si la base de un día no coincide con lo que quedó al cerrar el día anterior, la
   caja de ambos días lo avisa hasta que cuadre, con un botón para usar la cifra nueva como base. Si ese día ya se
   cerró, no hay botón: hay que reabrirlo. Nunca se cambia sola, para no descuadrar un cierre ya hecho.

## Inventario (módulo 3)

4. **Precios por lote.** Cada compra guarda su propio precio de venta. Si llega mercancía nueva a otro precio,
   las unidades viejas se siguen vendiendo a su precio anterior hasta que se acaban (en la venta se ven los
   tramos). ¿Es así como cobra el taller, o todo debería pasar al precio nuevo al entrar la compra?
   **Decidido (2026-11-14): un solo precio por producto.** Al registrar una compra, todas las unidades del producto
   pasan a su precio de venta. Cada lote conserva su costo, así la ganancia sigue saliendo con lo que costó cada
   unidad. «Corregir lote» ya no cambia el precio de venta: se cambia en «Editar producto». Una migración dejó el
   inventario que ya había al precio actual de cada producto.
5. **Cambiar el precio de venta en «Editar producto» cambia el de todas las unidades en inventario**
   (corregido en la auditoría: antes se seguía cobrando el precio viejo). El costo de cada lote no cambia.
6. **Cambiar el precio de compra en «Editar producto» no cambia el costo de los lotes que ya hay.** Solo se usa
   para los ajustes positivos y las unidades que vuelven sin lote de origen.
7. **Los empleados pueden editar productos, incluidos los precios,** y ver los costos en Inventario. Solo
   borrar productos, ajustes y correcciones de lote son de administrador. Hoy da igual (solo ella usa el
   sistema), pero importa si algún día hay empleados con usuario.
   **Decidido (2026-11-14, junto con 9 y 14): cerrarlo ahora.** Productos (crear y editar), compras, trazabilidad
   por lotes y Reportes son de administrador. El empleado ve los productos con su precio de venta y stock, sin costo
   ni margen.
8. **Transferencias entre sucursales:** el producto de destino se busca por código (o por nombre y categoría si
   no tiene código). Si no existe, se crea uno nuevo con los mismos datos.

## Órdenes y trabajos (módulo 4)

9. **Los empleados pueden eliminar órdenes sin trabajos** (las que tienen trabajos solo se cancelan).
   **Decidido (2026-11-14): solo el administrador elimina órdenes.** El empleado las cancela.
10. **La opción «Mano de obra variable» de los tipos de trabajo no hace nada.** Se guarda, pero el valor de la mano
    de obra siempre se escribe en cada trabajo (salvo en los tipos de monto fijo, donde se usa el monto fijo). El
    campo «total fijo por porcentaje» tampoco se usa. ¿Quitar la opción o hacer que funcione (valor fijo no editable)?
    **Decidido (2026-11-14): quitarlos.** En los datos la casilla solo repetía el tipo de cálculo (encendida en los de
    porcentaje, apagada en los de monto fijo) y el total fijo estaba vacío en todos. Se quitaron del formulario, la
    lista, la plantilla de Excel y la base. Un precio fijo se hace con «Monto fijo». Las plantillas viejas con esa
    columna se siguen leyendo; la columna se ignora.
11. **Corregir un comprobante ya emitido es difícil.** Al anularlo, la orden sigue «Completada»: se pueden eliminar
    trabajos, pero no agregar nuevos. Si hay que cambiar un trabajo, toca hacer una orden nueva. ¿Al anular el
    comprobante, la orden debería volver a «En progreso»?
    **Decidido (2026-11-14): sí, y la factura guarda sus líneas.** Al anular una factura, la orden vuelve a «En
    progreso» para corregir trabajos y facturarla de nuevo. Cada factura guarda al emitirse una copia de sus líneas
    (`line_items`) y se imprime siempre con ella, aunque la orden cambie después. Las facturas anteriores reciben su
    copia al anularse, cuando sus líneas todavía son las facturadas.
12. **Una orden se puede marcar «Completada» sin generar el comprobante.** Entonces ese cobro no aparece en la caja
    ni en «Cobrado hoy por método». ¿Obligar a que completar la orden sea generando el comprobante?
    **Decidido (2026-11-14): sí.** «Completada» ya no aparece en Editar; la orden se completa solo con «Generar
    factura». Las órdenes que ya estaban completadas sin factura siguen mostrando el botón para generarla.

## Reportes y documentos (módulo 7)

13. **«Ingresos» y «Cobrado» son cifras distintas a propósito, y pueden no coincidir.** «Ingresos de hoy/del mes»
    (dashboard y reportes) cuenta las ventas y las **órdenes entregadas** ese día. «Cobrado hoy por método» y la
    caja cuentan las ventas y los **comprobantes generados** ese día. Difieren cuando una orden se entrega un día y
    se cobra otro, cuando se completa sin comprobante (ver 12) o cuando el comprobante se anula y no se vuelve a
    generar (la orden sigue sumando como ingreso). ¿Está bien así, o «Ingresos» debería contar comprobantes?
    **Decidido (2026-11-14): contar facturas.** «Ingresos» (dashboard y reportes) suma las ventas y las **facturas
    vigentes por su fecha**, igual que «Cobrado» y la caja. Una factura anulada deja de sumar; las órdenes viejas
    completadas sin factura no suman hasta que se les genere. La ganancia se calcula sobre los trabajos de esas
    facturas. Las comisiones siguen por trabajo completado, porque es lo que se le debe al mecánico (igual que en
    la caja). En Reportes, «Órdenes entregadas» pasó a «Órdenes facturadas».
14. **Los empleados ven Reportes completos:** utilidad, comisiones de cada mecánico y valor del inventario al costo.
    Igual que el punto 7: hoy da igual, importa si algún día hay empleados con usuario.
    **Decidido (2026-11-14): Reportes solo de administrador.** En el inicio, el empleado tampoco ve «Comisiones del
    mes». En Caja sí ve las comisiones por pagar, porque las paga desde ahí.

## Respaldos y servidor (módulo 8)

15. **Los respaldos van a Google Drive sin cifrar.** El .zip trae toda la base: clientes, teléfonos, ventas y las
    contraseñas de los usuarios (estas sí cifradas). Quien entre a esa cuenta de Google puede leerlo. Lo mínimo es
    activar la verificación en dos pasos en esa cuenta. ¿Cifrar además el .zip con una clave (por ejemplo con 7-Zip),
    sabiendo que si se pierde esa clave el respaldo no sirve?

## Conocido, sin corregir (impacto bajo)

- **Reimprimir un comprobante anulado puede mostrar líneas que no suman su total.** Corregido con la decisión 11
  para todo lo que se emita o anule desde v1.1.0. Solo queda en los comprobantes anulados antes de esa versión si
  después se borró un trabajo de su orden.
- **Restaurar no pone la app en mantenimiento.** Si alguien registra algo mientras `restaurar.bat` carga la base, ese
  registro se pierde. Con un solo usuario no pasa: se restaura con el sistema sin uso.
