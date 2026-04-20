<?php
define('EVO_API_MODE', true);

if (file_exists(__DIR__ . '/config.php')) {
    $config = require __DIR__ . '/config.php';
} elseif (file_exists(dirname(__DIR__) . '/config.php')) {
    $config = require dirname(__DIR__) . '/config.php';
} else {
    $config = [
        'root' => dirname(__DIR__)
    ];
}

if (!empty($config['root']) && file_exists($config['root']. '/index.php')) {
    require_once $config['root'] . '/index.php';
} else {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');

    echo '<h3>Unable to load configuration settings</h3>';
    echo 'Please run the Evolution CMS install utility';

    exit;
}

$modx = evo();

$modx->documentMethod = 'id';
$modx->documentIdentifier = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 1;
$modx->documentObject = $modx->getDocumentObject('id', $modx->documentIdentifier);

$modx->invokeEvent('OnWebPageInit');

$captcha = new EvolutionCMS\Support\Captcha(148, 60);
$captcha->output();
$captcha->destroy();

/**
 * @deprecated use EvolutionCMS\Support\Captcha
 */
class VeriWord extends EvolutionCMS\Support\Captcha
{
    public function set_veriword()
    {
        parent::set_veriword();
    }

    public function output_image()
    {
        parent::output();
    }

    public function pick_word()
    {
        return parent::makeText();
    }

    public function draw_text()
    {
        return parent::drawText();
    }


    public function draw_image()
    {
        return parent::drawImage();
    }

    public function destroy_image()
    {
        parent::destroy();
    }
}
