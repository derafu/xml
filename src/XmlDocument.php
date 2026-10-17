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
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Exception\XmlQueryException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;

/**
 * Class that represents an XML document.
 */
final class XmlDocument implements XmlDocumentInterface
{
    /**
     * Underlying DOM document.
     *
     * @var DOMDocument
     */
    private DOMDocument $dom;

    /**
     * Instance to facilitate XML handling using XPath.
     *
     * @var XPathQuery
     */
    private XPathQuery $xPathQuery;

    /**
     * Constructor of the XML document.
     *
     * The parameters `encoding` and `version` are used to generate the XML
     * header when saving the XML. If the XmlDocument is instantiated for
     * loading an XML string, this parameters are ignored and will be determined
     * by the header of the XML string.
     *
     * @param string $encoding XML encoding to generate the XML header.
     * @param string $version XML version to generate the XML header.
     */
    public function __construct(
        string $encoding = 'UTF-8',
        string $version = '1.0'
    ) {
        $this->dom = new DOMDocument($version, $encoding);

        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDomDocument(): DOMDocument
    {
        return $this->dom;
    }

    /**
     * {@inheritDoc}
     */
    public function getEncoding(): string
    {
        return strtoupper($this->dom->encoding ?: 'UTF-8');
    }

    /**
     * {@inheritDoc}
     */
    public function setEncoding(string $encoding): static
    {
        $this->dom->encoding = $encoding;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormatOutput(bool $formatOutput): static
    {
        $this->dom->formatOutput = $formatOutput;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPreserveWhiteSpace(bool $preserveWhiteSpace): static
    {
        $this->dom->preserveWhiteSpace = $preserveWhiteSpace;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentElement(): ?DOMElement
    {
        return $this->dom->documentElement;
    }

    /**
     * {@inheritDoc}
     *
     * It is also what the methods that ask something to the document element
     * use (`getName()`, `getNamespace()`, `getSchema()`...).
     */
    public function requireDocumentElement(): DOMElement
    {
        return $this->dom->documentElement
            ?? throw new XmlException('The XML document has no document element.')
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->requireDocumentElement()->tagName;
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace(): ?string
    {
        $namespace = $this->requireDocumentElement()->getAttribute('xmlns');

        return $namespace !== '' ? $namespace : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): ?string
    {
        $schemaLocation = $this->requireDocumentElement()->getAttribute(
            'xsi:schemaLocation'
        );

        if (!$schemaLocation || !str_contains($schemaLocation, ' ')) {
            return null;
        }

        return explode(' ', $schemaLocation)[1];
    }

    /**
     * {@inheritDoc}
     */
    public function loadXml(string $source, int $options = 0): bool
    {
        // If there is no XML string in the source, then an exception is thrown.
        if (empty($source)) {
            throw new XmlParseException(
                'The XML content that you want to load is empty.'
            );
        }

        // Get the current state of libxml and change it before loading the XML
        // to get the errors in a variable if something fails.
        $useInternalErrors = libxml_use_internal_errors(true);

        // Load the XML.
        $status = $this->dom->loadXml($source, $options);

        // Get errors, clear them and restore the libxml error state.
        $errors = XmlError::fromLibxmlErrors(libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if (!$status) {
            $main = XmlError::main($errors);

            throw new XmlParseException(
                $main === null
                    ? 'Failed to load the XML.'
                    : ['Failed to load the XML: {error}', 'error' => $main],
                errors: $errors
            );
        }

        // Return the status of the XML loading.
        // It will only return `true`, since if it fails an exception is thrown.
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(?DOMNode $node = null, int $options = 0): string
    {
        $xml = $this->dom->saveXml($node, $options);
        if ($xml === false) {
            throw new XmlException('The XML could not be converted to text.');
        }

        return XmlHelper::fixEntities($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        $xml = $this->saveXml();
        $xml = preg_replace(
            '/^\s*<\?xml\s[^?]*\?>/i',
            '',
            $xml
        );
        if ($xml === null) {
            throw new XmlException('The XML declaration could not be removed.');
        }

        return trim($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function C14NEncoded(?string $xpath = null): string
    {
        // If an XPath is provided, filter the nodes.
        if ($xpath) {
            $node = $this->getNodes($xpath)->item(0);
            if (!$node instanceof DOMNode) {
                throw new XmlQueryException([
                    'The node with the XPath {xpath} could not be obtained.',
                    'xpath' => $xpath,
                ]);
            }
            $xml = XmlHelper::canonicalize($node);
        }
        // Use C14N() for the entire document if no XPath is specified.
        else {
            $xml = $this->C14N();
        }

        // Fix XML entities.
        $xml = XmlHelper::fixEntities($xml);

        // C14N() always delivers data in UTF-8. Convert to the document's
        // declared encoding if it differs from UTF-8.
        $encoding = $this->getEncoding();
        if ($encoding !== 'UTF-8') {
            $xml = mb_convert_encoding($xml, $encoding, 'UTF-8');
        }

        // Return the canonicalized XML.
        return $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function C14NEncodedFlattened(?string $xpath = null): string
    {
        // Get the canonicalized XML in the document's encoding.
        $xml = $this->C14NEncoded($xpath);

        // Remove the spaces between tags.
        $xml = preg_replace("/>\s+</", '><', $xml);
        if ($xml === null) {
            throw new XmlException('The spaces between the tags could not be removed.');
        }

        // Return the canonicalized and flattened XML.
        return $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function getTopLevelSignatureC14N(): ?string
    {
        // The document element must exist: it is where the signature is.
        $this->requireDocumentElement();

        // Any name that the document element has, with or without a prefix.
        $signatureElement = $this->getNodes('/*/Signature')->item(0);

        if (!$signatureElement instanceof DOMNode) {
            return null;
        }

        return XmlHelper::canonicalize($signatureElement);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, array $params = []): string|array|null
    {
        if (!isset($this->xPathQuery)) {
            $this->xPathQuery = new XPathQuery($this);
        }

        return $this->xPathQuery->get($query, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodes(string $query, array $params = []): DOMNodeList
    {
        if (!isset($this->xPathQuery)) {
            $this->xPathQuery = new XPathQuery($this);
        }

        return $this->xPathQuery->getNodes($query, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $selector, mixed $default = null): mixed
    {
        $array = $this->toArray();

        $keys = explode('.', $selector);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = $this->query('/');

        // A document without content has nothing to give: an empty array, as
        // the decoder does for a document without a document element.
        return is_array($array) ? $array : [];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function C14N(
        bool $exclusive = false,
        bool $withComments = false,
        ?array $xpath = null,
        ?array $nsPrefixes = null
    ): string {
        return XmlHelper::canonicalize($this->dom, $exclusive, $withComments, $xpath, $nsPrefixes);
    }

    /**
     * Returns the data to be serialized by PHP's serialize() function.
     *
     * Stores the full XML string (including the XML declaration with encoding
     * and version) so that the document can be completely reconstructed upon
     * unserialization, preserving encoding, structure, and content.
     *
     * @return array{xml: string}
     */
    public function __serialize(): array
    {
        return ['xml' => $this->saveXml()];
    }

    /**
     * Restores the document from the data produced by __serialize().
     *
     * Allocates a fresh DOMDocument, reapplies the default output settings,
     * then reloads the XML string (which carries its own encoding declaration).
     *
     * @param array{xml: string} $data Data produced by __serialize().
     */
    public function __unserialize(array $data): void
    {
        $this->dom = new DOMDocument();

        $this->dom->formatOutput = true;
        $this->dom->preserveWhiteSpace = true;

        $this->loadXml($data['xml']);
    }
}
