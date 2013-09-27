<?php
// ---------------------------------------------------
// Group: Filters
// The filter works with TV settings that have multiple values, checking for compliance with each

// Example call Ditto:
// [!Ditto? &startID=`42` &tpl=`tpl` &extenders=`andFilter` &andFilterTv=`color:green,red;size:L,XL,XXL` !]
//  will return documents where all the red and green things the size L, XL and XXL ;)
// ---------------------------------------------------
 
// If no fieldname value has been supplied, don't do anything else
if ($andFilterTv === false) {
    return false;  
}
 
global $tvsarray;
$tmparray = explode ( ';', $andFilterTv);
foreach ( $tmparray as $tmpvalue ) {
        $tmpexplode = explode ( ':', $tmpvalue );
        $tvsarray[] = $tmpexplode;
        $filtertvs .= (empty($filtertvs)?"":",") . $tmpexplode[0];
        }
     
 
 
$filters["custom"]["andFilter"] = array( $filtertvs, "andFilter");
   
if (!function_exists("andFilter")) {
  function andFilter($resource) {
    global $modx,$tvsarray;
    $good = true;
    foreach ( $tvsarray as $tv ) {
        $values = explode( ',', $tv[1] );
        if ( array_search($resource[$tv[0]], $values) !== false  ) {     
            $good = $good && true;
        } else {
            $good = $good && false;
        }
    }
     
    if ($good) { return 1; } else { return 0; }
     
  }
}
?>