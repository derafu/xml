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
use Derafu\Xml\Contract\XmlEncoderInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\Contract\XmlValidatorInterface;
use DOMElement;

/**
 * Service to work with XML documents.
 */
final class XmlService implements XmlServiceInterface
{
    public function __construct(
        private readonly XmlEncoderInterface $encoder,
        private readonly XmlDecoderInterface $decoder,
        private readonly XmlValidatorInterface $validator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): XmlDocumentInterface
    {
        return $this->encoder->encode($data);
    }

    /**
     * {@inheritDoc}
     */
    public function decode(
        XmlDocumentInterface|DOMElement $documentElement,
    ): array {
        return $this->decoder->decode($documentElement);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(
        XmlDocumentInterface $xml,
        ?string $schemaPath = null,
        array $translations = []
    ): void {
        $this->validator->validate($xml, $schemaPath, $translations);
    }
}
