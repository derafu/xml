<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    // XmlDocument.
    'The XML document has no document element.' =>
        'El documento XML no tiene elemento raíz.',
    'The XML content that you want to load is empty.' =>
        'El contenido XML que se quiere cargar está vacío.',
    'Failed to load the XML.' =>
        'Error al cargar el XML.',
    'Failed to load the XML: {errors}' =>
        'Error al cargar el XML: {errors}',
    'The element does not belong to an XML document.' =>
        'El elemento no pertenece a un documento XML.',
    'The XML could not be converted to text.' =>
        'No fue posible convertir el XML a texto.',
    'The XML declaration could not be removed.' =>
        'No fue posible quitar la declaración del XML.',
    'The XML could not be canonicalized.' =>
        'No fue posible canonicalizar el XML.',
    'The spaces between the tags could not be removed.' =>
        'No fue posible quitar los espacios entre las etiquetas.',
    'The node with the XPath {xpath} could not be obtained.' =>
        'No fue posible obtener el nodo con el XPath {xpath}.',

    // XmlHelper.
    'Invalid XPath expression: {expression}' =>
        'Expresión XPath inválida: {expression}',
    'The text could not be sanitized.' =>
        'No fue posible sanear el texto.',

    // XPathQuery.
    'An error occurred while executing the XPath expression: {query}. {error}' =>
        'Ocurrió un error al ejecutar la expresión XPath: {query}. {error}',
    '{error}' =>
        '{error}',
    'An error occurred in XPathQuery.' =>
        'Ocurrió un error en XPathQuery.',
    'The XPath expression could not be prepared.' =>
        'No fue posible preparar la expresión XPath.',

    // XmlValidator.
    'The XML validation failed using the schema {schema}.' =>
        'La validación del XML falló usando el esquema {schema}.',
    'The XML validation failed using the schema {schema}: {errors}' =>
        'La validación del XML falló usando el esquema {schema}: {errors}',
    'The XML does not contain a valid schema location in the "xsi:schemaLocation" attribute.' =>
        'El XML no contiene una ubicación de esquema válida en el atributo "xsi:schemaLocation".',
    'To validate an XML, the absolute path to the schema must be specified.' =>
        'Para validar un XML se debe indicar la ruta absoluta al esquema.',

    // XmlEncoder.
    'The type of data of the value entered for the attribute "{attribute}" of the node "{node}" is incorrect (cannot be an array). The value is: {value}' =>
        'El tipo de dato del valor ingresado para el atributo "{attribute}" del nodo "{node}" es incorrecto (no puede ser un arreglo). El valor es: {value}',
    'An XML document can only have one root element. There are {count}: {names}.' =>
        'Un documento XML solo puede tener un elemento raíz. Hay {count}: {names}.',
    'A text can not be written outside of an element.' =>
        'Un texto no se puede escribir fuera de un elemento.',
    'A comment can not have two hyphens in a row nor end with a hyphen: {comment}' =>
        'Un comentario no puede tener dos guiones seguidos ni terminar con un guion: {comment}',
    'The name of a node or an attribute must be text, {name} given.' =>
        'El nombre de un nodo o de un atributo debe ser texto, se entregó {name}.',
    'The value of "{name}" can not be written as text, {type} given.' =>
        'El valor de "{name}" no se puede escribir como texto, se entregó {type}.',
    'The node "{node}" allows including arrays, but they must be arrays with other nodes. The current value is incorrect: {value}' =>
        'El nodo "{node}" permite incluir arreglos, pero deben ser arreglos con otros nodos. El valor actual es incorrecto: {value}',
];
