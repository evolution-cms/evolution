<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
$logo= '<img src="media/style/default/images/misc/login-logo.png" height="54" width="358" border="0">';
$downloadLinks = array(
	0=>array('title'=>$_lang["information"],'link'=>'https://evo.im/'),
	1=>array('title'=>$_lang["download"],'link'=>'https://github.com/evolution-cms/evolution/releases'),
	2=>array('title'=>$_lang["previous_releases"],'link'=>'https://modx.com/download/evolution/previous-releases.html'),
	3=>array('title'=>$_lang["extras"],'link'=>array(
		'http://extras.evolution-cms.com/',
		'https://github.com/extras-evolution'
	)),
);

$translationLinks = array(
	0=>array('title'=>'Evolution CMS','link'=>'https://www.transifex.com/evolutioncms/evolution/'),
	1=>array('title'=>$_lang["extras"],'link'=>'https://www.transifex.com/evolutioncms/extras/'),
);


echo $logo;
echo createList($_lang['evo_downloads_title'], $downloadLinks);
echo createList($_lang['help_translating_title'], $translationLinks);

?>

<div class="sectionHeader"><?php echo $_lang['about_title']; ?></div><div class="sectionBody">
<?php echo $_lang['about_msg']; ?><?php echo $_lang['credits_shouts_msg']; ?>
</div>




