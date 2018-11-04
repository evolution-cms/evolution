<?php
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
/** @var DocumentParser $modx */

// get manager role check
$internalKey = $modx->getLoginUserID();
$sid = $modx->sid;
$role = (int)$_SESSION['mgrRole'];
$user = $_SESSION['mgrShortname'];

switch (true)
{
    case ($role !== 1 && $wdgVisibility === 'AdminOnly'):
        // show widget only to Admin role 1
        break;
    case ($role === 1 && $wdgVisibility === 'AdminExcluded'):
        // show widget to all manager users excluded Admin role 1
        break;
    case ($role != $ThisRole && $wdgVisibility === 'ThisRoleOnly'):
        // show widget only to "this" role id
        break;
    case ($user != $ThisUser && $wdgVisibility === 'ThisUserOnly'):
        // show widget only to "this" username
        break;
    default: // get plugin id and setting button
        global $_lang;
        $plugin_path = __DIR__;
        if (!class_exists('CheckOutdated')) {
            include_once __DIR__ . '/CheckOutdated.class.php';
        }
        $checkOutdated = new CheckOutdated($modx, $modx->event->activePlugin, $_lang);
        $outdated = $checkOutdated->load(
            'https://raw.githubusercontent.com/evolution-cms/OutdatedExtrasCheck/master/outdated.json'
        );

        $out = '';
        foreach ($outdated as $type => $elements) {
            foreach ($elements as $item => $options) {
                $out .= $checkOutdated->process($type, $item, $options);
            }
        }

        if ($out !== '' && $modx->event->name === 'OnManagerWelcomeHome') {
            $button = $checkOutdated->makeConfigButton($_lang['settings_config']);
            $modx->setPlaceholder('button_pl_config', $button);

            $wdgTitle = $checkOutdated->parseTemplate('@CODE:EVO [+evo_cms_version+] - [%title%]');
            $widgets['xtraCheck'] = array(
                'menuindex' => '0',
                'id'        => 'xtraCheck' . $checkOutdated->getPluginId() . '',
                'cols'      => 'col-lg-12',
                'headAttr'  => 'style="color:#E84B39;"',
                'bodyAttr'  => '',
                'icon'      => 'fa-warning',
                'title'     => '' . $wdgTitle . ' ' . $button . '',
                'body'      => '<div class="card-body">' . $out . '</div>',
                'hide'      => '0'
            );
            $modx->event->setOutput(serialize($widgets));
        }
        break;
}
