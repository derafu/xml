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
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XPathQuery::class)]
#[CoversClass(XmlQueryException::class)]
class XmlTest extends TestCase
{
    /**
     * Verifica que el documento XML se carga correctamente.
     */
    public function testXmlLoadXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $result = $doc->loadXml($xmlContent);

        $this->assertTrue($result);
        $this->assertSame('root', $doc->getDomDocument()->documentElement->tagName);
    }

    /**
     * Verifica que se obtenga correctamente el nombre del tag raíz del
     * documento XML.
     */
    public function testXmlGetName(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('root', $doc->getName());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * existe.
     */
    public function testXmlGetNamespace(): void
    {
        $xmlContent = <<<XML
        <root xmlns="http://example.com">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('http://example.com', $doc->getNamespace());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * no existe.
     */
    public function testXmlGetNamespaceNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertNull($doc->getNamespace());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando
     * existe.
     */
    public function testXmlGetSchema(): void
    {
        $xmlContent = <<<XML
        <root xsi:schemaLocation="http://example.com schema.xsd"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('schema.xsd', $doc->getSchema());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando no
     * existe.
     */
    public function testXmlGetSchemaNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertNull($doc->getSchema());
    }

    /**
     * Verifica que el método saveXml() genera correctamente el XML.
     */
    public function testXmlSaveXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $expectedXml = <<<XML
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <root>
            <element>Value</element>
        </root>

        XML;

        $this->assertXmlStringEqualsXmlString($expectedXml, $doc->saveXml());
    }

    /**
     * Verifica que el método C14N() funcione correctamente, generando la
     * versión canónica del XML.
     */
    public function testXmlC14N(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $canonicalXml = $doc->C14N();

        $this->assertNotEmpty($canonicalXml);
        $expectedXml = "<root>\n    <element>Value</element>\n</root>";
        $this->assertStringContainsString($expectedXml, $canonicalXml);
    }

    /**
     * Verifica que el método C14NEncoded() aplane correctamente el
     * documento XML.
     */
    public function testXmlC14NEncoded(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $flattenedXml = $doc->C14NEncodedFlattened();

        $expectedXml = '<root><element>Value</element></root>';
        $this->assertSame($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que C14NEncoded() funcione correctamente cuando se
     * proporciona una expresión XPath.
     */
    public function testXmlC14NEncodedWithXPath(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
            <element2>Other Value</element2>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $flattenedXml = $doc->C14NEncoded('//element2');

        $expectedXml = '<element2>Other Value</element2>';
        $this->assertSame($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que un elemento raíz vacío (self-closing) se carga correctamente.
     *
     * Un documento como <root/> es XML válido. El elemento raíz existe pero no
     * tiene hijos ni atributos. Esto prueba que el parser no lo rechaza y que
     * getName() funciona sin contenido interior.
     */
    public function testLoadEmptySelfClosingRoot(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root/>');

        $this->assertSame('root', $doc->getName());
        $this->assertFalse($doc->getDomDocument()->documentElement->hasChildNodes());
        $this->assertFalse($doc->getDomDocument()->documentElement->hasAttributes());
    }

    /**
     * Verifica que un elemento raíz vacío con etiqueta de cierre explícita se
     * carga y serializa correctamente, y que decode() retorna null para él.
     *
     * <root></root> y <root/> son semánticamente equivalentes en XML. Aquí se
     * verifica además que el decoder los trata igual: retorna ['root' => null].
     */
    public function testLoadEmptyRootWithExplicitClosingTag(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root></root>');

        $this->assertSame('root', $doc->getName());
        $this->assertFalse($doc->getDomDocument()->documentElement->hasAttributes());

        $decoded = (new XmlDecoder())->decode($doc);
        $this->assertSame(['root' => null], $decoded);
    }

    /**
     * Verifica que get() retorna el valor correcto con selector de puntos.
     */
    public function testGetWithDotNotation(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child><value>42</value></child></root>');

        $this->assertSame('42', $doc->get('root.child.value'));
    }

    /**
     * Verifica que get() retorna null cuando el selector no existe.
     */
    public function testGetReturnsNullForMissingKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child>val</child></root>');

        $this->assertNull($doc->get('root.nonexistent'));
    }

    /**
     * Verifica que get() retorna el default proporcionado cuando el selector no existe.
     */
    public function testGetReturnsCustomDefaultForMissingKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child>val</child></root>');

        $this->assertSame('fallback', $doc->get('root.nonexistent', 'fallback'));
    }

    /**
     * Verifica que getSignatureNodeXml() retorna el XML canonicalizado del nodo
     * Signature cuando existe como hijo directo del root.
     */
    public function testGetSignatureNodeXmlReturnsCanonicalXml(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(
            '<root><Signature><SignedInfo>data</SignedInfo></Signature></root>'
        );

        $result = $doc->getSignatureNodeXml();

        $this->assertNotNull($result);
        $this->assertStringContainsString('<Signature>', $result);
        $this->assertStringContainsString('<SignedInfo>data</SignedInfo>', $result);
    }

    /**
     * Verifica que getSignatureNodeXml() retorna null cuando no hay nodo Signature.
     */
    public function testGetSignatureNodeXmlReturnsNullWhenAbsent(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element></root>');

        $this->assertNull($doc->getSignatureNodeXml());
    }

    /**
     * Verifica que setFormatOutput() es fluent y propaga el valor al DOMDocument interno.
     */
    public function testSetFormatOutput(): void
    {
        $doc = new XmlDocument();

        $this->assertSame($doc, $doc->setFormatOutput(false));
        $this->assertFalse($doc->getDomDocument()->formatOutput);

        $doc->setFormatOutput(true);
        $this->assertTrue($doc->getDomDocument()->formatOutput);
    }

    /**
     * Verifica que setPreserveWhiteSpace() es fluent y propaga el valor al DOMDocument interno.
     */
    public function testSetPreserveWhiteSpace(): void
    {
        $doc = new XmlDocument();

        $this->assertSame($doc, $doc->setPreserveWhiteSpace(false));
        $this->assertFalse($doc->getDomDocument()->preserveWhiteSpace);

        $doc->setPreserveWhiteSpace(true);
        $this->assertTrue($doc->getDomDocument()->preserveWhiteSpace);
    }

    /**
     * Verifica que C14NEncoded() retorne false cuando la expresión
     * XPath no coincide con ningún nodo.
     */
    public function testXmlC14NEncodedXPathNotFound(): void
    {
        $this->expectException(XmlException::class);

        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $xml = $doc->C14NEncoded('//nonexistent');
    }
}
