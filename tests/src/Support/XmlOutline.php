<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml\Support;

use DOMComment;
use DOMElement;
use DOMText;

/**
 * What an element has, to compare it with another one by what it has and not by
 * how it is written: its name and its namespace, its attributes, its text, its
 * comments and its child elements in order.
 *
 * The text is just as it is in an element that only has text. In one that has
 * elements or comments the text is in parts, so the spaces around them are not
 * compared.
 */
final readonly class XmlOutline
{
    /**
     * @param array<string, string> $attributes The value of each attribute, by its name and its namespace.
     * @param list<string> $comments
     * @param list<self> $children
     */
    private function __construct(
        public string $name,
        public ?string $namespace,
        public array $attributes,
        public string $text,
        public array $comments,
        public array $children
    ) {
    }

    public static function of(DOMElement $element): self
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[$attribute->nodeName . ' {' . $attribute->namespaceURI . '}'] = $attribute->value;
        }

        $text = '';
        $comments = [];
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMText) {
                $text .= $child->textContent;
            } elseif ($child instanceof DOMComment) {
                $comments[] = $child->data;
            } elseif ($child instanceof DOMElement) {
                $children[] = self::of($child);
            }
        }

        return new self(
            $element->nodeName,
            $element->namespaceURI,
            $attributes,
            $children !== [] || $comments !== [] ? trim($text) : $text,
            $comments,
            $children
        );
    }

    /**
     * The differences with what another element has, with the place of each.
     *
     * @return list<string> What the other has that is not what this has. Empty
     * if they have the same.
     */
    public function differencesWith(self $other, string $path = ''): array
    {
        $path .= '/' . $this->name;
        $differences = [];

        $compared = [
            'name' => [$this->name, $other->name],
            'namespace' => [$this->namespace, $other->namespace],
            'attributes' => [$this->attributes, $other->attributes],
            'text' => [$this->text, $other->text],
            'comments' => [$this->comments, $other->comments],
        ];
        foreach ($compared as $what => [$original, $result]) {
            if ($original !== $result) {
                $differences[] = sprintf(
                    '%s: %s is %s, and it was %s',
                    $path,
                    $what,
                    json_encode($result, JSON_UNESCAPED_UNICODE),
                    json_encode($original, JSON_UNESCAPED_UNICODE)
                );
            }
        }

        $names = fn (self $outline) => array_map(fn (self $child) => $child->name, $outline->children);
        if ($names($this) !== $names($other)) {
            return [...$differences, sprintf(
                '%s: the child elements are %s, and they were %s',
                $path,
                json_encode($names($other)),
                json_encode($names($this))
            )];
        }

        foreach ($this->children as $i => $child) {
            $differences = [...$differences, ...$child->differencesWith($other->children[$i], $path . '[' . $i . ']')];
        }

        return $differences;
    }
}
