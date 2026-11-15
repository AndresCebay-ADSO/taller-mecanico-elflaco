<?php

return [

    /*
    | Carpeta donde scripts\windows\respaldo.bat deja los respaldos (taller_*.zip).
    | Con ella configurada, el dashboard avisa al administrador si los respaldos se detienen
    | (Google Drive desconectado, tarea programada borrada, MySQL caído…). Vacía: no se revisa.
    */
    'respaldos_dir' => env('RESPALDOS_DIR'),

    /*
    | Carpeta de Google Drive a donde respaldo.bat copia cada respaldo (la misma RESPALDOS_NUBE de
    | scripts\windows\config.bat). Con ella configurada, el dashboard avisa si la copia a la nube se
    | detiene aunque los respaldos locales sigan bien. Vacía: no se revisa.
    */
    'respaldos_nube' => env('RESPALDOS_NUBE'),

    // Días sin un respaldo nuevo antes de avisar.
    'respaldos_dias_aviso' => (int) env('RESPALDOS_DIAS_AVISO', 3),

    /*
    | httpd.conf de Apache: taller:verificar revisa que Apache escuche solo en 127.0.0.1
    | (sin esto, cualquiera en el wifi puede abrir el sistema). Si no existe, no se revisa.
    */
    'apache_conf' => env('APACHE_CONF', 'C:\xampp\apache\conf\httpd.conf'),

];
