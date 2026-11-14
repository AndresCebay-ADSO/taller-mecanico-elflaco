# Instalación en una máquina Windows (XAMPP)

Guía para instalar el taller en **cualquier** equipo Windows sin Docker: el portátil del cliente, un PC nuevo de
desarrollo o una reinstalación. Todo lo que aquí está escrito ya causó o puede causar un «en mi máquina sí funciona».
Si algo nuevo se descubre al instalar, **se agrega a la sección 9**.

Al terminar, en cualquier máquina:

```bat
php artisan taller:verificar
```

revisa PHP, extensiones, OPcache, `.env`, zona horaria, base de datos, migraciones, permisos y la interfaz compilada,
y dice qué corregir. Sale con error si algo impide trabajar.

Complementa a [`PRODUCCION_Y_DEMO.md`](PRODUCCION_Y_DEMO.md) (qué cambió para producción y guion de demo).

---

## 1. Qué se instala

| Programa | Versión verificada | Para qué | Notas |
| :-- | :-- | :-- | :-- |
| XAMPP | 8.2.12 (PHP 8.2.12, MariaDB 10.4.32, Apache 2.4) | Servidor web + base de datos | Las 60+ pruebas pasan sobre MariaDB 10.4. |
| Git | cualquiera reciente | Traer y actualizar el código | |
| Composer | 2.8 | Dependencias de PHP | |
| Node.js | 20 o superior (verificado 22.15) | Compilar la interfaz | Solo se usa al instalar o actualizar. |
| AnyDesk | — | Soporte remoto | Configurar acceso desatendido con contraseña. |

Ruta del proyecto: **`C:\taller\taller-mecanico-elflaco`**. Fuera de `htdocs` y **sin espacios ni tildes** en la
ruta (algunas herramientas fallan con `C:\Users\María\Mis documentos\...`).

---

## 2. Configurar PHP (`C:\xampp\php\php.ini`)

XAMPP viene pensado para desarrollo. Cambiar estas líneas (quitar el `;` del inicio donde lo tenga) y **reiniciar
Apache** desde el panel de XAMPP:

```ini
; OPcache: guarda el código PHP ya compilado. Sin esto cada página tarda ~4 veces más
; (medido: ~210 ms → ~50 ms por página).
zend_extension=opcache

[opcache]
opcache.enable=1
opcache.enable_cli=1            ; necesario si se usa `php artisan serve`
opcache.memory_consumption=128
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1   ; revisa si un archivo cambió (para que `git pull` se vea)
opcache.revalidate_freq=2       ; en desarrollo: 0 (cambios visibles al recargar)

; Zona horaria (XAMPP trae Europe/Berlin). La app usa la suya, pero los comandos sueltos de PHP no.
date.timezone=America/Bogota

; Extensiones (casi todas vienen activas; verificar)
extension=fileinfo
extension=mbstring
extension=pdo_mysql
extension=zip
extension=intl                  ; recomendada (hoy la app no la usa, Laravel sí en algunos helpers)
```

Comprobar: `php -v` debe mostrar `with Zend OPcache`.

---

## 3. Configurar MySQL / MariaDB

### 3.1 Seguridad (obligatorio en la máquina del cliente)

XAMPP deja MySQL **escuchando en toda la red con `root` sin contraseña**: cualquiera conectado al mismo wifi puede
entrar a la base. En `C:\xampp\mysql\bin\my.ini`, sección `[mysqld]`:

```ini
bind-address="127.0.0.1"
```

### 3.2 Base y usuario propios (no usar `root`)

Desde `C:\xampp\mysql\bin\mysql.exe -u root`:

```sql
CREATE DATABASE taller_el_flaco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'taller'@'localhost' IDENTIFIED BY 'una-contraseña-larga';
GRANT ALL PRIVILEGES ON taller_el_flaco.* TO 'taller'@'localhost';
FLUSH PRIVILEGES;
```

Y ponerle contraseña a `root`:

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'otra-contraseña-larga';
```

> Si se le pone contraseña a `root`, phpMyAdmin deja de entrar hasta editar
> `C:\xampp\phpMyAdmin\config.inc.php` (`$cfg['Servers'][$i]['password']`). En la máquina del cliente no hace falta
> phpMyAdmin.

Guardar las contraseñas en el gestor de contraseñas, **no** en un archivo dentro del proyecto.

---

## 4. Instalar el proyecto

```bat
cd /d C:\taller
git clone https://github.com/AndresCebay-ADSO/taller-mecanico-elflaco.git
cd taller-mecanico-elflaco

composer install --no-dev --optimize-autoloader
npm ci
npm run build

copy .env.example .env
php artisan key:generate
```

Editar `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://127.0.0.1
APP_TIMEZONE=America/Bogota

LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taller_el_flaco
DB_USERNAME=taller
DB_PASSWORD=una-contraseña-larga

ADMIN_NAME="Nombre del cliente"
ADMIN_EMAIL=correo@del-cliente
ADMIN_PASSWORD=contraseña-fuerte-que-luego-cambia-ella

SESSION_SECURE_COOKIE=false   # true solo si se sirve con HTTPS

RESPALDOS_DIR=C:\taller\respaldos   # el dashboard avisa si los respaldos se detienen (§6)
```

Luego:

```bat
php artisan migrate --force
php artisan db:seed --force          :: configuración, sede principal y administrador
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan taller:verificar
```

> **Nunca** en la máquina del cliente: `migrate:fresh`, `db:wipe` ni `--seeder=DemoSeeder`. Borran todo.

### 4.1 Carga inicial de datos reales

Los seeders de demostración traen datos **inventados**: no se usan en el cliente. Los datos reales se cargan así:

1. En la app, como administrador: **Administración → Importar datos → Descargar plantilla**.
2. El cliente llena el Excel (proveedores, productos con su stock actual, mecánicos, tipos de trabajo). Las hojas vienen
   vacías; los ejemplos están en la hoja «Instrucciones».
3. Se sube el archivo: el sistema lo **revisa sin guardar nada** y muestra los errores fila por fila.
4. Con el archivo sin errores, **Importar**. El stock de cada producto entra como primer lote (queda en trazabilidad).
   Lo que ya exista con el mismo nombre se omite, así que se puede importar por partes.

Productos y mecánicos entran en la **sucursal activa** (selector del menú); proveedores y tipos de trabajo son
globales.

> Para cambiar las columnas de la plantilla: editar `resources/templates/generar_plantilla.py` **y**
> `app/Services/DataImport/ImportSchema.php`, y regenerar con `python resources/templates/generar_plantilla.py`
> (requiere `pip install openpyxl`, solo en la máquina de desarrollo). Una prueba falla si no coinciden.

---

## 5. Servir la app con Apache (arranca solo con Windows)

`php artisan serve` sirve para desarrollo, pero es una ventana que se puede cerrar y atiende una petición a la vez.
En el cliente se usa el Apache de XAMPP.

En `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (el `httpd.conf` de XAMPP ya lo incluye):

```apache
<VirtualHost *:80>
    ServerName 127.0.0.1
    DocumentRoot "C:/taller/taller-mecanico-elflaco/public"
    <Directory "C:/taller/taller-mecanico-elflaco/public">
        AllowOverride All
        Require local
    </Directory>
</VirtualHost>
```

`Require local`: solo se abre desde ese mismo portátil. Si más adelante se quiere usar desde el celular en el wifi del
taller, cambiar por `Require ip 192.168.` (y revisar el firewall de Windows).

**Arranque automático:** abrir el *XAMPP Control Panel* **como administrador** y marcar la casilla **Svc** de Apache y
de MySQL (los instala como servicios de Windows). Después de reiniciar el portátil, deben quedar en verde sin abrir
nada.

**Acceso directo:** crear en el escritorio un acceso a `http://127.0.0.1` con el nombre «Taller».

> Usar siempre **`127.0.0.1`**, no `localhost`: Windows intenta primero IPv6 y cada página tarda ~160 ms más.

---

## 6. Copias de seguridad (obligatorio)

El portátil se puede caer, mojar o perder. Sin copia, se pierde todo el historial. Los scripts están en
`scripts\windows\` y leen sus rutas de `config.bat` (ajustarlo una vez si alguna ruta cambia).

| Script | Qué hace |
| :-- | :-- |
| `respaldo.bat` | Exporta la base, **verifica que quedó completa**, agrega el logo, comprime en `taller_AAAA-MM-DD_HHMMSS.zip`, copia a Google Drive y borra los locales de más de 30 días. Deja `respaldo.log`. |
| `instalar-respaldo-automatico.bat` | Programa el respaldo diario a las 7 p. m.; si el portátil estaba apagado, corre apenas se encienda. |
| `restaurar.bat` | Recupera un respaldo. Antes guarda uno del estado actual y pide escribir RESTAURAR. |

1. Instalar **Google Drive para escritorio** con la cuenta del cliente y crear la carpeta `Respaldos Taller`.
   Revisar la letra de la unidad (normalmente `G:`) y ajustar `RESPALDOS_NUBE` en `config.bat` si es otra.
2. Crear `C:\taller\respaldo.cnf` (**fuera** del proyecto; es la clave de MySQL):

   ```ini
   [client]
   user=taller
   password=una-contraseña-larga
   ```

3. Probar a mano: `scripts\windows\respaldo.bat`. Debe terminar en «Listo» y el `.zip` aparecer en
   `C:\taller\respaldos` y en Google Drive.
4. Programarlo: `scripts\windows\instalar-respaldo-automatico.bat` (otra hora: `instalar-respaldo-automatico.bat 18:30`).
5. En el `.env`, `RESPALDOS_DIR=C:\taller\respaldos`. Con eso, **el dashboard le avisa al administrador si pasan más
   de 3 días sin un respaldo nuevo** (Google Drive cerrado, tarea borrada, MySQL apagado…), y `taller:verificar`
   también lo revisa. Nadie mira el Programador de tareas: el aviso es lo que evita enterarse tarde.
6. **Probar una restauración** antes de entregar: `scripts\windows\restaurar.bat` sin argumentos lista los
   respaldos y pregunta cuál. Para practicar sin tocar los datos reales, en una consola:
   `set DB_NAME=prueba_restauracion` (base creada vacía antes) y luego `scripts\windows\restaurar.bat`.

Si algo falla, `C:\taller\respaldos\respaldo.log` dice qué fue (cada línea empieza con OK, AVISO o ERROR).

---

## 7. Actualizar a una versión nueva

Por AnyDesk: `scripts\windows\actualizar.bat`. Hace, en orden: respaldo (sin respaldo no sigue), modo mantenimiento,
`git pull`, dependencias, interfaz, migraciones, cachés y `taller:verificar`. Si un paso falla, deja el sistema en línea
con lo que tenía y dice qué hacer.

Si `git pull` se queja de cambios locales, **no** forzar: revisar con `git status` qué cambió en esa máquina.

---

## 8. Checklist de entrega

- [ ] `php artisan taller:verificar` sin errores.
- [ ] Reiniciar el portátil: Apache y MySQL arrancan solos; el acceso directo abre el login.
- [ ] Entrar con el administrador y **cambiar la contraseña** desde *Perfil* junto con el cliente.
- [ ] Borrar la línea `ADMIN_PASSWORD` del `.env` y correr `php artisan config:cache`: ya no se usa y deja una contraseña escrita en el disco.
- [ ] Registrar una venta de prueba, una orden con un trabajo y su factura; imprimir la factura. Luego anularlas.
- [ ] `respaldo.bat` a mano: «Listo» y el `.zip` en Google Drive. Tarea programada instalada.
- [ ] Restauración probada con `restaurar.bat` sobre una base de práctica.
- [ ] `RESPALDOS_DIR` en el `.env` y `taller:verificar` muestra «Respaldos: OK».
- [ ] AnyDesk con acceso desatendido y contraseña.
- [ ] Desactivar la suspensión de Windows mientras está enchufado (opcional, evita respaldos perdidos).

---

## 9. Diferencias entre máquinas que ya conocemos

| Síntoma | Causa | Solución |
| :-- | :-- | :-- |
| Cada página tarda en aparecer | OPcache apagado (XAMPP lo trae así) | §2. `taller:verificar` avisa. |
| Todo ~160 ms más lento en un equipo | Se entra por `localhost` | Usar `127.0.0.1` en el acceso directo y en `APP_URL`. |
| Página sin estilos (solo texto) | Falta `npm run build`, o quedó `public/hot` de un `npm run dev` mal cerrado | `npm run build`; borrar `public/hot`. `taller:verificar` lo detecta. |
| Un icono sale en blanco | Solo se empaquetan los iconos cuyo nombre aparece escrito entre comillas en el código (la consola del navegador lo avisa) | Escribir el nombre completo (`'chevron-down'`, no armado por partes) y `npm run build`. Ver `resources/js/lucide-used-icons.js`. |
| Cambié el `.env` y no pasa nada | La configuración está en caché | `php artisan config:cache` después de cada cambio al `.env`. |
| Ventas de la noche con fecha de mañana | Zona horaria distinta de `America/Bogota` | `APP_TIMEZONE=America/Bogota`. Los datos creados antes del 2026-09 están en UTC. |
| Un cálculo da distinto en MySQL que en las pruebas | MySQL devuelve `SUM()` como texto (`'2'`); SQLite como número | Nunca comparar sumas con `===`; convertir con `(int)` / `(float)`. |
| Error 500 sin detalle | `APP_DEBUG=false` (correcto en el cliente) | Ver `storage\logs\laravel-AAAA-MM-DD.log`. |
| Olvidó la contraseña | El sistema no envía correos | `php artisan taller:restablecer-clave correo@del-usuario` muestra una temporal; que la cambie en *Perfil*. Volver a correr `db:seed` **no** la cambia. |
| «Permission denied» en `storage` | Carpeta copiada desde otro usuario | Dar permiso de escritura en `storage\` y `bootstrap\cache\`. |
| Apache no arranca | Puerto 80 ocupado (IIS, Skype, «Servicio de publicación World Wide Web») | Detener ese servicio o cambiar `Listen 80` en `httpd.conf`. |
| MySQL no arranca | Puerto 3306 ocupado por otro MySQL instalado | Detener el otro servicio o cambiar el puerto (y `DB_PORT`). |
| `composer install` muy lento | El antivirus revisa cada archivo | Excluir `C:\taller` y `C:\xampp` del análisis en tiempo real. |
| La documentación pide MySQL 8 / MariaDB 10.6 | XAMPP trae MariaDB 10.4 | Verificado: funciona con 10.4 (pruebas en verde). |
| Sin internet, la letra se ve distinta | — | Ya no pasa: fuente e iconos vienen dentro de la app. |
