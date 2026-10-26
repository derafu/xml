<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml;

use Derafu\Translation\TranslatorFactory;
use Derafu\Xml\Contract\XmlDecoderInterface;
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\Contract\XmlValidatorInterface;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\Translation\XmlTranslationResourceProvider;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Exercises the real configuration with XML conversion and schema validation.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function container(): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($root . '/resources/config'));
        $loader->load('xml-services.yaml');
        $this->assertArrayHasKey(
            XmlTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $container->register('app.translator', Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], new TaggedIteratorArgument('derafu_translation.resource_provider')]);
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        $container->compile();

        return $container;
    }

    public function testConfigurationConstructsEveryDeclaredService(): void
    {
        $container = $this->container();
        foreach ([
            XmlServiceInterface::class => XmlService::class,
            XmlEncoderInterface::class => XmlEncoder::class,
            XmlDecoderInterface::class => XmlDecoder::class,
            XmlValidatorInterface::class => XmlValidator::class,
            XmlTranslationResourceProvider::class => XmlTranslationResourceProvider::class,
        ] as $id => $class) {
            $this->assertInstanceOf($class, $container->get($id));
        }
        $provider = $container->get(XmlTranslationResourceProvider::class);
        $this->assertInstanceOf(XmlTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testConfiguredServicesRoundTripAndValidateXmlAgainstARealSchema(): void
    {
        $container = $this->container();
        $service = $container->get(XmlServiceInterface::class);
        $this->assertInstanceOf(XmlServiceInterface::class, $service);
        $encoder = $container->get(XmlEncoderInterface::class);
        $this->assertInstanceOf(XmlEncoderInterface::class, $encoder);
        $decoder = $container->get(XmlDecoderInterface::class);
        $this->assertInstanceOf(XmlDecoderInterface::class, $decoder);
        $validator = $container->get(XmlValidatorInterface::class);
        $this->assertInstanceOf(XmlValidatorInterface::class, $validator);
        $data = ['root' => [
            'pat' => 'ABC',
            'rng' => '5',
            'emp' => '',
            'elo' => ['c' => 'Árbol & texto <literal>'],
        ]];
        $document = $service->encode($data);
        $schema = dirname(__DIR__) . '/fixtures/errors.xsd';

        $service->validate($document, $schema);
        $validator->validate($document, $schema);
        $this->assertSame($data, $decoder->decode($document));
        $this->assertSame($data, $service->decode($encoder->encode($data)));
        $this->assertStringContainsString('&amp;', $document->saveXml());
        $this->assertStringContainsString('&lt;literal&gt;', $document->saveXml());
    }

    public function testTaggedResourcesTranslateARealSchemaValidationExceptionAndItsErrors(): void
    {
        $container = $this->container();
        $service = $container->get(XmlServiceInterface::class);
        $this->assertInstanceOf(XmlServiceInterface::class, $service);
        $translator = $container->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        $document = $service->encode(['root' => [
            'pat' => 'invalid',
            'emp' => '',
            'elo' => ['c' => 'value'],
        ]]);

        try {
            $service->validate($document, dirname(__DIR__) . '/fixtures/errors.xsd');
            $this->fail('A document that violates the schema was accepted.');
        } catch (XmlException $exception) {
            $this->assertSame($document, $exception->getXmlDocument());
            $this->assertNotEmpty($exception->getErrors());
            $message = $exception->trans($translator);
            $this->assertStringStartsWith('La validación del XML falló usando el esquema errors.xsd:', $message);
            $this->assertStringContainsString('no es válido según el patrón', $message);
        }
    }
}
