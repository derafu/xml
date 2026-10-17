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

use DateTimeImmutable;
use Derafu\Xml\Exception\XmlEncoderException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

/**
 * Encoding of an array to XML: which values make a node, which ones do not, how
 * the attributes and the repeated nodes are written and what is not valid.
 */
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlEncoderException::class)]
#[CoversClass(XmlHelper::class)]
#[UsesClass(XmlError::class)]
final class XmlEncoderTest extends TestCase
{
    /**
     * @return array<string, array{array<int|string, mixed>, string}>
     */
    public static function arrayProvider(): array
    {
        return [
            // The values of the nodes.
            'a text' => [
                ['r' => ['a' => 'x']],
                "<r>\n  <a>x</a>\n</r>",
            ],
            'numbers are text' => [
                ['r' => ['i' => 5, 'f' => 1.5]],
                "<r>\n  <i>5</i>\n  <f>1.5</f>\n</r>",
            ],
            'true is 1' => [
                ['r' => ['a' => true]],
                "<r>\n  <a>1</a>\n</r>",
            ],
            // An empty text is a node without content, the values that mean
            // nothing are not nodes.
            'an empty text is an empty node' => [
                ['r' => ['a' => '']],
                "<r>\n  <a/>\n</r>",
            ],
            'false, null and an empty array are not nodes' => [
                ['r' => ['a' => false, 'b' => null, 'c' => [], 'd' => 'x']],
                "<r>\n  <d>x</d>\n</r>",
            ],
            // Attributes and the text of an element with attributes.
            'attributes' => [
                ['r' => ['@attributes' => ['id' => '1', 'empty' => ''], 'a' => 'x']],
                "<r id=\"1\" empty=\"\">\n  <a>x</a>\n</r>",
            ],
            'numbers and true are written as attributes' => [
                ['r' => ['@attributes' => ['n' => 7, 'f' => 1.5, 't' => true], 'a' => 'x']],
                "<r n=\"7\" f=\"1.5\" t=\"1\">\n  <a>x</a>\n</r>",
            ],
            'an attribute that is false is not written' => [
                ['r' => ['@attributes' => ['x' => false], 'a' => 'x']],
                "<r>\n  <a>x</a>\n</r>",
            ],
            'an attribute that is null is not written' => [
                ['r' => ['@attributes' => ['id' => null], 'a' => 'x']],
                "<r>\n  <a>x</a>\n</r>",
            ],
            'the value of an element with attributes' => [
                ['r' => ['@attributes' => ['id' => '1'], '@value' => 'text']],
                '<r id="1">text</r>',
            ],
            // Repeated nodes: a list of values or of nodes.
            'a list of texts' => [
                ['r' => ['a' => ['x', 'y']]],
                "<r>\n  <a>x</a>\n  <a>y</a>\n</r>",
            ],
            'a list skips what means nothing but keeps the empty texts' => [
                ['r' => ['a' => ['x', null, '', false, 'y']]],
                "<r>\n  <a>x</a>\n  <a/>\n  <a>y</a>\n</r>",
            ],
            'a list of nodes' => [
                ['r' => ['a' => [['b' => '1'], ['b' => '2']]]],
                "<r>\n  <a>\n    <b>1</b>\n  </a>\n  <a>\n    <b>2</b>\n  </a>\n</r>",
            ],
            'a node with nodes' => [
                ['r' => ['a' => ['b' => '1', 'c' => '2']]],
                "<r>\n  <a>\n    <b>1</b>\n    <c>2</c>\n  </a>\n</r>",
            ],
            // The text is made valid for XML.
            'control characters are removed' => [
                ['r' => ['a' => "x\x01y"]],
                "<r>\n  <a>xy</a>\n</r>",
            ],
            'special characters are escaped' => [
                ['r' => ['a' => 'a & b < c']],
                "<r>\n  <a>a &amp; b &lt; c</a>\n</r>",
            ],
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     */
    #[DataProvider('arrayProvider')]
    public function testItEncodesTheArray(array $data, string $expected): void
    {
        $this->assertSame($expected, (new XmlEncoder())->encode($data)->getXml());
    }

    public function testItGivesADocumentThatCanBeDecodedBack(): void
    {
        $data = ['r' => ['@attributes' => ['id' => '1'], 'a' => ['x', 'y'], 'b' => ['c' => 'z']]];

        $document = (new XmlEncoder())->encode($data);

        $this->assertInstanceOf(XmlDocument::class, $document);
        $this->assertSame($data, (new XmlDecoder())->decode($document));
    }

    /**
     * The elements without content are not lost when an XML is decoded and
     * encoded again: an empty element is the empty text, and it is encoded as
     * an empty element.
     */
    public function testTheEmptyElementsSurviveDecodingAndEncodingAgain(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><a/><b>x</b><c/><c/></r>');

        $encoded = (new XmlEncoder())->encode((new XmlDecoder())->decode($document));

        $this->assertSame(
            "<r>\n  <a/>\n  <b>x</b>\n  <c/>\n  <c/>\n</r>",
            $encoded->getXml()
        );
    }

    /**
     * The name of a node or an attribute is text: a number is the position of
     * a value in a list, so where a name is needed it is not valid.
     *
     * @return array<string, array{array<int|string, mixed>, int}>
     */
    public static function dataWithANameThatIsNotTextProvider(): array
    {
        return [
            'a list as the root' => [[['a' => 'x']], 0],
            'a value without name among the nodes' => [['r' => ['a' => '1', 1 => 'x', 'b' => '2']], 1],
            'an attribute without name' => [['r' => ['@attributes' => ['x', 'y'], 'a' => 'x']], 0],
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     */
    #[DataProvider('dataWithANameThatIsNotTextProvider')]
    public function testAnythingWithoutANameIsNotValid(array $data, int $name): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage(sprintf('The name of a node or an attribute must be text, %d given.', $name));

        (new XmlEncoder())->encode($data);
    }

    /**
     * @return array<string, array{array<int|string, mixed>, string}>
     */
    public static function dataWithAValueThatIsNotTextProvider(): array
    {
        return [
            'a node' => [['r' => ['a' => new stdClass()]], 'The value of "a" can not be written as text, stdClass given.'],
            'a node in a list' => [['r' => ['a' => ['x', new DateTimeImmutable()]]], 'The value of "a" can not be written as text, DateTimeImmutable given.'],
            'an attribute' => [['r' => ['@attributes' => ['id' => new stdClass()]]], 'The value of "r/@id" can not be written as text, stdClass given.'],
            'the value of a node with attributes' => [['r' => ['@attributes' => ['id' => '1'], '@value' => new stdClass()]], 'The value of "r" can not be written as text, stdClass given.'],
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     */
    #[DataProvider('dataWithAValueThatIsNotTextProvider')]
    public function testAValueThatCanNotBeWrittenAsTextIsNotValid(array $data, string $message): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage($message);

        (new XmlEncoder())->encode($data);
    }

    public function testAnObjectThatCanBeAStringIsWrittenAsIt(): void
    {
        $value = new class () implements Stringable {
            public function __toString(): string
            {
                return 'its text';
            }
        };

        $this->assertSame(
            "<r id=\"its text\">\n  <a>its text</a>\n  <b>its text</b>\n  <b>x</b>\n</r>",
            (new XmlEncoder())->encode(['r' => ['@attributes' => ['id' => $value], 'a' => $value, 'b' => [$value, 'x']]])->getXml()
        );
    }

    /**
     * The text of an element is written before its child elements and its
     * comments, wherever `@value` is: no position loses it.
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function valueInAnyPlaceProvider(): array
    {
        return [
            'first' => [['@value' => 'text', '@attributes' => ['id' => '1'], 'a' => '1', 'b' => '2']],
            'among the others' => [['@attributes' => ['id' => '1'], 'a' => '1', '@value' => 'text', 'b' => '2']],
            'last' => [['@attributes' => ['id' => '1'], 'a' => '1', 'b' => '2', '@value' => 'text']],
        ];
    }

    /**
     * @param array<string, mixed> $element
     */
    #[DataProvider('valueInAnyPlaceProvider')]
    public function testTheValueOfAnElementIsWrittenWhereverItIs(array $element): void
    {
        $this->assertSame(
            "<r id=\"1\">text<a>1</a><b>2</b></r>",
            (new XmlEncoder())->encode(['r' => $element])->getXml()
        );
    }

    public function testTheValueOfAnElementIsWrittenBeforeItsComments(): void
    {
        $this->assertSame(
            '<r>text<!--c--><a>1</a></r>',
            (new XmlEncoder())->encode(['r' => ['@comment' => 'c', 'a' => '1', '@value' => 'text']])->getXml()
        );
    }

    public function testTheValueOfAnElementWithElementsIsInTheDecodedArray(): void
    {
        $document = (new XmlEncoder())->encode(['r' => ['a' => '1', '@value' => 'text']]);

        $this->assertSame(
            ['r' => ['@value' => 'text', 'a' => '1']],
            (new XmlDecoder())->decode($document)
        );
    }

    public function testTheValueThatMeansNothingIsNotWritten(): void
    {
        $this->assertSame(
            "<r>\n  <a>1</a>\n</r>",
            (new XmlEncoder())->encode(['r' => ['a' => '1', '@value' => null]])->getXml()
        );
    }

    /**
     * An empty value is an empty text of the node: the node has a closing tag,
     * as it is written for any element that has a text.
     */
    public function testAnEmptyValueIsAnEmptyText(): void
    {
        $this->assertSame(
            '<r id="1"></r>',
            (new XmlEncoder())->encode(['r' => ['@attributes' => ['id' => '1'], '@value' => '']])->getXml()
        );
    }

    public function testTheValueOfNoElementIsNotValid(): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage('A text can not be written outside of an element.');

        (new XmlEncoder())->encode(['@value' => 'text', 'r' => '1']);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function severalRootsProvider(): array
    {
        return [
            'two nodes' => [['a' => '1', 'b' => '2'], 'There are 2: a, b.'],
            'a list' => [['r' => ['x', 'y', 'z']], 'There are 3: r, r, r.'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('severalRootsProvider')]
    public function testAnXmlWithSeveralRootsIsNotValid(array $data, string $message): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage('An XML document can only have one root element. ' . $message);

        (new XmlEncoder())->encode($data);
    }

    public function testNothingToWriteIsADocumentWithoutRoot(): void
    {
        $this->assertNull((new XmlEncoder())->encode([])->getDocumentElement());
    }

    public function testAnAttributeThatIsAnArrayIsNotValid(): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage('for the attribute "id" of the node "r" is incorrect');

        (new XmlEncoder())->encode(['r' => ['@attributes' => ['id' => ['x']], 'a' => 'x']]);
    }

    public function testAListOfListsIsNotValid(): void
    {
        $this->expectException(XmlEncoderException::class);
        $this->expectExceptionMessage('The node "a" allows including arrays, but they must be arrays with other nodes.');

        (new XmlEncoder())->encode(['r' => ['a' => [[['b' => '1']]]]]);
    }
}
