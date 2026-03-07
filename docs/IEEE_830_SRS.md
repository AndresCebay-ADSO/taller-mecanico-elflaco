# Especificación de Requisitos de Software (SRS) 
**Basado en el estándar IEEE 830-1998**

**Proyecto:** Sistema de Gestión de Taller Mecánico "El Flaco"  
**Versión:** 1.0  

---

## 1. Introducción

### 1.1 Propósito
El propósito de este documento es especificar los requisitos de software, tanto funcionales como no funcionales, del "Sistema de Gestión de Taller Mecánico El Flaco". Este documento servirá de guía para los desarrolladores, testers, y los propietarios del negocio para entender el alcance final del sistema y las reglas funcionales inherentes.

### 1.2 Convenciones del Documento
Los requisitos funcionales se identifican con la sigla **RF-XXX** (Ej. RF-001) para facilitar su trazabilidad. Se aplican prioridades a los requisitos en escala: *Alta, Media, Crítica.*

### 1.3 Alcance del Proyecto
El software a desarrollar es un sistema web de gestión administrativa y operativa (ERP/POS ligero) diseñado específicamente para el rubro automotriz. 
El sistema permitirá:
- Control riguroso del inventario mediante el método FIFO (PEPS), vinculación con proveedores y alertas de escasez.
- Administración del flujo de clientes a través de Órdenes de Servicio formales.
- Gestión de la fuerza laboral (mecánicos) con cálculo automatizado de comisiones (porcentuales o fijas).
- Punto de Venta (POS) para clientes de mostrador.
- Análisis de datos mediante un Dashboard que exponga las métricas financieras (ingresos del taller vs. pago de comisiones) en tiempo real.

---

## 2. Descripción General

### 2.1 Perspectiva del Producto
El sistema funciona de forma independiente y autónoma, reemplazando el uso de hojas de cálculo o procesos de seguimiento manuales por una plataforma web centralizada. 

### 2.2 Funciones del Producto
Las principales agrupaciones lógicas (módulos) del sistema son:
1. **Inventario, Productos y Proveedores:** Manejo de mercancía.
2. **Control Operativo (Órdenes y Trabajos):** Recepción de vehículos, asignación de tareas a mecánicos y reporte de avance.
3. **Módulo de Ventas:** Interfaz de facturación y venta directa de repuestos.
4. **Configuración del Taller (Reglas de Negocio):** ABM de mecánicos, roles y tipos de trabajos con asignación de márgenes de ganancia.
5. **Dashboard e Informes:** Inteligencia de negocios para el dueño del taller.

### 2.3 Tipos de Usuarios y Características
- **Administrador / Dueño:** Tiene acceso total a configuraciones, reportes financieros, inventario, costos reales FIFO y porcentajes de ganancia de los mecánicos.
- **Recepción / Ventas:** Puede crear órdenes de servicio, realizar ventas directas, visualizar stock y facturar, pero carece de permisos para alterar las configuraciones base o ver ingresos netos globales.

### 2.4 Entorno Operativo
- **Servidor:** Servidor Linux/Windows con soporte para PHP 8.2+ y MySQL/MariaDB.
- **Cliente:** Cualquier navegador web moderno (Chrome, Edge, Firefox, Safari) en PC de escritorio o tablets.

---

## 3. Requisitos Funcionales Específicos

A continuación se listan los requisitos funcionales extraídos de la etapa de análisis (referir al documento `requisitos.md` para más información técnica).

### 3.1 Módulo de Inventario
- **RF-001 (CRUD Productos):** El sistema debe permitir el mantenimiento del catálogo de repuestos e insumos (crear, leer, actualizar, borrar), categorizarlos y establecer stock mínimo.
- **RF-002 (Alertas de Stock):** El sistema debe evaluar continuamente el stock y notificar visualmente en el Dashboard cuando el nivel (`stock`) descienda por debajo del `minStock`.
- **RF-003 (Manejo FIFO/Costos Reales):** Todas las entradas de inventario se deben registrar como "Lotes" de compra con su proveedor y costo asociados. Las salidas se extraerán de los lotes más antiguos (FIFO) para un correcto cálculo de rentabilidad.
- **RF-005 (Movimientos Automáticos):** Toda acción de venta de repuestos o uso en un trabajo debe generar automáticamente un registro de salida de inventario (`out`) asegurando trazabilidad.

### 3.2 Órdenes de Servicio y Trabajos
- **RF-009 (Apertura de Órdenes):** El usuario deberá poder aperturar una orden asociada a los datos de un cliente y su vehículo para agrupar múltiples tareas de reparación bajo un mismo folio.
- **RF-006 (Registro y Asignación de Trabajos):** Dentro de una orden, se deben poder agregar N trabajos individuales. A cada trabajo se le asigna un mecánico, una categoría de reparación, un costo de mano de obra y productos de inventario a utilizar.
- **RF-007 (Cálculo de Comisiones a Mecánicos):** El sistema distribuirá obligatoria y automáticamente las ganancias procedentes del pago de un trabajo separando la cuota del mecánico y la del taller.
- **RF-011 (Máquina de Estados de la Orden):** La Orden de servicio cambiará su estado basado en eventos: `Abierta` -> `En progreso` (cuando se le añaden trabajos o se envían mecánicos) -> `Completada` -> `Facturada`.

### 3.3 Reglas de Negocio / Tipos de Trabajo
- **RF-013 (Configurador de Tipos de Trabajo):** El administrador definirá modelos de cobro: 
   - **Porcentaje:** La mano de obra se divide porcentualmente entre Taller y Mecánico. (Ej: 60% Mecánico / 40% Taller).
   - **Fijo:** Se establece una tarifa fija pagada al mecánico por tarea completada, independientemente de lo que se le cobre al cliente.

### 3.4 Pagos y Facturación
- **RF-015 (Venta Directa POS):** Capacidad de despachar mercancía sin aperturar una Orden de Servicio (compras rápidas al mostrador).
- **RF-012 (Consolidación de Factura):** Cierre de una Orden de Servicio donde se sumen los subtotales de todos los trabajos individuales (mano de obra + repuestos usados) deduciendo correctamente los costos de inventario de acuerdo al estándar FIFO.

### 3.5 Panel de Control (Dashboard)
- **RF-018 (Métricas KPI en Vivo):** Indicadores visuales en pantalla de inicio que informen: cantidad de reparaciones en curso, ingresos brutos diarios/mensuales, comisiones por pagar a mecánicos y lista rápida de piezas agotadas.

---

## 4. Requisitos No Funcionales

### 4.1 Rendimiento
- El sistema web no debe tardar más de 2 segundos en renderizar las vistas bajo carga moderada y conexión a internet estándar.
- Las consultas a reportes financieros extensos deben realizarse en menos de 5 segundos aprovechando el ORM optimizado y los índices de bases de datos.

### 4.2 Seguridad
- **Autenticación:** Todo acceso al sistema debe estar protegido mediante un sistema seguro de usuario y contraseña (Laravel Breeze / Hasheo bcrypt).
- **Autorización:** Bloqueo de URLs operativas para usuarios que no estén autenticados, y prevención de reasignaciones (Mass Assignment) a nivel de base de datos a través de políticas en Eloquent.

### 4.3 Calidad del Software
- **Usabilidad:** El Frontend, diseñado con **TailwindCSS**, deberá ser intuitivo, minimalista y responder a dispositivos de escritorio (Responsive design). Las tablas deben incorporar paginación dinámica.
- **Mantenibilidad:** El código backend debe seguir estrictamente la arquitectura MVC (Model-View-Controller) proporcionada por Laravel, adhiriéndose a principios limpios y utilizando el sistema de Inyección de Dependencias.
- **Disponibilidad:** Dado que reemplazará registros en papel, el sistema debe orientarse a un despliegue en la nube que garantice alto uptime.

---

## 5. Otras Restricciones de Diseño
- **Restricción de Integridad Referencial:** No debe ser posible borrar a un mecánico que tenga reparaciones en su historial, ni borrar un producto que ya tenga registros de ventas. Deberá implementarse 'Soft Deletes' (eliminación lógica) o simplemente deshabilitar entidades no activas.
- **Cálculo de Impuestos:** *(Por definir en futuras versiones el manejo fiscal de IVA u otros tributos locales).*  

*(Fin del documento)*
