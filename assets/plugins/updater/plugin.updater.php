<?php
/*
@TODO:
— auto backup system files
— rollback option for updater
*/

if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (empty($_SESSION['mgrInternalKey'])) {
    return;
}
// get manager role
$internalKey = $modx->getLoginUserID();
$sid = $modx->sid;
$role = isset($_SESSION['mgrRole']) ? $_SESSION['mgrRole'] : '';
$user = isset($_SESSION['mgrShortname']) ? $_SESSION['mgrShortname'] : '';
$wdgVisibility = isset($wdgVisibility) ? $wdgVisibility : '';
$ThisRole = isset($ThisRole) ? $ThisRole : '';
$ThisUser = isset($ThisUser) ? $ThisUser : '';
$version = isset($version) ? $version : 'evolution-cms/evolution';
$type = isset($type) ? $type : 'tags';
$showButton = isset($showButton) ? $showButton : 'AdminOnly';
$result = '';

if ($role != 1 && $wdgVisibility == 'AdminOnly') {

} else if ($role == 1 && $wdgVisibility == 'AdminExcluded') {

} else if ($role != $ThisRole && $wdgVisibility == 'ThisRoleOnly') {

} else if ($user != $ThisUser && $wdgVisibility == 'ThisUserOnly') {

} else {

    //lang
    $_lang = array();
    $plugin_path = MODX_BASE_PATH . "assets/plugins/updater/";
    include($plugin_path . 'lang/english.php');
    if (file_exists($plugin_path . 'lang/' . $modx->config['manager_language'] . '.php')) {
        include($plugin_path . 'lang/' . $modx->config['manager_language'] . '.php');
    }

    $e = &$modx->Event;
    if ($e->name == 'OnSiteRefresh') {
        array_map("unlink", glob(MODX_BASE_PATH . 'assets/cache/updater/*.json'));
    }

    if ($e->name == 'OnManagerWelcomeHome') {
        $errorsMessage = '';
        $errors = 0;
        if (!extension_loaded('curl')) {
            $errorsMessage .= '-' . $_lang['error_curl'] . '<br>';
            $errors += 1;
            $curlNotReady = true;
        }
        if (!extension_loaded('zip')) {
            $errorsMessage .= '-' . $_lang['error_zip'] . '<br>';
            $errors += 1;
        }
        if (!extension_loaded('openssl')) {
            $errorsMessage .= '-' . $_lang['error_openssl'] . '<br>';
            $errors += 1;
        }
        if (!is_writable(MODX_BASE_PATH . 'assets/')) {
            $errorsMessage .= '-' . $_lang['error_overwrite'] . '<br>';
            $errors += 1;
        }

        // Avoid "Fatal error: Call to undefined function curl_init()"
        if (isset($curlNotReady)) {
            $output = '<div class="card-body">
                <small style="color:red;font-size:10px">' . $errorsMessage . '</small></div>';

            $widgets['updater'] = array(
                'menuindex' => '1',
                'id' => 'updater',
                'cols' => 'col-sm-12',
                'icon' => 'fa-exclamation-triangle',
                'title' => $_lang['system_update'],
                'body' => $output
            );
            $e->output(serialize($widgets));
            return;
        }

        $_SESSION['updatelink'] = md5(time());

        // if a GitHub commit feed
        if ($type == 'commits') {

            // include MagPieRSS
            require_once('media/rss/rss_fetch.inc');
            $branchPath = 'https://github.com/'.$version.'/'.$type.'/'.$branch;
            $url = $branchPath.'.atom';

            // create Feed
            $updateButton = '';
            $rss = @fetch_rss($url);
                if (!$rss){
                    $errorsMessage .= '-'.$_lang['error_failedtogetfeed'].':'.$url.'<br>';
                    $errors += 1;
                }
                $updateButton .= '<div class="table-responsive" style="max-height:200px;"><table class="table data">';
                $updateButton .= '<thead><tr><th>'.$_lang['table_commitdate'].'</th><th>'.$_lang['table_titleauthor'].'</th><th></th></tr></thead><tbody>';

            $items = array_slice($rss->items, 0, $commitCount);
            foreach ($items as $item) {
                $commitid = $item['id'];
                $commit = substr($commitid, strpos($commitid, "Commit/") + 7);
                $href = $item['link'];
                $title = $item['title'];
                $pubdate = $item['updated'];
                $pubdate = $modx->toDateFormat(strtotime($pubdate));
                $author = $item['author_name'];
                $updateButton .= '<tr><td><b>' . $pubdate . '</b></td><td><a href="' . $href . '" target="_blank">' . $title . '</a> (' . $author . ')</td>';
                if (($role != 1) AND ($showButton == 'AdminOnly') OR ($showButton == 'hide') OR ($errors > 0)) {
                    $updateButton .= '<td></td></tr>';
                }  else {
                    $updateButton .= '<td><a onclick="return confirm(\''.$_lang['are_you_sure_update'].'\')" target="_parent" title="sha: '.$commit.'" class="btn btn-sm btn-danger" href="'.MODX_SITE_URL.$_SESSION['updatelink'].'&sha='.$commit.'">'.$_lang['updateButtonCommit_txt'].'</a></td></tr>';
                }
            }

            $updateButton .= '</tbody></table></div>';

            $output = '<div class="card-body">GitHub commits for <strong>(<a target="_blank" href="' . $branchPath . '">' . $branchPath . '</a>)</strong><br>
            <small style="color:red;font-size:10px"> ' . $_lang['bkp_before_msg'] . '</small><br>
            <small style="color:red;font-size:10px">' . $errorsMessage . '</small>
                    </div>' . $updateButton;
                // Add widget to end as is always displayed for commits
                $widgets['updater'] = array(
                    'menuindex' =>'1000',
                    'id' => 'updater',
                    'cols' => 'col-sm-12',
                    'icon' => 'fa-exclamation-triangle',
                    'title' => $_lang['system_update'],
                    'body' => $output
                );
                $e->output(serialize($widgets));
        } else {
            // Create directory 'assets/cache/updater'
            if (!file_exists(MODX_BASE_PATH . 'assets/cache/updater')) {
                mkdir(MODX_BASE_PATH . 'assets/cache/updater', intval($modx->config['new_folder_permissions'], 8), true);
            }

            $output = '';
            
            $currentVersion = $modx->getVersionData();
            $currentMajorVersion = array_shift(explode('.', $currentVersion['version']));

            if (!file_exists(MODX_BASE_PATH . 'assets/cache/updater/check_' . date("d") . '.json')) {
                $ch = curl_init();
                $url = 'https://api.github.com/repos/' . $version . '/' . $type;
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, false);
                //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_REFERER, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: updateNotify widget'));
                $info = curl_exec($ch);
                curl_close($ch);
                if (substr($info, 0, 1) != '[') {
                    return;
                }
                $info = json_decode($info, true);

                foreach($info as $key => $val ) {
                    if( $currentMajorVersion == array_shift(explode('.', $val['name'])) ){
                        
                        $git['version'] = $val['name'];
                        
                        if(strpos($val['name'], 'alpha')) {
                            $git['alpha'] = $val['name'];
                            continue;
                        }
                        elseif(strpos($val['name'], 'beta')) {
                            $git['beta'] = $val['name'];
                            continue;
                        }
                        else {
                            $git['stable'] = $val['name'];
                            break;
                        }
                    }
                 }

                file_put_contents(MODX_BASE_PATH . 'assets/cache/updater/check_' . date("d") . '.json', json_encode($git));
            } else {
                $git = file_get_contents(MODX_BASE_PATH . 'assets/cache/updater/check_' . date("d") . '.json');
                $git = json_decode($git, true);
            }

            if($stableOnly == 'true') {
                if(version_compare($git['version'], $git['stable'], '!=')) {
                    $git['version'] = $git['stable'];
                }
            }

            $_SESSION['updateversion'] = $git['version'];

            if (version_compare($git['version'], $currentVersion['version'], '>') && $git['version'] != '') {
                // get manager role
                $role = $_SESSION['mgrRole'];
                if (($role != 1) AND ($showButton == 'AdminOnly') OR ($showButton == 'hide') OR ($errors > 0)) {
                    $updateButton = '';
                } else {
                    $updateButton = '<a target="_parent" onclick="return confirm(\''.$_lang['are_you_sure_update'].'\')" href="' . MODX_SITE_URL . $_SESSION['updatelink'] . '" class="btn btn-sm btn-danger">' . $_lang['updateButton_txt'] . ' ' . $git['version'] . '</a><br><br>';
                }

                $output = '<div class="card-body">' . $_lang['cms_outdated_msg'] . ' <strong>' . $git['version'] . '</strong> <br><br>
                    ' . $updateButton . '
                    <small style="color:red;font-size:10px"> ' . $_lang['bkp_before_msg'] . '</small>
                    <small style="color:red;font-size:10px">' . $errorsMessage . '</small></div>';

                $widgets['updater'] = array(
                    'menuindex' => '1',
                    'id' => 'updater',
                    'cols' => 'col-sm-12',
                    'icon' => 'fa-exclamation-triangle',
                    'title' => $_lang['system_update'],
                    'body' => $output
                );

                $e->output(serialize($widgets));
            }
        }
    }

    if ($e->name == 'OnPageNotFound' && isset($_GET['q'])) {
        if (empty($_SESSION['mgrInternalKey']) || empty($_SESSION['updatelink']) ) {
            return;
        }
        switch ($_GET['q']) {
            case $_SESSION['updatelink']:
                $currentVersion = $modx->getVersionData();
                $commit = isset($_GET['sha']) ? $_GET['sha'] : '';
                if ($_SESSION['updateversion'] != $currentVersion['version'] || (isset($commit) && $type == 'commits')) {

                    file_put_contents(MODX_BASE_PATH . 'update.php', '<?php
function downloadFile(
    $url,
    $path
) {
    $newfname = $path;
    $file = null;
    $newf = null;
    try {
        if (ini_get("allow_url_fopen")) {
            $file = fopen($url, "rb");
            if ($file) {
                $newf = fopen($newfname, "wb");
                if ($newf) {
                    while (!feof($file)) {
                        fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    }
                }
            }
        } elseif (function_exists("curl_version")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $content = curl_exec($ch);
            curl_close($ch);
            file_put_contents($newfname, $content);
        }
    } catch (Exception $e) {
        $this->errors[] = array("ERROR:Download", $e->getMessage());
        return false;
    }
    if ($file) {
        fclose($file);
    }

    if ($newf) {
        fclose($newf);
    }

    return true;
}

function removeFolder($path)
{
    $dir = realpath($path);
    if (!is_dir($dir)) {
        return;
    }

    $it = new RecursiveDirectoryIterator($dir);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->getFilename() === "." || $file->getFilename() === "..") {
            continue;
        }
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

function copyFolder(
    $src,
    $dest
) {
    $path = realpath($src);
    $dest = realpath($dest);
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $name => $object) {

        $startsAt = substr(dirname($name), strlen($path));
        mmkDir($dest . $startsAt);
        if ($object->isDir()) {
            mmkDir($dest . substr($name, strlen($path)));
        }

        if (is_writable($dest . $startsAt) and $object->isFile()) {
            copy((string)$name, $dest . $startsAt . DIRECTORY_SEPARATOR . basename($name));
        }
    }
}

function mmkDir(
    $folder,
    $perm = 0777
) {
    if (!is_dir($folder)) {
        mkdir($folder, $perm);
    }
}

$version = "' . $version . '";

downloadFile("https://github.com/" . $version . "/archive/" . $_GET["version"] . ".zip", "evo.zip");
$zip = new ZipArchive;
$res = $zip->open(__DIR__ . "/evo.zip");
$zip->extractTo(__DIR__ . "/temp");
$zip->close();

if ($handle = opendir(__DIR__ . "/temp")) {
    while (false !== ($name = readdir($handle))) {
        if ($name != "." && $name != "..") {
            $dir = $name;
        }
    }
    closedir($handle);
}
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/chunks");
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/tvs");
removeFolder(__DIR__ . "/temp/" . $dir . "/install/assets/templates");
unlink(__DIR__ . "/temp/" . $dir . "/ht.access");
unlink(__DIR__ . "/temp/" . $dir . "/README.md");
unlink(__DIR__ . "/temp/" . $dir . "/sample-robots.txt");
unlink(__DIR__ . "/temp/" . $dir . "/composer.json");

if (is_file(__DIR__ . "/assets/cache/siteManager.php")) {

    unlink(__DIR__ . "/temp/" . $dir . "/assets/cache/siteManager.php");
    include_once(__DIR__ . "/assets/cache/siteManager.php");
    if (!defined("MGR_DIR")) {
        define("MGR_DIR", "manager");
    }
    if (MGR_DIR != "manager") {
        mmkDir(__DIR__ . "/temp/" . $dir . "/" . MGR_DIR);
        copyFolder(__DIR__ . "/temp/" . $dir . "/manager", __DIR__ . "/temp/" . $dir . "/" . MGR_DIR);
        removeFolder(__DIR__ . "/temp/" . $dir . "/manager");
    }
    // echo __DIR__."/temp/".$dir."/".MGR_DIR;
}
copyFolder(__DIR__ . "/temp/" . $dir, __DIR__ . "/");
removeFolder(__DIR__ . "/temp");
unlink(__DIR__ . "/evo.zip");
unlink(__DIR__ . "/update.php");
header("Location: ' . constant('MODX_SITE_URL') . 'install/index.php?action=mode");');
                if ($result === false){
                    echo 'Update failed: cannot write to ' . MODX_BASE_PATH . 'update.php';
                } else {
                    if ($type == 'commits') {
                        $versionGet = $commit;
                        $versionText = $version . '/' . $type . '/' . $branch . '/' . $commit;
                    } else {
                        $versionGet = $_SESSION['updateversion'];
                        $versionText = $_SESSION['updateversion'];
                    }
                    echo '<html><head></head><body><h2>Evolution Updater</h2>
                          <p>Downloading version: <strong>' . $versionText . '</strong>.</p>
                          <p>You will be redirected to the update wizard shortly.</p>
                          <p>Please wait...</p>
                          <script>window.location = "' . MODX_SITE_URL . 'update.php?version=' . $versionGet . '";</script>
                          </body></html>';
                }
            }
            die();
            break;
        }
    }
}
