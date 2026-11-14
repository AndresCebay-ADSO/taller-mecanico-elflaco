/**
 * Plugin de Vite: empaqueta solo los iconos Lucide que la app usa.
 *
 * `import { icons } from 'lucide'` mete los ~1.600 iconos (≈500 KB de JS que el navegador
 * descarga y procesa en cada pantalla). Este plugin ofrece el módulo `virtual:lucide-icons`
 * con solo los iconos cuyo nombre aparece entre comillas en vistas, JS o PHP
 * (`data-lucide="plus"`, `icon="circle-check"`, `'chevron-down'`, `return 'circle-x';`...).
 *
 * Un nombre de más solo suma un icono al paquete; uno de menos deja el <i> vacío.
 * Por eso se busca en todo el código y no solo en `data-lucide`, y con `npm run dev`
 * se sirven todos (las vistas cambian sin reiniciar Vite).
 */
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { icons } from 'lucide';

const VIRTUAL_ID = 'virtual:lucide-icons';
const RESOLVED_ID = '\0' + VIRTUAL_ID;
const SCAN_DIRS = ['resources/views', 'resources/js', 'app'];
const QUOTED_NAME = /["']([a-z][a-z0-9]*(?:-[a-z0-9]+)*)["']/g;

// Misma conversión que usa createIcons para leer data-lucide.
const toPascalCase = (name) => name.replace(/(\w)(\w*)(_|-|\s*)/g, (_, first, rest) => first.toUpperCase() + rest.toLowerCase());

function* files(dir) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) yield* files(path);
        else if (/\.(php|js)$/.test(entry.name)) yield path;
    }
}

export function usedIconNames(root = process.cwd()) {
    const used = new Set();
    for (const dir of SCAN_DIRS) {
        for (const file of files(join(root, dir))) {
            for (const [, name] of readFileSync(file, 'utf8').matchAll(QUOTED_NAME)) {
                const pascal = toPascalCase(name);
                if (pascal in icons) used.add(pascal);
            }
        }
    }
    return [...used].sort();
}

export default function lucideUsedIcons() {
    let serving = false;

    return {
        name: 'lucide-used-icons',
        configResolved(config) {
            serving = config.command === 'serve';
        },
        resolveId(id) {
            return id === VIRTUAL_ID ? RESOLVED_ID : null;
        },
        load(id) {
            if (id !== RESOLVED_ID) return null;
            if (serving) return "export { icons as default } from 'lucide';";

            const names = usedIconNames();
            this.info?.(`${names.length} iconos Lucide incluidos`);
            return `import { ${names.join(', ')} } from 'lucide';\nexport default { ${names.join(', ')} };`;
        },
    };
}
