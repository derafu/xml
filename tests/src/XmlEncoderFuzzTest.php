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

use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlError;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Properties of the encoder, checked with many arrays that are made at random
 * (with a seed, so a failure can be repeated: the message has the seed).
 *
 *   - What is encoded and decoded is what it was: the elements, the attributes,
 *     the lists, the text and the order.
 *   - What means nothing (`null`, `false` and `[]`) does not change the XML,
 *     wherever it is.
 *   - A number or `true` is the same as its text.
 */
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlHelper::class)]
#[UsesClass(XmlError::class)]
final class XmlEncoderFuzzTest extends TestCase
{
    private const ITERATIONS = 300;

    /**
     * Text that is not changed by the encoding, nor by the decoding: without
     * spaces around it, that the decoder removes.
     */
    private const TEXTS = [
        '', 'x', 'two words', 'ñ', '0', '1', '5', '12', '1.5', 'a & b', 'a < b', 'say "q"', "it's",
    ];

    /**
     * The values that are not text but are written as one text.
     */
    private const VARIANTS = [
        '0' => [0],
        '1' => [1, true],
        '5' => [5],
        '12' => [12],
        '1.5' => [1.5],
    ];

    /**
     * Makes the data in the form that the decoder gives, so it is what comes
     * back: the text of an element with attributes is in `@value`, and the
     * repeated elements are lists of two or more.
     *
     * @return string|array<string, mixed>
     */
    private function node(Randomizer $random, int $depth): string|array
    {
        if ($depth >= 3 || $random->getInt(0, 9) < 4) {
            return self::TEXTS[$random->getInt(0, count(self::TEXTS) - 1)];
        }

        $map = [];
        if ($random->getInt(0, 3) === 0) {
            $attributes = [];
            for ($i = 0, $n = $random->getInt(1, 3); $i < $n; $i++) {
                $attributes['k' . $i] = self::TEXTS[$random->getInt(0, count(self::TEXTS) - 1)];
            }
            $map['@attributes'] = $attributes;

            // An element with attributes has text or elements, not both: the
            // text is the value of the node, and it would replace the elements.
            if ($random->getInt(0, 1) === 0) {
                $map['@value'] = self::TEXTS[$random->getInt(1, count(self::TEXTS) - 1)];

                return $map;
            }
            if ($random->getInt(0, 2) === 0) {
                return $map;
            }
        }

        $names = $random->shuffleArray(['a', 'b', 'c', 'd']);
        for ($i = 0, $n = $random->getInt(1, 3); $i < $n; $i++) {
            if ($random->getInt(0, 4) < 3) {
                $map[$names[$i]] = $this->node($random, $depth + 1);

                continue;
            }
            $list = [];
            for ($j = 0, $m = $random->getInt(2, 3); $j < $m; $j++) {
                $list[] = $this->node($random, $depth + 1);
            }
            $map[$names[$i]] = $list;
        }

        return $map;
    }

    /**
     * Gives the same data, with a text that is a number or `true` in some places.
     *
     * @param mixed $data A text, or an array of them.
     * @return mixed The same, with some text that is a number or `true`.
     */
    private function withVariants(Randomizer $random, mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map(fn ($value) => $this->withVariants($random, $value), $data);
        }

        if (is_string($data) && isset(self::VARIANTS[$data]) && $random->getInt(0, 1) === 0) {
            $variants = self::VARIANTS[$data];

            return $variants[$random->getInt(0, count($variants) - 1)];
        }

        return $data;
    }

    /**
     * Gives the same data with values that mean nothing, in any place: in the
     * maps, in the lists and in the attributes.
     *
     * @param mixed $data A text, or an array of them.
     * @return mixed The same, with values that mean nothing.
     */
    private function withNothing(Randomizer $random, mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $nothing = [null, false, []];
        $result = [];
        $count = 0;
        foreach ($data as $key => $value) {
            if ($random->getInt(0, 2) === 0) {
                $result['nothing' . $count++] = $nothing[$random->getInt(0, 2)];
            }
            if ($key === '@attributes' && is_array($value)) {
                $value['nothing'] = $nothing[$random->getInt(0, 1)];
                $result[$key] = $value;
            } elseif (is_array($value) && array_is_list($value)) {
                $items = [];
                foreach ($value as $item) {
                    $items[] = $nothing[$random->getInt(0, 2)];
                    $items[] = $this->withNothing($random, $item);
                }
                $result[$key] = $items;
            } else {
                $result[$key] = $this->withNothing($random, $value);
            }
        }

        return $result;
    }

    private function seed(int $iteration): Randomizer
    {
        return new Randomizer(new Mt19937($iteration));
    }

    public function testWhatIsEncodedIsDecodedAsItWas(): void
    {
        for ($i = 1; $i <= self::ITERATIONS; $i++) {
            $data = ['root' => $this->node($this->seed($i), 0)];

            $encoded = (new XmlEncoder())->encode($data);
            $reloaded = new XmlDocument();
            $reloaded->loadXml($encoded->saveXml());

            $this->assertSame(
                $data,
                (new XmlDecoder())->decode($reloaded),
                sprintf('Seed %d, XML: %s', $i, $encoded->getXml())
            );
        }
    }

    public function testWhatMeansNothingDoesNotChangeTheXml(): void
    {
        for ($i = 1; $i <= self::ITERATIONS; $i++) {
            $data = ['root' => $this->node($this->seed($i), 0)];
            $withNothing = ['root' => $this->withNothing($this->seed($i + 1000), $data['root'])];

            $this->assertSame(
                (new XmlEncoder())->encode($data)->saveXml(),
                (new XmlEncoder())->encode($withNothing)->saveXml(),
                sprintf('Seed %d, data: %s', $i, json_encode($withNothing))
            );
        }
    }

    public function testANumberOrTrueIsTheSameAsItsText(): void
    {
        for ($i = 1; $i <= self::ITERATIONS; $i++) {
            $data = ['root' => $this->node($this->seed($i), 0)];
            $withVariants = ['root' => $this->withVariants($this->seed($i + 2000), $data['root'])];

            $this->assertSame(
                (new XmlEncoder())->encode($data)->saveXml(),
                (new XmlEncoder())->encode($withVariants)->saveXml(),
                sprintf('Seed %d, data: %s', $i, json_encode($withVariants))
            );
        }
    }
}
