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

    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'ctype', 'tokenizer', 'xml', 'bcmath'];

    /** @var array<int, array{string, string, string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->checkPhp();
        $this->checkEnv();
        $this->checkDatabase();
        $this->checkFiles();

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
        $this->add(version_compare(PHP_VERSION, '8.2.0', '>=') ? 'OK' : 'ERROR', 'PHP 8.2 o superior', PHP_VERSION);

        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn ($ext) => ! extension_loaded($ext)));
        $this->add($missing ? 'ERROR' : 'OK', 'Extensiones de PHP', $missing ? 'Faltan: ' . implode(', ', $missing) . ' (activar en php.ini)' : 'Completas');

        // php artisan serve corre en modo CLI: ahí OPcache depende de opcache.enable_cli.
        $opcacheOn = extension_loaded('Zend OPcache')
            && (bool) ini_get(PHP_SAPI === 'cli' ? 'opcache.enable_cli' : 'opcache.enable');
        $this->add($opcacheOn ? 'OK' : 'AVISO', 'OPcache', $opcacheOn ? 'Activo' : 'Apagado: cada página tarda ~4 veces más (ver php.ini en la guía)');
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

    private function add(string $status, string $check, string $detail): void
    {
        $this->rows[] = [$status, $check, $detail];
    }
}
