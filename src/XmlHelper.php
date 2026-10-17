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
use Derafu\Xml\Exception\XmlQueryException;
use DOMDocument;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use LibXMLError;

/**
 * Class with utilities to work with XML strings.
 */
final class XmlHelper
{
    /**
     * Executes an XPath query on an XML document.
     *
     * @param string|DOMDocument|XmlDocumentInterface $xml XML document to query.
     * @param string $expression XPath expression to execute on the XML.
     * @return DOMNodeList<DOMNameSpaceNode|DOMNode> Nodes resulting from the XPath query.
     */
    public static function xpath(
        string|DOMDocument|XmlDocumentInterface $xml,
        string $expression
    ): DOMNodeList {
        if (is_string($xml)) {
            $document = new DOMDocument();
            $document->loadXml($xml);
        } elseif ($xml instanceof XmlDocumentInterface) {
            $document = $xml->getDomDocument();
        } else {
            $document = $xml;
        }

        $xpath = new DOMXPath($document);
        $result = @$xpath->query($expression);

        if ($result === false) {
            throw new XmlQueryException([
                'Invalid XPath expression: {expression}',
                'expression' => $expression,
            ]);
        }

        return $result;
    }

    /**
     * Canonicalizes (C14N) a document or a node.
     *
     * `DOMNode::C14N()` gives `false` when libxml can not canonicalize, for
     * example when a namespace is a relative URI (the Canonical XML
     * recommendation requires it to fail). That is an error, and here it is an
     * exception that has what libxml said, instead of a `false` that whoever
     * calls it has to remember to check. libxml does not emit warnings of PHP
     * for it, and its state of errors is the one that it was.
     *
     * @param DOMNode $node The document, or the node, to canonicalize.
     * @param bool $exclusive Indicates if exclusive canonicalization is used.
     * @param bool $withComments Includes comments in the output if `true`.
     * @param array<int|string, mixed>|null $xpath Optional list of nodes to include.
     * @param array<int|string, string>|null $nsPrefixes Namespace prefixes to consider.
     * @return string The canonicalized XML, in UTF-8.
     * @throws XmlException If it can not be canonicalized.
     */
    public static function canonicalize(
        DOMNode $node,
        bool $exclusive = false,
        bool $withComments = false,
        ?array $xpath = null,
        ?array $nsPrefixes = null
    ): string {
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = $node->C14N($exclusive, $withComments, $xpath, $nsPrefixes);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($xml === false) {
            throw new XmlException(
                'The XML could not be canonicalized.',
                errors: self::libxmlErrorsToText($errors)
            );
        }

        return $xml;
    }

    /**
     * Describes the errors of libxml as text, one text for each error.
     *
     * @param array<int, LibXMLError> $errors Errors of libxml.
     * @return list<string> The errors, as text.
     */
    public static function libxmlErrorsToText(array $errors): array
    {
        return array_values(array_map(
            fn (LibXMLError $error) => sprintf(
                'Error %s: %s in line %d, column %d (Code: %d).',
                $error->level === LIBXML_ERR_WARNING ? 'Warning' :
                ($error->level === LIBXML_ERR_ERROR ? 'Error' : 'Fatal'),
                trim($error->message),
                $error->line,
                $error->column,
                $error->code
            ),
            $errors
        ));
    }

    /**
     * Sanitizes the values that are assigned to the tags of the XML.
     *
     * @param string $value Text to assign as value to the XML node.
     * @return string Sanitized text.
     */
    public static function sanitize(string $value): string
    {
        // If no text is passed or it is a number, do nothing.
        if (!$value || is_numeric($value)) {
            return $value;
        }

        // Remove control characters that are illegal in XML 1.0 (everything
        // outside #x9 | #xA | #xD | [#x20-...]). Tab and LF are preserved
        // as-is. CR is normalized to LF per XML spec §2.11 (parsers would
        // convert it anyway, and leaving it would serialize as &#13;).
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        if ($value === null) {
            throw new XmlException('The text could not be sanitized.');
        }
        $value = str_replace("\x0D", "\x0A", $value);

        // Convert "predefined entities" of XML.
        $replace = [
            '&amp;' => '&',
            '&#38;' => '&',
            '&lt;' => '<',
            '&#60;' => '<',
            '&gt;' => '>',
            '&#62' => '>',
            '&quot;' => '"',
            '&#34;' => '"',
            '&apos;' => '\'',
            '&#39;' => '\'',
        ];
        $value = str_replace(array_keys($replace), array_values($replace), $value);

        // This is on purpose, the replacements must be done again.
        $value = str_replace('&', '&amp;', $value);

        /*$value = str_replace(
            ['"', '\''],
            ['&quot;', '&apos;'],
            $value
        );*/

        // Return the sanitized text.
        return $value;
    }

    /**
     * Fixes the entities '&apos;' and '&quot;' in the XML.
     *
     * The correction is done in the content of the XML tags, and also in the
     * attributes of the tags.
     *
     * @param string $xml XML to fix.
     * @return string Fixed XML.
     */
    public static function fixEntities(string $xml): string
    {
        $replace = [
            '\'' => '&apos;',
            '"' => '&quot;',
        ];
        $replaceFrom = array_keys($replace);
        $replaceTo = array_values($replace);

        $newXml = '';
        $n_chars = strlen($xml);
        $convert = false;
        $inAttribute = false;
        $attributeDelimiter = null;

        for ($i = 0; $i < $n_chars; ++$i) {
            $char = $xml[$i];

            // Detect when we enter/exit attribute values.
            if (
                !$convert
                && $char === '='
                && $i + 1 < $n_chars
                && ($xml[$i + 1] === '"' || $xml[$i + 1] === "'")
            ) {
                $inAttribute = true;
                $attributeDelimiter = $xml[$i + 1];
                $i++; // Skip the delimiter.
                $newXml .= $char . $attributeDelimiter;
                continue;
            }

            // Detect when we exit attribute values.
            if ($inAttribute && $char === $attributeDelimiter) {
                $inAttribute = false;
                $attributeDelimiter = null;
                $newXml .= $char;
                continue;
            }

            // Toggle convert mode for tag content.
            if ($char === '>') {
                $convert = true;
            }
            if ($char === '<') {
                $convert = false;
            }

            // Only convert entities if we're in tag content and not in an
            // attribute.
            if ($convert && !$inAttribute) {
                $newXml .= str_replace($replaceFrom, $replaceTo, $char);
            } else {
                $newXml .= $char;
            }
        }

        return $newXml;
    }
}
