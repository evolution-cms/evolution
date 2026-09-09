<?php

/*
|--------------------------------------------------------------------------
| unserialize() object-injection hardening
|--------------------------------------------------------------------------
|
| Two entry points rebuilt PHP objects out of storage an attacker can reach without any code
| privilege:
|
|   - Core::getDocumentObjectFromCache()          assets/cache/*.pageCache.php
|   - ManagerApi::getModifiedSystemFilesList()    system_settings.sys_files_checksum
|
| Both only ever store arrays, so a single write into the cache directory - or into the settings
| table through some other flaw - was enough to run a vendor POP chain on the next request. Passing
| allowed_classes => false turns any object in the payload into __PHP_Incomplete_Class, which has no
| magic methods to trigger.
|
| @since 3.5.8
*/

use EvolutionCMS\Core;
use EvolutionCMS\Legacy\ManagerApi;

beforeAll(function () {
    if (!defined('IN_INSTALL_MODE')) {
        define('IN_INSTALL_MODE', false);
    }
    if (!defined('EVO_API_MODE')) {
        define('EVO_API_MODE', true);
    }
    if (!defined('IN_MANAGER_MODE')) {
        define('IN_MANAGER_MODE', false);
    }
    $root = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__, 3)) . '/';
    if (!defined('EVO_BASE_PATH')) {
        define('EVO_BASE_PATH', $root);
    }
    if (!defined('EVO_CORE_PATH')) {
        define('EVO_CORE_PATH', $root . 'core/');
    }
    if (!defined('EVO_MANAGER_PATH')) {
        define('EVO_MANAGER_PATH', $root . 'manager/');
    }
    $autoload = EVO_CORE_PATH . 'vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }
});

/**
 * Stands in for a vendor gadget: any wakeup on this class means the payload was instantiated.
 */
class UnserializeGadget
{
    public $payload = 'x';

    public function __wakeup()
    {
        $GLOBALS['evo_unserialize_gadget_fired'] = true;
    }
}

/**
 * A Core built without the heavy constructor, with the cache path and the event dispatcher
 * redirected so the read path can run outside a bootstrapped application.
 */
class UnserializeCacheCore extends Core
{
    public $hashFile = '';

    public function getConfig($name = '', $default = null)
    {
        return $default;
    }

    public function getHashFile($key)
    {
        return $this->hashFile;
    }

    public function isFrontend()
    {
        return false;
    }

    public function invokeEvent($evtName, $extParams = [])
    {
        return [];
    }
}

function unserializeCacheCore(string $hashFile): UnserializeCacheCore
{
    $core = (new ReflectionClass(UnserializeCacheCore::class))->newInstanceWithoutConstructor();
    $core->hashFile = $hashFile;

    return $core;
}

describe('page cache', function () {

    test('an object in the cache file is not instantiated', function () {
        $GLOBALS['evo_unserialize_gadget_fired'] = false;

        $file = str_replace(DIRECTORY_SEPARATOR, '/', sys_get_temp_dir()) . '/evo_cache_' . bin2hex(random_bytes(6)) . '.php';
        $docObj = ['privateweb' => 0, 'gadget' => new UnserializeGadget()];
        file_put_contents($file, '<?php die();?>' . serialize($docObj) . '<!--__MODxCacheSpliter__-->CONTENT');

        $result = unserializeCacheCore($file)->getDocumentObjectFromCache(1);
        @unlink($file);

        expect($GLOBALS['evo_unserialize_gadget_fired'])->toBeFalse()
            ->and($result)->toBe('CONTENT');
    });

    test('the surrounding document fields still survive the round trip', function () {
        $file = str_replace(DIRECTORY_SEPARATOR, '/', sys_get_temp_dir()) . '/evo_cache_' . bin2hex(random_bytes(6)) . '.php';
        $docObj = ['privateweb' => 0, 'pagetitle' => 'Home', 'published' => 1];
        file_put_contents($file, '<?php die();?>' . serialize($docObj) . '<!--__MODxCacheSpliter__-->CONTENT');

        $core = unserializeCacheCore($file);
        $core->getDocumentObjectFromCache(1);
        @unlink($file);

        expect($core->documentObject['pagetitle'])->toBe('Home');
    });
});

describe('system files checksum', function () {

    test('an object in the stored checksum is not instantiated', function () {
        $GLOBALS['evo_unserialize_gadget_fired'] = false;

        $api = new ManagerApi();
        $api->getModifiedSystemFilesList('composer.json', serialize(['x' => new UnserializeGadget()]));

        expect($GLOBALS['evo_unserialize_gadget_fired'])->toBeFalse();
    });

    test('a checksum that did not hold an array is treated as empty', function () {
        // The stored setting used to be handed straight to array_key_exists(), which fatals on
        // anything but an array.
        $api = new ManagerApi();

        expect($api->getModifiedSystemFilesList('composer.json', serialize('corrupted')))
            ->toBe(['composer.json']);
    });

    test('a matching checksum still reports no modified files', function () {
        $api = new ManagerApi();
        $file = EVO_BASE_PATH . 'composer.json';
        $checksum = serialize([$file => md5_file($file)]);

        expect($api->getModifiedSystemFilesList('composer.json', $checksum))->toBe([]);
    });
});
