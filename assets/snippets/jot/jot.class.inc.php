<?php
/*####
#
#	Name: Jot
#	Version: 1.1.4
#	Author: Armand "bS" Pondman (apondman@zerobarrier.nl)
#	Date: Aug 04, 2008
#
# Latest Version: http://modxcms.com/Jot-998.html
# Jot Demo Site: http://projects.zerobarrier.nl/modx/
# Documentation: http://wiki.modxcms.com/index.php/Jot (wiki)
#
####*/

class CJot {
	var $name;
	var $version;
	var $config = array();
	var $parameters = array();
	var $_ctime;
	var $_provider;
	var $_instance;
	var $templates = array();
	var $_link = array();
	
	function CJot() {
		global $modx;
		$path = strtr(realpath(dirname(__FILE__)), '\\', '/');
		include_once($path . '/includes/jot.db.class.inc.php');
		if (!class_exists('CChunkie'))
			include_once($path . '/includes/chunkie.class.inc.php');
		$this->name = $this->config["snippet"]["name"] = "Jot";
		$this->version = $this->config["snippet"]["version"] = "1.1.4"; //
		$this->config["snippet"]["versioncheck"] = "Unknown";
		$this->_ctime = time();
		$this->_check = 0;
		$this->provider = new CJotDataDb;
		$this->form = array();
	}
	
	function VersionCheck($version) {	
		if ($version == $this->version) $this->_check = 1;
		$this->config["snippet"]["versioncheck"] = $version;
	}
	
	function Get($field) {
		return $this->parameters[$field];
	}
	
	function Set($field, $value) {
		$this->parameters[$field] = $value;
	}

	function UniqueId($docid = 0,$tagid = '') {
		// Creates a unique hash / id
		$id[] = $docid."&".$tagid."&";
		foreach ($this->parameters as $n => $v) { $id[] = $n.'='.($v); }
		return md5(join('&',$id));
	}

	function Run() {
		global $modx;

		// Check version		
		$this->config["path"] = $this->Get("path");
		if (!$this->_check) {
			$output = '<div style="border: 1px solid red;font-weight: bold;margin: 10px;padding: 5px;">
			Jot cannot load because the snippet code version ('.$this->config["snippet"]["versioncheck"].') isn\'t the same as the snippet included files version ('.$this->config["snippet"]["version"].').
			Possible cause is that you updated the jot files in the modx directory but didn\'t update the snippet code from the manager. The content for the updated snippet code can be found in jot.snippet.txt
			</div>';
			return $output;
		}
		
		// Add input parameters (just for debugging purposes)
		$this->config["snippet"]["input"] = $this->parameters; 
				
		// General settings
		// TODO Add docid/tagid from all
		$this->config["docid"] = !is_null($this->Get("docid")) ? intval($this->Get("docid")):$modx->documentIdentifier;
		$this->config["tagid"] = !is_null($this->Get("tagid")) ? preg_replace("/[^A-z0-9_\-]/",'',$this->Get("tagid")):'';
		$this->config["pagination"] = !is_null($this->Get("pagination")) ? $this->Get("pagination") : 10; // Set pagination (0 = disabled, # = comments per page)
		$this->config["captcha"] = !is_null($this->Get("captcha")) ? intval($this->Get("captcha")) : 0; // Set captcha (0 = disabled, 1 = enabled, 2 = enabled for not logged in users)
		$this->config["postdelay"] = !is_null($this->Get("postdelay")) ? $this->Get("postdelay") : 15; // Set post delay in seconds
		$this->config["guestname"] = !is_null($this->Get("guestname")) ? $this->Get("guestname") : "Anonymous"; // Set guestname if none is specified
		$this->config["subscribe"] = !is_null($this->Get("subscribe")) ? intval($this->Get("subscribe")) : 0;
		$this->config["numdir"] = !is_null($this->Get("numdir")) ? intval($this->Get("numdir")) : 1;
		$this->config["placeholders"] = !is_null($this->Get("placeholders")) ? intval($this->Get("placeholders")) : 0;
		$this->config["authorid"] = !is_null($this->Get("authorid")) ? intval($this->Get("authorid")) : $modx->documentObject["createdby"];
		$this->config["title"] = !is_null($this->Get("title")) ? $this->Get("title") : $modx->documentObject["longtitle"];
		$this->config["subject"]["subscribe"] = !is_null($this->Get("subjectSubscribe")) ? $this->Get("subjectSubscribe") : "New reply to a topic you are watching";
		$this->config["subject"]["moderate"] = !is_null($this->Get("subjectModerate")) ? $this->Get("subjectModerate") : "New reply to a topic you are moderating";
		$this->config["subject"]["author"] = !is_null($this->Get("subjectAuthor")) ? $this->Get("subjectAuthor") : "New comment on your post";
		$this->config["debug"] = !is_null($this->Get("debug")) ? intval($this->Get("debug")) : 0;
		$this->config["output"] = !is_null($this->Get("output")) ? intval($this->Get("output")) : 1;
		$this->config["validate"] = !is_null($this->Get("validate")) ? $this->Get("validate") : "content:You forgot to enter a comment.";
		
		// CSS Settings (basic)
		$this->config["css"]["include"] = !is_null($this->Get("css")) ? intval($this->Get("css")) : 1;
		$this->config["css"]["file"] = !is_null($this->Get("cssFile")) ? $this->Get("cssFile") : "assets/snippets/jot/templates/jot.css";
		$this->config["css"]["rowalt"] = !is_null($this->Get("cssRowAlt")) ? $this->Get("cssAltRow") : "jot-row-alt";
		$this->config["css"]["rowme"] = !is_null($this->Get("cssRowMe")) ? $this->Get("cssRowMe") : "jot-row-me";
		$this->config["css"]["rowauthor"] = !is_null($this->Get("cssRowAuthor")) ? $this->Get("cssRowAuthor") : "jot-row-author";
		
		// Security
		$this->config["user"]["mgrid"] = intval($_SESSION['mgrInternalKey']);
		$this->config["user"]["usrid"] = intval($_SESSION['webInternalKey']);
		$this->config["user"]["id"] = (	$this->config["user"]["usrid"] > 0 ) ? (-$this->config["user"]["usrid"]) : $this->config["user"]["mgrid"];

		$this->config["user"]["host"] = $_SERVER['REMOTE_ADDR'];
		$this->config["user"]["ip"] = $_SERVER['REMOTE_ADDR'];
		$this->config["user"]["agent"] = $_SERVER['HTTP_USER_AGENT'];
		$this->config["user"]["sechash"] = md5($this->config["user"]["id"].$this->config["user"]["host"].$this->config["user"]["ip"].$this->config["user"]["agent"]);
		
		// Automatic settings
		$this->_instance = $this->config["id"] = $this->UniqueId($this->config["docid"],$this->config["tagid"]);
		$this->_idshort = substr($this->_instance,0,8);
		if($this->config["captcha"] == 2) { if ($this->config["user"]["id"]) {	$this->config["captcha"] = 0;} else { $this->config["captcha"] = 1;} }
		$this->config["seed"] = rand();
		$this->config["doc.pagetitle"] = $modx->documentObject["pagetitle"];
		$this->config["customfields"] = $this->Get("customfields") ? explode(",",$this->Get("customfields")):array("name","email"); // Set names of custom fields
		$this->config["sortby"] = !is_null($this->Get("sortby")) ? $this->Get("sortby") : "createdon:d";		
		$this->config["sortby"] = $this->validateSortString($this->config["sortby"]);
								
		// Set access groups
		$this->config["permissions"]["post"] = !is_null($this->Get("canpost")) ? explode(",",$this->Get("canpost")):array();
		$this->config["permissions"]["view"] = !is_null($this->Get("canview")) ? explode(",",$this->Get("canview")):array();
		$this->config["permissions"]["edit"] = !is_null($this->Get("canedit")) ? explode(",",$this->Get("canedit")):array();
		$this->config["permissions"]["moderate"] = !is_null($this->Get("canmoderate")) ? explode(",",$this->Get("canmoderate")):array();
		$this->config["permissions"]["trusted"] = !is_null($this->Get("trusted")) ? explode(",",$this->Get("trusted")):array();
		
		// Moderation
		$this->config["moderation"]["type"] = !is_null($this->Get("moderated")) ? intval($this->Get("moderated")) : 0;
		$this->config["moderation"]["notify"] = !is_null($this->Get("notify")) ? intval($this->Get("notify")) : 1;
		$this->config["moderation"]["notifyAuthor"] = !is_null($this->Get("notifyAuthor")) ? intval($this->Get("notifyAuthor")) : 0;
		
		// Access Booleans
		// TODO Add logic for manager groups
		$this->isModerator = $this->config["moderation"]["enabled"] = intval($modx->isMemberOfWebGroup($this->config["permissions"]["moderate"] ) || $modx->checkSession());
		$this->isTrusted = $this->config["moderation"]["trusted"] = intval($modx->isMemberOfWebGroup($this->config["permissions"]["trusted"] ) || $this->isModerator);
		$this->canPost = $this->config["user"]["canpost"] = ((count($this->config["permissions"]["post"])==0) || $modx->isMemberOfWebGroup($this->config["permissions"]["post"]) || $this->isModerator) ? 1 : 0;
		$this->canView = $this->config["user"]["canview"] = ((count($this->config["permissions"]["view"])==0) || $modx->isMemberOfWebGroup($this->config["permissions"]["view"]) || $this->isModerator) ? 1 : 0;
		$this->canEdit = $this->config["user"]["canedit"] = intval($modx->isMemberOfWebGroup($this->config["permissions"]["edit"]) || $this->isModerator);
		
		// Templates
		$this->templates["form"] = !is_null($this->Get("tplForm")) ? $this->Get("tplForm") : $this->config["path"]."/templates/chunk.form.inc.html";
		$this->templates["comments"] = !is_null($this->Get("tplComments")) ? $this->Get("tplComments") : $this->config["path"]."/templates/chunk.comment.inc.html";
		$this->templates["navigation"] = !is_null($this->Get("tplNav")) ? $this->Get("tplNav") : $this->config["path"]."/templates/chunk.navigation.inc.html";
		$this->templates["moderate"] = !is_null($this->Get("tplModerate")) ? $this->Get("tplModerate") : $this->config["path"]."/templates/chunk.moderate.inc.html";
		$this->templates["subscribe"] = !is_null($this->Get("tplSubscribe")) ? $this->Get("tplSubscribe") : $this->config["path"]."/templates/chunk.subscribe.inc.html";
		$this->templates["notify"] = !is_null($this->Get("tplNotify")) ? $this->Get("tplNotify") : $this->config["path"]."/templates/chunk.notify.inc.txt";				
		$this->templates["notifymoderator"] = !is_null($this->Get("tplNotifyModerator")) ? $this->Get("tplNotifyModerator") : $this->config["path"]."/templates/chunk.notify.moderator.inc.txt";
		$this->templates["notifyauthor"] = !is_null($this->Get("tplNotifyAuthor")) ? $this->Get("tplNotifyAuthor") : $this->config["path"]."/templates/chunk.notify.author.inc.txt";
		
		// Querystring keys
		$this->config["querykey"]["action"] = "jot".$this->_idshort;
		$this->config["querykey"]["navigation"] = "jn".$this->_idshort;
		$this->config["querykey"]["id"] = "jid".$this->_idshort;
		$this->config["querykey"]["view"] = "jv".$this->_idshort;
		
		// Querystring values
		$this->config["query"]["action"] = $_GET[$this->config["querykey"]["action"]];
		$this->config["query"]["navigation"] = intval($_GET[$this->config["querykey"]["navigation"]]);
		$this->config["query"]["id"] = intval($_GET[$this->config["querykey"]["id"]]);
		$this->config["query"]["view"] = intval($_GET[$this->config["querykey"]["view"]]);
		
		// Form options
		$this->isPostback = $this->config["form"]["postback"] = ($_POST["JotForm"] == $this->_instance) ? 1 : 0;
		
		// Field validation array
		$valStrings = explode(",",$this->config["validate"]);
		$valFields = array();
		foreach($valStrings as $valString) {
			$valProp = explode(":",$valString,3);
			$valField = array();
			$valField["validation"] = "required";

			foreach($valProp as $i => $v) {
				if ($i==1) $valField["msg"] = $v;
				if ($i==2) $valField["validation"] = $v;
			}
			
			$valFields[$valProp[0]][] = $valField;
		}
		$this->config["form"]["validation"] = $valFields;
		
		//-- Initialize form array()
		$this->form = array();
		$this->form["source"] = $this->config["query"]["id"];
		$this->form["guest"] = ($this->config["user"]["id"]) ? 0 : 1;
		$this->form["field"] = array("custom" => array());
		$this->form["error"] = 0;
		$this->form["confirm"] = 0;
		$this->form["published"] = 0;
		$this->form["badwords"] = 0;
		$this->form["edit"] = 0;
		$this->form["save"] = 0;		
		
		// Modes
		$this->config["mode"]["type"] = "comments";
		$this->config["mode"]["active"] = $this->config["query"]["action"];
		$this->config["mode"]["passive"] = $this->Get("action");
		
		// Generated links
		$this->_link = array($this->config["querykey"]["action"]=>NULL,$this->config["querykey"]["id"]=>NULL);
		$this->config["link"]["id"] = $this->_idshort;
		$this->config["link"]["current"] = $this->preserveUrl($modx->documentIdentifier,'',$this->_link);
		$this->config["link"]["navigation"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["navigation"]=>NULL)),true);
		$this->config["link"]["subscribe"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'subscribe')));
		$this->config["link"]["unsubscribe"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'unsubscribe')));
		$this->config["link"]["save"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'save',$this->config["querykey"]["id"]=>$this->config["query"]["id"])));
		$this->config["link"]["edit"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'edit')),true);
		$this->config["link"]["delete"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'delete')),true);
		$this->config["link"]["view"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["view"]=>NULL)),true);
		$this->config["link"]["publish"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'publish')),true);
		$this->config["link"]["unpublish"] = $this->preserveUrl($modx->documentIdentifier,'',array_merge($this->_link,array($this->config["querykey"]["action"]=>'unpublish')),true);
		
		// Check for first run
		$this->provider->FirstRun($this->config["path"]);
		
		// Badwords
		$this->config["badwords"]["enabled"] = !is_null($this->Get("badwords")) ? 1 : 0;
		$this->config["badwords"]["type"] = !is_null($this->Get("bw")) ? intval($this->Get("bw")) : 1;
		if($this->config["badwords"]["enabled"]) {
			$badwords = $this->Get("badwords");
			$badwords = preg_replace("~([\n\r\t\s]+)~","",$badwords);
			$this->config["badwords"]["words"] = explode(",",$badwords);
			$this->config["badwords"]["regexp"] = "~" . implode("|",$this->config["badwords"]["words"]) . "~i";
		}
				
		// Moderation
		if ($this->isModerator) {
			$this->config["moderation"]["view"] = $view = isset($_GET[$this->config["querykey"]["view"]]) ? $this->config["query"]["view"]: 2;
		}
		
		// Subscription
		$this->config["subscription"]["enabled"] = 0;
		$this->config["subscription"]["status"] = 0;
		if ($this->config["user"]["id"] && $this->config["subscribe"]) {
			$this->config["subscription"]["enabled"] = 1;
			$isSubscribed = $this->provider->hasSubscription($this->config["docid"],$this->config["tagid"], $this->config["user"]);
			if ($isSubscribed) $this->config["subscription"]["status"] = 1;
		}
					
		
		$commentId = $this->config["query"]["id"];
		
		// Active action
		switch ($this->config["mode"]["active"]) {
			case "delete":
				$this->doModerate('delete',$commentId);
				break;
			case "publish":
				$this->doModerate('publish',$commentId);
				break;
			case "unpublish":
				$this->doModerate('unpublish',$commentId);
				break;
			case "edit":
				if ($this->isModerator) {
					$this->doModerate('edit',$commentId); 
					break;
				} else {
					$this->form["edit"] = 1;
				}
			case "save": 
			 		if ($this->isModerator) {
						$this->doModerate('save',$commentId);
						break;
					} else {
						$this->form["edit"] = 1;
						$this->form["save"] = 1;
					}
			case "move":
				break;
			case "subscribe":
					if ($this->config["subscription"]["enabled"] == 1) {
						if ($this->config["subscription"]["status"] == 0) {
							$this->provider->Subscribe($this->config["docid"],$this->config["tagid"],$this->config["user"]);
							$this->config["subscription"]["status"] = 1;
						}
					}
					break;
			case "unsubscribe":
					if ($this->config["subscription"]["enabled"] == 1) {
						if ($this->config["subscription"]["status"] == 1) {
							$this->provider->Unsubscribe($this->config["docid"],$this->config["tagid"],$this->config["user"]);
							$this->config["subscription"]["status"] = 0;
						}
					}
			break;
		}
		
		// Form Processing
		$frmCommentId = ($this->form["edit"]) ? $commentId : 0;
		$this->processForm($frmCommentId);	
	
		// Passive Action					
		switch ($this->config["mode"]["passive"]) {
		  case "count-comments" : $output = $this->getCommentCount(); break;
		  case "count-subscriptions": $output = $this->SubscriptionCount(); break;
		  case "comments": $output = $this->getOutputComments(); break;
		  case "form": $output = $this->getOutputForm(); break;
		  case "blank": break;
			case "default":
		  default: $output = $this->getOutputDefault(); break;
		}
		
		if ($this->config["debug"]) {
			$output .= '<br /><hr /><b>'.$this->name.' '.$this->version.': Debug</b><hr /><pre style="overflow: auto;background-color: white;font-weight: bold;">';
			$output .= $this->getOutputDebug($this->config,"jot");
			$output .= '</pre><hr />';
	  }
		
		// Dump config into placeholders?
		if ($this->config["placeholders"]) $this->setPlaceholders($this->config,"jot");
		
		// Include stylesheet if needed
		$src = '<link rel="stylesheet" type="text/css" href="'.$modx->config["base_url"].$this->config["css"]["file"].'" />';
		if ($this->config["css"]["include"]) $modx->regClientCSS($src);
		
		return $output;
	}
	
	// Output snippet values in debug format
	function getOutputDebug($value = '', $key = '', $path = '') {
		$keypath = !empty($path) ? $path . "." . $key : $key;
	    $output = array();
		if (is_array($value)) { 
			foreach ($value as $subkey => $subval) {
				$output[] = $this->getOutputDebug($subval, $subkey, $keypath);
            }
		} else { 
			$output[] = '<span style="color: navy;">'.$keypath.'</span> = <span style="color: maroon;">'.htmlspecialchars($value).'</span><br />';	
		}
		return implode("",$output);
	}
	
	// Create placeholders in MODx from arrays
	function setPlaceholders($value = '', $key = '', $path = '') {
		global $modx;
		$keypath = !empty($path) ? $path . "." . $key : $key;
	    $output = array();
		if (is_array($value)) { 
			foreach ($value as $subkey => $subval) {
				$this->setPlaceholders($subval, $subkey, $keypath);
            }
		} else {
			if (strlen($this->config["tagid"]) > 0) {$keypath .= ".".$this->config["tagid"]; }
			$modx->setPlaceholder($keypath,$value);	
		}
	}
	
	// Display default
	function getOutputDefault() {
		global $modx;
		$output = $this->getOutputForm();
		$output .= $this->getOutputComments();
		return $output;
	}
	
	// Display comments
	function getOutputComments() {
		// Check if viewing is allowed
		if($this->canView) {
				
				// View (Moderation)
				$view = 1;
				if ($this->isModerator) { 
					$view = $this->config["moderation"]["view"];
					$this->config["moderation"]["unpublished"] = $this->getCommentCount(0);
					$this->config["moderation"]["published"] = $this->getCommentCount(1);
					$this->config["moderation"]["mixed"] = $this->getCommentCount(2);
				}
				
				// Get total number of comments
				$commentTotal = $this->getCommentCount($view);
				$pagination = $this->config["pagination"];
				
				// Apply pagination if enabled
				if ($pagination > 0) {
					$pageLength = $pagination;
					$pageTotal = ceil($commentTotal / $pageLength);
					$pageCurrent = isset($_GET[$this->config["querykey"]["navigation"]]) ? $_GET[$this->config["querykey"]["navigation"]]: 1;
					if ( ($pageCurrent < 1) || ($pageCurrent > $pageTotal) ) { $pageCurrent = 1; };
					$pageOffset = (($pageCurrent*$pageLength)-$pageLength);
					$navStart = ($pageOffset+1);
					$navEnd = ($pageOffset+$pageLength) > $commentTotal ? $commentTotal : ($pageOffset+$pageLength);
				} else {
					$pageLength = 0;
					$pageOffset = 0;
					$pageTotal = 1;
					$pageCurrent = 1;
					$navStart = 0;
					$navEnd = $commentTotal;
				}
				
				// Navigation
				$this->config['nav'] = array('total'=>$commentTotal,'start'=>$navStart,'end'=> $navEnd);
				$this->config['page'] = array('length'=>$pageLength,'total'=>$pageTotal,'current'=>$pageCurrent);
				
				// Render Moderation Options
				$output_moderate = NULL;
				if ($this->isModerator) { 
					$tpl = new CChunkie($this->templates["moderate"]);
					$tpl->AddVar('jot',$this->config);
					$this->config["html"]["moderate"] = $output_moderate = $tpl->Render();
				}
					
				// Get comments
				$array_comments = $this->provider->GetComments($this->config["docid"],$this->config["tagid"],$view,$this->config["sortby"],$pageOffset,$pageLength);
								
				// Render navigation
				$output_navigation = NULL;
				if (($pagination > 0) && ($pageTotal > 1) ) {
						$tpl = new CChunkie($this->templates["navigation"]);
						$tpl->AddVar('jot',$this->config);
						$output_navigation = $tpl->Render();
				}	
				
				// Render subscription options
				$output_subscribe = NULL;
				$tpl = new CChunkie($this->templates["subscribe"]);
				$tpl->AddVar('jot',$this->config);
				$this->config["html"]["subscribe"] = $output_subscribe = $tpl->Render();
				
				// Render comments
				$count = count($array_comments);
				$comments = array();
				
				// Comment Numbering
				for ($i = 0; $i < $count; $i++) {
					$num = ($this->config["numdir"]) ? $commentTotal - ($pageOffset + $i) :  $pageOffset + ($i+1);
					$array_comments[$i]["postnumber"] = $num;			
				}
	
				
				
				for ($i = 0; $i < $count; $i++) {
					$chunk["rowclass"] = $this->getChunkRowClass($i+1,$array_comments[$i]["createdby"]);
					$tpl = new CChunkie($this->templates["comments"]);
					$tpl->AddVar('jot',$this->config);
					$tpl->AddVar('comment',$array_comments[$i]);
					$tpl->AddVar('chunk',$chunk);
					$comments[] = $tpl->Render();
				}

				$this->config["html"]["comments"] = $output_comments = join("",$comments);
				$this->config["html"]["navigation"] = $output_navigation;
				$output_comments = $output_subscribe.$output_moderate.$output_navigation.$output_comments.$output_navigation;
		}		
		if ($this->config["output"]) return $output_comments;
	}
		
	function processForm($id=0) {
		global $modx;
		
		// Comment
		$id = intval($id);
		$pObj = $this->provider;
		$formMode = $this->config["mode"]["passive"];	
		$saveComment = 1;
		$this->form["action"] = $this->config["link"]["current"];
		if ($id && $pObj->isValidComment($this->config["docid"],$this->config["tagid"],$id) && $this->canEdit) {
				$pObj->Comment($id);
				if (($pObj->Get("createdby") == $this->config["user"]["id"]) || $this->isModerator) {
					$this->form["action"] = $this->config["link"]["save"];
					$this->form['guest'] = ($pObj->Get("createdby") == 0) ? 1 : 0;
					$this->form["field"] = $pObj->getFields();
					$this->config["mode"]["passive"] =  "form";
				} else {
					$this->form['edit'] = 0;
					$this->form['save'] = 0;
					$saveComment = 0;
				}
			} else {
			$pObj->Comment(0); // fix for update/new problem
		}
	
		// If this is not a postback or a false edit then return.
		if (!$this->isPostback || !$saveComment) return;
		
		// If we get here switch passive mode back and let the save option decide the final passive mode
		$this->config["mode"]["passive"] = $formMode;
						
		//-- Get Post Objects
			$chkPost = array();
			$valFields = array();
			// For every field posted loop
			foreach($_POST as $n=>$v) {
			
						// Stripslashes if needed
						if (get_magic_quotes_gpc()) { $v = stripslashes($v); }
						
						// Validate fields and store error level + msg in array
						$valFields[] = $this->validateFormField($n,$v);
						
						// Store field data
						switch($n) {
							case 'title': // Title field
								if ($v == '') $v = "Re: " . $this->config["title"];
								$this->form["field"]["title"] = $v;
								$pObj->Set("title",$v); 
								break;
							case 'content': // Content field
								$this->form["field"]["content"] = $v;
								$pObj->Set("content",$v); 
								break;
							default: // Custom fields
								if (in_array($n, $this->config["customfields"])) {
									$this->form["field"]["custom"][$n] = $v;
									$pObj->SetCustom($n,$v);
								} else {
									$this->form["field"][$n] = $v;
								}
						}
					//-- Detect bad words
				if ($this->config["badwords"]["enabled"]) $this->form['badwords'] = $this->form['badwords'] + preg_match_all($this->config["badwords"]["regexp"],$v,$matches);
				//-- 
				$chkPost[] = $n.'='.($v);
			} // --	
		
		//-- Double Post Capture
		$chkPost = md5(join('&',$chkPost));
		if ($_SESSION['JotLastPost'] == $chkPost) {
			$this->form['error'] = 1;
			$this->form['confirm'] = 0;
			$saveComment = 0;
		} else {
			$_SESSION['JotLastPost'] = $chkPost;
		}
		
		//-- Security check (Post Delay?)
		if ($saveComment && $this->form['error'] == 0 && $this->config["postdelay"] != 0 && $pObj->hasPosted($this->config["postdelay"],$this->config["user"])) {
			$this->form['error'] = 3; // Post to fast (within delay)
			return;
		};

		//-- Captcha/Veriword
		if ($saveComment && !(($this->config["captcha"] == 0 || isset($_POST['vericode']) && isset($_SESSION['veriword']) && $_SESSION['veriword'] == $_POST['vericode']))) {
			$this->form['error'] = 2; // Veriword / Captcha incorrect
			unset($pObj);
			return;
		} else {
			$_SESSION['veriword'] = md5($this->config["seed"]);
		}
		
		//-- Validate fields
		if ($saveComment) {
			foreach($valFields as $valid) {
				if (!$valid[0]) {
					$this->form['error'] = 5;
					$this->form['errormsg'] = $valid[1];
					$this->form['confirm'] = 0;
					$saveComment = 0;
					return;
				}
			}
		}
			
		// Everything OK so far
		if ($saveComment) {
			$this->form['confirm'] = 1;
			$this->form['published'] = 1;
		}
		
		//-- Check publish settings (moderations)
		if ($saveComment && $this->config["moderation"]["type"] && !$this->isTrusted) {
			$this->form['confirm'] = 2;
			$this->form['published'] = 0;
		} 
		
		// Badwords detection logic
		if ($saveComment && $this->form["badwords"] && $this->config["badwords"]["enabled"] && !$this->isTrusted) {
			switch($this->config["badwords"]["type"]) {
				case 2: // Post Rejected
					$this->form['error'] = 4;
					$this->form['confirm'] = 0;
					$saveComment = 0;
					break;
				case 1:  // Post Not Published
					$this->form['published'] = 0;
					$this->form['confirm'] = 2; // Post Not Published
					break;
				}
		}
		// If published or unpublished save the comment, else do nothing.
		if (!$id) {
			// this is a new post
			$pObj->Set("createdon",$this->_ctime);
			$pObj->Set("createdby",$this->config["user"]["id"]);
			$pObj->Set("secip",$this->config["user"]["ip"]);
			$pObj->Set("sechash",$this->config["user"]["sechash"]);
			$pObj->Set("uparent",$this->config["docid"]);
			$pObj->Set("tagid",$this->config["tagid"]);
		} else {
			// edit/save
			$pObj->Set("editedon",$this->_ctime);
			$pObj->Set("editedby",$this->config["user"]["id"]);
		}
		
		$pObj->Set("published",$this->form['published']);
		if ($saveComment) $pObj->Save();
		
		// Edit mode logic
		if ($saveComment && $this->form["save"]) { 
			$this->form["moderation"] = 0;
			$this->form["edit"] = 0;
			if($this->form["confirm"]==1) $this->form["confirm"] = 3;
			$this->form["action"] = $this->config["link"]["current"];
		}
		
		if ($this->form["edit"]) { $this->config["mode"]["passive"] = "form"; }
		
		// Notify Subscribers
		if ($saveComment && $this->form['published']>0) $this->doNotifySubscribers($pObj->Get("id"));
		
		// Notify Moderators
		if ($saveComment && (($this->form['published']==0) || ($this->form['published'] >0 && $this->config["moderation"]["notify"]==2))) $this->doNotifyModerators($pObj->Get("id"));
		
		// Notify Author
		if ($saveComment && $this->config["moderation"]["notifyAuthor"]) $this->doNotifyAuthor($pObj->Get("id"));
		
		// If no error occured clear fields.
		if ($this->form['error'] <= 0 ) $this->form["field"] = array();
		
		// Destroy Comment Object and return form array()
		unset($pObj);
		return;
	}
	
	// Display Form
	function getOutputForm() {
		global $modx;
		$output_form = NULL;

		//----  Allow post?
		if ($this->canPost) {
															
			// Render Form
			$tpl = new CChunkie($this->templates["form"]);
			$tpl->AddVar('jot',$this->config);
			$tpl->AddVar('form',$this->form);
			$this->config["html"]["form"] = $output_form = $tpl->Render();
			
		} // -----
		
		// Output or placeholder?
		if ($this->config["output"]) return $output_form;
	}
		
	// Notifications
	function doNotifySubscribers($commentid=0) {
		global $modx;
		if ($this->config["subscription"]["enabled"]) {
				
				// Get comment fields
				$cObj = $this->provider;
				$cObj->Comment($commentid);
				$comment = $cObj->getFields();
				unset($cObj);
																
				$subscriptions = $this->provider->getSubscriptions($this->config["docid"],$this->config["tagid"]);
				$count = count($subscriptions);
				for ($i = 0; $i < $count; $i++) {
						if ($this->config["user"]["id"] != $subscriptions[$i]["userid"] ) {
							$user = $this->getUserInfo($subscriptions[$i]["userid"]);
							$tpl = new CChunkie($this->templates["notify"]);
							$tpl->AddVar('jot',$this->config);
							$tpl->AddVar('comment',$comment);
							$tpl->AddVar('siteurl',"http://".$_SERVER["SERVER_NAME"]);
							$tpl->AddVar('recipient',$user);
							$message = $tpl->Render();
							mail($user["email"], $this->config["subject"]["subscribe"], $message, "From: ".$modx->config['emailsender']."\r\n"."X-Mailer: Content Manager - PHP/".phpversion());
						}
				}
		}
	}
	
	// Moderator Notification
	function doNotifyModerators($commentid=0) {
		global $modx;
		if ($this->config["moderation"]["notify"]) {

		  // Get comment fields
			$cObj = $this->provider;
			$cObj->Comment($commentid);
			$comment = $cObj->getFields();
			unset($cObj);
					
			$moderators = $this->getMembersOfWebGroup($this->config["permissions"]["moderate"]);
			foreach ($moderators as $moderator){
				$user = $modx->getWebUserInfo($moderator);
				$tpl = new CChunkie($this->templates["notifymoderator"]);
				$tpl->AddVar('jot',$this->config);
				$tpl->AddVar('comment',$comment);
				$tpl->AddVar('siteurl',"http://".$_SERVER["SERVER_NAME"]);
				$tpl->AddVar('recipient',$user);
				$message = $tpl->Render();
				mail($user["email"], $this->config["subject"]["moderate"], $message, "From: ".$modx->config['emailsender']."\r\n"."X-Mailer: Content Manager - PHP/".phpversion());
			}
		}
	}
	
	// Author Notification
	function doNotifyAuthor($commentid=0) {
		
		echo '<!-- notifying author -->';
		
		global $modx;
		
		if ($this->config["moderation"]["notifyAuthor"]) {
			
			// What is the e-mail address of the article author?
			$author_id = $this->config['authorid'];		
			$res = $modx->db->select('*',  $modx->getFullTableName('user_attributes'), 'id = '.$author_id);
			$results_array = $modx->db->makeArray($res);
			$user = $results_array[0]; // Assume there is only one result			
			
			// Get comment fields (copied from doNotifyModerators)
			$cObj = $this->provider;
			$cObj->Comment($commentid);
			$comment = $cObj->getFields();
			unset($cObj);
			
			$tpl = new CChunkie($this->templates["notifyauthor"]);
			$tpl->AddVar('jot',$this->config);
			$tpl->AddVar('comment',$comment);
			$tpl->AddVar('siteurl',"http://".$_SERVER["SERVER_NAME"]);
			$tpl->AddVar('recipient',$user);
			$message = $tpl->Render();
			
			mail($user["email"], $this->config["subject"]["author"], $message, "From: ".$modx->config['emailsender']."\r\n"."X-Mailer: Content Manager - PHP/".phpversion());
			
		}
		
		
		
	}
	
	// Returns comment count
	function getCommentCount($view=1) {
		return $this->provider->GetCommentCount($this->config["docid"],$this->config["tagid"],$view);
	}

	// Moderation
	function doModerate($action = '',$id = 0) {
		$output = NULL;
		$pObj = $this->provider;
		if ($this->isModerator && $pObj->isValidComment($this->config["docid"],$this->config["tagid"],$id)) {
			switch ($action) {
				case "delete":
					$pObj->Comment($id);
					$pObj->Delete();
					break;
				case "publish":
					$pObj->Comment($id);
					$pObj->Set("publishedon",$this->_ctime);
					$pObj->Set("publishedby",$this->config["user"]["id"]);
					$pObj->Set("published",1);
					$pObj->Save();
					$this->doNotifySubscribers($id);
					break;
				case "edit":
					$this->form["moderation"] = 1;
					$this->form["edit"] = 1;
					break;		
				case "save":
					$this->form["moderation"] = 1;
					$this->form["edit"] = 1;
					$this->form["save"] = 1;
					break;							
				case "unpublish":
					$pObj->Comment($id);
					$pObj->Set("publishedon",$this->_ctime);
					$pObj->Set("publishedby",$this->config["user"]["id"]);
					$pObj->Set("published",0);
					$pObj->Save();
					break;
				}
			
		}
		unset($pObj);
		return $output;
	}

	// Templating
	function getChunkRowClass($count,$userid) {
		$rowstyle = ($count%2) ? "jot-row-alt" : "";
		if ( $this->config["user"]["id"] == $userid && ($userid != 0)) {
			$rowstyle .= " jot-row-me";
		} elseif ( $this->config["authorid"] == $userid && ($userid != 0) ) {
			$rowstyle .= " jot-row-author";
		} 
		return $rowstyle;
	}
	
	// Validate a field
	function validateFormField($name = '', $value = '') {
		$returnValue = array(1,"");
		$validateFields = $this->config["form"]["validation"];
		
		// Validation Exists?
		if (!array_key_exists($name, $validateFields))
			return $returnValue;
			
		// Load field validation array
		$validations = $validateFields[$name];
		
		// Loop validation array
		foreach($validations as $validation) {
				switch ($validation["validation"]) {
					// email validation
					case "email": $re = "~^(?:[a-z0-9_-]+?\.)*?[a-z0-9_-]+?@(?:[a-z0-9_-]+?\.)*?[a-z0-9_-]+?\.[a-z0-9]{2,5}$~i"; break;
					// simple required field validation
					case "required": $re = "~.+~s";break;
					// simple number validation
					case "number": $re = "~^\d+$~";break;
					// custom regexp pattern
					default: $re = $validation["validation"]; break;
				}
				// if not a match return error msg
				if (!preg_match($re,$value))
					return array(0,$validation["msg"]);
		}
		return $returnValue;					
	}
		
	// Validates and returns a special sort string so the data provider can handle this.
	function validateSortString($strSort = '') {
		$z = array();
		$xObj = $this->provider;
		$xObj->Comment();
		$y = explode(",",$strSort); // suggested sort fields
		$x = $xObj->getFields(); // actual available sort fields
		$x2 = $this->config["customfields"]; // actual available custom sort fields
		unset($xObj);
		
		// for each suggested sort
		foreach ($y as $i) {
			$i = trim($i);
			if(strlen($i)>2) {
				// get direction
				$dir = substr($i, -2);
				// get fieldname
				$name = substr($i,0,(strlen($i)-2));
				// if this is a custom field prefix with '#' so data provider can detect it.
				if (in_array($name, $x2)) { $z[] = "#".$name.$dir; }
				// if normal field
				elseif (array_key_exists($name, $x)) { $z[] = $name.$dir; }
			}
		}
		return implode(",",$z);
	}
	
	
	// Returns an array containing webusers which are a member of the specified group(s).
	function getMembersOfWebGroup($groupNames=array()) {
		global $modx;
		$usrIDs = array();
		$tbl = $modx->getFullTableName("webgroup_names");
		$tbl2 = $modx->getFullTableName("web_groups");
		$sql = "SELECT distinct wg.webuser
						FROM $tbl wgn
						INNER JOIN $tbl2 wg ON wg.webgroup=wgn.id AND wgn.name IN ('" . implode("','",$groupNames) . "')";
		$usrRows = $modx->db->getColumn("webuser", $sql);
		foreach ($usrRows as $k => $v) $usrIDs[] = intval($v);
		return $usrIDs;
	}	
	
	// MODx UserInfo enhanced
	function getUserInfo($userid = 0,$field = NULL) {
		global $modx;
		if (intval($userid) < 0) {
			$user = $modx->getWebUserInfo(-($userid));
		} else {
			$user = $modx->getUserInfo($userid);
		}
		if ($field) {	return $user[$field]; }
		return $user;
	}	
	
	// MODx makeUrl enhanced: preserves querystring.
	function preserveUrl($docid = '', $alias = '', $array_values = array(), $suffix = false) {
		global $modx;
		$array_get = $_GET;
		$urlstring = array();
		
		unset($array_get["id"]);
		unset($array_get["q"]);
		
		$array_url = array_merge($array_get, $array_values);
		foreach ($array_url as $name => $value) {
			if (!is_null($value)) {
			  $urlstring[] = $name . '=' . urlencode($value);
			}
		}
		
		$url = join('&',$urlstring);
		if ($suffix) {
			if (empty($url)) { $url = "?"; }
			 else { $url .= "&"; }
		}

		return $modx->makeUrl($docid, $alias, $url);
	}
	
}
?>