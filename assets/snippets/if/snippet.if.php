<?php
/**
 * if
 *
 * A simple conditional snippet. Allows for eq/neq/lt/gt/etc logic within templates, resources, chunks, etc.
 *
 * @category    snippet
 * @version     1.4
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal    @properties
 * @internal    @modx_category Navigation
 * @internal    @installset base
 * @documentation Readme [+site_url+]assets/snippets/if/readme.html
 * @reportissues https://github.com/modxcms/evolution
 * @author      Created By Bumkaka bumkaka@yandex.ru
 * @lastupdate  21/05/2017
 */
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

$s = isset($separator) ? $separator: ':';
$math = isset($math) ? $math : 'off';
$lp = 0;
$opers=explode($s,$is);
$subject=$opers[0];
$eq=true;
$and=false;
$or = false;
$else = isset($else) ? $else : '';
// Prepare custom conditions
$customConditions = array();
if(!empty($custom)) {
    $snippetPath = realpath(dirname(__FILE__));
    $cc = explode(',', $custom);
    foreach($cc as $cCond) {
        if(function_exists($cCond)) {
            $customConditions[$cCond] = true;
        } else {
            $ccFile = $snippetPath.'/custom/if.'.$cCond.'.php';
            if(is_file($ccFile)) {
                include($ccFile);
                $customConditions[$cCond] = true;
            }
        }
    }
}

for ($i=1;$i<count($opers);$i++){
    if ($opers[$i]=='or') {$or=true;$part_eq=$eq;$eq=true;continue;}
    if ($or) {$subject=$opers[$i];$or=false;continue;}

    if ($opers[$i]=='and') {
        $lp=1;
        $and=true;
        if (!empty($part_eq)){if ($part_eq||$eq){$left_part=true;}} else {$left_part=$eq?true:false;}
        $eq=true;unset($part_eq);
        continue;
    }
    if ($and) {$subject=$opers[$i];$and=false;continue;}

    $operator = $opers[$i];
    $operand  = isset($opers[$i+1]) ? $opers[$i+1] : '';

    if (!isset($subject)) continue;
    if (empty($operator)) continue;

    if(isset($customConditions[$operator])) {
        $output = call_user_func($operator, $subject, $operand);
        if(is_array($output)) {
            if(isset($output[1]) && $output[1]) $i++; // $operand was used
            $output = $output[0];
        }
        $output = (bool)$output;
        $operator = 'custom';
    }

    if ($math=='on' && !empty($subject)) {
        $subject = preg_replace('@([a-zA-Z\n\r\t\s])@','',$subject);
        $subject = $modx->safeEval('return ' . $subject.';');
    }
    $operator = strtolower($operator);

    if(preg_match('/^(\[\+(.*?)\+\])$/i', $subject)) $subject = '';
    
    switch ($operator) {
        case '%':
            $output = ($subject %$operand==0) ? true: false;$i++;
            break;
        case '!=':
        case 'not':$output = ($subject != $operand) ? true: false;$i++;
            break;
        case '<':
        case 'lt':$output = ($subject < $operand) ? true : false;$i++;
            break;
        case '>':
        case 'gt':$output = ($subject > $operand) ? true : false;$i++;
            break;
        case '<=':
        case 'lte':$output = ($subject <= $operand) ? true : false;$i++;
            break;
        case '>=':
        case 'gte':$output = ($subject >= $operand) ? true : false;$i++;
            break;
        case 'isempty':
        case 'empty':$output = empty($subject) ? true : false;
            break;
        case '!empty':
        case 'notempty':
        case 'not_empty':
        case 'isnotempty':$output = empty($subject) || $subject == '' ? false : true;
            break;
        case 'isnull':
        case 'null':$output = $subject == null || strtolower($subject) == 'null' ? true : false;
            break;
        case 'inarray':
        case 'in_array':
        case 'in':
            $operand = explode(',',$operand);
            $output = in_array($subject,$operand) ? true : false;
            $i++;
            break;
        case 'not_in':
        case '!in':
        case '!inarray':
            $operand = explode(',',$operand);
            $output = in_array($subject,$operand) ? false : true;
            $i++;
            break;
        case 'contains':
            $output = (strpos($subject,$operand) !== false) ? true : false;
            $i++;
            break;
        case '!contains':
        case 'not_contains':
            $output = (strpos($subject,$operand) !== false) ? false : true;
            $i++;
            break;
        case 'custom':
            break;
        case '==':
        case '=':
        case 'eq':
        case 'is':
        default:
            $output = ((string)$subject == (string)$operand) ? true : false;
            $i++;
            break;
    }

    $eq=$output?$eq:false;
}
if ($lp==1){
    if ($left_part) {
        if (!empty($part_eq)){
            if ($part_eq||$eq){$output=$then;}
        } else {
            $output=$eq?$then:$else;
        }
    }
    else
    {
        $output=$else;
    }
} else {
    if (!empty($part_eq)){
        if ($part_eq||$eq){
            $output=$then;
        }
    } else {$output=$eq?$then:$else;}
}
if (strpos($output,'@TPL:')!==FALSE){$output='{{'.(str_replace('@TPL:','',$output)).'}}';}

if (substr($output,0,6) == "@eval:") {
    $output = $modx->safeEval(substr($output,6));
}
if (empty($then)&&empty($else)&&$math=='on') {
    return $modx->safeEval('return '.$subject.';');
}

return $output;
