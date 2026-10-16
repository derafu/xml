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

    // Cases for testUtf8CharactersNotSupportedInIso88591().
    'testUtf8CharactersNotSupportedInIso88591' => [
        // UTF-8 characters that do NOT exist in ISO-8859-1.
        // NOTE: Currently, the code does NOT validate these characters,
        // so NO exception is thrown.
        'euro_symbol' => [
            'data' => ['root' => ['element' => 'Precio: 100€']],
            'expected' => ['root' => ['element' => 'Precio: 100€']],
            'expectedException' => null,
        ],
        'trademark_symbol' => [
            'data' => ['root' => ['element' => 'Marca™ registrada']],
            'expected' => ['root' => ['element' => 'Marca™ registrada']],
            'expectedException' => null,
        ],
        'copyright_symbol' => [
            'data' => ['root' => ['element' => 'Derechos© reservados']],
            'expected' => ['root' => ['element' => 'Derechos© reservados']],
            'expectedException' => null,
        ],
        'registered_symbol' => [
            'data' => ['root' => ['element' => 'Marca® registrada']],
            'expected' => ['root' => ['element' => 'Marca® registrada']],
            'expectedException' => null,
        ],
        'degree_symbol' => [
            'data' => ['root' => ['element' => 'Temperatura: 25°C']],
            'expected' => ['root' => ['element' => 'Temperatura: 25°C']],
            'expectedException' => null,
        ],
        'plus_minus_symbol' => [
            'data' => ['root' => ['element' => 'Error ±5%']],
            'expected' => ['root' => ['element' => 'Error ±5%']],
            'expectedException' => null,
        ],
        'superscript_numbers' => [
            'data' => ['root' => ['element' => 'x² + y² = z²']],
            'expected' => ['root' => ['element' => 'x² + y² = z²']],
            'expectedException' => null,
        ],
        'micro_symbol' => [
            'data' => ['root' => ['element' => 'Concentration: 5µg']],
            'expected' => ['root' => ['element' => 'Concentration: 5µg']],
            'expectedException' => null,
        ],
        'multiple_unsupported' => [
            'data' => ['root' => ['element' => 'Text with €™©®°±²³µ']],
            'expected' => ['root' => ['element' => 'Text with €™©®°±²³µ']],
            'expectedException' => null,
        ],
    ],

    // Cases for testControlCharactersInIso88591().
    'testControlCharactersInIso88591' => [
        // Control characters that may cause problems in XML-DSIG.
        'null_character' => [
            'data' => ['root' => ['element' => "Text\x00 with null"]],
            'expected' => ['root' => ['element' => "Text with null"]],
            'expectedException' => null,
        ],
        'start_of_heading' => [
            'data' => ['root' => ['element' => "Text\x01 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'start_of_text' => [
            'data' => ['root' => ['element' => "Text\x02 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'end_of_text' => [
            'data' => ['root' => ['element' => "Text\x03 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'end_of_transmission' => [
            'data' => ['root' => ['element' => "Text\x04 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'enquiry' => [
            'data' => ['root' => ['element' => "Text\x05 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'acknowledge' => [
            'data' => ['root' => ['element' => "Text\x06 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'bell' => [
            'data' => ['root' => ['element' => "Text\x07 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'delete_character' => [
            'data' => ['root' => ['element' => "Text\x7F control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
        'multiple_control_chars' => [
            'data' => ['root' => ['element' => "Text\x00\x01\x02\x03 control"]],
            'expected' => ['root' => ['element' => "Text control"]],
            'expectedException' => null,
        ],
    ],

    // Cases for testSpecialCharactersInAttributes().
    'testSpecialCharactersInAttributes' => [
        // Special characters in XML attributes.
        'ampersand_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value & special'],
                    '@value' => 'content',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value & special'],
                    '@value' => 'content',
                ],
            ]],
            'expectedException' => null,
        ],
        'quotes_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value with "quotes" and \'apostrophes\''],
                    '@value' => 'content',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value with "quotes" and \'apostrophes\''],
                    '@value' => 'content',
                ],
            ]],
            'expectedException' => null,
        ],
        'less_greater_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value < 10 > 5'],
                    '@value' => 'content',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value < 10 > 5'],
                    '@value' => 'content',
                ],
            ]],
            'expectedException' => null,
        ],
        'accented_chars_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú ñ'],
                    '@value' => 'content',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú ñ'],
                    '@value' => 'content',
                ],
            ]],
            'expectedException' => null,
        ],
        'mixed_special_chars_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú & < > " \' ñ'],
                    '@value' => 'content',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú & < > " \' ñ'],
                    '@value' => 'content',
                ],
            ]],
            'expectedException' => null,
        ],
    ],

    // Cases for testC14NWithSpecialCharacters().
    'testC14NWithSpecialCharacters' => [
        // Canonicalization with special characters for XML-DSIG.
        // NOTE: C14NEncoded() converts to ISO-8859-1 if the encoding is ISO-8859-1,
        // so accented characters become substitution characters.
        'accented_chars_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['id' => 'F33T1', 'version' => '1.0'],
                    '@value' => 'Content with áéíóú ñ',
                ],
            ]],
            'encoding' => 'ISO-8859-1',
            'expected' => '<root><element id="F33T1" version="1.0">Content with ' . mb_convert_encoding('áéíóú ñ', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        'special_chars_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'value & < > " \''],
                    '@value' => 'content with & < > " \'',
                ],
            ]],
            'expected' => '<root><element attr="value &amp; &lt; > &quot; \'">content with &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        'mixed_content_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['id' => 'F33T1', 'date' => '2025-01-03'],
                    '@value' => 'Árbol with ñ and áéíóú & < > " \'',
                ],
            ]],
            'expected' => '<root><element date="2025-01-03" id="F33T1">' . 'Árbol with ñ and áéíóú' . ' &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
    ],

    // Cases for testSpecialWhitespaceCharacters().
    'testSpecialWhitespaceCharacters' => [
        // Special whitespace characters.
        // NOTE: Tab (#x9), LF (#xA) and CR (#xD) are legal XML characters per
        // the XML 1.0 spec and are preserved by sanitization. CR normalization
        // to LF (XML spec §2.11) only applies when parsing XML strings; since
        // the encode→decode round-trip does not re-parse, CR is preserved as-is.
        'tab_character' => [
            'data' => ['root' => ['element' => "Text\x09 with tab"]],
            'expected' => ['root' => ['element' => "Text\x09 with tab"]],
            'expectedException' => null,
        ],
        'line_feed' => [
            'data' => ['root' => ['element' => "Text\x0A with LF"]],
            'expected' => ['root' => ['element' => "Text\x0A with LF"]],
            'expectedException' => null,
        ],
        'carriage_return' => [
            'data' => ['root' => ['element' => "Text\x0D with CR"]],
            'expected' => ['root' => ['element' => "Text\x0A with CR"]],
            'expectedException' => null,
        ],
        'space_character' => [
            'data' => ['root' => ['element' => "Text\x20 with space"]],
            'expected' => ['root' => ['element' => "Text\x20 with space"]],
            'expectedException' => null,
        ],
        'mixed_whitespace' => [
            'data' => ['root' => ['element' => "Text\x09\x0A\x0D\x20 with spaces special"]],
            'expected' => ['root' => ['element' => "Text\x09\x0A\x0A\x20 with spaces special"]],
            'expectedException' => null,
        ],
    ],

    // Cases for testC14NEncodingValidation().
    'testC14NEncodingValidation' => [
        // Encoding validation in canonicalization.
        // NOTE: C14NEncoded() converts to ISO-8859-1 if the encoding is ISO-8859-1,
        // so accented characters become substitution characters.
        'accented_chars_encoding' => [
            'data' => ['root' => ['element' => 'Árbol with ñ and áéíóú']],
            'expected' => '<root><element>' . 'Árbol with ñ and áéíóú' . '</element></root>',
            'expectedException' => null,
        ],
        'special_chars_encoding' => [
            'data' => ['root' => ['element' => 'Text with & < > " \'']],
            'expected' => '<root><element>Text with &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        'mixed_chars_encoding' => [
            'data' => ['root' => ['element' => 'Árbol with ñ and & < > " \'']],
            'encoding' => 'ISO-8859-1',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol with ñ and', 'ISO-8859-1', 'UTF-8') . ' &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
    ],

    // Cases for testExportedXmlCompatibilityWithIso88591Encoding().
    'testExportedXmlCompatibilityWithIso88591Encoding' => [
        // Specific compatibility with SII XML-DSIG.
        'sii_document_structure' => [
            'data' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Encabezado' => [
                        'IdDoc' => [
                            'TipoDTE' => '33',
                            'Folio' => '1',
                            'FchEmis' => '2025-01-03',
                        ],
                        'Emisor' => [
                            'RUTEmisor' => '76192083-9',
                            'RznSoc' => 'Company with áéíóú ñ',
                        ],
                    ],
                ],
            ]],
            'expected' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Encabezado' => [
                        'IdDoc' => [
                            'TipoDTE' => '33',
                            'Folio' => '1',
                            'FchEmis' => '2025-01-03',
                        ],
                        'Emisor' => [
                            'RUTEmisor' => '76192083-9',
                            'RznSoc' => 'Company with áéíóú ñ',
                        ],
                    ],
                ],
            ]],
            'expectedException' => null,
        ],
        'sii_with_special_chars' => [
            'data' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Detalle' => [
                        'NmbItem' => 'Product with áéíóú ñ & < > " \'',
                        'DscItem' => 'Description with characters special',
                    ],
                ],
            ]],
            'expected' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Detalle' => [
                        'NmbItem' => 'Product with áéíóú ñ & < > " \'',
                        'DscItem' => 'Description with characters special',
                    ],
                ],
            ]],
            'expectedException' => null,
        ],
    ],

];
