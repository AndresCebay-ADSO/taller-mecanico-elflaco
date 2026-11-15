@echo off
rem ============================================================================
rem  Configuracion de los scripts del taller (respaldo, restaurar, actualizar).
rem  Ajustar estas rutas UNA vez en cada maquina. Los demas scripts la leen.
rem  Cada valor se puede sobreescribir antes de llamar al script (util en pruebas).
rem ============================================================================

rem Carpeta del proyecto
if not defined TALLER_DIR set "TALLER_DIR=C:\taller\taller-mecanico-elflaco"

rem Programas de XAMPP
if not defined MYSQL_BIN set "MYSQL_BIN=C:\xampp\mysql\bin"
if not defined PHP_EXE set "PHP_EXE=C:\xampp\php\php.exe"

rem Base de datos y archivo con su usuario y clave (fuera del proyecto, ver docs\INSTALACION_LOCAL.md)
rem DB_REAL es la base del sistema. DB_NAME es donde trabajan los scripts: si es otra (por ejemplo
rem prueba_restauracion), restaurar.bat entra en modo practica y no toca la base real.
if not defined DB_REAL set "DB_REAL=taller_el_flaco"
if not defined DB_NAME set "DB_NAME=%DB_REAL%"
if not defined DB_CNF set "DB_CNF=C:\taller\respaldo.cnf"

rem Donde quedan los respaldos. La carpeta de la nube puede no existir (sin internet): se avisa y sigue.
if not defined RESPALDOS_LOCAL set "RESPALDOS_LOCAL=C:\taller\respaldos"
if not defined RESPALDOS_NUBE set "RESPALDOS_NUBE=G:\Mi unidad\Respaldos Taller"

rem Dias que se conservan los respaldos en el portatil (en la nube no se borran)
if not defined DIAS_LOCAL set "DIAS_LOCAL=30"
