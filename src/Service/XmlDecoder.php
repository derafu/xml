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
use DOMNodeList;
use DOMText;

/**
 * Class that creates a PHP array from an XML document.
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

        $data = [$tagElement->tagName => null];

        $this->decodeNode($tagElement, $data, false);

        return $data;
    }

    /**
     * Recursively decodes a DOM element into a PHP array.
     *
     * @param DOMElement $tagElement The element to decode.
     * @param array &$data The array being populated.
     * @param bool $twinsAsArray Whether to insert children at the current level
     * instead of nesting them under the element key (used internally when
     * processing items inside a list of twin nodes).
     * @return void
     */
    private function decodeNode(
        DOMElement $tagElement,
        array|null &$data,
        bool $twinsAsArray,
    ): void {
        $key = $tagElement->tagName;

        if ($data === null) {
            $data = [$key => null];
        }

        // If the tagElement has attributes, add them to the array within the
        // special '@attributes' index.
        if ($tagElement->hasAttributes()) {
            $data[$key]['@attributes'] = [];
            foreach ($tagElement->attributes as $attribute) {
                $data[$key]['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        // If the tagElement has child nodes, add them to the value of the tag.
        if ($tagElement->hasChildNodes()) {
            $this->arrayAddChilds(
                $data,
                $tagElement,
                $tagElement->childNodes,
                $twinsAsArray
            );
        }
    }

    /**
     * Adds child nodes of an XML document to a PHP array.
     *
     * @param array &$data Array where the child nodes will be added.
     * @param DOMElement $tagElement Parent node from which the child nodes will
     * be extracted.
     * @param DOMNodeList $childs List of child nodes of the parent node.
     * @param bool $twinsAsArray Indicates if the twin nodes should be treated
     * as an array.
     * @return void
     */
    private function arrayAddChilds(
        array &$data,
        DOMElement $tagElement,
        DOMNodeList $childs,
        bool $twinsAsArray,
    ): void {
        $key = $tagElement->tagName;
        // Loop through each of the child nodes.
        foreach ($childs as $child) {
            if ($child instanceof DOMText) {
                $textContent = trim($child->textContent);
                if ($textContent !== '') {
                    if ($tagElement->hasAttributes()) {
                        $data[$key]['@value'] = $textContent;
                    } elseif ($childs->length === 1 && empty($data[$key])) {
                        $data[$key] = $textContent;
                    } else {
                        $data[$key]['@value'] = $textContent;
                    }
                }
            } elseif ($child instanceof DOMElement) {
                $n_twinsNodes = $this->nodeCountTwins(
                    $tagElement,
                    $child->tagName
                );
                if ($n_twinsNodes === 1) {
                    if ($twinsAsArray) {
                        $this->decodeNode($child, $data, false);
                    } else {
                        $this->decodeNode($child, $data[$key], false);
                    }
                } else {
                    // Create a list for the child node, because it has several
                    // equal nodes in the XML.
                    if (!isset($data[$key][$child->tagName])) {
                        $data[$key][$child->tagName] = [];
                    }

                    // Check if the child node is scalar. If it is, add it
                    // directly to the list.
                    if ($child->childElementCount === 0) {
                        $textContent = trim($child->textContent);
                        $data[$key][$child->tagName][] = $textContent;
                    }
                    // If the child node is not scalar, it is built as if it
                    // were a normal array with the recursive call to decodeNode().
                    else {
                        $nextIndex = count($data[$key][$child->tagName]);
                        $data[$key][$child->tagName][$nextIndex] = [];
                        $this->decodeNode(
                            $child,
                            $data[$key][$child->tagName][$nextIndex],
                            true
                        );
                    }
                }
            }
        }
    }

    /**
     * Counts the nodes with the same name as the child nodes of a DOMElement.
     *
     * @param DOMElement $dom Element where the nodes will be searched.
     * @param string $tagName Name of the tag to count.
     * @return int Quantity of nodes with the same name.
     */
    private function nodeCountTwins(DOMElement $dom, string $tagName): int
    {
        $twins = 0;
        foreach ($dom->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tagName) {
                $twins++;
            }
        }

        return $twins;
    }
}
