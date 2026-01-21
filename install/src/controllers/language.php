<?php
// step 1
$content = file_get_contents(dirname(__DIR__) . '/template/actions/language.tpl');
$content = parse($content, array_merge(ph(), ['langOptions' => getLangOptions($install_language)]));
$content = parse($content, $_lang,'[%','%]');
echo $content;
