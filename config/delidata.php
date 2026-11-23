<?php

return [
    /*
    | Muestra en el login los accesos rápidos a las cuentas de demostración.
    | Útil para el portafolio; en producción ponlo en false (DELIDATA_DEMO=false).
    */
    'demo' => (bool) env('DELIDATA_DEMO', env('APP_ENV', 'production') !== 'production'),

    'contacto' => env('DELIDATA_CONTACTO', 'ventas@delidata.mx'),
];
