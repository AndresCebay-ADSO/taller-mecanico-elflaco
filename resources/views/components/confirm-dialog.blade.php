{{--
    Diálogo de confirmación de la app (DESIGN.md §8). Uno solo, en el layout.
    Un formulario lo usa con atributos, sin JavaScript propio:

    <form data-confirm="¿Anular la venta #S0042?"
          data-confirm-description="El stock vuelve al inventario."
          data-confirm-label="Anular venta"
          data-confirm-destructive>

    Para pedir además una respuesta sí/no (llega en el campo «refunded» como 1 o 0):
          data-confirm-choice="refunded"
          data-confirm-choice-question="¿Le devolviste la plata?"
          data-confirm-choice-yes="Sí…" data-confirm-choice-no="No…"
--}}
<div x-data="confirmDialog"
     x-show="open"
     x-on:confirm-dialog.window="ask($event.detail)"
     x-on:keydown.escape.window="open && cancel()"
     class="ds fixed inset-0 z-[60] flex items-center justify-center p-4"
     role="alertdialog"
     aria-modal="true"
     aria-labelledby="confirm-dialog-title"
     aria-describedby="confirm-dialog-description"
     x-cloak>
    <div class="absolute inset-0 bg-overlay" x-show="open" x-transition.opacity x-on:click="cancel()"></div>

    <div class="relative w-full max-w-md rounded-lg border border-border bg-card p-6 text-foreground shadow-lg"
         x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">
        <h2 id="confirm-dialog-title" class="text-base font-semibold" x-text="title"></h2>
        <p id="confirm-dialog-description" class="mt-2 text-sm text-muted-foreground" x-show="description" x-text="description"></p>

        <template x-if="choice">
            <fieldset class="mt-4">
                <legend class="text-sm font-medium" x-text="choice.question"></legend>
                <div class="mt-2 space-y-2">
                    <template x-for="option in [{ value: '1', label: choice.yes }, { value: '0', label: choice.no }]" :key="option.value">
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3 text-sm transition-colors"
                               :class="choiceValue === option.value ? 'border-ring bg-primary/5' : 'border-border hover:bg-muted/50'">
                            <input type="radio" name="confirm-dialog-choice" :value="option.value" x-model="choiceValue"
                                   class="mt-0.5 size-4 cursor-pointer border-input text-primary focus:ring-2 focus:ring-ring/30">
                            <span x-text="option.label"></span>
                        </label>
                    </template>
                </div>
            </fieldset>
        </template>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button variant="secondary" x-ref="cancelButton" x-on:click="cancel()">Cancelar</x-button>
            <button type="button"
                    x-on:click="accept()"
                    :disabled="needsChoice"
                    x-text="confirmLabel"
                    :class="destructive ? 'bg-destructive text-destructive-foreground hover:bg-destructive-hover' : 'bg-primary text-primary-foreground hover:bg-primary-hover'"
                    class="inline-flex h-10 cursor-pointer items-center justify-center disabled:cursor-not-allowed disabled:opacity-50 rounded-md px-4 text-sm font-medium shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/40"></button>
        </div>
    </div>
</div>
