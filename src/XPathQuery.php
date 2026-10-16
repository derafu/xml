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

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlQueryException;
use DOMDocument;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;

/**
 * Class to facilitate XML handling using XPath.
 */
final class XPathQuery
{
    /**
     * Instance of the XML document.
     *
     * @var DOMDocument
     */
    private readonly DOMDocument $dom;

    /**
     * Instance that represents the XPath searcher.
     *
     * @var DOMXPath
     */
    private readonly DOMXPath $xpath;

    /**
     * Whether to use the namespace prefixes or ignore them.
     *
     * @var boolean
     */
    private readonly bool $registerNodeNS;

    /**
     * Constructor that receives the XML document and prepares XPath.
     *
     * @param string|XmlDocumentInterface|DOMDocument $xml XML document.
     * @param array<string, string> $namespaces Associative array with prefix and URI.
     */
    public function __construct(
        string|XmlDocumentInterface|DOMDocument $xml,
        array $namespaces = []
    ) {
        // Assign the DOM document instance.
        if ($xml instanceof XmlDocumentInterface) {
            $this->dom = $xml->getDomDocument();
        } elseif ($xml instanceof DOMDocument) {
            $this->dom = $xml;
        } else {
            $xmlDoc = new XmlDocument();
            $xmlDoc->loadXml($xml);
            $this->dom = $xmlDoc->getDomDocument();
        }

        // Create the XPath instance over the DOM document.
        $this->xpath = new DOMXPath($this->dom);

        // Assign or disable the use of namespaces.
        if ($namespaces) {
            foreach ($namespaces as $prefix => $namespace) {
                $this->xpath->registerNamespace($prefix, $namespace);
            }
            $this->registerNodeNS = true;
        } else {
            $this->registerNodeNS = false;
        }
    }

    /**
     * Returns the DOMDocument used internally.
     *
     * @return DOMDocument
     */
    public function getDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * Executes an XPath query and returns the processed result.
     *
     * The result will depend on what is found:
     *
     *   - `null`: if there was no match.
     *   - string: if there was one match and the matched node has no children.
     *   - array: if there was more than one match, or the matched node has
     *     children - in that case, recursively, down to each leaf node.
     *
     * @param string $query XPath query.
     * @param array<string, string> $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return string|array<int|string, mixed>|null The processed value: string, array or null.
     */
    public function get(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): string|array|null {
        $nodes = $this->getNodes($query, $params, $contextNode);

        $results = [];
        foreach ($nodes as $node) {
            $results[] = $this->processNode($node);
        }

        return match (count($results)) {
            // No matches.
            0 => null,
            // Single node.
            1 => $results[0],
            // Multiple nodes.
            default => $results,
        };
    }

    /**
     * Executes an XPath query and returns an array of values.
     *
     * @param string $query XPath query.
     * @param array<string, string> $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return list<string|null> Array of found values (`null` for a node without
     * value).
     */
    public function getValues(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): array {
        $nodes = $this->getNodes($query, $params, $contextNode);

        $results = [];
        foreach ($nodes as $node) {
            $results[] = $node->nodeValue;
        }

        return $results;
    }

    /**
     * Executes an XPath query and returns the first result as a string.
     *
     * @param string $query XPath query.
     * @param array<string, string> $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return string|null The value of the node, or `null` if it does not exist.
     */
    public function getValue(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): ?string {
        $nodes = $this->getNodes($query, $params, $contextNode);

        return $nodes->item(0)?->nodeValue;
    }

    /**
     * Executes an XPath query and returns the resulting nodes.
     *
     * @param string $query XPath query.
     * @param array<string, string> $params Array of parameters.
     * @param DOMNode|null $contextNode From which node to evaluate the expression.
     * @return DOMNodeList<DOMNameSpaceNode|DOMNode> Resulting nodes from the XPath query.
     */
    public function getNodes(
        string $query,
        array $params = [],
        ?DOMNode $contextNode = null
    ): DOMNodeList {
        try {
            $query = $this->resolveQuery($query, $params);
            $nodes = $this->execute(fn () => $this->xpath->query(
                $query,
                $contextNode,
                $this->registerNodeNS
            ));
        } catch (XmlQueryException $e) {
            throw new XmlQueryException([
                'An error occurred while executing the XPath expression: {query}. {error}',
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }

        return $nodes;
    }

    /**
     * Processes a DOM node and its children recursively.
     *
     * @param DOMNode|DOMNameSpaceNode $node DOM node to process.
     * @return string|array<int|string, mixed>|null The value of the node (`null`
     * if it has none) or the structure of children as an array.
     */
    private function processNode(DOMNode|DOMNameSpaceNode $node): string|array|null
    {
        // A namespace node has no children: its value is its URI.
        if ($node instanceof DOMNameSpaceNode) {
            return $node->nodeValue;
        }

        if ($node->hasChildNodes()) {
            // Values of the child elements, by name of the element.
            $grouped = [];

            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $grouped[$child->nodeName][] = $this->processNode($child);
                }
            }

            // If it has processed children, return the structure: the value of
            // an element that appears once, the list of values of one that is
            // repeated.
            if ($grouped !== []) {
                return array_map(
                    fn (array $values) => count($values) === 1 ? $values[0] : $values,
                    $grouped
                );
            }

            return $node->nodeValue;
        }

        // If it has no children, return the value.
        return $node->nodeValue;
    }

    /**
     * Resolves the parameters of an XPath query.
     *
     * This method replaces the named markers (like `:param`) in the query with
     * the escaped values in quotes.
     *
     * @param string $query XPath query with named markers (e.g.: ":param").
     * @param array<string, string> $params Array of parameters in the format ['param' => 'value'].
     * @return string XPath query with the values replaced.
     */
    private function resolveQuery(string $query, array $params = []): string
    {
        // If the namespaces are disabled, adapt the XPath query.
        if (!$this->registerNodeNS) {
            $query = preg_replace_callback(
                '/(?<=\/|^)(\w+)/',
                fn ($matches) => '*[local-name()="' . $matches[1] . '"]',
                $query
            );
            if ($query === null) {
                throw new XmlQueryException('The XPath expression could not be prepared.');
            }
        }

        // Replace parameters.
        foreach ($params as $key => $value) {
            $placeholder = ':' . ltrim($key, ':');
            $quotedValue = $this->quoteValue($value);
            $query = str_replace($placeholder, $quotedValue, $query);
        }

        // Return the resolved query.
        return $query;
    }

    /**
     * Escapes a value to use it in an XPath query.
     *
     * @param string $value Value to escape.
     * @return string Escaped value as an XPath literal.
     */
    private function quoteValue(string $value): string
    {
        return DOMXPath::quote($value);
    }

    /**
     * Wrapper to execute capturing the errors of the methods associated with
     * the XPath instance.
     *
     * @template T
     * @param callable(): (T|false) $function The code to execute, that gives
     * `false` when it fails.
     * @return T What the code gives, that is never `false`.
     * @throws XmlQueryException If the code fails.
     */
    private function execute(callable $function): mixed
    {
        $use_errors = libxml_use_internal_errors(true);

        $result = $function();

        $error = $this->getLastError();
        if ($result === false || $error) {
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);

            throw new XmlQueryException(
                $error
                    ? ['{error}', 'error' => $error]
                    : 'An error occurred in XPathQuery.'
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($use_errors);

        return $result;
    }

    /**
     * Returns, if it exists, the last error generated from XML.
     *
     * @return string|null
     */
    private function getLastError(): ?string
    {
        $error = libxml_get_last_error();

        if (!$error) {
            return null;
        }

        return trim($error->message) . '.';
    }
}
