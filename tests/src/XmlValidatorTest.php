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

use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Validation of an XML against its schema: the schema that is given or the one
 * that the XML says, what is not valid and the messages that explain it.
 */
#[CoversClass(XmlValidator::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
final class XmlValidatorTest extends TestCase
{
    private string $schemaPath;

    protected function setUp(): void
    {
        $this->schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($this->schemaPath, <<<XSD
            <?xml version="1.0" encoding="UTF-8"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema" targetNamespace="urn:x" xmlns="urn:x" elementFormDefault="qualified">
                <xs:element name="root">
                    <xs:complexType>
                        <xs:sequence>
                            <xs:element name="element" type="xs:string"/>
                        </xs:sequence>
                    </xs:complexType>
                </xs:element>
            </xs:schema>
            XSD);
    }

    protected function tearDown(): void
    {
        unlink($this->schemaPath);
    }

    private function document(string $body, ?string $schemaPath = null): XmlDocument
    {
        $location = $schemaPath !== null
            ? ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:x ' . $schemaPath . '"'
            : '';

        $document = new XmlDocument();
        $document->loadXml('<root xmlns="urn:x"' . $location . '>' . $body . '</root>');

        return $document;
    }

    public function testItValidatesWithTheSchemaThatIsGiven(): void
    {
        (new XmlValidator())->validate($this->document('<element>Value</element>'), $this->schemaPath);

        $this->expectNotToPerformAssertions();
    }

    public function testItValidatesWithTheSchemaThatTheXmlSays(): void
    {
        $document = $this->document('<element>Value</element>', $this->schemaPath);

        (new XmlValidator())->validate($document);

        $this->expectNotToPerformAssertions();
    }

    public function testTheSchemaThatTheXmlSaysMustBeAnAbsolutePathThatExists(): void
    {
        $document = $this->document('<element>Value</element>', '/nonexistent/schema.xsd');

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('To validate an XML, the absolute path to the schema must be specified.');

        (new XmlValidator())->validate($document);
    }

    public function testAnXmlThatDoesNotSayItsSchemaCanNotBeValidatedWithoutOne(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The XML does not contain a valid schema location');

        (new XmlValidator())->validate($this->document('<element>Value</element>'));
    }

    public function testTheErrorsOfAnXmlThatIsNotValidAreSimpleAndWithoutTheNamespace(): void
    {
        $document = $this->document('<wrong>Value</wrong>');

        try {
            (new XmlValidator())->validate($document, $this->schemaPath);
            $this->fail('An XML that is not valid was accepted.');
        } catch (XmlException $e) {
            $this->assertCount(1, $e->getErrors());
            $this->assertStringContainsString("Field 'wrong' (line 1): was not expected", $e->getErrors()[0]);
            $this->assertStringNotContainsString('{urn:x}', $e->getErrors()[0]);
            $this->assertSame($document, $e->getXmlDocument());
        }
    }

    public function testTheTranslationsThatAreGivenAreApplied(): void
    {
        $document = $this->document('<wrong>Value</wrong>');

        try {
            (new XmlValidator())->validate($document, $this->schemaPath, ['was not expected' => 'WAS NOT EXPECTED']);
            $this->fail('An XML that is not valid was accepted.');
        } catch (XmlException $e) {
            $this->assertStringContainsString('WAS NOT EXPECTED', $e->getErrors()[0]);
        }
    }
}
