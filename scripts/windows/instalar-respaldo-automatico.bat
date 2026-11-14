@echo off
rem ============================================================================
rem  Programa respaldo.bat todos los dias a las 7 p. m. en el Programador de tareas.
rem  - Si a esa hora el portatil estaba apagado, corre apenas se encienda.
rem  - Corre aunque el portatil este con bateria.
rem  Volver a ejecutarlo reemplaza la tarea (sirve para cambiar la hora).
rem ============================================================================
setlocal EnableExtensions
set "HORA=%~1"
if "%HORA%"=="" set "HORA=19:00"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$accion = New-ScheduledTaskAction -Execute '%~dp0respaldo.bat' -WorkingDirectory '%~dp0';" ^
  "$cuando = New-ScheduledTaskTrigger -Daily -At '%HORA%';" ^
  "$ajustes = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 30);" ^
  "Register-ScheduledTask -TaskName 'Taller - Respaldo diario' -Action $accion -Trigger $cuando -Settings $ajustes -Description 'Respaldo diario de MotoTaller (scripts\windows\respaldo.bat)' -Force | Out-Null"
if errorlevel 1 (
    echo No se pudo crear la tarea. Intente ejecutar este archivo como administrador.
    exit /b 1
)

echo Tarea "Taller - Respaldo diario" programada a las %HORA%.
echo Si el portatil esta apagado a esa hora, el respaldo se hace apenas se encienda.
exit /b 0
