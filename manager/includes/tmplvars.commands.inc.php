<?php
/*
 * Template Variable Data Source @Bindings
 * Created by Raymond Irving Feb, 2005
 */
global $BINDINGS; // Array of supported bindings. must be upper case
$BINDINGS = array (
    'FILE',
    'CHUNK',
    'DOCUMENT',
    'SELECT',
    'EVAL',
    'INHERIT',
    'DIRECTORY'
);

function ProcessTVCommand($value, $name = '', $docid = '', $src='docform') {
    global $modx;
    $etomite = & $modx;
    $docid = intval($docid) ? intval($docid) : $modx->documentIdentifier;
    $nvalue = trim($value);
    if (substr($nvalue, 0, 1) != '@')
        return $value;
    elseif(isset($modx->config['enable_bindings']) && $modx->config['enable_bindings']!=1 && $src==='docform') {
        return '@Bindings is disabled.';
    }
    else {
        list ($cmd, $param) = ParseCommand($nvalue);
        $cmd = trim($cmd);
        switch ($cmd) {
            case "FILE" :
                $output = ProcessFile(trim($param));
                $output = str_replace('@FILE ' . $param, $output, $nvalue);
                break;

            case "CHUNK" : // retrieve a chunk and process it's content
                $chunk = $modx->getChunk($param);
                $output = $chunk;
                break;

            case "DOCUMENT" : // retrieve a document and process it's content
                $rs = $modx->getDocument($param);
                if (is_array($rs))
                    $output = $rs['content'];
                else
                    $output = "Unable to locate document $param";
                break;

            case "SELECT" : // selects a record from the cms database
                $rt = array ();
                $replacementVars = array (
                    'DBASE' => $modx->db->config['dbase'],
                    'PREFIX' => $modx->db->config['table_prefix']
                );
                foreach ($replacementVars as $rvKey => $rvValue) {
                    $modx->setPlaceholder($rvKey, $rvValue);
                }
                $param = $modx->mergePlaceholderContent($param);
                $rs = $modx->db->query("SELECT $param;");
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
                    if ($doc['parent'] != 0 && !$doc['published'])
                        continue; // hide unpublished docs if we're not at the top

                    $tv = $modx->getTemplateVar($name, '*', $doc['id'], $doc['published']);

                    // if an inherited value is found and if there is content following the @INHERIT binding
                    // remove @INHERIT and output that following content. This content could contain other 
                    // @ bindings, that are processed in the next step                    
                    if ((string) $tv['value'] !== '' && !preg_match('%^@INHERIT[\s\n\r]*$%im', $tv['value'])) {
                        $output = trim(str_replace('@INHERIT', '', (string) $tv['value']));
                        break 2;
                    }
                }
                break;

            case 'DIRECTORY' :
                $files = array ();
                $path = $modx->config['base_path'] . $param;
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
        return is_string($output) && ($output != $value) ? ProcessTVCommand($output, $name, $docid, $src) : $output;
    }
}

function ProcessFile($file) {
    // get the file
    if (file_exists($file) && @ $handle = fopen($file, "r")) {
        $buffer = "";
        while (!feof($handle)) {
            $buffer .= fgets($handle, 4096);
        }
        fclose($handle);
    } else {
        $buffer = " Could not retrieve document '$file'.";
    }
    return $buffer;
}

// ParseCommand - separate @ cmd from params
function ParseCommand($binding_string)
{
    global $BINDINGS;
    $binding_array = array();
    foreach($BINDINGS as $cmd)
    {
        if(strpos($binding_string,'@'.$cmd)===0)
        {
            $code = substr($binding_string,strlen($cmd)+1);
            $binding_array = array($cmd,$code);
            break;
        }
    }
    return $binding_array;
}
