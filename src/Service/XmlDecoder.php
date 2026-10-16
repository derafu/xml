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
use Derafu\Xml\Exception\XmlException;
use DOMComment;
use DOMElement;
use DOMText;
use DOMXPath;

/**
 * Class that creates a PHP array from an XML document.
 *
 * The value of an element is:
 *
 *   - A string, if it only has text: the text as the XML has it, with its
 *     spaces. An element without content is the empty string, as it is in the
 *     data model of XML.
 *   - An array, if it has attributes, child elements, or text mixed with
 *     elements:
 *       - `@attributes`: its attributes, by name, and the namespaces that it
 *         declares (`xmlns` for the default one, `xmlns:prefix` for the others,
 *         sorted by name), so its names keep their namespaces when the array is
 *         encoded again.
 *       - `@value`: its text, if it has any besides its other content. It is
 *         the text as the XML has it, with its spaces. The parts of text that
 *         are separated by elements or comments are joined with a space,
 *         without the spaces around each part.
 *       - `@comment`: its comments, as they are written (a text, or a list of
 *         texts if there are several). Their place among the elements is not
 *         kept.
 *       - One key for each child element, by its name. The value is the one of
 *         the element, or a list of values if the element is repeated.
 *
 * What the array can not say, so it is not kept when it is encoded again:
 *
 *   - The order among child elements with different names when one of them is
 *     repeated: `<a/><b/><a/>` is `a` (a list of two) and `b`, so it is encoded
 *     as `<a/><a/><b/>`.
 *   - The place of the text and of the comments among the child elements: the
 *     text that is mixed with elements or comments is joined, and the comments
 *     of an element are all together.
 *   - What is outside of the document element (comments, processing
 *     instructions and the document type), the processing instructions of the
 *     elements, the declaration of the XML (version, encoding and standalone:
 *     the encoding is the one of the document that is written, see
 *     `XmlDocument::setEncoding()`) and the way that the text was written (the
 *     CDATA sections and the entities are the text that they are).
 */
final class XmlDecoder implements XmlDecoderInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws XmlException If the element does not belong to an XML document.
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

        $document = $tagElement->ownerDocument
            ?? throw new XmlException('The element does not belong to an XML document.')
        ;

        return [
            $tagElement->tagName => $this->decodeElement(
                $tagElement,
                new DOMXPath($document),
                []
            ),
        ];
    }

    /**
     * Decodes a DOM element, and what it has inside, to its value.
     *
     * @param DOMElement $element The element to decode.
     * @param DOMXPath $xpath XPath of the document of the element.
     * @param array<string, string> $parentNamespaces The namespaces that are in
     * scope in the parent of the element: the ones that the element declares
     * are the ones that are not there.
     * @return string|array<string, mixed> The value of the element.
     */
    private function decodeElement(
        DOMElement $element,
        DOMXPath $xpath,
        array $parentNamespaces
    ): string|array {
        $namespaces = $this->namespacesOf($element, $xpath);
        $declared = $this->declaredNamespaces($namespaces, $parentNamespaces);

        $hasAttributes = $element->hasAttributes() || $declared !== [];
        $result = [];

        // The attributes go first, in the special '@attributes' index.
        if ($hasAttributes) {
            $result['@attributes'] = $declared;
            foreach ($element->attributes as $attribute) {
                $result['@attributes'][$attribute->nodeName] = $attribute->value;
            }
        }

        // How many child elements there are with each name: the ones that are
        // repeated are a list.
        $counts = [];
        $hasComments = false;
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $counts[$child->tagName] = ($counts[$child->tagName] ?? 0) + 1;
            } elseif ($child instanceof DOMComment) {
                $hasComments = true;
            }
        }

        // The text is exactly the one of the XML, as the XML says it: what is
        // done with the spaces around it is for whoever uses it. The text
        // that is mixed with child elements or comments is the exception: it
        // is in parts, and the parts are joined with a space, so only the
        // spaces that separate them are not text.
        $mixed = $counts !== [] || $hasComments;
        $text = null;
        $comments = [];
        $lists = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMText) {
                $part = $mixed ? trim($child->textContent) : $child->textContent;
                if ($part === '') {
                    continue;
                }

                $text = $text === null ? $part : $text . ($mixed ? ' ' : '') . $part;
                $result['@value'] = $text;
            } elseif ($child instanceof DOMComment) {
                // The key is reserved here, in the order of the first one that
                // is found, and it gets its comments at the end.
                $result['@comment'] = null;
                $comments[] = $child->data;
            } elseif ($child instanceof DOMElement) {
                if ($counts[$child->tagName] === 1) {
                    $result[$child->tagName] = $this->decodeElement($child, $xpath, $namespaces);

                    continue;
                }

                // The key is reserved here, in the order of the first one that
                // is found, and it gets its list at the end.
                $result[$child->tagName] = null;

                // A repeated element that is only text is that text.
                $lists[$child->tagName][] = $this->decodeElement($child, $xpath, $namespaces);
            }
        }

        foreach ($lists as $name => $list) {
            $result[$name] = $list;
        }

        // One comment is a text, several are a list of texts, as the elements.
        if ($comments !== []) {
            $result['@comment'] = count($comments) === 1 ? $comments[0] : $comments;
        }

        // An element that is only text is that text.
        if ($text !== null && array_keys($result) === ['@value']) {
            return $text;
        }

        // An element without content is an empty text.
        return $result === [] ? '' : $result;
    }

    /**
     * Gets the namespaces that are in scope in an element: the ones that it
     * declares and the ones that it gets from its ancestors.
     *
     * @param DOMElement $element The element.
     * @param DOMXPath $xpath XPath of the document of the element.
     * @return array<string, string> The URI of each namespace, by the name of
     * its declaration (`xmlns` for the default one, `xmlns:prefix` for the
     * others).
     */
    private function namespacesOf(DOMElement $element, DOMXPath $xpath): array
    {
        $namespaces = [];
        $nodes = $xpath->query('namespace::*', $element);
        foreach ($nodes !== false ? $nodes : [] as $namespace) {
            // The one of the prefix `xml` is always in scope, it is not declared.
            if ($namespace->nodeName !== 'xmlns:xml') {
                $namespaces[$namespace->nodeName] = (string) $namespace->nodeValue;
            }
        }

        return $namespaces;
    }

    /**
     * Gets the namespaces that an element declares, by what it has that its
     * parent does not have.
     *
     * The default namespace that is left without URI (`xmlns=""`) is a
     * declaration too: without it the element would be in the default namespace
     * of its parent.
     *
     * @param array<string, string> $namespaces Namespaces in scope in the element.
     * @param array<string, string> $parentNamespaces Namespaces in scope in its parent.
     * @return array<string, string> The namespaces that it declares, sorted by
     * name.
     */
    private function declaredNamespaces(array $namespaces, array $parentNamespaces): array
    {
        $declared = array_diff_assoc($namespaces, $parentNamespaces);

        if (isset($parentNamespaces['xmlns']) && !isset($namespaces['xmlns'])) {
            $declared['xmlns'] = '';
        }

        ksort($declared);

        return $declared;
    }
}
