<?php

/*
 * Title: Summary
 * Purpose:
 *  	Legacy support for the [+summary+] placeholder
*/
mb_internal_encoding("UTF-8");
$placeholders['summary'] = array("introtext,content","determineSummary","@GLOBAL ditto_summary_type");
$placeholders['link'] = array("id","determineLink");

$trunc = isset($trunc) ? $trunc : 1;
 /*
	Param: trunc

	Purpose:
	Enable truncation on the summary placeholder

	Options:
	0 - off
	1 - on
	
	Default:
	1 - on
*/
$splitter = isset($truncAt) ? $truncAt : "<!-- splitter -->";
 /*
	Param: truncAt

	Purpose:
	Location to split the content at

	Options:
	Any unique text or code string that is contained
	in the content of each document
	
	Default:
	"<!-- splitter -->"
*/
$length = isset($truncLen) ? $truncLen : 300;
 /*
	Param: truncLen

	Purpose:
	Number of characters to show of the content

	Options:
	Any number greater than <truncOffset>

	Default:
	300
*/
$offset = isset($truncOffset) ? $truncOffset : 30;
 /*
	Param: truncOffset

	Purpose:
	Number of charactars to "wander" either way of <truncLen>

	Options:
	Any number greater less than <truncLen>

	Default:
	30
*/
$text = isset($truncText)? $truncText : "Read more...";
 /*
	Param: truncText

	Purpose:
	Text to be displayed in [+link+]

	Options:
	Any valid text or html
	
	Default:
	"Read more..."
*/
$trunc_tpl = isset($tplTrunc)? template::fetch($tplTrunc) : false;
 /*
	Param: tplTrunc

	Purpose:
	Template to be used for [+link+]

	Options:
	- Any valid chunk name
	- Code via @CODE:
	- File via @FILE:
	
	Placeholders:
	[+url+] - URL of the document
	[+text+] - &truncText

	Default:
	&truncText
*/
$GLOBALS['ditto_summary_link'] = "";
$GLOBALS['ditto_summary_params'] = compact("trunc","splitter","length","offset","text","trunc_tpl");
$GLOBALS['ditto_object'] = $ditto;
// ---------------------------------------------------
// Truncate Functions
// ---------------------------------------------------
if (!function_exists("determineLink")) {
	function determineLink($resource) {
		global $ditto_object,$ditto_summary_params,$ditto_summary_link;
		if ($ditto_summary_link !== false) {
			$parameters = array(
			"url" => $ditto_summary_link,
			"text" => $ditto_summary_params["text"],
			);
			$tplTrunc = $ditto_summary_params["trunc_tpl"];
			if ($tplTrunc !== false) {
				$source = $tplTrunc;
			} else {
				$source = '<a href="[+url+]" title="[+text+]">[+text+]</a>';
			}
			return $ditto_object->template->replace($parameters,$source);
		} else {
			return '';
		}
	}
}
if (!function_exists("determineSummary")) {
	function determineSummary($resource) {
		global $ditto_summary_params;
		$trunc = new truncate();
		$p = $ditto_summary_params;
		$output = $trunc->execute($resource, $p['trunc'], $p['splitter'], $p['text'], $p['length'], $p['offset'], $p['splitter'], true);
		$GLOBALS['ditto_summary_link'] = $trunc->link;
		$GLOBALS['ditto_summary_type'] = $trunc->summaryType;
		return $output;
	}
}
// ---------------------------------------------------
// Truncate Class
// ---------------------------------------------------
if (!class_exists("truncate")) {
	class truncate{
		var $summaryType,$link;

		function html_substr($posttext, $minimum_length = 200, $length_offset = 20, $truncChars=false) {

		   // $minimum_length:
		   // The approximate length you want the concatenated text to be


		   // $length_offset:
		   // The variation in how long the text can be in this example text
		   // length will be between 200 and 200-20=180 characters and the
		   // character where the last tag ends

		   // Reset tag counter & quote checker
		   $tag_counter = 0;
		   $quotes_on = FALSE;
		   // Check if the text is too long
		   if (mb_strlen($posttext) > $minimum_length && $truncChars != 1) {

		       // Reset the tag_counter and pass through (part of) the entire text
		       $c = 0;
		       for ($i = 0; $i < mb_strlen($posttext); $i++) {
		           // Load the current character and the next one
		           // if the string has not arrived at the last character
		           $current_char = mb_substr($posttext,$i,1);
		           if ($i < mb_strlen($posttext) - 1) {
		               $next_char = mb_substr($posttext,$i + 1,1);
		           }
		           else {
		               $next_char = "";
		           }
		           // First check if quotes are on
		           if (!$quotes_on) {
		               // Check if it's a tag
		               // On a "<" add 3 if it's an opening tag (like <a href...)
		               // or add only 1 if it's an ending tag (like </a>)
		               if ($current_char == '<') {
		                   if ($next_char == '/') {
		                       $tag_counter += 1;
		                   }
		                   else {
		                       $tag_counter += 3;
		                   }
		               }
		               // Slash signifies an ending (like </a> or ... />)
		               // substract 2
		               if ($current_char == '/' && $tag_counter <> 0) $tag_counter -= 2;
		               // On a ">" substract 1
		               if ($current_char == '>') $tag_counter -= 1;
		               // If quotes are encountered, start ignoring the tags
		               // (for directory slashes)
		               if ($current_char == '"') $quotes_on = TRUE;
		           }
		           else {
		               // IF quotes are encountered again, turn it back off
		               if ($current_char == '"') $quotes_on = FALSE;
		           }

		           // Count only the chars outside html tags
		           if($tag_counter == 2 || $tag_counter == 0){
		               $c++;
		           }

		           // Check if the counter has reached the minimum length yet,
		           // then wait for the tag_counter to become 0, and chop the string there
		           if ($c > $minimum_length - $length_offset && $tag_counter == 0) {
		               $posttext = mb_substr($posttext,0,$i + 1);
		               return $posttext;
		           }
		       }
		   }  return $this->textTrunc($posttext, $minimum_length + $length_offset);
		}

		function textTrunc($string, $limit, $break=". ") {
	  	// Original PHP code from The Art of Web: www.the-art-of-web.com

	    // return with no change if string is shorter than $limit
	    if(mb_strlen($string) <= $limit) return $string;

	    $string = mb_substr($string, 0, $limit);
	    if(false !== ($breakpoint = mb_strrpos($string, $break))) {
	      $string = mb_substr($string, 0, $breakpoint+1);
	    }

	    return $string;
	  }

		function closeTags($text) {
			global $debug;
			$openPattern = "/<([^\/].*?)>/";
			$closePattern = "/<\/(.*?)>/";
			$endOpenPattern = "/<([^\/].*?)$/";
			$endClosePattern = "/<(\/.*?[^>])$/";
			$endTags = '';

			preg_match_all($openPattern, $text, $openTags);
			preg_match_all($closePattern, $text, $closeTags);

			if ($debug == 1) {
				print_r($openTags);
				print_r($closeTags);
			}

			$c = 0;
			$loopCounter = count($closeTags[1]); //used to prevent an infinite loop if the html is malformed
			while ($c < count($closeTags[1]) && $loopCounter) {
				$i = 0;
				while ($i < count($openTags[1])) {
					$tag = trim($openTags[1][$i]);

					if (mb_strstr($tag, ' ')) {
						$tag = mb_substr($tag, 0, strpos($tag, ' '));
					}
					if ($debug == 1) {
						echo $tag . '==' . $closeTags[1][$c] . "\n";
					}
					if ($tag == $closeTags[1][$c]) {
						$openTags[1][$i] = '';
						$c++;
						break;
					}
					$i++;
				}
				$loopCounter--;
			}

			$results = $openTags[1];

			if (is_array($results)) {
				$results = array_reverse($results);

				foreach ($results as $tag) {
					$tag = trim($tag);

					if (mb_strstr($tag, ' ')) {
						$tag = mb_substr($tag, 0, strpos($tag, ' '));
					}
					if (!mb_stristr($tag, 'br') && !mb_stristr($tag, 'img') && !empty ($tag)) {
						$endTags .= '</' . $tag . '>';
					}
				}
			}
			return $text . $endTags;
		}

		function execute($resource, $trunc, $splitter, $linktext, $truncLen, $truncOffset, $truncsplit, $truncChars) {
			$summary = '';
			$this->summaryType = "content";
			$this->link = false;
			$closeTags = true;
			// summary is turned off

			if ((mb_strstr($resource['content'], $splitter)) && $truncsplit) {
				$summary = array ();

				// HTMLarea/XINHA encloses it in paragraph's
				$summary = explode('<p>' . $splitter . '</p>', $resource['content']);

				// For TinyMCE or if it isn't wrapped inside paragraph tags
				$summary = explode($splitter, $summary['0']);

				$summary = $summary['0'];
				$this->link = '[~' . $resource['id'] . '~]';
				$this->summaryType = "content";
		
				// fall back to the summary text
			} else if (mb_strlen($resource['introtext']) > 0) {
					$summary = $resource['introtext'];
					$this->link = '[~' . $resource['id'] . '~]';
					$this->summaryType = "introtext";
					$closeTags = false;
					// fall back to the summary text count of characters
			} else if (mb_strlen($resource['content']) > $truncLen && $trunc == 1) {
					$summary = $this->html_substr($resource['content'], $truncLen, $truncOffset, $truncChars);
					$this->link = '[~' . $resource['id'] . '~]';
					$this->summaryType = "content";
					// and back to where we started if all else fails (short post)
			} else {
				$summary = $resource['content'];
				$this->summaryType = "content";
				$this->link = false;
			}

			// Post-processing to clean up summaries
			$summary = ($closeTags === true) ? $this->closeTags($summary) : $summary;
			return $summary;
		}
	}
}
?>