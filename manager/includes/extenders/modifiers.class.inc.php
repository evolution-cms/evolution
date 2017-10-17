<?php

if(!defined('MODX_CORE_PATH')) define('MODX_CORE_PATH', MODX_MANAGER_PATH.'includes/');

class MODIFIERS {
    
    var $placeholders = array();
    var $vars = array();
    var $tmpCache = array();
    var $bt;
    var $srcValue;
    var $condition = array();
    var $condModifiers;
    
    var $key;
    var $value;
    var $opt;
    
    function __construct()
    {
        global $modx;
        
        if (function_exists('mb_internal_encoding')) mb_internal_encoding($modx->config['modx_charset']);
        $this->condModifiers = '=,is,eq,equals,ne,neq,notequals,isnot,isnt,not,%,isempty,isnotempty,isntempty,>=,gte,eg,gte,greaterthan,>,gt,isgreaterthan,isgt,lowerthan,<,lt,<=,lte,islte,islowerthan,islt,el,find,in,inarray,in_array,fnmatch,wcard,wcard_match,wildcard,wildcard_match,is_file,is_dir,file_exists,is_readable,is_writable,is_image,regex,preg,preg_match,memberof,mo,isinrole,ir';
    }
    
    function phxFilter($key,$value,$modifiers)
    {
        global $modx;
        if(substr($modifiers,0,3)!=='id(') $value = $this->parseDocumentSource($value);
        $this->srcValue = $value;
        $modifiers = trim($modifiers);
        $modifiers = ':'.trim($modifiers,':');
        $modifiers = str_replace(array("\r\n","\r"), "\n", $modifiers);
        $modifiers = $this->splitEachModifiers($modifiers);
        
        $this->placeholders = array();
        $this->placeholders['phx'] = '';
        $this->placeholders['dummy'] = '';
        $this->condition = array();
        $this->vars = array();
        $this->vars['name']    = & $key;
        $value = $this->parsePhx($key,$value,$modifiers);
        $this->vars = array();
        return $value;
    }
    
    function _getDelim($mode,$modifiers) {
        $c = substr($modifiers,0,1);
        if(!in_array($c, array('"', "'", '`')) ) return false;
        
        $modifiers = substr($modifiers,1);
        $closure = $mode=='(' ? "{$c})" : $c;
        if(strpos($modifiers, $closure)===false) return false;
        
        return  $c;
    }
    
    function _getOpt($mode,$delim,$modifiers) {
        if($delim) {
            if($mode=='(') return substr($modifiers,1,strpos($modifiers, $delim . ')' )-1);
            
            return substr($modifiers,1,strpos($modifiers,$delim,1)-1);
        }
        else {
            if($mode=='(') return substr($modifiers,0,strpos($modifiers, ')') );
            
            $chars = str_split($modifiers);
            $opt='';
            foreach($chars as $c) {
                if($c==':' || $c==')') break;
                $opt .=$c;
            }
            return $opt;
        }
    }
    function _getRemainModifiers($mode,$delim,$modifiers) {
        if($delim) {
            if($mode=='(')
                return $this->_fetchContent($modifiers, $delim . ')');
            else {
                $modifiers = trim($modifiers);
                $modifiers = substr($modifiers,1);
                return $this->_fetchContent($modifiers, $delim);
            }
        }
        else {
            if($mode=='(') return $this->_fetchContent($modifiers, ')');
            $chars = str_split($modifiers);
            foreach($chars as $c) {
                if($c==':') return $modifiers;
                else $modifiers = substr($modifiers,1);
            }
            return $modifiers;
        }
    }
    
    function _fetchContent($string,$delim) {
        $len = strlen($delim);
        $string = $this->parseDocumentSource($string);
        return substr($string,strpos($string, $delim)+$len);
    }
    
    function splitEachModifiers($modifiers) {
        global $modx;
        
        $cmd = '';
        $bt = '';
        while($bt!==$modifiers) {
            $bt = $modifiers;
            $c = substr($modifiers,0,1);
            $modifiers = substr($modifiers,1);
            
            if($c===':' && preg_match('@^(!?[<>=]{1,2})@', $modifiers, $match)) { // :=, :!=, :<=, :>=, :!<=, :!>=
                $c = substr($modifiers,strlen($match[1]),1);
                $debuginfo = "#i=0 #c=[{$c}] #m=[{$modifiers}]";
                if($c==='(') $modifiers = substr($modifiers,strlen($match[1])+1);
                else         $modifiers = substr($modifiers,strlen($match[1]));
                
                $delim     = $this->_getDelim($c,$modifiers);
                $opt       = $this->_getOpt($c,$delim,$modifiers);
                $modifiers = trim($this->_getRemainModifiers($c,$delim,$modifiers));
                
                $result[]=array('cmd'=>trim($match[1]),'opt'=>$opt,'debuginfo'=>$debuginfo);
                $cmd = '';
            }
            elseif(in_array($c,array('+','-','*','/')) && preg_match('@^[0-9]+@', $modifiers, $match)) { // :+3, :-3, :*3 ...
                $modifiers = substr($modifiers,strlen($match[0]));
                $result[]=array('cmd'=>'math','opt'=>'%s'.$c.$match[0]);
                $cmd = '';
            }
            elseif($c==='(' || $c==='=') {
                $modifiers = $m1 = trim($modifiers);
                $delim     = $this->_getDelim($c,$modifiers);
                $opt       = $this->_getOpt($c,$delim,$modifiers);
                $modifiers = trim($this->_getRemainModifiers($c,$delim,$modifiers));
                $debuginfo = "#i=1 #c=[{$c}] #delim=[{$delim}] #m1=[{$m1}] remainMdf=[{$modifiers}]";
                
                $result[]=array('cmd'=>trim($cmd),'opt'=>$opt,'debuginfo'=>$debuginfo);
                
                $cmd = '';
            }
            elseif($c==':') {
                $debuginfo = "#i=2 #c=[{$c}] #m=[{$modifiers}]";
                if($cmd!=='') $result[]=array('cmd'=>trim($cmd),'opt'=>'','debuginfo'=>$debuginfo);
                
                $cmd = '';
            }
            elseif(trim($modifiers)=='' && trim($cmd)!=='') {
                $debuginfo = "#i=3 #c=[{$c}] #m=[{$modifiers}]";
                $cmd .= $c;
                $result[]=array('cmd'=>trim($cmd),'opt'=>'','debuginfo'=>$debuginfo);
                
                break;
            }
            else {
                $cmd .= $c;
            }
        }
        
        if(empty($result)) return array();
        
        foreach($result as $i=>$a)
        {
            $a['opt'] = $this->parseDocumentSource($a['opt']);
            $result[$i]['opt'] = $modx->mergePlaceholderContent($a['opt'],$this->placeholders);
        }
        
        return $result;
    }
    
    function parsePhx($key,$value,$modifiers)
    {
        global $modx;
        $cacheKey = md5(sprintf('parsePhx#%s#%s#%s',$key,$value,print_r($modifiers,true)));
        if(isset($this->tmpCache[$cacheKey])) return $this->tmpCache[$cacheKey];
        if(empty($modifiers)) return '';
        
        foreach($modifiers as $m)
        {
            $lastKey = strtolower($m['cmd']);
        }
        $_ = explode(',',$this->condModifiers);
        if(in_array($lastKey,$_))
        {
            $modifiers[] = array('cmd'=>'then','opt'=>'1');
            $modifiers[] = array('cmd'=>'else','opt'=>'0');
        }
        
        foreach($modifiers as $i=>$a)
        {
            $value = $this->Filter($key,$value, $a['cmd'], $a['opt']);
        }
        $this->tmpCache[$cacheKey] = $value;
        return $value;
    }
    
    // Parser: modifier detection and eXtended processing if needed
    function Filter($key, $value, $cmd, $opt='')
    {
        global $modx;
        
        if($key==='documentObject') $value = $modx->documentIdentifier;
        $cmd = $this->parseDocumentSource($cmd);
        if(preg_match('@^[1-9][/0-9]*$@',$cmd))
        {
            if(strpos($cmd,'/')!==false)
                $cmd = $this->substr($cmd,strrpos($cmd,'/')+1);
            $opt = $cmd;
            $cmd = 'id';
        }
        
        if(isset($modx->snippetCache["phx:{$cmd}"]))   $this->elmName = "phx:{$cmd}";
        elseif(isset($modx->chunkCache["phx:{$cmd}"])) $this->elmName = "phx:{$cmd}";
        else                                           $this->elmName = '';
        
        $cmd = strtolower($cmd);
        if($this->elmName!=='')
            $value = $this->getValueFromElement($key, $value, $cmd, $opt);
        else
            $value = $this->getValueFromPreset($key, $value, $cmd, $opt);
        
        $value = str_replace('[+key+]', $key, $value);
        
        return $value;
    }
    
    function isEmpty($cmd,$value)
    {
        if($value!=='') return false;
        
        $_ = explode(',', $this->condModifiers . ',_default,default,if,input,or,and,show,this,select,switch,then,else,id,ifempty,smart_desc,smart_description,summary');
        if(in_array($cmd,$_)) return false;
        else                  return true;
    }
    
    function getValueFromPreset($key, $value, $cmd, $opt)
    {
        global $modx;
        
        if($this->isEmpty($cmd,$value)) return '';
        
        $this->key = $key;
        $this->value  = $value;
        $this->opt    = $opt;
        
        switch ($cmd)
        {
            #####  Conditional Modifiers 
            case 'input':
            case 'if':
                if(!$opt) return $value;
                return $opt;
            case '=':
            case 'eq':
            case 'is':
            case 'equals':
                $this->condition[] = intval($value == $opt); break;
            case 'neq':
            case 'ne':
            case 'notequals':
            case 'isnot':
            case 'isnt':
            case 'not':
                $this->condition[] = intval($value != $opt);break;
            case '%':
                $this->condition[] = intval($value%$opt==0);break;
            case 'isempty':
                $this->condition[] = intval(empty($value)); break;
            case 'isntempty':
            case 'isnotempty':
                $this->condition[] = intval(!empty($value)); break;
            case '>=':
            case 'gte':
            case 'eg':
            case 'isgte':
                $this->condition[] = intval($value >= $opt);break;
            case '<=':
            case 'lte':
            case 'el':
            case 'islte':
                $this->condition[] = intval($value <= $opt);break;
            case '>':
            case 'gt':
            case 'greaterthan':
            case 'isgreaterthan':
            case 'isgt':
                $this->condition[] = intval($value > $opt);break;
            case '<':
            case 'lt':
            case 'lowerthan':
            case 'islowerthan':
            case 'islt':
                $this->condition[] = intval($value < $opt);break;
            case 'find':
                $this->condition[] = intval(strpos($value, $opt)!==false);break;
            case 'inarray':
            case 'in_array':
            case 'in':
                $opt = explode(',', $opt);
                $this->condition[] = intval(in_array($value, $opt)!==false);break;
            case 'wildcard_match':
            case 'wcard_match':
            case 'wildcard':
            case 'wcard':
            case 'fnmatch':
                $this->condition[] = intval(fnmatch($opt, $value)!==false);break;
            case 'is_file':
            case 'is_dir':
            case 'file_exists':
            case 'is_readable':
            case 'is_writable':
                if(!$opt) $path = $value;
                else      $path = $opt;
                if(strpos($path,MODX_MANAGER_PATH)!==false) exit('Can not read core path');
                if(strpos($path,$modx->config['base_path'])===false) $path = ltrim($path,'/');
                $this->condition[] = intval($cmd($path)!==false);break;
            case 'is_image':
                if(!$opt) $path = $value;
                else      $path = $opt;
                if(!is_file($path)) {$this->condition[]='0';break;}
                $_ = getimagesize($path);
                $this->condition[] = intval($_[0]);break;
            case 'regex':
            case 'preg':
            case 'preg_match':
                $this->condition[] = intval(preg_match($opt,$value));break;
            case 'isinrole':
            case 'ir':
            case 'memberof':
            case 'mo':
                // Is Member Of  (same as inrole but this one can be stringed as a conditional)
                $this->condition[] = $this->includeMdfFile('memberof');
                break;
            case 'or':
                $this->condition[] = '||';break;
            case 'and':
                $this->condition[] = '&&';break;
            case 'show':
            case 'this':
                $conditional = join(' ',$this->condition);
                $isvalid = intval(eval("return ({$conditional});"));
                if ($isvalid) return $this->srcValue;
                return NULL;
            case 'then':
                $conditional = join(' ',$this->condition);
                $isvalid = intval(eval("return ({$conditional});"));
                if ($isvalid)  return $opt;
                return null;
            case 'else':
                $conditional = join(' ',$this->condition);
                $isvalid = intval(eval("return ({$conditional});"));
                if (!$isvalid) return $opt;
                break;
            case 'select':
            case 'switch':
                $raw = explode('&',$opt);
                $map = array();
                $c = count($raw);
                for($m=0; $m<$c; $m++) {
                    $mi = explode('=',$raw[$m],2);
                    $map[$mi[0]] = $mi[1];
                }
                if(isset($map[$value])) return $map[$value];
                else                    return '';
            ##### End of Conditional Modifiers
            
            #####  Encode / Decode / Hash / Escape
            case 'htmlent':
            case 'htmlentities':
                return htmlentities($value,ENT_QUOTES,$modx->config['modx_charset']);
            case 'html_entity_decode':
            case 'decode_html':
            case 'html_decode':
                return html_entity_decode($value,ENT_QUOTES,$modx->config['modx_charset']);
            case 'esc':
            case 'escape':
                $value = preg_replace('/&amp;(#[0-9]+|[a-z]+);/i', '&$1;', htmlspecialchars($value, ENT_QUOTES, $modx->config['modx_charset']));
                return str_replace(array('[', ']', '`'),array('&#91;', '&#93;', '&#96;'),$value);
            case 'sql_escape':
            case 'encode_js':
                return $modx->db->escape($value);
            case 'htmlspecialchars':
            case 'hsc':
            case 'encode_html':
            case 'html_encode':
                return preg_replace('/&amp;(#[0-9]+|[a-z]+);/i', '&$1;', htmlspecialchars($value, ENT_QUOTES, $modx->config['modx_charset']));
            case 'spam_protect':
                return str_replace(array('@','.'),array('&#64;','&#46;'),$value);
            case 'strip':
                if($opt==='') $opt = ' ';
                return preg_replace('/[\n\r\t\s]+/', $opt, $value);
            case 'strip_linefeeds':
                return str_replace(array("\n","\r"), '', $value);
            case 'notags':
            case 'strip_tags':
            case 'remove_html':
                if($opt!=='')
                {
                    $param = array();
                    foreach(explode(',',$opt) as $v)
                    {
                        $v = trim($v,'</> ');
                        $param[] = "<{$v}>";
                    }
                    $params = join(',',$param);
                }
                else $params = '';
                if(!strpos($params,'<br>')===false) {
                    $value = preg_replace('@(<br[ /]*>)\n@','$1',$value);
                    $value = preg_replace('@<br[ /]*>@',"\n",$value);
                }
                return $this->strip_tags($value,$params);
            case 'urlencode':
            case 'url_encode':
            case 'encode_url':
                return urlencode($value);
            case 'base64_decode':
                if($opt!=='false') $opt = true;
                else               $opt = false;
                return base64_decode($value,$opt);
            case 'encode_sha1': $cmd = 'sha1';
            case 'addslashes':
            case 'urldecode':
            case 'url_decode':
            case 'rawurlencode':
            case 'rawurldecode':
            case 'base64_encode':
            case 'md5':
            case 'sha1':
            case 'json_encode':
            case 'json_decode':
                return $cmd($value);
            
            #####  String Modifiers
            case 'lcase':
            case 'strtolower':
            case 'lower_case':
                return $this->strtolower($value);
            case 'ucase':
            case 'strtoupper':
            case 'upper_case':
                return $this->strtoupper($value);
            case 'capitalize':
                $_ = explode(' ',$value);
                foreach($_ as $i=>$v)
                {
                    $_[$i] = ucfirst($v);
                }
                return join(' ',$_);
            case 'zenhan':
                if(empty($opt)) $opt='VKas';
                return mb_convert_kana($value,$opt,$modx->config['modx_charset']);
            case 'hanzen':
                if(empty($opt)) $opt='VKAS';
                return mb_convert_kana($value,$opt,$modx->config['modx_charset']);
            case 'str_shuffle':
            case 'shuffle':
                return $this->str_shuffle($value);
            case 'reverse':
            case 'strrev':
                return $this->strrev($value);
            case 'length':
            case 'len':
            case 'strlen':
            case 'count_characters':
                return $this->strlen($value);
            case 'count_words':
                $value = trim($value);
                return count(preg_split('/\s+/',$value));
            case 'str_word_count':
            case 'word_count':
            case 'wordcount':
                return $this->str_word_count($value);
            case 'count_paragraphs':
                $value = trim($value);
                $value = preg_replace('/\r/', '', $value);
                return count(preg_split('/\n+/',$value));
            case 'strpos':
                if($opt!=0&&empty($opt)) return $value;
                return $this->strpos($value,$opt);
            case 'wordwrap':
                // default: 70
                  $wrapat = intval($opt) ? intval($opt) : 70;
                if (version_compare(PHP_VERSION, '5.3.0') >= 0) return $this->includeMdfFile('wordwrap');
                else return preg_replace("@(\b\w+\b)@e","wordwrap('\\1',\$wrapat,' ',1)",$value);
            case 'wrap_text':
                $width = preg_match('/^[1-9][0-9]*$/',$opt) ? $opt : 70;
                if($modx->config['manager_language']==='japanese-utf8') {
                    $chunk = array();
                    $bt='';
                    while($bt!=$value) {
                        $bt = $value;
                        if($this->strlen($value)<$width) {
                            $chunk[] = $value;
                            break;
                        }
                        $chunk[] = $this->substr($value,0,$width);
                        $value = $this->substr($value,$width);
                    }
                    return join("\n",$chunk);
                }
                else
                    return wordwrap($value,$width,"\n",true);
            case 'substr':
                if(empty($opt)) break;
                if(strpos($opt,',')!==false) {
                    list($b,$e) = explode(',',$opt,2);
                    return $this->substr($value,$b,(int)$e);
                }
                else return $this->substr($value,$opt);
            case 'limit':
            case 'trim_to': // http://www.movabletype.jp/documentation/appendices/modifiers/trim_to.html
                if(strpos($opt,'+')!==false)
                    list($len,$str) = explode('+',$opt,2);
                else {
                    $len = $opt;
                    $str = '';
                }
                if($len==='') $len = 100;
                if(abs($len) > $this->strlen($value)) $str ='';
                if(preg_match('/^[1-9][0-9]*$/',$len)) {
                    return $this->substr($value,0,$len) . $str;
                }
                elseif(preg_match('/^\-[1-9][0-9]*$/',$len)) {
                    return $str . $this->substr($value,$len);
                }
                break;
            case 'summary':
            case 'smart_description':
            case 'smart_desc':
                return $this->includeMdfFile('summary');
            case 'replace':
            case 'str_replace':
                if(empty($opt) || strpos($opt,',')===false) break;
                if    (substr_count($opt, ',') ==1) $delim = ',';
                elseif(substr_count($opt, '|') ==1) $delim = '|';
                elseif(substr_count($opt, '=>')==1) $delim = '=>';
                elseif(substr_count($opt, '/') ==1) $delim = '/';
                else break;
                list($s,$r) = explode($delim,$opt);
                if($value!=='') return str_replace($s,$r,$value);
                break;
            case 'replace_to':
            case 'tpl':
                if($value!=='') return str_replace(array('[+value+]','[+output+]','{value}','%s'),$value,$opt);
                break;
            case 'eachtpl':
                $value = explode('||',$value);
                $_ = array();
                foreach($value as $v) {
                    $_[] = str_replace(array('[+value+]','[+output+]','{value}','%s'),$v,$opt);
                }
                return join("\n", $_);
            case 'array_pop':
            case 'array_shift':
                if(strpos($value,'||')!==false) $delim = '||';
                else                            $delim = ',';
                return $cmd(explode($delim,$value));
            case 'preg_replace':
            case 'regex_replace':
                if(empty($opt) || strpos($opt,',')===false) break;
                list($s,$r) = explode(',',$opt,2);
                if($value!=='') return preg_replace($s,$r,$value);
                break;
            case 'cat':
            case 'concatenate':
            case '.':
                if($value!=='') return $value . $opt;
                break;
            case 'sprintf':
            case 'string_format':
                if($value!=='') return sprintf($opt,$value);
                break;
            case 'number_format':
                    if($opt=='') $opt = 0;
                    return number_format($value,$opt);
            case 'money_format':
                    setlocale(LC_MONETARY,setlocale(LC_TIME,0));
                    if($value!=='') return money_format($opt,(double)$value);
                    break;
            case 'tobool':
                return boolval($value);
            case 'nl2lf':
                if($value!=='') return str_replace(array("\r\n","\n", "\r"), '\n', $value);
                break;
            case 'br2nl':
                return preg_replace('@<br[\s/]*>@i', "\n", $value);
            case 'nl2br':
                if (version_compare(PHP_VERSION, '5.3.0', '<'))
                    return nl2br($value);
                if($opt!=='')
                {
                    $opt = trim($opt);
                    $opt = strtolower($opt);
                    if($opt==='false') $opt = false;
                    elseif($opt==='0') $opt = false;
                    else               $opt = true;
                }
                elseif(isset($modx->config['mce_element_format'])&&$modx->config['mce_element_format']==='html')
                                       $opt = false;
                else                   $opt = true;
                return nl2br($value,$opt);
            case 'ltrim':
            case 'rtrim':
            case 'trim': // ref http://mblo.info/modifiers/custom-modifiers/rtrim_opt.html
                if($opt==='')
                    return $cmd($value);
                else return $cmd($value,$opt);
            // These are all straight wrappers for PHP functions
            case 'ucfirst':
            case 'lcfirst':
            case 'ucwords':
                return $cmd($value);
            
            #####  Date time format
            case 'strftime':
            case 'date':
            case 'dateformat':
                if(empty($opt)) $opt = $modx->toDateFormat(null, 'formatOnly');
                if(!preg_match('@^[0-9]+$@',$value)) $value = strtotime($value);
                if(strpos($opt,'%')!==false)
                    return strftime($opt,0+$value);
                else
                    return date($opt,0+$value);
            case 'time':
                if(empty($opt)) $opt = '%H:%M';
                if(!preg_match('@^[0-9]+$@',$value)) $value = strtotime($value);
                return strftime($opt,0+$value);
            case 'strtotime':
                return strtotime($value);
            #####  mathematical function
            case 'toint':
                return intval($value);
            case 'tofloat':
                return floatval($value);
            case 'round':
                if(!$opt) $opt = 0;
                return $cmd($value,$opt);
            case 'max':
            case 'min':
                return $cmd(explode(',',$value));
            case 'floor':
            case 'ceil':
            case 'abs':
                return $cmd($value);
            case 'math':
            case 'calc':
                $value = (int)$value;
                if(empty($value)) $value = '0';
                $filter = str_replace(array('[+value+]','[+output+]','{value}','%s'),'?',$opt);
                $filter = preg_replace('@([a-zA-Z\n\r\t\s])@','',$filter);
                if(strpos($filter,'?')===false) $filter = "?{$filter}";
                $filter = str_replace('?',$value,$filter);
                return eval("return {$filter};");
            case 'count':
                if($value=='') return 0;
                $value = explode(',',$value);
                return count($value);
            case 'sort':
            case 'rsort':
                if(strpos($value,"\n")!==false) $delim="\n";
                else $delim = ',';
                $swap = explode($delim,$value);
                if(!$opt) $opt = SORT_REGULAR;
                else      $opt = constant($opt);
                $cmd($swap,$opt);
                return join($delim,$swap);
            #####  Resource fields
            case 'id':
                if($opt) return $this->getDocumentObject($opt,$key);
                break;
            case 'type':
            case 'contenttype':
            case 'pagetitle':
            case 'longtitle':
            case 'description':
            case 'alias':
            case 'introtext':
            case 'link_attributes':
            case 'published':
            case 'pub_date':
            case 'unpub_date':
            case 'parent':
            case 'isfolder':
            case 'content':
            case 'richtext':
            case 'template':
            case 'menuindex':
            case 'searchable':
            case 'cacheable':
            case 'createdby':
            case 'createdon':
            case 'editedby':
            case 'editedon':
            case 'deleted':
            case 'deletedon':
            case 'deletedby':
            case 'publishedon':
            case 'publishedby':
            case 'menutitle':
            case 'donthit':
            case 'haskeywords':
            case 'privateweb':
            case 'privatemgr':
            case 'content_dispo':
            case 'hidemenu':
                if($cmd==='contenttype') $cmd = 'contentType';
                return $this->getDocumentObject($value,$cmd);
            case 'title':
                $pagetitle = $this->getDocumentObject($value,'pagetitle');
                $longtitle = $this->getDocumentObject($value,'longtitle');
                return $longtitle ? $longtitle : $pagetitle;
            case 'shorttitle':
                $pagetitle = $this->getDocumentObject($value,'pagetitle');
                $menutitle = $this->getDocumentObject($value,'menutitle');
                return $menutitle ? $menutitle : $pagetitle;
            case 'templatename':
                $rs = $modx->db->select('templatename','[+prefix+]site_templates',"id='{$value}'");
                $templateName = $modx->db->getValue($rs);
                return !$templateName ? '(blank)' : $templateName;
            case 'getfield':
                if(!$opt) $opt = 'content';
                return $modx->getField($opt,$value);
            case 'children':
            case 'childids':
                if($value=='') $value = 0; // 値がない場合はルートと見なす
                $published = 1;
                if($opt=='') $opt = 'page';
                $_ = explode(',',$opt);
                $where = array();
                foreach($_ as $opt) {
                    switch(trim($opt)) {
                        case 'page'; case '!folder'; case '!isfolder': $where[] = 'sc.isfolder=0'; break;
                        case 'folder'; case 'isfolder':                $where[] = 'sc.isfolder=1'; break;
                        case  'menu';  case  'show_menu':              $where[] = 'sc.hidemenu=0'; break;
                        case '!menu';  case '!show_menu':              $where[] = 'sc.hidemenu=1'; break;
                        case  'published':                             $published = 1; break;
                        case '!published':                             $published = 0; break;
                    }
                }
                $where = join(' AND ', $where);
                $children = $modx->getDocumentChildren($value, $published, '0', 'id', $where);
                $result = array();
                foreach((array)$children as $child){
                    $result[] = $child['id'];
                }
                return join(',', $result);
            case 'fullurl':
                if(!is_numeric($value)) return $value;
                return $modx->makeUrl($value);
            case 'makeurl':
                if(!is_numeric($value)) return $value;
                if(!$opt) $opt = 'full';
                return $modx->makeUrl($value,'','',$opt);
                
            #####  File system
            case 'getimageinfo':
            case 'imageinfo':
                if(!is_file($value)) return '';
                $_ = getimagesize($value);
                if(!$_[0]) return '';
                $info['width']  = $_[0];
                $info['height'] = $_[1];
                if    ($_[0] > $_[1]) $info['aspect'] = 'landscape';
                elseif($_[0] < $_[1]) $info['aspect'] = 'portrait';
                else                  $info['aspect'] = 'square';
                switch($_[2]) {
                    case IMAGETYPE_GIF  : $info['type'] = 'gif'; break;
                    case IMAGETYPE_JPEG : $info['type'] = 'jpg'; break;
                    case IMAGETYPE_PNG  : $info['type'] = 'png'; break;
                    default             : $info['type'] = 'unknown';
                }
                $info['attrib'] = $_[3];
                switch($opt) {
                    case 'width' : return $info['width'];
                    case 'height': return $info['height'];
                    case 'aspect': return $info['aspect'];
                    case 'type'  : return $info['type'];
                    case 'attrib': return $info['attrib'];
                    default      : return print_r($info,true);
                }
            
            case 'file_get_contents':
            case 'readfile':
                if(!is_file($value)) return $value;
                $value = realpath($value);
                if(strpos($value,MODX_MANAGER_PATH)!==false) exit('Can not read core file');
                $ext = strtolower(substr($value,-4));
                if($ext==='.php') exit('Can not read php file');
                if($ext==='.cgi') exit('Can not read cgi file');
                return file_get_contents($value);
            case 'filesize':
                if($value == '') return '';
                $filename = $value;
                
                $site_url = $modx->config['site_url'];
                if(strpos($filename,$site_url) === 0)
                    $filename = substr($filename,0,strlen($site_url));
                $filename = trim($filename,'/');
                
                $opt = trim($opt,'/');
                if($opt!=='') $opt .= '/';
                
                $filename = MODX_BASE_PATH.$opt.$filename;
                
                if(is_file($filename)){
                    clearstatcache();
                    $size = filesize($filename);
                    return $size;
                }
                else return '';
            #####  User info
            case 'username':
            case 'fullname':
            case 'role':
            case 'email':
            case 'phone': 
            case 'mobilephone': 
            case 'blocked':
            case 'blockeduntil':
            case 'blockedafter':
            case 'logincount':
            case 'lastlogin':
            case 'thislogin':
            case 'failedlogincount':
            case 'dob':
            case 'gender':
            case 'country':
            case 'street':
            case 'city':
            case 'state':
            case 'zip':
            case 'fax':
            case 'photo':
            case 'comment':
                $this->opt = $cmd;
                return $this->includeMdfFile('moduser');
            case 'userinfo':
                if(empty($opt)) $this->opt = 'username';
                return $this->includeMdfFile('moduser');
            case 'webuserinfo':
                if(empty($opt)) $this->opt = 'username';
                $this->value = -$value;
                return $this->includeMdfFile('moduser');
            #####  Special functions 
            case 'ifempty':
            case '_default':
            case 'default':
                if (empty($value)) return $opt; break;
            case 'ifnotempty':
                if (!empty($value)) return $opt; break;
            case 'datagrid':
                include_once(MODX_CORE_PATH . 'controls/datagrid.class.php');
                $grd = new DataGrid();
                $grd->ds = trim($value);
                $grd->itemStyle = '';
                $grd->altItemStyle = '';
                $pos = strpos($value,"\n");
                if($pos) $_ = substr($value,0,$pos);
                else $_ = $pos;
                $grd->cdelim = strpos($_,"\t")!==false ? 'tab' : ',';
                return $grd->render();
            case 'rotate':
            case 'evenodd':
                if(strpos($opt,',')===false) $opt = 'odd,even';
                $_ = explode(',', $opt);
                $c = count($_);
                $i = $value + $c;
                $i = $i % $c;
                return $_[$i];
            case 'takeval':
                $arr = explode(",",$opt);
                $idx = $value;
                if(!is_numeric($idx)) return $value;
                return $arr[$idx];
            case 'getimage':
                return $this->includeMdfFile('getimage');
            case 'nicesize':
                    return $modx->nicesize($value);
            case 'googlemap':
            case 'googlemaps':
                if(empty($opt)) $opt = 'border:none;width:500px;height:350px;';
                $tpl = '<iframe style="[+style+]" src="https://maps.google.co.jp/maps?ll=[+value+]&output=embed&z=15"></iframe>';
                $ph['style'] = $opt;
                $ph['value'] = $value;
                return $modx->parseText($tpl,$ph);
            case 'youtube':
            case 'youtube16x9':
                if(empty($opt)) $opt = 560;
                $h = round($opt*0.5625);
                $tpl = '<iframe width="%s" height="%s" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>';
                return sprintf($tpl,$opt,$h,$value);
            //case 'youtube4x3':%s*0.75＋25
            case 'setvar':
                $modx->placeholders[$opt] = $value;
                return '';
            case 'csstohead':
                $modx->regClientCSS($value);
                return '';
            case 'htmltohead':
                $modx->regClientStartupHTMLBlock($value);
                return '';
            case 'htmltobottom':
                $modx->regClientHTMLBlock($value);
                return '';
            case 'jstohead':
                $modx->regClientStartupScript($value);
                return '';
            case 'jstobottom':
                $modx->regClientScript($value);
                return '';
            case 'dummy':
                return $value;
                
            // If we haven't yet found the modifier, let's look elsewhere
            default:
                $value = $this->getValueFromElement($key, $value, $cmd, $opt);
        }
        return $value;
    }

    function includeMdfFile($cmd) {
        global $modx;
        $key = $this->key;
        $value  = $this->value;
        $opt    = $this->opt;
        return include(MODX_CORE_PATH."extenders/modifiers/mdf_{$cmd}.inc.php");
    }
    
    function getValueFromElement($key, $value, $cmd, $opt)
    {
        global $modx;
        if( isset($modx->snippetCache[$this->elmName]) )
        {
            $php = $modx->snippetCache[$this->elmName];
        }
        else
        {
            $esc_elmName = $modx->db->escape($this->elmName);
            $result = $modx->db->select('snippet','[+prefix+]site_snippets',"name='{$esc_elmName}'");
            $total = $modx->db->getRecordCount($result);
            if($total == 1)
            {
                $row = $modx->db->getRow($result);
                $php = $row['snippet'];
            }
            elseif($total == 0)
            {
                $assets_path = MODX_BASE_PATH.'assets/';
                if(is_file($assets_path."modifiers/mdf_{$cmd}.inc.php"))
                    $modifiers_path = $assets_path."modifiers/mdf_{$cmd}.inc.php";
                elseif(is_file($assets_path."plugins/phx/modifiers/{$cmd}.phx.php"))
                    $modifiers_path = $assets_path."plugins/phx/modifiers/{$cmd}.phx.php";
                elseif(is_file(MODX_CORE_PATH."extenders/modifiers/mdf_{$cmd}.inc.php"))
                    $modifiers_path = MODX_CORE_PATH."extenders/modifiers/mdf_{$cmd}.inc.php";
                else $modifiers_path = false;
                
                if($modifiers_path) {
                    $php = @file_get_contents($modifiers_path);
                    $php = trim($php);
                    if(substr($php,0,5)==='<?php') $php = substr($php,6);
                    if(substr($php,0,2)==='<?')    $php = substr($php,3);
                    if(substr($php,-2)==='?>')     $php = substr($php,0,-2);
                    if($this->elmName!=='')
                        $modx->snippetCache[$this->elmName.'Props'] = '';
                }
                else
                    $php = false;
            }
            else $php = false;
            if($this->elmName!=='') $modx->snippetCache[$this->elmName]= $php;
        }
        if($php==='') $php=false;
        
        if($php===false) $html = $modx->getChunk($this->elmName);
        else             $html = false;

        $self = '[+output+]';
        
        if($php !== false)
        {
            ob_start();
            $options = $opt;
            $output = $value;
            $name   = $key;
            $this->bt = $value;
            $this->vars['value']   = & $value;
            $this->vars['input']   = & $value;
            $this->vars['option']  = & $opt;
            $this->vars['options'] = & $opt;
            $custom = eval($php);
            $msg = ob_get_contents();
            if($value===$this->bt) $value = $msg . $custom;
            ob_end_clean();
        }
        elseif($html!==false && isset($value) && $value!=='')
        {
            $html = str_replace(array($self,'[+value+]'), $value, $html);
            $value = str_replace(array('[+options+]','[+param+]'), $opt, $html);
        }
        else return false;
        
        if($php===false && $html===false && $value!==''
           && (strpos($cmd,'[+value+]')!==false || strpos($cmd,$self)!==false))
        {
            $value = str_replace(array('[+value+]',$self),$value,$cmd);
        }
        return $value;
    }
    
    function parseDocumentSource($content='')
    {
        global $modx;
        
        if(strpos($content,'[')===false && strpos($content,'{')===false) return $content;
        
        if(!$modx->maxParserPasses) $modx->maxParserPasses = 10;
        $bt='';
        $i=0;
        while($bt!==$content)
        {
            $bt = $content;
            if(strpos($content,'[*')!==false && $modx->documentIdentifier)
                                              $content = $modx->mergeDocumentContent($content);
            if(strpos($content,'[(')!==false) $content = $modx->mergeSettingsContent($content);
            if(strpos($content,'{{')!==false) $content = $modx->mergeChunkContent($content);
            if(strpos($content,'[!')!==false) $content = str_replace(array('[!','!]'),array('[[',']]'),$content);
            if(strpos($content,'[[')!==false) $content = $modx->evalSnippets($content);
            
            if($content===$bt)              break;
            if($modx->maxParserPasses < $i) break;
            $i++;
        }
        return $content;
    }
    
    function getDocumentObject($target='',$field='pagetitle')
    {
        global $modx;
        
        $target = trim($target);
        if(empty($target)) $target = $modx->config['site_start'];
        if(preg_match('@^[1-9][0-9]*$@',$target)) $method='id';
        else $method = 'alias';

        if(!isset($this->documentObject[$target]))
        {
            $this->documentObject[$target] = $modx->getDocumentObject($method,$target,'direct');
        }
        
        if($this->documentObject[$target]['publishedon']==='0')
            return '';
        elseif(isset($this->documentObject[$target][$field]))
        {
            if(is_array($this->documentObject[$target][$field]))
            {
                $a = $modx->getTemplateVarOutput($field,$target);
                $this->documentObject[$target][$field] = $a[$field];
            }
        }
        else $this->documentObject[$target][$field] = false;
        
        return $this->documentObject[$target][$field];
    }
    
    function setPlaceholders($value = '', $key = '', $path = '') {
        if($path!=='') $key = "{$path}.{$key}";
        if (is_array($value)) {
            foreach ($value as $subkey => $subval) {
                $this->setPlaceholders($subval, $subkey, $key);
            }
        }
        else $this->setModifiersVariable($key, $value);
    }
    
    // Sets a placeholder variable which can only be access by Modifiers
    function setModifiersVariable($key, $value) {
        if ($key != 'phx' && $key != 'dummy') $this->placeholders[$key] = $value;
    }
    
    //mbstring
    function substr($str, $s, $l = null) {
        global $modx;
        if(is_null($l)) $l = $this->strlen($str);
        if (function_exists('mb_substr'))
        {
            if(strpos($str,"\r")!==false)
                $str = str_replace(array("\r\n","\r"), "\n", $str);
            return mb_substr($str, $s, $l, $modx->config['modx_charset']);
        }
        return substr($str, $s, $l);
    }
    function strpos($haystack,$needle,$offset=0) {
        global $modx;
        if (function_exists('mb_strpos')) return mb_strpos($haystack,$needle,$offset,$modx->config['modx_charset']);
        return strpos($haystack,$needle,$offset);
    }
    function strlen($str) {
        global $modx;
        if (function_exists('mb_strlen')) return mb_strlen(str_replace("\r\n", "\n", $str),$modx->config['modx_charset']);
        return strlen($str);
    }
    function strtolower($str) {
        if (function_exists('mb_strtolower')) return mb_strtolower($str);
        return strtolower($str);
    }
    function strtoupper($str) {
        if (function_exists('mb_strtoupper')) return mb_strtoupper($str);
        return strtoupper($str);
    }
    function ucfirst($str) {
        if (function_exists('mb_strtoupper')) 
            return mb_strtoupper($this->substr($str, 0, 1)).$this->substr($str, 1, $this->strlen($str));
        return ucfirst($str);
    }
    function lcfirst($str) {
        if (function_exists('mb_strtolower')) 
            return mb_strtolower($this->substr($str, 0, 1)).$this->substr($str, 1, $this->strlen($str));
        return lcfirst($str);
    }
    function ucwords($str) {
        if (function_exists('mb_convert_case'))
            return mb_convert_case($str, MB_CASE_TITLE);
        return ucwords($str);
    }
    function strrev($str) {
        preg_match_all('/./us', $str, $ar);
        return join(array_reverse($ar[0]));
    }
    function str_shuffle($str) {
        preg_match_all('/./us', $str, $ar);
        shuffle($ar[0]);
        return join($ar[0]);
    }
    function str_word_count($str) {
        return count(preg_split('~[^\p{L}\p{N}\']+~u',$str));
    }
    function strip_tags($value,$params='') {
        global $modx;

        if(stripos($params,'style')===false && stripos($value,'</style>')!==false) {
            $value = preg_replace('@<style.*?>.*?</style>@is', '', $value);
        }
        if(stripos($params,'script')===false && stripos($value,'</script>')!==false) {
            $value = preg_replace('@<script.*?>.*?</script>@is', '', $value);        
        }
  
        return trim(strip_tags($value,$params));
    }
}
