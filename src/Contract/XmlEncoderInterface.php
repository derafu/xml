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

/**
 * Interface for the class that encodes a PHP array to XML.
 */
interface XmlEncoderInterface
{
    /**
     * Converts a PHP array to an XML document, generating the nodes and
     * respecting a namespace if provided.
     *
     * The document that is given is the one of the XML with its namespaces: its
     * elements and its attributes are in the namespaces that the array declares
     * in `@attributes` (`xmlns` and `xmlns:prefix`), as in a document that is
     * loaded from the XML. So the XPath with namespaces and the canonical XML
     * work in it without writing it and loading it again.
     *
     * @param array<int|string, mixed> $data The array with the data that will be used to generate
     * XML.
     * @return XmlDocumentInterface
     */
    public function encode(array $data): XmlDocumentInterface;
}
