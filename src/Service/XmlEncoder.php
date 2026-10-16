<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Service;

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\Exception\XmlEncoderException;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use DOMElement;
use DOMNode;
use Stringable;

/**
 * Class that creates an XML document from a PHP array.
 */
final class XmlEncoder implements XmlEncoderInterface
{
    /**
     * Namespace of the declarations of namespaces (`xmlns` and `xmlns:prefix`).
     */
    private const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    /**
     * Namespace of the attributes with the prefix `xml` (`xml:lang`...).
     */
    private const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    /**
     * Rules for converting a PHP array to XML and vice versa.
     *
     * @var array{node_values: array{skip_generation: list<mixed>, generate_empty: list<mixed>}}
     */
    private array $rules = [
        // How to process the values of the nodes.
        'node_values' => [
            // Values that make the node not be generated (are skipped).
            'skip_generation' => [null, false, []],
            // Values that generate an empty node.
            'generate_empty' => ['', true],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): XmlDocumentInterface
    {
        $doc = new XmlDocument();

        $this->encodeNode($data, $doc, $doc->getDomDocument(), []);

        // An XML has only one root element: the array has one node (and not a
        // list of them) at its first level.
        $roots = [];
        foreach ($doc->getDomDocument()->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $roots[] = $node->nodeName;
            }
        }
        if (count($roots) > 1) {
            throw new XmlEncoderException([
                'An XML document can only have one root element. There are {count}: {names}.',
                'count' => count($roots),
                'names' => implode(', ', $roots),
            ]);
        }

        return $doc;
    }

    /**
     * Recursively encodes an array into XML nodes and appends them to a parent.
     *
     * @param array<int|string, mixed> $data The array with the data to encode.
     * @param XmlDocumentInterface $doc The root XML document.
     * @param DOMNode $parent The parent node to append nodes to.
     * @param array<string, string> $namespaces The namespaces that are in scope
     * in the parent node, by prefix (the default one is the empty prefix).
     * @return void
     */
    private function encodeNode(
        array $data,
        XmlDocumentInterface $doc,
        DOMNode $parent,
        array $namespaces,
    ): void {
        // The value of the node (its text) is written first, wherever `@value`
        // is: the text goes before the child nodes, and that is the same in
        // every place of the array.
        if (array_key_exists('@value', $data) && !$this->skipValue($data['@value'])) {
            if (!$parent instanceof DOMElement) {
                throw new XmlEncoderException('A text can not be written outside of an element.');
            }
            // The text is made as the one of any other node is (`nodeAddValue()`),
            // in a node that gives its content, so it is escaped only once.
            $text = XmlHelper::sanitize($this->toText($data['@value'], $parent->nodeName));
            $holder = $doc->getDomDocument()->createElement('value', $text);
            if ($holder->firstChild === null) {
                // An empty value is an empty text, as it always was written.
                $parent->appendChild($doc->getDomDocument()->createTextNode(''));
            }
            while ($holder->firstChild !== null) {
                $parent->appendChild($holder->firstChild);
            }
        }

        foreach ($data as $key => $value) {
            if ($key === '@value') {
                continue;
            }

            // If the index is '@attributes' then the value of this index is an
            // array where the key is the name of the attribute of the tag of
            // $parent and the value is the value of the attribute.
            if ($key === '@attributes') {
                // Only attributes are added if the value is an array.
                if (is_array($value)) {
                    // In the first iteration of recursion it must be checked
                    // that $parent is a DOMElement. And only in that case
                    // continue.
                    if ($parent instanceof DOMElement) {
                        $this->nodeAddAttributes($parent, $value, $namespaces);
                    }
                }
            }

            // If the index is '@comment' then the value is a comment, or a list of
            // them, that are added to the parent in the place of the index.
            elseif ($key === '@comment') {
                $this->nodeAddComments($doc, $parent, $value);
            }

            // Here the index is the name of a node. In this case, the node is
            // an array. So it will be processed recursively to add the child
            // nodes that are in the array to this node.
            elseif (is_array($value)) {
                // Only the node is created if it has child nodes. The node will
                // not be created if an empty array (without children) is passed.
                if (!empty($value)) {
                    $this->nodeAddChilds($doc, $parent, $this->name($key), $value, $namespaces);
                }
            }

            // The node is a scalar (not an array, not child nodes). So the
            // node is created and the value is assigned directly.
            else {
                if (!$this->skipValue($value)) {
                    $name = $this->name($key);
                    $this->nodeAddValue($doc, $parent, $name, $this->toText($value, $name), $namespaces);
                }
            }
        }
    }

    /**
     * Adds attributes to an XML node from an array.
     *
     * @param DOMElement $node Node to which the attributes will be added.
     * @param array<int|string, mixed> $attributes Array of attributes (key => value).
     * @param array<string, string> $namespaces The namespaces that are in scope
     * in the node, with the ones that it declares.
     * @return void
     * @throws XmlEncoderException If the name of an attribute is not text, or
     * if its value is an array or can not be written as text.
     */
    private function nodeAddAttributes(DOMElement $node, array $attributes, array $namespaces): void
    {
        // The declarations of namespaces go first, so the attributes that have
        // a prefix find their namespace declared wherever they are in the array.
        uksort($attributes, fn (int|string $a, int|string $b) => $this->isDeclaration($b) <=> $this->isDeclaration($a));

        foreach ($attributes as $key => $value) {
            $attribute = $this->name($key);

            // If the attribute value is an array, it cannot be assigned.
            if (is_array($value)) {
                throw new XmlEncoderException([
                    'The type of data of the value entered for the attribute "{attribute}" of the node "{node}" is incorrect (cannot be an array). The value is: {value}',
                    'attribute' => $attribute,
                    'node' => $node->tagName,
                    'value' => json_encode($value),
                ]);
            }

            // Assign the attribute value only if it is not skipped according to
            // the type of value to be assigned.
            if (!$this->skipValue($value)) {
                $text = $this->toText($value, $node->tagName . '/@' . $attribute);
                $prefix = str_contains($attribute, ':') ? strstr($attribute, ':', true) : '';

                if ($this->isDeclaration($attribute)) {
                    $node->setAttributeNS(self::XMLNS_NAMESPACE, $attribute, $text);
                } elseif ($prefix === 'xml') {
                    $node->setAttributeNS(self::XML_NAMESPACE, $attribute, $text);
                } elseif ($prefix !== '' && isset($namespaces[$prefix])) {
                    $node->setAttributeNS($namespaces[$prefix], $attribute, $text);
                } else {
                    $node->setAttribute($attribute, $text);
                }
            }
        }
    }

    /**
     * Adds child nodes to an XML node from an array.
     *
     * @param XmlDocumentInterface $doc XML document in which the nodes will be added.
     * @param DOMNode $parent Node parent to which the child nodes will be added.
     * @param string $tagName Name of the child node tag.
     * @param array<int|string, mixed> $childs Array of data of the child nodes.
     * @param array<string, string> $namespaces The namespaces that are in scope
     * in the parent node.
     * @return void
     * @throws XmlEncoderException If a child node is not an array.
     */
    private function nodeAddChilds(
        XmlDocumentInterface $doc,
        DOMNode $parent,
        string $tagName,
        array $childs,
        array $namespaces,
    ): void {
        $keys = array_keys($childs);
        if (!is_int($keys[0])) {
            $childs = [$childs];
        }
        foreach ($childs as $child) {
            // Skip values that must be skipped.
            if ($this->skipValue($child)) {
                continue;
            }

            // If the child is an array, a node is created for the child and the
            // elements that are in the array are added.
            if (is_array($child)) {

                // If the array is not associative (with new nodes) error.
                if (isset($child[0])) {
                    throw new XmlEncoderException([
                        'The node "{node}" allows including arrays, but they must be arrays with other nodes. The current value is incorrect: {value}',
                        'node' => $tagName,
                        'value' => json_encode($child),
                    ]);
                }

                // The namespaces that the node declares are the ones of the node
                // and of what is inside of it.
                $nodeNamespaces = $this->declareNamespaces($child, $namespaces);
                $Node = $this->createElement($doc, $tagName, $nodeNamespaces);
                $parent->appendChild($Node);
                $this->encodeNode($child, $doc, $Node, $nodeNamespaces);
            }
            // If the child is not an array, it is simply a duplicate node that
            // must be added at the same level as the parent node.
            else {
                $this->nodeAddValue(
                    $doc,
                    $parent,
                    $tagName,
                    $this->toText($child, $tagName),
                    $namespaces
                );
            }
        }
    }

    /**
     * Adds an XML node with a scalar value to a parent node.
     *
     * @param XmlDocumentInterface $doc XML document in which the nodes will be added.
     * @param DOMNode $parent Node parent to which the node will be added.
     * @param string $tagName Name of the child node tag.
     * @param string $value Value of the child node.
     * @param array<string, string> $namespaces The namespaces that are in scope
     * in the parent node.
     * @return void
     */
    private function nodeAddValue(
        XmlDocumentInterface $doc,
        DOMNode $parent,
        string $tagName,
        string $value,
        array $namespaces,
    ): void {
        $value = XmlHelper::sanitize($value);
        $Node = $this->createElement($doc, $tagName, $namespaces, $value);
        $parent->appendChild($Node);
    }

    /**
     * Adds comments to a node.
     *
     * @param XmlDocumentInterface $doc XML document in which the comments will be added.
     * @param DOMNode $parent Node to which the comments will be added.
     * @param mixed $comments A comment, or a list of them.
     * @return void
     * @throws XmlEncoderException If a comment can not be written as text, or
     * it has two hyphens in a row or ends with a hyphen (not valid in XML).
     */
    private function nodeAddComments(XmlDocumentInterface $doc, DOMNode $parent, mixed $comments): void
    {
        foreach (is_array($comments) ? $comments : [$comments] as $comment) {
            if ($this->skipValue($comment)) {
                continue;
            }

            $text = $this->toText($comment, '@comment');
            if (str_contains($text, '--') || str_ends_with($text, '-')) {
                throw new XmlEncoderException([
                    'A comment can not have two hyphens in a row nor end with a hyphen: {comment}',
                    'comment' => $text,
                ]);
            }

            $parent->appendChild($doc->getDomDocument()->createComment($text));
        }
    }

    /**
     * Tells if the name of an attribute is the declaration of a namespace.
     */
    private function isDeclaration(int|string $name): bool
    {
        return $name === 'xmlns' || (is_string($name) && str_starts_with($name, 'xmlns:'));
    }

    /**
     * Gets the namespaces that are in scope in a node: the ones of its parent
     * and the ones that it declares in its `@attributes`.
     *
     * @param array<int|string, mixed> $node The data of the node.
     * @param array<string, string> $inherited The namespaces of the parent.
     * @return array<string, string> The URI of each namespace, by prefix (the
     * default one is the empty prefix).
     */
    private function declareNamespaces(array $node, array $inherited): array
    {
        $attributes = $node['@attributes'] ?? null;
        if (!is_array($attributes)) {
            return $inherited;
        }

        foreach ($attributes as $name => $uri) {
            if ($this->isDeclaration($name) && is_string($uri)) {
                $inherited[substr((string) $name, 6)] = $uri;
            }
        }

        return $inherited;
    }

    /**
     * Creates an element in the namespace that its prefix has in scope. A name
     * without prefix is in the default namespace, if there is one. A prefix that
     * is not declared is a name without namespace.
     *
     * @param array<string, string> $namespaces The namespaces that are in scope.
     */
    private function createElement(XmlDocumentInterface $doc, string $name, array $namespaces, string $value = ''): DOMElement
    {
        $prefix = str_contains($name, ':') ? (string) strstr($name, ':', true) : '';
        $uri = $namespaces[$prefix] ?? '';

        return $uri === ''
            ? $doc->getDomDocument()->createElement($name, $value)
            : $doc->getDomDocument()->createElementNS($uri, $name, $value)
        ;
    }

    /**
     * Gets a name of a node or of an attribute, that must be text: a number is
     * the position of a value in a list, not a name.
     *
     * @param int|string $key Key of the array that is the name.
     * @return string The name.
     * @throws XmlEncoderException If the key is not text.
     */
    private function name(int|string $key): string
    {
        if (is_string($key)) {
            return $key;
        }

        throw new XmlEncoderException([
            'The name of a node or an attribute must be text, {name} given.',
            'name' => $key,
        ]);
    }

    /**
     * Writes a value as the text of an XML node or attribute.
     *
     * Text, numbers, booleans and objects that can be a string are valid. What
     * means nothing (see `skipValue()`) is never given to this method.
     *
     * @param mixed $value Value to write.
     * @param string $name Name of the node or attribute, to say where it is.
     * @return string The text.
     * @throws XmlEncoderException If the value can not be written as text.
     */
    private function toText(mixed $value, string $name): string
    {
        if (
            is_string($value)
            || is_int($value)
            || is_float($value)
            || is_bool($value)
            || $value instanceof Stringable
        ) {
            return (string) $value;
        }

        throw new XmlEncoderException([
            'The value of "{name}" can not be written as text, {type} given.',
            'name' => $name,
            'type' => get_debug_type($value),
        ]);
    }

    /**
     * Checks if a value must be skipped when generating an XML node.
     *
     * @param mixed $value Value to check.
     * @return bool `true` if the value must be skipped, `false` otherwise.
     */
    private function skipValue(mixed $value): bool
    {
        return in_array(
            $value,
            $this->rules['node_values']['skip_generation'],
            true
        );
    }
}
