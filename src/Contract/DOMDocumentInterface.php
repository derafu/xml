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

use DOMDocument;
use DOMElement;

/**
 * Interface for the underlying DOM document access.
 *
 * Exposes the minimal DOM surface needed by XmlDocumentInterface: access to
 * the raw DOMDocument, the document element, and canonicalization. All other
 * DOM operations (mutation, validation) are handled by the services that need
 * them via getDomDocument().
 */
interface DOMDocumentInterface
{
    /**
     * Returns the underlying DOMDocument instance.
     *
     * Provides an escape hatch for services (encoder, validator) that need
     * direct DOM access without polluting the XmlDocument public API.
     *
     * @return DOMDocument
     */
    public function getDomDocument(): DOMDocument;

    /**
     * Returns the root element instance of the document.
     *
     * @return DOMElement|null
     */
    public function getDocumentElement(): ?DOMElement;

    /**
     * Canonicalizes the XML according to the C14N specification.
     *
     * @param bool $exclusive Indicates if exclusive canonicalization is used.
     * @param bool $withComments Includes comments in the output if `true`.
     * @param array|null $xpath Optional list of nodes to include.
     * @param array|null $nsPrefixes Namespace prefixes to consider.
     * @return string|false Canonicalized XML or `false` on error.
     */
    public function C14N(
        bool $exclusive = false,
        bool $withComments = false,
        ?array $xpath = null,
        ?array $nsPrefixes = null
    ): string|false;
}
