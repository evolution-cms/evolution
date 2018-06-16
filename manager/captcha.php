<?php
define('MODX_API_MODE', true);

include_once '../index.php';

$modx->getDatabase()->connect();
$modx->getSettings();

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
