<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\Array\ArrayReader;
use Derafu\Value\ValueType;
use PHPUnit\Framework\TestCase;

/**
 * Assertions for F60T33-ejemplo-oficial-SII.xml.
 *
 * Receives the full decoded array of the XML and runs the validations.
 */
return function (TestCase $test, array $xml): void {
    $reader = ArrayReader::fromArray($xml, 'F60T33-ejemplo-oficial-SII.xml');
    $env = ArrayReader::fromArray($reader->require('EnvioDTE', ValueType::array()));
    $doc = ArrayReader::fromArray(
        $reader->require('EnvioDTE.SetDTE.DTE.Documento', ValueType::array())
    );

    // --- EnvioDTE header ---
    $test->assertTrue($env->hasKey('@attributes'));
    $test->assertSame('1.0', $env->requireString('@attributes.version'));

    // --- IdDoc ---
    $test->assertSame('33', $doc->requireString('Encabezado.IdDoc.TipoDTE'));
    $test->assertSame('60', $doc->requireString('Encabezado.IdDoc.Folio'));
    $test->assertSame('2003-10-13', $doc->requireString('Encabezado.IdDoc.FchEmis'));

    // --- Emisor ---
    $test->assertSame('97975000-5', $doc->requireString('Encabezado.Emisor.RUTEmisor'));
    $test->assertSame('RUT DE PRUEBA', $doc->requireString('Encabezado.Emisor.RznSoc'));
    $test->assertSame('Santiago', $doc->requireString('Encabezado.Emisor.CmnaOrigen'));

    // --- Receptor ---
    $test->assertSame('77777777-7', $doc->requireString('Encabezado.Receptor.RUTRecep'));
    $test->assertSame('EMPRESA  LTDA', $doc->requireString('Encabezado.Receptor.RznSocRecep'));

    // --- Totales ---
    $test->assertSame('100000', $doc->requireString('Encabezado.Totales.MntNeto'));
    $test->assertSame('19', $doc->requireString('Encabezado.Totales.TasaIVA'));
    $test->assertSame('19000', $doc->requireString('Encabezado.Totales.IVA'));
    $test->assertSame('119000', $doc->requireString('Encabezado.Totales.MntTotal'));

    // --- Detalle: must be a list of complex nodes, not strings ---
    // The reader fails if Detalle is not a list of arrays.
    $detalle = $doc->getReaders('Detalle') ?? [];
    $test->assertCount(3, $detalle, 'There must be 3 Detalle lines.');

    // Each item must have the expected keys.
    foreach ($detalle as $i => $item) {
        foreach (['NroLinDet', 'NmbItem', 'QtyItem', 'PrcItem', 'MontoItem'] as $key) {
            $test->assertTrue($item->hasKey($key), "Detalle[$i] must have $key.");
        }
    }

    // Specific assertions for each line.
    $lines = [
        ['1', 'Parlantes Multimedia 180W.', '20', '4500', '90000'],
        ['2', 'Mouse Inalambrico PS/2', '1', '5000', '5000'],
        ['3', 'Caja de Diskettes 10 Unidades', '5', '1000', '5000'],
    ];
    foreach ($lines as $i => [$nroLinDet, $nmbItem, $qtyItem, $prcItem, $montoItem]) {
        $test->assertSame($nroLinDet, $detalle[$i]->requireString('NroLinDet'));
        $test->assertSame($nmbItem, $detalle[$i]->requireString('NmbItem'));
        $test->assertSame($qtyItem, $detalle[$i]->requireString('QtyItem'));
        $test->assertSame($prcItem, $detalle[$i]->requireString('PrcItem'));
        $test->assertSame($montoItem, $detalle[$i]->requireString('MontoItem'));
    }

    // Each Detalle has CdgItem with a nested structure (not a string).
    $test->assertSame('INT1', $detalle[0]->requireString('CdgItem.TpoCodigo'));
    $test->assertSame('011', $detalle[0]->requireString('CdgItem.VlrCodigo'));

    // --- TED ---
    $test->assertTrue($doc->hasKey('TED'));
    $test->assertSame('97975000-5', $doc->requireString('TED.DD.RE'));
    $test->assertSame('33', $doc->requireString('TED.DD.TD'));
    $test->assertSame('60', $doc->requireString('TED.DD.F'));
    $test->assertSame('119000', $doc->requireString('TED.DD.MNT'));
};
