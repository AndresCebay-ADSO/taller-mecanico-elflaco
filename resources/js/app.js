import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
        const moneyInputs = e.target.querySelectorAll('.money-input');
        moneyInputs.forEach(input => {
            input.value = cleanMoney(input.value);
        });
    });

    /**
     * Prevent multiple form submissions (Con delegación de eventos para formularios dinámicos)
     */
    document.addEventListener("submit", function (e) {
        if (e.target && e.target.tagName === 'FORM') {
            const form = e.target;
            form.querySelectorAll("button[type=submit]").forEach(btn => {
                // Usar un pequeño timeout permite que el envío nativo o Alpine procese la petición antes de deshabilitar el botón
                setTimeout(() => {
                    btn.disabled = true;
                    btn.innerText = "Guardando...";
                }, 10);
            });
        }
    });
});
