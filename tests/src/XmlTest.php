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
use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XPathQuery::class)]
#[CoversClass(XmlQueryException::class)]
#[CoversClass(XmlParseException::class)]
class XmlTest extends TestCase
{
    /**
     * Verifies that the XML document is loaded correctly.
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
        $root = $doc->getDomDocument()->documentElement;
        $this->assertNotNull($root);
        $this->assertSame('root', $root->tagName);
    }

    /**
     * Verifies that the name of the root tag of the XML document is obtained
     * correctly.
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
     * Verifies that the namespace of the XML document is obtained when it
     * exists.
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
     * Verifies that the namespace of the XML document is obtained when it
     * does not exist.
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
     * Verifies that the schema associated with the XML document is obtained
     * when it exists.
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
     * Verifies that the schema associated with the XML document is obtained
     * when it does not exist.
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
     * Verifies that the saveXml() method generates the XML correctly.
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
     * Verifies that the C14N() method works correctly, generating the
     * canonical version of the XML.
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
     * Verifies that the C14NEncodedFlattened() method flattens the XML
     * document correctly.
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
     * Verifies that C14NEncoded() works correctly when an XPath expression
     * is given.
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
     * Verifies that an empty root element (self-closing) is loaded correctly.
     *
     * A document like <root/> is valid XML. The root element exists but it has
     * no children nor attributes. This tests that the parser does not reject it
     * and that getName() works without inner content.
     */
    public function testLoadEmptySelfClosingRoot(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root/>');

        $this->assertSame('root', $doc->getName());
        $root = $doc->getDomDocument()->documentElement;
        $this->assertNotNull($root);
        $this->assertFalse($root->hasChildNodes());
        $this->assertFalse($root->hasAttributes());
    }

    /**
     * Verifies that an empty root element with an explicit closing tag is
     * loaded and serialized correctly, and that decode() returns an empty text
     * for it.
     *
     * <root></root> and <root/> are semantically equivalent in XML. It also
     * verifies that the decoder treats them the same: it returns
     * ['root' => ''] (the value of an element without content is the empty
     * string).
     */
    public function testLoadEmptyRootWithExplicitClosingTag(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root></root>');

        $this->assertSame('root', $doc->getName());
        $root = $doc->getDomDocument()->documentElement;
        $this->assertNotNull($root);
        $this->assertFalse($root->hasAttributes());

        $decoded = (new XmlDecoder())->decode($doc);
        $this->assertSame(['root' => ''], $decoded);
    }

    /**
     * Verifies that get() returns the correct value with a dot selector.
     */
    public function testGetWithDotNotation(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child><value>42</value></child></root>');

        $this->assertSame('42', $doc->get('root.child.value'));
    }

    /**
     * Verifies that get() returns null when the selector does not exist.
     */
    public function testGetReturnsNullForMissingKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child>val</child></root>');

        $this->assertNull($doc->get('root.nonexistent'));
    }

    /**
     * Verifies that get() returns the given default when the selector does
     * not exist.
     */
    public function testGetReturnsCustomDefaultForMissingKey(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child>val</child></root>');

        $this->assertSame('fallback', $doc->get('root.nonexistent', 'fallback'));
    }

    /**
     * Verifies that getTopLevelSignatureC14N() returns the canonicalized XML
     * of the Signature node when it exists as a direct child of the root.
     */
    public function testGetTopLevelSignatureC14NReturnsCanonicalXml(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml(
            '<root><Signature><SignedInfo>data</SignedInfo></Signature></root>'
        );

        $result = $doc->getTopLevelSignatureC14N();

        $this->assertNotNull($result);
        $this->assertStringContainsString('<Signature>', $result);
        $this->assertStringContainsString('<SignedInfo>data</SignedInfo>', $result);
    }

    /**
     * Verifies that the signature is looked up under the root element
     * whatever the root is called: it is also found with a namespace prefix.
     */
    public function testGetTopLevelSignatureC14NWithARootWithAPrefix(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<p:r xmlns:p="urn:p"><Signature><a>1</a></Signature></p:r>');

        // The C14N declares the namespaces that are in scope: the one of the root.
        $this->assertSame('<Signature xmlns:p="urn:p"><a>1</a></Signature>', $doc->getTopLevelSignatureC14N());
    }

    /**
     * Verifies that the signature is found by its local name, in any
     * namespace or prefix, and that the result is the canonicalized XML.
     */
    public function testGetTopLevelSignatureC14NFindsTheSignatureInAnyNamespace(): void
    {
        $dsig = 'http://www.w3.org/2000/09/xmldsig#';

        $withDefault = new XmlDocument();
        $withDefault->loadXml('<r xmlns="urn:r"><Signature xmlns="' . $dsig . '"><a/></Signature></r>');
        $withPrefix = new XmlDocument();
        $withPrefix->loadXml('<r xmlns:ds="' . $dsig . '"><ds:Signature><ds:a/></ds:Signature></r>');

        $this->assertSame('<Signature xmlns="' . $dsig . '"><a></a></Signature>', $withDefault->getTopLevelSignatureC14N());
        $this->assertSame('<ds:Signature xmlns:ds="' . $dsig . '"><ds:a></ds:a></ds:Signature>', $withPrefix->getTopLevelSignatureC14N());
    }

    /**
     * Verifies that it is canonical XML and not a serialization: without the
     * XML declaration, with the attributes sorted and without abbreviated
     * empty elements.
     */
    public function testGetTopLevelSignatureC14NIsCanonicalXml(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<?xml version="1.0" encoding="UTF-8"?><r><Signature z="1" a="2"><e/></Signature></r>');

        $this->assertSame('<Signature a="2" z="1"><e></e></Signature>', $doc->getTopLevelSignatureC14N());
    }

    /**
     * Verifies that only the direct children of the root element are
     * searched: a signature deeper in the document is not found. With several,
     * the first one is given.
     */
    public function testGetTopLevelSignatureC14NOnlyLooksAmongTheChildrenOfTheRoot(): void
    {
        $deeper = new XmlDocument();
        $deeper->loadXml('<r><ext><Signature>x</Signature></ext></r>');
        $several = new XmlDocument();
        $several->loadXml('<r><Signature>first</Signature><Signature>second</Signature></r>');

        $this->assertNull($deeper->getTopLevelSignatureC14N());
        $this->assertSame('<Signature>first</Signature>', $several->getTopLevelSignatureC14N());
    }

    /**
     * Verifies that getTopLevelSignatureC14N() returns null when there is no
     * Signature node.
     */
    public function testGetTopLevelSignatureC14NReturnsNullWhenAbsent(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element></root>');

        $this->assertNull($doc->getTopLevelSignatureC14N());
    }

    /**
     * A document without content has no root element: what is asked to the
     * root is a usage error, not a value that does not exist, so it is not
     * confused with a `null`.
     *
     * @return array<string, array{string}>
     */
    public static function methodsThatNeedTheRootProvider(): array
    {
        return [
            'getName' => ['getName'],
            'getNamespace' => ['getNamespace'],
            'getSchema' => ['getSchema'],
            'getTopLevelSignatureC14N' => ['getTopLevelSignatureC14N'],
            'requireDocumentElement' => ['requireDocumentElement'],
        ];
    }

    /**
     * What `getDocumentElement()` gives (a `DOMElement` or `null`) is the same
     * element, but a document without it is an error and not a `null`: whoever
     * asks for the element needs it, and has no `null` to check.
     */
    public function testTheDocumentElementThatIsRequiredIsTheRootElement(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><child/></root>');

        $this->assertSame($doc->getDocumentElement(), $doc->requireDocumentElement());
        $this->assertSame('root', $doc->requireDocumentElement()->tagName);
    }

    #[DataProvider('methodsThatNeedTheRootProvider')]
    public function testADocumentWithoutRootElementFailsWhenTheRootIsNeeded(string $method): void
    {
        $doc = new XmlDocument();

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The XML document has no document element.');

        $doc->$method();
    }

    /**
     * The regular expressions of PHP can fail (for example, when the
     * backtracking limit is exhausted): the failure is an error of the package,
     * not a `null` that goes on as if it were the text.
     *
     * The expressions that PHP already compiled with JIT do not respect the
     * limit, so the tests that use it run in a separate process.
     */
    private function failRegularExpressions(callable $do): void
    {
        $jit = ini_set('pcre.jit', '0');
        $limit = ini_set('pcre.backtrack_limit', '1');

        try {
            $do();
        } finally {
            ini_set('pcre.backtrack_limit', (string) $limit);
            ini_set('pcre.jit', (string) $jit);
        }
    }

    #[RunInSeparateProcess]
    public function testGetXmlFailsWhenTheDeclarationCanNotBeRemoved(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element></root>');

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The XML declaration could not be removed.');

        $this->failRegularExpressions(fn () => $doc->getXml());
    }

    #[RunInSeparateProcess]
    public function testC14NEncodedFlattenedFailsWhenTheSpacesCanNotBeRemoved(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml("<root>\n    <element>Value</element>\n</root>");

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The spaces between the tags could not be removed.');

        $this->failRegularExpressions(fn () => $doc->C14NEncodedFlattened());
    }

    #[RunInSeparateProcess]
    public function testSanitizeFailsWhenTheControlCharactersCanNotBeRemoved(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The text could not be sanitized.');

        $this->failRegularExpressions(fn () => XmlHelper::sanitize("Value \x01 with control characters"));
    }

    #[RunInSeparateProcess]
    public function testAnXPathQueryFailsWhenItCanNotBePrepared(): void
    {
        $query = new XPathQuery('<root><element>Value</element></root>');

        $this->expectException(XmlQueryException::class);
        $this->expectExceptionMessage('The XPath expression could not be prepared.');

        $this->failRegularExpressions(fn () => $query->get('/root/element'));
    }

    /**
     * Verifies that loading an empty XML is a parse error.
     */
    public function testLoadEmptyXmlFails(): void
    {
        $doc = new XmlDocument();

        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage('The XML content that you want to load is empty.');

        $doc->loadXml('');
    }

    /**
     * Verifies that getXml() gives the XML without the declaration, and
     * saveXml() with it.
     */
    public function testGetXmlIsTheXmlWithoutTheDeclaration(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<?xml version="1.0" encoding="UTF-8"?><root><element>Value</element></root>');

        $this->assertSame("<root>\n  <element>Value</element>\n</root>", $doc->getXml());
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $doc->saveXml());
    }

    /**
     * Verifies that getXml() gives the XML without the declaration, whatever
     * the declaration of the XML is: without encoding, with it or with
     * standalone.
     *
     * @return array<string, array{string}>
     */
    public static function declarationsProvider(): array
    {
        return [
            'without declaration' => ['<root><a>1</a></root>'],
            'without encoding' => ['<?xml version="1.0"?><root><a>1</a></root>'],
            'with encoding' => ['<?xml version="1.0" encoding="UTF-8"?><root><a>1</a></root>'],
            'with standalone' => ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root><a>1</a></root>'],
        ];
    }

    #[DataProvider('declarationsProvider')]
    public function testGetXmlHasNoDeclarationWhateverItIs(string $xml): void
    {
        $doc = new XmlDocument();
        $doc->loadXml($xml);

        $this->assertSame("<root>\n  <a>1</a>\n</root>", $doc->getXml());
    }

    /**
     * Verifies that the document is serialized to JSON as its array.
     */
    public function testTheDocumentIsSerializedToJsonAsItsArray(): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<root><element>Value</element><list>1</list><list>2</list></root>');

        $this->assertSame(
            '{"root":{"element":"Value","list":["1","2"]}}',
            json_encode($doc)
        );
    }

    /**
     * Verifies that a document without content is an empty array, and that
     * asking it for a value gives the default value.
     */
    public function testADocumentWithoutContentIsAnEmptyArray(): void
    {
        $doc = new XmlDocument();

        $this->assertSame([], $doc->toArray());
        $this->assertSame([], $doc->jsonSerialize());
        $this->assertSame('default', $doc->get('root.element', 'default'));
    }

    /**
     * Verifies that setFormatOutput() is fluent and propagates the value to
     * the inner DOMDocument.
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
     * Verifies that setPreserveWhiteSpace() is fluent and propagates the
     * value to the inner DOMDocument.
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
     * Verifies that C14NEncoded() throws an exception when the XPath
     * expression matches no node.
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

    /**
     * An XML whose namespace is a relative URI is loaded by libxml, but it can
     * not be canonicalized (C14N): the document that can not be canonicalized
     * is an error of the package, not a `false` that goes on as if it were the
     * text.
     *
     * @return array<string, array{callable(XmlDocument): mixed}>
     */
    public static function canonicalizationsThatFailProvider(): array
    {
        return [
            'C14N' => [fn (XmlDocument $doc) => $doc->C14N()],
            'the document' => [fn (XmlDocument $doc) => $doc->C14NEncoded()],
            'a node, by its XPath' => [fn (XmlDocument $doc) => $doc->C14NEncoded('/*')],
            'the top level signature' => [fn (XmlDocument $doc) => $doc->getTopLevelSignatureC14N()],
        ];
    }

    /**
     * @param callable(XmlDocument): mixed $canonicalize
     */
    #[DataProvider('canonicalizationsThatFailProvider')]
    public function testWhatCanNotBeCanonicalizedIsAnException(callable $canonicalize): void
    {
        $doc = new XmlDocument();
        $doc->loadXml('<r xmlns="relative"><Signature xmlns="relative"><x/></Signature></r>');

        try {
            $canonicalize($doc);
            $this->fail('It should have failed.');
        } catch (XmlException $e) {
            $this->assertSame('The XML could not be canonicalized.', $e->getMessage());
            // What libxml said is in the errors, it is not emitted as a warning.
            $this->assertStringContainsString('Relative namespace', implode(' ', $e->getErrors()));
        }
    }
}
