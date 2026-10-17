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
    'Failed to load the XML: {error}' =>
        'Error al cargar el XML: {error}',
    'The element does not belong to an XML document.' =>
        'El elemento no pertenece a un documento XML.',
    'The XML could not be converted to text.' =>
        'No fue posible convertir el XML a texto.',
    'The XML declaration could not be removed.' =>
        'No fue posible quitar la declaración del XML.',
    'The XML could not be canonicalized.' =>
        'No fue posible canonicalizar el XML.',
    'The XML could not be canonicalized: {error}' =>
        'No fue posible canonicalizar el XML: {error}',
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

    // XmlError and XmlErrorExplainer.
    'Line {line}, column {column}: {message}' =>
        'Línea {line}, columna {column}: {message}',
    'Warning, line {line}, column {column}: {message}' =>
        'Advertencia, línea {line}, columna {column}: {message}',
    'Warning: {message}' =>
        'Advertencia: {message}',
    '{message}' =>
        '{message}',
    'Relative namespace URI is invalid here' =>
        'La URI de un espacio de nombres relativo no es válida aquí',
    'Internal error: {detail}' =>
        'Error interno: {detail}',
    'Excessive depth in document: {depth}, use the XML_PARSE_HUGE option' =>
        'Profundidad excesiva en el documento: {depth}, use la opción XML_PARSE_HUGE',
    'Start tag expected, \'<\' not found' =>
        'Se esperaba una etiqueta de apertura, no se encontró \'<\'',
    'Extra content at the end of the document' =>
        'Contenido extra al final del documento',
    'CharRef: invalid hexadecimal value' =>
        'Referencia de carácter: valor hexadecimal inválido',
    'CharRef: invalid decimal value' =>
        'Referencia de carácter: valor decimal inválido',
    'Invalid character reference, the value {value} is not an XML character' =>
        'Referencia de carácter inválida, el valor {value} no es un carácter XML',
    'Character reference out of bounds' =>
        'Referencia de carácter fuera de rango',
    'Invalid character in the content, the value {value} is not an XML character' =>
        'Carácter inválido en el contenido, el valor {value} no es un carácter XML',
    'Input is not proper UTF-8, indicate encoding ! Bytes: {bytes}' =>
        'La entrada no es UTF-8 válido, indique la codificación. Bytes: {bytes}',
    'Char {char} out of allowed range' =>
        'El carácter {char} está fuera del rango permitido',
    'EntityRef: expecting \';\'' =>
        'Referencia de entidad: se esperaba \';\'',
    'Entity \'\'{name}\'\' not defined' =>
        'La entidad \'\'{name}\'\' no está definida',
    'Unsupported encoding {encoding}' =>
        'Codificación no soportada: {encoding}',
    'String not closed expecting " or \'' =>
        'Texto sin cerrar, se esperaba " o \'',
    'Unescaped \'<\' not allowed in attributes values' =>
        'No se permite \'<\' sin escapar en el valor de un atributo',
    'AttValue: " or \' expected' =>
        'Valor de atributo: se esperaba " o \'',
    'AttValue: \' expected' =>
        'Valor de atributo: se esperaba \'',
    'AttValue length too long' =>
        'El valor del atributo es demasiado largo',
    'Specification mandates value for attribute {name}' =>
        'El atributo {name} debe tener un valor',
    'Attribute {name} redefined' =>
        'El atributo {name} está repetido',
    'Comment not terminated' =>
        'Comentario sin terminar',
    'Processing instruction without a target name' =>
        'Instrucción de procesamiento sin nombre',
    'Processing instruction {name} never ends' =>
        'La instrucción de procesamiento {name} nunca termina',
    'Parsing XML declaration: \'?>\' expected' =>
        'Al leer la declaración XML: se esperaba \'?>\'',
    'DOCTYPE improperly terminated' =>
        'DOCTYPE mal terminado',
    'Sequence \']]>\' not allowed in content' =>
        'No se permite la secuencia \']]>\' en el contenido',
    'CData section not finished: {text}' =>
        'Sección CDATA sin terminar: {text}',
    'XML declaration allowed only at the start of the document' =>
        'La declaración XML solo se permite al inicio del documento',
    'Attributes construct error' =>
        'Error en la construcción de los atributos',
    'Blank needed here' =>
        'Se necesita un espacio en blanco aquí',
    'Space required after \'\'{token}\'\'' =>
        'Se requiere un espacio después de \'\'{token}\'\'',
    'Invalid element name in the start tag' =>
        'Nombre de elemento inválido en la etiqueta de apertura',
    'Error parsing attribute name' =>
        'Error al leer el nombre de un atributo',
    'Entity reference without a name' =>
        'Referencia de entidad sin nombre',
    'No DOCTYPE name' =>
        'El DOCTYPE no tiene nombre',
    'Entity declaration without a name' =>
        'Declaración de entidad sin nombre',
    'Couldn\'\'t find end of the start tag {name} (line {line})' =>
        'No se encontró el final de la etiqueta de apertura {name} (línea {line})',
    'Expected \'>\'' =>
        'Se esperaba \'>\'',
    'Opening and ending tag mismatch: {open} (line {line}) and {close}' =>
        'La etiqueta de apertura {open} (línea {line}) no coincide con la de cierre {close}',
    'Premature end of data in tag {name} (line {line})' =>
        'Fin prematuro de los datos en la etiqueta {name} (línea {line})',
    'Standalone accepts only \'yes\' or \'no\'' =>
        'Standalone solo acepta \'yes\' o \'no\'',
    'Double hyphen within comment: {text}' =>
        'Doble guion dentro de un comentario: {text}',
    'Document labelled {declared} but has {actual} content' =>
        'El documento declara {declared} pero su contenido es {actual}',
    'Detected an entity reference loop' =>
        'Se detectó un ciclo de referencias de entidades',
    'Malformed declaration expecting version' =>
        'Declaración mal formada, se esperaba la versión',
    'xmlns: \'\'{uri}\'\' is not a valid URI' =>
        'xmlns: \'\'{uri}\'\' no es una URI válida',
    'xmlns: URI {uri} is not absolute' =>
        'xmlns: la URI {uri} no es absoluta',
    'Unsupported version \'\'{version}\'\'' =>
        'Versión no soportada: \'\'{version}\'\'',
    'Name too long: {type}' =>
        'Nombre demasiado largo: {type}',
    '{declaration}: Empty XML namespace is not allowed' =>
        '{declaration}: no se permite un espacio de nombres XML vacío',
    'Redefinition of the xmlns prefix is forbidden' =>
        'Está prohibido redefinir el prefijo xmlns',
    'Xml namespace prefix mapped to wrong URI' =>
        'El prefijo xml está asociado a una URI incorrecta',
    'Xml namespace URI cannot be the default namespace' =>
        'La URI del espacio de nombres xml no puede ser el espacio de nombres por defecto',
    'Namespace prefix {prefix} for {attribute} on {element} is not defined' =>
        'El prefijo de espacio de nombres {prefix} del atributo {attribute} en {element} no está definido',
    'Namespace prefix {prefix} on {element} is not defined' =>
        'El prefijo de espacio de nombres {prefix} en {element} no está definido',
    'Failed to parse QName \'\'{qname}\'\'' =>
        'No fue posible leer el QName \'\'{qname}\'\'',
    'Namespaced attribute {attribute} in \'\'{namespace}\'\' redefined' =>
        'El atributo {attribute} del espacio de nombres \'\'{namespace}\'\' está repetido',
    'Failed to load external entity "{path}"' =>
        'No fue posible cargar la entidad externa "{path}"',
    'Failed to locate the main schema resource at \'\'{path}\'\'.' =>
        'No fue posible encontrar el recurso principal del esquema en \'\'{path}\'\'.',
    'The XML document \'\'{path}\'\' is not a schema document.' =>
        'El documento XML \'\'{path}\'\' no es un documento de esquema.',
    'In the declaration of the element \'\'{element}\'\', the value \'\'{value}\'\' of the attribute \'\'{attribute}\'\' does not resolve to a type definition.' =>
        'En la declaración del elemento \'\'{element}\'\', el valor \'\'{value}\'\' del atributo \'\'{attribute}\'\' no corresponde a ninguna definición de tipo.',
    'Attribute \'\'{attribute}\'\' of element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the atomic type \'\'{type}\'\'.' =>
        'Atributo \'\'{attribute}\'\' del elemento \'\'{element}\'\': \'\'{value}\'\' no es un valor válido del tipo atómico \'\'{type}\'\'.',
    'Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the atomic type \'\'{type}\'\'.' =>
        'Elemento \'\'{element}\'\': \'\'{value}\'\' no es un valor válido del tipo atómico \'\'{type}\'\'.',
    'Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the list type \'\'{type}\'\'.' =>
        'Elemento \'\'{element}\'\': \'\'{value}\'\' no es un valor válido del tipo lista \'\'{type}\'\'.',
    'Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the union type \'\'{type}\'\'.' =>
        'Elemento \'\'{element}\'\': \'\'{value}\'\' no es un valor válido del tipo unión \'\'{type}\'\'.',
    'Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this differs from the allowed length of \'\'{allowed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor tiene un largo de \'\'{length}\'\', pero el largo permitido es \'\'{allowed}\'\'.',
    'Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this underruns the allowed minimum length of \'\'{allowed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor tiene un largo de \'\'{length}\'\', menor al largo mínimo permitido de \'\'{allowed}\'\'.',
    'Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this exceeds the allowed maximum length of \'\'{allowed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor tiene un largo de \'\'{length}\'\', mayor al largo máximo permitido de \'\'{allowed}\'\'.',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' is less than the minimum value allowed (\'\'{allowed}\'\').' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' es menor al mínimo permitido (\'\'{allowed}\'\').',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' is greater than the maximum value allowed (\'\'{allowed}\'\').' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' es mayor al máximo permitido (\'\'{allowed}\'\').',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' must be greater than \'\'{allowed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' debe ser mayor a \'\'{allowed}\'\'.',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' must be less than \'\'{allowed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' debe ser menor a \'\'{allowed}\'\'.',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' has more digits than are allowed (\'\'{allowed}\'\').' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' tiene más dígitos de los permitidos (\'\'{allowed}\'\').',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' has more fractional digits than are allowed (\'\'{allowed}\'\').' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' tiene más decimales de los permitidos (\'\'{allowed}\'\').',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' is not accepted by the pattern \'\'{pattern}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' no es válido según el patrón \'\'{pattern}\'\'.',
    'Element \'\'{element}\'\': The value \'\'{value}\'\' is not an element of the set {set}.' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' no es válido, debe ser uno de {set}.',
    'Element \'\'{element}\'\': Character content is not allowed, because the content type is empty.' =>
        'Elemento \'\'{element}\'\': no se permite texto, porque el elemento debe estar vacío.',
    'Element \'\'{element}\'\': Element content is not allowed, because the content type is empty.' =>
        'Elemento \'\'{element}\'\': no se permiten elementos hijos, porque el elemento debe estar vacío.',
    'Element \'\'{element}\'\': Character content other than whitespace is not allowed because the content type is \'\'element-only\'\'.' =>
        'Elemento \'\'{element}\'\': no se permite texto, solo elementos hijos.',
    'Element \'\'{element}\'\': No matching global declaration available for the validation root.' =>
        'Elemento \'\'{element}\'\': el elemento raíz no está declarado en el esquema.',
    'Element \'\'{element}\'\': The element is not \'\'nillable\'\'.' =>
        'Elemento \'\'{element}\'\': el elemento no admite xsi:nil.',
    'Element \'\'{element}\'\': Neither character nor element content is allowed because the element is \'\'nilled\'\'.' =>
        'Elemento \'\'{element}\'\': no se permite contenido porque el elemento es xsi:nil.',
    'Element \'\'{element}\'\': The actual value \'\'{value}\'\' does not match the fixed value constraint \'\'{fixed}\'\'.' =>
        'Elemento \'\'{element}\'\': el valor \'\'{value}\'\' no coincide con el valor fijo \'\'{fixed}\'\'.',
    'Element \'\'{element}\'\': The attribute \'\'{attribute}\'\' is not allowed.' =>
        'Elemento \'\'{element}\'\': el atributo \'\'{attribute}\'\' no está permitido.',
    'Element \'\'{element}\'\': The attribute \'\'{attribute}\'\' is required but missing.' =>
        'Elemento \'\'{element}\'\': falta el atributo obligatorio \'\'{attribute}\'\'.',
    'Element \'\'{element}\'\': Missing child element(s). Expected is one of ( {expected} ).' =>
        'Elemento \'\'{element}\'\': faltan elementos hijos, se esperaba uno de: {expected}.',
    'Element \'\'{element}\'\': Missing child element(s). Expected is ( {expected} ).' =>
        'Elemento \'\'{element}\'\': faltan elementos hijos, se esperaba: {expected}.',
    'Element \'\'{element}\'\': This element is not expected. Expected is one of ( {expected} ).' =>
        'Elemento \'\'{element}\'\': no se esperaba este elemento, se esperaba uno de: {expected}.',
    'Element \'\'{element}\'\': This element is not expected. Expected is ( {expected} ).' =>
        'Elemento \'\'{element}\'\': no se esperaba este elemento, se esperaba: {expected}.',
    'Element \'\'{element}\'\': This element is not expected.' =>
        'Elemento \'\'{element}\'\': no se esperaba este elemento.',
    'Element \'\'{element}\'\', attribute \'\'{attribute}\'\': The value \'\'{value}\'\' does not match the fixed value constraint \'\'{fixed}\'\'.' =>
        'Elemento \'\'{element}\'\', atributo \'\'{attribute}\'\': el valor \'\'{value}\'\' no coincide con el valor fijo \'\'{fixed}\'\'.',
    'Element \'\'{element}\'\': Duplicate key-sequence [{keys}] in unique identity-constraint \'\'{constraint}\'\'.' =>
        'Elemento \'\'{element}\'\': secuencia de claves [{keys}] duplicada en la restricción de unicidad \'\'{constraint}\'\'.',
];
