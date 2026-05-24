<?php
if (!function_exists('getDefaultTemplate')) {
    /**
     * @return string
     */
    function getDefaultTemplate()
    {
        $modx = evolutionCMS();

        $default_template = '';

        switch ($modx->getConfig('auto_template_logic')) {
            case 'sibling':
                if (!isset($_GET['pid']) || empty($_GET['pid'])) {
                    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                        $site_start = $modx->getConfig('site_start');
                        $where = ['isfolder' => 0, 'site_content.id' => $site_start];//"sc.isfolder=0 AND sc.id!='{$site_start}'";

                        $sibl = $modx->getDocumentChildren($_REQUEST['id'], 1, 0, 'template,menuindex', $where, 'menuindex', 'ASC', 1);
                        if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                            $default_template = $sibl[0]['template'];
                        }
                    }
                } else {
                    $sibl = evo()->getDocumentChildren($_REQUEST['pid'], 1, 0, 'template,menuindex', [], 'isfolder', 'ASC', 1);

                    if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                        $default_template = $sibl[0]['template'];
                    } else {
                        $sibl = $modx->getDocumentChildren($_REQUEST['pid'], 0, 0, 'template,menuindex', [],
                            'isfolder', 'ASC', 1);
                        if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                            $default_template = $sibl[0]['template'];
                        }
                    }
                }
                if (is_numeric($default_template)) {
                    break;
                } // If $default_template could not be determined, fall back / through to "parent"-mode
            case 'parent':
                if (isset($_REQUEST['pid']) && !empty($_REQUEST['pid'])) {
                    $parent = $modx->getPageInfo($_REQUEST['pid'], 0, 'template');
                    if (isset($parent['template'])) {
                        $default_template = $parent['template'];
                    }
                }
                break;
            case 'system':
            default: // default_template is already set
                $default_template = $modx->getConfig('default_template');
        }

        return empty($default_template) ? $modx->getConfig('default_template') : $default_template;
    }
}

if (! function_exists('ProcessTVCommand')) {
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
        $modx = evolutionCMS();
        $docid = (int)$docid > 0 ? (int)$docid : $modx->documentIdentifier;
        $nvalue = is_array($value) ? $value : trim($value);
        if (is_array($nvalue) || substr($nvalue, 0, 1) != '@') {
            return $value;
        } elseif (isset($modx->config['enable_bindings']) && $modx->config['enable_bindings'] != 1 && $src === 'docform') {
            return '@Bindings is disabled.';
        } else {
            [$cmd, $param] = ParseCommand($nvalue);
            $cmd = trim($cmd);
            $param = parseTvValues($param, $tvsArray);
            switch ($cmd) {
                case "FILE" :
                    $output = $modx->atBindFileContent($nvalue);
                    break;

                case "CHUNK" : // retrieve a chunk and process it's content
                    $chunk = $modx->getChunk(trim($param));
                    $output = $chunk;
                    break;

                case "DOCUMENT" : // retrieve a document and process it's content
                    $rs = $modx->getDocument($param);
                    if (is_array($rs)) {
                        $output = $rs['content'];
                    } else {
                        $output = "Unable to locate document $param";
                    }
                    break;

                case "SELECT" : // selects a record from the cms database
                    $rt = [];
                    $replacementVars = [
                        'DBASE'  => $modx->getDatabase()->getConfig('database'),
                        'PREFIX' => $modx->getDatabase()->getConfig('prefix')
                    ];
                    foreach ($replacementVars as $rvKey => $rvValue) {
                        $modx->setPlaceholder($rvKey, $rvValue);
                    }
                    $param = $modx->mergePlaceholderContent($param);
                    $rs = $modx->getDatabase()->query("SELECT $param;");
                    $output = $rs;
                    break;

                case "EVAL" : // evaluates text as php codes return the results
                    $output = eval ($param);
                    break;

                case "INHERIT" :
                    $output = $param; // Default to param value if no content from parents
                    $doc = $modx->getPageInfo($docid, 0, 'id,parent');

                    while ($doc['parent'] != 0) {
                        $parent_id = $doc['parent'];

                        // Grab document regardless of publish status
                        $doc = $modx->getPageInfo($parent_id, 0, 'id,parent,published');
                        if ($doc['parent'] != 0 && !$doc['published']) {
                            continue;
                        } // hide unpublished docs if we're not at the top

                        $tv = $modx->getTemplateVar($name, '*', $doc['id'], $doc['published']);

                        // if an inherited value is found and if there is content following the @INHERIT binding
                        // remove @INHERIT and output that following content. This content could contain other
                        // @ bindings, that are processed in the next step
                        if ((string)$tv['value'] !== '' && !preg_match('%^@INHERIT[\s\n\r]*$%im', $tv['value'])) {
                            $output = trim(str_replace('@INHERIT', '', (string)$tv['value']));
                            break 2;
                        }
                    }
                    break;

                case 'DIRECTORY' :
                    $files = [];
                    $path = EVO_BASE_PATH . $param;
                    if (substr($path, -1, 1) != '/') {
                        $path .= '/';
                    }
                    if (!is_dir($path)) {
                        die($path);
                        break;
                    }
                    $dir = dir($path);
                    while (($file = $dir->read()) !== false) {
                        if (substr($file, 0, 1) != '.') {
                            $files[] = "{$file}=={$param}{$file}";
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
            return is_string($output) && ($output != $value) ? ProcessTVCommand($output, $name, $docid, $src,
                $tvsArray) : $output;
        }
    }
}

if (! function_exists('ProcessFile')) {
    /**
     * @param $file
     * @return string
     */
    function ProcessFile($file)
    {
        // get the file
        $buffer = @file_get_contents($file);
        if ($buffer === false) {
            $buffer = " Could not retrieve document '$file'.";
        }

        return $buffer;
    }
}

if (! function_exists('ParseCommand')) {
    /**
     * ParseCommand - separate @ cmd from params
     *
     * @param string $binding_string
     * @return array
     */
    function ParseCommand($binding_string)
    {
        $binding_array = [];
        foreach (\EvolutionCMS\Parser::TV_BINDINGS as $cmd) {
            if (strpos($binding_string, '@' . $cmd) === 0) {
                $code = substr($binding_string, strlen($cmd) + 1);
                $binding_array = [$cmd, trim($code)];
                break;
            }
        }

        return $binding_array;
    }
}

if (! function_exists('parseTvValues')) {
    /**
     * Parse Evolution CMS Template-Variables
     *
     * @param string $param
     * @param array $tvsArray
     * @return mixed
     */
    function parseTvValues($param, $tvsArray)
    {
        $modx = evolutionCMS();
        $tvsArray = is_array($modx->documentObject) ? array_merge($tvsArray, $modx->documentObject) : $tvsArray;
        if (strpos($param, '[*') !== false) {
            $matches = $modx->getTagsFromContent($param, '[*', '*]');
            foreach ($matches[0] as $i => $match) {
                if (isset($tvsArray[$matches[1][$i]])) {
                    if (is_array($tvsArray[$matches[1][$i]])) {
                        $value = $tvsArray[$matches[1][$i]]['value'];
                        $value = $value === '' ? $tvsArray[$matches[1][$i]]['default_text'] : $value;
                    } else {
                        $value = $tvsArray[$matches[1][$i]];
                    }
                    $param = str_replace($match, $value, $param);
                }
            }
        }

        return $param;
    }
}

if (! function_exists('getTVDisplayFormat')) {
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
    function getTVDisplayFormat($name, $value, $format, $paramstring = "", $tvtype = "", $docid = "", $sep = '')
    {

        $modx = evolutionCMS();
        $o = '';

        // process any TV commands in value
        $docid = (int)$docid > 0 ? (int)$docid : $modx->documentIdentifier;
        $value = ProcessTVCommand($value, $name, $docid);

        $params = [];
        if ($paramstring) {
            $cp = explode("&", $paramstring);
            foreach ($cp as $p => $v) {
                $v = trim($v); // trim
                $ar = explode("=", $v);
                if (is_array($ar) && count($ar) == 2) {
                    $params[$ar[0]] = decodeParamValue($ar[1]);
                }
            }
        }

        $id = "tv$name";
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
                            'src'   => $src,
                            'id'    => ($params['id'] ? $params['id'] : ''),
                            'alt'   => $modx->getPhpCompat()->htmlspecialchars($params['alttext']),
                            'style' => $params['style']
                        ];
                        if (isset($params['align']) && $params['align'] != 'none') {
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

            case "delim":    // display as delimitted list
                $value = parseInput($value, "||");
                $p = $params['format'] ? $params['format'] : " ";
                if ($p == "\\n") {
                    $p = "\n";
                }
                $o = str_replace("||", $p, $value);
                break;

            case "string":
                $value = parseInput($value);
                $format = strtolower($params['format']);
                if ($format == 'upper case') {
                    $o = strtoupper($value);
                } else {
                    if ($format == 'lower case') {
                        $o = strtolower($value);
                    } else {
                        if ($format == 'sentence case') {
                            $o = ucfirst($value);
                        } else {
                            if ($format == 'capitalize') {
                                $o = ucwords($value);
                            } else {
                                $o = $value;
                            }
                        }
                    }
                }
                break;

            case "date":
                if ($value != '' || $params['default'] == 'Yes') {
                    if (empty($value)) {
                        $value = 'now';
                    }
                    $timestamp = getUnixtimeFromDateString($value);
                    $p = $params['format'] ? $params['format'] : "%A %d, %B %Y";
                    $o = strftime($p, $timestamp);
                } else {
                    $value = '';
                }
                break;

            case "hyperlink":
                $value = parseInput($value, "||", "array");
                $o = '';
                $countValue = count($value);
                for ($i = 0; $i < $countValue; $i++) {
                    [$name, $url] = is_array($value[$i]) ? $value[$i] : array_merge(explode("==", $value[$i]), ['']);
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
                            'href'   => $url,
                            'title'  => $params['title'] ? $modx->getPhpCompat()->htmlspecialchars($params['title']) : $name,
                            'class'  => $params['class'],
                            'style'  => $params['style'],
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

            case "htmltag":
                $value = parseInput($value, "||", "array");
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
                        'id'    => ($tagid ? $tagid : $id),
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

            case "richtext":
                $value = parseInput($value);
                $w = $params['w'] ? $params['w'] : '100%';
                $h = $params['h'] ? $params['h'] : '400px';
                $richtexteditor = $params['edt'] ? $params['edt'] : "";
                $o = '<div class="EVO_RichTextWidget"><textarea id="' . $id . '" name="' . $id . '" style="width:' . $w . '; height:' . $h . ';">';
                $o .= $modx->getPhpCompat()->htmlspecialchars($value);
                $o .= '</textarea></div>';
                $replace_richtext = [$id];
                // setup editors
                if (!empty($replace_richtext) && !empty($richtexteditor)) {
                    // invoke OnRichTextEditorInit event
                    $evtOut = $modx->invokeEvent("OnRichTextEditorInit", [
                        'editor'      => $richtexteditor,
                        'elements'    => $replace_richtext,
                        'forfrontend' => 1,
                        'width'       => $w,
                        'height'      => $h
                    ]);
                    if (is_array($evtOut)) {
                        $o .= implode("", $evtOut);
                    }
                }
                break;

            case "unixtime":
                $value = parseInput($value);
                $o = getUnixtimeFromDateString($value);
                break;

            case "viewport":
                $value = parseInput($value);
                $id = '_' . time();
                if (!$params['vpid']) {
                    $params['vpid'] = $id;
                }
                $sTag = "<iframe";
                $eTag = "</iframe>";
                $autoMode = "0";
                $w = $params['width'];
                $h = $params['height'];
                if ($params['stretch'] == 'Yes') {
                    $w = "100%";
                    $h = "100%";
                }
                if ($params['asize'] == 'Yes' || ($params['awidth'] == 'Yes' && $params['aheight'] == 'Yes')) {
                    $autoMode = "3";  //both
                } else {
                    if ($params['awidth'] == 'Yes') {
                        $autoMode = "1"; //width only
                    } else {
                        if ($params['aheight'] == 'Yes') {
                            $autoMode = "2";    //height only
                        }
                    }
                }

                $modx->regClientStartupScript(EVO_MANAGER_URL . "media/script/bin/viewport.js", [
                    'name'      => 'viewport',
                    'version'   => '0',
                    'plaintext' => false
                ]);
                $o = $sTag . " id='" . $params['vpid'] . "' name='" . $params['vpid'] . "' ";
                if ($params['class']) {
                    $o .= " class='" . $params['class'] . "' ";
                }
                if ($params['style']) {
                    $o .= " style='" . $params['style'] . "' ";
                }
                if ($params['attrib']) {
                    $o .= $params['attrib'] . " ";
                }
                $o .= "scrolling='" . ($params['sbar'] == 'No' ? "no" : ($params['sbar'] == 'Yes' ? "yes" : "auto")) . "' ";
                $o .= "src='" . $value . "' frameborder='" . $params['borsize'] . "' ";
                $o .= "onload=\"window.setTimeout('ResizeViewPort(\\'" . $params['vpid'] . "\\'," . $autoMode . ")',100);\" width='" . $w . "' height='" . $h . "' ";
                $o .= ">";
                $o .= $eTag;
                break;

            case "datagrid":
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
                if ($tvtype == 'checkbox' || $tvtype == 'listbox-multiple') {
                    // remove delimiter from checkbox and listbox-multiple TVs
                    $value = str_replace('||', '', $value);
                }
                $o = htmlentities($value, ENT_NOQUOTES, $modx->getConfig('modx_charset'));
                break;

            case 'custom_widget':
                $widget_output = '';
                $o = '';
                /* If we are loading a file */
                if (substr($params['output'], 0, 5) == "@FILE") {
                    $file_name = EVO_BASE_PATH . trim(substr($params['output'], 6));
                    if (!file_exists($file_name)) {
                        $widget_output = $file_name . ' does not exist';
                    } else {
                        $widget_output = file_get_contents($file_name);
                    }
                } elseif (substr($params['output'], 0, 8) == '@INCLUDE') {
                    $file_name = EVO_BASE_PATH . trim(substr($params['output'], 9));
                    if (!file_exists($file_name)) {
                        $widget_output = $file_name . ' does not exist';
                    } else {
                        /* The included file needs to set $widget_output. Can be string, array, object */
                        include $file_name;
                    }
                } elseif (substr($params['output'], 0, 6) == '@CHUNK' && $value !== '') {
                    $chunk_name = trim(substr($params['output'], 7));
                    $widget_output = $modx->getChunk($chunk_name);
                } elseif (substr($params['output'], 0, 5) == '@EVAL' && $value !== '') {
                    $eval_str = trim(substr($params['output'], 6));
                    $widget_output = eval($eval_str);
                } elseif ($value !== '') {
                    $widget_output = $params['output'];
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
                if ($tvtype == 'checkbox' || $tvtype == 'listbox-multiple') {
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

if (! function_exists('decodeParamValue')) {
    /**
     * @param string $s
     * @return string
     */
    function decodeParamValue($s)
    {
        $s = str_replace("%3D", '=', $s); // =

        return str_replace("%26", '&', $s); // &
    }
}

if (! function_exists('parseInput')) {
    /**
     * returns an array if a delimiter is present. returns array is a recordset is present
     *
     * @param $src
     * @param string $delim
     * @param string $type
     * @param bool $columns
     * @return array|string
     */
    function parseInput($src, $delim = "||", $type = "string", $columns = true)
    { // type can be: string, array
        $modx = evolutionCMS();
        if ($modx->getDatabase()->isResult($src)) {
            // must be a recordset
            $rows = [];
            while ($cols = $modx->getDatabase()->getRow($src, 'num')) {
                $rows[] = ($columns) ? $cols : implode(" ", $cols);
            }

            return ($type == "array") ? $rows : implode($delim, $rows);
        } else {
            // must be a text
            if ($type == "array") {
                return explode($delim, $src);
            } else {
                return $src;
            }
        }
    }
}

if (! function_exists('getUnixtimeFromDateString')) {
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

if (! function_exists('renderFormElement')) {
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
        $content = null,
        $properties = []
    ) {
        $modx = evolutionCMS();
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
                case "text": // handler for regular text boxes
                case "rawtext": // non-htmlentity converted text boxes
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%" />';
                    break;
                case "email": // handles email input fields
                    $field_html .= '<input type="email" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%"/>';
                    break;
                case "number": // handles the input of numbers
                    if (empty($properties)) {
                        $step = '';
                        $min = '';
                        $max = '';
                    } else {
                        $step = isset($properties['step']) ? $properties['step'] : '';
                        $min = isset($properties['min']) ? $properties['min'] : '';
                        $max = isset($properties['max']) ? $properties['max'] : '';
                    }
                    $field_html .= '<input type="number"' . ($step ? '" step="'.$step.'"' : '') . ($min ? ' min="'.$min.'"' : '') . ($max ? ' max="'.$max.'"' : '') . ' id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' tvtype="' . $field_type . '" onchange="documentDirty=true;" style="width:100%" onkeyup="this.value=this.value.replace(/[^\d-,.+]/,\'\')"/>';
                    break;
                case "textareamini": // handler for textarea mini boxes
                    $field_html .= '<textarea id="tv' . $field_id . '" name="tv' . $field_id . '" cols="40" rows="5" onchange="documentDirty=true;" style="width:100%">' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '</textarea>';
                    break;
                case "textarea": // handler for textarea boxes
                case "rawtextarea": // non-htmlentity convertex textarea boxes
                case "htmlarea": // handler for textarea boxes (deprecated)
                case "richtext": // handler for textarea boxes
                    $field_html .= '<textarea id="tv' . $field_id . '" name="tv' . $field_id . '" cols="40" rows="15" onchange="documentDirty=true;" style="width:100%">' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '</textarea>';
                    break;
                case "date":
                    $field_id = str_replace([
                        '-',
                        '.'
                    ], '_', urldecode($field_id));
                    if ($field_value == '') {
                        $field_value = 0;
                    }
                    $field_html .= '<input id="tv' . $field_id . '" name="tv' . $field_id . '" class="DatePicker" type="text" value="' . ( !isset($field_value) || $field_value == 0 ? "" : $field_value) . '" onblur="documentDirty=true;" />';
                    $field_html .= ' <a onclick="document.forms[\'mutate\'].elements[\'tv' . $field_id . '\'].value=\'\';document.forms[\'mutate\'].elements[\'tv' . $field_id . '\'].onblur(); return true;" onmouseover="window.status=\'clear the date\'; return true;" onmouseout="window.status=\'\'; return true;" style="cursor:pointer; cursor:hand"><i class="' . ManagerTheme::getStyle("icon_calendar_close") . '"></i></a>';

                    break;
                case "dropdown": // handler for select boxes
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '" size="1" onchange="documentDirty=true;">';
                    $index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id, '', 'tvform',
                        $tvsArray));
                    foreach($index_list as $item => $itemvalue) {
                        [$item, $itemvalue] = (is_array($itemvalue)) ? $itemvalue : array_merge(explode("==", $itemvalue), ['']);
                        if (strlen($itemvalue) == 0) {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . ($itemvalue == $field_value ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= "</select>";
                    break;
                case "listbox": // handler for select boxes
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '" onchange="documentDirty=true;" size="8">';
                    $index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id, '', 'tvform',
                        $tvsArray));
                    foreach($index_list as $item => $itemvalue) {
                        [$item, $itemvalue] = (is_array($itemvalue)) ? $itemvalue : array_merge(explode("==", $itemvalue), ['']);
                        if (strlen($itemvalue) == 0) {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . ($itemvalue == $field_value ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= "</select>";
                    break;
                case "listbox-multiple": // handler for select boxes where you can choose multiple items
                    $field_value = explode("||", $field_value);
                    $field_html .= '<select id="tv' . $field_id . '" name="tv' . $field_id . '[]" multiple="multiple" onchange="documentDirty=true;" size="8">';
                    $index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id, '', 'tvform',
                        $tvsArray));
                    foreach($index_list as $item => $itemvalue) {
                        [$item, $itemvalue] = (is_array($itemvalue)) ? $itemvalue : array_merge(explode("==", $itemvalue), ['']);
                        if (strlen($itemvalue) == 0) {
                            $itemvalue = $item;
                        }
                        $field_html .= '<option value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '"' . (in_array($itemvalue,
                                $field_value) ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($item) . '</option>';
                    }
                    $field_html .= "</select>";
                    break;
                case "url": // handles url input fields
                    $urls = [
                        ''         => '--',
                        'http://'  => 'http://',
                        'https://' => 'https://',
                        'ftp://'   => 'ftp://',
                        'mailto:'  => 'mailto:'
                    ];
                    $field_html = '<table border="0" cellspacing="0" cellpadding="0"><tr><td><select id="tv' . $field_id . '_prefix" name="tv' . $field_id . '_prefix" onchange="documentDirty=true;">';
                    foreach ($urls as $k => $v) {
                        if (strpos($field_value, $v) === false) {
                            $field_html .= '<option value="' . $v . '">' . $k . '</option>';
                        } else {
                            $field_value = str_replace($v, '', $field_value);
                            $field_html .= '<option value="' . $v . '" selected="selected">' . $k . '</option>';
                        }
                    }
                    $field_html .= '</select></td><td>';
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" width="100" ' . $field_style . ' onchange="documentDirty=true;" /></td></tr></table>';
                    break;
                case 'checkbox': // handles check boxes
                    $values = !is_array($field_value) ? explode('||', $field_value) : $field_value;
                    $index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id, '', 'tvform',
                        $tvsArray));
                    $tpl = '<label class="checkbox"><input type="checkbox" value="%s" id="tv_%s" name="tv%s[]" %s onchange="documentDirty=true;" />%s</label><br />';
                    $_ = [];
                    foreach ($index_list as $c => $item) {
                        if (is_array($item)) {
                            $name = trim($item[0]);
                            $value = isset($item[1]) ? $item[1] : $name;
                        } else {
                            $item = trim($item);
                            [$name, $value] = (strpos($item, '==') !== false) ? array_merge(explode('==', $item, 2), ['']) : [
                                $item,
                                $item
                            ];
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
                case "option": // handles radio buttons
                    $index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id, '', 'tvform', $tvsArray));
                    foreach($index_list as $item => $itemvalue) {
                        [$item, $itemvalue] = (is_array($itemvalue)) ? $itemvalue : array_merge(explode("==", $itemvalue), ['']);
                        if (strlen($itemvalue) == 0) {
                            $itemvalue = $item;
                        }
                        $field_html .= '<input type="radio" value="' . $modx->getPhpCompat()->htmlspecialchars($itemvalue) . '" id="tv_' . $i . '" name="tv' . $field_id . '" ' . ($itemvalue == $field_value ? 'checked="checked"' : '') . ' onchange="documentDirty=true;" /><label for="tv_' . $i . '" class="radio">' . $item . '</label><br />';
                        $i++;
                    }
                    break;
                case "image": // handles image fields using htmlarea image manager
                    global $ResourceManagerLoaded;
                    global $content, $which_editor;
                    if (!$ResourceManagerLoaded && !(((isset($content['richtext']) && $content['richtext'] == 1) || $modx->getManagerApi()->action == 4) && $modx->getConfig('use_editor') && $which_editor == 3)) {
                        $ResourceManagerLoaded = true;
                    }
                    $size = '';
                    $image = $field_value;
                    if(!empty($properties['width'])) {
                        $size .= 'width:' . $properties['width'] . 'px;';
                    }
                    if(!empty($properties['height'])) {
                        $size .= 'height:' . $properties['height'] . 'px;';
                    }
                    if(!empty($properties['thumbnailer'])) {
                        if (is_callable($properties['thumbnailer'])) {
                            $image = call_user_func($properties['thumbnailer'],
                                $image,
                                $properties['width'] ?: 120,
                                $properties['height'] ?: 120
                            );
                        } elseif (\Illuminate\Support\Facades\Storage::exists($properties['thumbnailer'])) {
                        $image = $properties['thumbnailer'] . '?' . http_build_query([
                                'src' => $image,
                                'w'   => $properties['width'] ?: 120,
                                'h'   => $properties['height'] ?: 120,
                            ]);
                        }
                    }
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '"  value="' . $field_value . '" ' . $field_style . ' onchange="documentDirty=true;" /><input type="button" value="' . ManagerTheme::getLexicon('insert') . '" onclick="BrowseServer(\'tv' . $field_id . '\')" />
                    <div class="col-12" style="padding-left: 0px;">
                        <div id="image_for_tv' . $field_id . '" class="image_for_field" data-image="' . $field_value . '" onclick="BrowseServer(\'tv' . $field_id . '\')" style="' . $size . 'background-image: url(\'' . evo()->getConfig('site_url') . $image . '\');"></div>
                        <script>document.getElementById(\'tv' . $field_id . '\').addEventListener(\'change\', evoRenderTvImageCheck, false);</script>
                    </div>';
                    break;
                case "file": // handles the input of file uploads
                    /* Modified by Timon for use with resource browser */
                    global $ResourceManagerLoaded;
                    global $content, $which_editor;
                    if (!$ResourceManagerLoaded && !(((isset($content['richtext']) && $content['richtext'] == 1) || $modx->getManagerApi()->action == 4) && $modx->getConfig('use_editor') && $which_editor == 3)) {
                        $ResourceManagerLoaded = true;
                    }
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '"  value="' . $field_value . '" ' . $field_style . ' onchange="documentDirty=true;" /><input type="button" value="' . ManagerTheme::getLexicon('insert') . '" onclick="BrowseFileServer(\'tv' . $field_id . '\')" />';

                    break;

                case 'custom_tv':
                    $custom_output = '';
                    /* If we are loading a file */
                    if (substr($field_elements, 0, 5) == "@FILE") {
                        $file_name = EVO_BASE_PATH . trim(substr($field_elements, 6));
                        if (!file_exists($file_name)) {
                            $custom_output = $file_name . ' does not exist';
                        } else {
                            $custom_output = file_get_contents($file_name);
                        }
                    } elseif (substr($field_elements, 0, 8) == '@INCLUDE') {
                        $file_name = EVO_BASE_PATH . trim(substr($field_elements, 9));
                        if (!file_exists($file_name)) {
                            $custom_output = $file_name . ' does not exist';
                        } else {
                            ob_start();
                            include $file_name;
                            $custom_output = ob_get_contents();
                            ob_end_clean();
                        }
                    } elseif (substr($field_elements, 0, 6) == "@CHUNK") {
                        $chunk_name = trim(substr($field_elements, 7));
                        $chunk_body = $modx->getChunk($chunk_name);
                        if ($chunk_body == false) {
                            $custom_output = $_lang['chunk_no_exist'] . '(' . $_lang['htmlsnippet_name'] . ':' . $chunk_name . ')';
                        } else {
                            $custom_output = $chunk_body;
                        }
                    } elseif (substr($field_elements, 0, 5) == "@EVAL") {
                        $eval_str = trim(substr($field_elements, 6));
                        $custom_output = eval($eval_str);
                    } else {
                        $custom_output = $field_elements;
                    }
                    $replacements = [
                        '[+field_type+]'   => $field_type,
                        '[+field_id+]'     => $field_id,
                        '[+default_text+]' => $default_text,
                        '[+field_value+]'  => $modx->getPhpCompat()->htmlspecialchars($field_value),
                        '[+field_style+]'  => $field_style,
                    ];
                    $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
                    $modx->documentObject = $content;
                    $modx->documentIdentifier = $content['id'];
                    $custom_output = $modx->parseDocumentSource($custom_output);
                    $field_html .= $custom_output;
                    break;

                default: // the default handler -- for errors, mostly
                    $field_html .= '<input type="text" id="tv' . $field_id . '" name="tv' . $field_id . '" value="' . $modx->getPhpCompat()->htmlspecialchars($field_value) . '" ' . $field_style . ' onchange="documentDirty=true;" />';

            } // end switch statement
        } else {
            $custom = explode(":", $field_type);
            $custom_output = '';
            $file_name = EVO_BASE_PATH . 'assets/tvs/' . $custom['1'] . '/' . $custom['1'] . '.customtv.php';
            if (!file_exists($file_name)) {
                $custom_output = $file_name . ' does not exist';
            } else {
                ob_start();
                include $file_name;
                $custom_output = ob_get_contents();
                ob_end_clean();
            }
            $replacements = [
                '[+field_type+]'   => $field_type,
                '[+field_id+]'     => $field_id,
                '[+default_text+]' => $default_text,
                '[+field_value+]'  => $modx->getPhpCompat()->htmlspecialchars($field_value),
                '[+field_style+]'  => $field_style,
            ];
            $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
            $modx->documentObject = $content;
            $custom_output = $modx->parseDocumentSource($custom_output);
            $field_html .= $custom_output;
        }

        return $field_html;
    } // end renderFormElement function
}

if (! function_exists('ParseInputOptions')) {
    /**
     * @param string|array $v
     * @return array
     */
    function ParseInputOptions($v)
    {
        $modx = evolutionCMS();
        $a = [];
        if (is_array($v)) {
            return $v;
        } else {
            if ($modx->getDatabase()->isResult($v)) {
                /**
                 * @todo May be, should use DBAPI::makeArray($v);
                 */
                while ($cols = $modx->getDatabase()->getRow($v, 'num')) {
                    $a[] = $cols;
                }
            } else {
                $a = explode("||", $v);
            }
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
