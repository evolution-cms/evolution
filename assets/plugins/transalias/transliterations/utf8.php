<?php
/**
 * UTF-8 transliteration table
 */
return [
// File/path punctuation (usually not wanted, but might be wanted in some cases)
    '/' => '',
// Generally unwanted punctuation
    '!' => '', '(' => '', ')' => '', '*' => '', ',' => '', ':' => '', ';' => '', '…' => '', '¡' => '', '¿' => '', '%' => '',
// various quotation marks
    '‘' => '', '’' => '', '‚' => '', '‛' => '', '“' => '', '”' => '', '„' => '', '‟' => '', '«' => '', '»' => '', '‹' => '', '›' => '',
// replace various spaces with a regular space (or nothing for zero-width spaces)
    ' ' => ' ', // no-break space
    ' ' => ' ', // en quad
    ' ' => ' ', // em quad
    ' ' => ' ', // en space
    ' ' => ' ', // em space
    ' ' => ' ', // three-per-em space
    ' ' => ' ', // four-per-em space
    ' ' => ' ', // six-per-em space
    ' ' => ' ', // figure space
    ' ' => ' ', // punctuation space
    ' ' => ' ', // thin space
    ' ' => ' ', // narrow no-break space
    ' ' => ' ', // medium mathmatical space
    '　' => ' ', // ideographic space
    ' ' => '', // hair width space
    '​' => '', // zero-width space
    '﻿' => '', // zero-width no-break space
    '‍' => '', // zero-width joiner
    '‌' => '', // zero-width non-joiner
    '͏' => '', // combining grapheme joiner
    '⁠' => '', // word joiner
// replace various hyphens with a standard hyphen
    '­' => '-', // some other hyphen
    '‐' => '-', // hyphen (2010)
    '‑' => '-', // non-breaking hyphen
    '‒' => '-', // figure dash
    '–' => '-', // en dash
    '—' => '-', // em dash
    '―' => '-', // horizontal bar
// greek
    ';' => '', '΄' => '',
// armenian punctuation
    'ՙ' => '', '՚' => '', '՛' => '', '՜' => '', '՝' => '', '՞' => '', '՟' => '', '։' => '',
// hebrew punctuation
    '׀' => '', '׃' => '',
// arabic punctuation
    '،' => '', '؛' => '', '؟' => '', '۔' => '',
// hindi punctuation
    '।' => '', '॥' => '',
// cjk punctuation
    '，' => '', '、' => '', '。' => '', '〃' => '', '〈' => '', '〉' => '', '《' => '', '》' => '',
    '「' => '', '」' => '', '『' => '', '』' => '', '【' => '', '】' => '', '〔' => '', '〕' => '',
    '〖' => '', '〗' => '', '〘' => '', '〙' => '', '〚' => '', '〛' => '',
    '〝' => '', '〞' => '', '〟' => '', '〿' => ''
];
