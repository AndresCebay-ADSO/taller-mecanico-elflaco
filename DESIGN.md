# DESIGN.md — MotoTaller El Flaco

Contrato de interfaz del taller. **Todo cambio de pantalla lo sigue.** Las reglas marcadas _(pendiente)_ describen una
decisión cuyo código aún no existe: mientras tanto se sigue el patrón actual, sin inventar un tercero.

Alcance: `resources/views/**`. Solo `resources/views/components/` puede definir clases de color o tamaño propias; las
vistas usan los componentes y los tokens.

## 1. Principios

- **Minimalista y neutro.** Superficies grises de los tokens. El color comunica (estado, alerta, acción principal);
  nunca decora. Si todo tiene color, nada resalta.
- **Primero el portátil, pero sin romper en el celular.** La usuaria trabaja en un portátil; cada pantalla igual debe
  verse sin scroll horizontal de la página a 390 px.
- **Consistencia antes que creatividad.** Si existe un componente, se usa. Si no, se crea una vez en `components/` y se
  usa en todas partes. Nunca una variante por pantalla.
- **Nada decorativo.** Un control que no hace nada (un buscador que no busca, una campana sin notificaciones) se quita.
- **Español, con mayúscula solo al inicio:** «Nueva venta», «Órdenes de servicio». Se conservan siglas (NIT, FIFO). El
  título de la página, la pestaña del navegador y el menú lateral dicen lo mismo.
- **Funciona sin internet.** Fuentes, iconos y scripts van incluidos en la app. Nada se carga de un CDN.

## 2. Colores

Todo color sale de un token en `resources/css/app.css` (`:root` = claro, `.dark` = oscuro), expuesto en `@theme` como
`--color-*`. Para cambiar un color se cambia el token, nunca cada vista.

| Token (`bg-*`, `text-*`, `border-*`) | Uso |
| :-- | :-- |
| `background` / `foreground` | fondo de la app / texto principal |
| `card` | superficie de trabajo (tarjetas, tablas, formularios) |
| `muted` / `muted-foreground` | superficie sutil / texto secundario; el tono **neutro** |
| `border`, `input`, `ring` | líneas, borde de campos, foco |
| `primary` / `primary-foreground` | **solo** la acción principal de la pantalla, enlaces y el ítem activo del menú |
| `destructive` / `destructive-foreground` | botón que anula o elimina |
| `success`, `warning`, `info`, `destructive` | base de los tintes de estado (`bg-success/15`) |
| `success-text`, `warning-text`, `info-text`, `destructive-text` | texto de estado sobre el fondo o sobre su tinte |
| `sidebar`, `sidebar-foreground`, `sidebar-active` | menú lateral (oscuro en ambos temas) |

Valores (escala gris y azul que el taller ya tenía):

| Token | Claro | Oscuro |
| :-- | :-- | :-- |
| `background` | `#f9fafb` | `#0c111d` |
| `card` | `#ffffff` | `#101828` |
| `muted` | `#f2f4f7` | `#1d2939` |
| `foreground` | `#101828` | `#f2f4f7` |
| `muted-foreground` | `#475467` | `#98a2b3` |
| `border` / `input` | `#e4e7ec` / `#d0d5dd` | `#1d2939` / `#344054` |
| `primary` / `-foreground` | `#3641f5` / `#ffffff` | `#7592ff` / `#0c111d` |
| `ring` | `#465fff` | `#7592ff` |
| `destructive` / `-foreground` | `#d92d20` / `#ffffff` | `#d92d20` / `#ffffff` |
| `success` / `success-text` | `#12b76a` / `#067647` | `#12b76a` / `#32d583` |
| `warning` / `warning-text` | `#f79009` / `#8a5a00` | `#f79009` / `#fdb022` |
| `info` / `info-text` | `#0ba5ec` / `#026aa2` | `#0ba5ec` / `#36bffa` |
| `destructive-text` | `#be123c` | `#f97066` |
| `sidebar` / `-foreground` / `-active` | `#0c111d` / `#98a2b3` / `#ffffff` | igual |

- Contraste verificado (WCAG ≥ 4,5:1) en ambos temas: texto sobre `background`, `card` y `muted`; cada `-text` sobre el
  fondo y sobre su tinte al 15 %; cada `-foreground` sobre su relleno. Si un token cambia, se vuelve a verificar.
- **`info` no es `primary`.** Son azules distintos a propósito: el azul de la acción principal nunca significa un estado.
- **Prohibido en vistas:** paleta cruda de Tailwind (`slate-*`, `gray-*`, `blue-*`, `emerald-*`, `red-*`, `amber-*`…),
  `bg-white`/`text-white`/`text-black`, y hex, `rgb()` o `hsl()` en `class` o `style`. La única excepción es la hoja de
  impresión de la factura (`@media print`), que fuerza negro sobre blanco.

## 3. Estados (tonos)

Cinco tonos, ninguno más (sin morado, sin teal, sin `primary`).

**Regla de oro: el estado normal o terminado no lleva color.** El color se reserva para lo que pide acción o es
excepcional. Si todo tiene color, el ojo aprende a ignorarlo: en los datos de demo, el 83 % de los movimientos son
ventas y usos en orden, y el 90 % de las órdenes están completadas; pintarlos convertía las tablas en un muro
amarillo o verde.

| Tono | Icono | Clases | Significa |
| :-- | :-- | :-- | :-- |
| `neutral` | — | `bg-muted text-muted-foreground` | lo normal o terminado: completada, vigente, cancelada, rutina, método de pago, inactivo |
| `info` | `circle-dot` | `bg-info/15 text-info-text` | en curso: en progreso, en tránsito, transferencia |
| `warning` | `circle-alert` | `bg-warning/15 text-warning-text` | pide acción: pendiente, stock bajo, ajuste manual |
| `success` | `circle-check` | `bg-success/15 text-success-text` | algo bueno que conviene notar: entra mercancía, activo |
| `danger` | `circle-x` | `bg-destructive/15 text-destructive-text` | dinero revertido o problema: anulada, agotado |

- **Nunca solo color.** Rojo, verde y ámbar se confunden con daltonismo (verificado: ningún ajuste de color los
  separa del todo). Cada píldora lleva su texto **y** el icono del tono (`App\Support\Tone::icon()`); un movimiento
  usa su flecha en vez del icono (`<x-status-badge :icon="…">`).
- Una sola forma de píldora: `rounded-full px-2 py-0.5 text-xs font-medium` + el tono.

| Qué | Valor → tono |
| :-- | :-- |
| Orden de servicio, trabajo | pending warning · in_progress info · completed neutral · cancelled neutral |
| Transferencia | pending warning · in_transit info · completed neutral · cancelled neutral |
| Venta | completada neutral · anulada danger |
| Factura | vigente neutral · anulada danger |
| Producto, mecánico, proveedor, usuario | activo success · inactivo neutral (listas cortas: el verde ayuda a ver los inactivos) |
| Stock | normal neutral (en listados, sin píldora) · bajo warning · agotado danger |
| Movimiento de inventario | compra success · ajuste warning · transferencia info · venta, uso en orden y anulación neutral (flecha y signo dicen la dirección) |
| Método de pago (Efectivo, Nequi…) | siempre neutral |

- **Una sola fuente de verdad, en PHP.** Cada modelo con estado expone `statusLabel()` y `statusTone()` (`ServiceOrder`,
  `WorkshopJob`, `Sale`, `Invoice`, `BranchTransfer`; `Product` con `stockLabel()`/`stockTone()` e `InventoryMovement` con
  `typeLabel()`/`directionTone()`/`directionIcon()`), y las vistas pintan con `<x-status-badge :tone="$order->statusTone()">{{ $order->statusLabel() }}</x-status-badge>`.
  **Nada de mapas de color en las vistas** (`match($status) { 'pending' => 'amber' … }`). El único mapa tono → clases es
  `App\Support\Tone` (píldora, aviso y texto).
- **Nunca solo color.** Un movimiento lleva icono y signo: entrada `arrow-down` `+5`, salida `arrow-up` `−5`. El tono va
  en la píldora y en la cantidad, **nunca en el fondo de la fila**.

## 4. Tipografía

- **Una familia: Instrument Sans**, incluida en la app con `@fontsource/instrument-sans` (pesos 400/500/600). Números de
  factura, órdenes y lotes usan la mono del sistema: `font-mono`. **Prohibido:** Inter, Google Fonts, bunny.net o
  cualquier `<link>` de fuentes.
- **Pesos:** `font-normal`, `font-medium`, `font-semibold`. **Prohibido:** `font-bold`, `font-extrabold`, `font-black`
  (700+ no se carga y el navegador lo simula).
- **Tamaños:** solo la escala de Tailwind. **Prohibido:** `text-[10px]`, `text-[11px]` y similares.

| Rol | Clases |
| :-- | :-- |
| Título de página (`h1`, `<x-page-header>`) | `text-2xl font-semibold tracking-tight` |
| Título de sección (`h2`, encabezado de `<x-card>`) | `text-base font-semibold` |
| Subsección (`h3`) | `text-base font-semibold` |
| Cuerpo, celda, botón, etiqueta | `text-sm` (`font-medium` en etiquetas y botones) |
| Ayuda de campo, metadatos | `text-xs text-muted-foreground` |
| Descripción de página o sección | `text-sm text-muted-foreground` |
| Encabezado de tabla | `text-xs font-medium uppercase tracking-wide text-muted-foreground` |
| Cifra de KPI (dashboard, reportes) | `text-2xl font-semibold tabular-nums` |

`uppercase` + `tracking-*` solo en el encabezado de tabla. No en botones, filtros, badges ni títulos.

## 5. Forma

- **Radios:** `rounded-lg` en tarjetas, diálogos y tablas; `rounded-md` en botones y campos; `rounded-full` solo en
  píldoras y avatares. **Prohibido:** `rounded-2xl`, `rounded-3xl`, `rounded-[…]`.
- **Sombras:** tarjeta `border border-border shadow-xs`; diálogo `shadow-lg`. Sin sombras de color
  (`shadow-brand-500/20`).
- **Movimiento:** solo transiciones de color y opacidad (`transition-colors`). Sin `active:scale-95`, sin animaciones
  de entrada en tarjetas.
- **Iconos:** Lucide (incluido en la app). `size-4` en botones, tablas y campos; `size-5` junto a títulos de tarjeta.
  Un botón solo-icono lleva `<span class="sr-only">` y `title`, ambos en español. Los botones con texto («Nueva venta»,
  «Guardar») no llevan icono, salvo que el icono diga algo que el texto no: dirección de un movimiento o formato
  (Imprimir, PDF).

## 6. Números y fechas

- Dinero siempre en formato colombiano, sin decimales: `$1.250.000`. **Nunca un número crudo** en la vista.
- Un solo helper: `money($valor)` en PHP (`app/helpers.php`) y `formatCurrency` / `money()` en Alpine para los totales
  en vivo (`resources/js/app.js`). Nunca `number_format(...)` en una vista.
- Columnas numéricas: alineadas a la derecha con `tabular-nums`.
- Fechas `d/m/Y`, con hora `d/m/Y h:i a`, en `America/Bogota`.

## 7. Tablas y listados

- Usar `<x-table>`. Encabezado según §4; celdas `text-sm`; filas separadas por `divide-y divide-border`, sin rayado.
- **El identificador abre el detalle**, no la fila entera: el número de orden o de factura es un enlace; el resto de la
  fila es texto seleccionable. La fila solo se resalta al pasar el mouse (`hover:bg-muted/50`).
- **Acciones:** al final de la fila, como mucho dos botones solo-icono (`ghost`). Si hay más, un menú «⋯». «Anular» o
  «Eliminar» siempre en rojo (`destructive-text`) y de último.
- **Celda sin dato:** «—» en `text-muted-foreground`. Nunca vacía, nunca «N/A».
- **Listado vacío:** `<x-empty-state>`: caja con borde punteado, mensaje centrado y, si aplica, la acción
  principal («Aún no hay ventas. Nueva venta»).
- Una tabla más ancha que su caja hace scroll **dentro de la caja**, nunca la página.
- **Los totales no van dentro de la tabla:** van en una tarjeta debajo, alineada a la derecha (`ml-auto max-w-sm`).
- Filtros arriba de la tabla con `<x-search-filter>`; los filtros por estado son un `<select>` o pestañas neutras, no
  botones de colores.

## 8. Formularios

- Etiqueta `text-sm font-medium`, ayuda `text-xs text-muted-foreground` y error `text-xs text-destructive-text`, en ese
  orden y debajo del campo. Campo obligatorio: `*` después de la etiqueta.
- Campos: `bg-card border-input rounded-md text-sm`, foco con `ring-2 ring-ring/30 border-ring`.
- **Dinero:** `<x-input is-money>` (formatea con puntos de miles mientras se escribe). **Prohibido `type="number"`**
  para dinero y cantidades.
- **Pie del formulario:** «Cancelar» (`secondary`) a la izquierda y la acción principal (`primary`) a la derecha;
  `destructive` cuando anula algo.
- **Sin doble envío:** al enviar, el botón se deshabilita y muestra «Guardando…» (global, en `resources/js/app.js`).
  Crítico en ventas, compras y facturas, donde un doble clic duplica el registro.
- **Un error de validación nunca borra lo escrito.** Los datos vuelven con `old()` y el foco va al primer campo con
  error.
- **Confirmaciones:** con el diálogo de la app (`<x-confirm-dialog>`, montado una vez en el layout). El formulario solo
  declara atributos: `data-confirm` (pregunta), `data-confirm-description` (qué pasa), `data-confirm-label` (botón) y
  `data-confirm-destructive` (botón en rojo). El foco empieza en «Cancelar». **Prohibido:** `confirm()` y `alert()`
  del navegador.
- Formularios cortos (≤ 6 campos, sin líneas) pueden ir en un diálogo; los que tienen líneas de productos (venta,
  trabajo, compra) van en página.

## 9. Componentes

| Componente | Contrato |
| :-- | :-- |
| `<x-page-header>` | El único `h1` de la pantalla: `title`, `subtitle`, slot `badge` (estado del registro) y slot `actions` a la derecha (debajo en celular). En detalles, `back` + `back-label` ponen «‹ Ventas» sobre el título. Sin icono junto al título. |
| `<x-card>` | Toda sección de una página es una tarjeta. `title` (+ `description` y slot `actions`) arma el encabezado con su `h2` (`text-base font-semibold`); `flush` quita el relleno para tablas que llegan al borde. |
| `<x-button>` | Variantes: `primary`, `secondary`, `ghost`, `destructive` (los nombres viejos `danger`, `outline` y `success` se traducen). Tamaños `sm`, `md`, `icon`. Con `href` es un enlace. |
| `<x-status-badge>` | Píldora de estado. Recibe `tone` (§3). `<x-badge>` queda solo por compatibilidad y traduce sus colores viejos a tonos. |
| `<x-table>` | §7. |
| `<x-input>`, `<x-select>` | §8. Etiqueta, `help`, error y `aria-*` ya conectados. |
| `<x-alert>` | Mensajes flash y avisos fijos, con los tonos de §3. El de éxito se cierra solo a los 5 s. |
| `<x-empty-state>` | §7. `icon` opcional y slot `action`. |
| `<x-confirm-dialog>` | §8. Ya está en el layout; las vistas no lo incluyen. |
| `<x-textarea>` | §8. Igual que `<x-input>`, para texto largo. |
| `<x-checkbox>` | Casilla con su texto al lado y `help` debajo. Desmarcada no se envía: el controlador usa `$request->boolean()`. |
| `<x-active-badge>` | Activo success / inactivo neutral. `feminine` dice «Activa». |
| Paginación | `$items->links()` usa `resources/views/pagination/app.blade.php` (registrada en `AppServiceProvider`). |
| Menú lateral | Fijo desde `lg`; en pantallas chicas se abre con ☰ desde la barra superior (`navOpen` en el layout). |

## 10. Diálogos con datos de una fila

Para abrir un diálogo con los datos de un registro, el botón es un `<button>` normal con
`x-on:click="edit({{ \Illuminate\Support\Js::from($data) }})"`. **Nunca** `addslashes` ni `@json` dentro de un
atributo: un nombre con comillas o un salto de línea rompe el botón. Crear y editar comparten un solo diálogo
(proveedores, sucursales).

## 11. Cómo se hace un cambio de interfaz

1. Primero los tokens y los componentes, con 2–3 pantallas de ejemplo (dashboard, ventas, detalle de orden), revisadas
   en el navegador.
2. Después el resto de pantallas en un segundo cambio, repitiendo el patrón aprobado.
3. Cada cambio que toca pantallas se revisa en claro, en oscuro y a 390 px.

Checklist:

- [ ] Solo tokens: sin paleta cruda, hex ni tamaños arbitrarios en las vistas.
- [ ] Pesos 400/500/600 y tamaños de la tabla de §4.
- [ ] Estados con `<x-status-badge>` y los tonos de §3; `primary` solo en la acción principal.
- [ ] Dinero con el helper; columnas numéricas a la derecha.
- [ ] Sin `confirm()`/`alert()` ni `type="number"`; el botón principal evita el doble envío.
- [ ] Se lee bien en ambos temas; el foco se ve.
- [ ] Sin scroll horizontal de la página a 390 px.
- [ ] `php artisan test` en verde. `tests/Feature/DesignContractTest.php` revisa todas las vistas y falla si aparece algo
      prohibido de este documento (cada excepción lleva su motivo en el mismo archivo).
