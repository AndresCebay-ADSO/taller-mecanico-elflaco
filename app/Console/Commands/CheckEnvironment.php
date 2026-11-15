<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Revisa que la máquina donde corre el taller esté bien configurada (docs/INSTALACION_LOCAL.md).
 * Sale con error si algo impide trabajar; los avisos no bloquean, pero conviene corregirlos.
 */
class CheckEnvironment extends Command
{
    protected $signature = 'taller:verificar';

    protected $description = 'Revisa PHP, extensiones, .env, base de datos y archivos antes de usar el sistema en una máquina';

    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'ctype', 'tokenizer', 'xml', 'bcmath', 'zip'];

    /**
     * Primera versión de cada rama de PHP sin fallas de seguridad conocidas. Actualizar cuando php.net publique un
     * aviso de seguridad (https://www.php.net). Última revisión: 2026-11-14, por CVE-2026-109618 y CVE-2026-109619
     * («PHP2Shell», ejecución remota de código, corregidas el 21-oct-2026).
     */
    private const SAFE_PHP = ['8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

    /** @var array<int, array{string, string, string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->checkPhp();
        $this->checkEnv();
        $this->checkDatabase();
        $this->checkFiles();
        $this->checkBackups();
        $this->checkNetwork();

        $this->table(['', 'Revisión', 'Detalle'], $this->rows);

        $errors = collect($this->rows)->where(0, 'ERROR')->count();
        $warnings = collect($this->rows)->where(0, 'AVISO')->count();

        if ($errors > 0) {
            $this->error("{$errors} error(es): corrígelos antes de usar el sistema en esta máquina.");

            return self::FAILURE;
        }

        $warnings > 0
            ? $this->warn("Sin errores, con {$warnings} aviso(s).")
            : $this->info('Todo en orden.');

        return self::SUCCESS;
    }

    private function checkPhp(): void
    {
        $this->add(version_compare(PHP_VERSION, '8.3.0', '>=') ? 'OK' : 'ERROR', 'PHP 8.3 o superior', PHP_VERSION);
        $this->add(...self::phpSecurity(PHP_VERSION));

        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn ($ext) => ! extension_loaded($ext)));
        $this->add($missing ? 'ERROR' : 'OK', 'Extensiones de PHP', $missing ? 'Faltan: ' . implode(', ', $missing) . ' (activar en php.ini)' : 'Completas');

        // php artisan serve corre en modo CLI: ahí OPcache depende de opcache.enable_cli.
        $opcacheOn = extension_loaded('Zend OPcache')
            && (bool) ini_get(PHP_SAPI === 'cli' ? 'opcache.enable_cli' : 'opcache.enable');
        $this->add($opcacheOn ? 'OK' : 'AVISO', 'OPcache', $opcacheOn ? 'Activo' : 'Apagado: cada página tarda ~4 veces más (ver php.ini en la guía)');

        // Mitigación de CVE-2026-115560 que recomienda php.net hasta el próximo parche.
        $uploadProgress = (bool) ini_get('session.upload_progress.enabled');
        $this->add($uploadProgress ? 'AVISO' : 'OK', 'session.upload_progress', $uploadProgress ? 'Encendido: poner session.upload_progress.enabled = Off en php.ini (guía §2)' : 'Apagado');
    }

    /**
     * Si la versión de PHP tiene fallas de seguridad conocidas o su rama ya no recibe parches.
     *
     * @return array{string, string, string} estado, revisión y detalle
     */
    public static function phpSecurity(string $version): array
    {
        $branch = implode('.', array_slice(explode('.', $version), 0, 2));
        $safe = self::SAFE_PHP[$branch] ?? null;

        if ($safe === null) {
            $newer = version_compare($branch, (string) max(array_keys(self::SAFE_PHP)), '>');

            return [$newer ? 'OK' : 'AVISO', 'Seguridad de PHP', $newer
                ? "{$version} (más nueva que la lista revisada)"
                : "{$version}: esa rama ya no recibe parches de seguridad. Instalar el XAMPP más reciente (guía §1)"];
        }

        $patched = version_compare($version, $safe, '>=');

        return [$patched ? 'OK' : 'AVISO', 'Seguridad de PHP', $patched
            ? "{$version}: sin fallas conocidas"
            : "{$version} tiene fallas graves conocidas (corregidas en {$safe}): instalar el XAMPP más reciente (guía §1)"];
    }

    private function checkEnv(): void
    {
        $this->add(config('app.key') ? 'OK' : 'ERROR', 'APP_KEY', config('app.key') ? 'Definida' : 'Vacía: ejecutar php artisan key:generate');

        $production = app()->environment('production');
        if ($production && config('app.debug')) {
            $this->add('ERROR', 'APP_DEBUG', 'true en producción: muestra detalles internos ante cualquier error');
        } else {
            $this->add($production ? 'OK' : 'AVISO', 'APP_ENV / APP_DEBUG', app()->environment() . ' / ' . (config('app.debug') ? 'true' : 'false') . ($production ? '' : ' (en la máquina del cliente: production / false)'));
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        $this->add($host === 'localhost' ? 'AVISO' : 'OK', 'APP_URL', config('app.url') . ($host === 'localhost' ? ' (usar 127.0.0.1: «localhost» intenta IPv6 primero y suma ~160 ms)' : ''));

        $this->add(config('app.timezone') === 'America/Bogota' ? 'OK' : 'ERROR', 'Zona horaria', config('app.timezone') . (config('app.timezone') === 'America/Bogota' ? '' : ' (debe ser America/Bogota: las ventas de la noche quedan con fecha del día siguiente)'));

        // Pasos de la guía que se hacen a mano y se olvidan fácil (§3 y §8).
        if ($production && config('auth.admin.password')) {
            $this->add('AVISO', 'ADMIN_PASSWORD', 'Sigue en el .env: borrar la línea y ejecutar php artisan config:cache (solo sirve para crear el primer usuario)');
        }
        if ($production && config('database.connections.' . config('database.default') . '.username') === 'root') {
            $this->add('AVISO', 'Usuario de la base', 'La app entra como root: crear el usuario «taller» con permisos solo sobre su base (guía §3)');
        }

        $cached = app()->configurationIsCached();
        $this->add('OK', 'Configuración en caché', $cached ? 'Sí: tras editar .env ejecutar php artisan config:cache' : 'No');
    }

    private function checkDatabase(): void
    {
        try {
            $pdo = DB::connection()->getPdo();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $this->add('OK', 'Conexión a la base de datos', config('database.default') . ' · ' . DB::connection()->getDatabaseName() . ' · ' . $version);
        } catch (Throwable $e) {
            $this->add('ERROR', 'Conexión a la base de datos', 'Falla: ' . str($e->getMessage())->limit(120) . ' (¿MySQL encendido? ¿datos DB_* del .env?)');

            return;
        }

        if (! Schema::hasTable('migrations')) {
            $this->add('ERROR', 'Migraciones', 'La base está vacía: ejecutar php artisan migrate --force');

            return;
        }

        Artisan::call('migrate:status', ['--pending' => true]);
        $pending = substr_count(Artisan::output(), 'Pending');
        $this->add($pending ? 'ERROR' : 'OK', 'Migraciones', $pending ? "{$pending} pendiente(s): ejecutar php artisan migrate --force" : 'Al día');

        $admins = User::where('is_admin', true)->where('is_active', true)->count();
        $this->add($admins ? 'OK' : 'ERROR', 'Administrador activo', $admins ? "{$admins}" : 'No hay: ejecutar php artisan db:seed --force');

        $branches = Branch::active()->count();
        $this->add($branches ? 'OK' : 'ERROR', 'Sucursal activa', $branches ? "{$branches}" : 'No hay ninguna: ejecutar php artisan db:seed --force');
    }

    private function checkFiles(): void
    {
        foreach (['storage', 'storage/logs', 'storage/framework/views', 'bootstrap/cache'] as $dir) {
            if (! is_writable(base_path($dir))) {
                $this->add('ERROR', "Permiso de escritura en {$dir}", 'Sin permiso: Laravel no puede guardar sesiones, logs ni vistas');
            }
        }

        $manifest = file_exists(public_path('build/manifest.json'));
        $this->add($manifest ? 'OK' : 'ERROR', 'Interfaz compilada (public/build)', $manifest ? 'Presente' : 'Falta: ejecutar npm ci && npm run build');

        // public/hot queda si `npm run dev` se cerró mal: la app busca estilos en un servidor de Vite que ya no existe.
        $hot = file_exists(public_path('hot'));
        $this->add($hot ? 'ERROR' : 'OK', 'Sin public/hot', $hot ? 'Existe: borrar public/hot (la página saldría sin estilos)' : 'Correcto');
    }

    private function checkBackups(): void
    {
        $backups = \App\Support\BackupStatus::fromConfig();

        if (! $backups->isConfigured()) {
            $this->add('AVISO', 'Respaldos', 'RESPALDOS_DIR sin configurar en .env: la app no puede avisar si los respaldos se detienen');

            return;
        }

        $this->add($backups->needsAttention() ? 'AVISO' : 'OK', 'Respaldos', $backups->describe());
    }

    /** Base de datos y Apache solo deben escuchar en este equipo: si no, cualquiera en el wifi llega a ellos. */
    private function checkNetwork(): void
    {
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                $bind = (string) (DB::selectOne('SELECT @@bind_address AS bind')->bind ?? '');
                $local = in_array($bind, ['127.0.0.1', 'localhost', '::1'], true);
                $this->add($local ? 'OK' : 'AVISO', 'Base de datos solo en este equipo', $local
                    ? $bind
                    : 'Escucha en «' . ($bind ?: 'toda la red') . '»: poner bind-address=127.0.0.1 en my.ini (guía §3.1)');
            } catch (Throwable) {
                // Sin conexión ya se reportó en checkDatabase().
            }
        }

        $conf = (string) config('taller.apache_conf');
        if ($conf === '' || ! is_file($conf)) {
            return;
        }

        $files = [$conf, dirname($conf) . DIRECTORY_SEPARATOR . 'extra' . DIRECTORY_SEPARATOR . 'httpd-ssl.conf'];
        $open = [];
        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }
            foreach (preg_split('/\R/', (string) file_get_contents($file)) as $line) {
                if (preg_match('/^\s*Listen\s+(\S+)/i', $line, $match) && ! preg_match('/^(127\.0\.0\.1|localhost|\[::1\]):/i', $match[1])) {
                    $open[] = $match[1];
                }
            }
        }

        $this->add($open ? 'AVISO' : 'OK', 'Apache solo en este equipo', $open
            ? 'Escucha en ' . implode(', ', array_unique($open)) . ': usar Listen 127.0.0.1:80 en httpd.conf (guía §3.1)'
            : 'Listen 127.0.0.1');
    }

    private function add(string $status, string $check, string $detail): void
    {
        $this->rows[] = [$status, $check, $detail];
    }
}
