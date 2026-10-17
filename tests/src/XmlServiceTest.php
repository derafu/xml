<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml;

use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlService::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlValidator::class)]
#[CoversClass(XmlHelper::class)]
#[UsesClass(XmlError::class)]
#[UsesClass(XmlErrorExplainer::class)]
class XmlServiceTest extends TestCase
{
    /**
     * XML document service.
     *
     * @var XmlServiceInterface
     */
    private XmlServiceInterface $xmlService;

    /**
     * Attribute with cases for each test.
     *
     * @var array<string, array<string, array<int|string, mixed>>>
     */
    private static array $testCases;

    /**
     * Initializes each test.
     */
    protected function setUp(): void
    {
        $encoder = new XmlEncoder();
        $decoder = new XmlDecoder();
        $validator = new XmlValidator();

        $this->xmlService = new XmlService($encoder, $decoder, $validator);
    }

    /**
     * Provides cases based on the test name.
     *
     * This method is used by each data provider to get test data.
     *
     * @return array<string, array<int|string, mixed>>
     */
    private static function dataProvider(string $testName): array
    {
        if (!isset(self::$testCases)) {
            $testCasesFile = __DIR__ . '/../fixtures/encode_and_decode.php';
            self::$testCases = require $testCasesFile;
        }

        if (!isset(self::$testCases[$testName])) {
            self::fail(sprintf(
                'Test %s() has no associated cases in dataProvider().',
                $testName
            ));
        }

        return self::$testCases[$testName];
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function arrayToXmlAndBackToArrayDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlAndBackToArray');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function arrayToXmlSaveXmlDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlSaveXml');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function arrayToXmlC14NDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14N');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function arrayToXmlC14NEncodedDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14NEncoded');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function xmlToArrayDataProvider(): array
    {
        return self::dataProvider('testXmlToArray');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function xmlToSaveXmlDataProvider(): array
    {
        return self::dataProvider('testXmlToSaveXml');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function xmlToC14NDataProvider(): array
    {
        return self::dataProvider('testXmlToC14N');
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function xmlToC14NEncodedDataProvider(): array
    {
        return self::dataProvider('testXmlToC14NEncoded');
    }

    /**
     * Converts an array to XML and back to an array,
     * ensuring the original structure is maintained.
     *
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $expected
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('arrayToXmlAndBackToArrayDataProvider')]
    public function testArrayToXmlAndBackToArray(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate structure
        $this->assertSame($expected, $arrayData);

        // Validate encoding in each array value
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Converts an array to XML and saves it as an XML string
     * with saveXml(), ensuring encoding and content are correct.
     *
     * @param array<int|string, mixed> $data
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('arrayToXmlSaveXmlDataProvider')]
    public function testArrayToXmlSaveXml(
        array $data,
        string $expected,
        ?string $expectedException,
        string $encoding = 'UTF-8'
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xml->setEncoding($encoding);
        $xmlString = $xml->saveXml();

        // Validate content.
        $this->assertSame($expected, $xmlString);

        // Validate encoding.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Converts an array to XML and saves it as an XML string
     * with C14N(), ensuring the content is correct.
     *
     * @param array<int|string, mixed> $data
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('arrayToXmlC14NDataProvider')]
    public function testArrayToXmlC14N(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xmlString = $xml->C14N();
        $xmlString = XmlHelper::fixEntities($xmlString);

        // Validate content.
        $this->assertSame($expected, $xmlString);
    }

    /**
     * Converts an array to XML and saves it as an XML string
     * with testArrayToXmlC14NEncoded(), ensuring encoding
     * and content are correct.
     *
     * @param array<int|string, mixed> $data
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('arrayToXmlC14NEncodedDataProvider')]
    public function testArrayToXmlC14NEncoded(
        array $data,
        string $expected,
        ?string $expectedException,
        string $encoding = 'UTF-8'
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xml->setEncoding($encoding);
        $xmlString = $xml->C14NEncoded();

        // Validate content.
        $this->assertSame($expected, $xmlString);

        // Validate encoding.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Converts an XML string to XML and then to an array,
     * ensuring the structure is maintained and data is in the
     * correct encoding.
     *
     * @param array<int|string, mixed> $expected
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('xmlToArrayDataProvider')]
    public function testXmlToArray(
        string $xmlContent,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $arrayData = $this->xmlService->decode($doc);

        // Validate structure.
        $this->assertSame($expected, $arrayData);

        // Validate encoding in each array value.
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Converts an XML string to XML and saves it as an XML string
     * with saveXml(), ensuring encoding and content are correct.
     *
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('xmlToSaveXmlDataProvider')]
    public function testXmlToSaveXml(
        string $xmlContent,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $xmlString = $doc->saveXml();

        // Validate content.
        $this->assertSame($expected, $xmlString);

        // Validate encoding.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Converts an XML string to XML and saves it as an XML string
     * with C14N(), ensuring the content is correct.
     *
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('xmlToC14NDataProvider')]
    public function testXmlToC14N(
        string $xmlContent,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $xmlString = $doc->C14N();
        $xmlString = XmlHelper::fixEntities($xmlString);

        // Validate content.
        $this->assertSame($expected, $xmlString);
    }

    /**
     * Converts an XML string to XML and saves it as an XML string
     * with C14NEncoded(), ensuring encoding and content
     * are correct.
     *
     * @param class-string<\Throwable>|null $expectedException
     */
    #[DataProvider('xmlToC14NEncodedDataProvider')]
    public function testXmlToC14NEncoded(
        string $xmlContent,
        string $expected,
        ?string $expectedException,
        string $encoding = 'UTF-8'
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $doc->setEncoding($encoding);
        $xmlString = $doc->C14NEncoded();

        // Validate content.
        $this->assertSame($expected, $xmlString);

        // Validate encoding.
        $this->assertSame(
            $encoding,
            mb_detect_encoding($xmlString, $encoding, true)
        );
    }

    /**
     * Helper function to verify the encoding of values in an array.
     *
     * @param array $data Array containing the values to verify.
     * @param string $expectedEncoding Expected encoding (example: UTF-8).
     * @param array<int|string, mixed> $data
     */
    private function assertArrayEncoding(
        array $data,
        string $expectedEncoding
    ): void {
        array_walk_recursive($data, function ($item) use ($expectedEncoding) {
            if (is_string($item)) {
                $this->assertSame(
                    $expectedEncoding,
                    mb_detect_encoding($item, $expectedEncoding, true)
                );
            }
        });
    }

    /**
     * Verifies that XML validation against the schema passes correctly.
     */
    public function testValidateSchemaSuccess(): void
    {
        // Create the XML schema (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Save the schema to a temporary file.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Create a valid XML according to the schema.
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        // Load the XML into an Xml.
        $xml = new XmlDocument();
        $xml->loadXml($xmlContent);

        // Validate the XML against the schema.
        try {
            $this->xmlService->validate($xml, $schemaPath);
            $this->addToAssertionCount(1); // No exception means validation passed.
        } catch (XmlException $e) {
            $message = sprintf(
                'XML validation should not fail, but an error occurred: %s',
                $e->getMessage()
            );
            $this->fail($message);
        }

        // Delete the temporary schema file.
        unlink($schemaPath);
    }

    /**
     * Verifies that XML validation against the schema fails correctly.
     */
    public function testValidateSchemaFailure(): void
    {
        // Create the XML schema (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Save the schema to a temporary file.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Create an invalid XML according to the schema.
        $invalidXmlContent = <<<XML
        <root>
            <wrongElement>Value</wrongElement>
        </root>
        XML;

        // Load the XML into an Xml.
        $xml = new XmlDocument();
        $xml->loadXml($invalidXmlContent);

        // Validate the XML against the schema and expect it to fail.
        $this->expectException(XmlException::class);
        $this->xmlService->validate($xml);

        // Delete the temporary schema file.
        unlink($schemaPath);
    }
}
