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
use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Exception\XmlQueryException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;

/**
 * Class that represents an XML document.
 */
final class XmlDocument extends DOMDocument implements XmlDocumentInterface
{
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
        parent::__construct($version, $encoding);

        $this->formatOutput = true;
        $this->preserveWhiteSpace = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getEncoding(): string
    {
        return strtoupper($this->encoding ?: 'UTF-8');
    }

    /**
     * {@inheritDoc}
     */
    public function setEncoding(string $encoding): static
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormatOutput(bool $formatOutput): static
    {
        $this->formatOutput = $formatOutput;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPreserveWhiteSpace(bool $preserveWhiteSpace): static
    {
        $this->preserveWhiteSpace = $preserveWhiteSpace;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->documentElement->tagName;
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace(): ?string
    {
        $namespace = $this->documentElement->getAttribute('xmlns');

        return $namespace !== '' ? $namespace : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): ?string
    {
        $schemaLocation = $this->documentElement->getAttribute(
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
        $status = parent::loadXml($source, $options);

        // Get errors, clear them and restore the libxml error state.
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if (!$status) {
            throw new XmlParseException('Error al cargar el XML.', $errors);
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
        $xml = parent::saveXml($node, $options);

        return XmlHelper::fixEntities($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        $xml = $this->saveXml();
        $xml = preg_replace(
            '/<\?xml\s+version="1\.0"\s+encoding="[^"]+"\s*\?>/i',
            '',
            $xml
        );

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
            if (!$node) {
                throw new XmlQueryException(sprintf(
                    'No fue posible obtener el nodo con el XPath %s.',
                    $xpath
                ));
            }
            $xml = $node->C14N();
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

        // Return the canonicalized and flattened XML.
        return $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function getSignatureNodeXml(): ?string
    {
        $tag = $this->documentElement->tagName;
        $xpath = '/' . $tag . '/Signature';
        $signatureElement = $this->getNodes($xpath)->item(0);

        return $signatureElement?->C14N();
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
        return $this->query('/');
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentElement(): ?DOMElement
    {
        return $this->documentElement;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
