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

use Derafu\Xml\Exception\XmlEncoderException;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlHelper;
use DOMElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * What an XML has is in the array that is decoded from it, and it is in the XML
 * that is encoded from the array: the namespaces that its elements declare, its
 * comments and its text just as it is.
 */
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XmlEncoderException::class)]
#[CoversClass(XmlException::class)]
#[UsesClass(XmlError::class)]
final class XmlFidelityTest extends TestCase
{
    private function load(string $xml): XmlDocument
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        return $document;
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function namespacesProvider(): array
    {
        return [
            'the default namespace' => [
                '<r xmlns="urn:d"><a>1</a></r>',
                ['r' => ['@attributes' => ['xmlns' => 'urn:d'], 'a' => '1']],
            ],
            // They are sorted by name, the default one first, and they go
            // before the attributes.
            'the namespaces are sorted by name' => [
                '<r xmlns:b="urn:b" xmlns="urn:d" xmlns:a="urn:a"/>',
                ['r' => ['@attributes' => ['xmlns' => 'urn:d', 'xmlns:a' => 'urn:a', 'xmlns:b' => 'urn:b']]],
            ],
            'the namespaces go before the attributes' => [
                '<r id="1" xmlns:x="urn:x"/>',
                ['r' => ['@attributes' => ['xmlns:x' => 'urn:x', 'id' => '1']]],
            ],
            // A namespace is where it is declared, not in what inherits it.
            'a namespace is not repeated in what inherits it' => [
                '<r xmlns:x="urn:x"><x:a><x:b>1</x:b></x:a></r>',
                ['r' => ['@attributes' => ['xmlns:x' => 'urn:x'], 'x:a' => ['x:b' => '1']]],
            ],
            'a namespace declared in a child' => [
                '<r><x:a xmlns:x="urn:x">1</x:a></r>',
                ['r' => ['x:a' => ['@attributes' => ['xmlns:x' => 'urn:x'], '@value' => '1']]],
            ],
            'a namespace declared again with another URI' => [
                '<r xmlns="urn:a"><b xmlns="urn:b"><c/></b></r>',
                ['r' => ['@attributes' => ['xmlns' => 'urn:a'], 'b' => ['@attributes' => ['xmlns' => 'urn:b'], 'c' => '']]],
            ],
            'a namespace declared again with the same URI is not a declaration' => [
                '<r xmlns="urn:a"><b xmlns="urn:a">1</b></r>',
                ['r' => ['@attributes' => ['xmlns' => 'urn:a'], 'b' => '1']],
            ],
            // Without it, the element would be in the default namespace of its
            // parent.
            'the default namespace that is left without URI' => [
                '<r xmlns="urn:d"><a xmlns="">1</a></r>',
                ['r' => ['@attributes' => ['xmlns' => 'urn:d'], 'a' => ['@attributes' => ['xmlns' => ''], '@value' => '1']]],
            ],
            'a repeated element that declares a namespace' => [
                '<r><a xmlns="urn:a">1</a><a>2</a></r>',
                ['r' => ['a' => [['@attributes' => ['xmlns' => 'urn:a'], '@value' => '1'], '2']]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('namespacesProvider')]
    public function testItDecodesTheNamespacesThatTheElementsDeclare(string $xml, array $expected): void
    {
        $this->assertSame($expected, (new XmlDecoder())->decode($this->load($xml)));
    }

    public function testAnElementThatIsDecodedAloneDeclaresWhatItNeeds(): void
    {
        $document = $this->load('<r xmlns:x="urn:x"><x:a>1</x:a></r>');
        $element = $document->getDomDocument()->getElementsByTagName('a')->item(0);
        $this->assertInstanceOf(DOMElement::class, $element);

        $this->assertSame(
            ['x:a' => ['@attributes' => ['xmlns:x' => 'urn:x'], '@value' => '1']],
            (new XmlDecoder())->decode($element)
        );
    }

    public function testAnElementThatIsNotInADocumentCanNotBeDecoded(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('The element does not belong to an XML document.');

        (new XmlDecoder())->decode(new DOMElement('a'));
    }

    public function testTheElementsKeepTheirNamespacesDecodingAndEncodingAgain(): void
    {
        $xml = '<r xmlns="urn:d" xmlns:p="urn:p"><p:a>1</p:a><b xmlns="urn:e">2</b><c xmlns="">3</c></r>';

        $array = (new XmlDecoder())->decode($this->load($xml));
        $encoded = (new XmlEncoder())->encode($array);

        // Without loading it again, and loading it again.
        $this->assertSame($array, (new XmlDecoder())->decode($encoded));
        $reloaded = $this->load($encoded->saveXml())->getDomDocument();
        $names = [];
        foreach ($reloaded->getElementsByTagName('*') as $element) {
            $names[$element->nodeName] = $element->namespaceURI;
        }
        $this->assertSame(['r' => 'urn:d', 'p:a' => 'urn:p', 'b' => 'urn:e', 'c' => null], $names);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function commentsProvider(): array
    {
        return [
            'a comment, as it is written' => [
                '<r><!-- c --></r>',
                ['r' => ['@comment' => ' c ']],
            ],
            'a comment among elements' => [
                '<r><!--c--><a>1</a></r>',
                ['r' => ['@comment' => 'c', 'a' => '1']],
            ],
            // The place of the key is the one of the first comment.
            'several comments are a list' => [
                '<r><a>x</a><!--1--><b>y</b><!--2--></r>',
                ['r' => ['a' => 'x', '@comment' => ['1', '2'], 'b' => 'y']],
            ],
            'a comment in an element with attributes' => [
                '<r id="1"><!--c--></r>',
                ['r' => ['@attributes' => ['id' => '1'], '@comment' => 'c']],
            ],
            // The text that a comment separates is in parts, as with elements.
            'a comment in an element with text' => [
                '<r>a <!--c--> b</r>',
                ['r' => ['@value' => 'a b', '@comment' => 'c']],
            ],
            'the spaces around a comment are not text' => [
                "<r>\n  <!--c-->\n</r>",
                ['r' => ['@comment' => 'c']],
            ],
            'a comment in a repeated element' => [
                '<r><a><!--c--></a><a>2</a></r>',
                ['r' => ['a' => [['@comment' => 'c'], '2']]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('commentsProvider')]
    public function testItDecodesTheComments(string $xml, array $expected): void
    {
        $this->assertSame($expected, (new XmlDecoder())->decode($this->load($xml)));
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('commentsProvider')]
    public function testTheCommentsKeepTheirPlaceDecodingAndEncodingAgain(string $xml, array $expected): void
    {
        $encoded = (new XmlEncoder())->encode((new XmlDecoder())->decode($this->load($xml)));

        $this->assertSame($expected, (new XmlDecoder())->decode($encoded));
        $this->assertSame($expected, (new XmlDecoder())->decode($this->load($encoded->saveXml())));
    }

    public function testItEncodesTheComments(): void
    {
        $encoded = (new XmlEncoder())->encode(['r' => [
            '@comment' => ['one', null, false, [], ' two '],
            'a' => '1',
        ]]);

        $this->assertSame("<r>\n  <!--one-->\n  <!-- two -->\n  <a>1</a>\n</r>", $encoded->getXml());
    }

    public function testACommentThatMeansNothingIsNotWritten(): void
    {
        $encoded = (new XmlEncoder())->encode(['r' => ['@comment' => null, 'a' => '1']]);

        $this->assertSame("<r>\n  <a>1</a>\n</r>", $encoded->getXml());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function commentsThatAreNotValidProvider(): array
    {
        return [
            'two hyphens in a row' => [['r' => ['@comment' => 'a--b']], 'A comment can not have two hyphens in a row nor end with a hyphen: a--b'],
            'it ends with a hyphen' => [['r' => ['@comment' => ['ok', 'a-']]], 'A comment can not have two hyphens in a row nor end with a hyphen: a-'],
            'it is not text' => [['r' => ['@comment' => new stdClass()]], 'The value of "@comment" can not be written as text, stdClass given.'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('commentsThatAreNotValidProvider')]
    public function testACommentThatIsNotValidIsNotWritten(array $data, string $message): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage($message);

        (new XmlEncoder())->encode($data);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function textProvider(): array
    {
        return [
            'the spaces around the text of an element' => [
                '<r><a>  x  </a></r>',
                ['r' => ['a' => '  x  ']],
            ],
            'an element with only spaces' => [
                "<r><a> </a><b>\n  </b></r>",
                ['r' => ['a' => ' ', 'b' => "\n  "]],
            ],
            'the spaces around the text of repeated elements' => [
                '<r><a> 1</a><a>2 </a></r>',
                ['r' => ['a' => [' 1', '2 ']]],
            ],
            'the spaces around the text of an element with attributes' => [
                '<r id="1">  x  </r>',
                ['r' => ['@attributes' => ['id' => '1'], '@value' => '  x  ']],
            ],
            'the spaces of an element that has to keep them' => [
                '<r xml:space="preserve">  keep  </r>',
                ['r' => ['@attributes' => ['xml:space' => 'preserve'], '@value' => '  keep  ']],
            ],
            'the text in parts of CDATA and text is the text' => [
                '<r>a<![CDATA[ b ]]>c</r>',
                ['r' => 'a b c'],
            ],
            'the lines of a text' => [
                "<r><a>\nline 1\nline 2\n</a></r>",
                ['r' => ['a' => "\nline 1\nline 2\n"]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('textProvider')]
    public function testItDecodesTheTextJustAsItIs(string $xml, array $expected): void
    {
        $this->assertSame($expected, (new XmlDecoder())->decode($this->load($xml)));
    }

    public function testTheTextJustAsItIsSurvivesDecodingAndEncodingAgain(): void
    {
        $document = $this->load("<r><a>  x  </a><b>\n  y\n</b><c> </c><d> 1</d><d>2 </d><e id=\"1\"> z </e></r>");

        $encoded = (new XmlEncoder())->encode((new XmlDecoder())->decode($document));

        $this->assertSame($document->C14N(), $encoded->C14N());
    }

    /**
     * The attributes with a prefix keep it: it is what says to which namespace
     * they belong (`xsi:schemaLocation` is not `schemaLocation`).
     */
    public function testTheAttributesKeepTheirPrefix(): void
    {
        $xml = '<r xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:x s.xsd" xml:space="preserve" id="1"/>';

        $array = (new XmlDecoder())->decode($this->load($xml));

        $this->assertSame(
            ['r' => ['@attributes' => [
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:x s.xsd',
                'xml:space' => 'preserve',
                'id' => '1',
            ]]],
            $array
        );

        // And once it is encoded, the attribute is in the namespace again.
        $root = $this->load((new XmlEncoder())->encode($array)->saveXml())->getDomDocument()->documentElement;
        $this->assertNotNull($root);
        $this->assertSame(
            'urn:x s.xsd',
            $root->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation')
        );
    }

    public function testTheTextAmongElementsIsStillInPartsWithoutTheSpacesBetweenThem(): void
    {
        $this->assertSame(
            ['r' => ['@value' => 'one two', 'b' => 'x']],
            (new XmlDecoder())->decode($this->load("<r>\n  one <b>x</b>\n  two\n</r>"))
        );
    }
}
