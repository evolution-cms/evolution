<?php
if(!function_exists('get_lang_keys')) {
    /**
     * get_lang_keys
     *
     * @param string $filename
     * @return array of keys from a language file
     */
    function get_lang_keys($filename)
    {
        $file = MODX_MANAGER_PATH . 'includes/lang' . DIRECTORY_SEPARATOR . $filename;
        if (is_file($file) && is_readable($file)) {
            include($file);
            $out = isset($_lang) ? array_keys($_lang) : array();
        } else {
            $out = array();
        }

        return $out;
    }
}

if(!function_exists('get_langs_by_key')) {
    /**
     * get_langs_by_key
     *
     * @param string $key
     * @return array of languages that define the key in their file
     */
    function get_langs_by_key($key)
    {
        global $lang_keys;
        $lang_return = array();
        foreach ($lang_keys as $lang => $keys) {
            if (in_array($key, $keys)) {
                $lang_return[] = $lang;
            }
        }

        return $lang_return;
    }
}

if(!function_exists('get_lang_options')) {
    /**
     * get_lang_options
     *
     * returns html option list of languages
     *
     * @param string $key specify language key to return options of langauges that override it, default return all languages
     * @param string $selected_lang specify language to select in option list, default none
     * @return string html option list
     */
    function get_lang_options($key = '', $selected_lang = '')
    {
        global $lang_keys, $_lang;
        $lang_options = '';
        if (!empty($key)) {
            $languages = get_langs_by_key($key);
            sort($languages);
            $lang_options .= '<option value="">' . $_lang['language_title'] . '</option>';

            foreach ($languages as $language_name) {
                $uclanguage_name = ucwords(str_replace("_", " ", $language_name));
                $lang_options .= '<option value="' . $language_name . '">' . $uclanguage_name . '</option>';
            }

            return $lang_options;
        } else {
            $languages = array_keys($lang_keys);
            sort($languages);
            foreach ($languages as $language_name) {
                $uclanguage_name = ucwords(str_replace("_", " ", $language_name));
                $sel = $language_name === $selected_lang ? ' selected="selected"' : '';
                $lang_options .= '<option value="' . $language_name . '" ' . $sel . '>' . $uclanguage_name . '</option>';
            }

            return $lang_options;
        }
    }
}

if(!function_exists('form_radio')) {
    /**
     * @param string $name
     * @param string $value
     * @param string $add
     * @param bool $disabled
     * @return string
     */
    function form_radio($name, $value, $add = '', $disabled = false)
    {
        global ${$name};
        $var = ${$name};
        $checked = ($var == $value) ? ' checked="checked"' : '';
        if ($disabled) {
            $disabled = ' disabled';
        } else {
            $disabled = '';
        }
        if ($add) {
            $add = ' ' . $add;
        }

        return sprintf('<input onchange="documentDirty=true;" type="radio" name="%s" value="%s" %s %s %s />', $name,
            $value,
            $checked, $disabled, $add);
    }
}

if(!function_exists('wrap_label')) {
    /**
     * @param string $str
     * @param string $object
     * @return string
     */
    function wrap_label($str = '', $object)
    {
        return "<label>{$object}\n{$str}</label>";
    }

}

if(!function_exists('parseText')) {
    /**
     * @param string $tpl
     * @param array $ph
     * @return string
     */
    function parseText($tpl = '', $ph = array())
    {
        if (empty($ph) || empty($tpl)) {
            return $tpl;
        }

        foreach ($ph as $k => $v) {
            $k = "[+{$k}+]";
            $tpl = str_replace($k, $v, $tpl);
        }

        return $tpl;
    }
}

if(!function_exists('showHide')) {
    /**
     * @param bool $cond
     * @return string
     */
    function showHide($cond = true)
    {
        global $displayStyle;
        $showHide = $cond ? $displayStyle : 'none';

        return sprintf('style="display:%s"', $showHide);
    }
}
