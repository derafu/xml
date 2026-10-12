<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Derafu\Xml\Exception\XmlException;

return [

    // Cases for testArrayToXmlAndBackToArray().
    'testArrayToXmlAndBackToArray' => [
        // Simplest case with a single element.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => ['root' => ['element' => 'value']],
            'expectedException' => null,
        ],
        // Array with multiple elements and variants.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expectedException' => null,
        ],
        // Array with special characters that must be escaped.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => ['root' => ['element' => 'Special: & < > " \'']],
            'expectedException' => null,
        ],
        // Array with UTF-8 characters that must be converted to ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // Invalid (empty) array. Does not generate exception, but is not valid.
        'invalid_array' => [
            'data' => [],
            'expected' => [],
            'expectedException' => null,
        ],
        // Case with attributes in nodes.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expectedException' => null,
        ],
        // Case with empty nodes.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => ['root' => ['element' => null]],
            'expectedException' => null,
        ],
        // Case with multiple repeated values.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expectedException' => null,
        ],
        // Case with multiple repeated complex nodes (each node has children).
        'repeated_complex_nodes' => [
            'data' => ['root' => ['item' => [
                ['id' => '1', 'name' => 'A'],
                ['id' => '2', 'name' => 'B'],
            ]]],
            'expected' => ['root' => ['item' => [
                0 => ['id' => '1', 'name' => 'A'],
                1 => ['id' => '2', 'name' => 'B'],
            ]]],
            'expectedException' => null,
        ],
    ],

    // Cases for testArrayToXmlSaveXml().
    'testArrayToXmlSaveXml' => [
        // Simple case with a single element.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>'
                        . "\n<root>\n  <element>value</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Array with multiple elements.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>'
                        . "\n<root>\n  <element1>value1</element1>\n"
                        . "  <element2>value2</element2>\n"
                        . "  <element3>value3</element3>\n</root>\n",
            'expectedException' => null,
        ],
        // Array with special characters that must be escaped.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>'
                        . "\n<root>\n  <element>Special: &amp; &lt; &gt; &quot; &apos;</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Array with UTF-8 characters that must be converted to ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'encoding' => 'ISO-8859-1',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>'
                        . "\n<root>\n  <element>" . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . "</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Invalid (empty) array. Does not generate exception, but is not valid.
        'invalid_array' => [
            'data' => [],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
            'expectedException' => null,
        ],
        // Case with attributes in nodes.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n<root>\n  <element attr1=\"value1\">content</element>\n</root>\n",
            'expectedException' => null,
        ],
        // Case with empty nodes.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n<root>\n  <element/>\n</root>\n",
            'expectedException' => null,
        ],
        // Case with multiple repeated values.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n<root>\n  <item>value1</item>\n  <item>value2</item>\n  <item>value3</item>\n</root>\n",
            'expectedException' => null,
        ],
        // Whitespace control.
        'whitespace_control' => [
            'data' => ['root' => ['element' => '  value  ']],
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n<root>\n  <element>  value  </element>\n</root>\n",
            'expectedException' => null,
        ],
    ],

    // Cases for testArrayToXmlC14N().
    'testArrayToXmlC14N' => [
        // Simple case with a single element.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // Array with multiple elements.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<root><element1>value1</element1>'
                        . '<element2>value2</element2>'
                        . '<element3>value3</element3></root>',
            'expectedException' => null,
        ],
        // Array with special characters that must be escaped.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // Array with UTF-8 characters that must remain as UTF-8.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // Invalid (empty) array. Does not generate exception, but is not valid.
        'invalid_array' => [
            'data' => [],
            'expected' => '',
            'expectedException' => null,
        ],
        // Case with attributes in nodes.
        'element_with_attributes' => [
            'data' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expected' => '<root><element attr1="value1">content</element></root>',
            'expectedException' => null,
        ],
        // Case with empty nodes.
        'empty_node' => [
            'data' => ['root' => ['element' => '']],
            'expected' => '<root><element></element></root>',
            'expectedException' => null,
        ],
        // Case with multiple repeated values.
        'repeated_nodes' => [
            'data' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expected' => '<root><item>value1</item><item>value2</item><item>value3</item></root>',
            'expectedException' => null,
        ],
    ],

    // Cases for testArrayToXmlC14NEncoded().
    'testArrayToXmlC14NEncoded' => [
        // Simple case with a single element.
        'simple_element' => [
            'data' => ['root' => ['element' => 'value']],
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // Array with multiple elements.
        'multiple_elements' => [
            'data' => ['root' => [
                'element1' => 'value1',
                'element2' => 'value2',
                'element3' => 'value3',
            ]],
            'expected' => '<root><element1>value1</element1>'
                        . '<element2>value2</element2>'
                        . '<element3>value3</element3></root>',
            'expectedException' => null,
        ],
        // Array with special characters that must be escaped.
        'special_characters' => [
            'data' => ['root' => ['element' => 'Special: & < > " \'']],
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // Array with UTF-8 characters that must be converted to ISO-8859-1.
        'utf8_to_iso' => [
            'data' => ['root' => ['element' => 'Árbol']],
            'encoding' => 'ISO-8859-1',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // Invalid (empty) array. Does not generate exception, but is not valid.
        'invalid_array' => [
            'data' => [],
            'expected' => '',
            'expectedException' => null,
        ],
    ],

    // Cases for testXmlToArray().
    'testXmlToArray' => [
        // Simple XML with a single element (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => ['root' => ['element' => 'value']],
            'expectedException' => null,
        ],
        // XML with multiple elements (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => [
                'root' => [
                    'element1' => 'value1',
                    'element2' => 'value2',
                ],
            ],
            'expectedException' => null,
        ],
        // XML with escaped special characters (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => [
                'root' => ['element' => 'Special: & < > " \''],
            ],
            'expectedException' => null,
        ],
        // XML in UTF-8 with special characters (e.g. accents, ñ).
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // XML in ISO-8859-1 with special characters (e.g. accents, ñ).
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => ['root' => ['element' => 'Árbol']],
            'expectedException' => null,
        ],
        // Malformed (invalid) XML, expecting an exception.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => [],
            'expectedException' => XmlException::class,
        ],
        // Case with attributes in nodes.
        'element_with_attributes' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element attr1="value1">content</element></root>',
            'expected' => ['root' => ['element' => ['@attributes' => ['attr1' => 'value1'], '@value' => 'content']]],
            'expectedException' => null,
        ],
        // Case with empty nodes.
        'empty_node' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element></element></root>',
            'expected' => ['root' => ['element' => null]],
            'expectedException' => null,
        ],
        // Case with multiple repeated values.
        'repeated_nodes' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><item>value1</item><item>value2</item><item>value3</item></root>',
            'expected' => ['root' => ['item' => ['value1', 'value2', 'value3']]],
            'expectedException' => null,
        ],
        // Case with multiple repeated complex nodes (each node has children).
        'repeated_complex_nodes' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><item><id>1</id><name>A</name></item><item><id>2</id><name>B</name></item></root>',
            'expected' => ['root' => ['item' => [
                0 => ['id' => '1', 'name' => 'A'],
                1 => ['id' => '2', 'name' => 'B'],
            ]]],
            'expectedException' => null,
        ],
    ],

    // Cases for testXmlToSaveXml().
    'testXmlToSaveXml' => [
        // Simple XML with a single element (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>value</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML with multiple elements (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element1>value1</element1>'
                . "\n" . '  <element2>value2</element2>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML with escaped special characters (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>Special: &amp; &lt; &gt; &quot; &apos;</element>'
                . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML in UTF-8 with special characters (e.g. accents, ñ).
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<root>'
                . "\n" . '  <element>Árbol</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // XML in ISO-8859-1 with special characters (e.g. accents, ñ).
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . '<root>'
                . "\n" . '  <element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8')
                . '</element>' . "\n" . '</root>' . "\n",
            'expectedException' => null,
        ],
        // Malformed (invalid) XML, expecting an exception.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

    // Cases for testXmlToC14N().
    'testXmlToC14N' => [
        // Simple XML with a single element (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // XML with multiple elements (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<root><element1>value1</element1><element2>value2</element2></root>',
            'expectedException' => null,
        ],
        // XML with escaped special characters (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // XML in UTF-8 with special characters (e.g. accents, ñ) must remain as UTF-8.
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // XML in ISO-8859-1 with special characters (e.g. accents, ñ) must be converted to UTF-8.
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expected' => '<root><element>Árbol</element></root>',
            'expectedException' => null,
        ],
        // Malformed (invalid) XML, expecting an exception.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

    // Cases for testXmlToC14NEncoded().
    'testXmlToC14NEncoded' => [
        // Simple XML with a single element (ISO-8859-1).
        'simple_element_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element></root>',
            'expected' => '<root><element>value</element></root>',
            'expectedException' => null,
        ],
        // XML with multiple elements (ISO-8859-1).
        'multiple_elements_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element1>value1</element1><element2>value2</element2></root>',
            'expected' => '<root><element1>value1</element1><element2>value2</element2></root>',
            'expectedException' => null,
        ],
        // XML with escaped special characters (ISO-8859-1).
        'special_characters_iso' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expected' => '<root><element>Special: &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        // XML in UTF-8 with special characters (e.g. accents, ñ) must be converted to ISO-8859-1.
        'utf8_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="UTF-8"?><root><element>Árbol</element></root>',
            'encoding' => 'ISO-8859-1',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // XML in ISO-8859-1 with special characters (e.g. accents, ñ) must remain as ISO-8859-1.
        'iso_characters' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'encoding' => 'ISO-8859-1',
            'expected' => '<root><element>' . mb_convert_encoding('Árbol', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        // Malformed (invalid) XML, expecting an exception.
        'invalid_xml' => [
            'xmlContent' => '<?xml version="1.0" encoding="ISO-8859-1"?><root><element>value</element>',
            'expected' => '',
            'expectedException' => XmlException::class,
        ],
    ],

];
