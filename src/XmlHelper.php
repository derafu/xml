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

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;

/**
 * Class with utilities to work with XML strings.
 */
final class XmlHelper
{
    /**
     * Executes an XPath query on an XML document.
     *
     * @param string|DOMDocument $xml XML document to query.
     * @param string $expression XPath expression to execute on the XML.
     * @return DOMNodeList Nodes resulting from the XPath query.
     */
    public static function xpath(
        string|DOMDocument $xml,
        string $expression
    ): DOMNodeList {
        if (is_string($xml)) {
            $document = new DOMDocument();
            $document->loadXml($xml);
        } else {
            $document = $xml;
        }

        $xpath = new DOMXPath($document);
        $result = @$xpath->query($expression);

        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                'Invalid XPath expression: %s',
                $expression
            ));
        }

        return $result;
    }

    /**
     * Sanitizes the values that are assigned to the tags of the XML.
     *
     * @param string $xml Text to assign as value to the XML node.
     * @return string Sanitized text.
     */
    public static function sanitize(string $xml): string
    {
        // If no text is passed or it is a number, do nothing.
        if (!$xml || is_numeric($xml)) {
            return $xml;
        }

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
        $xml = str_replace(array_keys($replace), array_values($replace), $xml);

        // This is on purpose, the replacements must be done again.
        $xml = str_replace('&', '&amp;', $xml);

        /*$xml = str_replace(
            ['"', '\''],
            ['&quot;', '&apos;'],
            $xml
        );*/

        // Return the sanitized text.
        return $xml;
    }

    /**
     * Fixes the entities '&apos;' and '&quot;' in the XML.
     *
     * The correction is only done within the content of the XML tags, but not
     * in the attributes of the tags.
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

        for ($i = 0; $i < $n_chars; ++$i) {
            if ($xml[$i] === '>') {
                $convert = true;
            }
            if ($xml[$i] === '<') {
                $convert = false;
            }
            $newXml .= $convert
                ? str_replace($replaceFrom, $replaceTo, $xml[$i])
                : $xml[$i]
            ;
        }

        return $newXml;
    }
}
