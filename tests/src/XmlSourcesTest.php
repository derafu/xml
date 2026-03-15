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

use Closure;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlService::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlDocument::class)]
class XmlSourcesTest extends TestCase
{
    private XmlService $xmlService;

    protected function setUp(): void
    {
        $this->xmlService = new XmlService(
            new XmlEncoder(),
            new XmlDecoder(),
            new XmlValidator()
        );
    }

    public static function xmlSourcesDataProvider(): array
    {
        $testCases = [];

        $fixturesPath = __DIR__ . '/../fixtures/sources';
        $files = glob($fixturesPath . '/*/*.xml');

        foreach ($files as $xmlPath) {
            $name = basename($xmlPath, '.xml');
            $assertionsFile = $fixturesPath . '/' . $name . '.php';

            $source = basename(dirname($xmlPath));
            $testCaseName = $source . ':' . $name;
            $testCases[$testCaseName] = [
                'xmlContent' => file_get_contents($xmlPath),
                'assertions' => file_exists($assertionsFile)
                    ? require $assertionsFile
                    : null
                ,
            ];
        }

        return $testCases;
    }

    #[DataProvider('xmlSourcesDataProvider')]
    public function testXmlContent(string $xmlContent, ?Closure $assertions): void
    {
        $document = new XmlDocument();
        $document->loadXml($xmlContent);

        $decoded = $this->xmlService->decode($document);

        $this->assertNotEmpty($decoded, 'The decoded XML must not be empty.');

        if ($assertions !== null) {
            $assertions($this, $decoded);
        }
    }
}
