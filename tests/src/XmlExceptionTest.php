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

use Derafu\Translation\TranslatorFactory;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\Translation\XmlTranslationResourceProvider;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlErrorLevel;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Translation\Translator;

/**
 * The exceptions of the package are translatable exceptions like any other:
 * what they say is their message. What went wrong in detail (the errors of
 * libxml) and the document are data that they carry, not text that is added
 * to the message.
 */
#[CoversClass(XmlException::class)]
#[CoversClass(XmlParseException::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlValidator::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XmlTranslationResourceProvider::class)]
#[UsesClass(XmlError::class)]
#[UsesClass(XmlErrorExplainer::class)]
final class XmlExceptionTest extends TestCase
{
    private function translator(): Translator
    {
        return TranslatorFactory::create('es', [], [new XmlTranslationResourceProvider()]);
    }

    public function testItIsTranslatedIntoTheLanguageOfTheTranslator(): void
    {
        $exception = new XmlException(['The XML validation failed using the schema {schema}.', 'schema' => 'a.xsd']);

        $this->assertSame('The XML validation failed using the schema a.xsd.', $exception->getMessage());
        $this->assertSame(
            'La validación del XML falló usando el esquema a.xsd.',
            $exception->trans($this->translator())
        );
    }

    public function testItKeepsTheCodeAndThePreviousThrowable(): void
    {
        $previous = new RuntimeException('Previous.');

        $exception = new XmlException('Failed to load the XML.', 7, $previous);

        $this->assertSame(7, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testTheErrorsAreDataAndNotPartOfTheMessage(): void
    {
        $errors = [
            new XmlError(XmlErrorLevel::Fatal, 76, 1, 2, 'Error 1'),
            new XmlError(XmlErrorLevel::Error, 201, 3, 4, 'Error 2'),
        ];
        $exception = new XmlException('Failed to load the XML.', errors: $errors);

        $this->assertSame($errors, $exception->getErrors());
        $this->assertSame('Failed to load the XML.', $exception->getMessage());
        $this->assertSame('Error al cargar el XML.', $exception->trans($this->translator()));
    }

    public function testTheDocumentIsDataOfTheException(): void
    {
        $doc = new XmlDocument();

        $this->assertNull((new XmlException('Failed to load the XML.'))->getXmlDocument());
        $this->assertSame($doc, (new XmlException('Failed to load the XML.', xmlDocument: $doc))->getXmlDocument());
    }

    public function testAQueryExceptionHasTheXPathExpressionAndTheDocument(): void
    {
        $doc = new XmlDocument();

        $exception = new XmlQueryException('Invalid XPath expression: {expression}', xmlDocument: $doc, xpathExpression: '//a');

        $this->assertSame('//a', $exception->getXpathExpression());
        $this->assertSame($doc, $exception->getXmlDocument());
        $this->assertSame([], $exception->getErrors());
    }

    public function testTheErrorsOfAnXmlThatCanNotBeLoadedAreInTheMessageAndInTheErrors(): void
    {
        try {
            (new XmlDocument())->loadXml('<root><unclosed></root>');
            $this->fail('An XML that is not well formed was loaded.');
        } catch (XmlParseException $e) {
            $this->assertNotSame([], $e->getErrors());
            $this->assertStringStartsWith('Failed to load the XML: ', $e->getMessage());
            $this->assertSame(
                'Failed to load the XML: Line 1, column 24: Opening and ending tag mismatch: unclosed (line 1) and root',
                $e->getMessage()
            );
            $this->assertSame(
                'Error al cargar el XML: Línea 1, columna 24: La etiqueta de apertura unclosed (línea 1) no coincide con la de cierre root',
                $e->trans($this->translator())
            );
        }
    }

    public function testTheErrorsOfAnXmlThatIsNotValidAreInTheMessageAndInTheErrors(): void
    {
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, <<<XSD
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
            XSD);
        $doc = new XmlDocument();
        $doc->loadXml('<root><wrongElement>Value</wrongElement></root>');

        try {
            (new XmlValidator())->validate($doc, $schemaPath);
            $this->fail('An XML that is not valid was accepted.');
        } catch (XmlException $e) {
            $schema = basename($schemaPath);
            $this->assertNotSame([], $e->getErrors());
            $this->assertStringStartsWith('The XML validation failed using the schema ' . $schema . ': ', $e->getMessage());
            $this->assertStringContainsString((string) $e->getErrors()[0], $e->getMessage());
            $this->assertStringStartsWith('La validación del XML falló usando el esquema ' . $schema . ': ', $e->trans($this->translator()));
        } finally {
            unlink($schemaPath);
        }
    }
}
