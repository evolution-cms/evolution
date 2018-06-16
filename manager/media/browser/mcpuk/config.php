<?php

/** This file is part of KCFinder project
  *
  *      @desc Base configuration file
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

// IMPORTANT!!! Do not remove uncommented settings in this file even if
// you are using session configuration.
// See http://kcfinder.sunhater.com/install for setting descriptions

$modx = evolutionCMS();
$_CONFIG = array(

    'disabled' => false,
    'denyZipDownload' => $modx->config['denyZipDownload'],
    'denyExtensionRename' => $modx->config['denyExtensionRename'],
    'showHiddenFiles' => $modx->config['showHiddenFiles'],

    'theme' => "evo",

    'uploadURL' => rtrim($modx->config['rb_base_url'],'/'),
    'uploadDir' => rtrim($modx->config['rb_base_dir'],'/'),
    'siteURL' => $modx->config['site_url'],
    'assetsURL' => rtrim($modx->config['rb_base_url'],'/'),
    'dirPerms' => intval($modx->config['new_folder_permissions'],8),
    'filePerms' => intval($modx->config['new_file_permissions'],8),
    'maxfilesize' => $settings['upload_maxsize'],
    'denyUpdateCheck' => true,


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
        'files'   =>  str_replace(',',' ',$modx->config['upload_files']),
        'flash'   =>  str_replace(',',' ',$modx->config['upload_flash']),
        'images'  =>  str_replace(',',' ',$modx->config['upload_images']),

        // TinyMCE types
        'file'    =>  str_replace(',',' ',$modx->config['upload_files']),
        'media'   =>  str_replace(',',' ',$modx->config['upload_media']),
        'image'   =>  str_replace(',',' ',$modx->config['upload_images']),
    ),
    'dirnameChangeChars' => array(
        ' ' => "_",
        ':' => "."
     ),
    'mime_magic' => "",

    'maxImageWidth' => $modx->config['maxImageWidth'],
    'maxImageHeight' => $modx->config['maxImageHeight'],

    'thumbWidth' => $modx->config['thumbWidth'],
    'thumbHeight' => $modx->config['thumbHeight'],

    'thumbsDir' => $modx->config['thumbsDir'],

    'jpegQuality' => $modx->config['jpegQuality'],

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
