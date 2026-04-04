<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Exception;

use Derafu\Xml\Contract\XmlDocumentInterface;
use Throwable;

/**
 * Exception thrown when an XPath query fails.
 *
 * Covers two cases:
 *   - The XPath expression is syntactically invalid.
 *   - The XPath expression points to a node that does not exist (when the
 *     node is required).
 */
class XmlQueryException extends XmlException
{
    /**
     * Constructor of the exception.
     *
     * @param string $message The exception message.
     * @param array $errors The array with the errors.
     * @param int $code The exception code (optional).
     * @param Throwable|null $previous The previous exception (optional).
     * @param XmlDocumentInterface|null $xmlDocument The XML document that
     * caused the exception or `null` if it is not present.
     * @param string|null $xpathExpression The XPath expression that caused the
     * exception or `null` if it is not present.
     */
    public function __construct(
        string $message,
        array $errors = [],
        int $code = 0,
        ?Throwable $previous = null,
        ?XmlDocumentInterface $xmlDocument = null,
        private ?string $xpathExpression = null,
    ) {
        parent::__construct($message, $errors, $code, $previous, $xmlDocument);
    }

    /**
     * Gets the XPath expression that caused the exception.
     *
     * @return string|null The XPath expression that caused the exception or
     * `null` if it is not present.
     */
    public function getXpathExpression(): ?string
    {
        return $this->xpathExpression;
    }
}
