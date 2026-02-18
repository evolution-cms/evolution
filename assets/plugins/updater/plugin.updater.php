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
$supportLink = isset($supportLink) ? trim((string)$supportLink) : '';
if ($supportLink === '') {
    $supportLink = 'https://evo.im/support.html';
}
$result = '';

if (!function_exists('updaterParseSemver')) {
    function updaterParseSemver($versionString)
    {
        $match = [];
        if (preg_match('/(\d+)\.(\d+)\.(\d+)/', (string)$versionString, $match)) {
            return [(int)$match[1], (int)$match[2], (int)$match[3]];
        }

        $numbers = [];
        preg_match_all('/\d+/', (string)$versionString, $numbers);
        $parts = isset($numbers[0]) ? $numbers[0] : [];

        return [
            isset($parts[0]) ? (int)$parts[0] : 0,
            isset($parts[1]) ? (int)$parts[1] : 0,
            isset($parts[2]) ? (int)$parts[2] : 0,
        ];
    }
}

if (!function_exists('updaterGetSeverity')) {
    function updaterGetSeverity($currentVersion, $latestVersion)
    {
        $current = updaterParseSemver($currentVersion);
        $latest = updaterParseSemver($latestVersion);

        if ($latest[0] > $current[0]) {
            return 'critical';
        }
        if ($latest[1] > $current[1]) {
            return 'warning';
        }
        if ($latest[2] > $current[2]) {
            return 'info';
        }

        return 'info';
    }
}

if (!function_exists('updaterBuildHideKey')) {
    function updaterBuildHideKey($latestVersionRaw, $userId)
    {
        $versionPart = preg_replace('/[^A-Za-z0-9]+/', '_', strtolower((string)$latestVersionRaw));
        $versionPart = trim((string)$versionPart, '_');
        if ($versionPart === '') {
            $versionPart = 'version';
        }

        return '_hide_updater_notice_until_' . $versionPart . '_u_' . (int)$userId;
    }
}

if (!function_exists('updaterBuildReleaseUrls')) {
    function updaterBuildReleaseUrls($repository, $latestVersionRaw)
    {
        $repo = trim((string)$repository);
        $latest = trim((string)$latestVersionRaw);
        $base = 'https://github.com/' . $repo . '/releases';
        $urls = [];

        if ($latest !== '') {
            $urls[] = $base . '/tag/' . rawurlencode($latest);
            if (strpos($latest, 'v') !== 0) {
                $urls[] = $base . '/tag/' . rawurlencode('v' . $latest);
            }
        }
        $urls[] = $base;

        return array_values(array_unique($urls));
    }
}

if (!function_exists('updaterFetchReleasePublishedAt')) {
    function updaterFetchReleasePublishedAt($repository, $versionRaw)
    {
        $repo = trim((string)$repository);
        $version = trim((string)$versionRaw);

        if ($repo === '' || $version === '') {
            return '';
        }

        $tags = [$version];
        if (strpos($version, 'v') === 0) {
            $withoutPrefix = substr($version, 1);
            if ($withoutPrefix !== '') {
                $tags[] = $withoutPrefix;
            }
        } else {
            $tags[] = 'v' . $version;
        }

        foreach (array_unique($tags) as $tag) {
            $url = 'https://api.github.com/repos/' . $repo . '/releases/tags/' . rawurlencode($tag);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
            $response = curl_exec($ch);
            curl_close($ch);

            if (!is_string($response) || $response === '' || strpos(ltrim($response), '{') !== 0) {
                continue;
            }

            $release = json_decode($response, true);
            if (isset($release['published_at']) && $release['published_at'] !== '') {
                return (string)$release['published_at'];
            }
        }

        return '';
    }
}

if (!function_exists('updaterFormatReleaseDate')) {
    function updaterFormatReleaseDate($dateValue)
    {
        $raw = trim((string)$dateValue);
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return $raw;
        }

        return date('d.m.Y', $timestamp);
    }
}

if ($role != 1 && $wdgVisibility == 'AdminOnly') {

} else if ($role == 1 && $wdgVisibility == 'AdminExcluded') {

} else if ($role != $ThisRole && $wdgVisibility == 'ThisRoleOnly') {

} else if ($user != $ThisUser && $wdgVisibility == 'ThisUserOnly') {

} else {

    //lang
    $_lang = [];
    $plugin_path = MODX_BASE_PATH . "assets/plugins/updater/";
    include($plugin_path . 'lang/en.php');
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

            $widgets['updater'] = [
                'menuindex' => '1',
                'id' => 'updater',
                'cols' => 'col-sm-12',
                'icon' => 'fa-exclamation-triangle',
                'title' => $_lang['system_update'],
                'body' => $output
            ];
            $e->output(serialize($widgets));
            return;
        }

        if (!isset($_SESSION['updatelink'])) {
            $_SESSION['updatelink'] = md5(time());
        }

        // if a GitHub commit feed
        if ($type === 'commits') {

            $branchPath = 'https://github.com/' . $version . '/' . $type . '/' . $branch;
            $url = $branchPath . '.atom';

            // create Feed
            $updateButton = '';
            $rss = fetchCacheableRss($url, null, function (SimpleXMLElement $item) {
                return $item->getName() === 'entry' ? $item : null;
            });
            if (empty($rss)) {
                $errorsMessage .= '-' . $_lang['error_failedtogetfeed'] . ':' . $url . '<br>';
                $errors += 1;
            }
            $updateButton .= '<div class="table-responsive" style="max-height:200px;"><table class="table data">';
            $updateButton .= '<thead><tr><th>' . $_lang['table_commitdate'] . '</th><th>' . $_lang['table_titleauthor'] . '</th><th></th></tr></thead><tbody>';

            $items = array_slice($rss, 0, $commitCount);
            /** @var SimpleXMLElement $item */
            foreach ($items as $item) {
                $commitid = $item->id->__toString();
                $commit = substr($commitid, strpos($commitid, "Commit/") + 7);
                $href = $item->link['href'];
                $title = $item->title->__toString();
                $pubdate = $item->updated->__toString();
                $pubdate = $modx->toDateFormat(strtotime($pubdate));
                $author = $item->author->name->__toString();
                $updateButton .= '<tr><td><b>' . $pubdate . '</b></td><td><a href="' . $href . '" target="_blank">' . $title . '</a> (' . $author . ')</td>';
                if (($role != 1) AND ($showButton == 'AdminOnly') OR ($showButton == 'hide') OR ($errors > 0)) {
                    $updateButton .= '<td></td></tr>';
                } else {
                    $updateButton .= '<td><a onclick="return confirm(\'' . $_lang['are_you_sure_update'] . '\')" target="_parent" title="sha: '
                        . $commit . '" class="btn btn-sm btn-danger" href="' . MODX_SITE_URL . $_SESSION['updatelink']
                        . '&sha=' . $commit . '">' . $_lang['updateButtonCommit_txt'] . '</a></td></tr>';
                }
            }

            $updateButton .= '</tbody></table></div>';

            $output = '<div class="card-body">GitHub commits for <strong>(<a target="_blank" href="' . $branchPath . '">' . $branchPath . '</a>)</strong><br>
            <small style="color:red;font-size:10px"> ' . $_lang['bkp_before_msg'] . '</small><br>
            <small style="color:red;font-size:10px">' . $errorsMessage . '</small>
                    </div>' . $updateButton;
            // Add widget to end as is always displayed for commits
            $widgets['updater'] = [
                'menuindex' => '1000',
                'id' => 'updater',
                'cols' => 'col-sm-12',
                'icon' => 'fa-exclamation-triangle',
                'title' => $_lang['system_update'],
                'body' => $output
            ];
            $e->output(serialize($widgets));
        } else {
            // Create directory 'assets/cache/updater'
            if (!file_exists(MODX_BASE_PATH . 'assets/cache/updater')) {
                mkdir(MODX_BASE_PATH . 'assets/cache/updater', intval($modx->config['new_folder_permissions'], 8), true);
            }

            $output = '';

            $currentVersion = $modx->getVersionData();
            $arrayVersion = explode('.', $currentVersion['version']);
            $currentMajorVersion = array_shift($arrayVersion);

            $cacheFile = MODX_BASE_PATH . 'assets/cache/updater/check_' . date("d") . '.json';

            if (!file_exists($cacheFile)) {
                $ch = curl_init();
                $url = 'https://api.github.com/repos/' . $version . '/' . $type;
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, false);
                //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_REFERER, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
                $info = curl_exec($ch);
                curl_close($ch);
                if (substr($info, 0, 1) != '[') {
                    return;
                }
                $info = json_decode($info, true);

                foreach ($info as $key => $val) {
                    $candidateVersion = '';
                    if (isset($val['name']) && $val['name'] !== '') {
                        $candidateVersion = $val['name'];
                    } elseif (isset($val['tag_name']) && $val['tag_name'] !== '') {
                        $candidateVersion = $val['tag_name'];
                    }

                    if ($candidateVersion === '') {
                        continue;
                    }

                    $arrayVersion = explode('.', $candidateVersion);
                    if ($currentMajorVersion == array_shift($arrayVersion)) {

                        $git['version'] = $candidateVersion;
                        if (isset($val['published_at']) && $val['published_at'] !== '') {
                            $git['published_at'] = $val['published_at'];
                        }

                        if (strpos($candidateVersion, 'alpha')) {
                            $git['alpha'] = $candidateVersion;
                            continue;
                        } elseif (strpos($candidateVersion, 'beta')) {
                            $git['beta'] = $candidateVersion;
                            continue;
                        } else {
                            $git['stable'] = $candidateVersion;
                            break;
                        }
                    }
                }

                file_put_contents($cacheFile, json_encode($git));
            } else {
                $git = file_get_contents($cacheFile);
                $git = json_decode($git, true);
            }

            if ($stableOnly == 'true') {
                if (isset($git['stable'])) {
                    if (version_compare($git['version'], $git['stable'], '!=')) {
                        $git['version'] = $git['stable'];
                    }
                }
            }
            if (isset($git['version']) && (!isset($git['published_at']) || $git['published_at'] === '')) {
                $fallbackPublishedAt = updaterFetchReleasePublishedAt($version, $git['version']);
                if ($fallbackPublishedAt !== '') {
                    $git['published_at'] = $fallbackPublishedAt;
                    file_put_contents($cacheFile, json_encode($git));
                }
            }

            if (isset($git['version'])) {
                $_SESSION['updateversion'] = $git['version'];
            } else {
                $git['version'] = $currentVersion['version'];
            }
            if (version_compare($git['version'], $currentVersion['version'], '>') && $git['version'] != '') {
                $currentVersionString = (string)$currentVersion['version'];
                $latestVersionRaw = (string)$git['version'];
                $hideKey = updaterBuildHideKey($latestVersionRaw, $internalKey);
                $hideUntil = (int)$modx->getConfig($hideKey);

                if ($hideUntil <= time()) {
                    $severity = updaterGetSeverity($currentVersionString, $latestVersionRaw);
                    $severityAlertClass = 'alert-info';

                    if ($severity === 'critical') {
                        $severityAlertClass = 'alert-danger';
                    } elseif ($severity === 'warning') {
                        $severityAlertClass = 'alert-warning';
                    }

                    $releaseUrls = updaterBuildReleaseUrls($version, $latestVersionRaw);
                    $releaseUrl = reset($releaseUrls);
                    $releaseFallbackUrl = end($releaseUrls);
                    $safeReleaseUrl = htmlspecialchars((string)$releaseUrl, ENT_QUOTES, 'UTF-8');
                    $safeFallbackUrl = htmlspecialchars((string)$releaseFallbackUrl, ENT_QUOTES, 'UTF-8');

                    $currentReleaseDate = updaterFormatReleaseDate(isset($currentVersion['release_date']) ? (string)$currentVersion['release_date'] : '');
                    $latestReleaseDate = updaterFormatReleaseDate(isset($git['published_at']) ? (string)$git['published_at'] : '');

                    $currentWithDate = $currentVersionString;
                    if ($currentReleaseDate !== '') {
                        $currentWithDate .= ' (' . $currentReleaseDate . ')';
                    }

                    $latestWithDate = $latestVersionRaw;
                    if ($latestReleaseDate !== '') {
                        $latestWithDate .= ' (' . $latestReleaseDate . ')';
                    }

                    $safeCurrentWithDate = htmlspecialchars($currentWithDate, ENT_QUOTES, 'UTF-8');
                    $safeLatestWithDate = htmlspecialchars($latestWithDate, ENT_QUOTES, 'UTF-8');


                    $supportUrl = $supportLink;
                    $safeSupportUrl = htmlspecialchars($supportUrl, ENT_QUOTES, 'UTF-8');

                    $hideUntilValue = strtotime('tomorrow');
                    if ($hideUntilValue === false) {
                        $hideUntilValue = time() + 86400;
                    }
                    $csrfToken = isset($_SESSION['_token']) ? (string)$_SESSION['_token'] : '';
                    $hideAction = 'return window.updaterHideForDay('
                        . json_encode($hideKey) . ','
                        . json_encode($csrfToken) . ','
                        . (int)$hideUntilValue . ', this);';
                    $safeHideAction = htmlspecialchars($hideAction, ENT_QUOTES, 'UTF-8');
                    $hideTodayHtml = '<div style="margin-top:8px;font-size:12px;">'
                        . '<a href="#" onclick="' . $safeHideAction . '" style="color:#6c757d;text-decoration:underline;">'
                        . htmlspecialchars($_lang['updater_action_hide_today'], ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '</div>';

                    $supportButtonHtml = '<a class="btn btn-sm btn-warning" href="' . $safeSupportUrl . '" target="_blank" rel="noopener noreferrer">'
                        . '<i class="fa fa-envelope"></i> ' . htmlspecialchars($_lang['updater_action_support'], ENT_QUOTES, 'UTF-8') . '</a>';

                    $releaseButtonsHtml = '<div style="margin-left:auto;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">'
                        . '<a class="btn btn-xs btn-primary" href="' . $safeReleaseUrl . '" target="_blank" rel="noopener noreferrer">'
                        . '<i class="fa fa-external-link"></i> ' . htmlspecialchars($_lang['updater_action_release'], ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '<a href="' . $safeFallbackUrl . '" target="_blank" rel="noopener noreferrer" style="font-size:12px;text-decoration:underline;color:#0d6efd;">'
                        . '<i class="fa fa-list"></i> ' . htmlspecialchars($_lang['updater_action_release_all'], ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . '</div>';

                    $cliCommand = 'cd core && ' . $_lang['updater_cli_command'];
                    $safeCliCommand = htmlspecialchars($cliCommand, ENT_QUOTES, 'UTF-8');

                    $output = '<div class="card-body" data-updater-hide-root="1">'
                        . '<div class="alert ' . $severityAlertClass . '" role="alert" style="margin-bottom:12px;">'
                        . '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">'
                        . '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">'
                        . '<strong style="color:#dc3545;">' . $safeCurrentWithDate . '</strong>'
                        . '<i class="fa fa-arrow-right" aria-hidden="true"></i>'
                        . '<strong style="color:#28a745;">' . $safeLatestWithDate . '</strong>'
                        . '</div>'
                        . $releaseButtonsHtml
                        . '</div>'
                        . '</div>'

                        . '<div style="margin:0 0 12px 0;">'
                        . '<p style="margin:0 0 8px 0;"><i class="fa fa-check-circle"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_1'], ENT_QUOTES, 'UTF-8') . '</p>'
                        . '<p style="margin:0 0 8px 0;"><i class="fa fa-database"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_2'], ENT_QUOTES, 'UTF-8')
                        . '<span style="display:block;margin-top:4px;color:#dc3545;font-weight:600;">'
                        . htmlspecialchars($_lang['updater_notice_backup_warning'], ENT_QUOTES, 'UTF-8')
                        . '</span></p>'
                        . '<p style="margin:0;"><i class="fa fa-user"></i> '
                        . htmlspecialchars($_lang['updater_notice_text_3'], ENT_QUOTES, 'UTF-8') . '</p>'
                        . '</div>'

                        . '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">'
                        . '<a class="btn btn-sm btn-success" href="#" onclick="var panel=document.getElementById(\'updater-cli-panel\');if(panel){panel.style.display=(panel.style.display===\'block\'?\'none\':\'block\');}return false;">'
                        . '<i class="fa fa-terminal"></i> ' . htmlspecialchars($_lang['updater_cli_summary'], ENT_QUOTES, 'UTF-8')
                        . '</a>'
                        . $supportButtonHtml
                        . '<span data-updater-action-slot="update"></span>'
                        . '</div>'
                        . '<div id="updater-cli-panel" style="display:none;margin-top:12px;padding:8px;border:1px dashed #bdbdbd;border-radius:6px;">'
                        . '<div style="margin-bottom:6px;">' . htmlspecialchars($_lang['updater_cli_intro'], ENT_QUOTES, 'UTF-8') . '</div>'
                        . '<code style="display:block;padding:8px;background:rgba(127,127,127,0.12);border:1px solid rgba(127,127,127,0.35);border-radius:4px;color:inherit;">' . $safeCliCommand . '</code>'
                        . '</div>'
                        . ($errorsMessage !== '' ? '<small style="color:red;font-size:10px;display:block;">' . $errorsMessage . '</small>' : '')
                        . $hideTodayHtml
                        . '<script>(function(){if(window.updaterHideForDay){return;}window.updaterHideForDay=function(hideKey,token,untilTs,trigger){var xhr=new XMLHttpRequest();xhr.open("POST","index.php?a=118",true);xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8");xhr.onload=function(){if(xhr.readyState!==4){return;}var root=null;if(trigger&&trigger.closest){root=trigger.closest("[data-updater-hide-root]");}if(!root){root=document.getElementById("updater");}if(root){root.style.display="none";}};var payload="action=setsetting&key="+encodeURIComponent(hideKey)+"&value="+encodeURIComponent(String(untilTs));if(token){payload+="&_token="+encodeURIComponent(token);}xhr.send(payload);return false;};})();</script>'
                        . '</div>';

                    $widgets['updater'] = [
                        'menuindex' => '1',
                        'id' => 'updater',
                        'cols' => 'col-sm-12',
                        'icon' => 'fa-exclamation-triangle',
                        'title' => $_lang['updater_widget_title'],
                        'body' => $output
                    ];

                    $e->output(serialize($widgets));
                }
            }
        }
    }
    if (isset($_GET['q']) && $_GET['q'] === $_SESSION['updatelink']) {
        if (empty($_SESSION['mgrInternalKey']) || empty($_SESSION['updatelink'])) {
            return;
        }
        unset($_SESSION['updatelink']);
                $currentVersion = $modx->getVersionData();
                $commit = isset($_GET['sha']) ? $_GET['sha'] : '';
                if ($_SESSION['updateversion'] != $currentVersion['version'] || (isset($commit) && $type == 'commits')) {
                    file_put_contents(MODX_BASE_PATH . 'update.php', '<?php
function downloadFile($url, $path)
{
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
        set_time_limit(30);
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

function copyFolder($src, $dest)
{
    $path = realpath($src);
    $dest = realpath($dest);
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $name => $object) {
        set_time_limit(30);
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

function mmkDir($folder, $perm = 0777)
{
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
unlink(__DIR__ . "/temp/" . $dir . "/ng.inx");
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
}
copyFolder(__DIR__ . "/temp/" . $dir, __DIR__ . "/");
removeFolder(__DIR__ . "/temp");
unlink(__DIR__ . "/evo.zip");
$ch = curl_init();
$url = "https://api.github.com/repos/' . $version . '/releases";
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_REFERER, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: updateNotify widget"));
$releases = curl_exec($ch);
curl_close($ch);
if (substr($releases, 0, 1) == "[") {
    $releases = json_decode($releases, true);
    foreach ($releases as $release) {
        if ($_GET["version"] == $release["tag_name"]) {
            $factoryDate = date("M j, Y", strtotime($release["published_at"]));
            $factoryVersion = \'<?php return [\'."\n";
            $factoryVersion .= "\t".\'"version" => "\'.$release["tag_name"].\'", // Current version number\'."\n";
            $factoryVersion .= "\t".\'"release_date" => "\'.$factoryDate.\'", // Date of release\'."\n";
            $factoryVersion .= "\t".\'"branch" => "Evolution CMS", // Codebase name\'."\n";
            $factoryVersion .= "\t".\'"full_appname" => "\'.$release["name"].\' (\'.$factoryDate.\')", // Date of release\'."\n";
            $factoryVersion .= \'];\';
            file_put_contents(__DIR__ . "/core/factory/version.php", $factoryVersion);
            break;
        }
    }
}
unlink(__DIR__ . "/update.php");
header("Location: ' . constant('MODX_SITE_URL') . 'install/index.php?action=mode");');
                    if ($result === false) {
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
    }
}
