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
 * Exception thrown when an XPath query fails.
 *
 * Covers two cases:
 *   - The XPath expression is syntactically invalid.
 *   - The XPath expression points to a node that does not exist (when the
 *     node is required).
 */
class XmlQueryException extends XmlException
{
}
