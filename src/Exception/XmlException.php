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

use Derafu\Xml\Contract\XmlDocumentInterface;
use Exception;
use LibXMLError;
use Throwable;

/**
 * Custom exception for XML errors.
 */
class XmlException extends Exception
{
    /**
     * Constructor of the exception.
     *
     * @param string $message The exception message.
     * @param array $errors The array with the errors.
     * @param int $code The exception code (optional).
     * @param Throwable|null $previous The previous exception (optional).
     * @param XmlDocumentInterface|null $xmlDocument The XML document that
     * caused the exception or `null` if it is not present in the exception.
     */
    public function __construct(
        string $message,
        private array $errors = [],
        int $code = 0,
        ?Throwable $previous = null,
        private ?XmlDocumentInterface $xmlDocument = null
    ) {
        $message = trim(sprintf(
            '%s %s',
            $message,
            implode(' ', $this->libXmlErrorToString($this->errors))
        ));

        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the array with the errors.
     *
     * @return array The array with the errors.
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

    /**
     * Processes an array of errors, probably of LibXMLError, and returns it as
     * an array of strings.
     *
     * @param array $errors
     * @return array
     */
    private function libXmlErrorToString(array $errors): array
    {
        return array_map(function ($error) {
            if ($error instanceof LibXMLError) {
                return sprintf(
                    'Error %s: %s in line %d, column %d (Code: %d).',
                    $error->level === LIBXML_ERR_WARNING ? 'Warning' :
                    ($error->level === LIBXML_ERR_ERROR ? 'Error' : 'Fatal'),
                    trim($error->message),
                    $error->line,
                    $error->column,
                    $error->code
                );
            }

            return $error;
        }, $errors);
    }
}
