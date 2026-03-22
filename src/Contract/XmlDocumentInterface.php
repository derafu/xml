<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Contract;

use Derafu\Xml\Exception\XmlException;
use DOMElement;
use DOMNode;
use DOMNodeList;
use JsonSerializable;

/**
 * Interface for the class that represents an XML document.
 */
interface XmlDocumentInterface extends DOMDocumentInterface, JsonSerializable
{
    /**
     * Returns the encoding of the XML document.
     *
     * @return string The encoding of the XML document.
     */
    public function getEncoding(): string;

    /**
     * Sets the encoding of the XML document.
     *
     * @param string $encoding The encoding of the XML document.
     * @return static
     */
    public function setEncoding(string $encoding): static;

    /**
     * Sets the format output of the XML document.
     *
     * @param bool $formatOutput The format output of the XML document.
     * @return static
     */
    public function setFormatOutput(bool $formatOutput): static;

    /**
     * Sets the preserve white space of the XML document.
     *
     * @param bool $preserveWhiteSpace The preserve white space of the XML document.
     * @return static
     */
    public function setPreserveWhiteSpace(bool $preserveWhiteSpace): static;

    /**
     * Returns the root element instance of the document.
     *
     * @return DOMElement|null The root element instance of the document or
     * `null` if it is not present.
     */
    public function getDocumentElement(): ?DOMElement;

    /**
     * Returns the name of the root tag of the XML.
     *
     * @return string The name of the root tag.
     */
    public function getName(): string;

    /**
     * Gets the namespace (namespace) of the root element of the XML document.
     *
     * @return string|null The namespace of the XML document or `null` if it is
     * not present.
     */
    public function getNamespace(): ?string;

    /**
     * Returns the name of the schema file of the XML.
     *
     * @return string|null The name of the schema or `null` if it is not found.
     */
    public function getSchema(): ?string;

    /**
     * Loads an XML string into the XML document instance.
     *
     * @param string $source The string with the XML to load.
     * @param int $options The options for loading the XML.
     * @return bool `true` if the XML was loaded correctly.
     * @throws XmlException If it is not possible to load the XML.
     */
    public function loadXml(string $source, int $options = 0): bool;

    /**
     * Generates the XML document as a string.
     *
     * Wrapper of parent::saveXml() to correct XML entities.
     *
     * Includes the XML header with version and encoding.
     *
     * @param DOMNode|null $node The node to serialize.
     * @param int $options The serialization options.
     * @return string Serialized XML and corrected.
     */
    public function saveXml(?DOMNode $node = null, int $options = 0): string;

    /**
     * Generates the XML document as a string.
     *
     * Wrapper of saveXml() to generate a string without the XML header and
     * without initial or final line break.
     *
     * @return string Serialized XML and corrected.
     */
    public function getXml(): string;

    /**
     * Returns the canonicalized XML string respecting the document's encoding.
     *
     * This basically uses C14N(), but C14N() always returns the XML in UTF-8
     * encoding. So this method converts the result to the encoding declared in
     * the XML document (e.g., ISO-8859-1). If no encoding is declared, UTF-8
     * is used. Also, XML entities are corrected.
     *
     * @param string|null $xpath The XPath to query the XML and extract only a
     * part, from a specific tag/node.
     * @return string The canonicalized XML string.
     * @throws XmlException If a XPath is passed and not found.
     */
    public function C14NEncoded(?string $xpath = null): string;

    /**
     * Returns the canonicalized XML string respecting the document's encoding
     * and flattened.
     *
     * This is a wrapper of C14NEncoded() that flattens the resulting XML by
     * removing whitespace between tags.
     *
     * @param string|null $xpath The XPath to query the XML and extract only a
     * part, from a specific tag/node.
     * @return string The canonicalized XML string and flattened.
     * @throws XmlException If a XPath is passed and not found.
     */
    public function C14NEncodedFlattened(?string $xpath = null): string;

    /**
     * Gets the XML string of the electronic signature node.
     *
     * @return string|null The XML string of the signature if it exists.
     */
    public function getSignatureNodeXml(): ?string;

    /**
     * Executes an XPath query on the XML document.
     *
     * The query that is performed is simple, without namespaces. If you need to
     * use namespaces, use the XPathQuery class directly.
     *
     * @param string $query The XPath query with named markers (e.g.: ":param").
     * @param array $params The array of parameters in the format ['param' => 'value'].
     * @return string|array|null
     */
    public function query(string $query, array $params = []): string|array|null;

    /**
     * Executes an XPath query on the XML document.
     *
     * The query that is performed is simple, without namespaces. If you need to
     * use namespaces, use the XPathQuery class directly.
     *
     * @param string $query The XPath query with named markers (e.g.: ":param").
     * @param array $params The array of parameters in the format ['param' => 'value'].
     * @return DOMNodeList
     */
    public function getNodes(string $query, array $params = []): DOMNodeList;

    /**
     * Queries the XML array using a selector.
     *
     * @param string $selector The selector for the query to the XML array.
     * @param mixed $default The default value to return if the selector is not found.
     * @return mixed The result of the selector query to the array.
     */
    public function get(string $selector, mixed $default = null): mixed;

    /**
     * Returns the data of the XML in an array structure.
     *
     * @return array
     */
    public function toArray(): array;
}
