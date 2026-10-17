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

use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use DOMDocument;
use DOMNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XPathQuery::class)]
#[CoversClass(XmlParseException::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
#[UsesClass(XmlError::class)]
#[UsesClass(XmlErrorExplainer::class)]
class XPathQueryTest extends TestCase
{
    private string $validXml;

    private string $invalidXml;

    private string $nestedXml;

    private string $xmlNamespaceAndParams;

    protected function setUp(): void
    {
        $this->validXml = <<<XML
            <root>
                <item id="1">First</item>
                <item id="2">Second</item>
                <item id="3">Third</item>
            </root>
        XML;

        $this->invalidXml = <<<XML
            <root>
                <item id="1">First</item>
                <item id="2">Second</item>
                <!-- Missing closing tag for root -->
        XML;
        $this->nestedXml = <<<XML
            <AUTORIZACION>
                <CAF>
                    <FRMA>firma_base64</FRMA>
                    <DA>
                        <TD>33</TD>
                        <RNG>
                            <D>1</D>
                            <H>100</H>
                        </RNG>
                    </DA>
                </CAF>
            </AUTORIZACION>
        XML;

        $this->xmlNamespaceAndParams = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
          <Documento ID="LibreDTE_76192083-9_T33F1">
            <Encabezado>
              <IdDoc>
                <TipoDTE>33</TipoDTE>
                <Folio>1</Folio>
                <FchEmis>2025-01-03</FchEmis>
              </IdDoc>
              <Emisor>
                <RUTEmisor>76192083-9</RUTEmisor>
                <RznSoc>SASCO SpA</RznSoc>
              </Emisor>
            </Encabezado>
          </Documento>
        </DTE>
        XML;
    }

    public function testGetSingleValue(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->getValue('/root/item[@id="1"]');

        $this->assertSame('First', $result);
    }

    public function testGetMultipleValues(): void
    {
        $query = new XPathQuery($this->validXml);
        $results = $query->getValues('/root/item');

        $this->assertCount(3, $results);
        $this->assertSame(['First', 'Second', 'Third'], $results);
    }

    public function testItQueriesADomDocument(): void
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->validXml);

        $query = new XPathQuery($dom);

        $this->assertSame($dom, $query->getDomDocument());
        $this->assertSame('First', $query->get('/root/item[@id=1]'));
    }

    public function testAnElementWithoutContentIsAnEmptyText(): void
    {
        $query = new XPathQuery('<r><a/><b id="5"/></r>');

        $this->assertSame('', $query->get('/r/a'));
        $this->assertSame('', $query->getValue('/r/a'));
        $this->assertSame(['', ''], $query->getValues('/r/*'));
        $this->assertSame('5', $query->get('/r/b/@id'));
    }

    /**
     * A namespace node has no children: what it has as value is its URI, in
     * `get()` as in `getValues()`.
     */
    public function testANamespaceNodeIsItsUri(): void
    {
        $query = new XPathQuery(
            '<root xmlns:a="urn:a" xmlns:b="urn:b"><x>1</x></root>',
            ['a' => 'urn:a']
        );

        $this->assertSame('urn:a', $query->get('/root/namespace::a'));
        $this->assertSame(
            $query->getValues('/root/namespace::*'),
            $query->get('/root/namespace::*')
        );
        $this->assertContains('urn:b', $query->getValues('/root/namespace::*'));
    }

    public function testGetNodes(): void
    {
        $query = new XPathQuery($this->validXml);
        $nodes = $query->getNodes('/root/item');

        $this->assertSame(3, $nodes->length);
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->nodeValue;
        }
        $this->assertSame(['First', 'Second', 'Third'], $values);
    }

    public function testGetSingleResultWithGet(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->get('/root/item[@id="2"]');

        $this->assertSame('Second', $result);
    }

    public function testGetMultipleResultsWithGet(): void
    {
        $query = new XPathQuery($this->validXml);
        $results = $query->get('/root/item');

        $this->assertSame(['First', 'Second', 'Third'], $results);
    }

    public function testGetNullForNoMatch(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->get('/root/nonexistent');

        $this->assertNull($result);
    }

    public function testInvalidXPathThrowsException(): void
    {
        $query = new XPathQuery($this->validXml);

        $this->expectException(XmlQueryException::class);
        $query->getNodes('//root@invalid_xpath]');
    }

    public function testLoadMalformedXmlThrowsException(): void
    {
        $this->expectException(XmlParseException::class);
        new XPathQuery($this->invalidXml);
    }

    public function testGetDomDocument(): void
    {
        $query = new XPathQuery($this->validXml);
        $dom = $query->getDomDocument();

        $this->assertInstanceOf(DOMDocument::class, $dom);
        $this->assertNotNull($dom->documentElement);
        $this->assertSame('root', $dom->documentElement->nodeName);
    }

    public function testGetSingleNode(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('//CAF/FRMA');

        $this->assertSame(
            'firma_base64',
            $result,
            'Should return the value of the FRMA node.'
        );
    }

    public function testGetComplexStructure(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('/AUTORIZACION/CAF');

        $expected = [
            'FRMA' => 'firma_base64',
            'DA' => [
                'TD' => '33',
                'RNG' => [
                    'D' => '1',
                    'H' => '100',
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            $result,
            'Should return the complete hierarchical structure of CAF.'
        );
    }

    public function testGetArrayOfNodes(): void
    {
        $xml = <<<XML
            <ROOT>
                <ITEM>Value1</ITEM>
                <ITEM>Value2</ITEM>
                <ITEM>Value3</ITEM>
            </ROOT>
        XML;

        $query = new XPathQuery($xml);
        $result = $query->get('//ITEM');

        $expected = ['Value1', 'Value2', 'Value3'];

        $this->assertSame(
            $expected,
            $result,
            'Should return an array of values for repeated nodes.'
        );
    }

    public function testGetWithInvalidXPath(): void
    {
        $query = new XPathQuery($this->nestedXml);

        $this->expectException(XmlQueryException::class);
        $this->expectExceptionMessage(
            'An error occurred while executing the XPath expression'
        );

        $query->get('//Invalid@[XPath]');
    }

    public function testGetNonexistentNode(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('//NonexistentNode');

        $this->assertNull($result, 'Should return null if the node does not exist.');
    }

    public function testNamespaceHandling(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $result = $query->get('//ns:Encabezado/ns:Emisor/ns:RUTEmisor');
        $this->assertSame('76192083-9', $result);

        $result = $query->get('//ns:Encabezado/ns:IdDoc/ns:TipoDTE');
        $this->assertSame('33', $result);
    }

    public function testQueryWithParameters(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $params = ['tipo' => '33', 'folio' => '1'];
        $result = $query->get('//ns:IdDoc[ns:TipoDTE=:tipo and ns:Folio=:folio]', $params);

        $this->assertNotNull($result);
        $expected = ['TipoDTE' => '33', 'Folio' => '1', 'FchEmis' => '2025-01-03'];
        $this->assertSame($expected, $result);
    }

    public function testInvalidQueryThrowsException(): void
    {
        $this->expectException(XmlQueryException::class);

        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $query->get('//invalid[');
    }

    public function testComplexStructureWithNamespaces(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $result = $query->get('//ns:Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testComplexStructureWithNamespacesDisabledAbsoluteNode(): void
    {
        $query = new XPathQuery($this->xmlNamespaceAndParams);

        $result = $query->get('/DTE/Documento/Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testComplexStructureWithNamespacesDisabledRelativeNode(): void
    {
        $query = new XPathQuery($this->xmlNamespaceAndParams);

        $result = $query->get('//Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testInvalidXmlThrowsException(): void
    {
        $this->expectException(XmlParseException::class);

        new XPathQuery('<invalid><xml>', ['ns' => 'http://www.sii.cl/SiiDte']);
    }

    public function testGetDomDocumentWithNamespace(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $dom = $query->getDomDocument();

        $this->assertInstanceOf(DOMDocument::class, $dom);
        $xml = $dom->saveXml();
        $this->assertIsString($xml);
        $this->assertStringContainsString('DTE', $xml);
    }

    // Case with single quotes in the value (value matches the XML node).
    public function testResolveQueryWithEscapedValuesSimpleQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO 'Testing' SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => "SASCO 'Testing' SpA"]
        );
        $this->assertSame("SASCO 'Testing' SpA", $result);
    }

    // Case with double quotes in the value.
    public function testResolveQueryWithEscapedValuesDobuleQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO "Testing" SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO "Testing" SpA']
        );
        $this->assertSame('SASCO "Testing" SpA', $result);
    }

    // Case with mixed single and double quotes.
    public function testResolveQueryWithEscapedValuesMixedQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO 'Mixed' "Testing" SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO \'Mixed\' "Testing" SpA']
        );
        $this->assertSame('SASCO \'Mixed\' "Testing" SpA', $result);
    }

    // Case without quotes (basic value).
    public function testResolveQueryWithEscapedValuesWithoutQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO Testing SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO Testing SpA']
        );
        $this->assertSame('SASCO Testing SpA', $result);
    }

    public function testQueryWithContextNode(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtain the 'Encabezado' node as context
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);
        $this->assertInstanceOf(DOMNode::class, $contextNode);

        // Query from the 'Encabezado' node context
        $result = $query->get('ns:IdDoc/ns:TipoDTE', contextNode: $contextNode);
        $this->assertSame('33', $result);

        $result = $query->get('ns:IdDoc/ns:Folio', contextNode: $contextNode);
        $this->assertSame('1', $result);
    }

    public function testQueryWithContextNodeNoResult(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtain the 'Encabezado' node as context
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);
        $this->assertInstanceOf(DOMNode::class, $contextNode);

        // Attempt to query a node that does not exist from the context
        $result = $query->get('ns:NonExistentNode', contextNode: $contextNode);
        $this->assertNull($result);
    }

    public function testQueryWithContextNodeOnMultipleMatches(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtain the 'Encabezado' node as context
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);
        $this->assertInstanceOf(DOMNode::class, $contextNode);

        // Query multiple nodes from the context
        $result = $query->getValues('ns:IdDoc/ns:*', contextNode: $contextNode);
        $this->assertCount(3, $result); // There are 3 nodes in 'IdDoc': TipoDTE, Folio and FchEmis.
        $this->assertContains('33', $result);
        $this->assertContains('1', $result);
    }

    public function testXmlWithMultipleNodesWithTheSameName(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO Testing SpA</RznSoc>
                    </Emisor>
                </Encabezado>
                <Detalle>
                    <NroLinDet>1</NroLinDet>
                    <NmbItem>Product A</NmbItem>
                    <QtyItem>2</QtyItem>
                    <PrcItem>1000</PrcItem>
                    <MontoItem>2000</MontoItem>
                </Detalle>
                <Detalle>
                    <NroLinDet>2</NroLinDet>
                    <NmbItem>Product B</NmbItem>
                    <QtyItem>1</QtyItem>
                    <PrcItem>1500</PrcItem>
                    <MontoItem>1500</MontoItem>
                </Detalle>
                <Detalle>
                    <NroLinDet>3</NroLinDet>
                    <NmbItem>Product C</NmbItem>
                    <QtyItem>3</QtyItem>
                    <PrcItem>500</PrcItem>
                    <MontoItem>1500</MontoItem>
                </Detalle>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get('/');

        // Verify that the result is an array.
        $this->assertIsArray($result);

        // Verify that the DTE > Documento > Detalle structure exists.
        $this->assertArrayHasKey('DTE', $result);
        $this->assertIsArray($result['DTE']);
        $this->assertArrayHasKey('Documento', (array) $result['DTE']);
        $this->assertIsArray($result['DTE']['Documento']);
        $this->assertArrayHasKey('Detalle', (array) $result['DTE']['Documento']);

        // Verify that Detalle is an array with 3 elements.
        $detalle = (array) $result['DTE']['Documento']['Detalle'];
        $this->assertCount(3, $detalle);

        // Verify the content of each Detalle node.
        $this->assertIsArray($detalle[0]);
        $this->assertSame('1', $detalle[0]['NroLinDet']);
        $this->assertSame('Product A', $detalle[0]['NmbItem']);
        $this->assertSame('2', $detalle[0]['QtyItem']);
        $this->assertSame('1000', $detalle[0]['PrcItem']);
        $this->assertSame('2000', $detalle[0]['MontoItem']);

        $this->assertIsArray($detalle[1]);
        $this->assertSame('2', $detalle[1]['NroLinDet']);
        $this->assertSame('Product B', $detalle[1]['NmbItem']);
        $this->assertSame('1', $detalle[1]['QtyItem']);
        $this->assertSame('1500', $detalle[1]['PrcItem']);
        $this->assertSame('1500', $detalle[1]['MontoItem']);

        $this->assertIsArray($detalle[2]);
        $this->assertSame('3', $detalle[2]['NroLinDet']);
        $this->assertSame('Product C', $detalle[2]['NmbItem']);
        $this->assertSame('3', $detalle[2]['QtyItem']);
        $this->assertSame('500', $detalle[2]['PrcItem']);
        $this->assertSame('1500', $detalle[2]['MontoItem']);
    }
}
