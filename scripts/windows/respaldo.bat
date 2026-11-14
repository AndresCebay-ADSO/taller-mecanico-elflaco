@echo off
rem ============================================================================
rem  Respaldo del taller: base de datos + logo, comprimido en un .zip con fecha.
rem  - Verifica que el volcado quedo completo (si no, no lo guarda como bueno).
rem  - Copia a Google Drive si la carpeta existe; si no, avisa y conserva el local.
rem  - Borra los respaldos locales de mas de DIAS_LOCAL dias.
rem  Sale con codigo 1 si falla: el Programador de tareas lo muestra como error.
rem ============================================================================
setlocal EnableExtensions
call "%~dp0config.bat"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmmss"') do set "FECHA=%%i"
if not exist "%RESPALDOS_LOCAL%" mkdir "%RESPALDOS_LOCAL%"
set "LOG=%RESPALDOS_LOCAL%\respaldo.log"
set "TRABAJO=%RESPALDOS_LOCAL%\tmp_%FECHA%"
set "ZIP=%RESPALDOS_LOCAL%\taller_%FECHA%.zip"

if not exist "%DB_CNF%" (
    call :fallo "No existe %DB_CNF% con el usuario y la clave de MySQL. Ver docs\INSTALACION_LOCAL.md seccion 6."
    exit /b 1
)

if exist "%TRABAJO%" rmdir /s /q "%TRABAJO%"
mkdir "%TRABAJO%"

echo Exportando la base de datos %DB_NAME%...
"%MYSQL_BIN%\mysqldump.exe" --defaults-extra-file="%DB_CNF%" --single-transaction --routines --triggers --default-character-set=utf8mb4 "%DB_NAME%" > "%TRABAJO%\base_de_datos.sql" 2> "%TRABAJO%\error.txt"
if errorlevel 1 goto :error_volcado

rem mysqldump escribe esta linea al terminar: sin ella, el archivo quedo cortado.
findstr /c:"-- Dump completed" "%TRABAJO%\base_de_datos.sql" >nul
if errorlevel 1 (
    call :fallo "El volcado quedo incompleto (falta la marca final). No se guardo."
    rmdir /s /q "%TRABAJO%"
    exit /b 1
)
del "%TRABAJO%\error.txt" 2>nul

if exist "%TALLER_DIR%\storage\app\private\branding" (
    xcopy /e /i /q /y "%TALLER_DIR%\storage\app\private\branding" "%TRABAJO%\branding" >nul
)

echo Comprimiendo...
powershell -NoProfile -Command "Compress-Archive -Path '%TRABAJO%\*' -DestinationPath '%ZIP%' -Force"
if errorlevel 1 (
    call :fallo "No se pudo crear %ZIP%."
    exit /b 1
)
rmdir /s /q "%TRABAJO%"

for %%F in ("%ZIP%") do set "TAMANO=%%~zF"

if exist "%RESPALDOS_NUBE%\" (
    copy /y "%ZIP%" "%RESPALDOS_NUBE%\" >nul
    if errorlevel 1 (
        call :registrar "AVISO  %FECHA%  respaldo local OK (%TAMANO% bytes) pero NO se pudo copiar a %RESPALDOS_NUBE%"
    ) else (
        call :registrar "OK     %FECHA%  taller_%FECHA%.zip (%TAMANO% bytes) local + nube"
    )
) else (
    call :registrar "AVISO  %FECHA%  respaldo local OK (%TAMANO% bytes); no se encontro %RESPALDOS_NUBE% (Google Drive sin abrir o sin internet)"
)

forfiles /p "%RESPALDOS_LOCAL%" /m taller_*.zip /d -%DIAS_LOCAL% /c "cmd /c del @path" 2>nul

echo Listo: %ZIP%
exit /b 0

:error_volcado
rem Fuera de un bloque ( ): asi %DETALLE% ya tiene el texto del error al usarse.
set "DETALLE=sin detalle"
set /p DETALLE=<"%TRABAJO%\error.txt"
rem Las comillas del mensaje de MySQL romperian el texto: se cambian por comillas simples.
set "DETALLE=%DETALLE:"='%"
call :fallo "mysqldump fallo. Esta encendido MySQL? Detalle: %DETALLE%"
rmdir /s /q "%TRABAJO%"
exit /b 1

:registrar
echo %~1
>>"%LOG%" echo %~1
exit /b 0

:fallo
echo ERROR: %~1
>>"%LOG%" echo ERROR  %FECHA%  %~1
exit /b 0
