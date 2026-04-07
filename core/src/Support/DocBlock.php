<?php namespace EvolutionCMS\Support;

class DocBlock
{
    /**
     * Parses docBlock from a file and returns the result as an array
     *
     * @param string $element_dir
     * @param string $filename
     * @return array Associative array in the form property name => property value
     */
    public function parseFromFile($element_dir, $filename)
    {
        $params = [];
        $fullpath = $element_dir . '/' . $filename;
        if (is_readable($fullpath)) {
            $tpl = @fopen($fullpath, "r");
            if ($tpl) {
                $params['filename'] = $filename;
                $docblock_start_found = false;
                $name_found = false;
                $description_found = false;
                $docblock_end_found = false;
                $arrayParams = ['author', 'documentation', 'reportissues', 'link'];

                while (!feof($tpl)) {
                    $line = fgets($tpl);
                    $r = $this->parseLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found);
                    $docblock_start_found = $r['docblock_start_found'];
                    $name_found = $r['name_found'];
                    $description_found = $r['description_found'];
                    $docblock_end_found = $r['docblock_end_found'];
                    $param = $r['param'];
                    $val = $r['val'];
                    if (!$docblock_end_found) {
                        break;
                    }
                    if (!$docblock_start_found || !$name_found || !$description_found || empty($param)) {
                        continue;
                    }
                    if (!empty($param)) {
                        if (in_array($param, $arrayParams)) {
                            if (!isset($params[$param])) {
                                $params[$param] = [];
                            }
                            $params[$param][] = $val;
                        } else {
                            $params[$param] = $val;
                        }
                    }
                }
                @fclose($tpl);
            }
        }
        return $params;
    }

    /**
     * Parses docBlock from string and returns the result as an array
     *
     * @param string $string
     * @return array Associative array in the form property name => property value
     */
    public function parseFromString($string)
    {
        $params = [];
        if (!empty($string)) {
            $string = str_replace(['\r\n',"\n"], '\n', $string);
            $exp = explode('\n', $string);
            $docblock_start_found = false;
            $name_found = false;
            $description_found = false;
            $docblock_end_found = false;
            $arrayParams = ['author', 'documentation', 'reportissues', 'link'];

            foreach ($exp as $line) {
                $r = $this->parseLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found);
                $docblock_start_found = $r['docblock_start_found'];
                $name_found = $r['name_found'];
                $description_found = $r['description_found'];
                $docblock_end_found = $r['docblock_end_found'];
                $param = $r['param'];
                $val = $r['val'];
                if (!$docblock_start_found) {
                    continue;
                }
                if ($docblock_end_found) {
                    break;
                }
                if (!empty($param)) {
                    if (in_array($param, $arrayParams)) {
                        if (!isset($params[$param])) {
                            $params[$param] = [];
                        }
                        $params[$param][] = $val;
                    } else {
                        $params[$param] = $val;
                    }
                }
            }
        }
        return $params;
    }

    /**
     * Parses docBlock of a component´s source-code and returns the result as an array
     * (modified parseDocBlock() from modules/stores/setup.info.php by Bumkaka & Dmi3yy)
     *
     * @param string $line
     * @param boolean $docblock_start_found
     * @param boolean $name_found
     * @param boolean $description_found
     * @param boolean $docblock_end_found
     * @return array Associative array in the form property name => property value
     */
    public function parseLine($line, $docblock_start_found, $name_found, $description_found, $docblock_end_found)
    {
        $param = '';
        $val = '';
        $ma = null;
        if (!$docblock_start_found) {
            // find docblock start
            if (strpos($line, '/**') !== false) {
                $docblock_start_found = true;
            }
        } elseif (!$name_found) {
            // find name
            if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                $param = 'name';
                $val = trim($ma[1]);
                $name_found = !empty($val);
            }
        } elseif (!$description_found) {
            // find description
            if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                $param = 'description';
                $val = trim($ma[1]);
                $description_found = !empty($val);
            }
        } else {
            if (preg_match("/^\s+\*\s+\@([^\s]+)\s+(.+)/", $line, $ma)) {
                $param = trim($ma[1]);
                $val = trim($ma[2]);
                if (!empty($param) && !empty($val)) {
                    if ($param == 'internal') {
                        $ma = null;
                        if (preg_match("/\@([^\s]+)\s+(.+)/", $val, $ma)) {
                            $param = trim($ma[1]);
                            $val = trim($ma[2]);
                        }
                    }
                }
            } elseif (preg_match("/^\s*\*\/\s*$/", $line)) {
                $docblock_end_found = true;
            }
        }
        return [
            'docblock_start_found' => $docblock_start_found,
            'name_found' => $name_found,
            'description_found' => $description_found,
            'docblock_end_found' => $docblock_end_found,
            'param' => $param,
            'val' => $val
        ];
    }

    /**
     * Renders docBlock-parameters into human readable list
     *
     * @param array $parsed
     * @return string List in HTML-format
     */
    public function convertIntoList($parsed)
    {
        global $_lang;

        // Replace special placeholders & make URLs + Emails clickable
        $ph = ['site_url' => EVO_SITE_URL];
        $regexUrl = "/((http|https|ftp|ftps)\:\/\/[^\/]+(\/[^\s]+[^,.?!:;\s])?)/";
        $regexEmail = '#([0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-wyz][a-z](fo|g|l|m|mes|o|op|pa|ro|seum|t|u|v|z)?)#i';
        $emailSubject = isset($parsed['name']) ? '?subject=' . $parsed['name'] : '';
        $emailSubject .= isset($parsed['version']) ? ' v' . $parsed['version'] : '';
        foreach ($parsed as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $key2 => $val2) {
                    $val2 = evo()->parseText($val2, $ph);
                    if (preg_match($regexUrl, $val2, $url)) {
                        $val2 = preg_replace($regexUrl, "<a href=\"{$url[0]}\" target=\"_blank\">{$url[0]}</a> ", $val2);
                    }
                    if (preg_match($regexEmail, $val2, $url)) {
                        $val2 = preg_replace($regexEmail, '<a href="mailto:\\1' . $emailSubject . '">\\1</a>', $val2);
                    }
                    $parsed[$key][$key2] = $val2;
                }
            } else {
                $val = evo()->parseText($val, $ph);
                if (preg_match($regexUrl, $val, $url)) {
                    $val = preg_replace($regexUrl, "<a href=\"{$url[0]}\" target=\"_blank\">{$url[0]}</a> ", $val);
                }
                if (preg_match($regexEmail, $val, $url)) {
                    $val = preg_replace($regexEmail, '<a href="mailto:\\1' . $emailSubject . '">\\1</a>', $val);
                }
                $parsed[$key] = $val;
            }
        }

        $arrayParams = [
            'documentation' => $_lang['documentation'],
            'reportissues' => $_lang['report_issues'],
            'link' => $_lang['further_info'],
            'author' => $_lang['author_infos']
        ];

        $nl = "\n";
        $list = isset($parsed['logo']) ? '<img src="' . EVO_BASE_URL . ltrim($parsed['logo'], "/") . '" style="float:right;max-width:100px;height:auto;" />' . $nl : '';
        $list .= '<p>' . $nl;
        $list .= isset($parsed['name']) ? '<strong>' . $parsed['name'] . '</strong><br/>' . $nl : '';
        $list .= isset($parsed['description']) ? $parsed['description'] . $nl : '';
        $list .= '</p><br/>' . $nl;
        $list .= isset($parsed['version']) ? '<p><strong>' . $_lang['version'] . ':</strong> ' . $parsed['version'] . '</p>' . $nl : '';
        $list .= isset($parsed['license']) ? '<p><strong>' . $_lang['license'] . ':</strong> ' . $parsed['license'] . '</p>' . $nl : '';
        $list .= isset($parsed['lastupdate']) ? '<p><strong>' . $_lang['last_update'] . ':</strong> ' . $parsed['lastupdate'] . '</p>' . $nl : '';
        $list .= '<br/>' . $nl;
        $first = true;
        foreach ($arrayParams as $param => $label) {
            if (isset($parsed[$param])) {
                if ($first) {
                    $list .= '<p><strong>' . $_lang['references'] . '</strong></p>' . $nl;
                    $list .= '<ul class="docBlockList">' . $nl;
                    $first = false;
                }
                $list .= '    <li><strong>' . $label . '</strong>' . $nl;
                $list .= '        <ul>' . $nl;
                foreach ($parsed[$param] as $val) {
                    $list .= '            <li>' . $val . '</li>' . $nl;
                }
                $list .= '        </ul></li>' . $nl;
            }
        }
        $list .= !$first ? '</ul>' . $nl : '';

        return $list;
    }

}
