# MotoTaller "El Flaco" - Sistema de Gestión Premium 🏍️🔧

![Laravel 11](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP 8.2](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS 4](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

MotoTaller "El Flaco" es una solución integral y moderna diseñada para la gestión administrativa y operativa de talleres mecánicos de motocicletas. Con un enfoque en la experiencia de usuario y la eficiencia técnica, el sistema ofrece una interfaz premium con soporte para modo oscuro y una gestión de inventario de vanguardia.

---

## ✨ Características Destacadas

### 🎨 Interfaz Premium & UX Moderna
- **Diseño Navy/Slate:** Una paleta de colores sofisticada y profesional.
- **Modo Oscuro Nativo:** Soporte completo para trabajar en condiciones de poca luz sin fatiga visual.
- **Componentes Dinámicos:** Animaciones sutiles y transiciones fluidas para una experiencia fluida.

### 📦 Gestión de Inventario Inteligente (FIFO/PEPS)
- **Trazabilidad por Lotes:** Control absoluto sobre cada entrada de repuestos.
- **Método PEPS:** El sistema utiliza el método *Primero en Entrar, Primero en Salir* para asegurar que los costos de venta y márgenes de ganancia sean exactos basándose en el precio de compra real de los lotes más antiguos.
- **Alertas Proactivas:** Notificaciones visuales de stock bajo directamente en el Dashboard.

### 💰 Ciclo de Ventas y Servicios
- **Órdenes de Servicio Consolidadas:** Agrupa múltiples trabajos, productos y diagnósticos en una sola orden para el cliente.
- **Venta Directa POS:** Módulo de venta rápida de mostrador con capacidad de agregar múltiples productos de forma simultánea.
- **Facturación Automatizada:** Generación de comprobantes detallados con desglose de mano de obra y refacciones.

### 📊 Inteligencia de Negocio
- **Dashboard en Tiempo Real:** Métricas clave (ganancias del día, trabajos activos, stock crítico) al alcance de un vistazo.
- **Tipos de Trabajo Configurables:** Reglas de ganancia personalizables (porcentaje sobre mano de obra o montos fijos) para mecánicos y taller.

---

## 🛠️ Stack Tecnológico

- **Backend:** Laravel 11.x (PHP 8.2+)
- **Frontend:** Blade Components + Alpine.js
- **Estilos:** Tailwind CSS 4 + Vite
- **Base de Datos:** MySQL / MariaDB
- **Autenticación:** Laravel Breeze (Custom Premium Theme)

---

## 📂 Documentación del Proyecto

Puedes encontrar detalles específicos sobre la arquitectura y requerimientos en la carpeta `/docs`:

- 📄 **[Requerimientos del Sistema (SRS)](docs/IEEE_830_SRS.md)**
- 📄 **[Guía Técnica de Arquitectura](docs/guia_tecnica.md)**
- 📄 **[Modelo de Datos y Eloquent](docs/explicacion_modelos.md)**

---

## ⚙️ Instalación Rápida

### Requisitos
- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL / MariaDB

### Pasos
1. **Clonar y entrar:**
   ```bash
   git clone https://github.com/AndresCebay-ADSO/taller-mecanico-elflaco.git
   cd taller-mecanico-elflaco
   ```
2. **Dependencias:**
   ```bash
   composer install
   npm install
   ```
3. **Configuración:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Base de Datos:**
   *(Configura tus credenciales en el archivo .env)*
   ```bash
   php artisan migrate --seed
   ```
5. **Ejecutar:**
   ```bash
   npm run dev
   # En otra terminal
   php artisan serve
   ```

---

## 👨‍💻 Contribución

Este proyecto es una herramienta de gestión privada. Para reportar errores o sugerir mejoras, por favor abre un issue en el repositorio.

---

## 📄 Licencia

Distribuido bajo la Licencia **MIT**. Consulta el archivo para más detalles.
