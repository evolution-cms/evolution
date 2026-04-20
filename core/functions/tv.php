<?php

if (!function_exists('ProcessTVCommand')) {
    /**
     * @param string $value
     * @param string $name
     * @param string $docid
     * @param string $src
     * @param array $tvsArray
     * @return string
     */
    function ProcessTVCommand($value, $name = '', $docid = '', $src = 'docform', $tvsArray = [])
    {
        $modx = evo();
        $docid = (int)$docid;
        if (!$docid) {
            $docid = $modx->documentIdentifier;
        }
        $nvalue = trim($value);
        if (strpos($nvalue, '@') !== 0) {
            return $value;
        }

        if (isset($modx->config['enable_bindings']) && $modx->config['enable_bindings'] != 1 && $src === 'docform') {
            return '@Bindings is disabled.';
        }

        list ($cmd, $param) = ParseCommand($nvalue);
        $cmd = trim($cmd);
        $param = parseTvValues($param, $tvsArray);
        switch ($cmd) {
            case 'FILE' :
                $output = $modx->atBindFileContent($nvalue);
                break;

            case 'CHUNK' : // retrieve a chunk and process it's content
                $chunk = $modx->getChunk(trim($param));
                $output = $chunk;
                break;

            case 'DOCUMENT' : // retrieve a document and process it's content
                $rs = $modx->getDocument($param);
                if (is_array($rs)) {
                    $output = $rs['content'];
                } else {
                    $output = 'Unable to locate document ' . $param;
                }
                break;

            case 'SELECT' : // selects a record from the cms database
                $rt = [];
                $replacementVars = [
                    'DBASE' => $modx->getDatabase()->getConfig('database'),
                    'PREFIX' => $modx->getDatabase()->getConfig('prefix')
                ];
                foreach ($replacementVars as $rvKey => $rvValue) {
                    $modx->setPlaceholder($rvKey, $rvValue);
                }
                $param = $modx->mergePlaceholderContent($param);
                $rs = $modx->getDatabase()->query('SELECT ' . $param . ';');
                $output = $rs;
                break;

            case 'EVAL' : // evaluates text as php codes return the results
                $output = eval ($param);
                break;

            case 'INHERIT' :
                $output = $param; // Default to param value if no content from parents
                $doc = $modx->getPageInfo($docid, 0, 'id,parent', false);

                while ($doc['parent'] != 0) {
                    $parent_id = $doc['parent'];

                    // Grab document regardless of publish status
                    $doc = $modx->getPageInfo($parent_id, 0, 'id,parent,published', false);
                    if ($doc['parent'] != 0 && !$doc['published']) {
                        continue;
                    } // hide unpublished docs if we're not at the top

                    $tv = $modx->getTemplateVar($name, '*', $doc['id'], $doc['published'], false);

                    // if an inherited value is found and if there is content following the @INHERIT binding
                    // remove @INHERIT and output that following content. This content could contain other
                    // @ bindings, that are processed in the next step
                    if (!empty($tv) && (string)$tv['value'] !== '' && !preg_match('%^@INHERIT[\s\n\r]*$%im', $tv['value'])) {
                        $output = trim(str_replace('@INHERIT', '', (string)$tv['value']));
                        break 2;
                    }
                }
                break;

            case 'DIRECTORY' :
                $files = [];
                $path = rtrim(EVO_BASE_PATH . $param, '/') . '/';
                if (!is_dir(rtrim($path, '/'))) {
                    die($path);
                    break;
                }
                $dir = dir($path);
                while (($file = $dir->read()) !== false) {
                    if (strpos($file, '.') !== 0) {
                        $files[] = $file . '==' . $param . $file;
                    }
                }
                asort($files);
                $output = implode('||', $files);
                break;

            default :
                $output = $value;
                break;

        }

        // support for nested bindings
        if (is_string($output) && ($output != $value)) {
            return ProcessTVCommand($output, $name, $docid, $src,
                $tvsArray);
        }

        return $output;
    }
}

if (!function_exists('ProcessFile')) {
    /**
     * @param $file
     * @return string
     */
    function ProcessFile($file)
    {
        // get the file
        $buffer = @file_get_contents($file);
        if ($buffer === false) {
            return " Could not retrieve document '" . $file . "'.";
        }

        return $buffer;
    }
}

if (!function_exists('ParseCommand')) {
    /**
     * ParseCommand - separate @ cmd from params
     *
     * @param string $binding_string
     * @return array
     */
    function ParseCommand($binding_string)
    {
        $BINDINGS = [ // Array of supported bindings. must be upper case
            'FILE',
            'CHUNK',
            'DOCUMENT',
            'SELECT',
            'EVAL',
            'INHERIT',
            'DIRECTORY'
        ];

        $binding_array = [];
        foreach ($BINDINGS as $cmd) {
            if (strpos($binding_string, '@' . $cmd) === 0) {
                $code = substr($binding_string, strlen($cmd) + 1);
                $binding_array = [$cmd, trim($code)];
                break;
            }
        }

        return $binding_array;
    }
}

if (!function_exists('parseTvValues')) {
    /**
     * Parse Evolution CMS Template-Variables
     *
     * @param string $param
     * @param array $tvsArray
     * @return mixed
     */
    function parseTvValues($param, $tvsArray)
    {
        if (strpos($param, '[*') === false) {
            return $param;
        }

        $modx = evo();
        if (is_array($modx->documentObject)) {
            $tvsArray = array_merge($tvsArray, $modx->documentObject);
        }
        $matches = $modx->getTagsFromContent($param, '[*', '*]');
        foreach ($matches[0] as $i => $match) {
            if (!isset($tvsArray[$matches[1][$i]])) {
                continue;
            }
            if (is_array($tvsArray[$matches[1][$i]])) {
                if ($tvsArray[$matches[1][$i]]['value'] === '') {
                    $value = $tvsArray[$matches[1][$i]]['default_text'];
                } else {
                    $value = $tvsArray[$matches[1][$i]]['value'];
                }
            } else {
                $value = $tvsArray[$matches[1][$i]];
            }
            $param = str_replace($match, $value, $param);
        }
        return $param;
    }
}

if (!function_exists('getTVDisplayFormat')) {
    /**
     * @param string $name
     * @param string $value
     * @param string $format
     * @param string $paramstring
     * @param string $tvtype
     * @param string $docid
     * @param string $sep
     * @return mixed|string
     */
    function getTVDisplayFormat($name, $value, $format, $paramstring = '', $tvtype = '', $docid = '', $sep = '')
    {

        $modx = evo();
        $o = '';

        // process any TV commands in value
        $docid = (int)$docid;
        if (!$docid) {
            $docid = $modx->documentIdentifier;
        }
        $value = ProcessTVCommand($value, $name, $docid);

        $params = [];
        if ($paramstring) {
            $cp = explode('&', $paramstring);
            foreach ($cp as $p => $v) {
                $v = trim($v); // trim
                $ar = explode('=', $v);
                if (is_array($ar) && count($ar) == 2) {
                    $params[$ar[0]] = decodeParamValue($ar[1]);
                }
            }
        }

        $id = 'tv' . $name;
        switch ($format) {
            case 'image':
                $images = parseInput($value, '||', 'array');
                foreach ($images as $image) {
                    if (!is_array($image)) {
                        $image = explode('==', $image);
                    }
                    $src = $image[0];

                    if ($src) {
                        // We have a valid source
                        $attributes = '';
                        $attr = [
                            'class' => $params['class'],
                            'src' => $src,
                            'id' => ($params['id'] ? $params['id'] : ''),
                            'alt' => $modx->getPhpCompat()->htmlspecialchars($params['alttext']),
                            'style' => $params['style']
                        ];
                        if (isset($params['align']) && $params['align'] !== 'none') {
                            $attr['align'] = $params['align'];
                        }
                        foreach ($attr as $k => $v) {
                            $attributes .= ($v ? ' ' . $k . '="' . $v . '"' : '');
                        }
                        $attributes .= ' ' . $params['attrib'];

                        // Output the image with attributes
                        $o .= '<img' . rtrim($attributes) . ' />';
                    }
                }
                break;

            case 'delim':    // display as delimitted list
                $value = parseInput($value);
                $p = $params['format'] ? $params['format'] : ' ';
                if ($p === "\\n") {
                    $p = "\n";
                }
                $o = str_replace('||', $p, $value);
                break;

            case 'string':
                $value = parseInput($value);
                $format = strtolower($params['format']);
                if ($format === 'upper case') {
                    $o = strtoupper($value);
                } else {
                    if ($format === 'lower case') {
                        $o = strtolower($value);
                    } else {
                        if ($format === 'sentence case') {
                            $o = ucfirst($value);
                        } else {
                            if ($format === 'capitalize') {
                                $o = ucwords($value);
                            } else {
                                $o = $value;
                            }
                        }
                    }
                }
                break;

            case 'date':
                if ($value != '' || $params['default'] === 'Yes') {
                    if (empty($value)) {
                        $value = 'now';
                    }
                    $timestamp = getUnixtimeFromDateString($value);
                    $p = $params['format'] ? $params['format'] : '%A %d, %B %Y';
                    $o = strftime($p, $timestamp);
                } else {
                    $value = '';
                }
                break;

            case 'hyperlink':
                $value = parseInput($value, '||', 'array');
                $o = '';
                $countValue = count($value);
                for ($i = 0; $i < $countValue; $i++) {
                    list($name, $url) = is_array($value[$i]) ? $value[$i] : explode('==', $value[$i]);
                    if (!$url) {
                        $url = $name;
                    }
                    if ($url) {
                        if ($o) {
                            $o .= '<br />';
                        }
                        $attributes = '';
                        // setup the link attributes
                        $attr = [
                            'href' => $url,
                            'title' => $params['title'] ? $modx->getPhpCompat()->htmlspecialchars($params['title']) : $name,
                            'class' => $params['class'],
                            'style' => $params['style'],
                            'target' => $params['target'],
                        ];
                        foreach ($attr as $k => $v) {
                            $attributes .= ($v ? ' ' . $k . '="' . $v . '"' : '');
                        }
                        $attributes .= ' ' . $params['attrib']; // add extra

                        // Output the link
                        $o .= '<a' . rtrim($attributes) . '>' . ($params['text'] ? $modx->getPhpCompat()->htmlspecialchars($params['text']) : $name) . '</a>';
                    }
                }
                break;

            case 'htmltag':
                $value = parseInput($value, '||', 'array');
                $tagid = $params['tagid'];
                $tagname = ($params['tagname']) ? $params['tagname'] : 'div';
                $o = '';
                // Loop through a list of tags
                $countValue = count($value);
                for ($i = 0; $i < $countValue; $i++) {
                    $tagvalue = is_array($value[$i]) ? implode(' ', $value[$i]) : $value[$i];
                    if (!$tagvalue) {
                        continue;
                    }

                    $attributes = '';
                    $attr = [
                        'id' => ($tagid ? $tagid : $id),
                        // 'tv' already added to id
                        'class' => $params['class'],
                        'style' => $params['style'],
                    ];
                    foreach ($attr as $k => $v) {
                        $attributes .= ($v ? ' ' . $k . '="' . $v . '"' : '');
                    }
                    $attributes .= ' ' . $params['attrib']; // add extra

                    // Output the HTML Tag
                    $o .= '<' . $tagname . rtrim($attributes) . '>' . $tagvalue . '</' . $tagname . '>';
                }
                break;

            case 'richtext':
                $value = parseInput($value);
                $w = $params['w'] ? $params['w'] : '100%';
                $h = $params['h'] ? $params['h'] : '400px';
                $richtexteditor = $params['edt'] ? $params['edt'] : '';
                $o = '<div class="EVO_RichTextWidget"><textarea id="' . $id . '" name="' . $id . '" style="width:' . $w . '; height:' . $h . ';">';

                $o .= $modx->getPhpCompat()->htmlspecialchars($value);
                $o .= '</textarea></div>';
                $replace_richtext = [$id];
                // setup editors
                if (!empty($replace_richtext) && !empty($richtexteditor)) {
                    // invoke OnRichTextEditorInit event
                    $evtOut = $modx->invokeEvent('OnRichTextEditorInit', [
                        'editor' => $richtexteditor,
                        'elements' => $replace_richtext,
                        'forfrontend' => 1,
                        'width' => $w,
                        'height' => $h
                    ]);
                    if (is_array($evtOut)) {
                        $o .= implode('', $evtOut);
                    }
                }
                break;

            case 'unixtime':
                $value = parseInput($value);
                $o = getUnixtimeFromDateString($value);
                break;

            case 'viewport':
                $value = parseInput($value);
                $id = '_' . time();
                if (!$params['vpid']) {
                    $params['vpid'] = $id;
                }

                if ($params['stretch'] === 'Yes') {
                    $params['width'] = '100%';
                    $params['height'] = '100%';
                }
                $autoMode = '0';
                if ($params['asize'] === 'Yes') {
                    $autoMode = '3';  //both
                } elseif ($params['awidth'] === 'Yes' && $params['aheight'] === 'Yes') {
                    $autoMode = '3';  //both
                } else {
                    if ($params['awidth'] === 'Yes') {
                        $autoMode = '1'; //width only
                    } elseif ($params['aheight'] === 'Yes') {
                        $autoMode = '2';    //height only
                    }
                }

                $modx->regClientStartupScript(EVO_MANAGER_URL . 'media/script/bin/viewport.js'
                    , [
                        'name' => 'viewport',
                        'version' => '0',
                        'plaintext' => false
                    ]
                );
                $o = '<iframe id="' . $params['vpid'] . '" name="' . $params['vpid'] . '" ';
                if ($params['class']) {
                    $o .= ' class="' . $params['class'] . '" ';
                }
                if ($params['style']) {
                    $o .= ' style="' . $params['style'] . '" ';
                }
                if ($params['attrib']) {
                    $o .= $params['attrib'] . ' ';
                }
                if ($params['sbar'] === 'No') {
                    $o .= 'scrolling="no" ';
                } elseif ($params['sbar'] === 'Yes') {
                    $o .= 'scrolling="yes" ';
                } else {
                    $o .= 'scrolling="auto" ';
                }
                $o .= 'src="' . $value . '" frameborder="' . $params['borsize'] . '" ';
                $o .= 'onload="window.setTimeout(\'ResizeViewPort(\\\'' . $params['vpid'] . '\\\',' . $autoMode . ')\',100);" width="' . $params['width'] . '" height="' . $params['height'] . '" >';
                $o .= '</iframe>';
                break;

            case 'datagrid':
                $grd = new \EvolutionCMS\Support\DataGrid('', $value);

                $grd->noRecordMsg = $params['egmsg'];

                $grd->columnHeaderClass = $params['chdrc'];
                $grd->cssClass = $params['tblc'];
                $grd->itemClass = $params['itmc'];
                $grd->altItemClass = $params['aitmc'];

                $grd->columnHeaderStyle = $params['chdrs'];
                $grd->cssStyle = $params['tbls'];
                $grd->itemStyle = $params['itms'];
                $grd->altItemStyle = $params['aitms'];

                $grd->columns = $params['cols'];
                $grd->fields = $params['flds'];
                $grd->colWidths = $params['cwidth'];
                $grd->colAligns = $params['calign'];
                $grd->colColors = $params['ccolor'];
                $grd->colTypes = $params['ctype'];

                $grd->cellPadding = $params['cpad'];
                $grd->cellSpacing = $params['cspace'];
                $grd->header = $params['head'];
                $grd->footer = $params['foot'];
                $grd->pageSize = $params['psize'];
                $grd->pagerLocation = $params['ploc'];
                $grd->pagerClass = $params['pclass'];
                $grd->pagerStyle = $params['pstyle'];
                $o = $grd->render();
                break;

            case 'htmlentities':
                $value = parseInput($value);
                if ($tvtype === 'checkbox' || $tvtype === 'listbox-multiple') {
                    // remove delimiter from checkbox and listbox-multiple TVs
                    $value = str_replace('||', '', $value);
                }
                $o = htmlentities($value, ENT_NOQUOTES, $modx->getConfig('modx_charset'));
                break;

            case 'custom_widget':
                $widget_output = '';
                /* If we are loading a file */
                if (strpos($params['output'], '@FILE') === 0) {
                    $file_name = EVO_BASE_PATH . trim(substr($params['output'], 6));
                    if (!is_file($file_name)) {
                        $widget_output = $file_name . ' does not exist';
                    } else {
                        $widget_output = file_get_contents($file_name);
                    }
                } elseif (strpos($params['output'], '@INCLUDE') === 0) {
                    $file_name = EVO_BASE_PATH . trim(substr($params['output'], 9));
                    if (!is_file($file_name)) {
                        $widget_output = $file_name . ' does not exist';
                    } else {
                        /* The included file needs to set $widget_output. Can be string, array, object */
                        include $file_name;
                    }
                } elseif ($value !== '') {
                    if (strpos($params['output'], '@CHUNK') === 0) {
                        $chunk_name = trim(substr($params['output'], 7));
                        $widget_output = $modx->getChunk($chunk_name);
                    } elseif (strpos($params['output'], '@EVAL') === 0) {
                        $eval_str = trim(substr($params['output'], 6));
                        $widget_output = eval($eval_str);
                    } else {
                        $widget_output = $params['output'];
                    }
                } else {
                    $widget_output = '';
                }
                if (is_string($widget_output)) {
                    $_ = $modx->getConfig('enable_filter');
                    $modx->setConfig('enable_filter', 1);
                    $widget_output = $modx->parseText($widget_output, ['value' => $value]);
                    $modx->setConfig('enable_filter', $_);
                    $o = $modx->parseDocumentSource($widget_output);
                } else {
                    $o = $widget_output;
                }
                break;

            default:
                $value = parseInput($value);
                if ($tvtype === 'checkbox' || $tvtype === 'listbox-multiple') {
                    // add separator
                    $value = explode('||', $value);
                    $value = implode($sep, $value);
                }
                $o = $value;
                break;
        }

        return $o;
    }
}

if (!function_exists('decodeParamValue')) {
    /**
     * @param string $s
     * @return string
     */
    function decodeParamValue($s)
    {
        return str_replace(['%3D', '%26'], ['=', '&'], $s); // &
    }
}

if (!function_exists('parseInput')) {
    /**
     * returns an array if a delimiter is present. returns array is a recordset is present
     *
     * @param $src
     * @param string $delim
     * @param string $type
     * @param bool $columns
     * @return array|string
     */
    function parseInput($src, $delim = '||', $type = 'string', $columns = true)
    { // type can be: string, array
        $modx = evo();
        if ($modx->getDatabase()->isResult($src)) {
            // must be a recordset
            $rows = [];
            while ($cols = $modx->getDatabase()->getRow($src, 'num')) {
                $rows[] = ($columns) ? $cols : implode(' ', $cols);
            }

            return ($type === 'array') ? $rows : implode($delim, $rows);
        }

// must be a text
        if ($type === 'array') {
            return explode($delim, $src);
        }

        return $src;
    }
}

if (!function_exists('getUnixtimeFromDateString')) {
    /**
     * @param string $value
     * @return bool|false|int
     */
    function getUnixtimeFromDateString($value)
    {
        $timestamp = false;
        // Check for MySQL or legacy style date
        $date_match_1 = '/^([0-9]{2})-([0-9]{2})-([0-9]{4})\ ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';
        $date_match_2 = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})\ ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';
        $matches = [];
        if (strpos($value, '-') !== false) {
            if (preg_match($date_match_1, $value, $matches)) {
                $timestamp = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[1], $matches[3]);
            } elseif (preg_match($date_match_2, $value, $matches)) {
                $timestamp = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
            }
        }
        // If those didn't work, use strtotime to figure out the date
        if ($timestamp === false || $timestamp === -1) {
            $timestamp = strtotime($value);
        }

        return $timestamp;
    }
}

if (!function_exists('renderFormElement')) {
    /**
     * DISPLAY FORM ELEMENTS
     *
     * @param string $field_type
     * @param string $field_id
     * @param string $default_text
     * @param string $field_elements
     * @param string $field_value
     * @param string $field_style
     * @param array $row
     * @param array $tvsArray
     * @return string
     */
    function renderFormElement(
        $field_type,
        $field_id,
        $default_text = '',
        $field_elements = '',
        $field_value = '',
        $field_style = '',
        $row = [],
        $tvsArray = [],
        $content = null
    )
    {
        $modx = evo();
        if ($content === null) {
            global $content;
        }
        if (substr($default_text, 0, 6) === '@@EVAL' && $field_value === $default_text) {
            $eval_str = trim(substr($default_text, 7));
            $default_text = eval($eval_str);
            $field_value = $default_text;
        }

        $field_html = '';
        $cimode = strpos($field_type, ':');
        static $i = 0;
        if ($cimode === false) {
            switch ($field_type) {

                case 'text': // handler for regular text boxes
                case 'rawtext'; // non-htmlentity converted text boxes
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%%" />';

                    break;
                case 'email': // handles email input fields
                    $field_html .= '<input type="email" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%%" />';
                    break;
                case 'number': // handles the input of numbers
                    $field_html .= '<input type="number" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%%" />';
                    break;
                case 'textareamini': // handler for textarea mini boxes
                    $field_html .= '<textarea id="tv' . $field_id . '" name="tv' . $field_id . '" cols="40" rows="5" onchange="documentDirty=true;" style="width:100%%">' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '</textarea>';
                    break;
                case 'textarea': // handler for textarea boxes
                case 'rawtextarea': // non-htmlentity convertex textarea boxes
                case 'htmlarea': // handler for textarea boxes (deprecated)
                case 'richtext': // handler for textarea boxes
                    $field_html .= '<textarea id="tv' . $field_id . '" name="tv' . $field_id . '" cols="40" rows="15" onchange="documentDirty=true;" style="width:100%%">' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '</textarea>';
                    break;
                case 'date':
                    $field_id = str_replace([
                        '-',
                        '.'
                    ], '_', urldecode($field_id));
                    if ($field_value == '') {
                        $field_value = 0;
                    }
                    $field_html .= '<input id="tv' . $field_id . '" name="tv' . $field_id . '" class="DatePicker" type="text" value="' . (!isset($field_value) || $field_value == 0) ? '' : $field_value . '" onblur="documentDirty=true;" />';
                    $field_html .= ' <a onclick="document.forms[\'mutate\'].elements[\'tv' . $field_id . '\'].value=\'\';document.forms[\'mutate\'].elements[\'tv' . $field_id . '\'].onblur(); return true;" onmouseover="window.status=\'clear the date\'; return true;" onmouseout="window.status=\'\'; return true;" style="cursor:pointer; cursor:hand"><i class="' . ManagerTheme::getStyle('icon_calendar_close') . '"></i></a>';
                    break;
                case 'dropdown': // handler for select boxes
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '" size="1" onchange="documentDirty=true;">';

                    $index_list = ParseIntputOptions(
                        ProcessTVCommand(
                            $field_elements
                            , $field_id
                            , ''
                            , 'tvform'
                            , $tvsArray
                        )
                    );
                    foreach ($index_list as $item => $itemvalue) {
                        if ((is_array($itemvalue))) {
                            list($item, $itemvalue) = $itemvalue;
                        } else {
                            list($item, $itemvalue) = array_merge(explode('==', $itemvalue), ['']);
                        }
                        if ($itemvalue == '') {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . ($itemvalue == $field_value ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= '</select>';
                    break;
                case 'listbox': // handler for select boxes
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '" onchange="documentDirty=true;" size="8">';
                    $index_list = ParseIntputOptions(
                        ProcessTVCommand(
                            $field_elements
                            , $field_id
                            , ''
                            , 'tvform'
                            , $tvsArray
                        )
                    );
                    foreach ($index_list as $item => $itemvalue) {
                        if (is_array($itemvalue)) {
                            list($item, $itemvalue) = $itemvalue;
                        } else {
                            list($item, $itemvalue) = array_merge(explode('==', $itemvalue), ['']);
                        }
                        if ($itemvalue == '') {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . ($itemvalue == $field_value ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= '</select>';
                    break;
                case 'listbox-multiple': // handler for select boxes where you can choose multiple items
                    $field_value = explode('||', $field_value);
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '[]" multiple="multiple" onchange="documentDirty=true;" size="8">';
                    $index_list = ParseIntputOptions(
                        ProcessTVCommand(
                            $field_elements
                            , $field_id
                            , ''
                            , 'tvform',
                            $tvsArray)
                    );
                    foreach ($index_list as $item => $itemvalue) {
                        if (is_array($itemvalue)) {
                            list($item, $itemvalue) = $itemvalue;
                        } else {
                            list($item, $itemvalue) = array_merge(explode('==', $itemvalue), ['']);
                        }
                        if ($itemvalue == '') {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . ($itemvalue == $field_value ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= '</select>';
                    break;
                case 'url': // handles url input fields
                    $urls = [
                        '' => '--',
                        'http://' => 'http://',
                        'https://' => 'https://',
                        'ftp://' => 'ftp://',
                        'mailto:' => 'mailto:'
                    ];
                    $field_html = '<table border="0" cellspacing="0" cellpadding="0"><tr><td><select id="tv'.$field_id.'_prefix" name="tv'.$field_id.'_prefix" onchange="documentDirty=true;">';
                    foreach ($urls as $k => $v) {
                        if (strpos($field_value, $v) === false) {
                            $field_html .= '<option value="'.$v.'">'.$k.'</option>';
                        } else {
                            $field_value = str_replace($v, '', $field_value);
                            $field_html .= '<option value="'.$v.'" selected="selected">'.$k.'</option>';
                        }
                    }
                    $field_html .= '</select></td><td>';
                    $field_html .= '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.$modx->getPhpCompat()->htmlspecialchars($field_value).'" width="100" '.$field_style.' onchange="documentDirty=true;" /></td></tr></table>';
                    break;
                case 'checkbox': // handles check boxes
                    if (!is_array($field_value)) {
                        $values = explode('||', $field_value);
                    } else {
                        $values = $field_value;
                    }
                    $index_list = ParseIntputOptions(
                        ProcessTVCommand(
                            $field_elements
                            , $field_id
                            , ''
                            , 'tvform'
                            , $tvsArray)
                    );
                    $tpl = '<label class="checkbox"><input type="checkbox" value="%s" id="tv_%s" name="tv%s[]" %s onchange="documentDirty=true;" />%s</label><br />';
                    $_ = [];
                    foreach ($index_list as $c => $item) {
                        if (is_array($item)) {
                            $name = trim($item[0]);
                            $value = isset($item[1]) ? $item[1] : $name;
                        } else {
                            $item = trim($item);
                            if (strpos($item, '==') !== false) {
                                list($name, $value) = array_merge(explode('==', $item, 2), ['']);
                            } else {
                                list($name, $value) = [$item, $item];
                            }
                        }
                        $checked = in_array($value, $values) ? ' checked="checked"' : '';
                        $param = [
                            $modx->getPhpCompat()->htmlspecialchars($value),
                            $i,
                            $field_id,
                            $checked,
                            $name
                        ];
                        $_[] = vsprintf($tpl, $param);
                        $i++;
                    }
                    $field_html = implode("\n", $_);
                    break;
                case 'option': // handles radio buttons
                    $index_list = ParseIntputOptions(
                        ProcessTVCommand(
                            $field_elements
                            , $field_id
                            , ''
                            , 'tvform'
                            , $tvsArray
                        )
                    );
                    foreach ($index_list as $item => $itemvalue) {
                        if (is_array($itemvalue)) {
                            list($item, $itemvalue) = $itemvalue;
                        } else {
                            list($item, $itemvalue) = array_merge(explode('==', $itemvalue), ['']);
                        }
                        if (strlen($itemvalue) == 0) {
                            $itemvalue = $item;
                        }
                        $field_html .= '<input type="radio" value="'.$modx->getPhpCompat()->htmlspecialchars($itemvalue).'" id="tv_'.$i.'" name="tv'.$field_id.'" '.($itemvalue == $field_value ? 'checked="checked"' : '').' onchange="documentDirty=true;" /><label for="tv_'.$i.'" class="radio">'.$item.'</label><br />';
                        $i++;
                    }
                    break;
                case 'image': // handles image fields using htmlarea image manager
                    global $ResourceManagerLoaded;
                    global $content, $which_editor;
                    if (!$ResourceManagerLoaded && !(($content['richtext'] == 1 || $modx->getManagerApi()->action == 4) && $modx->getConfig('use_editor') && $which_editor == 3)) {
                        $ResourceManagerLoaded = true;
                    }
                    $field_html .= '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value.'" '.$field_style.' onchange="documentDirty=true;" /><input type="button" value="'.ManagerTheme::getLexicon('insert').'" onclick="BrowseServer(\'tv'.$field_id.'\')" />';
                    break;
                case 'file': // handles the input of file uploads
                    /* Modified by Timon for use with resource browser */
                    global $ResourceManagerLoaded;
                    global $content, $which_editor;
                    if (!$ResourceManagerLoaded && !(($content['richtext'] == 1 || $modx->getManagerApi()->action == 4) && $modx->getConfig('use_editor') && $which_editor == 3)) {
                        $ResourceManagerLoaded = true;
                    }
                    $field_html .= '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value.'" '.$field_style.' onchange="documentDirty=true;" /><input type="button" value="'.ManagerTheme::getLexicon('insert').'" onclick="BrowseFileServer(\'tv'.$field_id.'\')" />';
                    break;

                case 'custom_tv':
                    $custom_output = '';
                    /* If we are loading a file */
                    if (strpos($field_elements, '@FILE') === 0) {
                        $file_name = EVO_BASE_PATH . trim(substr($field_elements, 6));
                        if (!file_exists($file_name)) {
                            $custom_output = $file_name . ' does not exist';
                        } else {
                            $custom_output = file_get_contents($file_name);
                        }
                    } elseif (strpos($field_elements, '@INCLUDE') === 0) {
                        $file_name = EVO_BASE_PATH . trim(substr($field_elements, 9));
                        if (!file_exists($file_name)) {
                            $custom_output = $file_name . ' does not exist';
                        } else {
                            ob_start();
                            include $file_name;
                            $custom_output = ob_get_clean();
                        }
                    } elseif (strpos($field_elements, '@CHUNK') === 0) {
                        $chunk_name = trim(substr($field_elements, 7));
                        $chunk_body = $modx->getChunk($chunk_name);
                        if ($chunk_body == false) {
                            $custom_output = ManagerTheme::getLexicon('chunk_no_exist').'('.ManagerTheme::getLexicon('htmlsnippet_name').':'.$chunk_name.')';
                        } else {
                            $custom_output = $chunk_body;
                        }
                    } elseif (strpos($field_elements, '@EVAL') === 0) {
                        $eval_str = trim(substr($field_elements, 6));
                        $custom_output = eval($eval_str);
                    } else {
                        $custom_output = $field_elements;
                    }
                    $replacements = [
                        '[+field_type+]' => $field_type,
                        '[+field_id+]' => $field_id,
                        '[+default_text+]' => $default_text,
                        '[+field_value+]' => $modx->getPhpCompat()->htmlspecialchars($field_value),
                        '[+field_style+]' => $field_style,
                    ];
                    $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
                    $modx->documentObject = $content;
                    $modx->documentIdentifier = $content['id'];
                    $custom_output = $modx->parseDocumentSource($custom_output);
                    $field_html .= $custom_output;
                    break;

                default: // the default handler -- for errors, mostly
                    $field_html .= '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.$modx->getPhpCompat()->htmlspecialchars($field_value).'" '.$field_style.' onchange="documentDirty=true;" />';

            } // end switch statement
        } else {
            $custom = explode(':', $field_type);
            $file_name = EVO_BASE_PATH.'assets/tvs/'.$custom['1'].'/'.$custom['1'].'.customtv.php';
            if (!is_file($file_name)) {
                $custom_output = $file_name . ' does not exist';
            } else {
                ob_start();
                include $file_name;
                $custom_output = ob_get_clean();
            }
            $replacements = [
                '[+field_type+]' => $field_type,
                '[+field_id+]' => $field_id,
                '[+default_text+]' => $default_text,
                '[+field_value+]' => $modx->getPhpCompat()->htmlspecialchars($field_value),
                '[+field_style+]' => $field_style,
            ];
            $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
            $modx->documentObject = $content;
            $custom_output = $modx->parseDocumentSource($custom_output);
            $field_html .= $custom_output;
        }

        return $field_html;
    } // end renderFormElement function
}

if (!function_exists('ParseInputOptions')) {
    /**
     * @param string|array $v
     * @return array
     */
    function ParseInputOptions($v): array
    {
        $modx = evo();
        if (is_array($v)) {
            return $v;
        }

        if (!$modx->getDatabase()->isResult($v)) {
            return explode('||', $v);
        }

        $a = [];
        while ($cols = $modx->getDatabase()->getRow($v, 'num')) {
            $a[] = $cols;
        }

        return $a;
    }
}
if (!function_exists('ParseIntputOptions')) {
    /**
     * @param $v
     * @return array
     * @todo [remove@3.7] Remove in Evolution CMS 3.7
     * @deprecated
     * @since 3.5.0
     */
    function ParseIntputOptions($v)
    {
        return ParseInputOptions($v);
    }
}
