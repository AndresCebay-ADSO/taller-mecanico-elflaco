@echo off
rem ============================================================================
rem  Actualiza el taller a la ultima version de GitHub.
rem  1) respaldo  2) modo mantenimiento  3) codigo, dependencias, interfaz y base
rem  Si un paso falla, el sistema vuelve a quedar en linea y se indica que hacer.
rem ============================================================================
setlocal EnableExtensions
call "%~dp0config.bat"
cd /d "%TALLER_DIR%" || exit /b 1

echo [1/7] Respaldo antes de actualizar...
call "%~dp0respaldo.bat"
if errorlevel 1 (
    echo Sin respaldo no se actualiza. Revise el error de arriba.
    exit /b 1
)

echo [2/7] Modo mantenimiento...
"%PHP_EXE%" artisan down --retry=15 || goto :fallo

echo [3/7] Codigo nuevo...
git pull --ff-only || goto :fallo

echo [4/7] Dependencias de PHP...
call composer install --no-dev --optimize-autoloader --no-interaction || goto :fallo

echo [5/7] Interfaz...
call npm ci || goto :fallo
call npm run build || goto :fallo

echo [6/7] Base de datos...
"%PHP_EXE%" artisan migrate --force || goto :fallo_base

echo [7/7] Caches...
"%PHP_EXE%" artisan config:cache || goto :fallo
"%PHP_EXE%" artisan route:cache || goto :fallo
"%PHP_EXE%" artisan view:cache || goto :fallo

"%PHP_EXE%" artisan up
echo.
"%PHP_EXE%" artisan taller:verificar
exit /b 0

:fallo_base
echo.
echo ERROR al actualizar la base de datos. El codigo nuevo ya esta, pero la base quedo a medias.
echo Para volver atras: restaurar.bat con el respaldo recien creado y "git reset --hard ORIG_HEAD".
"%PHP_EXE%" artisan up
exit /b 1

:fallo
echo.
echo ERROR en el paso de arriba. El sistema vuelve a quedar en linea con lo que tenia.
echo Si fallo "git pull": hay cambios locales en esta maquina; revise con "git status" (no forzar).
"%PHP_EXE%" artisan up
exit /b 1
