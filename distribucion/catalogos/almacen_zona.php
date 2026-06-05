<?php
// distribucion/catalogos/almacen_zona.php
// Mapeo ALMACEN_RURAL → ZONA (MIXTECA | ISTMO | OAXACA)
// Derivado del OPE042026DICONSA.xls (30 almacenes, 632 lecherías, abril 2026).
// El ORDEN del array es el que se usa al generar el OPE.
//
// Si la BDD usa otro nombre para un almacén, agrega el alias en $ALMACEN_ALIAS.

return [
    'orden_almacenes' => [
        // ─── MIXTECA (8) ──────────────────────────────────────────────
        'CHALCATONGO',
        'COIXTLAHUACA',
        'CONSTANCIA DEL ROSARIO',
        'HUAJOLOTITLAN',
        'TACACHE DE MINA',
        'TLAXIACO',
        'TECOMAXTLAHUACA',
        'YANHUITLAN',
        // ─── ISTMO (7) ────────────────────────────────────────────────
        'MORRO MAZATAN',
        'PALOMARES',
        'SANTIAGO NILTEPEC',
        'SANTIAGO LAOLLAGA',
        'TOMATAL',
        'HUAXPALTEPEC',
        'STA. MA. HUATULCO',
        // ─── OAXACA (15) ──────────────────────────────────────────────
        'REFORMA YAUTEPEC',
        'IDEALES',
        'PUEBLO NUEVO',
        'VALLES CENTRALES',
        'SAN ANDRES HIDALGO',
        'SAN JOSE DEL CHILAR',
        'TEOTITLAN DE FLORES',
        'AYUTLA MIXES',
        'IXTLAN DE JUAREZ',
        'MAGDALENA OCOTLAN',
        'TAMAZULAPAN',
        'JUCHATENGO',
        'MATATLAN',
        'SAN ANTONIO CUAJIMOLOYAS',
        'LACHIXIO',
    ],

    'zona_de' => [
        'CHALCATONGO'              => 'MIXTECA',
        'COIXTLAHUACA'             => 'MIXTECA',
        'CONSTANCIA DEL ROSARIO'   => 'MIXTECA',
        'HUAJOLOTITLAN'            => 'MIXTECA',
        'TACACHE DE MINA'          => 'MIXTECA',
        'TLAXIACO'                 => 'MIXTECA',
        'TECOMAXTLAHUACA'          => 'MIXTECA',
        'YANHUITLAN'               => 'MIXTECA',

        'MORRO MAZATAN'            => 'ISTMO',
        'PALOMARES'                => 'ISTMO',
        'SANTIAGO NILTEPEC'        => 'ISTMO',
        'SANTIAGO LAOLLAGA'        => 'ISTMO',
        'TOMATAL'                  => 'ISTMO',
        'HUAXPALTEPEC'             => 'ISTMO',
        'STA. MA. HUATULCO'        => 'ISTMO',

        'REFORMA YAUTEPEC'         => 'OAXACA',
        'IDEALES'                  => 'OAXACA',
        'PUEBLO NUEVO'             => 'OAXACA',
        'VALLES CENTRALES'         => 'OAXACA',
        'SAN ANDRES HIDALGO'       => 'OAXACA',
        'SAN JOSE DEL CHILAR'      => 'OAXACA',
        'TEOTITLAN DE FLORES'      => 'OAXACA',
        'AYUTLA MIXES'             => 'OAXACA',
        'IXTLAN DE JUAREZ'         => 'OAXACA',
        'MAGDALENA OCOTLAN'        => 'OAXACA',
        'TAMAZULAPAN'              => 'OAXACA',
        'JUCHATENGO'               => 'OAXACA',
        'MATATLAN'                 => 'OAXACA',
        'SAN ANTONIO CUAJIMOLOYAS' => 'OAXACA',
        'LACHIXIO'                 => 'OAXACA',
    ],

    // Aliases: nombre alternativo → nombre canónico (los que use la BDD)
    'alias' => [
        'MORRO MAZATÁN'              => 'MORRO MAZATAN',
        'SANTA MARIA HUATULCO'       => 'STA. MA. HUATULCO',
        'SANTA MA. HUATULCO'         => 'STA. MA. HUATULCO',
        'STA MA HUATULCO'            => 'STA. MA. HUATULCO',
        'HUAJUAPAN'                  => 'HUAJOLOTITLAN', // si aplica
    ],
];
