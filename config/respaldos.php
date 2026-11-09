<?php

return [

    'pg_bin' => env('PG_BIN', ''),

    'carpeta' => storage_path('app/private/respaldos'),

    'conservar' => (int) env('RESPALDOS_CONSERVAR', 7),

    'base_restauracion' => env('RESPALDO_BASE_PRUEBA', 'clinica_diente_feliz_restaurada'),

];
