@echo off
rem ============================================================================
rem  Restaura un respaldo hecho por respaldo.bat (base de datos + logo).
rem    restaurar.bat                      -> lista los respaldos y pregunta cual
rem    restaurar.bat taller_2026-11-14_1900.zip
rem  ANTES de restaurar hace un respaldo del estado actual: si se restauro el
rem  archivo equivocado, ese respaldo permite volver atras.
rem ============================================================================
setlocal EnableExtensions
call "%~dp0config.bat"

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
echo Los datos ACTUALES del sistema se reemplazan por los de ese respaldo.
echo (Antes se guarda un respaldo de como esta todo ahora.)
echo.
if not defined CONFIRMA set /p "CONFIRMA=Escriba RESTAURAR para continuar: "
if /i not "%CONFIRMA%"=="RESTAURAR" (
    echo Cancelado. No se cambio nada.
    exit /b 1
)

echo.
echo 1/3 Abriendo el respaldo...
rem Se abre ANTES del respaldo de seguridad: asi ese respaldo nunca puede pisar el archivo que se restaura.
set "TRABAJO=%RESPALDOS_LOCAL%\tmp_restaurar"
if exist "%TRABAJO%" rmdir /s /q "%TRABAJO%"
powershell -NoProfile -Command "Expand-Archive -Path '%RUTA%' -DestinationPath '%TRABAJO%' -Force"
if not exist "%TRABAJO%\base_de_datos.sql" goto :no_es_respaldo
findstr /c:"-- Dump completed" "%TRABAJO%\base_de_datos.sql" >nul
if errorlevel 1 goto :incompleto

echo 2/3 Respaldo de seguridad del estado actual...
call "%~dp0respaldo.bat"
if errorlevel 1 goto :sin_seguridad

echo 3/3 Cargando la base de datos %DB_NAME%...
"%MYSQL_BIN%\mysql.exe" --defaults-extra-file="%DB_CNF%" --default-character-set=utf8mb4 "%DB_NAME%" < "%TRABAJO%\base_de_datos.sql"
if errorlevel 1 goto :error_carga

if exist "%TRABAJO%\branding" (
    xcopy /e /i /q /y "%TRABAJO%\branding" "%TALLER_DIR%\storage\app\private\branding" >nul
)
rmdir /s /q "%TRABAJO%"

echo.
echo Restaurado. Revision del inventario:
"%PHP_EXE%" "%TALLER_DIR%\artisan" inventory:check
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
echo ERROR al cargar la base. Para volver a como estaba, restaure el respaldo de seguridad recien creado,
echo que es el mas reciente en %RESPALDOS_LOCAL%.
exit /b 1
