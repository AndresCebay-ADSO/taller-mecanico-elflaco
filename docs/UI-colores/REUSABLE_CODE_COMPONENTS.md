# TailAdmin - Código Reutilizable & Componentes Blade

## 📋 Índice
1. [Componentes Blade Base](#componentes-blade-base)
2. [Patches CSS Útiles](#patches-css-útiles)
3. [Snippets Alpine.js](#snippets-alpinejs)
4. [Patrones de Estructura](#patrones-de-estructura)
5. [Código Listo para Copiar](#código-listo-para-copiar)

---

## 🧩 Componentes Blade Base

### 1. Card Personalizable
Ubicación para crear: `resources/views/components/ui/card.blade.php`

```blade
@props([
    'title' => '',
    'description' => '',
    'footer' => null,
    'headerAction' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    @if($title && $icon || $title && $headerAction)
    <!-- Card Header with Icon or Action -->
    <div class="flex items-center justify-between px-6 py-5">
        <div class="flex items-center gap-3">
            @if ($icon)
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-brand-50 dark:bg-brand-500/10">
                    {{ $icon }}
                </div>
            @endif
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                @if ($description)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </div>
        @if ($headerAction)
            {{ $headerAction }}
        @endif
    </div>
    @elseif ($title)
    <!-- Card Header Simple -->
    <div class="px-6 py-5">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ $title }}
        </h3>
        @if ($description)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $description }}
            </p>
        @endif
    </div>
    @endif

    <!-- Card Body -->
    <div class="px-4 border-t border-gray-100 dark:border-gray-800 sm:px-6 py-6">
        <div {{ $attributes->merge(['class' => 'space-y-6']) }}>
            {{ $slot }}
        </div>
    </div>

    <!-- Card Footer -->
    @if ($footer)
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-800">
            {{ $footer }}
        </div>
    @endif
</div>
```

**Uso:**
```blade
<!-- Simple Card -->
<x-ui.card title="Mi Tarjeta">
    Contenido aquí
</x-ui.card>

<!-- Card con descripción -->
<x-ui.card 
    title="Estadísticas" 
    description="Datos del mes actual"
>
    Contenido...
</x-ui.card>

<!-- Card con botón en header -->
<x-ui.card 
    title="Usuarios"
    :headerAction="'<button class=\"text-brand-600 font-medium text-sm\">Ver Todos</button>'"
>
    Tabla de usuarios...
</x-ui.card>

<!-- Card con footer -->
<x-ui.card 
    title="Configuración"
    :footer="'<button class=\"px-4 py-2 bg-brand-500 text-white rounded-lg\">Guardar</button>'"
>
    Formulario...
</x-ui.card>
```

---

### 2. Badge Reutilizable
Ubicación: `resources/views/components/ui/badge.blade.php`

```blade
@props([
    'variant' => 'success', // success, error, warning, info, brand
    'size' => 'md', // sm, md, lg
])

@php
$variantClasses = match($variant) {
    'success' => 'bg-success-50 text-success-700 dark:bg-success-500/20 dark:text-success-400',
    'error' => 'bg-error-50 text-error-700 dark:bg-error-500/20 dark:text-error-400',
    'warning' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/20 dark:text-warning-400',
    'info' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/20 dark:text-blue-light-400',
    'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/20 dark:text-brand-400',
    default => 'bg-gray-50 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400',
};

$sizeClasses = match($size) {
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-0.5 text-xs',
    'lg' => 'px-3 py-1 text-sm',
    default => 'px-2.5 py-0.5 text-xs',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex rounded-full font-medium {$variantClasses} {$sizeClasses}"]) }}>
    {{ $slot }}
</span>
```

**Uso:**
```blade
<x-ui.badge variant="success">Activo</x-ui.badge>
<x-ui.badge variant="error">Error</x-ui.badge>
<x-ui.badge variant="warning" size="sm">Pendiente</x-ui.badge>
<x-ui.badge variant="brand" size="lg">Premium</x-ui.badge>
```

---

### 3. Button Variants
Ubicación: `resources/views/components/ui/button.blade.php`

```blade
@props([
    'variant' => 'primary', // primary, secondary, danger, ghost
    'size' => 'md', // sm, md, lg
])

@php
$variantClasses = match($variant) {
    'primary' => 'bg-brand-500 text-white hover:bg-brand-600 active:bg-brand-700 
                 dark:bg-brand-500 dark:hover:bg-brand-600',
    'secondary' => 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 
                   dark:bg-white/5 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/10',
    'danger' => 'bg-error-500 text-white hover:bg-error-600 active:bg-error-700',
    'ghost' => 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5',
    default => 'bg-brand-500 text-white hover:bg-brand-600',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm rounded-lg',
    'md' => 'px-4 py-2.5 text-sm rounded-lg',
    'lg' => 'px-5 py-3 text-base rounded-lg',
    default => 'px-4 py-2.5 text-sm rounded-lg',
};
@endphp

<button {{ $attributes->merge(['class' => "font-medium transition-colors {$variantClasses} {$sizeClasses}", 'type' => 'button']) }}>
    {{ $slot }}
</button>
```

**Uso:**
```blade
<x-ui.button variant="primary">Guardar</x-ui.button>
<x-ui.button variant="secondary" size="sm">Cancelar</x-ui.button>
<x-ui.button variant="danger">Eliminar</x-ui.button>
<x-ui.button variant="ghost">Más opciones</x-ui.button>
```

---

### 4. Input with Label
Ubicación: `resources/views/components/ui/input.blade.php`

```blade
@props([
    'label' => '',
    'type' => 'text',
    'placeholder' => '',
    'error' => null,
    'helpText' => null,
    'required' => false,
])

<div class="space-y-1.5">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if ($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full px-3 py-2.5 rounded-lg border ' . ($error ? 'border-error-400' : 'border-gray-300') . ' 
            bg-white text-gray-900 placeholder-gray-400 text-sm font-normal
            focus:border-brand-500 focus:ring-4 focus:ring-brand-50 focus:outline-none
            disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed
            dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500
            dark:focus:border-brand-500 dark:focus:ring-brand-500/30']) }}
    />

    @if ($error)
        <p class="text-sm text-error-600 dark:text-error-400 mt-1">{{ $error }}</p>
    @elseif ($helpText)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
    @endif
</div>
```

**Uso:**
```blade
<x-ui.input 
    label="Email" 
    type="email" 
    placeholder="ejemplo@mail.com"
    required
    helpText="Usaremos esto para contarte"
/>

<x-ui.input 
    label="Contraseña"
    type="password"
    error="La contraseña es requerida"
/>
```

---

### 5. Select Dropdown
Ubicación: `resources/views/components/ui/select.blade.php`

```blade
@props([
    'label' => '',
    'options' => [],
    'selected' => null,
    'error' => null,
])

<div class="space-y-1.5">
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
    @endif

    <select {{ $attributes->merge(['class' => 'w-full px-3 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-900 text-sm
        focus:border-brand-500 focus:ring-4 focus:ring-brand-50 focus:outline-none
        dark:border-gray-700 dark:bg-gray-900 dark:text-white']) }}>
        <option value="">-- Selecciona una opción --</option>
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" {{ $value === $selected ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>

    @if ($error)
        <p class="text-sm text-error-600 dark:text-error-400">{{ $error }}</p>
    @endif
</div>
```

**Uso:**
```blade
<x-ui.select
    label="Estado"
    name="status"
    :options="['active' => 'Activo', 'inactive' => 'Inactivo']"
    selected="active"
/>
```

---

## 🎨 Patches CSS Útiles

### Para agregar a `app.css`:

#### 1. Gradient Utilities
```css
@utility gradient-brand {
  @apply bg-gradient-to-r from-brand-500 to-brand-600;
}

@utility gradient-to-purple {
  @apply bg-gradient-to-r from-theme-pink-500 to-theme-purple-500;
}
```

#### 2. Hover Effects
```css
@utility hover-lift {
  @apply transition-transform duration-200 hover:-translate-y-1;
}

@utility hover-shadow {
  @apply transition-shadow duration-200 hover:shadow-theme-lg;
}
```

#### 3. Animation Utilities
```css
@utility animate-pulse-subtle {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@utility animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
```

#### 4. Text Utilities
```css
@utility text-truncate {
  @apply overflow-hidden text-overflow-ellipsis whitespace-nowrap;
}

@utility line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
```

---

## 🎪 Snippets Alpine.js

### 1. Modal Manager
```javascript
// En layout o component
x-data="{
    openModals: {},
    open(name) {
        this.openModals[name] = true;
        document.body.style.overflow = 'hidden';
    },
    close(name) {
        this.openModals[name] = false;
        if (Object.values(this.openModals).every(v => !v)) {
            document.body.style.overflow = 'auto';
        }
    },
    isOpen(name) {
        return this.openModals[name] || false;
    }
}"
```

**Uso:**
```blade
<button @click="open('confirmDelete')">Eliminar</button>

<div x-show="isOpen('confirmDelete')" class="fixed inset-0 bg-black/50 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 max-w-md">
        <h3 class="text-lg font-semibold">¿Estás seguro?</h3>
        <p class="text-gray-500 mt-2">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3 mt-6">
            <button @click="close('confirmDelete')" class="px-4 py-2 border border-gray-200 rounded-lg">
                Cancelar
            </button>
            <button class="px-4 py-2 bg-error-500 text-white rounded-lg">
                Eliminar
            </button>
        </div>
    </div>
</div>
```

### 2. Dropdown Menu
```javascript
x-data="{
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
}"
@click.away="close()"
```

**Uso:**
```blade
<div x-data="{ open: false }" @click.away="open = false" class="relative">
    <button @click="open = !open" class="px-4 py-2 border border-gray-200 rounded-lg">
        Opciones
    </button>
    
    <div x-show="open" x-transition class="absolute mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-theme-lg z-99">
        <a href="#" class="menu-dropdown-item-inactive">Editar</a>
        <a href="#" class="menu-dropdown-item-inactive">Duplicar</a>
        <a href="#" class="menu-dropdown-item-inactive text-error-600">Eliminar</a>
    </div>
</div>
```

### 3. Tabs Manager
```javascript
x-data="{
    activeTab: 'usuarios',
    selectTab(tab) {
        this.activeTab = tab;
    }
}"
```

**Uso:**
```blade
<div x-data="{ activeTab: 'usuarios' }">
    <!-- Tab Buttons -->
    <div class="flex gap-2 border-b border-gray-200">
        <button @click="activeTab = 'usuarios'" 
            :class="activeTab === 'usuarios' ? 'menu-item-active' : 'menu-item-inactive'"
            class="menu-item">
            Usuarios
        </button>
        <button @click="activeTab = 'roles'" 
            :class="activeTab === 'roles' ? 'menu-item-active' : 'menu-item-inactive'"
            class="menu-item">
            Roles
        </button>
    </div>

    <!-- Tab Content -->
    <div x-show="activeTab === 'usuarios'" class="mt-4">
        Contenido usuarios...
    </div>
    <div x-show="activeTab === 'roles'" class="mt-4">
        Contenido roles...
    </div>
</div>
```

---

## 🏗️ Patrones de Estructura

### Patrón 1: Página con Breadcrumb + Card
```blade
<!-- resources/views/pages/my-page.blade.php -->
@extends('layouts.app', ['title' => 'Mi Página'])

@section('content')
    <div class="space-y-6">
        <!-- Breadcrumb -->
        <x-page-breadcrumb pageTitle="Mi Página" />

        <!-- Main Content -->
        <x-ui.card title="Contenido Principal" description="Descripción aquí">
            <p>Tu contenido va aquí</p>
        </x-ui.card>
    </div>
@endsection
```

### Patrón 2: Grid de Cards
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach ($items as $item)
        <x-ui.card :title="$item->name">
            <p class="text-gray-600">{{ $item->description }}</p>
            <div class="mt-4 flex gap-2">
                <x-ui.badge variant="success">{{ $item->status }}</x-ui.badge>
            </div>
        </x-ui.card>
    @endforeach
</div>
```

### Patrón 3: Tabla con Acciones
```blade
<x-ui.card title="Usuarios">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Email</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <button class="text-brand-600 hover:text-brand-700 font-medium text-sm">
                                Editar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
```

### Patrón 4: Formulario con Validación
```blade
<x-ui.card title="Nuevo Usuario">
    <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
        @csrf

        <x-ui.input
            label="Nombre Completo"
            name="name"
            :value="old('name')"
            :error="$errors->has('name') ? $errors->first('name') : null"
            required
        />

        <x-ui.input
            label="Email"
            type="email"
            name="email"
            :value="old('email')"
            :error="$errors->has('email') ? $errors->first('email') : null"
            required
        />

        <x-ui.select
            label="Rol"
            name="role"
            :options="$roles"
            :selected="old('role')"
        />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="submit" variant="primary">
                Guardar
            </x-ui.button>
            <x-ui.button type="reset" variant="secondary">
                Limpiar
            </x-ui.button>
        </div>
    </form>
</x-ui.card>
```

---

## 💻 Código Listo para Copiar

### Header Component
```blade
<!-- Copiable directamente -->
<header class="sticky top-0 flex w-full bg-white border-b border-gray-200 z-99999 
              dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between w-full px-6 py-3 lg:py-4">
        
        <!-- Sidebar Toggle -->
        <button class="hidden xl:flex items-center justify-center w-10 h-10 text-gray-500 
                      border border-gray-200 rounded-lg dark:border-gray-800 dark:text-gray-400"
                @click="$store.sidebar.toggleExpanded()">
            <svg class="w-4 h-3" fill="currentColor" viewBox="0 0 16 12">
                <path fill-rule="evenodd" d="M0.58 1C0.58 0.586 0.92 0.25 1.33 0.25H14.67C15.08 0.25 15.42 0.586 15.42 1 
                     C15.42 1.414 15.08 1.75 14.67 1.75H1.33C0.92 1.75 0.58 1.414 0.58 1ZM0.58 11C0.58 10.586 0.92 10.25 1.33 10.25 
                     H14.67C15.08 10.25 15.42 10.586 15.42 11C15.42 11.414 15.08 11.75 14.67 11.75H1.33C0.92 11.75 0.58 11.414 0.58 11Z"/>
            </svg>
        </button>

        <!-- Spacer -->
        <div class="flex-1"></div>

        <!-- Theme Toggle -->
        <x-theme-toggle />
    </div>
</header>
```

### Stats Card
```blade
<!-- Tarjeta de estadísticas -->
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
    <div class="flex items-baseline justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ $value }}
            </p>
            @if ($change)
                <p class="mt-2 text-sm font-medium {{ str_starts_with($change, '+') ? 'text-success-600' : 'text-error-600' }}">
                    {{ $change }}
                </p>
            @endif
        </div>
        @if ($icon)
            <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-brand-50 dark:bg-brand-500/10">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
```

---

**Última actualización: Marzo 2026**
