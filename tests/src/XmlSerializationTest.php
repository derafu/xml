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
     *
     * @var array<string, string>
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
     * Verifies that a UTF-8 XmlDocument can be serialized and unserialized
     * preserving encoding and content.
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
     * Verifies that the XML is byte-for-byte identical before and after a
     * serialize/unserialize cycle in a UTF-8 document with special characters.
     *
     * - ñ (U+00F1) must be as UTF-8 sequence (0xC3 0xB1), never as
     *   ISO-8859-1 byte (0xF1).
     * - á (U+00E1) must be as UTF-8 sequence (0xC3 0xA1), never as
     *   ISO-8859-1 byte (0xE1).
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

        // ñ as UTF-8 sequence (0xC3 0xB1), never as ISO-8859-1 byte (0xF1).
        $this->assertStringContainsString("\xC3\xB1", $xmlAfter);
        $this->assertStringNotContainsString("\xF1", $xmlAfter);

        // á as UTF-8 sequence (0xC3 0xA1), never as ISO-8859-1 byte (0xE1).
        $this->assertStringContainsString("\xC3\xA1", $xmlAfter);
        $this->assertStringNotContainsString("\xE1", $xmlAfter);
    }

    // -------------------------------------------------------------------------
    // Case 2: ISO-8859-1 document serialize/unserialize
    // -------------------------------------------------------------------------

    /**
     * Verifies that an XmlDocument loaded from real ISO-8859-1 bytes
     * preserves the encoding declaration after serialize/unserialize.
     *
     * - ñ must be as ISO-8859-1 byte (0xF1), never as UTF-8
     *   sequence (0xC3 0xB1).
     * - á must be as ISO-8859-1 byte (0xE1), never as UTF-8
     *   sequence (0xC3 0xA1).
     */
    public function testIso88591DocumentPreservesEncodingAfterSerializeUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        // The encoding declaration is preserved.
        $this->assertSame('ISO-8859-1', $restored->getEncoding());

        $restoredXml = $restored->saveXml();

        // The header declares ISO-8859-1.
        $this->assertStringContainsString('encoding="ISO-8859-1"', $restoredXml);

        // ñ as ISO-8859-1 byte (0xF1), never as UTF-8 sequence (0xC3 0xB1).
        $this->assertStringContainsString("\xF1", $restoredXml);
        $this->assertStringNotContainsString("\xC3\xB1", $restoredXml);

        // á as ISO-8859-1 byte (0xE1), never as UTF-8 sequence (0xC3 0xA1).
        $this->assertStringContainsString("\xE1", $restoredXml);
        $this->assertStringNotContainsString("\xC3\xA1", $restoredXml);
    }

    /**
     * Verifies that decoded content from an ISO-8859-1 document is
     * identical before and after serialize/unserialize.
     *
     * DOMDocument stores values internally in UTF-8, so the
     * decoded values must always be valid UTF-8 strings.
     */
    public function testIso88591DocumentPreservesDecodedContentAfterSerializeUnserialize(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        /** @var XmlDocument $restored */
        $restored = unserialize(serialize($doc));

        $decoded = (new XmlDecoder())->decode($restored);

        $this->assertIsArray($decoded['documento']);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame(
                $expected,
                $decoded['documento'][$field],
                "Field '{$field}' has an unexpected value after serialize/unserialize."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Case 3: Multiple serialize/unserialize cycles
    // -------------------------------------------------------------------------

    /**
     * Verifies that multiple serialize/unserialize cycles do not accumulate
     * corruption in an ISO-8859-1 document.
     *
     * A single cycle is already covered by testIso88591DocumentPreservesEncoding*.
     * This test runs three additional cycles to confirm there is no
     * drift in the encoding declaration, bytes, or decoded values.
     */
    public function testMultipleCyclesDoNotCorruptIso88591Document(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        for ($i = 0; $i < 3; $i++) {
            /** @var XmlDocument $doc */
            $doc = unserialize(serialize($doc));
        }

        // The encoding declaration is still ISO-8859-1 after 3 cycles.
        $this->assertSame('ISO-8859-1', $doc->getEncoding());

        $xml = $doc->saveXml();

        // ñ is still the ISO-8859-1 byte 0xF1, not the UTF-8 sequence 0xC3 0xB1.
        $this->assertStringContainsString("\xF1", $xml);
        $this->assertStringNotContainsString("\xC3\xB1", $xml);

        // Decoded values match the originals.
        $decoded = (new XmlDecoder())->decode($doc);
        $this->assertIsArray($decoded['documento']);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    /**
     * Verifies that multiple serialize/unserialize cycles in a
     * UTF-8 document always produce the same XML byte-for-byte.
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
     * Verifies that XmlDocument methods work correctly after
     * deserialization, including XPath queries (xPathQuery lazy).
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

        // XPath query (forces lazy initialization of xPathQuery).
        $this->assertSame('Hola', $restored->query('//child'));
        $this->assertSame('42', $restored->query('//value'));
    }

    // -------------------------------------------------------------------------
    // Case 5: Serialized form uses the 'xml' key
    // -------------------------------------------------------------------------

    /**
     * Verifies that the PHP serialized form contains the 'xml' key defined
     * in __serialize(), confirming the correct mechanism is in use.
     */
    public function testSerializedStringContainsXmlKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element></root>');

        $serialized = serialize($doc);

        // The 'xml' key from the array returned by __serialize() must appear
        // in the PHP serialized representation.
        $this->assertStringContainsString('s:3:"xml";', $serialized);
    }
}
