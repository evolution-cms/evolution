<?php

/*
 * Title: MODx Debug Console
 * Purpose:
 *  	A class designed to help developers impliment a debug console
 * 		like to the one in Ditto 2
*/

class modxDebugConsole{
	var $templates;
	
	function modxDebugConsole($templates) {
		$this->templates = $templates;
			// set templates array
	}
	
	// ---------------------------------------------------
	// Function: render
	// Render the contents of the debug console
	// ---------------------------------------------------
	function render($cTabs,$title,$base_path) {
		global $modx;
		$content = "";
		foreach ($cTabs as $name=>$tab_content) {
			$content .= $this->makeTab($name,$tab_content);
		}
		$placeholders = array(
			"[+ditto_base_url+]" => $base_path,
			"[+base_url+]" => $modx->config["site_url"].MGR_DIR . '/',
			"[+theme+]" => $modx->config["manager_theme"],
			"[+title+]" => $title,
			"[+content+]" => $content,
			"[+charset+]" => $modx->config["modx_charset"],
		);
	
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->templates["main"]);
	}
	
	
	// ---------------------------------------------------
	// Function: save
	// Save the debug console as a file
	// ---------------------------------------------------
	function save($html,$filename) {
		global $modx;
		header('Content-Type: text/html; charset='.$modx->config["modx_charset"]);
		header("Content-Disposition: attachment; filename=\"$filename\"");
		exit($html);
	}
	
	// ---------------------------------------------------
	// Function: makelink
	// Render the links to the debug console
	// ---------------------------------------------------
	function makeLink($title,$open_text,$save_text,$base_path,$prefix="") {
		global $modx;
		$placeholders = array(
			"[+open_url+]" => $this->buildURL("debug=open",$modx->documentIdentifier,$prefix),
			"[+curl+]" => $_SERVER["REQUEST_URI"],
			"[+dbg_title+]" => $title,
			"[+dbg_icon_url+]" => $base_path.'bug.png',
			"[+save_url+]" => $this->buildURL("debug=save",$modx->documentIdentifier,$prefix),
			"[+open_dbg_console+]" => $open_text,
			"[+save_dbg_console+]" => $save_text,
		);
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->templates["links"]);
	}
	
	// ---------------------------------------------------
	// Function: buildURL
	// Build a URL with regard to a prefix
	// ---------------------------------------------------
	function buildURL($args,$id=false,$prefix="") {
		global $modx;
			$query = array();
			foreach ($_GET as $param=>$value) {
				if ($param != 'id' && $param != 'q') {
					$query[htmlspecialchars($param, ENT_QUOTES)] = htmlspecialchars($value, ENT_QUOTES);					
				}
			}
			if (!is_array($args)) {
				$args = explode("&",$args);
				foreach ($args as $arg) {
					$arg = explode("=",$arg);
					$query[$prefix.$arg[0]] = urlencode(trim($arg[1]));
				}
			} else {
				foreach ($args as $name=>$value) {
					$query[$prefix.$name] = urlencode(trim($value));
				}
			}
			$queryString = "";
			foreach ($query as $param=>$value) {
				$queryString .= '&'.$param.'='.(is_array($value) ? implode(",",$value) : $value);
			}
			$cID = ($id !== false) ? $id : $modx->documentObject['id'];
			$url = $modx->makeURL(trim($cID), '', $queryString);
			return ($modx->config['xhtml_urls']) ? $url : str_replace("&","&amp;",$url);
	}
	
	// ---------------------------------------------------
	// Function: makeTab
	// Render a tab
	// ---------------------------------------------------	
	function makeTab($title,$content) {
		$placeholders = array(
			"[+title+]" => $title,
			"[+tab_content+]" => $content,
		);
		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $this->templates["tab"]);
	}

	// ---------------------------------------------------
	// Function: makeMODxSafe
	// Make all MODx tags safe for the output
	// ---------------------------------------------------
	function makeMODxSafe($value) {
		global $modx;
		$value = (strpos($value,"<") !== FALSE) ? "<pre>".htmlentities($value,ENT_NOQUOTES,$modx->config["modx_charset"])."</pre>" : $value;
		$value = str_replace("[","&#091;",$value);
		$value = str_replace("]","&#093;",$value);
		$value = str_replace("{","&#123;",$value);
		$value = str_replace("}","&#125;",$value);
		return $value;
	}
	
	// ---------------------------------------------------
	// Function: makeParamTable
	// Turn an array of parameters in the format ["param"] => "value" into a table
	// ---------------------------------------------------	
	function makeParamTable($parameters=array(),$header="",$sort=true,$prep=true,$wordwrap=true) {
		if (!is_array($parameters)) {
			return "";
		}
		if ($sort === true) {
					ksort($parameters);
		}
				$output = '<table>
				  <tbody>
				    <tr>
				      <th>'.$header.'</th>
				    </tr>
				    <tr>
				      <td>
				      <table>
				        <tbody>
		';
		foreach ($parameters as $key=>$value) {
			if (!is_string($value)) {
				$value = var_export($value,true);
			}
			$value = ($prep == true) ? $this->makeMODxSafe($value) : $value;
			$value = ($wordwrap == true) ? wordwrap($value,100,"\r\n",1) : $value;
			$output .= '
					    <tr>
					      <th>'.$key.'</th>
					      <td>'.$value.'</td>
					    </tr>
			';
		}
		$output .=
		'
				        </tbody>
				      </table>
				      </td>
				    </tr>
				  </tbody>
				</table>
				';

	return $output;
	}
		

	// ---------------------------------------------------
	// Function: cleanArray
	// Remove empty items from the array
	// ---------------------------------------------------	
	function cleanArray($array) {
	   foreach ($array as $index => $value) {
	       if(is_array($array[$index])) $array[$index] = $this->cleanArray($array[$index]);
	       if (empty($value)) unset($array[$index]);
		   if (count($array[$index]) == 0) unset($array[$index]);
	   }
	   return $array;
	}
	
	/**
	 * Function: array2table
	 * 
	 * Translate a result array into a HTML table
	 *
	 * Author:      Aidan Lister <aidan@php.net>
	 * 
	 * Version:     1.3.1
	 * 
	 * Link:        http://aidanlister.com/repos/v/function.array2table.php
	 */
	function array2table($array, $recursive = false, $return = false, $null = '&nbsp;')
	{
	    // Sanity check
	    if (empty($array) || !is_array($array)) {
	        return false;
	    }

	    if (!isset($array[0]) || !is_array($array[0])) {
	        $array = array($array);
	    }

	    // Start the table
	    $table = "<table>\n";
		$head = array_keys($array[0]);
	if (!is_numeric($head[0])) {
	    // The header
	    $table .= "\t<tr>";
	    // Take the keys from the first row as the headings
	    foreach (array_keys($array[0]) as $heading) {
	        $table .= '<th>' . $heading . '</th>';
	    }
	    $table .= "</tr>\n";
	}
	    // The body
	    foreach ($array as $row) {
	        $table .= "\t<tr>" ;
	        foreach ($row as $cell) {
	            $table .= '<td>';

	            // Cast objects
	            if (is_object($cell)) { $cell = (array) $cell; }

	            if ($recursive === true && is_array($cell) && !empty($cell)) {
	                // Recursive mode
	                $table .= "\n" . $this->array2table($cell, true, true) . "\n";
	            } else {
	                $table .= (strlen($cell) > 0) ?
					htmlspecialchars((string) $cell) :
					$null;
	            }

	            $table .= '</td>';
	        }

	        $table .= "</tr>\n";
	    }

	    // End the table
	    $table .= '</table>';

	    // Method of output
	    if ($return === false) {
	        echo $table;
	    } else {
	        return $table;
	    }
	}	
}

?>