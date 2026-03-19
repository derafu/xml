<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

/**
 * Returns an XML string with a comprehensive set of ISO-8859-1 characters,
 * encoded as actual ISO-8859-1 bytes (not UTF-8, not XML entities).
 *
 * The content is defined here as UTF-8 string literals (the natural encoding
 * of PHP source files) and converted to ISO-8859-1 before being returned.
 *
 * Characters covered:
 *   - Lowercase accented vowels : á é í ó ú à è ì ò ù
 *   - Uppercase accented vowels : Á É Í Ó Ú À È Ì Ò Ù
 *   - Spanish ñ/Ñ
 *   - Umlaut vowels             : ü Ü ö Ö
 *   - Spanish punctuation       : ¿ ¡
 *   - Common symbols            : © ® ° ½ ¼ ¾
 *   - A representative sentence : Fabricación de Ñoños en Güemes
 *
 * @return string XML content encoded in ISO-8859-1.
 */

$xml = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n"
    . '<documento>' . "\n"
    . '  <vocales_min>á é í ó ú à è ì ò ù</vocales_min>' . "\n"
    . '  <vocales_may>Á É Í Ó Ú À È Ì Ò Ù</vocales_may>' . "\n"
    . '  <enie>ñ Ñ</enie>' . "\n"
    . '  <dieresis>ü Ü ö Ö</dieresis>' . "\n"
    . '  <puntuacion>¿Hola? ¡Mundo!</puntuacion>' . "\n"
    . '  <simbolos>© ® ° ½ ¼ ¾</simbolos>' . "\n"
    . '  <frase>Fabricación de Ñoños en Güemes</frase>' . "\n"
    . '</documento>' . "\n"
;

return mb_convert_encoding($xml, 'ISO-8859-1', 'UTF-8');
