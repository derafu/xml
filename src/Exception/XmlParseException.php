<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Exception;

/**
 * Exception thrown when an XML string cannot be parsed.
 *
 * Covers two cases:
 *   - The XML string is empty.
 *   - The XML string is malformed (libxml parse error).
 */
class XmlParseException extends XmlException
{
}
