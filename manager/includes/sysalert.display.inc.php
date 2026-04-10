<?php

/**
 *    System Alert Message Queue Display file
 *    Written By Raymond Irving, April, 2005
 *
 *    Used to display system alert messages inside the browser
 *
 */

$sysMsgs = '';
$limit = isset($SystemAlertMsgQueque) && is_array($SystemAlertMsgQueque) ? count($SystemAlertMsgQueque) : 0;
for ($i = 0; $i < $limit; $i++) {
    $sysMsgs .= $SystemAlertMsgQueque[$i] . '<hr sys/>';
}
// reset message queque
unset($_SESSION['SystemAlertMsgQueque']);
$_SESSION['SystemAlertMsgQueque'] = [];
$SystemAlertMsgQueque = &$_SESSION['SystemAlertMsgQueque'];

if ($sysMsgs != '') : ?>
    <link rel="stylesheet" type="text/css" href="<?=EVO_MANAGER_URL;?>media/style/<?=ManagerTheme::getTheme();?>/style.css" />
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        if (parent.modx) {
          parent.modx.popup({
            title: '<?=$_lang['sys_alert']; ?>',
            content: '<?=$sysMsgs?>',
            wrap: document.body,
            type: 'warning',
            width: '400px',
            hide: 0,
            hover: 0,
            overlay: 1,
            overlayclose: 1
          });
        }
      });
    </script>
<?php endif; ?>
