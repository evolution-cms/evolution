<?php
// step 1
$content = file_get_contents(dirname(__DIR__) . '/template/actions/language.tpl');
$content = parse($content, array_merge(ph($_lang, $moduleVersion, $evo_textdir ?? false, $evo_release_date),
    ['langOptions' => getLangOptions($install_language)]));
$content = parse($content, $_lang,'[%','%]');
echo $content;
