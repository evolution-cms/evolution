<?php
/**
 * @name TransAlias
 * @desc Handle the task of loading transliteration tables and applying them
 *      to a string for the purpose of creating a friendly URL alias.
 * @package modx
 * @subpackage modx.plugins.transalias
 * @author Olivier B. Deland
 * @license GNU General Public License
 */
class TransAlias {
    /**
     * name of the file to use for transliteration (without .php)
     * also used as array key
     *
     * @var string
     * @access private
     */
    var $_useTable;
    
    /**
     * hold conversion tables
     *
     * @var array
     * @access 
     */
    var $_tables = array ('named' => array (
            'quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;','OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;',
            'Yuml' => '&#376;','circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;',
            'lrm' => '&#8206;','rlm' => '&#8207;','ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;',
            'rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;','Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;',
            'fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;','Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;',
            'Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;','Xi' => '&#926;','Omicron' => '&#927;',
            'Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;',
            'Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;',
            'theta' => '&#952;','iota' => '&#953;','kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;',
            'pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;','tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;',
            'psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;','hellip' => '&#8230;','prime' => '&#8242;',
            'Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;','alefsym' => '&#8501;',
            'larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;',
            'rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;',
            'isin' => '&#8712;','notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;',
            'prop' => '&#8733;','infin' => '&#8734;','ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;',
            'there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;','ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;',
            'sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;','otimes' => '&#8855;','perp' => '&#8869;',
            'sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;','loz' => '&#9674;',
            'spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => '&#160;','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;',
            'curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;',
            'not' => '&#172;','shy' => '&#173;','reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;',
            'acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;','cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;',
            'frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;','Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;',
            'Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;','Ecirc' => '&#202;','Euml' => '&#203;',
            'Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;','Oacute' => '&#211;',
            'Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;',
            'Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;',
            'auml' => '&#228;','aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;',
            'igrave' => '&#236;','iacute' => '&#237;','icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;',
            'ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;','divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;',
            'uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;'
    ));

    /**
     * Swap named HTML entities with numeric entities.
     *
     * @see *deadlink*http://www.lazycat.org/software/html_entity_decode_full.phps
     */
    function convert_entity($matches, $destroy= true) {
         if (isset($this->$_tables['named'][$matches[1]]))
            return $this->$_tables['named'][$matches[1]];
        else
            return $destroy ? '' : $matches[0];
    }

    /**
     * return the value of a POSTed TV
     * TV may be used to override the default or hard-configured transliteration table
     *
     * @param string $tv name of Template Variable
     * @return value of TV in current POST operation or false if TV not found in POST
     */
    function getTVValue($tv) {
        $modx =& $GLOBALS['modx'];
        $additionalEncodings = array('-' => '%2D', '.' => '%2E', '_' => '%5F');
        $tvname = str_replace(array_keys($additionalEncodings), array_values($additionalEncodings), rawurlencode($tv));
        if(array_key_exists('tv'.$tvname, $_POST)) {
            include_once $GLOBALS['modx']->config['site_manager_path'] . 'includes/tmplvars.commands.inc.php';
            $val = $_POST['tv'.$tvname];
            $id = $_POST['id'];
            if($val == '@INHERIT' && empty($_POST['id']) && !empty($_POST['parent'])) {
                // we have to look at parent directly
                $id = $_POST['parent'];
                $parent = $modx->getPageInfo($_POST['parent'], 0, 'id,parent,published');
                $ptv = $modx->getTemplateVar($tv, '*', $parent['id'], $parent['published']);
                $val = $ptv['value'];
            }
            $return = ProcessTVCommand($val, $tv, $id);
    		return $return;
    	} else {
    		return false;
    	}
    }

    /**
     * load a transliteration table from a file and use it
     *
     * @param string $name
     * @return bool success
     */
    function loadTable($name, $remove_periods = 'No') {
        if (empty($name) || !is_string($name)) {
            return false;
        }
        if (isset ($this->_tables[$name] )) {
            $this->_useTable = $name;
            return true;
        } else {
            $filePath = dirname(__FILE__).'/transliterations/'.$name.'.php';
            if (is_file($filePath)) {
            	$table = include $filePath;
            	if ($remove_periods == 'Yes') {
            		$table['.'] = '';
            	}
                $this->_tables[$name] = $table;
                if (is_array($this->_tables[$name]) && !empty($this->_tables[$name])) {
                    $this->_useTable = $name;
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * perform transliteration and clean up alias
     *
     * @param string $alias
     * @return string alias
     */
    function stripAlias($alias,$char_restrict,$word_separator) {
        // Convert all named HTML entities to numeric entities
        $alias = preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]{1,7});/', array($this,'convert_entity'), $alias);
        
        // Convert all numeric entities to their actual character
        $alias = preg_replace('/&#x([0-9a-f]{1,7});/ei', 'chr(hexdec("\\1"))', $alias);
        $alias = preg_replace('/&#([0-9]{1,7});/e', 'chr("\\1")', $alias);
        
        if (!empty($this->_useTable)) {
            $alias = strtr($alias, $this->_tables[$this->_useTable]);
        }

        $alias = strip_tags($alias); // strip HTML
        if($char_restrict=='lowercase alphanumeric') {
            $alias = preg_replace('/[^\.%A-Za-z0-9 _-]/', '', $alias); // strip non-alphanumeric characters
            $alias = strtolower($alias); // make lowercase
        } elseif($char_restrict=='alphanumeric') {
            $alias = preg_replace('/[^\.%A-Za-z0-9 _-]/', '', $alias); // strip non-alphanumeric characters
        } else { // restrict only to legal characters
            $alias = preg_replace('/[&=+%#<>"~`@\?\[\]\{\}\|\^\'\\\\]/', '', $alias); // remove chars that are illegal/unsafe in a url
        }
        switch($word_separator) {
            case 'dash': $word_separator='-'; break;
            case 'underscore': $word_separator='_'; break;
            case 'none': $word_separator=''; break;
            // default: use the value of $word_separator
        }
        $alias = preg_replace('/\s+/', $word_separator, $alias); // convert white-space to word separator
        $alias = preg_replace('/'.$word_separator.'+/', $word_separator, $alias);  // convert multiple word separators to one
        if(strlen($word_separator)==1)
            $alias = trim($alias, $word_separator.'/. '); // trim excess and bad chars
        else
            $alias = trim($alias, '/. '); // trim bad chars
        return $alias;
    }

}
?>