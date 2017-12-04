## Lexicons

To use lexicons you should create a file named "lexicon name.inc.php" in a folder named as full language name (russian-UTF8, english etc.):
```
<?php
if (!defined('MODX_BASE_PATH')) {die();})
$_lang = array();
$_lang['key'] = 'Value.';
return $lang;
?>
```
Parameters to load lexicons are:

* langDir - lexicon folder path;
* lang - lexicon language (the "manager_language" configuration parameter value by default);
* lexicon - lexicon names, comma separated. Or specify an array of values right here:
```
&lexicon=`{
    "english":{
        "test":"Test lexicon value",
        "foo":"Another lexicon value",
        "bar":"And one more value"
    },
    "russian-UTF8":{
        "test":"Проверка",
        "foo":"Еще проверка",
        "bar":"И еще"
    }
}`
```

After that you can use the [%key%] placeholders to output lexicon entries. EvoBabel lexicons are supported as well.
