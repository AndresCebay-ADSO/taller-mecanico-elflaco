import './bootstrap';

import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;

/**
 * Iconos Lucide empaquetados localmente (la app no depende de internet para mostrarlos).
 * `inTemplates` también convierte los iconos dentro de <template>, así las filas que
 * Alpine agrega después (x-for / x-if) ya traen su SVG.
 */
window.lucide = {
    createIcons: (options = {}) => createIcons({ icons, inTemplates: true, ...options }),
};

/**
 * Formato de moneda colombiana: $1.250.000
 */
const formatCurrency = (value) => '$' + Math.round(Number(value) || 0).toLocaleString('es-CO');

/**
 * Valor de `quantity` unidades consumiendo los lotes en orden FIFO, igual que el servidor.
 * `tiers` = [{ qty, price }] del lote más antiguo al más nuevo.
 */
const fifoTotal = (tiers, quantity) => {
    let remaining = Math.max(0, parseInt(quantity, 10) || 0);
    let total = 0;

    for (const tier of tiers) {
        if (remaining <= 0) break;
        const take = Math.min(tier.qty, remaining);
        total += take * tier.price;
        remaining -= take;
    }

    // Sin stock suficiente: el servidor rechazará la operación; se estima con el último precio.
    if (remaining > 0 && tiers.length > 0) {
        total += remaining * tiers[tiers.length - 1].price;
    }

    return total;
};

window.formatCurrency = formatCurrency;

/**
 * Formulario de venta de mostrador con total en vivo.
 */
Alpine.data('saleForm', ({ products, items }) => ({
    products,
    items: items.length ? items : [{ id: '', quantity: 1 }],

    product(id) {
        return this.products.find((p) => p.id == id) ?? null;
    },
    addItem() {
        this.items.push({ id: '', quantity: 1 });
    },
    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        }
    },
    lineTotal(item) {
        const product = this.product(item.id);
        return product ? fifoTotal(product.tiers, item.quantity) : 0;
    },
    exceedsStock(item) {
        const product = this.product(item.id);
        return product !== null && (parseInt(item.quantity, 10) || 0) > product.stock;
    },
    get total() {
        return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },
    money: formatCurrency,
}));

/**
 * Formulario de trabajo de taller (en una orden o individual) con resumen y reparto en vivo.
 */
Alpine.data('jobForm', ({ products, jobTypes, old = {} }) => ({
    products,
    jobTypes,
    jobTypeId: old.job_type_id ?? '',
    laborCost: old.labor_cost ?? 0,
    selectedProducts: (old.products ?? [])
        .map((item) => {
            const product = products.find((p) => p.id == item.id);
            return product ? { id: product.id, name: product.name, quantity: parseInt(item.quantity, 10) || 1 } : null;
        })
        .filter(Boolean),

    // true mientras la mano de obra la puso un tipo de monto fijo (se limpia al pasar a uno por porcentaje).
    laborFromFixedType: false,

    init() {
        if (this.jobTypeId) {
            this.applyJobType(false);
        }
        this.$watch('jobTypeId', () => this.applyJobType(true));
    },
    get jobType() {
        return this.jobTypes.find((t) => t.id == this.jobTypeId) ?? null;
    },
    get isFixed() {
        return this.jobType?.calculation_type === 'fixed';
    },
    get allowsProducts() {
        return this.jobType ? this.jobType.allow_products : true;
    },
    applyJobType(changedByUser) {
        const type = this.jobType;
        if (!type) return;

        if (type.calculation_type === 'fixed') {
            this.laborCost = (Number(type.fixed_mechanic_amount) || 0) + (Number(type.fixed_workshop_amount) || 0);
        } else if (changedByUser && this.laborFromFixedType) {
            this.laborCost = 0;
        }
        this.laborFromFixedType = type.calculation_type === 'fixed';

        if (!type.allow_products) {
            this.selectedProducts = [];
        }
    },
    addProduct(id) {
        const product = this.products.find((p) => p.id == id);
        if (!product) return;

        const existing = this.selectedProducts.find((p) => p.id == id);
        if (existing) {
            existing.quantity = Math.min(existing.quantity + 1, product.stock);
        } else {
            this.selectedProducts.push({ id: product.id, name: product.name, quantity: 1 });
        }
    },
    removeProduct(index) {
        this.selectedProducts.splice(index, 1);
    },
    lineTotal(item) {
        const product = this.products.find((p) => p.id == item.id);
        return product ? fifoTotal(product.tiers, item.quantity) : 0;
    },
    stockOf(item) {
        return this.products.find((p) => p.id == item.id)?.stock ?? 0;
    },
    get labor() {
        return Number(this.laborCost) || 0;
    },
    get productsTotal() {
        return this.selectedProducts.reduce((sum, item) => sum + this.lineTotal(item), 0);
    },
    get grandTotal() {
        return this.labor + this.productsTotal;
    },
    get mechanicShare() {
        const type = this.jobType;
        if (!type) return 0;
        return type.calculation_type === 'fixed'
            ? Number(type.fixed_mechanic_amount) || 0
            : this.labor * ((Number(type.mechanic_percentage) || 0) / 100);
    },
    get mechanicLabel() {
        const type = this.jobType;
        if (!type) return 'Mecánico';
        return type.calculation_type === 'fixed'
            ? 'Mecánico (monto fijo)'
            : `Mecánico (${Number(type.mechanic_percentage) || 0}%)`;
    },
    get workshopShare() {
        return this.grandTotal - this.mechanicShare;
    },
    money: formatCurrency,
}));

/**
 * Diálogo de confirmación de la app (<x-confirm-dialog>), en lugar de window.confirm().
 * Un <form data-confirm="…"> se detiene al enviarse, pregunta y, si se acepta, se reenvía.
 */
Alpine.data('confirmDialog', () => ({
    open: false,
    title: '',
    description: '',
    confirmLabel: 'Confirmar',
    destructive: false,
    form: null,
    submitter: null,

    ask({ form, submitter }) {
        this.form = form;
        this.submitter = submitter;
        this.title = form.dataset.confirm;
        this.description = form.dataset.confirmDescription ?? '';
        this.confirmLabel = form.dataset.confirmLabel ?? 'Confirmar';
        this.destructive = form.hasAttribute('data-confirm-destructive');
        this.open = true;
        // Foco en «Cancelar»: Enter por accidente no anula nada.
        this.$nextTick(() => this.$refs.cancelButton?.focus());
    },
    cancel() {
        this.open = false;
        this.form = null;
    },
    accept() {
        const form = this.form;
        const submitter = this.submitter;
        this.open = false;
        this.form = null;
        if (!form) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
    },
}));

// Fase de captura: corre antes que el formateo de dinero y el bloqueo de doble envío.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

    if (form.dataset.confirmed === '1') {
        delete form.dataset.confirmed;
        return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();
    window.dispatchEvent(new CustomEvent('confirm-dialog', { detail: { form, submitter: e.submitter ?? null } }));
}, true);

window.lucide.createIcons();

Alpine.start();

/**
 * Global Money Input Formatter
 * Formats numbers with dots as thousand separators (Spanish/Colombian format: 1.250.000)
 */
document.addEventListener('DOMContentLoaded', () => {
    const formatMoney = (value) => {
        if (!value) return '';
        // Remove all non-digits
        const plainNumber = value.toString().replace(/\D/g, '');
        // Format with dots
        return plainNumber.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    };

    const cleanMoney = (value) => {
        return value.toString().replace(/\./g, '');
    };

    // Initial format on load
    document.querySelectorAll('.money-input').forEach(input => {
        input.value = formatMoney(input.value);
    });

    // Real-time formatting while typing
    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('money-input')) {
            // Save cursor position
            let cursorPosition = e.target.selectionStart;
            let originalLength = e.target.value.length;

            e.target.value = formatMoney(e.target.value);

            // Adjust cursor position
            let newLength = e.target.value.length;
            cursorPosition = cursorPosition + (newLength - originalLength);
            e.target.setSelectionRange(cursorPosition, cursorPosition);
        }
    });

    // Clean dots before form submission
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;

        const moneyInputs = e.target.querySelectorAll('.money-input');
        moneyInputs.forEach(input => {
            input.value = cleanMoney(input.value);
        });
    });

    /**
     * Prevent multiple form submissions (Con delegación de eventos para formularios dinámicos).
     * Si el envío se canceló (p. ej. el usuario respondió "Cancelar" en un confirm), no se bloquea nada.
     */
    document.addEventListener("submit", function (e) {
        if (e.defaultPrevented || !e.target || e.target.tagName !== 'FORM') return;

        e.target.querySelectorAll("button[type=submit]").forEach(btn => {
            // Usar un pequeño timeout permite que el envío nativo procese la petición antes de deshabilitar el botón
            setTimeout(() => {
                btn.dataset.originalHtml ??= btn.innerHTML;
                btn.disabled = true;
                if (btn.textContent.trim() !== '') {
                    btn.innerText = "Guardando...";
                }
            }, 10);
        });
    });

    // Al volver con el botón "Atrás" el navegador restaura la página tal cual: reactivar los botones.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;

        document.querySelectorAll('button[data-original-html]').forEach(btn => {
            btn.innerHTML = btn.dataset.originalHtml;
            btn.disabled = false;
            delete btn.dataset.originalHtml;
        });
    });
});
