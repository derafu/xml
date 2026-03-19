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
 * Exception thrown when a PHP array cannot be encoded into XML.
 *
 * Covers two cases:
 *   - An attribute value is an array (attributes must be scalar).
 *   - A child node contains a non-associative array where an associative
 *     one is required.
 */
class XmlEncoderException extends XmlException
{
}
