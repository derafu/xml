<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml;

use BackedEnum;
use DateTimeInterface;
use Derafu\Value\Exception\TypeMismatchException as ValueTypeMismatchException;
use Derafu\Value\ValueType;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlQueryException;
use DOMElement;
use DOMNameSpaceNode;
use DOMNode;

/**
 * Typed reader of an XML document, selected by XPath.
 *
 * It is the analogue of `ArrayReader` for an XML document: the same methods
 * with the same contract, with an XPath expression where `ArrayReader` takes
 * a key.
 *
 *   - `getXxx()` is strict: it returns `null` if no node matches and throws if
 *     what it finds is not of the type.
 *   - `requireXxx()` is strict and it also throws if no node matches.
 *   - `getAsXxx()` and `requireAsXxx()` convert the text of the node to the
 *     type (`'0021'` is `21`), because the text of an XML node is always a
 *     string. There are no strict getters for `int`, `float`, `number` or
 *     `bool`: they could never succeed with the text of a node.
 *
 * What an XPath finds is what `XPathQuery::get()` returns: `null` if no node
 * matches, a string for a node that has only text, an array for a node that
 * has child elements, and a list if several nodes match.
 *
 * XML has no list type: an element that appears once is a value and an element
 * that appears several times is a list. The methods that read a list
 * (`getList()`, `getStringList()`, `getListOf()` and their `require` pairs)
 * take what the XPath finds, whether it is one node or many, as a list.
 *
 * An element without text and without children is an empty structure: the
 * methods that read a structure (`getArray()`, `getMap()`, `getArrayOf()`,
 * `getMapOf()`, their `require` pairs and the readers) take it as `[]`. The
 * generic `get()` and `require()` give it as `query()` does, an empty string.
 *
 * `getReader()` and `getReaders()` give readers of the matched nodes. They are
 * readers like this one, scoped to a node: their XPath expressions are
 * relative to it (`NmbItem`, `./CdgItem`; a `//` still starts at the root of
 * the document) and they read in the same way, one node or many, at any depth.
 * Their errors have the full XPath of the node, with its position when it is
 * one of several: `(//Detalle)[2]/NmbItem`.
 *
 * Every error is an `XmlQueryException` that has the XPath expression and the
 * document, also in the operations on the whole structure (`to()`, `toMap()`,
 * `toList()` and `assertOnlyKeys()`). The XPath of the whole document is `/`.
 *
 * The reader reads the document each time it is asked, so it sees the changes
 * that are made to it, also if it is loaded again.
 *
 *     $reader = $document->getReader();
 *
 *     $reader->requireString('//Encabezado/Emisor/RUTEmisor');  // string, or an error
 *     $reader->getAsInt('//Encabezado/IdDoc/Folio');            // ?int
 *
 *     foreach ($reader->getReaders('//Detalle') ?? [] as $item) {
 *         $item->requireString('NmbItem');
 *         $item->getReaders('CdgItem');                         // ?list<XPathReader>
 *     }
 */
final readonly class XPathReader
{
    /**
     * @param XmlDocumentInterface $document The document to read.
     * @param array<string, string> $namespaces Prefix and URI of the namespaces
     * that the XPath expressions use. Without them the namespaces are ignored
     * (see `XPathQuery`).
     * @param string|null $source Where the document comes from (a file, a
     * request, anything that says it), for the errors.
     * @param DOMNode|null $context Node that the reader is scoped to: its
     * XPath expressions are relative to it. Without it the reader reads the
     * whole document.
     * @param string $path XPath of the context node inside the document, for
     * the errors.
     */
    public function __construct(
        private XmlDocumentInterface $document,
        private array $namespaces = [],
        private ?string $source = null,
        private ?DOMNode $context = null,
        private string $path = ''
    ) {
    }

    /**
     * Creates a reader of a document.
     *
     * @param XmlDocumentInterface $document The document to read.
     * @param array<string, string> $namespaces Prefix and URI of the namespaces.
     * @param string|null $source Where the document comes from, for the errors.
     */
    public static function fromDocument(
        XmlDocumentInterface $document,
        array $namespaces = [],
        ?string $source = null
    ): self {
        return new self($document, $namespaces, $source);
    }

    /**
     * Tells if the XPath matches a node, including when the node has no value.
     *
     * @throws XmlQueryException If the XPath expression is not valid.
     */
    public function hasKey(string $xpath): bool
    {
        return $this->query()->getNodes($xpath, [], $this->context)->length > 0;
    }

    /**
     * Tells if the XPath matches a node that has a value.
     *
     * @throws XmlQueryException If the XPath expression is not valid.
     */
    public function hasValue(string $xpath): bool
    {
        return $this->find($xpath) !== null;
    }

    /**
     * Reads what an XPath finds as a type.
     *
     * @template T
     * @template D
     * @param ValueType<T> $type
     * @param D $default What is returned when no node matches.
     * @return T|D
     * @throws XmlQueryException If the value is not of the type, or the XPath
     * expression is not valid.
     */
    public function get(string $xpath, ValueType $type, mixed $default = null): mixed
    {
        $value = $this->find($xpath);
        if ($value === null) {
            return $default;
        }

        return $this->parse($value, $type, $this->path($xpath));
    }

    /**
     * Reads what an XPath finds as a type, that must be there.
     *
     * @template T
     * @param ValueType<T> $type
     * @return T
     * @throws XmlQueryException If no node matches, the value is not of the
     * type, or the XPath expression is not valid.
     */
    public function require(string $xpath, ValueType $type): mixed
    {
        $value = $this->find($xpath);
        if ($value === null) {
            throw $this->missing($this->path($xpath));
        }

        return $this->parse($value, $type, $this->path($xpath));
    }

    /**
     * Reads the text of a node.
     *
     * @param string|null $default What is returned when no node matches.
     * @return ($default is null ? string|null : string)
     * @throws XmlQueryException If the value is not a string.
     */
    public function getString(string $xpath, ?string $default = null): ?string
    {
        return $this->get($xpath, ValueType::string(), $default);
    }

    /**
     * Reads the text of a node that is not empty, nor only spaces.
     *
     * @return non-empty-string|null
     * @throws XmlQueryException If the value is not that string.
     */
    public function getNonEmptyString(string $xpath): ?string
    {
        return $this->get($xpath, ValueType::nonEmptyString());
    }

    /**
     * Reads a date (see `ValueType::dateTime()`).
     *
     * @throws XmlQueryException If the value is not a date.
     */
    public function getDateTime(string $xpath): ?DateTimeInterface
    {
        return $this->get($xpath, ValueType::dateTime());
    }

    /**
     * Reads a case of a backed enum (see `ValueType::backedEnum()`).
     *
     * @template E of BackedEnum
     * @param class-string<E> $class
     * @return E|null
     * @throws XmlQueryException If the value is not a case of the enum.
     */
    public function getEnum(string $xpath, string $class): ?BackedEnum
    {
        return $this->get($xpath, ValueType::backedEnum($class));
    }

    /**
     * Reads the structure of a node. An empty element is `[]`.
     *
     * @return array<array-key, mixed>|null
     * @throws XmlQueryException If the value is not an array.
     */
    public function getArray(string $xpath): ?array
    {
        return $this->getStructure($xpath, ValueType::array());
    }

    /**
     * Reads the structure of a node, that has only string keys. An empty
     * element is `[]`.
     *
     * @return array<string, mixed>|null
     * @throws XmlQueryException If the value is not a map.
     */
    public function getMap(string $xpath): ?array
    {
        return $this->getStructure($xpath, ValueType::map());
    }

    /**
     * Reads what an XPath finds as a list, one node or many.
     *
     * @return list<mixed>|null
     * @throws XmlQueryException If the value is not a list.
     */
    public function getList(string $xpath): ?array
    {
        return $this->getListOf($xpath, ValueType::mixed());
    }

    /**
     * Reads what an XPath finds as a list of texts, one node or many.
     *
     * @return list<string>|null
     * @throws XmlQueryException If the value is not a list of strings.
     */
    public function getStringList(string $xpath): ?array
    {
        return $this->getListOf($xpath, ValueType::string());
    }

    /**
     * Reads what an XPath finds as a list whose items are all of a type, one
     * node or many.
     *
     * @template T
     * @param ValueType<T> $item
     * @return list<T>|null
     * @throws XmlQueryException If the value is not that list.
     */
    public function getListOf(string $xpath, ValueType $item): ?array
    {
        $items = $this->asList($this->find($xpath));

        return $items === null
            ? null
            : $this->parse(
                $this->emptyAsStructure($items, $item),
                ValueType::listOf($item),
                $this->path($xpath)
            );
    }

    /**
     * Reads the structure of a node as a map whose values are all of a type.
     * An empty element is `[]`.
     *
     * @template T
     * @param ValueType<T> $item
     * @return array<string, T>|null
     * @throws XmlQueryException If the value is not that map.
     */
    public function getMapOf(string $xpath, ValueType $item): ?array
    {
        return $this->getStructure($xpath, ValueType::mapOf($item), $item);
    }

    /**
     * Reads the structure of a node as an array with arbitrary keys whose
     * values match the given type. An empty element is `[]`.
     *
     * @template T
     * @param ValueType<T> $item
     * @return array<array-key, T>|null
     * @throws XmlQueryException If the value is not that array.
     */
    public function getArrayOf(string $xpath, ValueType $item): ?array
    {
        return $this->getStructure($xpath, ValueType::arrayOf($item), $item);
    }

    /**
     * Reads a required value (see `getString()`).
     *
     * @throws XmlQueryException If no node matches, or the value is not a string.
     */
    public function requireString(string $xpath): string
    {
        return $this->require($xpath, ValueType::string());
    }

    /**
     * Reads a required value (see `getNonEmptyString()`).
     *
     * @return non-empty-string
     * @throws XmlQueryException If no node matches, or the value is not that string.
     */
    public function requireNonEmptyString(string $xpath): string
    {
        return $this->require($xpath, ValueType::nonEmptyString());
    }

    /**
     * Reads a required value (see `getDateTime()`).
     *
     * @throws XmlQueryException If no node matches, or the value is not a date.
     */
    public function requireDateTime(string $xpath): DateTimeInterface
    {
        return $this->require($xpath, ValueType::dateTime());
    }

    /**
     * Reads a required value (see `getEnum()`).
     *
     * @template E of BackedEnum
     * @param class-string<E> $class
     * @return E
     * @throws XmlQueryException If no node matches, or the value is not a case of the enum.
     */
    public function requireEnum(string $xpath, string $class): BackedEnum
    {
        return $this->require($xpath, ValueType::backedEnum($class));
    }

    /**
     * Reads a required value (see `getArray()`).
     *
     * @return array<array-key, mixed>
     * @throws XmlQueryException If no node matches, or the value is not an array.
     */
    public function requireArray(string $xpath): array
    {
        return $this->requireStructure($xpath, ValueType::array());
    }

    /**
     * Reads a required value (see `getMap()`).
     *
     * @return array<string, mixed>
     * @throws XmlQueryException If no node matches, or the value is not a map.
     */
    public function requireMap(string $xpath): array
    {
        return $this->requireStructure($xpath, ValueType::map());
    }

    /**
     * Reads a required value (see `getList()`).
     *
     * @return list<mixed>
     * @throws XmlQueryException If no node matches, or the value is not a list.
     */
    public function requireList(string $xpath): array
    {
        return $this->requireListOf($xpath, ValueType::mixed());
    }

    /**
     * Reads a required value (see `getStringList()`).
     *
     * @return list<string>
     * @throws XmlQueryException If no node matches, or the value is not a list of strings.
     */
    public function requireStringList(string $xpath): array
    {
        return $this->requireListOf($xpath, ValueType::string());
    }

    /**
     * Reads a required value (see `getListOf()`).
     *
     * @template T
     * @param ValueType<T> $item
     * @return list<T>
     * @throws XmlQueryException If no node matches, or the value is not that list.
     */
    public function requireListOf(string $xpath, ValueType $item): array
    {
        $items = $this->asList($this->find($xpath));
        if ($items === null) {
            throw $this->missing($this->path($xpath));
        }

        return $this->parse(
            $this->emptyAsStructure($items, $item),
            ValueType::listOf($item),
            $this->path($xpath)
        );
    }

    /**
     * Reads a required value (see `getMapOf()`).
     *
     * @template T
     * @param ValueType<T> $item
     * @return array<string, T>
     * @throws XmlQueryException If no node matches, or the value is not that map.
     */
    public function requireMapOf(string $xpath, ValueType $item): array
    {
        return $this->requireStructure($xpath, ValueType::mapOf($item), $item);
    }

    /**
     * Reads a required value (see `getArrayOf()`).
     *
     * @template T
     * @param ValueType<T> $item
     * @return array<array-key, T>
     * @throws XmlQueryException If no node matches, or the value is not that array.
     */
    public function requireArrayOf(string $xpath, ValueType $item): array
    {
        return $this->requireStructure($xpath, ValueType::arrayOf($item), $item);
    }

    /**
     * Reads and converts the text of a node using `ValueType::toInt()`.
     *
     * @param int|null $default Returned when no node matches.
     * @return ($default is null ? int|null : int)
     * @throws XmlQueryException If the text cannot be converted.
     */
    public function getAsInt(string $xpath, ?int $default = null): ?int
    {
        return $this->get($xpath, ValueType::toInt(), $default);
    }

    /**
     * Reads and converts the text of a required node using `ValueType::toInt()`.
     *
     * @throws XmlQueryException If no node matches, or the text cannot be converted.
     */
    public function requireAsInt(string $xpath): int
    {
        return $this->require($xpath, ValueType::toInt());
    }

    /**
     * Reads and converts the text of a node using `ValueType::toFloat()`.
     *
     * @param float|null $default Returned when no node matches.
     * @return ($default is null ? float|null : float)
     * @throws XmlQueryException If the text cannot be converted.
     */
    public function getAsFloat(string $xpath, ?float $default = null): ?float
    {
        return $this->get($xpath, ValueType::toFloat(), $default);
    }

    /**
     * Reads and converts the text of a required node using `ValueType::toFloat()`.
     *
     * @throws XmlQueryException If no node matches, or the text cannot be converted.
     */
    public function requireAsFloat(string $xpath): float
    {
        return $this->require($xpath, ValueType::toFloat());
    }

    /**
     * Reads and converts the text of a node using `ValueType::toNumber()`.
     *
     * @param float|int|null $default Returned when no node matches.
     * @return ($default is null ? float|int|null : float|int)
     * @throws XmlQueryException If the text cannot be converted.
     */
    public function getAsNumber(string $xpath, float|int|null $default = null): float|int|null
    {
        return $this->get($xpath, ValueType::toNumber(), $default);
    }

    /**
     * Reads and converts the text of a required node using `ValueType::toNumber()`.
     *
     * @throws XmlQueryException If no node matches, or the text cannot be converted.
     */
    public function requireAsNumber(string $xpath): float|int
    {
        return $this->require($xpath, ValueType::toNumber());
    }

    /**
     * Reads and converts the text of a node using `ValueType::toBool()`.
     *
     * @param bool|null $default Returned when no node matches.
     * @return ($default is null ? bool|null : bool)
     * @throws XmlQueryException If the text cannot be converted.
     */
    public function getAsBool(string $xpath, ?bool $default = null): ?bool
    {
        return $this->get($xpath, ValueType::toBool(), $default);
    }

    /**
     * Reads and converts the text of a required node using `ValueType::toBool()`.
     *
     * @throws XmlQueryException If no node matches, or the text cannot be converted.
     */
    public function requireAsBool(string $xpath): bool
    {
        return $this->require($xpath, ValueType::toBool());
    }

    /**
     * Reads and converts the text of a node using `ValueType::toString()`.
     *
     * @param string|null $default Returned when no node matches.
     * @return ($default is null ? string|null : string)
     * @throws XmlQueryException If the value cannot be converted.
     */
    public function getAsString(string $xpath, ?string $default = null): ?string
    {
        return $this->get($xpath, ValueType::toString(), $default);
    }

    /**
     * Reads and converts the text of a required node using `ValueType::toString()`.
     *
     * @throws XmlQueryException If no node matches, or the value cannot be converted.
     */
    public function requireAsString(string $xpath): string
    {
        return $this->require($xpath, ValueType::toString());
    }

    /**
     * Reads a node with structure as a reader scoped to it. An empty element
     * is a reader of `[]`.
     *
     * @param ValueType<array<array-key, mixed>>|null $type Type of the structure of the node.
     * @throws XmlQueryException If the XPath matches more than one node, the
     * node has no structure, or the structure is not of the type.
     */
    public function getReader(string $xpath, ?ValueType $type = null): ?self
    {
        $nodes = $this->nodes($xpath);
        if ($nodes === []) {
            return null;
        }

        return $this->createReader($this->one($nodes, $xpath), $this->path($xpath), $type);
    }

    /**
     * Reads a required node with structure as a reader scoped to it.
     *
     * @param ValueType<array<array-key, mixed>>|null $type Type of the structure of the node.
     * @throws XmlQueryException If no node matches, the XPath matches more than
     * one node, the node has no structure, or the structure is not of the type.
     */
    public function requireReader(string $xpath, ?ValueType $type = null): self
    {
        $nodes = $this->nodes($xpath);
        if ($nodes === []) {
            throw $this->missing($this->path($xpath));
        }

        return $this->createReader($this->one($nodes, $xpath), $this->path($xpath), $type);
    }

    /**
     * Reads what an XPath finds, one node or many, as readers scoped to each
     * node, that report their errors with the XPath and the position.
     *
     * @param ValueType<array<array-key, mixed>>|null $type Type of the structure of each node.
     * @return list<self>|null
     * @throws XmlQueryException If a node has no structure, or its structure
     * is not of the type.
     */
    public function getReaders(string $xpath, ?ValueType $type = null): ?array
    {
        $nodes = $this->nodes($xpath);

        return $nodes === [] ? null : $this->createReaders($nodes, $xpath, $type);
    }

    /**
     * Reads what an XPath finds, one node or many, as required readers scoped
     * to each node.
     *
     * @param ValueType<array<array-key, mixed>>|null $type Type of the structure of each node.
     * @return list<self>
     * @throws XmlQueryException If no node matches, a node has no structure,
     * or its structure is not of the type.
     */
    public function requireReaders(string $xpath, ?ValueType $type = null): array
    {
        $nodes = $this->nodes($xpath);
        if ($nodes === []) {
            throw $this->missing($this->path($xpath));
        }

        return $this->createReaders($nodes, $xpath, $type);
    }

    /**
     * Says that the structure can only have some keys, the names of the child
     * elements: an element that is not one of them is an error, as a typo in a
     * document would be ignored otherwise.
     *
     * @param int|string ...$allowed The keys that the structure can have.
     * @return self The same reader, to go on reading.
     * @throws XmlQueryException If the structure has another key.
     */
    public function assertOnlyKeys(int|string ...$allowed): self
    {
        $allowed = array_map(static fn (int|string $key): string => (string) $key, $allowed);
        foreach (array_keys($this->structure()) as $key) {
            if (in_array((string) $key, $allowed, true)) {
                continue;
            }

            $path = $this->path . '/' . $key;

            throw new XmlQueryException(
                $this->source === null
                    ? [
                        'The element "{xpath}" is not allowed; the allowed elements are: {allowed}.',
                        'xpath' => $path,
                        'allowed' => implode(', ', $allowed),
                    ]
                    : [
                        'The element "{xpath}" of {source} is not allowed; the allowed elements are: {allowed}.',
                        'xpath' => $path,
                        'source' => $this->source,
                        'allowed' => implode(', ', $allowed),
                    ],
                xmlDocument: $this->document,
                xpathExpression: $path
            );
        }

        return $this;
    }

    /**
     * Returns the structure of the whole document, or of the node that the
     * reader is scoped to, as `XmlDocumentInterface::toArray()` gives it.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->structure();
    }

    /**
     * Checks the structure against a type.
     *
     * @template T
     * @param ValueType<T> $type
     * @return T
     * @throws XmlQueryException If the structure is not of the type.
     */
    public function to(ValueType $type): mixed
    {
        return $this->parse($this->structure(), $type, $this->path === '' ? '/' : $this->path);
    }

    /**
     * Returns the structure, that must have only string keys.
     *
     * @return array<string, mixed>
     * @throws XmlQueryException If the structure is not a map.
     */
    public function toMap(): array
    {
        return $this->to(ValueType::map());
    }

    /**
     * Returns the structure, that must be a list.
     *
     * @return list<mixed>
     * @throws XmlQueryException If the structure is not a list.
     */
    public function toList(): array
    {
        return $this->to(ValueType::list());
    }

    /**
     * Query of the document as it is now, so a document that is loaded again
     * is read as the new one.
     */
    private function query(): XPathQuery
    {
        return new XPathQuery($this->document, $this->namespaces);
    }

    /**
     * What an XPath finds, from the context node if the reader has one.
     *
     * @return string|array<int|string, mixed>|null
     */
    private function find(string $xpath): string|array|null
    {
        return $this->query()->get($xpath, [], $this->context);
    }

    /**
     * The nodes that an XPath matches, from the context node if the reader has
     * one.
     *
     * @return list<DOMNode|DOMNameSpaceNode>
     */
    private function nodes(string $xpath): array
    {
        $nodes = [];
        foreach ($this->query()->getNodes($xpath, [], $this->context) as $node) {
            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * XPath of an expression of this reader inside the document, for the
     * errors. An absolute XPath, that starts at the root of the document, is
     * the path as it is.
     */
    private function path(string $xpath): string
    {
        if ($this->path === '' || str_starts_with($xpath, '/')) {
            return $xpath;
        }

        return $this->path . '/' . (str_starts_with($xpath, './') ? substr($xpath, 2) : $xpath);
    }

    /**
     * Reads the structure of a node as a type, where an empty element is `[]`.
     *
     * @template T
     * @param ValueType<T> $type
     * @param ValueType<mixed>|null $item The type of the values, if it is a structure of values.
     * @return T|null
     * @throws XmlQueryException If the value is not of the type.
     */
    private function getStructure(string $xpath, ValueType $type, ?ValueType $item = null): mixed
    {
        $value = $this->find($xpath);
        if ($value === null) {
            return null;
        }

        return $this->parse(
            $this->structureOf($value, $item),
            $type,
            $this->path($xpath)
        );
    }

    /**
     * Reads the structure of a required node as a type, where an empty element
     * is `[]`.
     *
     * @template T
     * @param ValueType<T> $type
     * @param ValueType<mixed>|null $item The type of the values, if it is a structure of values.
     * @return T
     * @throws XmlQueryException If no node matches, or the value is not of the type.
     */
    private function requireStructure(string $xpath, ValueType $type, ?ValueType $item = null): mixed
    {
        $value = $this->find($xpath);
        if ($value === null) {
            throw $this->missing($this->path($xpath));
        }

        return $this->parse(
            $this->structureOf($value, $item),
            $type,
            $this->path($xpath)
        );
    }

    /**
     * Gives what a node found as the structure that is read: an empty element
     * is `[]`, and in a structure whose values are of a structure type, so are
     * its empty elements.
     *
     * @param string|array<array-key, mixed> $value
     * @param ValueType<mixed>|null $item
     * @return array<array-key, mixed>|string
     */
    private function structureOf(string|array $value, ?ValueType $item): array|string
    {
        if ($value === '') {
            return [];
        }

        return is_array($value) && $item !== null
            ? $this->emptyAsStructure($value, $item)
            : $value;
    }

    /**
     * Gives the items with their empty elements as `[]` when the items are of
     * a structure type (`map`, `array` or `list`, with or without a type for
     * their items), and as they are when they are of any other type.
     *
     * @param array<array-key, mixed> $items
     * @param ValueType<mixed> $item
     * @return array<array-key, mixed>
     */
    private function emptyAsStructure(array $items, ValueType $item): array
    {
        if (preg_match('/^(map|array|list)(<.*>)?$/', $item->name) !== 1) {
            return $items;
        }

        return array_map(
            static fn (mixed $value): mixed => $value === '' ? [] : $value,
            $items
        );
    }

    /**
     * Gives what an XPath found as a list: nothing is `null`, a list is itself
     * and anything else, one node, is a list of one.
     *
     * @param string|array<int|string, mixed>|null $value
     * @return list<mixed>|null
     */
    private function asList(string|array|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) && array_is_list($value) ? $value : [$value];
    }

    /**
     * The only node that an XPath matched.
     *
     * @param non-empty-list<DOMNode|DOMNameSpaceNode> $nodes
     * @throws XmlQueryException If more than one node matched.
     */
    private function one(array $nodes, string $xpath): DOMNode|DOMNameSpaceNode
    {
        if (count($nodes) > 1) {
            $path = $this->path($xpath);

            throw new XmlQueryException(
                $this->source === null
                    ? [
                        'The XPath "{xpath}" must match one node, {count} matched.',
                        'xpath' => $path,
                        'count' => count($nodes),
                    ]
                    : [
                        'The XPath "{xpath}" of {source} must match one node, {count} matched.',
                        'xpath' => $path,
                        'source' => $this->source,
                        'count' => count($nodes),
                    ],
                xmlDocument: $this->document,
                xpathExpression: $path
            );
        }

        return $nodes[0];
    }

    /**
     * Creates the readers of the nodes that an XPath matched, with the position
     * of each node in the path.
     *
     * @param non-empty-list<DOMNode|DOMNameSpaceNode> $nodes
     * @param ValueType<array<array-key, mixed>>|null $type
     * @return list<self>
     * @throws XmlQueryException If a node has no structure, or its structure
     * is not of the type.
     */
    private function createReaders(array $nodes, string $xpath, ?ValueType $type): array
    {
        $path = $this->path($xpath);
        $readers = [];
        foreach ($nodes as $index => $node) {
            $readers[] = $this->createReader($node, '(' . $path . ')[' . ($index + 1) . ']', $type);
        }

        return $readers;
    }

    /**
     * Creates the reader of a node, scoped to it.
     *
     * The node must have structure: an element with child elements or an empty
     * element. A node with text is not a structure.
     *
     * @param ValueType<array<array-key, mixed>>|null $type
     * @throws XmlQueryException If the node has no structure, or its structure
     * is not of the type.
     */
    private function createReader(DOMNode|DOMNameSpaceNode $node, string $path, ?ValueType $type): self
    {
        // Only an element has structure: the text of any other node is not an
        // array.
        if (!$node instanceof DOMElement) {
            throw $this->mismatch(
                ValueType::mismatch($path, 'array', $node->nodeValue, $this->source),
                $node->nodeValue,
                'array',
                $path
            );
        }

        // An element with only text is rejected by the type, as an element that
        // has no content is an empty structure.
        $structure = $this->query()->get('.', [], $node);
        $this->parse($structure === '' ? [] : $structure, $type ?? ValueType::array(), $path);

        return new self($this->document, $this->namespaces, $this->source, $node, $path);
    }

    /**
     * Structure of the whole document, or of the node that the reader is scoped
     * to.
     *
     * @return array<array-key, mixed>
     */
    private function structure(): array
    {
        if ($this->context === null) {
            return $this->document->toArray();
        }

        $value = $this->query()->get('.', [], $this->context);

        return is_array($value) ? $value : [];
    }

    /**
     * Delegates typing to Value, retaining the error contract of the XPath.
     *
     * @template T
     * @param ValueType<T> $type
     * @return T
     * @throws XmlQueryException If the value does not satisfy the type.
     */
    private function parse(mixed $value, ValueType $type, string $path): mixed
    {
        try {
            return $type->parse($value, $path, $this->source);
        } catch (ValueTypeMismatchException $exception) {
            throw $this->mismatch($exception, $value, $type->name, $path);
        }
    }

    /**
     * Creates the error of a value that is not of the expected type.
     *
     * @param string $expected Name of the type, if the cause does not say it.
     */
    private function mismatch(
        ValueTypeMismatchException $exception,
        mixed $value,
        string $expected,
        string $path
    ): XmlQueryException {
        $parameters = $exception->getTranslatableMessage()->getParameters();
        $errorPath = $parameters['path'] ?? $path;
        $expected = $parameters['expected'] ?? $expected;
        $given = $parameters['given'] ?? get_debug_type($value);

        return new XmlQueryException(
            $this->source === null
                ? [
                    'The value of the XPath "{path}" must be of type {expected}, {given} given.',
                    'path' => $errorPath,
                    'expected' => $expected,
                    'given' => $given,
                ]
                : [
                    'The value of the XPath "{path}" of {source} must be of type {expected}, {given} given.',
                    'path' => $errorPath,
                    'source' => $this->source,
                    'expected' => $expected,
                    'given' => $given,
                ],
            previous: $exception,
            xmlDocument: $this->document,
            xpathExpression: $path
        );
    }

    /**
     * Creates the error of an XPath that matches no node.
     */
    private function missing(string $path): XmlQueryException
    {
        return new XmlQueryException(
            $this->source === null
                ? [
                    'The XPath "{xpath}" is required: it matched no node.',
                    'xpath' => $path,
                ]
                : [
                    'The XPath "{xpath}" of {source} is required: it matched no node.',
                    'xpath' => $path,
                    'source' => $this->source,
                ],
            xmlDocument: $this->document,
            xpathExpression: $path
        );
    }
}
