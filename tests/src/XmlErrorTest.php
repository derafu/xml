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
use Derafu\Xml\Exception\XmlParseException;
use Derafu\Xml\Translation\XmlTranslationResourceProvider;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlErrorExplainer;
use Derafu\Xml\XmlErrorLevel;
use LibXMLError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;

/**
 * The errors of libxml as data, and as text that is translated.
 */
#[CoversClass(XmlError::class)]
#[CoversClass(XmlErrorExplainer::class)]
#[CoversClass(XmlErrorLevel::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlParseException::class)]
#[CoversClass(XmlTranslationResourceProvider::class)]
final class XmlErrorTest extends TestCase
{
    private function translator(): Translator
    {
        return TranslatorFactory::create('es', [], [new XmlTranslationResourceProvider()]);
    }

    private function libxmlError(int $level, int $code, int $line, int $column, string $message): LibXMLError
    {
        $error = new LibXMLError();
        $error->level = $level;
        $error->code = $code;
        $error->line = $line;
        $error->column = $column;
        $error->message = $message;

        return $error;
    }

    public function testItKeepsTheDataOfTheErrorOfLibxml(): void
    {
        $error = XmlError::fromLibxml($this->libxmlError(LIBXML_ERR_FATAL, 76, 3, 14, "Some text\n"));

        $this->assertSame(XmlErrorLevel::Fatal, $error->level);
        $this->assertSame(76, $error->code);
        $this->assertSame(3, $error->line);
        $this->assertSame(14, $error->column);
        $this->assertSame("Some text\n", $error->message);
    }

    public function testALevelThatIsNotKnownIsAnError(): void
    {
        $error = XmlError::fromLibxml($this->libxmlError(LIBXML_ERR_NONE, 1, 1, 1, 'Text'));

        $this->assertSame(XmlErrorLevel::Error, $error->level);
    }

    public function testTheErrorsAreCreatedInTheSameOrder(): void
    {
        $errors = XmlError::fromLibxmlErrors([
            5 => $this->libxmlError(LIBXML_ERR_WARNING, 100, 1, 1, 'A'),
            9 => $this->libxmlError(LIBXML_ERR_FATAL, 76, 1, 2, 'B'),
        ]);

        $this->assertSame([100, 76], array_map(fn (XmlError $e) => $e->code, $errors));
        $this->assertSame([0, 1], array_keys($errors));
    }

    public function testTheMainErrorIsTheFirstFatalOneElseTheFirstErrorElseTheFirst(): void
    {
        $warning = new XmlError(XmlErrorLevel::Warning, 100, 1, 1, 'W');
        $error1 = new XmlError(XmlErrorLevel::Error, 201, 1, 1, 'E1');
        $error2 = new XmlError(XmlErrorLevel::Error, 201, 1, 1, 'E2');
        $fatal1 = new XmlError(XmlErrorLevel::Fatal, 76, 1, 1, 'F1');
        $fatal2 = new XmlError(XmlErrorLevel::Fatal, 77, 1, 1, 'F2');

        $this->assertSame($fatal1, XmlError::main([$warning, $error1, $fatal1, $fatal2]));
        $this->assertSame($error1, XmlError::main([$warning, $error1, $error2]));
        $this->assertSame($warning, XmlError::main([$warning]));
        $this->assertNull(XmlError::main([]));
    }

    public function testItSaysWhereTheErrorIsAndWhatItIs(): void
    {
        $error = new XmlError(XmlErrorLevel::Fatal, 23, 2, 9, "EntityRef: expecting ';'\n");

        $this->assertSame("Line 2, column 9: EntityRef: expecting ';'", (string) $error);
        $this->assertSame("Línea 2, columna 9: Referencia de entidad: se esperaba ';'", $error->trans($this->translator()));
    }

    public function testAWarningSaysSo(): void
    {
        $error = new XmlError(XmlErrorLevel::Warning, 100, 1, 4, "xmlns: URI relative is not absolute\n");

        $this->assertSame('Warning, line 1, column 4: xmlns: URI relative is not absolute', (string) $error);
        $this->assertSame(
            'Advertencia, línea 1, columna 4: xmlns: la URI relative no es absoluta',
            $error->trans($this->translator())
        );
    }

    public function testWithoutALineThereIsNoLocation(): void
    {
        $error = new XmlError(XmlErrorLevel::Error, 1955, 0, 0, "Relative namespace UR is invalid here : (null)\n");
        $warning = new XmlError(XmlErrorLevel::Warning, 1549, 0, 0, "failed to load external entity \"/a.xsd\"\n");

        $this->assertSame('Relative namespace URI is invalid here', (string) $error);
        $this->assertSame('La URI de un espacio de nombres relativo no es válida aquí', $error->trans($this->translator()));
        $this->assertSame('Warning: Failed to load external entity "/a.xsd"', (string) $warning);
        $this->assertSame('Advertencia: No fue posible cargar la entidad externa "/a.xsd"', $warning->trans($this->translator()));
    }

    #[DataProvider('unknownProvider')]
    public function testWhatIsNotKnownKeepsTheTextOfLibxml(int $code, string $message): void
    {
        $error = new XmlError(XmlErrorLevel::Fatal, $code, 1, 2, $message . "\n");

        $this->assertNull(XmlErrorExplainer::explain($error));
        $this->assertSame('Line 1, column 2: ' . $message, (string) $error);
        $this->assertSame('Línea 1, columna 2: ' . $message, $error->trans($this->translator()));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function unknownProvider(): array
    {
        return [
            'a code that is not known' => [9999, 'Something that libxml says'],
            'a known code with a text that is not known' => [76, 'Opening and ending tag mismatch in a way that is new'],
            'a text with braces' => [9999, 'Value {x} and \'quotes\''],
        ];
    }

    public function testTheNamespaceOfTheNamesIsNotShown(): void
    {
        $error = new XmlError(
            XmlErrorLevel::Error,
            1871,
            1,
            0,
            "Element '{urn:x}root': This element is not expected. Expected is one of ( {urn:x}a, {urn:x}b ).\n"
        );

        $this->assertSame(
            "Line 1, column 0: Element 'root': This element is not expected. Expected is one of ( a, b ).",
            (string) $error
        );
    }

    public function testItIsData(): void
    {
        $error = new XmlError(XmlErrorLevel::Error, 201, 4, 7, "Namespace prefix p on a is not defined\n");

        $this->assertSame([
            'level' => 'Error',
            'code' => 201,
            'line' => 4,
            'column' => 7,
            'message' => 'Namespace prefix p on a is not defined',
        ], $error->jsonSerialize());
    }

    /**
     * @param list<string>|list<list<string>> $errors Level, code and line of
     * each error. libxml does not say the same errors in all its versions, then
     * it can be a list of the ways that it says them, and it has to be one.
     */
    #[DataProvider('loadProvider')]
    public function testAnXmlThatCanNotBeLoadedSaysTheErrorThatCausedIt(
        string $xml,
        string $english,
        string $spanish,
        array $errors
    ): void {
        try {
            (new XmlDocument())->loadXml($xml);
            $this->fail('An XML that is not well formed was loaded.');
        } catch (XmlParseException $e) {
            $this->assertSame($english, $e->getMessage());
            $this->assertSame($spanish, $e->trans($this->translator()));
            $said = array_map(
                fn (XmlError $error) => $error->level->name . ':' . $error->code . '@' . $error->line,
                $e->getErrors()
            );
            $ways = is_array($errors[0] ?? null) ? $errors : [$errors];
            $this->assertContains($said, $ways, sprintf(
                'libxml %s did not say the errors in any of the ways that are known; it said: %s',
                LIBXML_DOTTED_VERSION,
                implode(', ', $said)
            ));
        }
    }

    /**
     * @return array<string, array{string, string, string, list<string>|list<list<string>>}>
     */
    public static function loadProvider(): array
    {
        return [
            'a tag that is not closed' => [
                '<root><unclosed></root>',
                'Failed to load the XML: Line 1, column 24: Opening and ending tag mismatch: unclosed (line 1) and root',
                'Error al cargar el XML: Línea 1, columna 24: La etiqueta de apertura unclosed (línea 1) no coincide con la de cierre root',
                [['Fatal:76@1', 'Fatal:77@1'], ['Fatal:76@1']],
            ],
            'an ampersand' => [
                '<a>&amp</a>',
                "Failed to load the XML: Line 1, column 8: EntityRef: expecting ';'",
                "Error al cargar el XML: Línea 1, columna 8: Referencia de entidad: se esperaba ';'",
                ['Fatal:23@1'],
            ],
            'text and no tags' => [
                'texto',
                "Failed to load the XML: Line 1, column 1: Start tag expected, '<' not found",
                'Error al cargar el XML: Línea 1, columna 1: Se esperaba una etiqueta de apertura, no se encontró \'<\'',
                ['Fatal:4@1'],
            ],
            'a warning before the fatal error' => [
                '<a xmlns="relative"><b></a>',
                'Failed to load the XML: Line 1, column 28: Opening and ending tag mismatch: b (line 1) and a',
                'Error al cargar el XML: Línea 1, columna 28: La etiqueta de apertura b (línea 1) no coincide con la de cierre a',
                [['Warning:100@1', 'Fatal:76@1', 'Fatal:77@1'], ['Warning:100@1', 'Fatal:76@1']],
            ],
            'an error of namespace before the fatal error' => [
                '<p:a><b></p:a>',
                'Failed to load the XML: Line 1, column 15: Opening and ending tag mismatch: b (line 1) and p:a',
                'Error al cargar el XML: Línea 1, columna 15: La etiqueta de apertura b (línea 1) no coincide con la de cierre p:a',
                [['Error:201@1', 'Fatal:76@1', 'Fatal:77@1'], ['Error:201@1', 'Fatal:76@1']],
            ],
            'an error in a line that is not the first one' => [
                "<a>\n  <b>\n    <c>x</b>\n  </c>\n</a>",
                'Failed to load the XML: Line 3, column 13: Opening and ending tag mismatch: c (line 3) and b',
                'Error al cargar el XML: Línea 3, columna 13: La etiqueta de apertura c (línea 3) no coincide con la de cierre b',
                ['Fatal:76@3', 'Fatal:76@4'],
            ],
            'an attribute without quotes' => [
                '<a x=1/>',
                'Failed to load the XML: Line 1, column 6: AttValue: " or \' expected',
                'Error al cargar el XML: Línea 1, columna 6: Valor de atributo: se esperaba " o \'',
                [['Fatal:39@1', 'Fatal:65@1', 'Fatal:73@1', 'Fatal:5@1'], ['Fatal:39@1', 'Fatal:65@1', 'Fatal:73@1']],
            ],
        ];
    }

    /**
     * @return array<string, array{int, string, string, array<string, string>}>
     */
    public static function explainedProvider(): array
    {
        return [
            'depth, with the code of the limit' => [
                114,
                'Excessive depth in document: 300, use XML_PARSE_HUGE option',
                'Excessive depth in document: {depth}, use the XML_PARSE_HUGE option',
                ['depth' => '300'],
            ],
            'depth, with the generic code' => [
                1,
                'Excessive depth in document: 256 use XML_PARSE_HUGE option',
                'Excessive depth in document: {depth}, use the XML_PARSE_HUGE option',
                ['depth' => '256'],
            ],
            'an internal error' => [
                1,
                'Internal error: xmlParseInternalSubset: error detected in Markup declaration',
                'Internal error: {detail}',
                ['detail' => 'xmlParseInternalSubset: error detected in Markup declaration'],
            ],
            'an input that is not proper UTF-8' => [
                9,
                "Input is not proper UTF-8, indicate encoding !\nBytes: 0xFF 0x3C 0x2F 0x61",
                'Input is not proper UTF-8, indicate encoding ! Bytes: {bytes}',
                ['bytes' => '0xFF 0x3C 0x2F 0x61'],
            ],
            'a doctype that is improperly terminated' => [
                61,
                'DOCTYPE improperly terminated',
                'DOCTYPE improperly terminated',
                [],
            ],
            'a document labelled with another encoding' => [
                81,
                'Document labelled UTF-16 but has UTF-8 content',
                'Document labelled {declared} but has {actual} content',
                ['declared' => 'UTF-16', 'actual' => 'UTF-8'],
            ],
            'a char out of the allowed range' => [
                9,
                'Char 0x1F out of allowed range',
                'Char {char} out of allowed range',
                ['char' => '0x1F'],
            ],
            'a value of an attribute that is too long' => [
                40,
                'AttValue length too long',
                'AttValue length too long',
                [],
            ],
            'invalid bytes in the encoding' => [
                81,
                'Invalid bytes in character encoding',
                'Invalid bytes in character encoding',
                [],
            ],
            'an encoding that does not match' => [
                113,
                "Encoding 'UTF-8' doesn't match auto-detected 'UTF-16'",
                "Encoding ''{declared}'' doesn''t match auto-detected ''{actual}''",
                ['declared' => 'UTF-8', 'actual' => 'UTF-16'],
            ],
            'a buffer that is too big' => [
                114,
                'Resource limit exceeded: Buffer size limit exceeded, try XML_PARSE_HUGE',
                'Buffer size limit exceeded, use the XML_PARSE_HUGE option',
                [],
            ],
            'an error in the internal subset' => [
                118,
                'Content error in the internal subset',
                'Content error in the internal subset',
                [],
            ],
            'a file that failed to load' => [
                1524,
                'failed to load "/x.xsd": No such file or directory',
                'Failed to load "{path}": {reason}',
                ['path' => '/x.xsd', 'reason' => 'No such file or directory'],
            ],
        ];
    }

    /**
     * @param array<string, string> $parameters
     */
    #[DataProvider('explainedProvider')]
    public function testAnErrorOfLibxmlIsExplainedByItsCodeAndItsText(
        int $code,
        string $text,
        string $message,
        array $parameters
    ): void {
        $explanation = XmlErrorExplainer::explain(new XmlError(XmlErrorLevel::Fatal, $code, 1, 1, $text . "\n"));

        $this->assertNotNull($explanation);
        $this->assertSame($message, $explanation->getMessage());
        $this->assertSame($parameters, $explanation->getParameters());
        $this->assertSame('errors', $explanation->getDomain());
    }

    public function testTheErrorIsAMessageThatSaysWhereItIs(): void
    {
        $message = (new XmlError(XmlErrorLevel::Fatal, 9999, 3, 14, "Some text\n"))->toMessage();

        $this->assertSame('Line {line}, column {column}: {message}', $message->getMessage());
        $this->assertSame(['line' => 3, 'column' => 14, 'message' => 'Some text'], $message->getParameters());
    }
}
