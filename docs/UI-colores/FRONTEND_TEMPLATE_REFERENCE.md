# TailAdmin Frontend - Plantilla de Referencia

**Fecha de Documentación:** Marzo 2026  
**Proyecto:** TailAdmin Laravel - Tailwind CSS Admin Dashboard  
**Stack:** Laravel + Vue/Alpine.js + Tailwind CSS v4 + Vite

---

## 📋 Índice
1. [Arquitectura General](#arquitectura-general)
2. [Sistema de Colores](#sistema-de-colores)
3. [Tipografía](#tipografía)
4. [Componentes Principales](#componentes-principales)
5. [Layouts](#layouts)
6. [Estilos y Utilidades](#estilos-y-utilidades)
7. [Patrones Reutilizables](#patrones-reutilizables)
8. [Dependencias Clave](#dependencias-clave)
9. [Recomendaciones](#recomendaciones)

---

## 🏗️ Arquitectura General

### Estructura de Carpetas

```
resources/
├── css/
│   └── app.css           # Estilos base + utilidades personalizadas
├── js/
│   ├── app.js           # Punto de entrada
│   ├── bootstrap.js     # Inicialización
│   └── components/      # Componentes JS reutilizables
│       ├── calendar-init.js
│       ├── map.js
│       └── chart/       # Gráficos con ApexCharts
└── views/
    ├── layouts/         # Layouts base
    │   ├── app.blade.php          # Layout principal
    │   ├── app-header.blade.php   # Header/Navbar
    │   ├── sidebar.blade.php      # Sidebar navegación
    │   ├── backdrop.blade.php     # Modal backdrop
    │   └── fullscreen-layout.blade.php
    ├── components/      # Componentes Blade reutilizables
    │   ├── common/      # Componentes globales
    │   ├── ecommerce/   # Componentes específicos
    │   ├── form/
    │   ├── header/
    │   ├── profile/
    │   ├── tables/
    │   └── ui/
    └── pages/          # Páginas específicas
        ├── dashboard/
        ├── auth/
        ├── tables/
        ├── forms/
        ├── charts/
        └── errors/

app/
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       └── SidebarController.php
├── View/
│   └── Components/      # Componentes Blade reutilizables
└── Helpers/
    └── MenuHelper.php   # Lógica del menú
```

---

## 🎨 Sistema de Colores

### Paleta Base

**Forma de usar:** Las variables están definidas en `app.css` usando CSS custom properties (`--color-*`)

#### Colores Primarios
- **Brand (Principal):** `#465fff` a `#161950` (Azul electrónico)
  - `--color-brand-500: #465fff` (primario)
  - `--color-brand-400: #7592ff`
  - `--color-brand-600: #3641f5` (hover/active)
  - `--color-brand-700: #2a31d8` (dark)

#### Colores Neutros
- **Gray:** Escala completa de `#fcfcfd` a `#0c111d`
  - `--color-gray-50: #f9fafb` (fondos claros)
  - `--color-gray-500: #667085` (textos secundarios)
  - `--color-gray-800: #1d2939` (textos oscuros)
  - `--color-gray-900: #101828` (texto base)
  - `--color-gray-dark: #1a2231` (oscuro adicional)

#### Colores de Estado
- **Success:** Verde `#12b76a` principal
  - `--color-success-50: #ecfdf3` (fondo)
  - `--color-success-500: #12b76a` (activo)
  - `--color-success-700: #027a48` (dark)

- **Error:** Rojo `#f04438` principal
  - `--color-error-50: #fef3f2` (fondo)
  - `--color-error-500: #f04438` (activo)
  - `--color-error-700: #b42318` (dark)

- **Warning:** Ámbar `#f79009` principal
  - `--color-warning-50: #fffaeb` (fondo)
  - `--color-warning-500: #f79009` (activo)
  - `--color-warning-700: #b54708` (dark)

#### Colores Secundarios
- **Blue Light:** Cian/Turquesa
  - `--color-blue-light-500: #0ba5ec` (principal)
  
- **Orange:** Naranja
  - `--color-orange-500: #fb6514` (principal)

- **Accent Colors:**
  - `--color-theme-pink-500: #ee46bc`
  - `--color-theme-purple-500: #7a5af8`

#### Colores de Fondo
- Light mode: `bg-gray-50` (fondo principal)
- Dark mode: `bg-gray-900` (fondo oscuro)

---

## 📝 Tipografía

### Fuente Principal
- **Familia:** `Outfit` (Google Fonts)
- **Pesos:** 100-900
- **Carga:** Via Google Fonts CDN

```css
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
```

### Escalas de Tamaños de Texto

| Variable CSS | Tamaño | Altura de Línea | Uso |
|---|---|---|---|
| `--text-title-2xl` | 72px | 90px | Títulos principales |
| `--text-title-xl` | 60px | 72px | Títulos grandes |
| `--text-title-lg` | 48px | 60px | Títulos med-grandes |
| `--text-title-md` | 36px | 44px | Título medio |
| `--text-title-sm` | 30px | 38px | Título pequeño |
| `--text-theme-xl` | 20px | 30px | Texto destacado |
| `--text-theme-sm` | 14px | 20px | Texto normal (predeterminado) |
| `--text-theme-xs` | 12px | 18px | Texto pequeño |

### Pesos Recomendados
- **Títulos:** 600-700 (semibold/bold)
- **Texto principal:** 400-500 (normal/medium)
- **Etiquetas:** 500-600 (medium/semibold)
- **Helper text:** 400 (normal)

---

## 🧩 Componentes Principales

### 1. Component Card (Tarjeta Contenedor)
**Ubicación:** `resources/views/components/common/component-card.blade.php`

```blade
<x-card title="Título del Card" desc="Descripción opcional">
    <!-- Contenido -->
</x-card>
```

**Características:**
- Border: `border-gray-200` / `dark:border-gray-800`
- Fondo: `bg-white` / `dark:bg-white/[0.03]`
- Border-radius: `rounded-2xl`
- Sombra: Sutil

---

### 2. Page Breadcrumb (Pan de migas)
**Ubicación:** `resources/views/components/common/page-breadcrumb.blade.php`

```blade
<x-page-breadcrumb pageTitle="Nombre de la Página" />
```

**Características:**
- Breadcrumb automático hacia Home
- Título de página visible en la primera línea
- Estilos responsive

---

### 3. Theme Toggle (Cambio de tema)
**Ubicación:** `resources/views/components/common/theme-toggle.blade.php`

**Características:**
- Toggle Light/Dark mode
- Almacena preferencia en localStorage
- Iconos intercambiables (Sun/Moon)
- Integrado con Alpine.js

---

### 4. Sidebar (Navegación Lateral)
**Ubicación:** `resources/views/layouts/sidebar.blade.php`

**Características:**
- **Responsive:** Colapsa en móvil, expandible en desktop
- **Hover state:** Expansión al pasar mouse en estado colapsado
- **Submenús:** Desplegables dinámicos
- **Estados activos:** Automáticos según ruta actual
- **Animaciones:** Transiciones suaves `duration-300 ease-in-out`
- **Ancho dinámico:**
  - Expandido: `w-[290px]`
  - Colapsado: `w-[90px]`
  - Mobile: Completo con overlay

**Estructura:**
```blade
- Logo Section (expandible)
- Navigation Menu
  - Grupos de menú
  - Items con iconos
  - Submenús
  - Badges (New, Pro, etc)
```

---

### 5. App Header (Encabezado)
**Ubicación:** `resources/views/layouts/app-header.blade.php`

**Características:**
- **Sticky:** Permanece en la parte superior
- **Responsive:** Diferente en móvil vs desktop
- **Elementos principales:**
  - Sidebar toggle button
  - Mobile menu button
  - Logo (mobile only)
  - Search bar (desktop only)
  - Application menu
  - Theme toggle
  - User profile menu

---

### 6. Componentes Comunes Reutilizables

| Componente | Ubicación | Uso |
|---|---|---|
| **Dropdown Menu** | `common/dropdown-menu.blade.php` | Menús desplegables |
| **Table Dropdown** | `common/table-dropdown.blade.php` | Menús en tablas |
| **Preloader** | `common/preloader.blade.php` | Indicador de carga |
| **Grid Shape** | `common/common-grid-shape.blade.php` | Formas de diseño |

---

## 🎯 Layouts

### Layout Principal (app.blade.php)

**Estructura:**
```
HTML
├── Head
│   ├── Meta tags
│   ├── Vite assets
│   ├── Alpine.js store (tema y sidebar)
│   └── Script anti-flash (dark mode)
└── Body
    ├── Sidebar
    ├── Header
    ├── Main Content Area
    │   └── @yield('content')
    └── Scripts
```

**Alpine.js Stores:**
```javascript
$store.theme {
  theme: 'light' | 'dark'
  toggle()
  updateTheme()
}

$store.sidebar {
  isExpanded: boolean
  isMobileOpen: boolean
  isHovered: boolean
  toggleExpanded()
  toggleMobileOpen()
}
```

---

## 🎨 Estilos y Utilidades

### Utilidades CSS Personalizadas (@utility)

```css
@utility menu-item { /* Estilos base para items de menú */ }
@utility menu-item-active { /* Estados activos */ }
@utility menu-item-inactive { /* Estados inactivos */ }
@utility menu-item-icon { /* Iconos en items */ }
@utility menu-dropdown-item { /* Items en submenús */ }
@utility menu-dropdown-badge { /* Badges de estado */ }
@utility no-scrollbar { /* Ocultar scrollbar */ }
@utility custom-scrollbar { /* Scrollbar personalizado */ }
```

### Sombras Personalizadas

```css
--shadow-theme-xs: 0px 1px 2px 0px rgba(16, 24, 40, 0.05);
--shadow-theme-sm: 0px 1px 3px 0px rgba(16, 24, 40, 0.1), 0px 1px 2px 0px rgba(16, 24, 40, 0.06);
--shadow-theme-md: 0px 4px 8px -2px rgba(16, 24, 40, 0.1), 0px 2px 4px -2px rgba(16, 24, 40, 0.06);
--shadow-theme-lg: 0px 12px 16px -4px rgba(16, 24, 40, 0.08), 0px 4px 6px -2px rgba(16, 24, 40, 0.03);
--shadow-theme-xl: 0px 20px 24px -4px rgba(16, 24, 40, 0.08), 0px 8px 8px -4px rgba(16, 24, 40, 0.03);
```

### Z-index Stack

```css
--z-index-1: 1
--z-index-9: 9
--z-index-99: 99
--z-index-999: 999
--z-index-9999: 9999
--z-index-99999: 99999
--z-index-999999: 999999
```

### Breakpoints Personalizados

```css
--breakpoint-2xsm: 375px
--breakpoint-xsm: 425px
--breakpoint-sm: 640px
--breakpoint-md: 768px
--breakpoint-lg: 1024px
--breakpoint-xl: 1280px (punto de quiebre principal)
--breakpoint-2xl: 1536px
--breakpoint-3xl: 2000px
```

---

## 🔄 Patrones Reutilizables

### Patrón 1: Dark Mode

**Estructura:**
```blade
<!-- Light mode -->
<div class="bg-white dark:bg-gray-900">
  <img class="dark:hidden" src="light-image.svg" />
  <img class="hidden dark:block" src="dark-image.svg" />
</div>
```

**Implementación:**
- Clase `dark` en `<html>`
- Preferencia guardada en localStorage
- Alpine.js store para gestión

### Patrón 2: Componentes Responsivos

**Mobile-first approach:**
```blade
<div class="
  w-full                     <!-- Mobile -->
  md:w-1/2                   <!-- Tablet arriba -->
  lg:w-80                    <!-- Desktop -->
  xl:flex-row               <!-- XL: horizontal -->
">
```

### Patrón 3: Menú Activo Dinámico

**Estructura Python/PHP:**
```php
// MenuHelper.php
$menuGroups = [
  [
    'items' => [
      ['path' => '/dashboard', 'label' => 'Dashboard', 'icon' => '...'],
      ['path' => '/users', 'label' => 'Usuarios', 'icon' => '...', 'subItems' => [...]]
    ]
  ]
];
```

**Blade Alpine implementation:**
```blade
x-data="{
  isActive(path) {
    return window.location.pathname === path;
  }
}"
:class="{ 'menu-item-active': isActive(item.path) }"
```

### Patrón 4: Estados de Componentes

**Menú:**
```
Active: bg-brand-50 text-brand-500
Inactive: text-gray-700 hover:bg-gray-100
```

**Dark mode:**
```
Active: dark:bg-brand-500/[0.12] dark:text-brand-400
Inactive: dark:text-gray-300 dark:hover:bg-white/5
```

---

## 📦 Dependencias Clave

### Frontend
```json
{
  "@tailwindcss/vite": "^4.1.12",
  "tailwindcss": "^4.1.12",
  "alpinejs": "^3.14.9",
  "apexcharts": "^5.3.5",
  "swiper": "^12.0.3",
  "@fullcalendar/core": "^6.1.19",
  "flatpickr": "^4.6.13",
  "jsvectormap": "^1.7.0",
  "prismjs": "^1.30.0",
  "axios": "^1.11.0",
  "@floating-ui/dom": "^1.7.4",
  "@popperjs/core": "^2.11.8"
}
```

### Build Tools
```json
{
  "vite": "^7.0.4",
  "laravel-vite-plugin": "^2.0.0"
}
```

---

## 💡 Recomendaciones para Usar como Plantilla

### 1. **Estructura de Archivos**
✅ Mantén la carpeta `resources/views/components/` con subdirectorios por tipo
✅ Crea componentes Blade reutilizables para todo

### 2. **Sistema de Colores**
✅ Define todos los colores como variables CSS personalizadas en `app.css`
✅ Usa los custom properties de Tailwind (`--color-*`)
✅ Mantén la escala de 11 variantes (25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950)

### 3. **Componentes**
✅ Hazlos lo más reutilizables posible con `@props`
✅ Usa slots para flexibilidad
✅ Merge atributos con `{{ $attributes->merge([...]) }}`

### 4. **Dark Mode**
✅ Siempre incluye variantes `dark:` en estilos
✅ Usa Alpine.js store para gestión centralizada
✅ Almacena preferencia en localStorage

### 5. **Responsive Design**
✅ Mobile-first: estilos base para móvil, luego media queries
✅ Usa breakpoint `xl: 1280px` como punto de quiebre principal
✅ Considera `md: 768px` para cambios significativos

### 6. **Animaciones**
✅ Mantén transiciones suaves: `duration-300 ease-in-out`
✅ Usa Alpine.js para estados dinámicos
✅ Anima cambios de layout con transitions

### 7. **Accesibilidad**
✅ Incluye `aria-label` en botones
✅ Usa roles ARIA apropiados
✅ Mantén contraste de colores adecuado

### 8. **Performance**
✅ Usa lazy loading en imágenes
✅ Minifica CSS/JS en producción
✅ Implementa caché de vistas Blade

---

## 🎓 Ejemplos de Uso

### Ejemplo 1: Crear un Card Personalizado

```blade
<!-- resources/views/components/custom-card.blade.php -->
@props([
  'title',
  'footer' => null,
])

<x-component-card :title="$title">
  <div class="space-y-4">
    {{ $slot }}
  </div>

  @if ($footer)
    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
      {{ $footer }}
    </div>
  @endif
</x-component-card>
```

### Ejemplo 2: Agregar una Nueva Página

```blade
<!-- resources/views/pages/my-page.blade.php -->
@extends('layouts.app', ['title' => 'Mi Página'])

@section('content')
  <x-page-breadcrumb pageTitle="Mi Página" />

  <x-card title="Mi Tarjeta">
    <p>Contenido aquí</p>
  </x-card>
@endsection
```

### Ejemplo 3: Componente con Estado Activo

```blade
<div
  @class([
    'menu-item',
    'menu-item-active' => $isActive,
    'menu-item-inactive' => !$isActive,
  ])
>
  <span @class([
    'menu-item-icon',
    'menu-item-icon-active' => $isActive,
    'menu-item-icon-inactive' => !$isActive,
  ])>
    {{ $icon }}
  </span>
  <span>{{ $label }}</span>
</div>
```

---

## 📊 Chart Integration (ApexCharts)

Los gráficos están personalizados con:

```css
.apexcharts-legend-text {
  @apply !text-gray-700 dark:!text-gray-400;
}

.apexcharts-tooltip.apexcharts-theme-light {
  @apply gap-1 !rounded-lg !border-gray-200 p-3 !shadow-theme-sm 
         dark:!border-gray-800 dark:!bg-gray-900;
}

.apexcharts-gridline {
  @apply !stroke-gray-100 dark:!stroke-gray-800;
}
```

---

## 🔐 Notas Importantes

1. **Alpine.js:** Accede a stores globales con `$store.theme` y `$store.sidebar`
2. **Rutas activas:** Se detectan automáticamente comparando `window.location.pathname`
3. **Breakpoint principal:** `xl` (1280px) es donde la UI cambia significativamente
4. **Dark mode:** Auto-detección basada en preferencia del sistema si no hay localStorage
5. **Sprites de SVG:** Usa `{{ $slot }}` para inyectar SVGs dinámicamente

---

**Última actualización:** Marzo 6, 2026  
**Versión del proyecto:** TailAdmin 2.0 (Laravel + Tailwind CSS v4)
