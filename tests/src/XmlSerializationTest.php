<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml;

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XPathQuery::class)]
class XmlSerializationTest extends TestCase
{
    /**
     * ISO-8859-1 encoded XML bytes loaded from the comprehensive fixture.
     */
    private string $iso88591Xml;

    /**
     * Expected values after decoding the fixture. Always UTF-8 PHP strings,
     * regardless of the source encoding.
     */
    private array $expectedValues = [
        'vocales_min' => 'á é í ó ú à è ì ò ù',
        'vocales_may' => 'Á É Í Ó Ú À È Ì Ò Ù',
        'enie'        => 'ñ Ñ',
        'dieresis'    => 'ü Ü ö Ö',
        'puntuacion'  => '¿Hola? ¡Mundo!',
        'simbolos'    => '© ® ° ½ ¼ ¾',
        'frase'       => 'Fabricación de Ñoños en Güemes',
    ];

    protected function setUp(): void
    {
        $this->iso88591Xml = require __DIR__ . '/../fixtures/encoding/iso88591-comprehensive.php';
    }

    // -------------------------------------------------------------------------
    // Case 1: UTF-8 document serialize/unserialize
    // -------------------------------------------------------------------------

    /**
     * Verifica que un XmlDocument UTF-8 se puede serializar y deserializar
     * preservando la codificación y el contenido.
     */
    public function testUtf8DocumentPreservesContentAfterSerializeUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(
            '<?xml version="1.0" encoding="UTF-8"?><root><element>Valor</element></root>'
        );

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        $this->assertInstanceOf(XmlDocument::class, $restored);
        $this->assertSame('UTF-8', $restored->getEncoding());
        $this->assertSame('root', $restored->getName());
        $this->assertSame('Valor', $restored->query('//element'));
    }

    /**
     * Verifica que el XML byte-a-byte es idéntico antes y después de un ciclo
     * serialize/unserialize en un documento UTF-8 con caracteres especiales.
     *
     * - ñ (U+00F1) debe estar como secuencia UTF-8 (0xC3 0xB1), nunca como
     *   byte ISO-8859-1 (0xF1).
     * - á (U+00E1) debe estar como secuencia UTF-8 (0xC3 0xA1), nunca como
     *   byte ISO-8859-1 (0xE1).
     */
    public function testUtf8DocumentWithSpecialCharactersPreservesBytes(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(
            '<?xml version="1.0" encoding="UTF-8"?><root><nombre>áéíóú ñÑ Güemes</nombre></root>'
        );

        $xmlBefore = $doc->saveXml();

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));
        $xmlAfter = $restored->saveXml();

        $this->assertSame($xmlBefore, $xmlAfter);

        // ñ como secuencia UTF-8 (0xC3 0xB1), nunca como byte ISO-8859-1 (0xF1).
        $this->assertStringContainsString("\xC3\xB1", $xmlAfter);
        $this->assertStringNotContainsString("\xF1", $xmlAfter);

        // á como secuencia UTF-8 (0xC3 0xA1), nunca como byte ISO-8859-1 (0xE1).
        $this->assertStringContainsString("\xC3\xA1", $xmlAfter);
        $this->assertStringNotContainsString("\xE1", $xmlAfter);
    }

    // -------------------------------------------------------------------------
    // Case 2: ISO-8859-1 document serialize/unserialize
    // -------------------------------------------------------------------------

    /**
     * Verifica que un XmlDocument cargado desde bytes ISO-8859-1 reales
     * preserva la declaración de codificación después de serialize/unserialize.
     *
     * - ñ debe estar como byte ISO-8859-1 (0xF1), nunca como secuencia
     *   UTF-8 (0xC3 0xB1).
     * - á debe estar como byte ISO-8859-1 (0xE1), nunca como secuencia
     *   UTF-8 (0xC3 0xA1).
     */
    public function testIso88591DocumentPreservesEncodingAfterSerializeUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        // La declaración de codificación se preserva.
        $this->assertSame('ISO-8859-1', $restored->getEncoding());

        $restoredXml = $restored->saveXml();

        // El header declara ISO-8859-1.
        $this->assertStringContainsString('encoding="ISO-8859-1"', $restoredXml);

        // ñ como byte ISO-8859-1 (0xF1), nunca como secuencia UTF-8 (0xC3 0xB1).
        $this->assertStringContainsString("\xF1", $restoredXml);
        $this->assertStringNotContainsString("\xC3\xB1", $restoredXml);

        // á como byte ISO-8859-1 (0xE1), nunca como secuencia UTF-8 (0xC3 0xA1).
        $this->assertStringContainsString("\xE1", $restoredXml);
        $this->assertStringNotContainsString("\xC3\xA1", $restoredXml);
    }

    /**
     * Verifica que el contenido decodificado de un documento ISO-8859-1 es
     * idéntico antes y después de serialize/unserialize.
     *
     * DOMDocument almacena los valores internamente en UTF-8, por lo que los
     * valores decodificados deben ser siempre strings UTF-8 válidos.
     */
    public function testIso88591DocumentPreservesDecodedContentAfterSerializeUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        $decoded = (new XmlDecoder())->decode($restored);

        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame(
                $expected,
                $decoded['documento'][$field],
                "El campo '{$field}' tiene un valor inesperado tras serialize/unserialize."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Case 3: Multiple serialize/unserialize cycles
    // -------------------------------------------------------------------------

    /**
     * Verifica que múltiples ciclos de serialize/unserialize no acumulan
     * corrupción en un documento ISO-8859-1.
     *
     * Un único ciclo ya lo cubre testIso88591DocumentPreservesEncoding*.
     * Este test corre tres ciclos adicionales para confirmar que no hay
     * deriva en la declaración de codificación, los bytes o los valores
     * decodificados.
     */
    public function testMultipleCyclesDoNotCorruptIso88591Document(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        for ($i = 0; $i < 3; $i++) {
            /** @var XmlDocument $doc */
            $doc = unserialize(serialize($doc));
        }

        // La declaración de codificación sigue siendo ISO-8859-1 tras 3 ciclos.
        $this->assertSame('ISO-8859-1', $doc->getEncoding());

        $xml = $doc->saveXml();

        // ñ sigue siendo el byte ISO-8859-1 0xF1, no la secuencia UTF-8 0xC3 0xB1.
        $this->assertStringContainsString("\xF1", $xml);
        $this->assertStringNotContainsString("\xC3\xB1", $xml);

        // Los valores decodificados coinciden con los originales.
        $decoded = (new XmlDecoder())->decode($doc);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    /**
     * Verifica que múltiples ciclos de serialize/unserialize en un documento
     * UTF-8 producen siempre el mismo XML byte-a-byte.
     */
    public function testMultipleCyclesProduceSameXmlForUtf8Document(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(
            '<?xml version="1.0" encoding="UTF-8"?><root><frase>Fabricación de Ñoños</frase></root>'
        );

        $xmlOriginal = $doc->saveXml();

        for ($i = 0; $i < 3; $i++) {
            /** @var XmlDocument $doc */
            $doc = unserialize(serialize($doc));
        }

        $this->assertSame($xmlOriginal, $doc->saveXml());
    }

    // -------------------------------------------------------------------------
    // Case 4: Methods work correctly after unserialize (XPath, namespace, schema)
    // -------------------------------------------------------------------------

    /**
     * Verifica que los métodos de XmlDocument funcionan correctamente tras
     * la deserialización, incluidas las consultas XPath (xPathQuery lazy).
     */
    public function testAllMethodsWorkAfterUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(<<<XML
            <root xmlns="http://example.com"
                  xsi:schemaLocation="http://example.com schema.xsd"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <child>Hola</child>
                <nested><value>42</value></nested>
            </root>
        XML);

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        $this->assertSame('root', $restored->getName());
        $this->assertSame('http://example.com', $restored->getNamespace());
        $this->assertSame('schema.xsd', $restored->getSchema());

        // XPath query (fuerza la inicialización lazy de xPathQuery).
        $this->assertSame('Hola', $restored->query('//child'));
        $this->assertSame('42', $restored->query('//value'));
    }

    // -------------------------------------------------------------------------
    // Case 5: Serialized form uses the 'xml' key
    // -------------------------------------------------------------------------

    /**
     * Verifica que la forma serializada de PHP contiene la clave 'xml' definida
     * en __serialize(), confirmando que el mecanismo correcto está en uso.
     */
    public function testSerializedStringContainsXmlKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element></root>');

        $serialized = serialize($doc);

        // La clave 'xml' del array devuelto por __serialize() debe aparecer
        // en la representación serializada de PHP.
        $this->assertStringContainsString('s:3:"xml";', $serialized);
    }
}
