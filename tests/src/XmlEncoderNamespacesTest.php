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

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use DOMAttr;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The document that the encoder gives is the document of the XML, with its
 * namespaces, without writing it and loading it again: its elements and its
 * attributes are in their namespaces, so XPath with namespaces and the
 * canonical XML work in it as they do in a document that is loaded.
 */
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XPathQuery::class)]
#[UsesClass(XmlError::class)]
final class XmlEncoderNamespacesTest extends TestCase
{
    private const XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    private function load(string $xml): XmlDocument
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        return $document;
    }

    private function encodeAgain(XmlDocument $document): XmlDocument
    {
        $encoded = (new XmlEncoder())->encode((new XmlDecoder())->decode($document));
        $this->assertInstanceOf(XmlDocument::class, $encoded);

        return $encoded;
    }

    /**
     * The elements and the attributes of a document, with their namespaces, in
     * the order of the document.
     *
     * @return list<string>
     */
    private function names(DOMDocument $document): array
    {
        $names = [];
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//* | //@*') ?: [] as $node) {
            $names[] = sprintf('%s {%s}', $node->nodeName, $node->namespaceURI);
        }

        return $names;
    }

    #[DataProviderExternal(XmlRoundTripTest::class, 'fixturesProvider')]
    #[DataProviderExternal(XmlRoundTripTest::class, 'casesProvider')]
    public function testTheElementsAndTheAttributesAreInTheirNamespaces(string $xml): void
    {
        $document = $this->load($xml);

        $encoded = $this->encodeAgain($document);

        $this->assertSame($this->names($document->getDomDocument()), $this->names($encoded->getDomDocument()));
    }

    /**
     * The canonical XML of the document is the one of the XML: it does not need
     * that the document is written and loaded again.
     */
    #[DataProviderExternal(XmlRoundTripTest::class, 'fixturesProvider')]
    public function testTheCanonicalXmlIsTheOneOfTheXml(string $xml): void
    {
        $original = new DOMDocument();
        $original->loadXML($xml, LIBXML_NOBLANKS);

        $encoded = $this->encodeAgain($this->load($xml));

        $this->assertSame($original->C14N(false, true), $encoded->getDomDocument()->C14N(false, true));
    }

    public function testXPathWithNamespacesFindsWhatIsInTheDocument(): void
    {
        $xml = '<Invoice xmlns="urn:i" xmlns:cbc="urn:b"><cbc:ID>1</cbc:ID><cbc:Note>a</cbc:Note><cbc:Note>b</cbc:Note></Invoice>';

        $encoded = $this->encodeAgain($this->load($xml));

        $query = new XPathQuery($encoded, ['i' => 'urn:i', 'cbc' => 'urn:b']);
        $this->assertSame('1', $query->get('/i:Invoice/cbc:ID'));
        $this->assertSame(['a', 'b'], $query->get('//cbc:Note'));
    }

    public function testTheAttributesWithAPrefixAreInTheNamespaceOfTheirPrefix(): void
    {
        $xml = '<r xmlns:xsi="' . self::XSI . '" xsi:schemaLocation="urn:x s.xsd" xml:lang="es" id="1"/>';

        $root = $this->encodeAgain($this->load($xml))->getDomDocument()->documentElement;

        $this->assertNotNull($root);
        $this->assertSame('urn:x s.xsd', $root->getAttributeNS(self::XSI, 'schemaLocation'));
        $this->assertSame('es', $root->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang'));
        $this->assertSame('1', $root->getAttribute('id'));
        $id = $root->getAttributeNode('id');
        $this->assertInstanceOf(DOMAttr::class, $id);
        $this->assertNull($id->namespaceURI);
    }

    public function testTheDefaultNamespaceThatIsLeftWithoutUriLeavesTheElementWithoutNamespace(): void
    {
        $xml = '<r xmlns="urn:d"><a xmlns="">1</a><b>2</b></r>';

        $encoded = $this->encodeAgain($this->load($xml));

        $namespaces = [];
        foreach ($encoded->getDomDocument()->getElementsByTagName('*') as $element) {
            $namespaces[$element->nodeName] = $element->namespaceURI;
        }
        $this->assertSame(['r' => 'urn:d', 'a' => null, 'b' => 'urn:d'], $namespaces);
    }

    public function testTheNamespacesAreWrittenOnceWhereTheyAreDeclared(): void
    {
        $encoded = (new XmlEncoder())->encode(['r' => [
            '@attributes' => ['xmlns' => 'urn:d', 'xmlns:p' => 'urn:p'],
            'a' => '1',
            'p:b' => ['c' => '2', 'p:d' => '3'],
            'e' => ['4', '5'],
        ]]);

        $this->assertSame(
            '<r xmlns="urn:d" xmlns:p="urn:p"><a>1</a><p:b><c>2</c><p:d>3</p:d></p:b><e>4</e><e>5</e></r>',
            preg_replace('/>\s+</', '><', $encoded->getXml())
        );
    }

    /**
     * A prefix that is not declared in the array is a name without namespace.
     */
    public function testAPrefixThatIsNotDeclaredIsANameWithoutNamespace(): void
    {
        $encoded = (new XmlEncoder())->encode(['r' => ['x:a' => '1']]);

        $element = $encoded->getDomDocument()->getElementsByTagName('x:a')->item(0);
        $this->assertNotNull($element);
        $this->assertNull($element->namespaceURI);
        $this->assertSame("<r>\n  <x:a>1</x:a>\n</r>", $encoded->getXml());
    }

    /**
     * The namespaces are declared before the other attributes, wherever they are
     * in the array, so the attributes with a prefix have their namespace.
     */
    public function testTheNamespacesAreDeclaredWhereverTheyAreInTheAttributes(): void
    {
        $encoded = (new XmlEncoder())->encode(['r' => ['@attributes' => [
            'xsi:type' => 'T',
            'id' => '1',
            'xmlns:xsi' => self::XSI,
        ]]]);

        $root = $encoded->getDomDocument()->documentElement;
        $this->assertNotNull($root);
        $this->assertSame('T', $root->getAttributeNS(self::XSI, 'type'));
        $this->assertSame(1, substr_count($encoded->saveXml(), 'xmlns:xsi='));
    }
}
