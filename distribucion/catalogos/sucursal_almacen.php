<?php
// distribucion/catalogos/sucursal_almacen.php
// Estructura para reportes REQ 6.50 y 4.50:
//   Sucursal (HUAJUAPAN | ISTMO-COSTA | V. CENTRAL)
//     └── Almacén rural (nombre tal como aparece en lecheria.ALMACEN_RURAL)
//
// El ORDEN se respeta al generar la hoja "POR ALMACEN" y "TOTAL".

return [
    'sucursales' => [
        'HUAJUAPAN' => [
            'CHALCATONGO',
            'COIXTLAHUACA',
            'CONSTANCIA DEL ROSARIO',
            'HUAJOLOTITLAN',
            'TACACHE DE MINA',
            'TLAXIACO',
            'TECOMAXTLAHUACA',
            'YANHUITLAN',
        ],
        'ISTMO-COSTA' => [
            'MORRO MAZATAN',
            'PALOMARES',
            'SANTIAGO LAOLLAGA',
            'SANTIAGO NILTEPEC',
            'EL TOMATAL',
            'SAN ANDRES HUAXPALTEPEC',
            'SANTA MARIA HUATULCO',
            'LA REFORMA YAUTEPEC',
            'LOS IDEALES',
            'PUEBLO NUEVO, TUX.',
        ],
        'V. CENTRAL' => [
            'VALLES CENTRALES',
            'SAN ANDRES HIDALGO',
            'SAN JOSE DEL CHILAR',
            'TEOTITLAN DE FLORES MAGON',
            'AYUTLA MIXES',
            'IXTLAN DE JUAREZ',
            'MAGDALENA OCOTLAN',
            'SANTO TOMAS TAMAZULAPAN',
            'SAN PEDRO JUCHATENGO',
            'SANTA MARIA LACHIXIO',
            'MATATLAN',
            'CUAJIMOLOYAS',
        ],
    ],

    // Aliases (nombre BD → nombre canónico del catálogo)
    'alias' => [
        'TOMATAL'                  => 'EL TOMATAL',
        'HUAXPALTEPEC'             => 'SAN ANDRES HUAXPALTEPEC',
        'STA. MA. HUATULCO'        => 'SANTA MARIA HUATULCO',
        'STA MA HUATULCO'          => 'SANTA MARIA HUATULCO',
        'REFORMA YAUTEPEC'         => 'LA REFORMA YAUTEPEC',
        'IDEALES'                  => 'LOS IDEALES',
        'PUEBLO NUEVO'             => 'PUEBLO NUEVO, TUX.',
        'PUEBLO NUEVO TUXTEPEC'    => 'PUEBLO NUEVO, TUX.',
        'TEOTITLAN DE FLORES'      => 'TEOTITLAN DE FLORES MAGON',
        'TAMAZULAPAN'              => 'SANTO TOMAS TAMAZULAPAN',
        'SANTO TOMAS TAMAZULAPAM'  => 'SANTO TOMAS TAMAZULAPAN',
        'JUCHATENGO'               => 'SAN PEDRO JUCHATENGO',
        'LACHIXIO'                 => 'SANTA MARIA LACHIXIO',
        'SAN ANTONIO CUAJIMOLOYAS' => 'CUAJIMOLOYAS',
        'SAN JOSE EL CHILAR'       => 'SAN JOSE DEL CHILAR',
    ],
];
