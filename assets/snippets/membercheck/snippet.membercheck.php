<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
#::::::::::::::::::::::::::::::::::::::::
# Version: 1.0
# Created By Ryan Thrash (vertexworks.com)
# Sanitized By Jason Coward (opengeek.com)
#
# Date: November 29, 2005
#
# Changelog: 
# Nov 29, 05 -- initial release
# Jul 13, 06 -- adjusted Singleton to work under PHP4, added placeholder code (by: garryn)
#
#::::::::::::::::::::::::::::::::::::::::
# Description: 	
#	Checks to see if users belong to a certain group and 
#	displays the specified chunk if they do. Performs several
#	sanity checks and allows to be used multiple times on a page.
#
# Params:
#	&groups [array] (REQUIRED)
#		array of webuser group-names to check against
#
#	&chunk [string] (REQUIRED)
#		name of the chunk to use if passes the check
#
#	&ph [string] (optional)
#		name of the placeholder to set instead of directly retuning chunk
#
#	&debug [boolean] (optional | false) 
#		turn on debug mode for extra troubleshooting
#
# Example Usage:
#
#	[[MemberCheck? &groups=`siteadmin, registered users` &chunk=`privateSiteNav` &ph=`MemberMenu` &debug=`true`]]
#
#	This would place the 'members-only' navigation store in the chunk 'privateSiteNav'
#	into a placeholder (called 'MemberMenu'). It will only do this as long as the user 
#	is logged in as a webuser and is a member of the 'siteadmin' or the 'registered users'
#	groups. The optional debug parameter can be used to display informative error messages 
#	when configuring this snippet for your site. For example, if the developer had 
#	mistakenly typed 'siteowners' for the first group, and none existed with debug mode on, 
#	it would have returned the error message: The group siteowners could not be found....
#
#::::::::::::::::::::::::::::::::::::::::

# debug parameter
$debug = isset ($debug) ? $debug : false;

# check if inside manager
if ($m = $modx->insideManager()) {
	return ''; # don't go any further when inside manager
}

if (!isset ($groups)) {
	return $debug ? '<p>Error: No Group Specified</p>' : '';
}

if (!isset ($chunk)) {
	return $debug ? '<p>Error: No Chunk Specified</p>' : '';
}

# turn comma-delimited list of groups into an array
$groups = explode(',', $groups);

if (!class_exists('MemberCheck')) {
	class MemberCheck {
		var $allGroups = NULL;
		var $debug;

		function getInstance($debug) {
			static $instance;
			if (!isset ($instance)) {
				$instance = new MemberCheck($debug);
			}
			return $instance;
		}

		function MemberCheck($debug = false) {
			global $modx;

			$this->debug = $debug;
			if ($debug) {
				$this->allGroups = array ();
				$tableName = $modx->getFullTableName('webgroup_names');
				$sql = "SELECT name FROM $tableName";
				if ($rs = $modx->db->query($sql)) {
					while ($row = $modx->db->getRow($rs)) {
						array_push($this->allGroups, stripslashes($row['name']));
					}
				}
			}
		}

		function isValidGroup($groupName) {
			$isValid = !(array_search($groupName, $this->allGroups) === false);
			return $isValid;
		}

		function getMemberChunk(& $groups, $chunk) {
			global $modx;
			$o = '';
			if (is_array($groups)) {
				for ($i = 0; $i < count($groups); $i++) {
					$groups[$i] = trim($groups[$i]);
					if ($this->debug) {
						if (!$this->isValidGroup($groups[$i])) {
							return "<p>The group <strong>" . $groups[$i] . "</strong> could not be found...</p>";
						}
					}
				}

				$check = $modx->isMemberOfWebGroup($groups);

				$chunkcheck = $modx->getChunk($chunk);

				$o .= ($check && $chunkcheck) ? $chunkcheck : '';
				if (!$chunkcheck)
					$o .= $this->debug ? "<p>The chunk <strong>$chunk</strong> not found...</p>" : '';
			} else {
				$o .= "<p>No valid group names were specified!</p>";
			}

			return $o;
		}
	}
}

$memberCheck = MemberCheck :: getInstance($debug);

if (!isset ($ph)) {
	return $memberCheck->getMemberChunk($groups, $chunk);
} else {
	$modx->setPlaceholder($ph, $memberCheck->getMemberChunk($groups, $chunk));
	return '';
}
?>