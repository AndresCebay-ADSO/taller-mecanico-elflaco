{{--
    Atributos del formulario «Anular» de una venta o factura (decisión 2). Va dentro de la etiqueta <form>.
    En efectivo de un día anterior, el diálogo pregunta si se devolvió la plata; la respuesta llega en «refunded».
    Uso: @include('cash.partials.refund-question', ['document' => $sale, 'amount' => $sale->total_amount])
--}}
@if($document->asksForRefund())
data-confirm-choice="refunded"
data-confirm-choice-question="Se pagó en efectivo un día anterior. ¿Le devolviste la plata al cliente?"
data-confirm-choice-yes="Sí: sale de la caja de hoy como «Devolución a cliente» ({{ money($amount) }})."
data-confirm-choice-no="No: solo corrijo un error. La caja no cambia."
@endif
