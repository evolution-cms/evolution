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
$_CONFIG = [
    'disabled' => false,
    'denyZipDownload' => EvolutionCMS()->getConfig('denyZipDownload'),
    'denyExtensionRename' => EvolutionCMS()->getConfig('denyExtensionRename'),
    'showHiddenFiles' => EvolutionCMS()->getConfig('showHiddenFiles'),
    'theme' => "evo",
    'uploadURL'           => rtrim(EvolutionCMS()->getConfig('rb_base_url'), '/'),
    'uploadDir'           => rtrim(EvolutionCMS()->getConfig('rb_base_dir'), '/'),
    'siteURL' => MODX_SITE_URL,
    'assetsURL'           => rtrim(EvolutionCMS()->getConfig('rb_base_url'), '/'),
    'dirPerms'            => intval(EvolutionCMS()->getConfig('new_folder_permissions'), 8),
    'filePerms'           => intval(EvolutionCMS()->getConfig('new_file_permissions'), 8),
    'maxfilesize'         => (int)EvolutionCMS()->getConfig('upload_maxsize'),
    'noThumbnailsRecreation' => EvolutionCMS()->getConfig('noThumbnailsRecreation'),

    'access' => [

        'files' => [
            'upload' => true,
            'delete' => true,
            'copy' => true,
            'move' => true,
            'rename' => true
        ],

        'dirs' => [
            'create' => true,
            'delete' => true,
            'rename' => true
        ]
    ],

    'deniedExts' => "exe com msi bat php phps phtml php3 php4 cgi pl",

    'types' => [

        // CKEditor & FCKEditor types
        'files'  => str_replace(',', ' ', EvolutionCMS()->getConfig('upload_files')),
        'images' => str_replace(',', ' ', EvolutionCMS()->getConfig('upload_images')),

        // TinyMCE types
        'file'   => str_replace(',', ' ', EvolutionCMS()->getConfig('upload_files')),
        'media'  => str_replace(',', ' ', EvolutionCMS()->getConfig('upload_media')),
        'image'  => str_replace(',', ' ', EvolutionCMS()->getConfig('upload_images')),
    ],
    'dirnameChangeChars' => [
        ' ' => "_",
        ':' => "."
    ],
    'mime_magic' => "",

    'maxImageWidth' => EvolutionCMS()->getConfig('maxImageWidth'),
    'maxImageHeight' => EvolutionCMS()->getConfig('maxImageHeight'),
    'clientResize'   => EvolutionCMS()->getConfig('clientResize') && EvolutionCMS()->getConfig('maxImageWidth') && EvolutionCMS()->getConfig('maxImageHeight') ? ['maxWidth'  => EvolutionCMS()->getConfig('maxImageWidth'),
                                                                                                                                            'maxHeight' => EvolutionCMS()->getConfig('maxImageHeight'),
                                                                                                                                            'quality'   => EvolutionCMS()->getConfig('jpegQuality') / 100
    ] : [],

    'thumbWidth' => EvolutionCMS()->getConfig('thumbWidth'),
    'thumbHeight' => EvolutionCMS()->getConfig('thumbHeight'),
    'thumbsDir' => EvolutionCMS()->getConfig('thumbsDir'),

    'jpegQuality' => EvolutionCMS()->getConfig('jpegQuality'),

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
];

EvolutionCMS()->invokeEvent('OnFileBrowserInit', [
    'config' => &$_CONFIG,
]);
