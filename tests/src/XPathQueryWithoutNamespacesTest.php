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
use Derafu\Xml\XmlError;
use Derafu\Xml\XPathQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Without namespaces the XPath expressions are read as if the names had no
 * namespace: the names match the elements by their local name. Everything else
 * of an XPath expression is what XPath says: the functions, the axes, the
 * node tests, the operators, the literals and the attributes.
 */
#[CoversClass(XPathQuery::class)]
#[CoversClass(XmlQueryException::class)]
#[UsesClass(XmlDocument::class)]
#[UsesClass(XmlError::class)]
class XPathQueryWithoutNamespacesTest extends TestCase
{
    private const XML = <<<XML
        <r xmlns="urn:d" xmlns:p="urn:p">
            <my-el id="7" data-x="9">x</my-el>
            <a><b>1</b><b>2</b></a>
            <c.d>y</c.d>
            <a2>t</a2>
            <s v="/b"/>
            <n><v>3</v><v>4</v></n>
            <p:q>pp</p:q>
            <and>z</and>
            <or>o</or>
            <div>d</div>
            <mod>m</mod>
        </r>
        XML;

    /**
     * @param string|array<int|string, mixed>|null $expected
     * @param array<string, string> $params
     */
    #[DataProvider('provideQueries')]
    public function testTheNamesMatchByLocalName(
        string $query,
        string|array|null $expected,
        array $params = []
    ): void {
        $this->assertSame($expected, $this->query()->get($query, $params));
    }

    /**
     * @return array<string, array{0: string, 1: string|array<int|string, mixed>|null, 2?: array<string, string>}>
     */
    public static function provideQueries(): array
    {
        return [
            // The names, as they are.
            'child' => ['//a/b', ['1', '2']],
            'absolute path' => ['/r/a2', 't'],
            'a name with a hyphen' => ['//my-el', 'x'],
            'a name with a dot' => ['//c.d', 'y'],
            'a name that is a prefix of another' => ['//a2', 't'],
            'a name without namespace in the document' => ['//q', 'pp'],
            'a name that does not exist' => ['//zzz', null],

            // The first name of an expression is a name test, not an operator.
            'a name at the start' => ['a2', 't'],

            // What ends an operand is followed by an operator, not by a name test.
            'an operator after an attribute' => ['//my-el[@id and @data-x]', 'x'],
            'an operator after a function' => ['//a[last() and b]/b', ['1', '2']],
            'an operator after a predicate' => ['//a[b[1] and b[2]]/b', ['1', '2']],
            'an operator after the context' => ['//b[. and text()]', ['1', '2']],
            'an operator after the parent' => ['//b[.. and .]', ['1', '2']],
            'an operator after a wildcard' => ['//a[* and b]/b', ['1', '2']],
            'an operator after a literal' => ['//b[text()="2" and .="2"]', '2'],
            'a name after a multiplication' => ['//a[2 * b = 2]/b', ['1', '2']],
            'a name after the axis of an attribute' => ['//my-el/attribute::id | //a2', ['7', 't']],

            // Names that are the same as the operators.
            'an element named and' => ['//and', 'z'],
            'an element named or' => ['/r/or', 'o'],
            'an element named div' => ['/r/div', 'd'],
            'an element named mod' => ['//mod', 'm'],

            // Attributes.
            'an attribute' => ['//my-el/@id', '7'],
            'an attribute with a hyphen' => ['//my-el/@data-x', '9'],
            'the attribute axis' => ['//my-el/attribute::id', '7'],
            'a predicate with an attribute' => ['//*[@id="7"]', 'x'],
            'a parameter' => ['//my-el[@id=:id]', 'x', ['id' => '7']],
            'a parameter before an operator' => ['//n[v=:x and v=:y]/v', ['3', '4'], ['x' => '3', 'y' => '4']],
            'a parameter with a hyphen' => ['//my-el[@id=:my-id]', 'x', ['my-id' => '7']],

            // Node tests and the wildcard.
            'text()' => ['//a/b/text()', ['1', '2']],
            'text() of one node' => ['//a/b[1]/text()', '1'],
            'node()' => ['//a/node()', ['1', '2']],
            'the wildcard' => ['//a/*', ['1', '2']],
            'the wildcard with a position' => ['//a/*[2]', '2'],
            'the parent' => ['//b/..', ['b' => ['1', '2']]],

            // Axes.
            'the child axis' => ['//a/child::b', ['1', '2']],
            'the parent axis' => ['//b/parent::a', ['b' => ['1', '2']]],
            'an axis with a hyphen' => ['//a/following-sibling::a2', 't'],

            // Predicates and functions.
            'a position' => ['//a/b[1]', '1'],
            'a position with spaces' => ['//a/b [ 2 ]', '2'],
            'position()' => ['//a/b[position()=2]', '2'],
            'position() with an operator' => ['//a/b[position()>1]', '2'],
            'last()' => ['//a/b[last()]', '2'],
            'text() in a predicate' => ['//b[text()="2"]', '2'],
            'the context in a predicate' => ['//b[.="2"]', '2'],
            'a name in a predicate' => ['//a[b="2"]/b', ['1', '2']],
            'a function with a name' => ['//a[count(b)=2]/b', ['1', '2']],
            'a function with a name and a literal' => ['//a[string-length(b)=1 and b="2"]/b', ['1', '2']],
            'a group with a position' => ['(//a/b)[2]', '2'],

            // Operators.
            'and' => ['//n[v=3 and v=4]/v', ['3', '4']],
            'and between names' => ['//n[v and v]/v', ['3', '4']],
            'or' => ['//n[v=3 or v=9]/v', ['3', '4']],
            'mod' => ['//n/v[. mod 2 = 1]', '3'],
            'div' => ['//n/v[. div 2 = 2]', '4'],
            'a multiplication' => ['//n/v[. * 2 = 6]', '3'],
            'a union' => ['//a/b | //a2', ['1', '2', 't']],
            'a union with positions' => ['//a/b[1] | //a/b[2]', ['1', '2']],
            'a negation' => ['//n/v[not(. = 3)]', '4'],

            // Literals.
            'a literal that looks like a path' => ['//s[@v="/b"]', ''],
            'a literal in single quotes' => ["//a[b='2']/b", ['1', '2']],
            'a literal that has a name' => ['//n[v="b and c"]', null],
        ];
    }

    public function testAnExpressionThatIsNotValidThrows(): void
    {
        $this->expectException(XmlQueryException::class);

        $this->query()->get('//a[');
    }

    private function query(): XPathQuery
    {
        $document = new XmlDocument();
        $document->loadXml(self::XML);

        return new XPathQuery($document);
    }

    /**
     * An attribute is the one that has that name and no namespace: the one of
     * another namespace with the same local name is another attribute.
     */
    public function testAnAttributeIsTheOneWithoutNamespace(): void
    {
        $query = new XPathQuery('<r xmlns="urn:d"><l xml:lang="en" lang="es"/></r>');

        $this->assertSame('es', $query->get('//l/@lang'));
        $this->assertSame('es', $query->get('//l/attribute::lang'));
        $this->assertSame('es', $query->get('//l[@lang and @xml:lang]/@lang'));
    }

    /**
     * What is not a name test stays as it is, so libxml says it in the words of
     * the expression: the error has the expression that was run.
     */
    public function testAPrefixedNameIsLeftAsItIs(): void
    {
        try {
            $this->query()->get('//p:q');
            $this->fail('A prefix that is not registered was accepted.');
        } catch (XmlQueryException $e) {
            $this->assertSame(
                'An error occurred while executing the XPath expression: //p:q. Undefined namespace prefix.',
                $e->getMessage()
            );
        }
    }

    public function testAVariableIsLeftAsItIs(): void
    {
        try {
            $this->query()->get('//a[b=$v]');
            $this->fail('A variable that is not defined was accepted.');
        } catch (XmlQueryException $e) {
            $this->assertStringContainsString('=$v].', $e->getMessage());
        }
    }

    public function testWhatFollowsAnythingElseIsANameTest(): void
    {
        try {
            $this->query()->get('//a#b');
            $this->fail('An expression that is not valid was accepted.');
        } catch (XmlQueryException $e) {
            $this->assertStringContainsString('#*[local-name()="b"]', $e->getMessage());
        }
    }
}
