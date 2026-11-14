# Auditoría v1.0.x: decisiones de negocio por revisar

Durante la revisión módulo por módulo aparecen comportamientos que no son errores sino reglas del negocio
(o lo parecen). No se cambian solos: se anotan aquí y al final se decide con el taller si se quedan o cambian.

Estado: **pendiente de revisar** salvo que diga otra cosa.

## Caja (módulo 2)

1. **Vender después de cerrar la caja está permitido.** La venta o el comprobante en efectivo se registra y sale
   un aviso para reabrir y volver a cerrar la caja. En cambio, una compra pagada con la caja de un día cerrado
   se rechaza. ¿Bloquear también las ventas en efectivo con la caja cerrada, o dejar el aviso?
2. **Anular una venta de un día anterior no registra la devolución en la caja.** Solo avisa que, si se le
   devolvió la plata al cliente, se anote como gasto. El sistema no puede saber si fue una devolución real
   o la corrección de un error de digitación. ¿Agregar un tipo de salida «Devolución a cliente»?
3. **Reabrir y volver a cerrar un día no corrige la base del día siguiente** si ese día ya se había empezado
   a usar. La base se puede editar a mano.

## Inventario (módulo 3)

4. **Precios por lote.** Cada compra guarda su propio precio de venta. Si llega mercancía nueva a otro precio,
   las unidades viejas se siguen vendiendo a su precio anterior hasta que se acaban (en la venta se ven los
   tramos). ¿Es así como cobra el taller, o todo debería pasar al precio nuevo al entrar la compra?
5. **Cambiar el precio de venta en «Editar producto» cambia el de todas las unidades en inventario**
   (corregido en la auditoría: antes se seguía cobrando el precio viejo). El costo de cada lote no cambia.
6. **Cambiar el precio de compra en «Editar producto» no cambia el costo de los lotes que ya hay.** Solo se usa
   para los ajustes positivos y las unidades que vuelven sin lote de origen.
7. **Los empleados pueden editar productos, incluidos los precios,** y ver los costos en Inventario. Solo
   borrar productos, ajustes y correcciones de lote son de administrador. Hoy da igual (solo ella usa el
   sistema), pero importa si algún día hay empleados con usuario.
8. **Transferencias entre sucursales:** el producto de destino se busca por código (o por nombre y categoría si
   no tiene código). Si no existe, se crea uno nuevo con los mismos datos.

## Órdenes y trabajos (módulo 4)

9. **Los empleados pueden eliminar órdenes sin trabajos** (las que tienen trabajos solo se cancelan).
10. **La opción «Mano de obra variable» de los tipos de trabajo no hace nada.** Se guarda, pero el valor de la mano
    de obra siempre se escribe en cada trabajo (salvo en los tipos de monto fijo, donde se usa el monto fijo). El
    campo «total fijo por porcentaje» tampoco se usa. ¿Quitar la opción o hacer que funcione (valor fijo no editable)?
11. **Corregir un comprobante ya emitido es difícil.** Al anularlo, la orden sigue «Completada»: se pueden eliminar
    trabajos, pero no agregar nuevos. Si hay que cambiar un trabajo, toca hacer una orden nueva. ¿Al anular el
    comprobante, la orden debería volver a «En progreso»?
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

## Respaldos y servidor (módulo 8)

15. **Los respaldos van a Google Drive sin cifrar.** El .zip trae toda la base: clientes, teléfonos, ventas y las
    contraseñas de los usuarios (estas sí cifradas). Quien entre a esa cuenta de Google puede leerlo. Lo mínimo es
    activar la verificación en dos pasos en esa cuenta. ¿Cifrar además el .zip con una clave (por ejemplo con 7-Zip),
    sabiendo que si se pierde esa clave el respaldo no sirve?

## Conocido, sin corregir (impacto bajo)

- **Reimprimir un comprobante anulado puede mostrar líneas que no suman su total.** El comprobante guarda el total,
  pero las líneas se leen de la orden en el momento de imprimir. Si después de anularlo se borra un trabajo de la
  orden, la reimpresión del anulado muestra las líneas nuevas con el total viejo. Los comprobantes vigentes no se
  afectan: con comprobante activo no se pueden borrar trabajos.
- **Restaurar no pone la app en mantenimiento.** Si alguien registra algo mientras `restaurar.bat` carga la base, ese
  registro se pierde. Con un solo usuario no pasa: se restaura con el sistema sin uso.
