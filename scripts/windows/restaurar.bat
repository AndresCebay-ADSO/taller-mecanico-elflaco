@echo off
rem ============================================================================
rem  Restaura un respaldo hecho por respaldo.bat (base de datos + logo).
rem    restaurar.bat                      -> lista los respaldos y pregunta cual
rem    restaurar.bat taller_2026-11-14_1900.zip
rem  ANTES de restaurar hace un respaldo del estado actual: si se restauro el
rem  archivo equivocado, ese respaldo permite volver atras.
rem  La base se BORRA y se vuelve a crear antes de cargar el respaldo (asi no
rem  quedan tablas de una version mas nueva) y al final se corre migrate.
rem
rem  MODO PRACTICA: si DB_NAME no es la base real (DB_REAL en config.bat), se
rem  restaura solo en esa base. No hace respaldo de seguridad, no toca el logo ni
rem  la base real y no corre migrate. Ejemplo en una consola:
rem    set DB_NAME=prueba_restauracion
rem    scripts\windows\restaurar.bat
rem ============================================================================
setlocal EnableExtensions
call "%~dp0config.bat"

set "PRACTICA="
if /i not "%DB_NAME%"=="%DB_REAL%" set "PRACTICA=1"

set "ARCHIVO=%~1"
if not "%ARCHIVO%"=="" goto :resolver

echo Respaldos disponibles (el mas reciente primero):
echo.
dir /b /o-d "%RESPALDOS_LOCAL%\taller_*.zip" 2>nul
if errorlevel 1 (
    echo No hay respaldos en %RESPALDOS_LOCAL%.
    exit /b 1
)
echo.
set /p "ARCHIVO=Escriba (o pegue) el nombre del respaldo a restaurar: "

:resolver
set "RUTA="
if exist "%RESPALDOS_LOCAL%\%ARCHIVO%" set "RUTA=%RESPALDOS_LOCAL%\%ARCHIVO%"
if not defined RUTA if exist "%ARCHIVO%" set "RUTA=%ARCHIVO%"
if not defined RUTA (
    echo No se encontro el respaldo "%ARCHIVO%".
    exit /b 1
)

echo.
echo Se va a restaurar: %RUTA%
if defined PRACTICA goto :aviso_practica
echo Los datos ACTUALES del sistema se reemplazan por los de ese respaldo.
echo (Antes se guarda un respaldo de como esta todo ahora.)
goto :confirmar

:aviso_practica
echo MODO PRACTICA: se carga en la base %DB_NAME%. La base real %DB_REAL% y el logo no se tocan.

:confirmar
echo.
if not defined CONFIRMA set /p "CONFIRMA=Escriba RESTAURAR para continuar: "
if /i not "%CONFIRMA%"=="RESTAURAR" (
    echo Cancelado. No se cambio nada.
    exit /b 1
)

echo.
echo 1/4 Abriendo el respaldo...
rem Se abre ANTES del respaldo de seguridad: asi ese respaldo nunca puede pisar el archivo que se restaura.
set "TRABAJO=%RESPALDOS_LOCAL%\tmp_restaurar"
if exist "%TRABAJO%" rmdir /s /q "%TRABAJO%"
powershell -NoProfile -Command "Expand-Archive -Path '%RUTA%' -DestinationPath '%TRABAJO%' -Force"
if not exist "%TRABAJO%\base_de_datos.sql" goto :no_es_respaldo
findstr /c:"-- Dump completed" "%TRABAJO%\base_de_datos.sql" >nul
if errorlevel 1 goto :incompleto

if defined PRACTICA (
    echo 2/4 Practica: sin respaldo de seguridad ^(la base real no se toca^).
    goto :cargar
)

echo 2/4 Respaldo de seguridad del estado actual...
call "%~dp0respaldo.bat"
if errorlevel 1 goto :sin_seguridad
rem Nadie debe registrar nada mientras se cambia la base: se perderia.
"%PHP_EXE%" "%TALLER_DIR%\artisan" down --retry=15 >nul

:cargar
echo 3/4 Cargando la base de datos %DB_NAME% (se borra y se vuelve a crear)...
"%MYSQL_BIN%\mysql.exe" --defaults-extra-file="%DB_CNF%" -e "DROP DATABASE IF EXISTS `%DB_NAME%`; CREATE DATABASE `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 goto :error_carga
"%MYSQL_BIN%\mysql.exe" --defaults-extra-file="%DB_CNF%" --default-character-set=utf8mb4 "%DB_NAME%" < "%TRABAJO%\base_de_datos.sql"
if errorlevel 1 goto :error_carga

if defined PRACTICA goto :fin_practica

if exist "%TRABAJO%\branding" (
    xcopy /e /i /q /y "%TRABAJO%\branding" "%TALLER_DIR%\storage\app\private\branding" >nul
)
rmdir /s /q "%TRABAJO%"

echo 4/4 Poniendo la base al dia con esta version del sistema...
"%PHP_EXE%" "%TALLER_DIR%\artisan" migrate --force
if errorlevel 1 goto :error_migrar
"%PHP_EXE%" "%TALLER_DIR%\artisan" up >nul

echo.
echo Restaurado. Revision del inventario:
"%PHP_EXE%" "%TALLER_DIR%\artisan" inventory:check
exit /b 0

:fin_practica
rmdir /s /q "%TRABAJO%"
echo 4/4 Practica restaurada en %DB_NAME%. Registros cargados:
"%MYSQL_BIN%\mysql.exe" --defaults-extra-file="%DB_CNF%" "%DB_NAME%" -e "SELECT (SELECT COUNT(*) FROM sales) AS ventas, (SELECT COUNT(*) FROM invoices) AS facturas, (SELECT COUNT(*) FROM products) AS productos, (SELECT COUNT(*) FROM service_orders) AS ordenes;"
echo Compare estas cifras con las del sistema. Al terminar puede borrar la base %DB_NAME%.
exit /b 0

rem ---- Errores (fuera de bloques: los parentesis del texto no rompen nada) ----
:no_es_respaldo
echo El archivo no parece un respaldo del taller: no trae base_de_datos.sql. No se cambio nada.
if exist "%TRABAJO%" rmdir /s /q "%TRABAJO%"
exit /b 1

:incompleto
echo La base de datos de ese respaldo esta incompleta. No se cambio nada.
rmdir /s /q "%TRABAJO%"
exit /b 1

:sin_seguridad
echo No se pudo hacer el respaldo de seguridad: no se restaura nada.
rmdir /s /q "%TRABAJO%"
exit /b 1

:error_carga
if defined PRACTICA (
    echo ERROR al cargar la practica en %DB_NAME%. Revise que exista y que el usuario de respaldo.cnf tenga permisos sobre ella ^(guia, seccion 6^).
    exit /b 1
)
echo ERROR al cargar la base. El sistema queda en mantenimiento para que nadie registre nada.
echo Para volver a como estaba, restaure el respaldo de seguridad recien creado,
echo que es el mas reciente en %RESPALDOS_LOCAL%.
exit /b 1

:error_migrar
echo ERROR al poner la base al dia (migrate). El sistema queda en mantenimiento.
echo Si el respaldo es de una version MAS NUEVA que el codigo instalado, actualice primero con actualizar.bat.
exit /b 1
