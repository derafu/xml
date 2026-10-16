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

use Derafu\TestsXml\Support\XmlOutline;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMDocument;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The round trips of an XML and of the array that is decoded from it, with the
 * XML of the fixtures (the one of the SII, UBL documents of several versions
 * and a CAF) and with small XML that have what the XML of other documents have.
 *
 *   - XML to array to XML: the XML that comes back has the same elements, with
 *     their namespaces, the same attributes, the same text, just as it is, the
 *     same comments and the same order.
 *   - Array to XML to array: the array that comes back is the same.
 *   - What comes back is stable: one more round trip gives the same XML.
 *
 * The XML is compared by what it has, not by how it is written: the spaces
 * between the elements, the order of the namespaces, whether an element is
 * written empty or with its closing tag and the CDATA sections are not what
 * is compared. The encoding is not in the array, it is the one of the document
 * that is written: it is kept by the one that uses the decoder and the encoder.
 *
 * What the array can not say is not here (see `XmlDecoder`): the order among
 * child elements with different names when one is repeated, and the text that
 * is mixed with elements or comments.
 */
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
final class XmlRoundTripTest extends TestCase
{
    /**
     * The XML of the fixtures.
     *
     * @return array<string, array{string}>
     */
    public static function fixturesProvider(): array
    {
        $root = dirname(__DIR__) . '/fixtures/sources';
        $cases = [];
        foreach (glob($root . '/{*,*/*/*}/*.xml', GLOB_BRACE) ?: [] as $path) {
            $cases[substr($path, strlen($root) + 1)] = [(string) file_get_contents($path)];
        }

        return $cases;
    }

    /**
     * Small XML, each with something that the XML of other documents have.
     *
     * @return array<string, array{string}>
     */
    public static function casesProvider(): array
    {
        return [
            'default namespace and prefixes' => ['<r xmlns="urn:d" xmlns:p="urn:p"><p:a>1</p:a><b>2</b></r>'],
            'namespace that only a child declares' => ['<r><x:a xmlns:x="urn:x"><x:b>1</x:b></x:a></r>'],
            'namespace that is declared again' => ['<r xmlns="urn:a"><b xmlns="urn:b"><c>1</c></b></r>'],
            'namespace that is left without URI' => ['<r xmlns="urn:d"><a xmlns="">1</a></r>'],
            'namespace that is declared and not used' => ['<r xmlns:unused="urn:u"><a>1</a></r>'],
            'attributes with a prefix' => [
                '<r xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:x s.xsd" xml:lang="es" id="1"><a xsi:type="T"/></r>',
            ],
            'comments' => ['<r><!-- one --><a>1</a><!--two--><!--three--><b><!-- in b --></b></r>'],
            'comments in repeated elements' => ['<r><a><!--c--></a><a>2</a></r>'],
            'text with spaces around it' => ["<r><a>  x  </a><b>\n  line 1\n  line 2\n</b><c> </c><d xml:space=\"preserve\">  keep  </d></r>"],
            'repeated elements with spaces' => ['<r><a> 1</a><a>2 </a><a>  </a></r>'],
            'text of an element with attributes' => ['<r id="1">  text  </r>'],
            'CDATA' => ['<r><a><![CDATA[ <b>1 & 2</b> ]]></a></r>'],
            'entities' => ['<r a="x &amp; y &lt; z &quot;q&quot;"><b>a &amp; b &lt; c &gt; d</b></r>'],
            'unicode' => ['<r><a>ñandú — 日本語 😀</a></r>'],
            'numbers as text' => ['<r><n>007</n><f>1.50</f><t>true</t></r>'],
            'empty elements' => ['<r><a/><b></b><c id="1"/></r>'],
            'repeated elements' => ['<r><a id="1">x</a><a id="2"><b>y</b><b>z</b></a><c/><c/></r>'],
            'deep nesting' => ['<a><b><c><d><e><f>1</f></e></d></c></b></a>'],
            'attribute and child with the same name' => ['<r a="attribute"><a>child</a></r>'],
            'ISO-8859-1' => [
                mb_convert_encoding('<?xml version="1.0" encoding="ISO-8859-1"?><r><a>ñandú</a><b id="á">é</b></r>', 'ISO-8859-1', 'UTF-8'),
            ],
        ];
    }

    private function load(string $xml): XmlDocument
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        return $document;
    }

    private function outlineOf(XmlDocument $document): XmlOutline
    {
        return XmlOutline::of($document->getDocumentElement() ?? throw new LogicException('The document has no content.'));
    }

    /**
     * The XML that comes from decoding and encoding it, with the encoding that
     * the original has.
     */
    private function roundTrip(XmlDocument $document): XmlDocumentInterface
    {
        $encoded = (new XmlEncoder())->encode((new XmlDecoder())->decode($document));
        $encoded->setEncoding($document->getEncoding());

        return $encoded;
    }

    /**
     * The namespaces that an XML declares, as they are written.
     *
     * @return list<string>
     */
    private function declarations(string $xml): array
    {
        preg_match_all('/xmlns(?::[\w.-]+)?="[^"]*"/', $xml, $matches);
        sort($matches[0]);

        return $matches[0];
    }

    #[DataProvider('fixturesProvider')]
    #[DataProvider('casesProvider')]
    public function testXmlToArrayToXml(string $xml): void
    {
        $document = $this->load($xml);

        $result = $this->roundTrip($document);

        $this->assertSame(
            [],
            $this->outlineOf($document)->differencesWith($this->outlineOf($this->load($result->saveXml())))
        );
        $this->assertSame($document->getEncoding(), $result->getEncoding());
        $this->assertSame($this->declarations($document->getXml()), $this->declarations($result->getXml()));
    }

    /**
     * The same XML by Canonical XML (C14N, with the comments), a standard that
     * does not depend on how the comparison above is made. The spaces that only
     * format the XML are not compared. It is only with the fixtures: the small
     * XML with several comments among elements are not the same canonical XML,
     * because the place of the comments is not kept (see `XmlDecoder`).
     */
    #[DataProvider('fixturesProvider')]
    public function testXmlToArrayToXmlIsTheSameCanonicalXml(string $xml): void
    {
        $result = $this->roundTrip($this->load($xml));

        $this->assertSame($this->canonical($xml), $this->canonical($result->saveXml()));
    }

    private function canonical(string $xml): string
    {
        $document = new DOMDocument();
        $document->loadXML($xml, LIBXML_NOBLANKS);

        return $document->C14N(false, true);
    }

    #[DataProvider('fixturesProvider')]
    #[DataProvider('casesProvider')]
    public function testArrayToXmlToArray(string $xml): void
    {
        $array = (new XmlDecoder())->decode($this->load($xml));

        $encoded = (new XmlEncoder())->encode($array);

        // As it is built, and once it is written and loaded again.
        $this->assertSame($array, (new XmlDecoder())->decode($encoded));
        $this->assertSame($array, (new XmlDecoder())->decode($this->load($encoded->saveXml())));
    }

    #[DataProvider('fixturesProvider')]
    #[DataProvider('casesProvider')]
    public function testWhatComesBackIsStable(string $xml): void
    {
        $once = $this->roundTrip($this->load($xml));

        $twice = $this->roundTrip($this->load($once->saveXml()));

        $this->assertSame($once->saveXml(), $twice->saveXml());
    }
}
