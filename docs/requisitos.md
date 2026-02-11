3. Requisitos Funcionales

### 3.1 Módulo de Inventario

#### RF-001: Gestión de Productos
| Campo | Detalle |
|-------|---------|
| **ID** | RF-001 |
| **Nombre** | Gestión de Productos (CRUD) |
| **Descripción** | El sistema debe permitir crear, leer, actualizar y eliminar productos del inventario |
| **Entradas** | Nombre, categoría, precio compra, precio venta, stock inicial, stock mínimo, proveedor (opcional) |
| **Salidas** | Confirmación de operación, lista actualizada de productos |
| **Prioridad** | Alta |

**Categorías predefinidas:** Llantas, Lubricantes, Frenos, Transmisión, Neumáticos, Eléctricos, Filtros, Otros

#### RF-002: Alertas de Stock Bajo
| Campo | Detalle |
|-------|---------|
| **ID** | RF-002 |
| **Nombre** | Alertas de Stock Bajo |
| **Descripción** | El sistema debe mostrar alertas cuando el stock de un producto esté por debajo del mínimo establecido |
| **Condición** | `stock < minStock` |
| **Salidas** | Lista de productos con stock bajo en Dashboard, badge visual en tabla de inventario |
| **Prioridad** | Alta |

#### RF-003: Ajuste de Stock
| Campo | Detalle |
|-------|---------|
| **ID** | RF-003 |
| **Nombre** | Ajuste de Stock con Trazabilidad |
| **Descripción** | El sistema debe permitir entradas y salidas de inventario registrando proveedor y precio de compra en entradas |
| **Entradas** | Producto, tipo (entrada/salida), cantidad, razón, proveedor (en entradas), precio compra (en entradas) |
| **Efectos** | Actualiza stock, registra movimiento de inventario, actualiza precio de compra del producto, registra historial de compra |
| **Prioridad** | Alta |

#### RF-004: Historial de Compras por Proveedor
| Campo | Detalle |
|-------|---------|
| **ID** | RF-004 |
| **Nombre** | Historial de Compras |
| **Descripción** | El sistema debe registrar cada compra de inventario asociada a un proveedor y precio específico, permitiendo comparación de precios |
| **Datos registrados** | Producto, proveedor, precio de compra, cantidad, fecha |
| **Prioridad** | Media |

#### RF-005: Movimientos de Inventario
| Campo | Detalle |
|-------|---------|
| **ID** | RF-005 |
| **Nombre** | Registro Automático de Movimientos |
| **Descripción** | El sistema debe registrar automáticamente todas las entradas y salidas de inventario |
| **Tipos de movimiento** | `in` (entrada), `out` (salida) |
| **Razones** | `purchase` (compra), `sale` (venta), `job` (trabajo), `adjustment` (ajuste manual) |
| **Prioridad** | Alta |

---

### 3.2 Módulo de Trabajos

#### RF-006: Registro de Trabajos Individuales
| Campo | Detalle |
|-------|---------|
| **ID** | RF-006 |
| **Nombre** | Registro de Trabajos |
| **Descripción** | El sistema debe permitir registrar trabajos de reparación con mecánico asignado, tipo de trabajo configurable, productos utilizados y costo de mano de obra |
| **Entradas** | Tipo trabajo (select dinámico), mecánico, vehículo, cliente, teléfono (opcional), productos, mano de obra |
| **Salidas** | Trabajo registrado con cálculo automático de ganancias según reglas del tipo seleccionado |
| **Comportamiento** | Campos condicionales según configuración del tipo (mano de obra editable, productos habilitados, descripción por defecto) |
| **Prioridad** | Alta |

#### RF-007: Cálculo Automático de Ganancias
| Campo | Detalle |
|-------|---------|
| **ID** | RF-007 |
| **Nombre** | Cálculo de Ganancias por Tipo de Trabajo |
| **Descripción** | El sistema debe calcular automáticamente las ganancias del mecánico y del taller según las reglas configuradas en el tipo de trabajo |
| **Reglas** | Ver sección 4 (Reglas de Negocio) |
| **Prioridad** | Crítica |

#### RF-008: Gestión de Estados de Trabajo
| Campo | Detalle |
|-------|---------|
| **ID** | RF-008 |
| **Nombre** | Estados de Trabajo |
| **Descripción** | El sistema debe permitir cambiar el estado de un trabajo |
| **Estados** | `pending` → `in_progress` → `completed` |
| **Efecto al completar** | Registra `completedAt`, actualiza métricas del dashboard |
| **Prioridad** | Alta |

---

### 3.3 Módulo de Órdenes de Servicio

#### RF-009: Creación de Órdenes de Servicio
| Campo | Detalle |
|-------|---------|
| **ID** | RF-009 |
| **Nombre** | Órdenes de Servicio |
| **Descripción** | El sistema debe permitir crear órdenes que agrupen múltiples trabajos independientes para un mismo cliente/vehículo |
| **Entradas** | Nombre del cliente, teléfono (opcional), información del vehículo |
| **Salidas** | Orden creada con estado `open`, lista para agregar trabajos |
| **Prioridad** | Alta |

#### RF-010: Gestión de Trabajos dentro de Órdenes
| Campo | Detalle |
|-------|---------|
| **ID** | RF-010 |
| **Nombre** | Agregar/Eliminar Trabajos en Orden |
| **Descripción** | El sistema debe permitir agregar y eliminar trabajos individuales dentro de una orden de servicio |
| **Al agregar** | Calcula ganancias individualmente, descuenta inventario, estado de orden pasa a `in_progress` |
| **Al eliminar** | Restaura productos al inventario |
| **Prioridad** | Alta |

#### RF-011: Estados de Orden de Servicio
| Campo | Detalle |
|-------|---------|
| **ID** | RF-011 |
| **Nombre** | Flujo de Estados de Órdenes |
| **Descripción** | Las órdenes siguen un flujo de estados definido |
| **Flujo** | `open` → `in_progress` → `completed` → `invoiced` |
| **Transiciones** | Automática al agregar trabajo (→ in_progress), manual (→ completed), al facturar (→ invoiced) |
| **Prioridad** | Alta |

#### RF-012: Generación de Facturas
| Campo | Detalle |
|-------|---------|
| **ID** | RF-012 |
| **Nombre** | Facturación Consolidada |
| **Descripción** | El sistema debe generar una factura consolidada desde una orden de servicio completada |
| **Contenido** | Datos del cliente, vehículo, desglose por servicio (descripción, mecánico, mano de obra, productos, subtotal), resumen de totales (total mano de obra, total productos, gran total) |
| **Efecto** | Orden cambia a estado `invoiced` |
| **Prioridad** | Alta |

---

### 3.4 Módulo de Tipos de Trabajo Configurables

#### RF-013: CRUD de Tipos de Trabajo
| Campo | Detalle |
|-------|---------|
| **ID** | RF-013 |
| **Nombre** | Tipos de Trabajo Configurables |
| **Descripción** | El sistema debe permitir crear, editar y eliminar tipos de trabajo con reglas de pago personalizables |
| **Configuración por tipo** | Nombre, descripción, tipo de cálculo (porcentaje o fijo), porcentajes o montos fijos, permitir mano de obra personalizada, permitir productos, descripción por defecto |
| **Restricción** | Los tipos de sistema (`isSystem: true`) pueden editarse pero no eliminarse |
| **Prioridad** | Media |

#### RF-014: Tipos de Cálculo
| Campo | Detalle |
|-------|---------|
| **ID** | RF-014 |
| **Nombre** | Tipos de Cálculo de Ganancias |
| **Descripción** | El sistema soporta dos modos de cálculo |
| **Porcentaje** | Se define % mecánico y % taller sobre la mano de obra. Productos van al taller |
| **Fijo** | Se definen montos fijos para total, mecánico y taller. Productos adicionales van al taller |
| **Prioridad** | Crítica |

---

### 3.5 Módulo de Ventas

#### RF-015: Ventas Directas
| Campo | Detalle |
|-------|---------|
| **ID** | RF-015 |
| **Nombre** | Ventas Directas de Productos |
| **Descripción** | El sistema debe permitir registrar ventas de productos sin trabajo asociado |
| **Entradas** | Productos (múltiples), cantidades, cliente (opcional) |
| **Efectos** | Descuenta stock automáticamente, registra movimiento de inventario, calcula total |
| **Prioridad** | Media |

---

### 3.6 Módulo de Mecánicos

#### RF-016: Gestión de Mecánicos
| Campo | Detalle |
|-------|---------|
| **ID** | RF-016 |
| **Nombre** | CRUD de Mecánicos |
| **Descripción** | El sistema debe permitir crear, editar y eliminar mecánicos con estado activo/inactivo |
| **Entradas** | Nombre, teléfono, email (opcional), fecha contratación, estado activo |
| **Restricción** | Solo mecánicos activos aparecen en selectores de asignación |
| **Prioridad** | Alta |

---

### 3.7 Módulo de Proveedores

#### RF-017: Gestión de Proveedores
| Campo | Detalle |
|-------|---------|
| **ID** | RF-017 |
| **Nombre** | CRUD de Proveedores |
| **Descripción** | El sistema debe permitir crear, editar y eliminar proveedores |
| **Entradas** | Nombre, teléfono, email (opcional), dirección (opcional) |
| **Prioridad** | Media |

---

### 3.8 Módulo de Dashboard

#### RF-018: Panel de Métricas
| Campo | Detalle |
|-------|---------|
| **ID** | RF-018 |
| **Nombre** | Dashboard con Métricas en Tiempo Real |
| **Descripción** | El sistema debe mostrar un panel con métricas clave del negocio, calculadas en tiempo real |
| **Métricas** | Total productos, productos con stock bajo, mecánicos totales, trabajos activos, ganancias del día, ganancias del mes, ganancias acumuladas taller, ganancias acumuladas mecánicos |
| **Componentes** | Tarjetas de estadísticas (StatCard), alertas de stock bajo (LowStockAlert), trabajos recientes (RecentJobs) |
| **Prioridad** | Alta |

---

### 3.9 Módulo de Reportes

#### RF-019: Reportes de Ganancias
| Campo | Detalle |
|-------|---------|
| **ID** | RF-019 |
| **Nombre** | Reportes de Ganancias por Período |
| **Descripción** | El sistema debe generar reportes de ganancias con filtros por período y desglose taller vs mecánicos |
| **Prioridad** | Media |

#### RF-020: Reportes de Inventario
| Campo | Detalle |
|-------|---------|
| **ID** | RF-020 |
| **Nombre** | Reportes de Movimientos de Inventario |
| **Descripción** | El sistema debe mostrar movimientos de inventario con filtros por tipo, producto y fecha |
| **Prioridad** | Media |

---