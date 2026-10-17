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
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlHelper::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlException::class)]
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

    private const XML_THAT_CAN_NOT_BE_CANONICALIZED = '<r xmlns="relative"><b ID="B1"/></r>';

    /**
     * libxml loads it, with a notice that the namespace is a relative URI.
     */
    private function documentThatCanNotBeCanonicalized(): DOMDocument
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadXML(self::XML_THAT_CAN_NOT_BE_CANONICALIZED);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        return $document;
    }

    public function testCanonicalizeADocumentAndANode(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<r b="2" a="1"><!-- c --><x>1</x></r>');

        $this->assertSame('<r a="1" b="2"><x>1</x></r>', XmlHelper::canonicalize($document));
        $this->assertSame('<x>1</x>', XmlHelper::canonicalize($document->getElementsByTagName('x')->item(0) ?? $document));
    }

    public function testCanonicalizeWithTheOptionsOfC14n(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<r xmlns:p="urn:p"><!-- c --><x>1</x></r>');

        $this->assertStringContainsString('<!-- c -->', XmlHelper::canonicalize($document, withComments: true));
        $this->assertStringNotContainsString('<!-- c -->', XmlHelper::canonicalize($document));
        // The exclusive one does not have the namespace that is not used.
        $this->assertSame('<r><x>1</x></r>', XmlHelper::canonicalize($document, exclusive: true));
        $this->assertSame('<r xmlns:p="urn:p"><x>1</x></r>', XmlHelper::canonicalize($document));
    }

    /**
     * An XML whose namespace is a relative URI is loaded by libxml, but it can
     * not be canonicalized: it is an error of the package, with what libxml
     * said, and not a `false` that goes on as if it were the text.
     */
    public function testWhatCanNotBeCanonicalizedIsAnExceptionWithTheErrorsOfLibxml(): void
    {
        $document = $this->documentThatCanNotBeCanonicalized();

        try {
            XmlHelper::canonicalize($document);
            $this->fail('It should have failed.');
        } catch (XmlException $e) {
            $this->assertSame('The XML could not be canonicalized.', $e->getMessage());
            $this->assertStringContainsString('Relative namespace', implode(' ', $e->getErrors()));
        }
    }

    public function testTheErrorsOfLibxmlAreNotEmittedAsWarningsAndTheStateOfLibxmlIsTheOneThatWas(): void
    {
        $document = $this->documentThatCanNotBeCanonicalized();
        $warnings = [];
        set_error_handler(function (int $number, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        });
        $before = libxml_use_internal_errors(false);

        try {
            XmlHelper::canonicalize($document);
        } catch (XmlException) {
            // The error is tested above.
        } finally {
            $after = libxml_use_internal_errors($before);
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertFalse($after);
        $this->assertSame([], libxml_get_errors());
    }

    public function testTheStateOfLibxmlIsRestoredWhenItWasUsingInternalErrors(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<r/>');
        $before = libxml_use_internal_errors(true);

        try {
            XmlHelper::canonicalize($document);
            $this->assertTrue(libxml_use_internal_errors($before));
        } finally {
            libxml_use_internal_errors($before);
        }
    }
}
