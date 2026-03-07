# TailAdmin - Color & Style Guide - Quick Reference

## 🎨 Paleta de Colores - Valores Hexadecimales

### PRIMARY BRAND (Azul Electrónico)
```
#f2f7ff - brand-25  (casi blanco azulado)
#ecf3ff - brand-50
#dde9ff - brand-100
#c2d6ff - brand-200
#9cb9ff - brand-300
#7592ff - brand-400
#465fff - brand-500  ← PRINCIPAL
#3641f5 - brand-600  (hover/active)
#2a31d8 - brand-700
#252dae - brand-800
#262e89 - brand-900  (oscuro)
#161950 - brand-950  (muy oscuro)
```

**Usos:**
- Botones primarios
- Links activos
- Badges destacados
- Accents principales

---

### GRAY (Neutro - Escala Completa)
```
#fcfcfd - gray-25   (prácticamente blanco)
#f9fafb - gray-50   ← Fondo claro (body light)
#f2f4f7 - gray-100
#e4e7ec - gray-200  (borders)
#d0d5dd - gray-300
#98a2b3 - gray-400
#667085 - gray-500  (labels, ayuda)
#475467 - gray-600
#344054 - gray-700
#1d2939 - gray-800  (textos oscuros)
#101828 - gray-900  ← Texto principal
#0c111d - gray-950  (muy oscuro)
#1a2231 - gray-dark (alternativo oscuro)
```

**Usos por nivel:**
- **25-50:** Fondos muy claros
- **100-200:** Bordes, dividers
- **300-400:** Hover states sutiles
- **500-600:** Textos secundarios
- **700-900:** Textos primarios/oscuros

---

### SUCCESS (Verde)
```
#f6fef9 - success-25  (prácticamente blanco)
#ecfdf3 - success-50
#d1fadf - success-100
#a6f4c5 - success-200
#6ce9a6 - success-300
#32d583 - success-400
#12b76a - success-500  ← PRINCIPAL
#039855 - success-600  (hover)
#027a48 - success-700
#05603a - success-800
#054f31 - success-900
#053321 - success-950
```

**Usos:**
- Estados OK/éxito
- Indicators positivos
- Badges verdes
- Notificaciones exitosas

---

### ERROR (Rojo)
```
#fffbfa - error-25
#fef3f2 - error-50
#fee4e2 - error-100
#fecdca - error-200
#fda29b - error-300
#f97066 - error-400
#f04438 - error-500  ← PRINCIPAL
#d92d20 - error-600  (hover)
#b42318 - error-700
#912018 - error-800
#7a271a - error-900
#55160c - error-950
```

**Usos:**
- Estados de error
- Alertas críticas
- Validaciones fallidas
- Botones destructivos

---

### WARNING (Ámbar/Naranja)
```
#fffcf5 - warning-25
#fffaeb - warning-50
#fef0c7 - warning-100
#fedf89 - warning-200
#fec84b - warning-300
#fdb022 - warning-400
#f79009 - warning-500  ← PRINCIPAL
#dc6803 - warning-600  (hover)
#b54708 - warning-700
#93370d - warning-800
#7a2e0e - warning-900
#4e1d09 - warning-950
```

**Usos:**
- Advertencias
- Estados pendientes
- Información importante
- Badges de atención

---

### BLUE LIGHT (Cian/Turquesa)
```
#f5fbff - blue-light-25
#f0f9ff - blue-light-50
#e0f2fe - blue-light-100
#b9e6fe - blue-light-200
#7cd4fd - blue-light-300
#36bffa - blue-light-400
#0ba5ec - blue-light-500  ← PRINCIPAL
#0086c9 - blue-light-600  (hover)
#026aa2 - blue-light-700
#065986 - blue-light-800
#0b4a6f - blue-light-900
#062c41 - blue-light-950
```

---

### ORANGE (Naranja)
```
#fffaf5 - orange-25
#fff6ed - orange-50
#ffead5 - orange-100
#fddcab - orange-200
#feb273 - orange-300
#fd853a - orange-400
#fb6514 - orange-500  ← PRINCIPAL
#ec4a0a - orange-600  (hover)
#c4320a - orange-700
#9c2a10 - orange-800
#7e2410 - orange-900
#511c10 - orange-950
```

---

### ACCENT COLORS
```
#ee46bc - pink-500   (Acento rosado)
#7a5af8 - purple-500 (Acento púrpura)
```

---

## 📏 Tamaños de Fuente - Escala Tipográfica

| Tamaño | px | Línea | Uso | Clase Tailwind |
|--------|----|----|-----|---|
| Title 2XL | 72 | 90 | Hero, portada | text-8xl |
| Title XL | 60 | 72 | Portada grande | text-7xl |
| Title LG | 48 | 60 | Título página | text-6xl |
| Title MD | 36 | 44 | Título sección | text-5xl |
| Title SM | 30 | 38 | Subtítulo grande | text-4xl |
| Theme XL | 20 | 30 | Destacado | text-xl |
| **Theme SM** | **14** | **20** | Normal ← DEFAULT | text-sm |
| Theme XS | 12 | 18 | Helper/label | text-xs |

**Ejemplos de aplicación:**
```blade
<!-- Página principal -->
<h1 class="text-6xl font-bold">Título Página</h1>

<!-- Tarjeta -->
<h2 class="text-xl font-semibold">Título Card</h2>

<!-- Información auxiliar -->
<p class="text-sm text-gray-500">Texto descriptivo</p>

<!-- Badges/etiquetas -->
<span class="text-xs font-medium">BADGE</span>
```

---

## 🎯 Pesos de Fuente (Font Weight)

| Peso | Valor | Uso |
|------|-------|-----|
| Thin | 100 | Rara vez usado |
| Extra Light | 200 | Rara vez usado |
| Light | 300 | Rara vez usado |
| **Normal** | **400** | Textos base, párrafos |
| **Medium** | **500** | Etiquetas, textos destacados |
| **Semibold** | **600** | Títulos, encabezados |
| **Bold** | **700** | Títulos principales |
| Extra Bold | 800 | CTA buttons, énfasis |

---

## 🔷 Sombras Personalizadas

```css
/* Extra Small - Bordes sutiles */
--shadow-theme-xs: 0px 1px 2px 0px rgba(16, 24, 40, 0.05);

/* Small - Elementos flotantes */
--shadow-theme-sm: 0px 1px 3px 0px rgba(16, 24, 40, 0.1), 
                   0px 1px 2px 0px rgba(16, 24, 40, 0.06);

/* Medium - Cards elevadas */
--shadow-theme-md: 0px 4px 8px -2px rgba(16, 24, 40, 0.1), 
                   0px 2px 4px -2px rgba(16, 24, 40, 0.06);

/* Large - Dropdowns, modales */
--shadow-theme-lg: 0px 12px 16px -4px rgba(16, 24, 40, 0.08),
                   0px 4px 6px -2px rgba(16, 24, 40, 0.03);

/* Extra Large - Overlays principales */
--shadow-theme-xl: 0px 20px 24px -4px rgba(16, 24, 40, 0.08),
                   0px 8px 8px -4px rgba(16, 24, 40, 0.03);

/* Focus Ring - Para inputs con focus */
--shadow-focus-ring: 0px 0px 0px 4px rgba(70, 95, 255, 0.12);
```

**Aplicación:**
```blade
<!-- Card con sombra md -->
<div class="shadow-theme-md">Card Content</div>

<!-- Dropdown con sombra lg -->
<ul class="shadow-theme-lg">Menu Items</ul>

<!-- Elemento con focus -->
<input class="shadow-focus-ring" type="text" />
```

---

## 📐 Bordes y Espacios

### Border Radius
```
rounded-none    = 0px
rounded-sm      = 2px    (inputs pequeños)
rounded         = 4px    (elementos sutiles)
rounded-lg      = 8px    (botones, inputs)
rounded-2xl     = 16px   ← CARDS (estándar)
rounded-3xl     = 24px   (elementos grandes)
rounded-full    = 9999px (badges, avatares)
```

### Padding/Margin Escala
```
p-0   = 0px
p-1   = 4px
p-2   = 8px
p-3   = 12px
p-4   = 16px (padding estándar)
p-5   = 20px (card headers)
p-6   = 24px (card bodies)
```

---

## 🎪 Componentes - Estilos Base

### Card (Tarjeta)
```blade
class="rounded-2xl border border-gray-200 bg-white
       dark:border-gray-800 dark:bg-white/[0.03]"
```

**Header:** `px-6 py-5`  
**Body:** `p-4 border-t border-gray-100 dark:border-gray-800 sm:p-6`

### Button Primary
```blade
class="px-4 py-2.5 rounded-lg bg-brand-500 text-white
       font-medium hover:bg-brand-600 active:bg-brand-700
       dark:bg-brand-500 dark:hover:bg-brand-600"
```

### Button Secondary
```blade
class="px-4 py-2.5 rounded-lg bg-white border border-gray-200
       text-gray-700 font-medium hover:bg-gray-50
       dark:bg-white/5 dark:border-gray-700 dark:text-gray-300"
```

### Input Field
```blade
class="w-full px-3 py-2.5 rounded-lg border border-gray-300
       bg-white text-gray-900 placeholder-gray-500
       focus:border-brand-500 focus:ring-4 focus:ring-brand-50
       dark:border-gray-700 dark:bg-gray-900 dark:text-white"
```

### Badge Success
```blade
class="inline-flex px-2.5 py-0.5 rounded-full text-xs
       font-medium bg-success-50 text-success-700
       dark:bg-success-500/20 dark:text-success-400"
```

### Badge Error
```blade
class="inline-flex px-2.5 py-0.5 rounded-full text-xs
       font-medium bg-error-50 text-error-700
       dark:bg-error-500/20 dark:text-error-400"
```

### Menu Item Active
```blade
class="flex items-center gap-3 px-3 py-2 rounded-lg
       bg-brand-50 text-brand-500 font-medium
       dark:bg-brand-500/[0.12] dark:text-brand-400"
```

### Menu Item Inactive
```blade
class="flex items-center gap-3 px-3 py-2 rounded-lg
       text-gray-700 hover:bg-gray-100 font-medium
       dark:text-gray-300 dark:hover:bg-white/5"
```

---

## 🔄 Estados y Variantes

### Hover States
```css
Primary button:  #465fff → #3641f5 (más oscuro)
Secondary:       #f9fafb → #f2f4f7 (más gris)
Menu items:      transparent → #f2f4f7 (fondo)
Links:           #465fff → #3641f5
```

### Focus States
```css
Ring color:      rgba(70, 95, 255, 0.12)  (brand con alpha)
Ring width:      4px
Debería estar visible en inputs y botones
```

### Active/Selected States
```css
Matched con brand-600 o brand-700
Fondo: brand-50
Texto: brand-500
```

### Disabled States
```css
Opacidad:        50% (opacity-50)
Cursor:          not-allowed
Color:           gray-400
```

---

## 🌓 Dark Mode Aplicación

**Patrón general:**
```blade
class="
  light-value
  dark:dark-value
"
```

**Ejemplos específicos:**

| Elemento | Light | Dark |
|----------|-------|------|
| Fondo cardona | bg-white | dark:bg-white/[0.03] |
| Borde | border-gray-200 | dark:border-gray-800 |
| Texto | text-gray-900 | dark:text-white/90 |
| Texto secondary | text-gray-500 | dark:text-gray-400 |
| Hover bg | hover:bg-gray-100 | dark:hover:bg-white/5 |
| Button primary | bg-brand-500 | dark:bg-brand-500 |

---

## 📱 Breakpoints

| Nombre | Pixel | Uso |
|--------|-------|-----|
| 2xsm | 375px | Phones muy pequeños |
| xsm | 425px | Phones estándar |
| sm | 640px | Tablets pequeñas |
| md | 768px | Tablets |
| **lg** | **1024px** | Laptops pequeñas |
| **xl** | **1280px** | ← PUNTO PRINCIPAL |
| 2xl | 1536px | Desktops grandes |
| 3xl | 2000px | Pantallas ultra-wide |

**Estrategia mobile-first:**
```blade
<!-- Móvil por defecto -->
<div class="w-full p-4">
  <!-- Tablet -->
  md:w-1/2 md:p-6
  <!-- Desktop -->
  lg:w-1/3 lg:p-8
  <!-- Sidebar visible en XL+ -->
  xl:flex-row
</div>
```

---

## 👨‍💻 CSS Custom Properties - Para Usar Directamente

```css
/* Usar en estilos personalizados */
color: var(--color-brand-500);
background: var(--color-gray-50);
border-color: var(--color-gray-200);
box-shadow: var(--shadow-theme-md);
font-family: var(--font-outfit);
font-size: var(--text-theme-sm);
```

---

## 🎓 Recetas Rápidas

### Card con título y botón
```blade
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
  <div class="flex items-center justify-between px-6 py-5">
    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
      Mi Tarjeta
    </h3>
    <button class="px-3 py-1.5 rounded-lg bg-brand-50 text-brand-600
                   font-medium dark:bg-brand-500/20 dark:text-brand-400">
      Acción
    </button>
  </div>
  <div class="px-6 py-4 border-t border-gray-100">
    <!-- Contenido -->
  </div>
</div>
```

### Badge de estado
```blade
<!-- Success -->
<span class="inline-flex px-2.5 py-0.5 rounded-full bg-success-50 
             text-success-700 text-xs font-semibold dark:bg-success-500/20 
             dark:text-success-400">
  Activo
</span>

<!-- Error -->
<span class="inline-flex px-2.5 py-0.5 rounded-full bg-error-50 
             text-error-700 text-xs font-semibold dark:bg-error-500/20 
             dark:text-error-400">
  Error
</span>
```

### Input con label
```blade
<div class="space-y-2">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
    Email
  </label>
  <input type="email" class="w-full px-3 py-2.5 rounded-lg border border-gray-300
                             bg-white text-gray-900 placeholder-gray-500
                             focus:border-brand-500 focus:ring-4 focus:ring-brand-50
                             dark:border-gray-700 dark:bg-gray-900 dark:text-white" 
         placeholder="example@mail.com" />
</div>
```

---

## 📌 Color Psychology en el Diseño

- **Brand Blue (#465fff):** Confianza, profesionalidad, acción
- **Gray:** Neutralidad, estructura, información
- **Green (Success):** Validación, éxito, positividad
- **Red (Error):** Urgencia, crítico, atención
- **Amber (Warning):** Precaución, información importante
- **Light Blue:** Información secundaria, apoyo
- **Pink/Purple:** Accents creativos, llamadas a la atención

---

**Documento de referencia rápida creado: Marzo 2026**  
**Úsalo como guía visual y de valores para futuros proyectos**
