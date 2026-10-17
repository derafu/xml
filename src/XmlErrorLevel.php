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

/**
 * Severity of an error of libxml.
 */
enum XmlErrorLevel: int
{
    case Warning = LIBXML_ERR_WARNING;
    case Error = LIBXML_ERR_ERROR;
    case Fatal = LIBXML_ERR_FATAL;
}
