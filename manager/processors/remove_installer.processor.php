<?php

use EvolutionCMS\Support\InstallerCompletion;

/**
 * Installer remover processor
 * --------------------------------
 * This little script will be used by the installer to remove
 * the install folder from the web root after an install. Having
 * the install folder present after an install is considered a
 * security risk
 *
 * This file is normally called from the installer: the last page navigates the browser here
 * when "remove the install folder" is ticked. Nothing else links to it.
 *
 * It therefore cannot require a Manager login: fresh installations are unauthenticated, while
 * upgrades may have a Manager cookie. Successful installer completion writes a one-time token
 * to `install.session.php`; the final page posts that token here without disturbing either kind
 * of session. A generic installer lock does not authorize this destructive operation.
 */

$applicationBasePath = str_replace('\\', '/', dirname(__DIR__, 2)) . '/';
$basePath = defined('EVO_BASE_PATH') ? rtrim(str_replace('\\', '/', EVO_BASE_PATH), '/') . '/' : $applicationBasePath;
$lockFile = $basePath . 'install.session.php';
$installPath = $basePath . 'install/';

require_once $applicationBasePath . 'core/src/Support/InstallerCompletion.php';

if (!function_exists('rmdirRecursive')) {
    /**
     * rmdirRecursive - detects symbollic links on unix
     *
     * @param string $path
     * @param bool $followLinks
     * @return bool
     */
    function rmdirRecursive($path, $followLinks = false)
    {
        $dir = opendir($path);
        while ($entry = readdir($dir)) {
            if (is_file("$path/$entry") || ((!$followLinks) && is_link("$path/$entry"))) {
                @unlink("$path/$entry");
            } elseif (is_dir("$path/$entry") && $entry !== '.' && $entry !== '..') {
                rmdirRecursive("$path/$entry"); // recursive
            }
        }
        closedir($dir);

        return @rmdir($path);
    }
}

$requestToken = $_POST['installer_token'] ?? null;
$lock = InstallerCompletion::readLock($lockFile);
$maxLifetime = (int) ini_get('session.gc_maxlifetime');

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || !is_string($requestToken)
    || !InstallerCompletion::matches(
        $lock,
        $requestToken,
        (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        time(),
        $maxLifetime
    )
) {
    // 404 rather than 403, matching what install/index.php answers a request that does not hold
    // the lock. The message is for the person who let the lock expire mid-install; it tells them
    // nothing a request to /install/ would not.
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found.\n\nIf you were installing Evolution CMS, the installer session has expired."
        . "\nDelete the \"install\" folder manually before logging into the Manager.";
    exit;
}

$msg = '';

if (($_POST['rminstaller'] ?? null) === '1') {
    if (is_dir($installPath) && !rmdirRecursive($installPath)) {
        $msg = 'An error occured while attempting to remove the install folder';
    }
}

// The lock has served its purpose either way: it exists only to carry the installation - and
// this last step of it - through, and a stale file in the web root is not worth keeping.
if ($msg === '') {
    @unlink($lockFile);
}

if ($msg) {
    echo "<script>alert('" . addslashes($msg) . "');</script>";
}

echo "<script>window.location='../#?a=2';</script>";
