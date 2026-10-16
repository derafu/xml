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
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Decoding of XML to an array: the elements that are repeated, the elements
 * without content and the elements with text in several parts.
 */
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlHelper::class)]
final class XmlDecoderTest extends TestCase
{
    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function xmlProvider(): array
    {
        return [
            // The attributes of each repeated element are in the element.
            'repeated elements with attributes' => [
                '<order id="7"><item cod="A1"><name>Coffee</name></item><item cod="B2"><name>Tea</name></item></order>',
                ['order' => [
                    '@attributes' => ['id' => '7'],
                    'item' => [
                        ['@attributes' => ['cod' => 'A1'], 'name' => 'Coffee'],
                        ['@attributes' => ['cod' => 'B2'], 'name' => 'Tea'],
                    ],
                ]],
            ],
            // A repeated element that only has text and attributes.
            'repeated elements with text and attributes' => [
                '<r><t a="1">x</t><t a="2">y</t></r>',
                ['r' => ['t' => [
                    ['@attributes' => ['a' => '1'], '@value' => 'x'],
                    ['@attributes' => ['a' => '2'], '@value' => 'y'],
                ]]],
            ],
            // A repeated element that only has attributes.
            'repeated elements with only attributes' => [
                '<r><i a="1"/><i a="2"/></r>',
                ['r' => ['i' => [
                    ['@attributes' => ['a' => '1']],
                    ['@attributes' => ['a' => '2']],
                ]]],
            ],
            // The repeated elements inside repeated elements are in each one.
            'repeated elements inside repeated elements' => [
                '<r><i><s>1</s><s>2</s><n>a</n></i><i><s>3</s><s>4</s><n>b</n></i></r>',
                ['r' => ['i' => [
                    ['s' => ['1', '2'], 'n' => 'a'],
                    ['s' => ['3', '4'], 'n' => 'b'],
                ]]],
            ],
            // Repeated elements without attributes keep being a list of values.
            'repeated simple elements' => [
                '<r><i>1</i><i>2</i><i>3</i></r>',
                ['r' => ['i' => ['1', '2', '3']]],
            ],
            // An element without content is an empty text (its value in the data
            // model of XML is the empty string), in any position.
            'empty element as the first child' => [
                '<root><element/></root>',
                ['root' => ['element' => '']],
            ],
            'empty elements among others' => [
                '<order><client>Ana</client><empty/><other>x</other><empty2></empty2></order>',
                ['order' => ['client' => 'Ana', 'empty' => '', 'other' => 'x', 'empty2' => '']],
            ],
            'empty elements inside repeated elements' => [
                '<r><i><v/><w>1</w></i><i><v/><w>2</w></i></r>',
                ['r' => ['i' => [['v' => '', 'w' => '1'], ['v' => '', 'w' => '2']]]],
            ],
            'an element with only spaces and an element with only a comment' => [
                '<r><a>   </a><b><!-- c --></b></r>',
                ['r' => ['a' => '   ', 'b' => ['@comment' => ' c ']]],
            ],
            'an empty root element' => [
                '<r/>',
                ['r' => ''],
            ],
            // The text in several parts of an element is joined.
            'text in several parts' => [
                '<note>text <b>bold</b> end</note>',
                ['note' => ['@value' => 'text end', 'b' => 'bold']],
            ],
            'text in several parts with attributes' => [
                '<note k="1">one <b>x</b> two</note>',
                ['note' => ['@attributes' => ['k' => '1'], '@value' => 'one two', 'b' => 'x']],
            ],
            'text before and after repeated elements' => [
                '<r><a>1</a>tail<a>2</a></r>',
                ['r' => ['a' => ['1', '2'], '@value' => 'tail']],
            ],
            // The text of an element is exactly the one of the XML, and the spaces
            // between elements are not text.
            'text is exact' => [
                '<r>  hi  </r>',
                ['r' => '  hi  '],
            ],
            'spaces between elements are ignored' => [
                "<r>\n    <a>1</a>\n    <b>2</b>\n</r>",
                ['r' => ['a' => '1', 'b' => '2']],
            ],
            // The comments are in `@comment`, and a CDATA section is text.
            'comments are in @comment' => [
                '<r><!-- comment --><a>1</a></r>',
                ['r' => ['@comment' => ' comment ', 'a' => '1']],
            ],
            'CDATA is text' => [
                '<r><![CDATA[ <x> ]]></r>',
                ['r' => ' <x> '],
            ],
            'CDATA among text is part of it' => [
                '<r>a<![CDATA[b]]>c</r>',
                ['r' => 'abc'],
            ],
            // Attributes.
            'only attributes' => [
                '<r id="1"/>',
                ['r' => ['@attributes' => ['id' => '1']]],
            ],
            'attributes and text' => [
                '<r id="1">hi</r>',
                ['r' => ['@attributes' => ['id' => '1'], '@value' => 'hi']],
            ],
            'a child with only attributes' => [
                '<r><a id="1"/></r>',
                ['r' => ['a' => ['@attributes' => ['id' => '1']]]],
            ],
            // The names are the ones of the XML, with their prefix.
            'names with a prefix' => [
                '<x:r xmlns:x="urn:x"><x:a>1</x:a></x:r>',
                ['x:r' => ['@attributes' => ['xmlns:x' => 'urn:x'], 'x:a' => '1']],
            ],
            'nested elements' => [
                '<r><a><b><c>1</c></b></a></r>',
                ['r' => ['a' => ['b' => ['c' => '1']]]],
            ],
            // Repeated elements are a list, and the ones without content are
            // empty texts as an element that is not repeated.
            'repeated elements without content' => [
                '<r><a/><a/></r>',
                ['r' => ['a' => ['', '']]],
            ],
            'repeated elements of different kinds' => [
                '<r><a>1</a><a id="2"/></r>',
                ['r' => ['a' => ['1', ['@attributes' => ['id' => '2']]]]],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('xmlProvider')]
    public function testItDecodesTheXml(string $xml, array $expected): void
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        $this->assertSame($expected, (new XmlDecoder())->decode($document));
    }

    public function testAnElementOfTheDocumentIsDecodedAsTheDocumentIs(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><a id="1"><b>x</b><b>y</b></a><c>z</c></r>');
        $element = $document->getDomDocument()->getElementsByTagName('a')->item(0);
        $this->assertInstanceOf(DOMElement::class, $element);

        $this->assertSame(
            ['a' => ['@attributes' => ['id' => '1'], 'b' => ['x', 'y']]],
            (new XmlDecoder())->decode($element)
        );
    }

    public function testADocumentWithoutRootElementIsAnEmptyArray(): void
    {
        $this->assertSame([], (new XmlDecoder())->decode(new XmlDocument()));
    }
}
