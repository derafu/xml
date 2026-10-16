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

use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlHelper::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlDocument::class)]
class XmlHelperTest extends TestCase
{
    public function testXpathOverAnXmlThatIsAString(): void
    {
        $result = XmlHelper::xpath('<root><element>Value 1</element><element>Value 2</element></root>', '//element');

        $this->assertInstanceOf(DOMNodeList::class, $result);
        $this->assertSame(2, $result->length);
    }

    public function testXmlXpath(): void
    {
        $xmlContent = <<<XML
        <root>
            <element1>Value 1</element1>
            <element2>Value 2</element2>
        </root>
        XML;

        $doc = new DOMDocument();
        $doc->loadXml($xmlContent);

        $result = XmlHelper::xpath($doc, '//element1');

        $this->assertInstanceOf(DOMNodeList::class, $result);
        $this->assertSame(1, $result->length);
        $item = $result->item(0);
        $this->assertInstanceOf(DOMNode::class, $item);
        $this->assertSame('Value 1', $item->textContent);
    }

    public function testXmlXpathWithXmlDocument(): void
    {
        $xmlContent = <<<XML
        <root>
            <element1>Value 1</element1>
            <element2>Value 2</element2>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $result = XmlHelper::xpath($doc, '//element1');

        $this->assertInstanceOf(DOMNodeList::class, $result);
        $this->assertSame(1, $result->length);
        $item = $result->item(0);
        $this->assertInstanceOf(DOMNode::class, $item);
        $this->assertSame('Value 1', $item->textContent);
    }

    public function testXmlFixEntities(): void
    {
        $xml = '<root>He said "Hello" & ' . "'Goodbye'</root>";
        //$expectedXml = '<root>He said &quot;Hello&quot; &amp; &apos;Goodbye&apos;</root>';
        $expectedXml = '<root>He said &quot;Hello&quot; & &apos;Goodbye&apos;</root>';

        $result = XmlHelper::fixEntities($xml);

        $this->assertSame($expectedXml, $result);
    }

    public function testXmlXpathInvalidExpression(): void
    {
        $xmlContent = <<<XML
        <root>
            <element1>Value 1</element1>
            <element2>Value 2</element2>
        </root>
        XML;

        $doc = new DOMDocument();
        $doc->loadXml($xmlContent);

        $this->expectException(XmlQueryException::class);
        $result = XmlHelper::xpath($doc, '//*invalid_xpath');
    }

    public function testXmlFixEntitiesMalformedXml(): void
    {
        // XML without the closing `root` tag.
        $malformedXml = '<root>He said "Hello" & <child>Goodbye</child>';

        $result = XmlHelper::fixEntities($malformedXml);

        // The malformed XML is expected to remain the same. And the
        // entity correction is applied to the rest of the XML string.
        //$expectedXml = '<root>He said &quot;Hello&quot; &amp; <child>Goodbye</child>';
        $expectedXml = '<root>He said &quot;Hello&quot; & <child>Goodbye</child>';
        $this->assertSame($expectedXml, $result);
    }

    public function testXmlFixEntitiesEmptyString(): void
    {
        $emptyXml = '';

        $result = XmlHelper::fixEntities($emptyXml);

        // An empty XML string should return an empty result.
        $this->assertSame('', $result);
    }

    public function testXmlSanitizeNoSpecialCharacters(): void
    {
        $input = 'Hello World';
        $expected = 'Hello World';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeWithAmpersand(): void
    {
        $input = 'Tom & Jerry';
        $expected = 'Tom &amp; Jerry';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeWithQuotes(): void
    {
        $input = 'She said "Hello"';
        //$expected = 'She said &quot;Hello&quot;';
        $expected = 'She said "Hello"';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeWithApostrophe(): void
    {
        $input = "It's a beautiful day";
        //$expected = 'It&apos;s a beautiful day';
        $expected = 'It\'s a beautiful day';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeWithLessThanAndGreaterThan(): void
    {
        $input = '5 < 10 > 2';
        //$expected = '5 &lt; 10 &gt; 2';
        $expected = '5 < 10 > 2';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeWithNumericValue(): void
    {
        $input = '12345';
        $expected = '12345';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }

    public function testXmlSanitizeEmptyString(): void
    {
        $input = '';
        $expected = '';

        $result = XmlHelper::sanitize($input);

        $this->assertSame($expected, $result);
    }
}
