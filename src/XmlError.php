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

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\TranslatableMessage;
use JsonSerializable;
use LibXMLError;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * An error that libxml found when it loaded, canonicalized or validated XML.
 *
 * It keeps the data of the error of libxml, and it is translatable: as text it
 * says where the error is and what it is, explained with the translation of its
 * code when it is known, or with the original text of libxml when it is not.
 */
final readonly class XmlError implements TranslatableInterface, JsonSerializable
{
    /**
     * @param XmlErrorLevel $level Severity of the error.
     * @param int $code Code of the error in libxml.
     * @param int $line Line of the XML where libxml found it, `0` if it has none.
     * @param int $column Column of the line, `0` if it has none.
     * @param string $message Original text of libxml.
     */
    public function __construct(
        public XmlErrorLevel $level,
        public int $code,
        public int $line,
        public int $column,
        public string $message
    ) {
    }

    /**
     * Creates the error from the one of libxml.
     */
    public static function fromLibxml(LibXMLError $error): self
    {
        return new self(
            XmlErrorLevel::tryFrom($error->level) ?? XmlErrorLevel::Error,
            $error->code,
            $error->line,
            $error->column,
            $error->message
        );
    }

    /**
     * Creates the errors from the ones of libxml, in the same order.
     *
     * @param array<int, LibXMLError> $errors Errors of libxml.
     * @return list<self>
     */
    public static function fromLibxmlErrors(array $errors): array
    {
        return array_values(array_map(self::fromLibxml(...), $errors));
    }

    /**
     * Gets the error that tells what happened: the first fatal one, or else
     * the first error, or else the first of any level.
     *
     * After the first fatal error libxml keeps on finding errors that are
     * consequences of it, and the errors of namespaces or the warnings can come
     * before it.
     *
     * @param list<self> $errors
     */
    public static function main(array $errors): ?self
    {
        foreach ([XmlErrorLevel::Fatal, XmlErrorLevel::Error] as $level) {
            foreach ($errors as $error) {
                if ($error->level === $level) {
                    return $error;
                }
            }
        }

        return $errors[0] ?? null;
    }

    /**
     * The error as a translatable message.
     */
    public function toMessage(): TranslatableMessage
    {
        $detail = XmlErrorExplainer::explain($this) ?? trim($this->message);
        $parameters = [
            'line' => $this->line,
            'column' => $this->column,
            'message' => $detail,
        ];
        $warning = $this->level === XmlErrorLevel::Warning;

        return match (true) {
            $warning && $this->line > 0 => new TranslatableMessage(
                'Warning, line {line}, column {column}: {message}',
                $parameters,
                domain: 'errors'
            ),
            $warning => new TranslatableMessage(
                'Warning: {message}',
                $parameters,
                domain: 'errors'
            ),
            $this->line > 0 => new TranslatableMessage(
                'Line {line}, column {column}: {message}',
                $parameters,
                domain: 'errors'
            ),
            default => new TranslatableMessage(
                '{message}',
                $parameters,
                domain: 'errors'
            ),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->toMessage()->trans($translator, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return (string) $this->toMessage();
    }

    /**
     * {@inheritDoc}
     *
     * @return array{level: string, code: int, line: int, column: int, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level->name,
            'code' => $this->code,
            'line' => $this->line,
            'column' => $this->column,
            'message' => trim($this->message),
        ];
    }
}
