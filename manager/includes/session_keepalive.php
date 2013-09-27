<?php
/**
 * session_keepalive.php
 *
 * This page is requested once in awhile to keep the session alive and kicking.
 */
require_once(dirname(__FILE__).'/protect.inc.php');

$ok = false;
if ($rt = @ include_once('config.inc.php')) {
// Keep it alive
  startCMSSession();
  if($_GET['tok'] == md5(session_id())) {
      $ok = true;
  }
}
header('Content-type: application/json');
if($ok) {
    echo '{status:"ok"}';
} else {
  echo '{status:"null"}';
}
