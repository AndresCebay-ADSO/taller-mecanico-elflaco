# Sistema de Gestión de Taller Mecánico "El Flaco" 🚗🔧

![Laravel 11](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

Este es un sistema integral y moderno desarrollado en **Laravel 11** para la optimización y gestión administrativa y operativa de un taller mecánico. Diseñado para simplificar el día a día, el sistema centraliza el control de inventarios, órdenes de servicio, gestión de mecánicos, ventas directas y métricas de negocio.

---

## 🚀 Características Principales

### 📦 Gestión de Inventario Avanzada
- **Control de Stock y Trazabilidad:** Seguimiento preciso de productos, control de stock mínimo y alertas automatizadas.
- **Gestión de Proveedores y Compras:** Historial detallado de compras por proveedor y seguimiento de precios.
- **Sistema FIFO:** Gestión de inventario mediante el método PEPS (Primeros en Entrar, Primeros en Salir) para un cálculo preciso de los márgenes de ganancia.

### 📋 Órdenes de Servicio y Trabajos
- **Control Integral de Vehículos:** Registro detallado de la entrada de vehículos, clientes asociados y seguimiento de diagnóstico.
- **Asignación a Mecánicos:** Asignación de tareas específicas a mecánicos.
- **Cálculo de Ganancias Automático:** Reglas de negocio configurables por *Tipo de Trabajo* (porcentaje sobre mano de obra o cuotas fijas).
- **Control de Estados:** Flujo de vida desde la recepción hasta la entrega (`Pendiente` → `En Progreso` → `Completado`).

### 💰 Ventas y Facturación
- **Venta Directa de Mostrador:** Módulo de Venta Rápida (POS) para repuestos e insumos sin requerir orden de servicio.
- **Facturación Integrada:** Generación automática de facturas a partir de órdenes de servicio completadas, detallando mano de obra y refacciones.

### 📊 Dashboard y Reportes
- **Métricas en Tiempo Real:** Visualización rápida del estado del taller (trabajos activos, ganancias del día/mes, stock bajo).

---

## 🛠️ Tecnologías y Arquitectura

El proyecto sigue una arquitectura sólida utilizando los últimos estándares de desarrollo web:

- **Backend:** Laravel 11.x (PHP 8.2+) con Eloquent ORM.
- **Frontend:** Laravel Breeze, Blade Components y Tailwind CSS para una interfaz reactiva y limpia.
- **Base de Datos:** MySQL / MariaDB (con diseño relacional normalizado).
- **Pruebas:** Pest PHP para pruebas unitarias y de integración.

---

## 📁 Documentación Técnica del Sistema

Toda la documentación relacionada con la arquitectura, requerimientos y lógica de negocio se encuentra en el directorio `/docs`:

- 📄 **[IEEE 830 - Funcionalidades y Requisitos (SRS)](docs/IEEE_830_SRS.md)**: Especificación formal de requerimientos de software.
- 📄 **[Guía Técnica de Rutas y Controladores](docs/guia_tecnica.md)**: Arquitectura RESTful implementada.
- 📄 **[Explicación de Modelos y DB](docs/explicacion_modelos.md)**: Relaciones de Eloquent y diagrama de flujo de datos.
- 📄 **[Requisitos Iniciales](docs/requisitos.md)**: Historias de usuario base del proyecto.

---

## ⚙️ Requisitos Previos e Instalación

### Requisitos del Servidor
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL o MariaDB

### Instalación Paso a Paso

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/tu-usuario/taller-mecanico-elflaco.git
   cd taller-mecanico-elflaco
   ```

2. **Instalar dependencias de PHP:**
   ```bash
   composer install
   ```

3. **Configurar el entorno:**
   ```bash
   cp .env.example .env
   # Configura tus variables de entorno DB_DATABASE, DB_USERNAME, DB_PASSWORD en el archivo .env
   ```

4. **Generar la clave de la aplicación:**
   ```bash
   php artisan key:generate
   ```

5. **Ejecutar migraciones y seeders:**
   ```bash
   php artisan migrate --seed
   ```

6. **Instalar y compilar dependencias del Frontend:**
   ```bash
   npm install
   npm run build
   ```

7. **Iniciar el servidor local:**
   ```bash
   php artisan serve
   ```
   *Accede a [http://localhost:8000](http://localhost:8000) en tu navegador.*

---

## 👨‍💻 Contribución

Este proyecto es mantenido privadamente, pero las sugerencias de mejora son bienvenidas. Por favor, asegúrate de correr los tests antes de hacer un commit:
```bash
php artisan test
```

## 📄 Licencia

Este proyecto se distribuye bajo la licencia **MIT**. Siéntete libre de modificarlo según las necesidades de tu taller.
