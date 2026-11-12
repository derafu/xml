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

use Derafu\Value\Exception\TypeMismatchException as ValueTypeMismatchException;
use Derafu\Value\ValueType;
use Derafu\Xml\Exception\XmlQueryException;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XPathQuery;
use Derafu\Xml\XPathReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

enum XPathReaderKind: string
{
    case Standard = '33';
    case Simple = '39';
}

#[CoversClass(XPathReader::class)]
#[UsesClass(XPathQuery::class)]
#[UsesClass(XmlDocument::class)]
#[UsesClass(XmlQueryException::class)]
#[UsesClass(XmlError::class)]
class XPathReaderTest extends TestCase
{
    private const XML = <<<XML
        <order>
            <header>
                <info>
                    <docType>33</docType>
                    <number>0021</number>
                    <issued>2025-03-07</issued>
                    <active>true</active>
                    <blank/>
                </info>
                <seller>
                    <taxId>TAX-1</taxId>
                    <name>Seller name</name>
                    <phone>111</phone>
                    <phone>222</phone>
                </seller>
                <totals>
                    <net>1000</net>
                    <rate>19.00</rate>
                    <tax>190</tax>
                    <levy>
                        <levyType>15</levyType>
                        <levyAmount>10</levyAmount>
                    </levy>
                </totals>
            </header>
            <line>
                <pos>1</pos>
                <title>Item A</title>
                <qty>2</qty>
                <code><scheme>EAN</scheme><value>111</value></code>
                <code><scheme>INT</scheme><value>222</value></code>
            </line>
            <line>
                <pos>2</pos>
                <title>Item B</title>
                <qty>3</qty>
                <code><scheme>EAN</scheme><value>333</value></code>
            </line>
            <shipping>
                <customs/>
            </shipping>
        </order>
        XML;

    private const XML_SINGLE = <<<XML
        <order>
            <line>
                <pos>1</pos>
                <title>Only</title>
            </line>
            <phone>111</phone>
        </order>
        XML;

    private const XML_NAMESPACES = <<<XML
        <Invoice xmlns="urn:example:invoice" xmlns:cbc="urn:example:basic">
            <cbc:ID>F-1</cbc:ID>
            <cbc:Note>Uno</cbc:Note>
            <cbc:Note>Dos</cbc:Note>
        </Invoice>
        XML;

    private function reader(string $xml = self::XML, ?string $source = null): XPathReader
    {
        $document = new XmlDocument();
        $document->loadXml($xml);

        return XPathReader::fromDocument($document, [], $source);
    }

    private function exception(callable $read): XmlQueryException
    {
        try {
            $read();
        } catch (XmlQueryException $exception) {
            return $exception;
        }

        $this->fail('An XmlQueryException was expected.');
    }

    // Creation.

    public function testFromDocumentAndConstructorAreTheSame(): void
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML);

        $fromDocument = XPathReader::fromDocument($document);
        $constructed = new XPathReader($document);

        $this->assertSame(
            $constructed->requireString('//name'),
            $fromDocument->requireString('//name')
        );
    }

    public function testNamespacesAreIgnoredByDefault(): void
    {
        $this->assertSame('F-1', $this->reader(self::XML_NAMESPACES)->requireString('//ID'));
    }

    public function testNamespacesAreUsedWhenTheyAreGiven(): void
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML_NAMESPACES);
        $reader = XPathReader::fromDocument($document, ['cbc' => 'urn:example:basic']);

        $this->assertSame('F-1', $reader->requireString('//cbc:ID'));
        $this->assertSame(['Uno', 'Dos'], $reader->requireStringList('//cbc:Note'));
        $this->assertNull($reader->getString('//ID'));
    }

    public function testItReadsTheDocumentEachTimeItIsAsked(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<a><b>1</b></a>');
        $reader = XPathReader::fromDocument($document);
        $this->assertSame('1', $reader->requireString('//b'));

        $document->loadXml('<a><b>2</b></a>');
        $this->assertSame('2', $reader->requireString('//b'));
    }

    // Presence.

    public function testHasKeyAndHasValue(): void
    {
        $reader = $this->reader();

        $this->assertTrue($reader->hasKey('//number'));
        $this->assertTrue($reader->hasValue('//number'));
        $this->assertFalse($reader->hasKey('//missing'));
        $this->assertFalse($reader->hasValue('//missing'));
    }

    public function testAnEmptyElementHasKeyAndValue(): void
    {
        $reader = $this->reader();

        $this->assertTrue($reader->hasKey('//blank'));
        $this->assertTrue($reader->hasValue('//blank'));
        $this->assertSame('', $reader->getString('//blank'));
    }

    public function testAnInvalidXPathThrows(): void
    {
        $this->expectException(XmlQueryException::class);

        $this->reader()->hasKey('//[');
    }

    // Generic read.

    public function testGetReadsAsAType(): void
    {
        $reader = $this->reader();

        $this->assertSame('0021', $reader->get('//number', ValueType::string()));
        $this->assertSame(21, $reader->get('//number', ValueType::toInt()));
    }

    public function testGetReturnsTheDefaultWhenNoNodeMatches(): void
    {
        $reader = $this->reader();

        $this->assertNull($reader->get('//missing', ValueType::string()));
        $this->assertSame('x', $reader->get('//missing', ValueType::string(), 'x'));
    }

    public function testRequireReadsAsAType(): void
    {
        $this->assertSame(21, $this->reader()->require('//number', ValueType::toInt()));
    }

    public function testRequireThrowsWhenNoNodeMatches(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->require('//missing', ValueType::string()));

        $this->assertSame('//missing', $exception->getXpathExpression());
        $this->assertNotNull($exception->getXmlDocument());
        $this->assertStringContainsString('//missing', $exception->getMessage());
        $this->assertNull($exception->getPrevious());
    }

    public function testTheErrorOfAMissingNodeSaysTheSource(): void
    {
        $reader = $this->reader(self::XML, 'envio.xml');

        $exception = $this->exception(fn () => $reader->requireString('//missing'));

        $this->assertStringContainsString('//missing', $exception->getMessage());
        $this->assertStringContainsString('envio.xml', $exception->getMessage());
    }

    public function testTheErrorOfAWrongTypeHasTheCause(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getString('//seller'));

        $this->assertSame('//seller', $exception->getXpathExpression());
        $this->assertNotNull($exception->getXmlDocument());
        $this->assertInstanceOf(ValueTypeMismatchException::class, $exception->getPrevious());
        $this->assertStringContainsString('//seller', $exception->getMessage());
        $this->assertStringContainsString('string', $exception->getMessage());
        $this->assertStringContainsString('array', $exception->getMessage());
    }

    public function testTheErrorOfAWrongTypeSaysTheSource(): void
    {
        $reader = $this->reader(self::XML, 'envio.xml');

        $exception = $this->exception(fn () => $reader->getString('//seller'));

        $this->assertStringContainsString('envio.xml', $exception->getMessage());
    }

    // Strict getters.

    public function testStringGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame('Seller name', $reader->getString('//name'));
        $this->assertNull($reader->getString('//missing'));
        $this->assertSame('x', $reader->getString('//missing', 'x'));
        $this->assertSame('Seller name', $reader->requireString('//name'));
    }

    public function testRequireStringThrowsWhenItIsNotAString(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->requireString('//seller');
    }

    public function testNonEmptyStringGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame('Item A', $reader->getNonEmptyString('//line[1]/title'));
        $this->assertNull($reader->getNonEmptyString('//missing'));
        $this->assertSame('Item A', $reader->requireNonEmptyString('//line[1]/title'));
    }

    public function testNonEmptyStringRejectsAnEmptyElement(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getNonEmptyString('//blank');
    }

    public function testDateTimeGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame('2025-03-07', $reader->getDateTime('//issued')?->format('Y-m-d'));
        $this->assertNull($reader->getDateTime('//missing'));
        $this->assertSame('2025-03-07', $reader->requireDateTime('//issued')->format('Y-m-d'));
    }

    public function testDateTimeRejectsATextThatIsNotADate(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getDateTime('//name');
    }

    public function testEnumGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame(XPathReaderKind::Standard, $reader->getEnum('//docType', XPathReaderKind::class));
        $this->assertNull($reader->getEnum('//missing', XPathReaderKind::class));
        $this->assertSame(XPathReaderKind::Standard, $reader->requireEnum('//docType', XPathReaderKind::class));
    }

    public function testEnumRejectsAValueThatIsNotACase(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getEnum('//name', XPathReaderKind::class);
    }

    public function testArrayAndMapGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame('TAX-1', $reader->getArray('//seller')['taxId'] ?? null);
        $this->assertSame('TAX-1', $reader->getMap('//seller')['taxId'] ?? null);
        $this->assertNull($reader->getArray('//missing'));
        $this->assertNull($reader->getMap('//missing'));
        $this->assertSame('TAX-1', $reader->requireArray('//seller')['taxId'] ?? null);
        $this->assertSame('TAX-1', $reader->requireMap('//seller')['taxId'] ?? null);
    }

    public function testMapRejectsAText(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getMap('//name');
    }

    public function testMapOfAndArrayOfGetters(): void
    {
        $reader = $this->reader();

        $this->assertSame(
            ['levyType' => '15', 'levyAmount' => '10'],
            $reader->getMapOf('//levy', ValueType::string())
        );
        $this->assertSame(
            ['levyType' => 15, 'levyAmount' => 10],
            $reader->requireMapOf('//levy', ValueType::toInt())
        );
        $this->assertSame(
            ['levyType' => 15, 'levyAmount' => 10],
            $reader->getArrayOf('//levy', ValueType::toInt())
        );
        $this->assertSame(
            ['levyType' => '15', 'levyAmount' => '10'],
            $reader->requireArrayOf('//levy', ValueType::string())
        );
        $this->assertNull($reader->getMapOf('//missing', ValueType::string()));
        $this->assertNull($reader->getArrayOf('//missing', ValueType::string()));
    }

    // Lists: one node or many.

    public function testListGettersTakeManyNodesAsAList(): void
    {
        $reader = $this->reader();

        $this->assertSame(['111', '222'], $reader->getStringList('//phone'));
        $this->assertSame(['111', '222'], $reader->requireStringList('//phone'));
        $this->assertSame(['111', '222'], $reader->getList('//phone'));
        $this->assertSame(['111', '222'], $reader->requireList('//phone'));
    }

    public function testListGettersTakeOneNodeAsAListOfOne(): void
    {
        $reader = $this->reader(self::XML_SINGLE);

        $this->assertSame(['111'], $reader->getStringList('//phone'));
        $this->assertSame(['111'], $reader->requireStringList('//phone'));
        $this->assertSame(['111'], $reader->getList('//phone'));
        $this->assertCount(1, $reader->requireList('//line'));
    }

    public function testListGettersReturnNullWhenNoNodeMatches(): void
    {
        $reader = $this->reader();

        $this->assertNull($reader->getStringList('//missing'));
        $this->assertNull($reader->getList('//missing'));
        $this->assertNull($reader->getListOf('//missing', ValueType::string()));
    }

    public function testRequiredListsThrowWhenNoNodeMatches(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->requireList('//missing'));

        $this->assertSame('//missing', $exception->getXpathExpression());
        $this->expectException(XmlQueryException::class);
        $reader->requireStringList('//missing');
    }

    public function testListOfConvertsTheItems(): void
    {
        $reader = $this->reader();

        $this->assertSame([1, 2], $reader->getListOf('//line/pos', ValueType::toInt()));
        $this->assertSame([2, 3], $reader->requireListOf('//line/qty', ValueType::toInt()));
    }

    public function testListOfOneNodeIsAListOfOne(): void
    {
        $reader = $this->reader(self::XML_SINGLE);

        $this->assertSame([1], $reader->getListOf('//line/pos', ValueType::toInt()));
    }

    public function testListOfRejectsAnItemOfAnotherType(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getListOf('//line/pos', ValueType::int()));

        $this->assertSame('//line/pos', $exception->getXpathExpression());
        $this->assertStringContainsString('//line/pos.0', $exception->getMessage());
    }

    public function testStringListRejectsAListOfStructures(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getStringList('//line');
    }

    // Conversions.

    public function testIntConversion(): void
    {
        $reader = $this->reader();

        $this->assertSame(21, $reader->getAsInt('//number'));
        $this->assertSame(21, $reader->requireAsInt('//number'));
        $this->assertNull($reader->getAsInt('//missing'));
        $this->assertSame(7, $reader->getAsInt('//missing', 7));
    }

    public function testIntConversionRejectsAnImpossibleConversion(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getAsInt('//name'));

        $this->assertSame('//name', $exception->getXpathExpression());
    }

    public function testRequiredConversionThrowsWhenNoNodeMatches(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->requireAsInt('//missing');
    }

    public function testFloatConversion(): void
    {
        $reader = $this->reader();

        $this->assertSame(19.0, $reader->getAsFloat('//rate'));
        $this->assertSame(19.0, $reader->requireAsFloat('//rate'));
        $this->assertNull($reader->getAsFloat('//missing'));
        $this->assertSame(1.5, $reader->getAsFloat('//missing', 1.5));
    }

    public function testNumberConversion(): void
    {
        $reader = $this->reader();

        $this->assertSame(1000, $reader->getAsNumber('//net'));
        $this->assertSame(1000, $reader->requireAsNumber('//net'));
        $this->assertSame(19, $reader->requireAsNumber('//rate'));
        $this->assertNull($reader->getAsNumber('//missing'));
        $this->assertSame(5, $reader->getAsNumber('//missing', 5));
    }

    public function testBoolConversion(): void
    {
        $reader = $this->reader();

        $this->assertTrue($reader->getAsBool('//active'));
        $this->assertTrue($reader->requireAsBool('//active'));
        $this->assertNull($reader->getAsBool('//missing'));
        $this->assertFalse($reader->getAsBool('//missing', false));
    }

    public function testStringConversion(): void
    {
        $reader = $this->reader();

        $this->assertSame('0021', $reader->getAsString('//number'));
        $this->assertSame('0021', $reader->requireAsString('//number'));
        $this->assertNull($reader->getAsString('//missing'));
        $this->assertSame('x', $reader->getAsString('//missing', 'x'));
    }

    #[DataProvider('provideConversionsThatThrow')]
    public function testConversionsThatCannotBeDone(string $method): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->{$method}('//name');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideConversionsThatThrow(): array
    {
        return [
            'int' => ['requireAsInt'],
            'float' => ['requireAsFloat'],
            'number' => ['requireAsNumber'],
            'bool' => ['requireAsBool'],
        ];
    }

    // Empty element.

    public function testAnEmptyElementIsAnEmptyStructure(): void
    {
        $reader = $this->reader();

        $this->assertSame([], $reader->getArray('//customs'));
        $this->assertSame([], $reader->getMap('//customs'));
        $this->assertSame([], $reader->requireArray('//customs'));
        $this->assertSame([], $reader->requireMap('//customs'));
        $this->assertSame([], $reader->getMapOf('//customs', ValueType::string()));
        $this->assertSame([], $reader->requireMapOf('//customs', ValueType::string()));
        $this->assertSame([], $reader->getArrayOf('//customs', ValueType::string()));
        $this->assertSame([], $reader->requireArrayOf('//customs', ValueType::string()));
    }

    public function testAnEmptyElementIsAnEmptyStructureInTheItemsOfAList(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><item/><item><a>1</a></item><one/></r>');
        $reader = $document->getReader();

        $this->assertSame([[], ['a' => '1']], $reader->getListOf('//item', ValueType::map()));
        $this->assertSame([[], ['a' => '1']], $reader->requireListOf('//item', ValueType::array()));
        $this->assertSame([[]], $reader->getListOf('//one', ValueType::map()));
        $this->assertSame([[]], $reader->requireListOf('//one', ValueType::array()));
    }

    public function testAnEmptyElementIsAnEmptyStructureInTheValuesOfAStructure(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><x><e/><f><a>1</a></f></x></r>');
        $reader = $document->getReader();

        $this->assertSame(['e' => [], 'f' => ['a' => '1']], $reader->getMapOf('//x', ValueType::map()));
        $this->assertSame(['e' => [], 'f' => ['a' => '1']], $reader->requireMapOf('//x', ValueType::map()));
        $this->assertSame(['e' => [], 'f' => ['a' => '1']], $reader->getArrayOf('//x', ValueType::array()));
        $this->assertSame(['e' => [], 'f' => ['a' => '1']], $reader->requireArrayOf('//x', ValueType::array()));
    }

    public function testAnEmptyElementIsAnEmptyStringInTheItemsThatAreNotStructures(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><s/><s>x</s></r>');
        $reader = $document->getReader();

        $this->assertSame(['', 'x'], $reader->getListOf('//s', ValueType::string()));
        $this->assertSame(['', 'x'], $reader->getStringList('//s'));
    }

    public function testAnEmptyElementIsAnEmptyStringForTheGenericRead(): void
    {
        $reader = $this->reader();

        $this->assertSame('', $reader->get('//customs', ValueType::string()));
        $this->assertSame('', $reader->require('//customs', ValueType::string()));
    }

    public function testAnElementWithTextIsNotAnEmptyStructure(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getMap('//name');
    }

    public function testAMissingStructureIsNotAnEmptyStructure(): void
    {
        $reader = $this->reader();

        $this->assertNull($reader->getMap('//missing'));
        $this->expectException(XmlQueryException::class);
        $reader->requireMap('//missing');
    }

    // Readers of a node.

    public function testReaderOfANode(): void
    {
        $reader = $this->reader()->getReader('//seller');

        $this->assertInstanceOf(XPathReader::class, $reader);
        $this->assertSame('TAX-1', $reader->requireString('taxId'));
        $this->assertSame('TAX-1', $reader->requireString('./taxId'));
        $this->assertSame(['111', '222'], $reader->getStringList('phone'));
    }

    public function testReaderOfAnEmptyElementIsEmpty(): void
    {
        $reader = $this->reader()->requireReader('//customs');

        $this->assertSame([], $reader->toArray());
        $this->assertFalse($reader->hasKey('Codigo'));
        $this->assertNull($reader->getString('Codigo'));
    }

    public function testReaderOfAMissingNodeIsNull(): void
    {
        $this->assertNull($this->reader()->getReader('//missing'));
    }

    public function testRequiredReaderThrowsWhenNoNodeMatches(): void
    {
        $this->expectException(XmlQueryException::class);

        $this->reader()->requireReader('//missing');
    }

    public function testReaderRejectsAnElementWithText(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getReader('//name'));

        $this->assertSame('//name', $exception->getXpathExpression());
    }

    public function testReaderRejectsANodeThatIsNotAnElement(): void
    {
        // With namespaces the XPath is used as it is, with `text()` too.
        $document = new XmlDocument();
        $document->loadXml(self::XML);
        $reader = XPathReader::fromDocument($document, ['x' => 'urn:example:unused']);

        $exception = $this->exception(fn () => $reader->getReader('//number/text()'));

        $this->assertSame('//number/text()', $exception->getXpathExpression());
        $this->assertInstanceOf(ValueTypeMismatchException::class, $exception->getPrevious());
    }

    public function testReaderRejectsAStructureThatIsNotOfTheType(): void
    {
        $reader = $this->reader();

        $this->expectException(XmlQueryException::class);

        $reader->getReader('//line[1]', ValueType::mapOf(ValueType::int()));
    }

    public function testReaderRejectsAnXPathThatMatchesManyNodes(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getReader('//line'));

        $this->assertSame('//line', $exception->getXpathExpression());
        $this->assertStringContainsString('2', $exception->getMessage());
        $this->expectException(XmlQueryException::class);
        $reader->requireReader('//line');
    }

    public function testTheErrorOfManyNodesSaysTheSource(): void
    {
        $reader = $this->reader(self::XML, 'envio.xml');

        $exception = $this->exception(fn () => $reader->getReader('//line'));

        $this->assertStringContainsString('envio.xml', $exception->getMessage());
    }

    public function testReaderReportsItsErrorsWithTheFullXPath(): void
    {
        $reader = $this->reader()->requireReader('//seller');

        $exception = $this->exception(fn () => $reader->requireString('missing'));

        $this->assertSame('//seller/missing', $exception->getXpathExpression());
        $this->assertStringContainsString('//seller/missing', $exception->getMessage());
        $this->assertNotNull($exception->getXmlDocument());
    }

    public function testReaderReportsItsErrorsWithTheSource(): void
    {
        $reader = $this->reader(self::XML, 'envio.xml')->requireReader('//seller');

        $exception = $this->exception(fn () => $reader->getAsInt('name'));

        $this->assertStringContainsString('envio.xml', $exception->getMessage());
        $this->assertStringContainsString('//seller/name', $exception->getMessage());
    }

    public function testAReaderOfAReaderKeepsTheFullXPath(): void
    {
        $reader = $this->reader()->requireReader('//header')->requireReader('./seller');

        $exception = $this->exception(fn () => $reader->requireString('./missing'));

        $this->assertSame('//header/seller/missing', $exception->getXpathExpression());
    }

    public function testTheAbsoluteXPathOfAReaderIsReportedAsItIs(): void
    {
        $reader = $this->reader()->requireReader('//seller');

        $exception = $this->exception(fn () => $reader->requireString('//missing'));

        $this->assertSame('//missing', $exception->getXpathExpression());
        $this->assertStringContainsString('"//missing"', $exception->getMessage());

        $exception = $this->exception(fn () => $reader->requireString('/order/missing'));

        $this->assertSame('/order/missing', $exception->getXpathExpression());
    }

    public function testAReaderCanReadFromTheRootWithADoubleSlash(): void
    {
        $reader = $this->reader()->requireReader('//seller');

        $this->assertSame('33', $reader->requireString('//docType'));
    }

    public function testReaderOfANodeReadsOnlyItsNode(): void
    {
        $readers = $this->reader()->requireReaders('//line');

        $this->assertSame('Item A', $readers[0]->requireString('title'));
        $this->assertSame('Item B', $readers[1]->requireString('title'));
        $this->assertTrue($readers[0]->hasKey('title'));
        $this->assertFalse($readers[0]->hasKey('phone'));
        $this->assertTrue($readers[0]->hasValue('qty'));
        $this->assertFalse($readers[0]->hasValue('missing'));
    }

    // Readers of many nodes.

    public function testReadersOfManyNodes(): void
    {
        $readers = $this->reader()->getReaders('//line');

        $this->assertNotNull($readers);
        $this->assertCount(2, $readers);
        $this->assertSame('Item A', $readers[0]->requireString('title'));
        $this->assertSame(3, $readers[1]->requireAsInt('qty'));
    }

    public function testReadersOfOneNode(): void
    {
        $readers = $this->reader(self::XML_SINGLE)->getReaders('//line');

        $this->assertNotNull($readers);
        $this->assertCount(1, $readers);
        $this->assertSame('Only', $readers[0]->requireString('title'));
    }

    public function testReadersOfAMissingNodeAreNull(): void
    {
        $this->assertNull($this->reader()->getReaders('//missing'));
    }

    public function testRequiredReaders(): void
    {
        $readers = $this->reader()->requireReaders('//line');

        $this->assertCount(2, $readers);
        $this->assertSame('Item B', $readers[1]->requireString('title'));
    }

    public function testRequiredReadersThrowWhenNoNodeMatches(): void
    {
        $this->expectException(XmlQueryException::class);

        $this->reader()->requireReaders('//missing');
    }

    public function testReadersReportTheirErrorsWithThePositionAndTheSource(): void
    {
        $readers = $this->reader(self::XML, 'envio.xml')->requireReaders('//line');

        $exception = $this->exception(fn () => $readers[1]->requireString('missing'));

        $this->assertSame('(//line)[2]/missing', $exception->getXpathExpression());
        $this->assertStringContainsString('(//line)[2]/missing', $exception->getMessage());
        $this->assertStringContainsString('envio.xml', $exception->getMessage());
    }

    public function testReadersRejectAListOfTexts(): void
    {
        $reader = $this->reader();

        $exception = $this->exception(fn () => $reader->getReaders('//phone'));

        $this->assertSame('(//phone)[1]', $exception->getXpathExpression());
    }

    public function testReadersRejectAStructureThatIsNotOfTheType(): void
    {
        $this->expectException(XmlQueryException::class);

        $this->reader()->getReaders('//line', ValueType::mapOf(ValueType::int()));
    }

    public function testReadersOfAReaderTakeOneNodeOrManyAtAnyDepth(): void
    {
        $readers = $this->reader()->requireReaders('//line');

        $codes = $readers[0]->getReaders('code');
        $this->assertNotNull($codes);
        $this->assertCount(2, $codes);
        $this->assertSame('222', $codes[1]->requireString('value'));

        $codes = $readers[1]->getReaders('code');
        $this->assertNotNull($codes);
        $this->assertCount(1, $codes);
        $this->assertSame('333', $codes[0]->requireString('value'));

        $this->assertNull($readers[1]->getReaders('missing'));
        $this->assertSame(['EAN', 'INT'], $readers[0]->requireListOf('code/scheme', ValueType::string()));
    }

    // Whole structure.

    public function testToArrayIsTheArrayOfTheDocument(): void
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML);

        $this->assertSame($document->toArray(), XPathReader::fromDocument($document)->toArray());
    }

    public function testToArrayOfAReaderIsTheStructureOfItsNode(): void
    {
        $reader = $this->reader()->requireReader('//totals');

        $this->assertSame('1000', $reader->toArray()['net'] ?? null);
        $this->assertSame('1000', $reader->toMap()['net'] ?? null);
        $this->assertSame(['net' => '1000'], array_intersect_key($reader->to(ValueType::map()), ['net' => 0]));
    }

    public function testToMapAndToAndToList(): void
    {
        $reader = $this->reader('<a><b>1</b></a>');

        $this->assertSame(['a' => ['b' => '1']], $reader->toMap());
        $this->assertSame(['a' => ['b' => '1']], $reader->to(ValueType::arrayOf(ValueType::array())));
    }

    public function testToListRejectsAMap(): void
    {
        $reader = $this->reader('<a><b>1</b></a>');

        $exception = $this->exception(fn () => $reader->toList());

        $this->assertSame('/', $exception->getXpathExpression());
        $this->assertNotNull($exception->getXmlDocument());
        $this->assertInstanceOf(ValueTypeMismatchException::class, $exception->getPrevious());
    }

    public function testToOfAReaderRejectsAStructureThatIsNotOfTheType(): void
    {
        $reader = $this->reader()->requireReader('//totals');

        $exception = $this->exception(fn () => $reader->toList());

        $this->assertSame('//totals', $exception->getXpathExpression());
        $this->assertStringContainsString('//totals', $exception->getMessage());
    }

    public function testAssertOnlyKeys(): void
    {
        $reader = $this->reader('<a><b>1</b></a>');

        $this->assertSame($reader, $reader->assertOnlyKeys('a'));
    }

    public function testAssertOnlyKeysThrowsForAnotherKey(): void
    {
        $reader = $this->reader('<a><b>1</b></a>');

        $exception = $this->exception(fn () => $reader->assertOnlyKeys('x'));

        $this->assertSame('/a', $exception->getXpathExpression());
        $this->assertNotNull($exception->getXmlDocument());
        $this->assertStringContainsString('/a', $exception->getMessage());
        $this->assertStringContainsString('x', $exception->getMessage());
    }

    public function testAssertOnlyKeysOfAReaderChecksTheChildren(): void
    {
        $reader = $this->reader('<a><b><c>1</c><d>2</d></b></a>')->requireReader('//b');

        $this->assertSame($reader, $reader->assertOnlyKeys('c', 'd'));

        $exception = $this->exception(fn () => $reader->assertOnlyKeys('c'));

        $this->assertSame('//b/d', $exception->getXpathExpression());
        $this->assertStringContainsString('//b/d', $exception->getMessage());
    }

    public function testAssertOnlyKeysSaysTheSource(): void
    {
        $reader = $this->reader('<a><b>1</b></a>', 'envio.xml');

        $exception = $this->exception(fn () => $reader->assertOnlyKeys('x'));

        $this->assertStringContainsString('envio.xml', $exception->getMessage());
    }

    // From the document.

    public function testTheDocumentGivesItsReader(): void
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML_NAMESPACES);

        $reader = $document->getReader(['cbc' => 'urn:example:basic'], 'invoice.xml');

        $this->assertSame('F-1', $reader->requireString('//cbc:ID'));
        $exception = $this->exception(fn () => $reader->requireString('//cbc:missing'));
        $this->assertStringContainsString('invoice.xml', $exception->getMessage());
    }

    public function testTheDocumentGivesAReaderWithoutNamespacesByDefault(): void
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML_NAMESPACES);

        $this->assertSame('F-1', $document->getReader()->requireString('//ID'));
    }

    public function testAKeyThatIsANumberCanBeAllowed(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><a>1</a></r>');

        $reader = $document->getReader();

        $this->assertSame($reader, $reader->assertOnlyKeys('r', 7));
    }

    /**
     * A node that is not an element has no structure, even if it has no text.
     */
    public function testANodeThatIsNotAnElementIsNotAReader(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r e="" f="v"/>');
        $reader = $document->getReader();

        foreach (['/r/@e', '/r/@f'] as $xpath) {
            $exception = $this->exception(fn () => $reader->requireReader($xpath));

            $this->assertSame('The value of the XPath "' . $xpath . '" must be of type array, string given.', $exception->getMessage());
        }
    }

    /**
     * The error says what failed inside of the list, not the list.
     */
    public function testTheErrorOfAnItemSaysTheTypeOfTheItemAndTheValueThatFailed(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><pos>1</pos><pos>x</pos></r>');

        $exception = $this->exception(fn () => $document->getReader()->getListOf('//pos', ValueType::int()));

        $this->assertSame('The value of the XPath "//pos.0" must be of type int, string given.', $exception->getMessage());
    }

    /**
     * An empty element is an empty structure only for the items whose type is a
     * structure: `bool|map` and `map|null` are other types.
     */
    public function testAnEmptyElementIsNotAnEmptyStructureForATypeThatOnlyHasOneInIt(): void
    {
        $document = new XmlDocument();
        $document->loadXml('<r><item/></r>');
        $reader = $document->getReader();

        $this->assertSame([[]], $reader->getListOf('//item', ValueType::map()));

        $union = $this->exception(fn () => $reader->getListOf('//item', ValueType::oneOf(ValueType::bool(), ValueType::map())));
        $nullable = $this->exception(fn () => $reader->getListOf('//item', ValueType::map()->nullable()));

        $this->assertStringContainsString('must be of type bool|map, string given', $union->getMessage());
        $this->assertStringContainsString('must be of type map, string given', $nullable->getMessage());
    }
}
