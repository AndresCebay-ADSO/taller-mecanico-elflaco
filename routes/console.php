<?php

// Sin tareas programadas: en Windows nadie corre schedule:run. La revisión diaria del inventario
// (inventory:check) la hace scripts/windows/respaldo.bat, que sí se ejecuta cada día, y taller:verificar
// también la muestra.
