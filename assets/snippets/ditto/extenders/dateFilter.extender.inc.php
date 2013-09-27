<?php

/*
 * Title: Date Filter
 * Purpose:
 *  	Filtering companion to Reflect or other date based filtering
*/

// ---------------------------------------------------
// Date Filter Class
// ---------------------------------------------------

if (!class_exists("dateFilter")) {
	class dateFilter {
		var $month,$year,$day,$dateSource;
	
		function dateFilter($month,$year,$day,$dateSource) {
			$this->month = $month;
			$this->year = $year;
			$this->day = $day;
			$this->dateSource = $dateSource;
		}
		function execute($value) {
			$month = $this->month;
			$year = $this->year;
			$day = $this->day;
			$unset = 1;
			
			if ($year && !$month && !$day) { // Year only e.g. 2007
				$min = mktime(0,0,0,1,1,$year);
				$max = mktime(23,59,59,12,31,$year);
			} else if ($year && $month && !$day) { // Year and month e.g. 2007-01
				$min = mktime(0,0,0,$month, 1, $year);
				$max = mktime(23,59,59,$month, date("t", $min), $year);
			} else if ($year && $month && $day) { // Year month and day e.g. 2007-01-11
				$min = mktime(0,0,0,$month, $day, $year);
				$max = mktime(23,59,59,$month, $day, $year);
			}
			
			$compare = $value[$this->dateSource];
			if ($compare < $min || $compare > $max){
				$unset = 0;
			}
			return $unset;
		}
	}
}

// ---------------------------------------------------
// Date Filter Parameters
// ---------------------------------------------------

$source = isset($dateFilterSource) ? $dateFilterSource : 'get';
/*
	Param: dateFilterSource

	Purpose:
	Source for the day, month, and year to filter by

	Options:
	get - gets the value of year, month, and day from the URL (pre-appended with the Ditto ID)
	params - gets the value from the snippet cal
	text - name of the request variable to use

	Default:
	get
*/
$dateSource = isset($dateSource) ? $dateSource : "createdon";
/*
	Param: dateSource

	Purpose:
	Source of the [+date+] placeholder

	Options:
	# - Any UNIX timestamp from MODx fields or TVs such as createdon, pub_date, or editedon
	
	Default:
	"createdon"
	
	Related:
	- <dateFormat>
*/
if(!isset($dateFilterDefault)) $dateFilterDefault = 0;
/*
	Param: dateFilterDefault

	Purpose:
	Determine the default filter

	Options:
	0 - filter off
	1 - current year
	2 - current year and month
	3 - current year, month, and day
	
	Default:
	0
*/
if ($source == 'get') {
	$year = (!empty($_GET[$dittoID.'year']) && $_GET[$dittoID.'year'] != 'false') ? intval($_GET[$dittoID.'year']) : 0;
	$month = (!empty($_GET[$dittoID.'month']) && $_GET[$dittoID.'month'] != 'false') ? intval($_GET[$dittoID.'month']) : 0;
	$day = (!empty($_GET[$dittoID.'day']) && $_GET[$dittoID.'day'] != 'false') ? intval($_GET[$dittoID.'day']) : 0;
} else if ($source == 'params'){
	$month = isset($month) ? intval($month) : 0;
	/*
		Param: month

		Purpose:
		Month to filter by

		Options:
		# - Number between 1-12 (inclusive) that corresponds to the month to filter by

		Default:
		[NULL]
	*/
	$year = isset($year) ? intval($year) : 0;
	/*
		Param: year

		Purpose:
		Year to filter by

		Options:
		# - Any numerical year (4 numbers; ex: 2006)

		Default:
		[NULL]
	*/
	$day = isset($day) ? intval($day) : 0;
	/*
		Param: day

		Purpose:
		Day to filter by

		Options:
		# - Any numerical day within the current month

		Default:
		[NULL]
	*/
} else {
	if (!empty($_REQUEST[$dittoID.$source])) {
		$date = getdate(strtotime($_REQUEST[$dittoID.$source]));
		$year = $date["year"];
		$month = $date["mon"];
		$day = $date["mday"];
	}
}

// ---------------------------------------------------
// Date Filter Defaults
// ---------------------------------------------------


switch ($dateFilterDefault) {
	case 0:
		// do nothing
	break;

	case 1:
		$cDate = getdate();
		$year = ($year) ? $year : $cDate["year"]; 
	break;
	
	case 2:
		$cDate = getdate();
		$year = ($year) ? $year : $cDate["year"]; 
		$month = ($month) ? $month : $cDate["mon"];
	break;
	
	case 3:
		$cDate = getdate();
		$year = ($year) ? $year : $cDate["year"]; 
		$month = ($month) ? $month : $cDate["mon"];
		$day = ($day) ? $day : $cDate["mday"];
	break;
}

// ---------------------------------------------------
// Date Filter Placeholders
// ---------------------------------------------------

if ($year) {
	$modx->setPlaceholder($dittoID."year",$year);
	/*
		Placeholder: year

		Content:
		Year being filtered by
	*/
}
if ($month && $year) {
	$month_text = strftime("%B", mktime(10, 10, 10, $month, 10, $year));
	$modx->setPlaceholder($dittoID."month",$month_text);
	/*
		Placeholder: month

		Content:
		Month being filtered by
	*/
	$modx->setPlaceholder($dittoID."month_numeric",$month);
	/*
		Placeholder: month

		Content:
		Numeric version of the month being filtered by
	*/
}
if ($day && $month && $year) {
	$modx->setPlaceholder($dittoID."day",$day);
	/*
		Placeholder: day

		Content:
		Day being filtered by
	*/
}

// ---------------------------------------------------
// Date Filter Execution
// ---------------------------------------------------
if ($year || ($year && $month) || ($year && $month && $day)) {
	$dateFilterOject = new dateFilter($month,$year,$day,$dateSource);
	$filters["custom"]["dateFilter"] = array($dateSource,array($dateFilterOject,"execute"));
}

?>