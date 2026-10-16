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

use Derafu\Xml\Contract\XmlDecoderInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use DOMElement;
use DOMText;

/**
 * Class that creates a PHP array from an XML document.
 *
 * The value of an element is:
 *
 *   - A string, if it only has text: the text without the spaces around it.
 *     An element without content is the empty string, as it is in the data
 *     model of XML.
 *   - An array, if it has attributes, child elements, or text mixed with
 *     elements:
 *       - `@attributes`: its attributes, by name.
 *       - `@value`: its text, if it has any besides its other content. The
 *         parts of text that are separated by elements are joined with a space.
 *       - One key for each child element, by its name. The value is the one of
 *         the element, or a list of values if the element is repeated.
 */
final class XmlDecoder implements XmlDecoderInterface
{
    /**
     * {@inheritDoc}
     */
    public function decode(XmlDocumentInterface|DOMElement $documentElement): array
    {
        $tagElement = $documentElement instanceof DOMElement
            ? $documentElement
            : $documentElement->getDocumentElement()
        ;

        if ($tagElement === null) {
            return [];
        }

        return [$tagElement->tagName => $this->decodeElement($tagElement)];
    }

    /**
     * Decodes a DOM element, and what it has inside, to its value.
     *
     * @param DOMElement $element The element to decode.
     * @return string|array<string, mixed> The value of the element.
     */
    private function decodeElement(DOMElement $element): string|array
    {
        $hasAttributes = $element->hasAttributes();
        $result = [];

        // The attributes go first, in the special '@attributes' index.
        if ($hasAttributes) {
            $result['@attributes'] = [];
            foreach ($element->attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        // How many child elements there are with each name: the ones that are
        // repeated are a list.
        $counts = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $counts[$child->tagName] = ($counts[$child->tagName] ?? 0) + 1;
            }
        }

        $text = null;
        $lists = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMText) {
                $part = trim($child->textContent);
                if ($part === '') {
                    continue;
                }

                // An element that only has text is that text.
                if (!$hasAttributes && $element->childNodes->length === 1) {
                    return $part;
                }

                $text = $text === null ? $part : $text . ' ' . $part;
                $result['@value'] = $text;
            } elseif ($child instanceof DOMElement) {
                if ($counts[$child->tagName] === 1) {
                    $result[$child->tagName] = $this->decodeElement($child);

                    continue;
                }

                // The key is reserved here, in the order of the first one that
                // is found, and it gets its list at the end.
                $result[$child->tagName] = null;

                // A repeated element that has no elements nor attributes is
                // just its text.
                $lists[$child->tagName][] = $child->childElementCount === 0 && !$child->hasAttributes()
                    ? trim($child->textContent)
                    : $this->decodeElement($child)
                ;
            }
        }

        foreach ($lists as $name => $list) {
            $result[$name] = $list;
        }

        // An element without content is an empty text.
        return $result === [] ? '' : $result;
    }
}
