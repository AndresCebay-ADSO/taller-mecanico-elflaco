<?php

return [

    /*
    | Carpeta donde scripts\windows\respaldo.bat deja los respaldos (taller_*.zip).
    | Con ella configurada, el dashboard avisa al administrador si los respaldos se detienen
    | (Google Drive desconectado, tarea programada borrada, MySQL caído…). Vacía: no se revisa.
    */
    'respaldos_dir' => env('RESPALDOS_DIR'),

    // Días sin un respaldo nuevo antes de avisar.
    'respaldos_dias_aviso' => (int) env('RESPALDOS_DIAS_AVISO', 3),

];
