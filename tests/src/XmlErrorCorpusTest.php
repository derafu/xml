<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml;

use Derafu\Translation\TranslatorFactory;
use Derafu\Xml\Translation\XmlTranslationResourceProvider;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlErrorLevel;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Each error of libxml that can be provoked is explained, in English and in
 * Spanish, from its code: one case for each message of the explainer.
 *
 * The inputs are the ones that make libxml say it, with libxml 2.9 or later.
 */
#[CoversClass(XmlError::class)]
#[CoversClass(XmlErrorExplainer::class)]
#[CoversClass(XmlErrorLevel::class)]
#[CoversClass(XmlTranslationResourceProvider::class)]
final class XmlErrorCorpusTest extends TestCase
{
    /**
     * @return array<string, array{string, string, int, string, string}>
     */
    public static function corpusProvider(): array
    {
        return [
        "4 text only" => ["xml", "texto", 4,
            "Start tag expected, '<' not found",
            "Se esperaba una etiqueta de apertura, no se encontró '<'"],
        "68 lt only" => ["xml", "<", 68,
            "Invalid element name in the start tag",
            "Nombre de elemento inválido en la etiqueta de apertura"],
        "5 lt gt" => ["xml", "<>", 5,
            "Extra content at the end of the document",
            "Contenido extra al final del documento"],
        "77 unclosed root" => ["xml", "<a><b>x</b>", 77,
            "Premature end of data in tag a (line 1)",
            "Fin prematuro de los datos en la etiqueta a (línea 1)"],
        "76 mismatch" => ["xml", "<a><b>x</c></a>", 76,
            "Opening and ending tag mismatch: b (line 1) and c",
            "La etiqueta de apertura b (línea 1) no coincide con la de cierre c"],
        "73 start tag not finished" => ["xml", "<a", 73,
            "Couldn't find end of the start tag a (line 1)",
            "No se encontró el final de la etiqueta de apertura a (línea 1)"],
        "65 start tag not finished 2" => ["xml", "<a b=\"1\"", 65,
            "Attributes construct error",
            "Error en la construcción de los atributos"],
        "68 slash without gt" => ["xml", "<a/", 68,
            "Error parsing attribute name",
            "Error al leer el nombre de un atributo"],
        "73 end tag not finished" => ["xml", "<a></a", 73,
            "Expected '>'",
            "Se esperaba '>'"],
        "39 attr without quotes" => ["xml", "<a x=1/>", 39,
            "AttValue: \" or ' expected",
            "Valor de atributo: se esperaba \" o '"],
        "41 attr without value" => ["xml", "<a x/>", 41,
            "Specification mandates value for attribute x",
            "El atributo x debe tener un valor"],
        "40 attr unclosed quote" => ["xml", "<a x=\"1/>", 40,
            "AttValue: ' expected",
            "Valor de atributo: se esperaba '"],
        "42 attr repeated" => ["xml", "<a x=\"1\" x=\"2\"/>", 42,
            "Attribute x redefined",
            "El atributo x está repetido"],
        "38 attr lt" => ["xml", "<a x=\"<\"/>", 38,
            "Unescaped '<' not allowed in attributes values",
            "No se permite '<' sin escapar en el valor de un atributo"],
        "68 attr amp" => ["xml", "<a x=\"&\"/>", 68,
            "Entity reference without a name",
            "Referencia de entidad sin nombre"],
        "26 attr undeclared entity" => ["xml", "<a x=\"&nope;\"/>", 26,
            "Entity 'nope' not defined",
            "La entidad 'nope' no está definida"],
        "27 undefined in dtd" => ["xml", "<!DOCTYPE a SYSTEM \"nonexistent.dtd\"><a>&e;</a>", 27,
            "Entity 'e' not defined",
            "La entidad 'e' no está definida"],
        "23 amp no semicolon" => ["xml", "<a>&amp</a>", 23,
            "EntityRef: expecting ';'",
            "Referencia de entidad: se esperaba ';'"],
        "6 bad char ref" => ["xml", "<a>&#xZZ;</a>", 6,
            "CharRef: invalid hexadecimal value",
            "Referencia de carácter: valor hexadecimal inválido"],
        "9 bad char ref" => ["xml", "<a>&#xZZ;</a>", 9,
            "Invalid character reference, the value 0 is not an XML character",
            "Referencia de carácter inválida, el valor 0 no es un carácter XML"],
        "9 char ref too big" => ["xml", "<a>&#x110000;</a>", 9,
            "Character reference out of bounds",
            "Referencia de carácter fuera de rango"],
        "9 control char" => ["xml", "<a>\x01</a>", 9,
            "Invalid character in the content, the value 1 is not an XML character",
            "Carácter inválido en el contenido, el valor 1 no es un carácter XML"],
        "62 cdata end in text" => ["xml", "<a>]]></a>", 62,
            "Sequence ']]>' not allowed in content",
            "No se permite la secuencia ']]>' en el contenido"],
        "63 cdata unclosed" => ["xml", "<a><![CDATA[x</a>", 63,
            "CData section not finished: x</",
            "Sección CDATA sin terminar: x</"],
        "9 invalid utf8" => ["xml", "<a>\xFF</a>", 9,
            "Input is not proper UTF-8, indicate encoding ! Bytes: 0xFF 0x3C 0x2F 0x61",
            "La entrada no es UTF-8 válido, indique la codificación. Bytes: 0xFF 0x3C 0x2F 0x61"],
        "9 nonchar" => ["xml", "<a>￿</a>", 9,
            "Char 0xFFFF out of allowed range",
            "El carácter 0xFFFF está fuera del rango permitido"],
        "45 comment unclosed" => ["xml", "<a><!-- x</a>", 45,
            "Comment not terminated",
            "Comentario sin terminar"],
        "80 comment double hyphen" => ["xml", "<a><!-- -- --></a>", 80,
            "Double hyphen within comment: <!--",
            "Doble guion dentro de un comentario: <!--"],
        "47 pi unclosed" => ["xml", "<a><?pi x</a>", 47,
            "Processing instruction pi never ends",
            "La instrucción de procesamiento pi nunca termina"],
        "46 pi no name" => ["xml", "<a><?></a>", 46,
            "Processing instruction without a target name",
            "Instrucción de procesamiento sin nombre"],
        "64 pi xml name" => ["xml", "<a><?xml x?></a>", 64,
            "XML declaration allowed only at the start of the document",
            "La declaración XML solo se permite al inicio del documento"],
        "96 version missing" => ["xml", "<?xml encoding=\"UTF-8\"?><a/>", 96,
            "Malformed declaration expecting version",
            "Declaración mal formada, se esperaba la versión"],
        "65 version missing" => ["xml", "<?xml encoding=\"UTF-8\"?><a/>", 65,
            "Blank needed here",
            "Se necesita un espacio en blanco aquí"],
        "34 version bad" => ["xml", "<?xml version=\"x\"?><a/>", 34,
            "String not closed expecting \" or '",
            "Texto sin cerrar, se esperaba \" o '"],
        "57 version bad" => ["xml", "<?xml version=\"x\"?><a/>", 57,
            "Parsing XML declaration: '?>' expected",
            "Al leer la declaración XML: se esperaba '?>'"],
        "108 version 2" => ["xml", "<?xml version=\"2.0\"?><a/>", 108,
            "Unsupported version '2.0'",
            "Versión no soportada: '2.0'"],
        "32 encoding bad" => ["xml", "<?xml version=\"1.0\" encoding=\"NOPE\"?><a/>", 32,
            "Unsupported encoding NOPE",
            "Codificación no soportada: NOPE"],
        "78 standalone bad" => ["xml", "<?xml version=\"1.0\" standalone=\"maybe\"?><a/>", 78,
            "Standalone accepts only 'yes' or 'no'",
            "Standalone solo acepta 'yes' o 'no'"],
        "81 decl enc mismatch" => ["xml", "<?xml version=\"1.0\" encoding=\"UTF-16\"?><a>x</a>", 81,
            "Document labelled UTF-16 but has UTF-8 content",
            "El documento declara UTF-16 pero su contenido es UTF-8"],
        "1 doctype unclosed" => ["xml", "<!DOCTYPE a [<a/>", 1,
            "Internal error: xmlParseInternalSubset: error detected in Markup declaration",
            "Error interno: xmlParseInternalSubset: error detected in Markup declaration"],
        "61 doctype unclosed" => ["xml", "<!DOCTYPE a [<a/>", 61,
            "DOCTYPE improperly terminated",
            "DOCTYPE mal terminado"],
        "68 doctype bad" => ["xml", "<!DOCTYPE><a/>", 68,
            "No DOCTYPE name",
            "El DOCTYPE no tiene nombre"],
        "89 entity recursive" => ["xml", "<!DOCTYPE a [<!ENTITY e \"&e;\">]><a>&e;</a>", 89,
            "Detected an entity reference loop",
            "Se detectó un ciclo de referencias de entidades"],
        "65 entity decl bad" => ["xml", "<!DOCTYPE a [<!ENTITY>]><a/>", 65,
            "Space required after '<!ENTITY'",
            "Se requiere un espacio después de '<!ENTITY'"],
        "68 entity decl bad" => ["xml", "<!DOCTYPE a [<!ENTITY>]><a/>", 68,
            "Entity declaration without a name",
            "Declaración de entidad sin nombre"],
        "201 prefix undefined" => ["xml", "<p:a/>", 201,
            "Namespace prefix p on a is not defined",
            "El prefijo de espacio de nombres p en a no está definido"],
        "201 prefix undefined attr" => ["xml", "<a p:x=\"1\"/>", 201,
            "Namespace prefix p for x on a is not defined",
            "El prefijo de espacio de nombres p del atributo x en a no está definido"],
        "202 ns two colons" => ["xml", "<a:b:c/>", 202,
            "Failed to parse QName 'a:b:'",
            "No fue posible leer el QName 'a:b:'"],
        "200 ns empty prefix value" => ["xml", "<a xmlns:p=\"\"/>", 200,
            "xmlns:p: Empty XML namespace is not allowed",
            "xmlns:p: no se permite un espacio de nombres XML vacío"],
        "200 xmlns xmlns" => ["xml", "<a xmlns:xmlns=\"x\"/>", 200,
            "Redefinition of the xmlns prefix is forbidden",
            "Está prohibido redefinir el prefijo xmlns"],
        "200 xml prefix rebind" => ["xml", "<a xmlns:xml=\"x\"/>", 200,
            "Xml namespace prefix mapped to wrong URI",
            "El prefijo xml está asociado a una URI incorrecta"],
        "200 xmlns value xml" => ["xml", "<a xmlns=\"http://www.w3.org/XML/1998/namespace\"/>", 200,
            "Xml namespace URI cannot be the default namespace",
            "La URI del espacio de nombres xml no puede ser el espacio de nombres por defecto"],
        "100 ns relative" => ["xml", "<a xmlns=\"relative\"/>", 100,
            "xmlns: URI relative is not absolute",
            "xmlns: la URI relative no es absoluta"],
        "99 ns space in uri" => ["xml", "<a xmlns=\"a b\"/>", 99,
            "xmlns: 'a b' is not a valid URI",
            "xmlns: 'a b' no es una URI válida"],
        "203 attrs same expanded name" => ["xml", "<a xmlns:p=\"x\" xmlns:q=\"x\" p:a=\"1\" q:a=\"2\"/>", 203,
            "Namespaced attribute a in 'x' redefined",
            "El atributo a del espacio de nombres 'x' está repetido"],
        "1 depth 300" => ["xml", str_repeat('<a>', 300) . str_repeat('</a>', 300), 1,
            "Excessive depth in document: 256, use the XML_PARSE_HUGE option",
            "Profundidad excesiva en el documento: 256, use la opción XML_PARSE_HUGE"],
        "110 name too long" => ["xml", '<' . str_repeat('a', 60000) . '/>', 110,
            "Name too long: NCName",
            "Nombre demasiado largo: NCName"],
        "40 attr value huge" => ["xml", '<a x="' . str_repeat('x', 11000000) . '"/>', 40,
            "AttValue length too long",
            "El valor del atributo es demasiado largo"],
        "1839 pattern" => ["xsd", "<root><pat>abc</pat><emp/><elo><c>1</c></elo></root>", 1839,
            "Element 'pat': The value 'abc' is not accepted by the pattern '[A-Z]{3}'.",
            "Elemento 'pat': el valor 'abc' no es válido según el patrón '[A-Z]{3}'."],
        "1840 enumeration" => ["xsd", "<root><enu>z</enu><emp/><elo><c>1</c></elo></root>", 1840,
            "Element 'enu': The value 'z' is not an element of the set {'a', 'b'}.",
            "Elemento 'enu': el valor 'z' no es válido, debe ser uno de {'a', 'b'}."],
        "1830 length" => ["xsd", "<root><len>ab</len><emp/><elo><c>1</c></elo></root>", 1830,
            "Element 'len': The value has a length of '2'; this differs from the allowed length of '3'.",
            "Elemento 'len': el valor tiene un largo de '2', pero el largo permitido es '3'."],
        "1831 minLength" => ["xsd", "<root><min>a</min><emp/><elo><c>1</c></elo></root>", 1831,
            "Element 'min': The value has a length of '1'; this underruns the allowed minimum length of '2'.",
            "Elemento 'min': el valor tiene un largo de '1', menor al largo mínimo permitido de '2'."],
        "1832 maxLength" => ["xsd", "<root><max>abcde</max><emp/><elo><c>1</c></elo></root>", 1832,
            "Element 'max': The value has a length of '5'; this exceeds the allowed maximum length of '4'.",
            "Elemento 'max': el valor tiene un largo de '5', mayor al largo máximo permitido de '4'."],
        "1833 minInclusive" => ["xsd", "<root><rng>0</rng><emp/><elo><c>1</c></elo></root>", 1833,
            "Element 'rng': The value '0' is less than the minimum value allowed ('1').",
            "Elemento 'rng': el valor '0' es menor al mínimo permitido ('1')."],
        "1834 maxInclusive" => ["xsd", "<root><rng>11</rng><emp/><elo><c>1</c></elo></root>", 1834,
            "Element 'rng': The value '11' is greater than the maximum value allowed ('10').",
            "Elemento 'rng': el valor '11' es mayor al máximo permitido ('10')."],
        "1835 minExclusive" => ["xsd", "<root><exc>1</exc><emp/><elo><c>1</c></elo></root>", 1835,
            "Element 'exc': The value '1' must be greater than '1'.",
            "Elemento 'exc': el valor '1' debe ser mayor a '1'."],
        "1836 maxExclusive" => ["xsd", "<root><exc>10</exc><emp/><elo><c>1</c></elo></root>", 1836,
            "Element 'exc': The value '10' must be less than '10'.",
            "Elemento 'exc': el valor '10' debe ser menor a '10'."],
        "1837 totalDigits" => ["xsd", "<root><dig>1234</dig><emp/><elo><c>1</c></elo></root>", 1837,
            "Element 'dig': The value '1234' has more digits than are allowed ('3').",
            "Elemento 'dig': el valor '1234' tiene más dígitos de los permitidos ('3')."],
        "1838 fractionDigits" => ["xsd", "<root><dig>1.25</dig><emp/><elo><c>1</c></elo></root>", 1838,
            "Element 'dig': The value '1.25' has more fractional digits than are allowed ('1').",
            "Elemento 'dig': el valor '1.25' tiene más decimales de los permitidos ('1')."],
        "1824 list item" => ["xsd", "<root><lst>1 x 3</lst><emp/><elo><c>1</c></elo></root>", 1824,
            "Element 'lst': 'x' is not a valid value of the atomic type 'xs:integer'.",
            "Elemento 'lst': 'x' no es un valor válido del tipo atómico 'xs:integer'."],
        "1825 list item" => ["xsd", "<root><lst>1 x 3</lst><emp/><elo><c>1</c></elo></root>", 1825,
            "Element 'lst': '1 x 3' is not a valid value of the list type 'Lst'.",
            "Elemento 'lst': '1 x 3' no es un valor válido del tipo lista 'Lst'."],
        "1826 union" => ["xsd", "<root><uni>z</uni><emp/><elo><c>1</c></elo></root>", 1826,
            "Element 'uni': 'z' is not a valid value of the union type 'Uni'.",
            "Elemento 'uni': 'z' no es un valor válido del tipo unión 'Uni'."],
        "1858 fixed" => ["xsd", "<root><fix>y</fix><emp/><elo><c>1</c></elo></root>", 1858,
            "Element 'fix': The actual value 'y' does not match the fixed value constraint 'x'.",
            "Elemento 'fix': el valor 'y' no coincide con el valor fijo 'x'."],
        "1841 empty with content" => ["xsd", "<root><emp>x</emp><elo><c>1</c></elo></root>", 1841,
            "Element 'emp': Character content is not allowed, because the content type is empty.",
            "Elemento 'emp': no se permite texto, porque el elemento debe estar vacío."],
        "1841 empty with child" => ["xsd", "<root><emp><z/></emp><elo><c>1</c></elo></root>", 1841,
            "Element 'emp': Element content is not allowed, because the content type is empty.",
            "Elemento 'emp': no se permiten elementos hijos, porque el elemento debe estar vacío."],
        "1843 element only with text" => ["xsd", "<root><emp/><elo>text<c>1</c></elo></root>", 1843,
            "Element 'elo': Character content other than whitespace is not allowed because the content type is 'element-only'.",
            "Elemento 'elo': no se permite texto, solo elementos hijos."],
        "1871 missing child" => ["xsd", "<root><emp/><elo/></root>", 1871,
            "Element 'elo': Missing child element(s). Expected is ( c ).",
            "Elemento 'elo': faltan elementos hijos, se esperaba: c."],
        "1871 unexpected child" => ["xsd", "<root><emp/><elo><c>1</c><z/></elo></root>", 1871,
            "Element 'z': This element is not expected.",
            "Elemento 'z': no se esperaba este elemento."],
        "1871 wrong order" => ["xsd", "<root><emp/><elo><c>1</c></elo><pat>ABC</pat></root>", 1871,
            "Element 'pat': This element is not expected. Expected is one of ( txt, chs, att, nil ).",
            "Elemento 'pat': no se esperaba este elemento, se esperaba uno de: txt, chs, att, nil."],
        "1845 unknown root" => ["xsd", "<other/>", 1845,
            "Element 'other': No matching global declaration available for the validation root.",
            "Elemento 'other': el elemento raíz no está declarado en el esquema."],
        "1871 choice none" => ["xsd", "<root><emp/><elo><c>1</c></elo><chs/></root>", 1871,
            "Element 'chs': Missing child element(s). Expected is one of ( x, y ).",
            "Elemento 'chs': faltan elementos hijos, se esperaba uno de: x, y."],
        "1868 missing required attr" => ["xsd", "<root><emp/><elo><c>1</c></elo><att/></root>", 1868,
            "Element 'att': The attribute 'req' is required but missing.",
            "Elemento 'att': falta el atributo obligatorio 'req'."],
        "1874 wrong fixed attr" => ["xsd", "<root><emp/><elo><c>1</c></elo><att req=\"1\" fx=\"w\"/></root>", 1874,
            "Element 'att', attribute 'fx': The value 'w' does not match the fixed value constraint 'v'.",
            "Elemento 'att', atributo 'fx': el valor 'w' no coincide con el valor fijo 'v'."],
        "1824 bad attr type" => ["xsd", "<root><emp/><elo><c>1</c></elo><att req=\"1\" n=\"x\"/></root>", 1824,
            "Attribute 'n' of element 'att': 'x' is not a valid value of the atomic type 'xs:integer'.",
            "Atributo 'n' del elemento 'att': 'x' no es un valor válido del tipo atómico 'xs:integer'."],
        "1866 prohibited attr" => ["xsd", "<root><emp/><elo><c>1</c></elo><att req=\"1\" pro=\"1\"/></root>", 1866,
            "Element 'att': The attribute 'pro' is not allowed.",
            "Elemento 'att': el atributo 'pro' no está permitido."],
        "1877 unique violated" => ["xsd", "<root><emp/><elo><c>1</c></elo><txt>1</txt><txt>1</txt></root>", 1877,
            "Element 'txt': Duplicate key-sequence ['1'] in unique identity-constraint 'u'.",
            "Elemento 'txt': secuencia de claves ['1'] duplicada en la restricción de unicidad 'u'."],
        "1847 nil not allowed" => ["xsd", "<root><pat xsi:nil=\"true\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"/><emp/><elo><c>1</c></elo></root>", 1847,
            "Element 'pat': The element is not 'nillable'.",
            "Elemento 'pat': el elemento no admite xsi:nil."],
        "1848 nil with content" => ["xsd", "<root><emp/><elo><c>1</c></elo><nil xsi:nil=\"true\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">x</nil></root>", 1848,
            "Element 'nil': Neither character nor element content is allowed because the element is 'nilled'.",
            "Elemento 'nil': no se permite contenido porque el elemento es xsi:nil."],
        "7 bad decimal char ref" => ["xml", "<a>&#12a;</a>", 7,
            "CharRef: invalid decimal value",
            "Referencia de carácter: valor decimal inválido"],
        "1871 unexpected before expected" => ["xsd", "<root><emp/><elo><z/><c>1</c></elo></root>", 1871,
            "Element 'z': This element is not expected. Expected is ( c ).",
            "Elemento 'z': no se esperaba este elemento, se esperaba: c."],
        "1955 relative namespace" => ["c14n", "<a xmlns=\"relative\"/>", 1955,
            "Relative namespace URI is invalid here",
            "La URI de un espacio de nombres relativo no es válida aquí"],
        "1549 schema missing" => ["schema", "missing", 1549,
            "Failed to load external entity \"/nonexistent.xsd\"",
            "No fue posible cargar la entidad externa \"/nonexistent.xsd\""],
        "1757 schema missing" => ["schema", "missing", 1757,
            "Failed to locate the main schema resource at '/nonexistent.xsd'.",
            "No fue posible encontrar el recurso principal del esquema en '/nonexistent.xsd'."],
        "1772 schema not a schema" => ["schema", "<root/>", 1772,
            "The XML document '{path}' is not a schema document.",
            "El documento XML '{path}' no es un documento de esquema."],
        "3004 schema broken" => ["schema", "<xs:schema xmlns:xs=\"http://www.w3.org/2001/XMLSchema\"><xs:element name=\"a\" type=\"nope\"/></xs:schema>", 3004,
            "In the declaration of the element 'a', the value 'nope' of the attribute 'type' does not resolve to a type definition.",
            "En la declaración del elemento 'a', el valor 'nope' del atributo 'type' no corresponde a ninguna definición de tipo."],
        ];
    }

    #[DataProvider('corpusProvider')]
    public function testTheErrorIsExplainedFromItsCode(
        string $kind,
        string $input,
        int $code,
        string $english,
        string $spanish
    ): void {
        $error = $this->errorWithTheCode($kind, $input, $code);
        $english = str_replace('{path}', $this->schemaPath(), $english);
        $spanish = str_replace('{path}', $this->schemaPath(), $spanish);

        $message = XmlErrorExplainer::explain($error);

        $this->assertNotNull($message, 'The error is not explained: ' . $error->message);
        $this->assertSame($english, (string) $message);
        $translator = TranslatorFactory::create('es', [], [new XmlTranslationResourceProvider()]);
        $this->assertSame($spanish, $message->trans($translator));
    }

    private function schemaPath(): string
    {
        // libxml reports the real path of the file.
        return realpath(sys_get_temp_dir()) . '/derafu-xml-errors-corpus.xsd';
    }

    /**
     * @return list<XmlError>
     */
    private function errorsOf(string $kind, string $input): array
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new DOMDocument();

        if ($kind === 'xml') {
            $document->loadXML($input);
        } elseif ($kind === 'xsd') {
            $document->loadXML($input);
            $document->schemaValidate(__DIR__ . '/../fixtures/errors.xsd');
        } elseif ($kind === 'c14n') {
            $document->loadXML($input);
            libxml_clear_errors();
            $document->C14N();
        } else {
            $document->loadXML('<a/>');
            $path = '/nonexistent.xsd';
            if ($input !== 'missing') {
                $path = $this->schemaPath();
                file_put_contents($path, $input);
            }
            // PHP warns about a schema that is not valid, libxml has the errors.
            @$document->schemaValidate($path);
            if ($input !== 'missing') {
                unlink($path);
            }
        }

        $errors = XmlError::fromLibxmlErrors(libxml_get_errors());
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        return $errors;
    }

    private function errorWithTheCode(string $kind, string $input, int $code): XmlError
    {
        foreach ($this->errorsOf($kind, $input) as $error) {
            if ($error->code === $code) {
                return $error;
            }
        }

        $this->fail('libxml did not say the error ' . $code . ' for the input.');
    }
}
