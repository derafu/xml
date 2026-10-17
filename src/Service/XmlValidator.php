<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Service;

use Derafu\Translation\TranslatableList;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlValidatorInterface;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\XmlError;

/**
 * Class for the validation of XML and error handling.
 */
final class XmlValidator implements XmlValidatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function validate(
        XmlDocumentInterface $xml,
        ?string $schemaPath = null
    ): void {
        // Determine $schemaPath if it was not passed.
        if ($schemaPath === null) {
            $schemaPath = $this->getSchemaPath($xml);
        }

        // Get the current state of libxml and change it before validating to
        // be able to obtain them in a variable if there are errors when
        // validating.
        $useInternalErrors = libxml_use_internal_errors(true);

        // Validate the XML document.
        $isValid = $xml->getDomDocument()->schemaValidate($schemaPath);

        // Get errors, clear them and restore the state of errors of libxml.
        $errors = XmlError::fromLibxmlErrors(libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        // If the XML is not valid, throw an exception with the errors.
        if (!$isValid) {
            throw new XmlException(
                $errors === []
                    ? [
                        'The XML validation failed using the schema {schema}.',
                        'schema' => basename($schemaPath),
                    ]
                    : [
                        'The XML validation failed using the schema {schema}: {errors}',
                        'schema' => basename($schemaPath),
                        'errors' => TranslatableList::separated($errors),
                    ],
                errors: $errors,
                xmlDocument: $xml
            );
        }
    }

    /**
     * Searches for the path of the XML schema to validate the XML document.
     *
     * @param XmlDocumentInterface $xml XML document for which the XML schema
     * is searched.
     * @return string Path to the XSD file with the XML schema.
     * @throws XmlException If the XML schema is not found.
     */
    private function getSchemaPath(XmlDocumentInterface $xml): string
    {
        $schema = $xml->getSchema();
        if ($schema === null) {
            throw new XmlException(
                'The XML does not contain a valid schema location in the "xsi:schemaLocation" attribute.'
            );
        }

        if (realpath($schema) === false) {
            throw new XmlException(
                'To validate an XML, the absolute path to the schema must be specified.'
            );
        }

        return $schema;
    }
}
