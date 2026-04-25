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
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlService::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlHelper::class)]
class XmlEncodingTest extends TestCase
{
    private XmlService $xmlService;

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
        $this->xmlService = new XmlService(
            new XmlEncoder(),
            new XmlDecoder(),
            new XmlValidator()
        );

        $this->iso88591Xml = require __DIR__ . '/../fixtures/encoding/iso88591-comprehensive.php';
    }

    // -------------------------------------------------------------------------
    // Case 1: ISO-8859-1 XML → loadXml() + saveXml() → ISO-8859-1 XML
    // -------------------------------------------------------------------------

    /**
     * Verifies that loading an ISO-8859-1 XML and saving it preserves the
     * encoding declaration and produces actual ISO-8859-1 bytes (not UTF-8).
     *
     * Key byte-level assertions:
     *   - ñ must be the single byte 0xF1 (ISO-8859-1), not the 2-byte UTF-8
     *     sequence 0xC3 0xB1 that would indicate encoding corruption.
     *   - á must be the single byte 0xE1 (ISO-8859-1), not the 2-byte UTF-8
     *     sequence 0xC3 0xA1.
     */
    public function testIso88591RoundtripPreservesEncoding(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        $this->assertSame('ISO-8859-1', $doc->getEncoding());

        $saved = $doc->saveXml();

        // The serialized XML still declares ISO-8859-1.
        $this->assertStringContainsString('encoding="ISO-8859-1"', $saved);

        // ñ as ISO-8859-1 byte (0xF1), never as UTF-8 sequence (0xC3 0xB1).
        $this->assertStringContainsString("\xF1", $saved);
        $this->assertStringNotContainsString("\xC3\xB1", $saved);

        // á as ISO-8859-1 byte (0xE1), never as UTF-8 sequence (0xC3 0xA1).
        $this->assertStringContainsString("\xE1", $saved);
        $this->assertStringNotContainsString("\xC3\xA1", $saved);
    }

    /**
     * Verifies that after the roundtrip (load → save → load), decoding the
     * result still produces the same correct UTF-8 values.
     */
    public function testIso88591RoundtripPreservesData(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        $doc2 = new XmlDocument();
        $doc2->loadXml($doc->saveXml());

        $decoded = $this->xmlService->decode($doc2);

        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    // -------------------------------------------------------------------------
    // Case 2: ISO-8859-1 XML → decode() → PHP array with UTF-8 strings
    // -------------------------------------------------------------------------

    /**
     * Verifies that decoding an ISO-8859-1 XML document always returns PHP
     * strings in UTF-8, regardless of the source encoding.
     *
     * DOMDocument stores all node values as UTF-8 internally. The decoded
     * array must reflect this: values are UTF-8 PHP strings even when the
     * source file is ISO-8859-1.
     */
    public function testIso88591DecodesAsUtf8Array(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        $decoded = $this->xmlService->decode($doc);

        foreach ($this->expectedValues as $field => $expected) {
            $actual = $decoded['documento'][$field];

            // Value matches expected UTF-8 string.
            $this->assertSame($expected, $actual, "Field '{$field}' has unexpected value.");

            // Value is valid UTF-8.
            $this->assertTrue(
                mb_check_encoding($actual, 'UTF-8'),
                "Field '{$field}' is not valid UTF-8."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Case 3: PHP array (UTF-8) → encode() → XML in different encodings
    // -------------------------------------------------------------------------

    /**
     * Verifies that encoding a PHP array into a UTF-8 XmlDocument produces
     * a valid UTF-8 XML with the correct encoding declaration and UTF-8 bytes.
     *
     * Key byte-level assertion:
     *   - ñ must be the 2-byte UTF-8 sequence 0xC3 0xB1, not the single
     *     ISO-8859-1 byte 0xF1.
     */
    public function testEncodeArrayAsUtf8Xml(): void
    {
        $data = ['documento' => $this->expectedValues];

        $encoded = $this->xmlService->encode($data);
        $encoded->setEncoding('UTF-8');
        $xml = $encoded->saveXml();

        // Header declares UTF-8.
        $this->assertStringContainsString('encoding="UTF-8"', $xml);

        // ñ as UTF-8 sequence (0xC3 0xB1), never as single ISO-8859-1 byte (0xF1).
        $this->assertStringContainsString("\xC3\xB1", $xml);
        $this->assertStringNotContainsString("\xF1", $xml);

        // á as UTF-8 sequence (0xC3 0xA1), never as single ISO-8859-1 byte (0xE1).
        $this->assertStringContainsString("\xC3\xA1", $xml);
        $this->assertStringNotContainsString("\xE1", $xml);

        // The entire serialized XML is valid UTF-8.
        $this->assertTrue(mb_check_encoding($xml, 'UTF-8'));

        // Decoding yields the same UTF-8 values.
        $decoded = $this->xmlService->decode($encoded);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    /**
     * Verifies that encoding a PHP array into an ISO-8859-1 XmlDocument
     * produces a valid ISO-8859-1 XML with the correct encoding declaration
     * and ISO-8859-1 bytes.
     *
     * Key byte-level assertion:
     *   - ñ must be the single byte 0xF1 (ISO-8859-1), not the 2-byte UTF-8
     *     sequence 0xC3 0xB1.
     */
    public function testEncodeArrayAsIso88591Xml(): void
    {
        $data = ['documento' => $this->expectedValues];

        $encoded = $this->xmlService->encode($data);
        $encoded->setEncoding('ISO-8859-1');
        $xml = $encoded->saveXml();

        // Header declares ISO-8859-1.
        $this->assertStringContainsString('encoding="ISO-8859-1"', $xml);

        // Detected encoding is ISO-8859-1.
        $this->assertSame('ISO-8859-1', mb_detect_encoding($xml, 'ISO-8859-1', true));

        // ñ as ISO-8859-1 byte (0xF1), never as UTF-8 sequence (0xC3 0xB1).
        $this->assertStringContainsString("\xF1", $xml);
        $this->assertStringNotContainsString("\xC3\xB1", $xml);

        // á as ISO-8859-1 byte (0xE1), never as UTF-8 sequence (0xC3 0xA1).
        $this->assertStringContainsString("\xE1", $xml);
        $this->assertStringNotContainsString("\xC3\xA1", $xml);

        // Decoding yields the same UTF-8 values as if the source were UTF-8.
        $decoded = $this->xmlService->decode($encoded);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    // -------------------------------------------------------------------------
    // Case 4: ISO-8859-1 multiple roundtrips (load → save → load, repeated)
    // -------------------------------------------------------------------------

    /**
     * Verifies that running multiple load → save → load cycles on an
     * ISO-8859-1 document does not accumulate corruption.
     *
     * A single cycle is already covered by testIso88591RoundtripPreservesData().
     * This test runs three additional cycles to confirm there is no drift in
     * encoding declaration, byte sequences, or decoded values.
     */
    public function testIso88591MultipleRoundtripsPreserveData(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($this->iso88591Xml);

        for ($i = 0; $i < 3; $i++) {
            $saved = $doc->saveXml();
            $doc = new XmlDocument();
            $doc->loadXml($saved);
        }

        // Encoding declaration must still be ISO-8859-1 after 3 cycles.
        $this->assertSame('ISO-8859-1', $doc->getEncoding());

        $saved = $doc->saveXml();

        // ñ must still be the ISO-8859-1 byte 0xF1 — not UTF-8 0xC3 0xB1.
        $this->assertStringContainsString("\xF1", $saved);
        $this->assertStringNotContainsString("\xC3\xB1", $saved);

        // Decoded values must match the original expected UTF-8 strings.
        $decoded = $this->xmlService->decode($doc);
        foreach ($this->expectedValues as $field => $expected) {
            $this->assertSame($expected, $decoded['documento'][$field]);
        }
    }

    // -------------------------------------------------------------------------
    // Case 5: Characters outside ISO-8859-1 saved as ISO-8859-1
    // -------------------------------------------------------------------------

    /**
     * Verifies what happens when characters outside the ISO-8859-1 range are
     * serialized with that encoding.
     *
     * ISO-8859-1 covers U+0000–U+00FF. Characters above that range (e.g. the
     * euro sign '€' at U+20AC, or '™' at U+2122) have no direct byte
     * representation in ISO-8859-1. libxml handles this correctly by emitting
     * numeric character references (NCRs) such as &#8364; for '€', rather
     * than substituting with '?' or dropping the character.
     *
     * Consequence: data is NOT lost. A load → decode cycle on the ISO-8859-1
     * output recovers the original characters, because XML parsers decode NCRs
     * back to Unicode codepoints regardless of the document encoding.
     *
     * This test documents and pins that behavior so any regression (e.g.
     * libxml starting to drop or corrupt these characters) is immediately
     * visible.
     */
    public function testCharactersOutsideIso88591AreEncodedAsNcrWhenSavedAsIso88591(): void
    {
        $data = ['root' => [
            'euro'  => 'Precio: 100€',  // U+20AC — not in ISO-8859-1
            'trade' => 'Marca™',         // U+2122 — not in ISO-8859-1
        ]];

        $doc = $this->xmlService->encode($data);

        // UTF-8: characters appear as raw UTF-8 bytes in the serialized XML.
        $doc->setEncoding('UTF-8');
        $utf8Xml = $doc->saveXml();
        $this->assertStringContainsString('€', $utf8Xml);
        $this->assertStringContainsString('™', $utf8Xml);

        // ISO-8859-1: characters that cannot be encoded directly are emitted
        // as numeric character references (&#8364; for €, &#8482; for ™).
        $doc->setEncoding('ISO-8859-1');
        $isoXml = $doc->saveXml();
        $this->assertStringNotContainsString('€', $isoXml);
        $this->assertStringNotContainsString('™', $isoXml);
        $this->assertStringContainsString('&#8364;', $isoXml);  // € as NCR
        $this->assertStringContainsString('&#8482;', $isoXml);  // ™ as NCR

        // After a load → decode cycle the original values are fully recovered,
        // because XML parsers resolve NCRs back to Unicode codepoints.
        $doc2 = new XmlDocument();
        $doc2->loadXml($isoXml);
        $decoded = $this->xmlService->decode($doc2);
        $this->assertSame('Precio: 100€', $decoded['root']['euro']);
        $this->assertSame('Marca™', $decoded['root']['trade']);
    }

    /**
     * Verifies that encoding the same PHP array as UTF-8 and as ISO-8859-1
     * produces different byte sequences but the same decoded data.
     *
     * This is the core guarantee of the library: encoding is a serialization
     * concern; the data model (the decoded array) is always the same.
     */
    public function testBothEncodingsProduceSameDecodedData(): void
    {
        $data = ['documento' => $this->expectedValues];

        $encodedUtf8 = $this->xmlService->encode($data);
        $encodedUtf8->setEncoding('UTF-8');

        $encodedIso = $this->xmlService->encode($data);
        $encodedIso->setEncoding('ISO-8859-1');

        // The serialized XML bytes are different.
        $this->assertNotSame($encodedUtf8->saveXml(), $encodedIso->saveXml());

        // But the decoded data is identical.
        $decodedFromUtf8 = $this->xmlService->decode($encodedUtf8);
        $decodedFromIso  = $this->xmlService->decode($encodedIso);

        $this->assertSame($decodedFromUtf8, $decodedFromIso);
    }
}
