<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Service;

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlValidatorInterface;
use Derafu\Xml\Exception\XmlException;

/**
 * Class for the validation of XML and error handling.
 */
class XmlValidator implements XmlValidatorInterface
{
    /**
     * Default translations, and transformations, of the errors of libxml.
     *
     * The objective is to simplify the "technical" messages of libxml and leave
     * them simpler so that a non-technical human can understand them more
     * easily.
     *
     * @var array
     */
    private array $defaultLibxmlTranslations = [
        '\': '
            => '\' (línea %(line)s): ',
        ': [facet \'pattern\'] The value'
            => ': tiene el valor',
        ': This element is not expected. Expected is one of'
            => ': no era el esperado, el campo esperado era alguno de los siguientes',
        ': This element is not expected. Expected is'
            => ': no era el esperado, el campo esperado era',
        'is not accepted by the pattern'
            => 'el que no es válido según la expresión regular (patrón)',
        'is not a valid value of the local atomic type'
            => 'no es un valor válido para el tipo de dato del campo',
        'is not a valid value of the atomic type'
            => 'no es un valor válido, se requiere un valor de tipo',
        ': [facet \'maxLength\'] The value has a length of '
            => ': el valor del campo tiene un largo de ',
        '; this exceeds the allowed maximum length of '
            => ' caracteres excediendo el largo máximo permitido de ',
        ': [facet \'enumeration\'] The value '
            => ': el valor ',
        'is not an element of the set'
            => 'no es válido, debe ser alguno de los valores siguientes',
        '[facet \'minLength\'] The value has a length of'
            => 'el valor del campo tiene un largo de ',
        '; this underruns the allowed minimum length of'
            => ' y el largo mínimo requerido es',
        'Missing child element(s). Expected is'
            => 'debe tener en su interior, nivel inferior, el campo',
        'Character content other than whitespace is not allowed because the content type is \'element-only\''
            => 'el valor del campo es inválido',
        'Element'
            => 'Campo',
        ' ( '
            => ' \'',
        ' ).'
            => '\'.',
        'No matching global declaration available for the validation root'
            => 'El nodo raíz del XML no coincide con lo esperado en la definición del esquema',
    ];

    /**
     * {@inheritDoc}
     */
    public function validate(
        XmlDocumentInterface $xml,
        ?string $schemaPath = null,
        array $translations = []
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
        $isValid = $xml->schemaValidate($schemaPath);

        // Get errors, clear them and restore the state of errors of libxml.
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        // If the XML is not valid, throw an exception with the translated errors.
        if (!$isValid) {
            $errors = !empty($errors)
                ? $this->translateLibxmlErrors($errors, array_merge($translations, [
                    '{' . $xml->getNamespace() . '}' => '',
                ]))
                : []
            ;
            throw new XmlException(
                sprintf(
                    'La validación del XML falló usando el esquema %s.',
                    basename($schemaPath)
                ),
                $errors
            );
        }
    }

    /**
     * Translates the errors of libxml to simpler messages for humans.
     *
     * @param array $errors Array with the original errors of libxml.
     * @param array $translations Additional translations to apply.
     * @return array Array with the translated errors.
     */
    private function translateLibxmlErrors(
        array $errors,
        array $translations = []
    ): array {
        // Define the translation rules.
        $replace = array_merge($this->defaultLibxmlTranslations, $translations);

        // Translate the errors.
        $translatedErrors = [];
        foreach ($errors as $error) {
            $translatedErrors[] = str_replace(
                ['%(line)s'],
                [(string) $error->line],
                str_replace(
                    array_keys($replace),
                    array_values($replace),
                    trim($error->message)
                )
            );
        }

        // Return the translated errors.
        return $translatedErrors;
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
