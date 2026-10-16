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

use DOMElement;

/**
 * Interface for the class that decodes an XML to a PHP array.
 */
interface XmlDecoderInterface
{
    /**
     * Converts an XML document to a PHP array.
     *
     * @param XmlDocumentInterface|DOMElement $documentElement XML document that
     * we want to convert to a PHP array or the element where we will make the
     * conversion if it is not the complete XML document.
     *
     * The array has the data of the XML: its elements, attributes, namespaces,
     * comments and text, just as they are. It does not have what an array of
     * names can not say, as the order among child elements with different names
     * when one of them is repeated, or the place of the text and of the comments
     * among the child elements. See `XmlDecoder` for the structure and its
     * limits.
     *
     * @return array<int|string, mixed> The PHP array with the XML representation.
     */
    public function decode(
        XmlDocumentInterface|DOMElement $documentElement
    ): array;
}
