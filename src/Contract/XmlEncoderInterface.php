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
     * @param array $data The array with the data that will be used to generate
     * XML.
     * @return XmlDocumentInterface
     */
    public function encode(array $data): XmlDocumentInterface;
}
