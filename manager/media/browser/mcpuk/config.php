<?php

/** This file is part of KCFinder project
  *
  *      @desc Base configuration file
  *   @package KCFinder
  *   @version 2.51
  *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
  * @copyright 2010, 2011 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

// IMPORTANT!!! Do not remove uncommented settings in this file even if
// you are using session configuration.
// See http://kcfinder.sunhater.com/install for setting descriptions

global $settings,$site_url;
$_CONFIG = array(

    'disabled' => false,
    'denyZipDownload' => $settings['denyZipDownload'],
    'denyUpdateCheck' => true,
    'denyExtensionRename' => $settings['denyExtensionRename'],
	'showHiddenFiles' => $settings['showHiddenFiles'],
	
    'theme' => "oxygen",

    'uploadURL' => $settings['rb_base_url'],
    'uploadDir' => $settings['rb_base_dir'],
    'siteURL' => $site_url,
	'assetsURL' => rtrim($settings['rb_base_url'],'/'),
    'dirPerms' => intval($settings['new_folder_permissions'],8),
    'filePerms' => intval($settings['new_file_permissions'],8),

    'access' => array(

        'files' => array(
            'upload' => true,
            'delete' => true,
            'copy' => true,
            'move' => true,
            'rename' => true
        ),

        'dirs' => array(
            'create' => true,
            'delete' => true,
            'rename' => true
        )
    ),

    'deniedExts' => "exe com msi bat php phps phtml php3 php4 cgi pl",

    'types' => array(

        // CKEditor & FCKEditor types
        'files'   =>  str_replace(',',' ',$settings['upload_files']),
        'flash'   =>  str_replace(',',' ',$settings['upload_flash']),
        'images'  =>  str_replace(',',' ',$settings['upload_images']),

        // TinyMCE types
        'file'    =>  str_replace(',',' ',$settings['upload_files']),
        'media'   =>  str_replace(',',' ',$settings['upload_media']),
        'image'   =>  str_replace(',',' ',$settings['upload_images']),
    ),

    'filenameChangeChars' => array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ы"=>"i","э"=>"e","ю"=>"yu","я"=>"ya",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E","Ё"=>"Yo","Ж"=>"Zh", "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Shh","Ы"=>"I","Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
        "ь"=>"","Ь"=>"","ъ"=>"","Ъ"=>""," "=>"-","№"=>"N","+"=>"-",":"=>"-",";"=>"-","!"=>"-","?"=>"-","&"=>"and","\'" =>"", "=" =>"-","%"=>'-',"ї"=>"yi","Ї"=>"yi","є"=>"ye","Є"=>"ye","і"=>"i","І"=>"i","ґ"=>"g","Ґ"=>"g"),

    'dirnameChangeChars' => array("а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ы"=>"i","э"=>"e","ю"=>"yu","я"=>"ya",
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E","Ё"=>"Yo","Ж"=>"Zh", "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Shh","Ы"=>"I","Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
        "ь"=>"","Ь"=>"","ъ"=>"","Ъ"=>""," "=>"-","№"=>"N","+"=>"-",":"=>"-",";"=>"-","!"=>"-","?"=>"-","&"=>"and","\'" =>"", "=" =>"-","%"=>'-',"ї"=>"yi","Ї"=>"yi","є"=>"ye","Є"=>"ye","і"=>"i","І"=>"i","ґ"=>"g","Ґ"=>"g"),

    'mime_magic' => "",

    'maxImageWidth' => $settings['maxImageWidth'],
    'maxImageHeight' => $settings['maxImageHeight'],

    'thumbWidth' => $settings['thumbWidth'],
    'thumbHeight' => $settings['thumbHeight'],

    'thumbsDir' => $settings['thumbsDir'],

    'jpegQuality' => $settings['jpegQuality'],

    'cookieDomain' => "",
    'cookiePath' => "",
    'cookiePrefix' => 'KCFINDER_',

    // THE FOLLOWING SETTINGS CANNOT BE OVERRIDED WITH SESSION CONFIGURATION
    '_check4htaccess' => false,
    '_tinyMCEPath' => MODX_BASE_URL . "assets/plugins/tinymce/tiny_mce",

    '_sessionVar' => &$_SESSION['KCFINDER'],
    //'_sessionLifetime' => 30,
    //'_sessionDir' => "/full/directory/path",

    //'_sessionDomain' => ".mysite.com",
    //'_sessionPath' => "/my/path",
);

?>