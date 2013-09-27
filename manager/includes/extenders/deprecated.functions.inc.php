<?php
$this->old = new OldFunctions();
class OldFunctions {
    
    function dbConnect()                 {global $modx;       $modx->db->connect();$modx->rs = $modx->db->conn;}
    function dbQuery($sql)               {global $modx;return $modx->db->query($sql);}
    function recordCount($rs)            {global $modx;return $modx->db->getRecordCount($rs);}
    function fetchRow($rs,$mode='assoc') {global $modx;return $modx->db->getRow($rs, $mode);}
    function affectedRows($rs)           {global $modx;return $modx->db->getAffectedRows($rs);}
    function insertId($rs)               {global $modx;return $modx->db->getInsertId($rs);}
    function dbClose()                   {global $modx;       $modx->db->disconnect();}
    
    function makeList($array, $ulroot= 'root', $ulprefix= 'sub_', $type= '', $ordered= false, $tablevel= 0) {
        // first find out whether the value passed is an array
        if (!is_array($array)) {
            return "<ul><li>Bad list</li></ul>";
        }
        if (!empty ($type)) {
            $typestr= " style='list-style-type: $type'";
        } else {
            $typestr= "";
        }
        $tabs= "";
        for ($i= 0; $i < $tablevel; $i++) {
            $tabs .= "\t";
        }
        $listhtml= $ordered == true ? $tabs . "<ol class='$ulroot'$typestr>\n" : $tabs . "<ul class='$ulroot'$typestr>\n";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $listhtml .= $tabs . "\t<li>" . $key . "\n" . $this->makeList($value, $ulprefix . $ulroot, $ulprefix, $type, $ordered, $tablevel +2) . $tabs . "\t</li>\n";
            } else {
                $listhtml .= $tabs . "\t<li>" . $value . "</li>\n";
            }
        }
        $listhtml .= $ordered == true ? $tabs . "</ol>\n" : $tabs . "</ul>\n";
        return $listhtml;
    }

    
    function getUserData() {
        $client['ip'] = $_SERVER['REMOTE_ADDR'];
        $client['ua'] = $_SERVER['HTTP_USER_AGENT'];
    	return $client;
    }
    
    # Returns true, install or interact when inside manager
    // deprecated
    function insideManager() {
        $m= false;
        if (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE == 'true') {
            $m= true;
            if (defined('SNIPPET_INTERACTIVE_MODE') && SNIPPET_INTERACTIVE_MODE == 'true')
                $m= "interact";
            else
                if (defined('SNIPPET_INSTALL_MODE') && SNIPPET_INSTALL_MODE == 'true')
                    $m= "install";
        }
        return $m;
    }

    // deprecated
    function putChunk($chunkName) { // alias name >.<
    	global $modx;
        return $modx->getChunk($chunkName);
    }

    function getDocGroups() {
    	global $modx;
        return $modx->getUserDocGroups();
    } // deprecated

    function changePassword($o, $n) {
        return changeWebUserPassword($o, $n);
    } // deprecated
    
    function mergeDocumentMETATags($template) {
    	global $modx;
        if ($modx->documentObject['haskeywords'] == 1) {
            // insert keywords
            $keywords = $modx->getKeywords();
            if (is_array($keywords) && count($keywords) > 0) {
	            $keywords = implode(", ", $keywords);
	            $metas= "\t<meta name=\"keywords\" content=\"$keywords\" />\n";
            }

	    // Don't process when cached
	    $modx->documentObject['haskeywords'] = '0';
        }
        if ($modx->documentObject['hasmetatags'] == 1) {
            // insert meta tags
            $tags= $modx->getMETATags();
            foreach ($tags as $n => $col) {
                $tag= strtolower($col['tag']);
                $tagvalue= $col['tagvalue'];
                $tagstyle= $col['http_equiv'] ? 'http-equiv' : 'name';
                $metas .= "\t<meta $tagstyle=\"$tag\" content=\"$tagvalue\" />\n";
            }

	    // Don't process when cached
	    $modx->documentObject['hasmetatags'] = '0';
        }
	if ($metas) $template = preg_replace("/(<head>)/i", "\\1\n\t" . trim($metas), $template);
        return $template;
    }
    
    function getMETATags($id= 0) {
    	global $modx;
        if ($id == 0) {
            $id= $modx->documentObject['id'];
        }
        $sql= "SELECT smt.* " .
        "FROM " . $modx->getFullTableName("site_metatags") . " smt " .
        "INNER JOIN " . $modx->getFullTableName("site_content_metatags") . " cmt ON cmt.metatag_id=smt.id " .
        "WHERE cmt.content_id = '$id'";
        $ds= $modx->db->query($sql);
        $limit= $modx->db->getRecordCount($ds);
        $metatags= array ();
        if ($limit > 0) {
            for ($i= 0; $i < $limit; $i++) {
                $row= $modx->db->getRow($ds);
                $metatags[$row['name']]= array (
                    "tag" => $row['tag'],
                    "tagvalue" => $row['tagvalue'],
                    "http_equiv" => $row['http_equiv']
                );
            }
        }
        return $metatags;
    }
    
    function userLoggedIn() {
    	global $modx;
        $userdetails= array ();
        if ($modx->isFrontend() && isset ($_SESSION['webValidated'])) {
            // web user
            $userdetails['loggedIn']= true;
            $userdetails['id']= $_SESSION['webInternalKey'];
            $userdetails['username']= $_SESSION['webShortname'];
            $userdetails['usertype']= 'web'; // added by Raymond
            return $userdetails;
        } else
            if ($modx->isBackend() && isset ($_SESSION['mgrValidated'])) {
                // manager user
                $userdetails['loggedIn']= true;
                $userdetails['id']= $_SESSION['mgrInternalKey'];
                $userdetails['username']= $_SESSION['mgrShortname'];
                $userdetails['usertype']= 'manager'; // added by Raymond
                return $userdetails;
            } else {
                return false;
            }
    }
    
    function getKeywords($id= 0) {
    	global $modx;
        if ($id == 0) {
            $id= $modx->documentObject['id'];
        }
        $tblKeywords= $modx->getFullTableName('site_keywords');
        $tblKeywordXref= $modx->getFullTableName('keyword_xref');
        $sql= "SELECT keywords.keyword FROM " . $tblKeywords . " AS keywords INNER JOIN " . $tblKeywordXref . " AS xref ON keywords.id=xref.keyword_id WHERE xref.content_id = '$id'";
        $result= $modx->db->query($sql);
        $limit= $modx->db->getRecordCount($result);
        $keywords= array ();
        if ($limit > 0) {
            for ($i= 0; $i < $limit; $i++) {
                $row= $modx->db->getRow($result);
                $keywords[]= $row['keyword'];
            }
        }
        return $keywords;
    }
    
    function makeFriendlyURL($pre, $suff, $alias, $isfolder=0, $id=0) {
    	global $modx;
        if ($id == $modx->config['site_start'] && $modx->config['seostrict']==='1') {return '/';}
        $Alias = explode('/',$alias);
        $alias = array_pop($Alias);
        $dir = implode('/', $Alias);
        unset($Alias);
        if($modx->config['make_folders']==='1' && $isfolder==1) $suff = '/';
        return ($dir != '' ? "$dir/" : '') . $pre . $alias . $suff;
    }

    /*############################################
      Etomite_dbFunctions.php
      New database functions for Etomite CMS
    Author: Ralph A. Dahlgren - rad14701@yahoo.com
    Etomite ID: rad14701
    See documentation for usage details
    ############################################*/
    function getIntTableRows($fields= "*", $from= "", $where= "", $sort= "", $dir= "ASC", $limit= "") {
        // function to get rows from ANY internal database table
    	global $modx;
        if ($from == "") {
            return false;
        } else {
            $where= ($where != "") ? "WHERE $where" : "";
            $sort= ($sort != "") ? "ORDER BY $sort $dir" : "";
            $limit= ($limit != "") ? "LIMIT $limit" : "";
            $tbl= $modx->getFullTableName($from);
            $sql= "SELECT $fields FROM $tbl $where $sort $limit;";
            $result= $modx->db->query($sql);
            $resourceArray= array ();
            for ($i= 0; $i < @ $modx->db->getRecordCount($result); $i++) {
                array_push($resourceArray, @ $modx->db->getRow($result));
            }
            return $resourceArray;
        }
    }

    function putIntTableRow($fields= "", $into= "") {
        // function to put a row into ANY internal database table
    	global $modx;
        if (($fields == "") || ($into == "")) {
            return false;
        } else {
            $tbl= $modx->getFullTableName($into);
            $sql= "INSERT INTO $tbl SET ";
            foreach ($fields as $key => $value) {
                $sql .= $key . "=";
                if (is_numeric($value))
                    $sql .= $value . ",";
                else
                    $sql .= "'" . $value . "',";
            }
            $sql= rtrim($sql, ",");
            $sql .= ";";
            $result= $modx->db->query($sql);
            return $result;
        }
    }

    function updIntTableRow($fields= "", $into= "", $where= "", $sort= "", $dir= "ASC", $limit= "") {
        // function to update a row into ANY internal database table
    	global $modx;
        if (($fields == "") || ($into == "")) {
            return false;
        } else {
            $where= ($where != "") ? "WHERE $where" : "";
            $sort= ($sort != "") ? "ORDER BY $sort $dir" : "";
            $limit= ($limit != "") ? "LIMIT $limit" : "";
            $tbl= $modx->getFullTableName($into);
            $sql= "UPDATE $tbl SET ";
            foreach ($fields as $key => $value) {
                $sql .= $key . "=";
                if (is_numeric($value))
                    $sql .= $value . ",";
                else
                    $sql .= "'" . $value . "',";
            }
            $sql= rtrim($sql, ",");
            $sql .= " $where $sort $limit;";
            $result= $modx->db->query($sql);
            return $result;
        }
    }

    function getExtTableRows($host= "", $user= "", $pass= "", $dbase= "", $fields= "*", $from= "", $where= "", $sort= "", $dir= "ASC", $limit= "") {
        // function to get table rows from an external MySQL database
    	global $modx;
        if (($host == "") || ($user == "") || ($pass == "") || ($dbase == "") || ($from == "")) {
            return false;
        } else {
            $where= ($where != "") ? "WHERE  $where" : "";
            $sort= ($sort != "") ? "ORDER BY $sort $dir" : "";
            $limit= ($limit != "") ? "LIMIT $limit" : "";
            $tbl= $dbase . "." . $from;
            $this->dbExtConnect($host, $user, $pass, $dbase);
            $sql= "SELECT $fields FROM $tbl $where $sort $limit;";
            $result= $modx->db->query($sql);
            $resourceArray= array ();
            for ($i= 0; $i < @ $modx->db->getRecordCount($result); $i++) {
                array_push($resourceArray, @ $modx->db->getRow($result));
            }
            return $resourceArray;
        }
    }

    function putExtTableRow($host= "", $user= "", $pass= "", $dbase= "", $fields= "", $into= "") {
        // function to put a row into an external database table
    	global $modx;
        if (($host == "") || ($user == "") || ($pass == "") || ($dbase == "") || ($fields == "") || ($into == "")) {
            return false;
        } else {
            $this->dbExtConnect($host, $user, $pass, $dbase);
            $tbl= $dbase . "." . $into;
            $sql= "INSERT INTO $tbl SET ";
            foreach ($fields as $key => $value) {
                $sql .= $key . "=";
                if (is_numeric($value))
                    $sql .= $value . ",";
                else
                    $sql .= "'" . $value . "',";
            }
            $sql= rtrim($sql, ",");
            $sql .= ";";
            $result= $modx->db->query($sql);
            return $result;
        }
    }

    function updExtTableRow($host= "", $user= "", $pass= "", $dbase= "", $fields= "", $into= "", $where= "", $sort= "", $dir= "ASC", $limit= "") {
        // function to update a row into an external database table
    	global $modx;
        if (($fields == "") || ($into == "")) {
            return false;
        } else {
            $this->dbExtConnect($host, $user, $pass, $dbase);
            $tbl= $dbase . "." . $into;
            $where= ($where != "") ? "WHERE $where" : "";
            $sort= ($sort != "") ? "ORDER BY $sort $dir" : "";
            $limit= ($limit != "") ? "LIMIT $limit" : "";
            $sql= "UPDATE $tbl SET ";
            foreach ($fields as $key => $value) {
                $sql .= $key . "=";
                if (is_numeric($value))
                    $sql .= $value . ",";
                else
                    $sql .= "'" . $value . "',";
            }
            $sql= rtrim($sql, ",");
            $sql .= " $where $sort $limit;";
            $result= $modx->db->query($sql);
            return $result;
        }
    }

    function dbExtConnect($host, $user, $pass, $dbase) {
        // function to connect to external database
    	global $modx;
        $tstart= $modx->getMicroTime();
        if (@ !$modx->rs= $modx->db->connect($host, $user, $pass)) {
            $modx->messageQuit("Failed to create connection to the $dbase database!");
        } else {
            $modx->db->selectDb($dbase);
            $tend= $modx->getMicroTime();
            $totaltime= $tend - $tstart;
            if ($modx->dumpSQL) {
                $modx->queryCode .= "<fieldset style='text-align:left'><legend>Database connection</legend>" . sprintf("Database connection to %s was created in %2.4f s", $dbase, $totaltime) . "</fieldset><br />";
            }
            $modx->queryTime= $modx->queryTime + $totaltime;
        }
    }

    function getFormVars($method= "", $prefix= "", $trim= "", $REQUEST_METHOD) {
        //  function to retrieve form results into an associative array
    	global $modx;
        $results= array ();
        $method= strtoupper($method);
        if ($method == "")
            $method= $REQUEST_METHOD;
        if ($method == "POST")
            $method= & $_POST;
        elseif ($method == "GET") $method= & $_GET;
        else
            return false;
        reset($method);
        foreach ($method as $key => $value) {
            if (($prefix != "") && (substr($key, 0, strlen($prefix)) == $prefix)) {
                if ($trim) {
                    $pieces= explode($prefix, $key, 2);
                    $key= $pieces[1];
                    $results[$key]= $value;
                } else
                    $results[$key]= $value;
            }
            elseif ($prefix == "") $results[$key]= $value;
        }
        return $results;
    }

    ########################################
    // END New database functions - rad14701
    ########################################
}