<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml;

use Derafu\Translation\TranslatableMessage;

/**
 * Explains an error of libxml with a message that can be translated.
 *
 * libxml gives its errors as English text with the data inside (names, values,
 * lines). The code of the error says which message it is, and the text is
 * matched to take the data out of it. Whatever is not matched, a code or a text
 * that libxml says in a way that is not known here, is not explained: the
 * caller keeps the original text of libxml.
 */
final class XmlErrorExplainer
{
    /**
     * Explains an error.
     *
     * @param XmlError $error The error of libxml.
     * @return TranslatableMessage|null The explanation, or `null` if the error
     * is not known and its original text is the only thing there is to say.
     */
    public static function explain(XmlError $error): ?TranslatableMessage
    {
        $text = trim($error->message);

        /** @var array<int|string, string> $g */
        $g = [];
        $is = static function (int|array $codes, string $pattern) use ($error, $text, &$g): bool {
            return in_array($error->code, (array) $codes, true)
                && preg_match('~^' . $pattern . '$~s', $text, $g) === 1;
        };

        // Names of elements, attributes and types come with the namespace as
        // "{uri}name" when libxml explains a validation, it is not shown.
        $strip = static fn (string $value): string => preg_replace('~\{[^}]*\}~', '', $value) ?? $value;

        return match (true) {
            $is(1, '[Ii]nternal error ?: (?<detail>.+)')
                => new TranslatableMessage('Internal error: {detail}', ['detail' => $g['detail']], domain: 'errors'),
            $is(1, 'Excessive depth in document: (?<depth>\d+) use XML_PARSE_HUGE option')
                => new TranslatableMessage('Excessive depth in document: {depth}, use the XML_PARSE_HUGE option', ['depth' => $g['depth']], domain: 'errors'),
            $is(1955, 'Relative namespace UR is invalid here : (?<detail>.*)')
                => new TranslatableMessage('Relative namespace URI is invalid here', domain: 'errors'),
            $is(4, 'Start tag expected, \'<\' not found')
                => new TranslatableMessage('Start tag expected, \'<\' not found', domain: 'errors'),
            $is(5, 'Extra content at the end of the document')
                => new TranslatableMessage('Extra content at the end of the document', domain: 'errors'),
            $is(6, 'CharRef: invalid hexadecimal value')
                => new TranslatableMessage('CharRef: invalid hexadecimal value', domain: 'errors'),
            $is(7, 'CharRef: invalid decimal value')
                => new TranslatableMessage('CharRef: invalid decimal value', domain: 'errors'),
            $is(9, 'xmlParseCharRef: invalid xmlChar value (?<value>-?\d+)')
                => new TranslatableMessage('Invalid character reference, the value {value} is not an XML character', ['value' => $g['value']], domain: 'errors'),
            $is(9, 'xmlParseCharRef: character reference out of bounds')
                => new TranslatableMessage('Character reference out of bounds', domain: 'errors'),
            $is(9, 'PCDATA invalid Char value (?<value>-?\d+)')
                => new TranslatableMessage('Invalid character in the content, the value {value} is not an XML character', ['value' => $g['value']], domain: 'errors'),
            $is(9, 'Input is not proper UTF-8, indicate encoding !\nBytes: (?<bytes>.+)')
                => new TranslatableMessage('Input is not proper UTF-8, indicate encoding ! Bytes: {bytes}', ['bytes' => $g['bytes']], domain: 'errors'),
            $is(9, 'Char (?<char>0x[0-9A-Fa-f]+) out of allowed range')
                => new TranslatableMessage('Char {char} out of allowed range', ['char' => $g['char']], domain: 'errors'),
            $is(23, 'EntityRef: expecting \';\'')
                => new TranslatableMessage('EntityRef: expecting \';\'', domain: 'errors'),
            $is([26, 27], 'Entity \'(?<name>[^\']*)\' not defined')
                => new TranslatableMessage('Entity \'\'{name}\'\' not defined', ['name' => $g['name']], domain: 'errors'),
            $is(32, 'Unsupported encoding (?<encoding>.+)')
                => new TranslatableMessage('Unsupported encoding {encoding}', ['encoding' => $g['encoding']], domain: 'errors'),
            $is(34, 'String not closed expecting \" or \'')
                => new TranslatableMessage('String not closed expecting " or \'', domain: 'errors'),
            $is(38, 'Unescaped \'<\' not allowed in attributes values')
                => new TranslatableMessage('Unescaped \'<\' not allowed in attributes values', domain: 'errors'),
            $is(39, 'AttValue: \" or \' expected')
                => new TranslatableMessage('AttValue: " or \' expected', domain: 'errors'),
            $is(40, 'AttValue: \' expected')
                => new TranslatableMessage('AttValue: \' expected', domain: 'errors'),
            $is(40, 'AttValue length too long')
                => new TranslatableMessage('AttValue length too long', domain: 'errors'),
            $is(41, 'Specification mandates value for attribute (?<name>.+)')
                => new TranslatableMessage('Specification mandates value for attribute {name}', ['name' => $g['name']], domain: 'errors'),
            $is(42, 'Attribute (?<name>.+) redefined')
                => new TranslatableMessage('Attribute {name} redefined', ['name' => $g['name']], domain: 'errors'),
            $is(45, 'Comment not terminated')
                => new TranslatableMessage('Comment not terminated', domain: 'errors'),
            $is(46, 'xmlParsePI : no target name')
                => new TranslatableMessage('Processing instruction without a target name', domain: 'errors'),
            $is(47, 'ParsePI: PI (?<name>.+) never end \.\.\.')
                => new TranslatableMessage('Processing instruction {name} never ends', ['name' => $g['name']], domain: 'errors'),
            $is(57, 'parsing XML declaration: \'\?>\' expected')
                => new TranslatableMessage('Parsing XML declaration: \'?>\' expected', domain: 'errors'),
            $is(61, 'DOCTYPE improperly terminated')
                => new TranslatableMessage('DOCTYPE improperly terminated', domain: 'errors'),
            $is(62, 'Sequence \']]>\' not allowed in content')
                => new TranslatableMessage('Sequence \']]>\' not allowed in content', domain: 'errors'),
            $is(63, 'CData section not finished\n(?<text>.*)')
                => new TranslatableMessage('CData section not finished: {text}', ['text' => $g['text']], domain: 'errors'),
            $is(64, 'XML declaration allowed only at the start of the document')
                => new TranslatableMessage('XML declaration allowed only at the start of the document', domain: 'errors'),
            $is(65, 'attributes construct error')
                => new TranslatableMessage('Attributes construct error', domain: 'errors'),
            $is(65, 'Blank needed here')
                => new TranslatableMessage('Blank needed here', domain: 'errors'),
            $is(65, 'Space required after \'(?<token>[^\']*)\'')
                => new TranslatableMessage('Space required after \'\'{token}\'\'', ['token' => $g['token']], domain: 'errors'),
            $is(68, 'StartTag: invalid element name')
                => new TranslatableMessage('Invalid element name in the start tag', domain: 'errors'),
            $is(68, 'error parsing attribute name')
                => new TranslatableMessage('Error parsing attribute name', domain: 'errors'),
            $is(68, 'xmlParseEntityRef: no name')
                => new TranslatableMessage('Entity reference without a name', domain: 'errors'),
            $is(68, 'xmlParseDocTypeDecl : no DOCTYPE name !')
                => new TranslatableMessage('No DOCTYPE name', domain: 'errors'),
            $is(68, 'xmlParseEntityDecl: no name')
                => new TranslatableMessage('Entity declaration without a name', domain: 'errors'),
            $is(73, 'Couldn\'t find end of Start Tag (?<name>.+) line (?<line>\d+)')
                => new TranslatableMessage('Couldn\'\'t find end of the start tag {name} (line {line})', ['name' => $g['name'], 'line' => $g['line']], domain: 'errors'),
            $is(73, 'expected \'>\'')
                => new TranslatableMessage('Expected \'>\'', domain: 'errors'),
            $is(76, 'Opening and ending tag mismatch: (?<open>.+) line (?<line>\d+) and (?<close>.*)')
                => new TranslatableMessage('Opening and ending tag mismatch: {open} (line {line}) and {close}', ['open' => $g['open'], 'line' => $g['line'], 'close' => $g['close']], domain: 'errors'),
            $is(77, 'Premature end of data in tag (?<name>.+) line (?<line>\d+)')
                => new TranslatableMessage('Premature end of data in tag {name} (line {line})', ['name' => $g['name'], 'line' => $g['line']], domain: 'errors'),
            $is(78, 'standalone accepts only \'yes\' or \'no\'')
                => new TranslatableMessage('Standalone accepts only \'yes\' or \'no\'', domain: 'errors'),
            $is(80, 'Double hyphen within comment: (?<text>.*)')
                => new TranslatableMessage('Double hyphen within comment: {text}', ['text' => $g['text']], domain: 'errors'),
            $is(81, 'Document labelled (?<declared>\S+) but has (?<actual>\S+) content')
                => new TranslatableMessage('Document labelled {declared} but has {actual} content', ['declared' => $g['declared'], 'actual' => $g['actual']], domain: 'errors'),
            $is(89, 'Detected an entity reference loop')
                => new TranslatableMessage('Detected an entity reference loop', domain: 'errors'),
            $is(96, 'Malformed declaration expecting version')
                => new TranslatableMessage('Malformed declaration expecting version', domain: 'errors'),
            $is(99, 'xmlns: \'(?<uri>[^\']*)\' is not a valid URI')
                => new TranslatableMessage('xmlns: \'\'{uri}\'\' is not a valid URI', ['uri' => $g['uri']], domain: 'errors'),
            $is(100, 'xmlns: URI (?<uri>.*) is not absolute')
                => new TranslatableMessage('xmlns: URI {uri} is not absolute', ['uri' => $g['uri']], domain: 'errors'),
            $is(108, 'Unsupported version \'(?<version>[^\']*)\'')
                => new TranslatableMessage('Unsupported version \'\'{version}\'\'', ['version' => $g['version']], domain: 'errors'),
            $is(110, 'Name too long: (?<type>.+)')
                => new TranslatableMessage('Name too long: {type}', ['type' => $strip($g['type'])], domain: 'errors'),
            $is(200, '(?<declaration>xmlns:\S+): Empty XML namespace is not allowed')
                => new TranslatableMessage('{declaration}: Empty XML namespace is not allowed', ['declaration' => $g['declaration']], domain: 'errors'),
            $is(200, 'redefinition of the xmlns prefix is forbidden')
                => new TranslatableMessage('Redefinition of the xmlns prefix is forbidden', domain: 'errors'),
            $is(200, 'xml namespace prefix mapped to wrong URI')
                => new TranslatableMessage('Xml namespace prefix mapped to wrong URI', domain: 'errors'),
            $is(200, 'xml namespace URI cannot be the default namespace')
                => new TranslatableMessage('Xml namespace URI cannot be the default namespace', domain: 'errors'),
            $is(201, 'Namespace prefix (?<prefix>.+) for (?<attribute>.+) on (?<element>.+) is not defined')
                => new TranslatableMessage('Namespace prefix {prefix} for {attribute} on {element} is not defined', ['prefix' => $g['prefix'], 'attribute' => $strip($g['attribute']), 'element' => $strip($g['element'])], domain: 'errors'),
            $is(201, 'Namespace prefix (?<prefix>.+) on (?<element>.+) is not defined')
                => new TranslatableMessage('Namespace prefix {prefix} on {element} is not defined', ['prefix' => $g['prefix'], 'element' => $strip($g['element'])], domain: 'errors'),
            $is(202, 'Failed to parse QName \'(?<qname>[^\']*)\'')
                => new TranslatableMessage('Failed to parse QName \'\'{qname}\'\'', ['qname' => $g['qname']], domain: 'errors'),
            $is(203, 'Namespaced Attribute (?<attribute>.+) in \'(?<namespace>[^\']*)\' redefined')
                => new TranslatableMessage('Namespaced attribute {attribute} in \'\'{namespace}\'\' redefined', ['attribute' => $strip($g['attribute']), 'namespace' => $g['namespace']], domain: 'errors'),
            $is(1549, 'failed to load external entity \"(?<path>.*)\"')
                => new TranslatableMessage('Failed to load external entity "{path}"', ['path' => $g['path']], domain: 'errors'),
            $is(1757, 'Failed to locate the main schema resource at \'(?<path>.*)\'\.')
                => new TranslatableMessage('Failed to locate the main schema resource at \'\'{path}\'\'.', ['path' => $g['path']], domain: 'errors'),
            $is(1772, 'The XML document \'(?<path>.*)\' is not a schema document\.')
                => new TranslatableMessage('The XML document \'\'{path}\'\' is not a schema document.', ['path' => $g['path']], domain: 'errors'),
            $is(3004, 'element decl\. \'(?<element>[^\']*)\', attribute \'(?<attribute>[^\']*)\': The QName value \'(?<value>[^\']*)\' does not resolve to a\(n\) type definition\.')
                => new TranslatableMessage('In the declaration of the element \'\'{element}\'\', the value \'\'{value}\'\' of the attribute \'\'{attribute}\'\' does not resolve to a type definition.', ['element' => $strip($g['element']), 'attribute' => $strip($g['attribute']), 'value' => $g['value']], domain: 'errors'),
            $is(1824, 'Element \'(?<element>[^\']*)\', attribute \'(?<attribute>[^\']*)\': \'(?<value>.*)\' is not a valid value of the atomic type \'(?<type>[^\']*)\'\.')
                => new TranslatableMessage('Attribute \'\'{attribute}\'\' of element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the atomic type \'\'{type}\'\'.', ['element' => $strip($g['element']), 'attribute' => $strip($g['attribute']), 'value' => $g['value'], 'type' => $strip($g['type'])], domain: 'errors'),
            $is(1824, 'Element \'(?<element>[^\']*)\': \'(?<value>.*)\' is not a valid value of the atomic type \'(?<type>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the atomic type \'\'{type}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'type' => $strip($g['type'])], domain: 'errors'),
            $is(1825, 'Element \'(?<element>[^\']*)\': \'(?<value>.*)\' is not a valid value of the list type \'(?<type>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the list type \'\'{type}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'type' => $strip($g['type'])], domain: 'errors'),
            $is(1826, 'Element \'(?<element>[^\']*)\': \'(?<value>.*)\' is not a valid value of the union type \'(?<type>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': \'\'{value}\'\' is not a valid value of the union type \'\'{type}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'type' => $strip($g['type'])], domain: 'errors'),
            $is(1830, 'Element \'(?<element>[^\']*)\': \[facet \'length\'\] The value has a length of \'(?<length>[^\']*)\'; this differs from the allowed length of \'(?<allowed>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this differs from the allowed length of \'\'{allowed}\'\'.', ['element' => $strip($g['element']), 'length' => $g['length'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1831, 'Element \'(?<element>[^\']*)\': \[facet \'minLength\'\] The value has a length of \'(?<length>[^\']*)\'; this underruns the allowed minimum length of \'(?<allowed>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this underruns the allowed minimum length of \'\'{allowed}\'\'.', ['element' => $strip($g['element']), 'length' => $g['length'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1832, 'Element \'(?<element>[^\']*)\': \[facet \'maxLength\'\] The value has a length of \'(?<length>[^\']*)\'; this exceeds the allowed maximum length of \'(?<allowed>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value has a length of \'\'{length}\'\'; this exceeds the allowed maximum length of \'\'{allowed}\'\'.', ['element' => $strip($g['element']), 'length' => $g['length'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1833, 'Element \'(?<element>[^\']*)\': \[facet \'minInclusive\'\] The value \'(?<value>[^\']*)\' is less than the minimum value allowed \(\'(?<allowed>[^\']*)\'\)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' is less than the minimum value allowed (\'\'{allowed}\'\').', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1834, 'Element \'(?<element>[^\']*)\': \[facet \'maxInclusive\'\] The value \'(?<value>[^\']*)\' is greater than the maximum value allowed \(\'(?<allowed>[^\']*)\'\)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' is greater than the maximum value allowed (\'\'{allowed}\'\').', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1835, 'Element \'(?<element>[^\']*)\': \[facet \'minExclusive\'\] The value \'(?<value>[^\']*)\' must be greater than \'(?<allowed>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' must be greater than \'\'{allowed}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1836, 'Element \'(?<element>[^\']*)\': \[facet \'maxExclusive\'\] The value \'(?<value>[^\']*)\' must be less than \'(?<allowed>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' must be less than \'\'{allowed}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1837, 'Element \'(?<element>[^\']*)\': \[facet \'totalDigits\'\] The value \'(?<value>[^\']*)\' has more digits than are allowed \(\'(?<allowed>[^\']*)\'\)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' has more digits than are allowed (\'\'{allowed}\'\').', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1838, 'Element \'(?<element>[^\']*)\': \[facet \'fractionDigits\'\] The value \'(?<value>[^\']*)\' has more fractional digits than are allowed \(\'(?<allowed>[^\']*)\'\)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' has more fractional digits than are allowed (\'\'{allowed}\'\').', ['element' => $strip($g['element']), 'value' => $g['value'], 'allowed' => $g['allowed']], domain: 'errors'),
            $is(1839, 'Element \'(?<element>[^\']*)\': \[facet \'pattern\'\] The value \'(?<value>.*)\' is not accepted by the pattern \'(?<pattern>.*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' is not accepted by the pattern \'\'{pattern}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'pattern' => $g['pattern']], domain: 'errors'),
            $is(1840, 'Element \'(?<element>[^\']*)\': \[facet \'enumeration\'\] The value \'(?<value>.*)\' is not an element of the set (?<set>\{.*\})\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The value \'\'{value}\'\' is not an element of the set {set}.', ['element' => $strip($g['element']), 'value' => $g['value'], 'set' => $g['set']], domain: 'errors'),
            $is(1841, 'Element \'(?<element>[^\']*)\': Character content is not allowed, because the content type is empty\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Character content is not allowed, because the content type is empty.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1841, 'Element \'(?<element>[^\']*)\': Element content is not allowed, because the content type is empty\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Element content is not allowed, because the content type is empty.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1843, 'Element \'(?<element>[^\']*)\': Character content other than whitespace is not allowed because the content type is \'element-only\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Character content other than whitespace is not allowed because the content type is \'\'element-only\'\'.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1845, 'Element \'(?<element>[^\']*)\': No matching global declaration available for the validation root\.')
                => new TranslatableMessage('Element \'\'{element}\'\': No matching global declaration available for the validation root.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1847, 'Element \'(?<element>[^\']*)\': The element is not \'nillable\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The element is not \'\'nillable\'\'.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1848, 'Element \'(?<element>[^\']*)\': Neither character nor element content is allowed because the element is \'nilled\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Neither character nor element content is allowed because the element is \'\'nilled\'\'.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1858, 'Element \'(?<element>[^\']*)\': The actual value \'(?<value>.*)\' does not match the fixed value constraint \'(?<fixed>.*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The actual value \'\'{value}\'\' does not match the fixed value constraint \'\'{fixed}\'\'.', ['element' => $strip($g['element']), 'value' => $g['value'], 'fixed' => $g['fixed']], domain: 'errors'),
            $is(1866, 'Element \'(?<element>[^\']*)\', attribute \'(?<attribute>[^\']*)\': The attribute \'[^\']*\' is not allowed\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The attribute \'\'{attribute}\'\' is not allowed.', ['element' => $strip($g['element']), 'attribute' => $strip($g['attribute'])], domain: 'errors'),
            $is(1868, 'Element \'(?<element>[^\']*)\': The attribute \'(?<attribute>[^\']*)\' is required but missing\.')
                => new TranslatableMessage('Element \'\'{element}\'\': The attribute \'\'{attribute}\'\' is required but missing.', ['element' => $strip($g['element']), 'attribute' => $strip($g['attribute'])], domain: 'errors'),
            $is(1871, 'Element \'(?<element>[^\']*)\': Missing child element\(s\)\. Expected is one of \( (?<expected>.*) \)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Missing child element(s). Expected is one of ( {expected} ).', ['element' => $strip($g['element']), 'expected' => $strip($g['expected'])], domain: 'errors'),
            $is(1871, 'Element \'(?<element>[^\']*)\': Missing child element\(s\)\. Expected is \( (?<expected>.*) \)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Missing child element(s). Expected is ( {expected} ).', ['element' => $strip($g['element']), 'expected' => $strip($g['expected'])], domain: 'errors'),
            $is(1871, 'Element \'(?<element>[^\']*)\': This element is not expected\. Expected is one of \( (?<expected>.*) \)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': This element is not expected. Expected is one of ( {expected} ).', ['element' => $strip($g['element']), 'expected' => $strip($g['expected'])], domain: 'errors'),
            $is(1871, 'Element \'(?<element>[^\']*)\': This element is not expected\. Expected is \( (?<expected>.*) \)\.')
                => new TranslatableMessage('Element \'\'{element}\'\': This element is not expected. Expected is ( {expected} ).', ['element' => $strip($g['element']), 'expected' => $strip($g['expected'])], domain: 'errors'),
            $is(1871, 'Element \'(?<element>[^\']*)\': This element is not expected\.')
                => new TranslatableMessage('Element \'\'{element}\'\': This element is not expected.', ['element' => $strip($g['element'])], domain: 'errors'),
            $is(1874, 'Element \'(?<element>[^\']*)\', attribute \'(?<attribute>[^\']*)\': The value \'(?<value>.*)\' does not match the fixed value constraint \'(?<fixed>.*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\', attribute \'\'{attribute}\'\': The value \'\'{value}\'\' does not match the fixed value constraint \'\'{fixed}\'\'.', ['element' => $strip($g['element']), 'attribute' => $strip($g['attribute']), 'value' => $g['value'], 'fixed' => $g['fixed']], domain: 'errors'),
            $is(1877, 'Element \'(?<element>[^\']*)\': Duplicate key-sequence \[(?<keys>.*)\] in unique identity-constraint \'(?<constraint>[^\']*)\'\.')
                => new TranslatableMessage('Element \'\'{element}\'\': Duplicate key-sequence [{keys}] in unique identity-constraint \'\'{constraint}\'\'.', ['element' => $strip($g['element']), 'keys' => $g['keys'], 'constraint' => $g['constraint']], domain: 'errors'),
            default => null,
        };
    }
}
