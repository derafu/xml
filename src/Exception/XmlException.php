<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Exception;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Core\TranslatableException as Exception;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Throwable;

/**
 * Custom exception for XML errors.
 *
 * It is a translatable exception like any other: what it says is its message.
 * The errors that explain it (the ones of libxml, as text) and the XML document
 * that caused it are data that it carries.
 */
class XmlException extends Exception
{
    /**
     * Constructor of the exception.
     *
     * @param string|array<int|string, mixed>|TranslatableInterface $message The
     * exception message, as it is given to a translatable exception.
     * @param int $code The exception code (optional).
     * @param Throwable|null $previous The previous exception (optional).
     * @param list<string> $errors The errors that explain the exception, as
     * text (optional).
     * @param XmlDocumentInterface|null $xmlDocument The XML document that
     * caused the exception or `null` if it is not present in the exception.
     */
    public function __construct(
        string|array|TranslatableInterface $message,
        int $code = 0,
        ?Throwable $previous = null,
        private array $errors = [],
        private ?XmlDocumentInterface $xmlDocument = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the errors that explain the exception.
     *
     * @return list<string> The errors, as text.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Gets the XML document that caused the exception.
     *
     * @return XmlDocumentInterface|null The XML document that caused the
     * exception or `null` if it is not present in the exception.
     */
    public function getXmlDocument(): ?XmlDocumentInterface
    {
        return $this->xmlDocument;
    }
}
