<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use PHPUnit\Framework\TestCase;

/**
 * Assertions for F60T33-ejemplo-oficial-SII.xml.
 *
 * Receives the full decoded array of the XML and runs the validations.
 */
return function (TestCase $test, array $xml): void {
    $env = $xml['EnvioDTE'];
    $doc = $env['SetDTE']['DTE']['Documento'];
    $enc = $doc['Encabezado'];
    $totales = $enc['Totales'];
    $detalle = $doc['Detalle'];

    // --- EnvioDTE header ---
    $test->assertArrayHasKey('@attributes', $env);
    $test->assertSame('1.0', $env['@attributes']['version']);

    // --- IdDoc ---
    $test->assertSame('33', $enc['IdDoc']['TipoDTE']);
    $test->assertSame('60', $enc['IdDoc']['Folio']);
    $test->assertSame('2003-10-13', $enc['IdDoc']['FchEmis']);

    // --- Emisor ---
    $test->assertSame('97975000-5', $enc['Emisor']['RUTEmisor']);
    $test->assertSame('RUT DE PRUEBA', $enc['Emisor']['RznSoc']);
    $test->assertSame('Santiago', $enc['Emisor']['CmnaOrigen']);

    // --- Receptor ---
    $test->assertSame('77777777-7', $enc['Receptor']['RUTRecep']);
    $test->assertSame('EMPRESA  LTDA', $enc['Receptor']['RznSocRecep']);

    // --- Totales ---
    $test->assertSame('100000', $totales['MntNeto']);
    $test->assertSame('19', $totales['TasaIVA']);
    $test->assertSame('19000', $totales['IVA']);
    $test->assertSame('119000', $totales['MntTotal']);

    // --- Detalle: must be an array of complex nodes, not strings ---
    $test->assertIsArray($detalle, 'Detalle must be an array.');
    $test->assertCount(3, $detalle, 'There must be 3 Detalle lines.');

    // Each item must be an array with the expected keys, not a string.
    foreach ($detalle as $i => $item) {
        $test->assertIsArray($item, "Detalle[$i] must be an array, not a string.");
        $test->assertArrayHasKey('NroLinDet', $item, "Detalle[$i] must have NroLinDet.");
        $test->assertArrayHasKey('NmbItem', $item, "Detalle[$i] must have NmbItem.");
        $test->assertArrayHasKey('QtyItem', $item, "Detalle[$i] must have QtyItem.");
        $test->assertArrayHasKey('PrcItem', $item, "Detalle[$i] must have PrcItem.");
        $test->assertArrayHasKey('MontoItem', $item, "Detalle[$i] must have MontoItem.");
    }

    // Specific assertions for each line.
    $test->assertSame('1', $detalle[0]['NroLinDet']);
    $test->assertSame('Parlantes Multimedia 180W.', $detalle[0]['NmbItem']);
    $test->assertSame('20', $detalle[0]['QtyItem']);
    $test->assertSame('4500', $detalle[0]['PrcItem']);
    $test->assertSame('90000', $detalle[0]['MontoItem']);

    $test->assertSame('2', $detalle[1]['NroLinDet']);
    $test->assertSame('Mouse Inalambrico PS/2', $detalle[1]['NmbItem']);
    $test->assertSame('1', $detalle[1]['QtyItem']);
    $test->assertSame('5000', $detalle[1]['PrcItem']);
    $test->assertSame('5000', $detalle[1]['MontoItem']);

    $test->assertSame('3', $detalle[2]['NroLinDet']);
    $test->assertSame('Caja de Diskettes 10 Unidades', $detalle[2]['NmbItem']);
    $test->assertSame('5', $detalle[2]['QtyItem']);
    $test->assertSame('1000', $detalle[2]['PrcItem']);
    $test->assertSame('5000', $detalle[2]['MontoItem']);

    // Each Detalle has CdgItem with a nested structure (not a string).
    $test->assertIsArray($detalle[0]['CdgItem']);
    $test->assertSame('INT1', $detalle[0]['CdgItem']['TpoCodigo']);
    $test->assertSame('011', $detalle[0]['CdgItem']['VlrCodigo']);

    // --- TED ---
    $test->assertArrayHasKey('TED', $doc);
    $test->assertSame('97975000-5', $doc['TED']['DD']['RE']);
    $test->assertSame('33', $doc['TED']['DD']['TD']);
    $test->assertSame('60', $doc['TED']['DD']['F']);
    $test->assertSame('119000', $doc['TED']['DD']['MNT']);
};
