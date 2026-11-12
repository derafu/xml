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
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlHelper;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlHelper::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlDocument::class)]
#[UsesClass(XmlError::class)]
#[UsesClass(XmlErrorExplainer::class)]
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
            $this->assertStringStartsWith('The XML could not be canonicalized: ', $e->getMessage());
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

    /**
     * A number is not touched, whatever it has around it that XML would not
     * accept.
     */
    public function testXmlSanitizeLeavesANumberAsItIs(): void
    {
        $this->assertSame("12\x0B", XmlHelper::sanitize("12\x0B"));
        $this->assertSame("5\r", XmlHelper::sanitize("5\r"));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function entitiesProvider(): array
    {
        return [
            'the ampersand' => ['a &amp; b', 'a &amp; b'],
            'the ampersand as a number' => ['a &#38; b', 'a &amp; b'],
            'less than' => ['a &lt; b', 'a < b'],
            'less than as a number' => ['a &#60; b', 'a < b'],
            'greater than' => ['a &gt; b', 'a > b'],
            'greater than as a number' => ['a &#62; b', 'a > b'],
            'the double quote' => ['a &quot; b', 'a " b'],
            'the double quote as a number' => ['a &#34; b', 'a " b'],
            'the apostrophe' => ['a &apos; b', "a ' b"],
            'the apostrophe as a number' => ['a &#39; b', "a ' b"],
        ];
    }

    #[DataProvider('entitiesProvider')]
    public function testXmlSanitizeConvertsTheEntitiesThatAreAlreadyThere(string $input, string $expected): void
    {
        $this->assertSame($expected, XmlHelper::sanitize($input));
    }

    public function testXmlFixEntitiesDoesNotChangeTheTextBeforeTheFirstTag(): void
    {
        $this->assertSame('"x"<r>&quot;y&quot;</r>', XmlHelper::fixEntities('"x"<r>"y"</r>'));
    }

    public function testXmlFixEntitiesReadsAnEqualSignAtTheEndOfTheXml(): void
    {
        $this->assertSame('<a b=', XmlHelper::fixEntities('<a b='));
    }

    /**
     * A value of an attribute is not converted, whatever quote it has.
     */
    public function testXmlFixEntitiesKeepsTheAttributesWithSingleQuotes(): void
    {
        $this->assertSame("<a b='1>2'>&quot;t&quot;</a>", XmlHelper::fixEntities("<a b='1>2'>\"t\"</a>"));
    }

    /**
     * What follows an equal sign that is not a quote is not an attribute.
     */
    public function testXmlFixEntitiesDoesNotTakeAnEqualSignWithoutQuotesForAnAttribute(): void
    {
        $this->assertSame('<a b=x>&quot;t&quot;</a>', XmlHelper::fixEntities('<a b=x>"t"</a>'));
    }

    /**
     * The errors that were in libxml before are not the ones of the
     * canonicalization.
     */
    public function testTheErrorsThatLibxmlHadBeforeAreNotThoseOfTheCanonicalization(): void
    {
        $document = $this->documentThatCanNotBeCanonicalized();
        $before = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            (new DOMDocument())->loadXML('<bad');
            $this->assertNotEmpty(libxml_get_errors());

            XmlHelper::canonicalize($document);
            $this->fail('It should have failed.');
        } catch (XmlException $e) {
            $this->assertSame('The XML could not be canonicalized: Relative namespace URI is invalid here', $e->getMessage());
            $this->assertStringNotContainsString('Start Tag', implode(' ', $e->getErrors()));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($before);
        }
    }

    public function testTheErrorsOfTheCanonicalizationAreCleanedWhenLibxmlWasUsingInternalErrors(): void
    {
        $document = $this->documentThatCanNotBeCanonicalized();
        $before = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            XmlHelper::canonicalize($document);
            $this->fail('It should have failed.');
        } catch (XmlException) {
            $this->assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($before);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function markupProvider(): array
    {
        return [
            'a comment' => ['<r><!-- a > "q" --><t>"x"</t></r>', '<r><!-- a > "q" --><t>&quot;x&quot;</t></r>'],
            'a CDATA section' => ['<r><![CDATA[ a > "q" \'s\' ]]><t>"x"</t></r>', '<r><![CDATA[ a > "q" \'s\' ]]><t>&quot;x&quot;</t></r>'],
            'a processing instruction' => ['<r><?php a > "q" ?><t>"x"</t></r>', '<r><?php a > "q" ?><t>&quot;x&quot;</t></r>'],
            'the declaration of the XML' => ['<?xml version="1.0"?><r>"x"</r>', '<?xml version="1.0"?><r>&quot;x&quot;</r>'],
            'an attribute with a >' => ['<r a="x>y" b="z"><t>"x"</t></r>', '<r a="x>y" b="z"><t>&quot;x&quot;</t></r>'],
            'an attribute with a > and single quotes' => ["<r a='x>y' b='z'>\"x\"</r>", "<r a='x>y' b='z'>&quot;x&quot;</r>"],
            'an attribute with a quote of the other kind' => ['<r a="it\'s" b=\'say "x"\'>"x"</r>', '<r a="it\'s" b=\'say "x"\'>&quot;x&quot;</r>'],
            'a DOCTYPE with an entity that has a >' => [
                '<!DOCTYPE r [<!ENTITY e "a > b">]><r>"x"</r>',
                '<!DOCTYPE r [<!ENTITY e "a > b">]><r>&quot;x&quot;</r>',
            ],
            'a tag without a name' => ['<>"x"', '<>&quot;x&quot;'],
            'text after the last tag' => ['<r/>"x"', '<r/>&quot;x&quot;'],
            'two markups together' => ['<r><!-- a --><!-- b -->"x"</r>', '<r><!-- a --><!-- b -->&quot;x&quot;</r>'],
        ];
    }

    #[DataProvider('markupProvider')]
    public function testXmlFixEntitiesOnlyChangesTheContent(string $xml, string $expected): void
    {
        $this->assertSame($expected, XmlHelper::fixEntities($xml));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unclosedProvider(): array
    {
        return [
            'a tag' => ['<r>"x"</r><a b="1" c="'],
            'a tag with an open quote' => ['<r>"x"</r><a b=\'1'],
            'a tag without the end' => ['<r>"x"</r><a b="1"'],
            'a comment' => ['<r>"x"</r><!-- "q"'],
            'a CDATA section' => ['<r>"x"</r><![CDATA[ "q"'],
            'a processing instruction' => ['<r>"x"</r><?php "q"'],
        ];
    }

    /**
     * What is not closed is not content: it is left as it is, with the rest of
     * the XML.
     */
    #[DataProvider('unclosedProvider')]
    public function testXmlFixEntitiesLeavesWhatIsNotClosed(string $xml): void
    {
        $this->assertSame('<r>&quot;x&quot;</r>' . substr($xml, strlen('<r>"x"</r>')), XmlHelper::fixEntities($xml));
    }

    public function testXmlFixEntitiesDoesNotChangeAnXmlWithoutTags(): void
    {
        $this->assertSame('He said "x" > \'y\'', XmlHelper::fixEntities('He said "x" > \'y\''));
    }

    /**
     * A big CDATA section, with the quotes that it has, is not a problem.
     */
    public function testXmlFixEntitiesGoesThroughAVeryBigDocument(): void
    {
        $cdata = str_repeat('a > "b" ', 400000);
        $xml = '<r><![CDATA[' . $cdata . ']]><t>"x"</t></r>';

        $this->assertSame('<r><![CDATA[' . $cdata . ']]><t>&quot;x&quot;</t></r>', XmlHelper::fixEntities($xml));
    }
}
