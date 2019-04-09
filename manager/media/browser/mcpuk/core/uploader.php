<?php

/** This file is part of KCFinder project
  *
  *      @desc Uploader class
  *   @package KCFinder
  *   @version 2.54
  *    @author Pavel Tzonkov <sunhater@sunhater.com>
  * @copyright 2010-2014 KCFinder Project
  *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
  *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
  *      @link http://kcfinder.sunhater.com
  */

class uploader {

/** Release version */
    const VERSION = "2.54";

/** Config session-overrided settings
  * @var array */
    protected $config = array();

/** Default image driver
  * @var string */
    protected $imageDriver = "gd";

/** Opener applocation properties
  *   $opener['name']                 Got from $_GET['opener'];
  *   $opener['CKEditor']['funcNum']  CKEditor function number (got from $_GET)
  *   $opener['TinyMCE']              Boolean
  * @var array */
    protected $opener = array();

/** Got from $_GET['type'] or first one $config['types'] array key, if inexistant
  * @var string */
    protected $type;

/** Helper property. Local filesystem path to the Type Directory
  * Equivalent: $config['uploadDir'] . "/" . $type
  * @var string */
    protected $typeDir;

/** Helper property. Web URL to the Type Directory
  * Equivalent: $config['uploadURL'] . "/" . $type
  * @var string */
    protected $typeURL;

/** Linked to $config['types']
  * @var array */
    protected $types = array();

/** Settings which can override default settings if exists as keys in $config['types'][$type] array
  * @var array */
    protected $typeSettings = array('disabled', 'theme', 'dirPerms', 'filePerms', 'denyZipDownload', 'maxImageWidth', 'maxImageHeight', 'thumbWidth', 'thumbHeight', 'jpegQuality', 'access', 'filenameChangeChars', 'dirnameChangeChars', 'denyExtensionRename', 'deniedExts', 'watermark');

/** Got from language file
  * @var string */
    protected $charset;

/** The language got from $_GET['lng'] or $_GET['lang'] or... Please see next property
  * @var string */
    protected $lang = 'en';

/** Possible language $_GET keys
  * @var array */
    protected $langInputNames = array('lang', 'langCode', 'lng', 'language', 'lang_code');

/** Uploaded file(s) info. Linked to first $_FILES element
  * @var array */
    protected $file;

/** Next three properties are got from the current language file
  * @var string */
    protected $dateTimeFull;   // Currently not used
    protected $dateTimeMid;    // Currently not used
    protected $dateTimeSmall;

/** Contain Specified language labels
  * @var array */
    protected $labels = array();

/** Contain unprocessed $_GET array. Please use this instead of $_GET
  * @var array */
    protected $get;

/** Contain unprocessed $_POST array. Please use this instead of $_POST
  * @var array */
    protected $post;

/** Contain unprocessed $_COOKIE array. Please use this instead of $_COOKIE
  * @var array */
    protected $cookie;

/** Session array. Please use this property instead of $_SESSION
  * @var array */
    protected $session;

/** CMS integration attribute (got from $_GET['cms'])
  * @var string */
    protected $cms = "";

    protected $modx = null;
/** Magic method which allows read-only access to protected or private class properties
  * @param string $property
  * @return mixed */
    public function __get($property) {
        return property_exists($this, $property) ? $this->$property : null;
    }

    /**
     * uploader constructor.
     * @param DocumentParser $modx
     */
    public function __construct(DocumentParser $modx) {

        //MODX
        try {
            if ($modx instanceof DocumentParser) {
                $this->modx = $modx;
            } else throw new Exception('MODX should be instance of DocumentParser');
        } catch (Exception $e) {
            die($e->getMessage());
        }

        // INPUT INIT

        $input = new input();
        $this->get = &$input->get;
        $this->post = &$input->post;
        $this->cookie = &$input->cookie;

        // SET CMS INTEGRATION ATTRIBUTE
        if (isset($this->get['cms']) &&
            in_array($this->get['cms'], array("drupal"))
        )
            $this->cms = $this->get['cms'];

		// LINKING UPLOADED FILE
        if (count($_FILES))
            $this->file = &$_FILES[key($_FILES)];

        // LOAD DEFAULT CONFIGURATION
        require "config.php";
        $this->config = $_CONFIG;

        // LOAD SESSION CONFIGURATION IF EXISTS
        if (isset($_CONFIG['_sessionVar']) &&
            is_array($_CONFIG['_sessionVar'])
        ) {
            foreach ($_CONFIG['_sessionVar'] as $key => $val)
                if ((substr($key, 0, 1) != "_") && isset($_CONFIG[$key]))
                    $this->config[$key] = $val;
            if (!isset($this->config['_sessionVar']['self']))
                $this->config['_sessionVar']['self'] = array();
            $this->session = &$this->config['_sessionVar']['self'];
        } else
            $this->session = &$_SESSION;

        // IMAGE DRIVER INIT
        if (isset($this->config['imageDriversPriority'])) {
            $this->config['imageDriversPriority'] =
                text::clearWhitespaces($this->config['imageDriversPriority']);
            $driver = image::getDriver(explode(' ', $this->config['imageDriversPriority']));
            if ($driver !== false)
                $this->imageDriver = $driver;
        }
        if ((!isset($driver) || ($driver === false)) &&
            (image::getDriver(array($this->imageDriver)) === false)
        )
            die("Cannot find any of the supported PHP image extensions!");

        // WATERMARK INIT
        if (isset($this->config['watermark']) && is_string($this->config['watermark']))
            $this->config['watermark'] = array('file' => $this->config['watermark']);

        // GET TYPE DIRECTORY
        $this->types = &$this->config['types'];
        $firstType = array_keys($this->types);
        $firstType = $firstType[0];
        $this->type = (
            isset($this->get['type']) &&
            isset($this->types[$this->get['type']])
        )
            ? $this->get['type'] : $firstType;

        // LOAD TYPE DIRECTORY SPECIFIC CONFIGURATION IF EXISTS
        if (is_array($this->types[$this->type])) {
            foreach ($this->types[$this->type] as $key => $val)
                if (in_array($key, $this->typeSettings))
                    $this->config[$key] = $val;
            $this->types[$this->type] = isset($this->types[$this->type]['type'])
                ? $this->types[$this->type]['type'] : "";
        }

        // COOKIES INIT
        $ip = '(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)';
        $ip = '/^' . implode('\.', array($ip, $ip, $ip, $ip)) . '$/';
        if (preg_match($ip, $_SERVER['HTTP_HOST']) ||
            preg_match('/^[^\.]+$/', $_SERVER['HTTP_HOST'])
        )
            $this->config['cookieDomain'] = "";
        elseif (!strlen($this->config['cookieDomain']))
            $this->config['cookieDomain'] = $_SERVER['HTTP_HOST'];
        if (!strlen($this->config['cookiePath']))
            $this->config['cookiePath'] = "/";

        // UPLOAD FOLDER INIT

        // FULL URL
        if (preg_match('/^([a-z]+)\:\/\/([^\/^\:]+)(\:(\d+))?\/(.+)\/?$/',
                $this->config['uploadURL'], $patt)
        ) {
            list($unused, $protocol, $domain, $unused, $port, $path) = $patt;
            $path = path::normalize($path);
            $this->config['uploadURL'] = "$protocol://$domain" . (strlen($port) ? ":$port" : "") . "/$path";
            $this->config['uploadDir'] = strlen($this->config['uploadDir'])
                ? path::normalize($this->config['uploadDir'])
                : path::url2fullPath("/$path");
         $this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
         $this->typeURL = "{$this->config['siteURL']}/{$this->config['uploadURL']}/{$this->type}";

        // SITE ROOT
        } elseif ($this->config['uploadURL'] == "/") {
            $this->config['uploadDir'] = strlen($this->config['uploadDir'])
                ? path::normalize($this->config['uploadDir'])
                : path::normalize($_SERVER['DOCUMENT_ROOT']);
            $this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
            $this->typeURL = "/{$this->type}";

        // ABSOLUTE & RELATIVE
        } else {
            $this->config['uploadURL'] = (substr($this->config['uploadURL'], 0, 1) === "/")
                ? path::normalize($this->config['uploadURL'])
                : path::rel2abs_url($this->config['uploadURL']);
            $this->config['uploadDir'] = strlen($this->config['uploadDir'])
                ? path::normalize($this->config['uploadDir'])
                : path::url2fullPath($this->config['uploadURL']);
            $this->typeDir = "{$this->config['uploadDir']}/{$this->type}";
            $this->typeURL = "{$this->config['uploadURL']}/{$this->type}";
        }
        if (!is_dir($this->config['uploadDir']))
            @mkdir($this->config['uploadDir'], $this->config['dirPerms']);

        // HOST APPLICATIONS INIT
        if (isset($this->get['CKEditorFuncNum']))
            $this->opener['CKEditor']['funcNum'] = $this->get['CKEditorFuncNum'];
        if (isset($this->get['opener']) &&
            (strtolower($this->get['opener']) == "tinymce") &&
            isset($this->config['_tinyMCEPath']) &&
            strlen($this->config['_tinyMCEPath'])
        )
            $this->opener['TinyMCE'] = true;

        // LOCALIZATION
        foreach ($this->langInputNames as $key)
            if (isset($this->get[$key]) &&
                preg_match('/^[a-z][a-z\._\-]*$/i', $this->get[$key]) &&
                file_exists("lang/" . strtolower($this->get[$key]) . ".php")
            ) {
                $this->lang = $this->get[$key];
                break;
            }
        $this->localize($this->lang);

        // CHECK & MAKE DEFAULT .htaccess
        if (isset($this->config['_check4htaccess']) &&
            $this->config['_check4htaccess']
        ) {
            $htaccess = "{$this->config['uploadDir']}/.htaccess";
            if (!file_exists($htaccess)) {
                if (!@file_put_contents($htaccess, $this->get_htaccess()))
                    $this->backMsg("Cannot write to upload folder. {$this->config['uploadDir']}");
            } else {
                if (false === ($data = @file_get_contents($htaccess)))
                    $this->backMsg("Cannot read .htaccess");
                if (($data != $this->get_htaccess()) && !@file_put_contents($htaccess, $data))
                    $this->backMsg("Incorrect .htaccess file. Cannot rewrite it!");
            }
        }

        // CHECK & CREATE UPLOAD FOLDER
        if (!is_dir($this->typeDir)) {
            if (!mkdir($this->typeDir, $this->config['dirPerms']))
                $this->backMsg("Cannot create {dir} folder.", array('dir' => $this->type));
        } elseif (!is_readable($this->typeDir))
            $this->backMsg("Cannot read upload folder.");
    }

    /**
     * @return array|bool|int|null|string|void
     */
    protected function getTransaliasSettings() {
		$modx = evolutionCMS();

		// Cleaning uploaded filename?
		$setting = $modx->db->select('count(*)', $modx->getFullTableName('system_settings'), 'setting_name="clean_uploaded_filename" AND setting_value=1');
		if ($modx->db->getValue($setting)>0) {
			// Transalias plugin active?
			$res = $modx->db->select('properties', $modx->getFullTableName('site_plugins'), 'name="TransAlias" AND disabled=0');
			if ($properties = $modx->db->getValue($res)) {
				$properties = $modx->parseProperties($properties, 'TransAlias', 'plugin');
			} else {
				$properties = NULL;
			}
		} else {
			$properties = NULL;
		}
		return $properties;
	}


    /**
     * @param $filename
     * @return mixed|string
     */
    protected function normalizeFilename($filename) {
		if ($this->getTransaliasSettings()) {
        		$format = strrchr($filename, ".");
        		$filename = str_replace($format, "", $filename);
            		$filename = $this->modx->stripAlias($filename).$format;
        	}
        	return $filename;
	}

    /**
     * @param $dirname
     * @return string
     */
    protected function normalizeDirname($dirname) {
        return $this->modx->stripAlias($dirname);
    }

    /**
     * @param array|null $aFile
     * @return bool|mixed
     */
    protected function checkUploadedFile(array $aFile=null) {
        $config = &$this->config;
        $file = ($aFile === null) ? $this->file : $aFile;

        if (!is_array($file) || !isset($file['name']))
            return $this->label("Unknown error.");

        $extension = file::getExtension($file['name']);
        $typePatt = strtolower(text::clearWhitespaces($this->types[$this->type]));

        // CHECK FOR UPLOAD ERRORS
        if ($file['error'])
            return
                ($file['error'] == UPLOAD_ERR_INI_SIZE) ?
                    $this->label("The uploaded file exceeds {size} bytes.",
                        array('size' => ini_get('upload_max_filesize'))) : (
                ($file['error'] == UPLOAD_ERR_FORM_SIZE) ?
                    $this->label("The uploaded file exceeds {size} bytes.",
                        array('size' => $this->get['MAX_FILE_SIZE'])) : (
                ($file['error'] == UPLOAD_ERR_PARTIAL) ?
                    $this->label("The uploaded file was only partially uploaded.") : (
                ($file['error'] == UPLOAD_ERR_NO_FILE) ?
                    $this->label("No file was uploaded.") : (
                ($file['error'] == UPLOAD_ERR_NO_TMP_DIR) ?
                    $this->label("Missing a temporary folder.") : (
                ($file['error'] == UPLOAD_ERR_CANT_WRITE) ?
                    $this->label("Failed to write file.") :
                    $this->label("Unknown error.")
            )))));

        // HIDDEN FILENAMES CHECK
        elseif (substr($file['name'], 0, 1) == ".")
            return $this->label("File name shouldn't begins with '.'");

        // EXTENSION CHECK
        elseif (!$this->validateExtension($extension, $this->type))
            return $this->label("Denied file extension.");

        // SPECIAL DIRECTORY TYPES CHECK (e.g. *img)
        elseif (preg_match('/^\*([^ ]+)(.*)?$/s', $typePatt, $patt)) {
            list($typePatt, $type, $params) = $patt;
            if (class_exists("type_$type")) {
                $class = "type_$type";
                $type = new $class();
                $cfg = $config;
                $cfg['filename'] = $file['name'];
                if (strlen($params))
                    $cfg['params'] = trim($params);
                $response = $type->checkFile($file['tmp_name'], $cfg);
                if ($response !== true)
                    return $this->label($response);
            } else
                return $this->label("Non-existing directory type.");
        }

        // IMAGE RESIZE
        $img = image::factory($this->imageDriver, $file['tmp_name']);
        if (!$img->initError && !$this->imageResize($img, $file['tmp_name']))
            return $this->label("The image is too big and/or cannot be resized.");


	// CHECK FOR MODX MAX FILE SIZE
	$actualfilesize=filesize($file['tmp_name']);
	if (isset($this->config['maxfilesize']) && $actualfilesize > $this->config['maxfilesize'])
	    return $this->label("File is too big: ".$actualfilesize." Bytes. (max ".$this->config['maxfilesize']." Bytes)");

        return true;
    }

    /**
     * @param $dir
     * @param bool $inclType
     * @param bool $existing
     * @return bool|string
     */
    protected function checkInputDir($dir, $inclType=true, $existing=true) {
        $dir = path::normalize($dir);
        if (substr($dir, 0, 1) == "/")
            $dir = substr($dir, 1);

        if ((substr($dir, 0, 1) == ".") || (substr(basename($dir), 0, 1) == "."))
            return false;

        if ($inclType) {
            $first = explode("/", $dir);
            $first = $first[0];
            if ($first != $this->type)
                return false;
            $return = $this->removeTypeFromPath($dir);
        } else {
            $return = $dir;
            $dir = "{$this->type}/$dir";
        }

        if (!$existing)
            return $return;

        $path = "{$this->config['uploadDir']}/$dir";
        return (is_dir($path) && is_readable($path)) ? $return : false;
    }

    /**
     * @param $ext
     * @param $type
     * @return bool
     */
    protected function validateExtension($ext, $type) {
        $ext = trim(strtolower($ext));
        if (!isset($this->types[$type]))
            return false;

        $exts = strtolower(text::clearWhitespaces($this->config['deniedExts']));
        if (strlen($exts)) {
            $exts = explode(" ", $exts);
            if (in_array($ext, $exts))
                return false;
        }

        $exts = trim($this->types[$type]);
        if (!strlen($exts) || substr($exts, 0, 1) == "*")
            return true;

        if (substr($exts, 0, 1) == "!") {
            $exts = explode(" ", trim(strtolower(substr($exts, 1))));
            return !in_array($ext, $exts);
        }

        $exts = explode(" ", trim(strtolower($exts)));
        return in_array($ext, $exts);
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function getTypeFromPath($path) {
        return preg_match('/^([^\/]*)\/.*$/', $path, $patt)
            ? $patt[1] : $path;
    }

    /**
     * @param $path
     * @return string
     */
    protected function removeTypeFromPath($path) {
        return preg_match('/^[^\/]*\/(.*)$/', $path, $patt)
            ? $patt[1] : "";
    }

    /**
     * @param $image
     * @param null $file
     * @return bool
     */
    protected function imageResize($image, $file=null) {

        if (!($image instanceof image)) {
            $img = image::factory($this->imageDriver, $image);
            if ($img->initError) return false;
            $file = $image;
        } elseif ($file === null)
            return false;
        else
            $img = $image;

        $orientation = 1;
        if (function_exists("exif_read_data")) {
            $orientation = @exif_read_data($file);
            $orientation = isset($orientation['Orientation']) ? $orientation['Orientation'] : 1;
        }

        // IMAGE WILL NOT BE RESIZED WHEN NO WATERMARK AND SIZE IS ACCEPTABLE
        if ((
                !isset($this->config['watermark']['file']) ||
                (!strlen(trim($this->config['watermark']['file'])))
            ) && (
            (
                    !$this->config['maxImageWidth'] &&
                    !$this->config['maxImageHeight']
                ) || (
                    ($img->width <= $this->config['maxImageWidth']) &&
                    ($img->height <= $this->config['maxImageHeight'])
            )
            ) &&
            ($orientation == 1)
        )
            return true;


        // PROPORTIONAL RESIZE
        if ((!$this->config['maxImageWidth'] || !$this->config['maxImageHeight'])) {

            if ($this->config['maxImageWidth'] &&
                ($this->config['maxImageWidth'] < $img->width)
            ) {
                $width = $this->config['maxImageWidth'];
                $height = $img->getPropHeight($width);

            } elseif (
                $this->config['maxImageHeight'] &&
                ($this->config['maxImageHeight'] < $img->height)
            ) {
                $height = $this->config['maxImageHeight'];
                $width = $img->getPropWidth($height);
            }

            if (isset($width) && isset($height) && !$img->resize($width, $height))
                return false;

        // RESIZE TO FIT
        } elseif (
            $this->config['maxImageWidth'] && $this->config['maxImageHeight'] &&
            !$img->resizeFit($this->config['maxImageWidth'], $this->config['maxImageHeight'])
        )
            return false;

        // AUTO FLIP AND ROTATE FROM EXIF
        if ((($orientation == 2) && !$img->flipHorizontal()) ||
            (($orientation == 3) && !$img->rotate(180)) ||
            (($orientation == 4) && !$img->flipVertical()) ||
            (($orientation == 5) && (!$img->flipVertical() || !$img->rotate(90))) ||
            (($orientation == 6) && !$img->rotate(90)) ||
            (($orientation == 7) && (!$img->flipHorizontal() || !$img->rotate(90))) ||
            (($orientation == 8) && !$img->rotate(270))
        )
            return false;
        if (($orientation >= 2) && ($orientation <= 8) && ($this->imageDriver == "imagick"))
            try {
                $img->image->setImageProperty('exif:Orientation', "1");
            } catch (Exception $e) {}

        // WATERMARK
        if (isset($this->config['watermark']['file']) &&
            is_file($this->config['watermark']['file'])
        ) {
            $left = isset($this->config['watermark']['left'])
                ? $this->config['watermark']['left'] : false;
            $top = isset($this->config['watermark']['top'])
                ? $this->config['watermark']['top'] : false;
            $img->watermark($this->config['watermark']['file'], $left, $top);
		}

        $options = array( 'file' => $file );

        $type = exif_imagetype( $file );

        switch ( $type ) {
            case IMAGETYPE_GIF:
                return $img->output( 'gif', $options );

            case IMAGETYPE_PNG:
		$options['quality'] = 9;
                return $img->output( 'png', $options );

            default:
                return $img->output( 'jpeg', array_merge( $options, array( 'quality' => $this->config['jpegQuality'] ) ) );
        }

    }

    /**
     * @param $file
     * @param bool $overwrite
     * @return bool
     */
    protected function makeThumb($file, $overwrite=true) {
        $img = image::factory($this->imageDriver, $file);

        // Drop files which are not images
        if ($img->initError)
            return true;

        $thumb = substr($file, strlen($this->config['uploadDir']));
        $thumb = $this->config['uploadDir'] . "/" . $this->config['thumbsDir'] . "/" . $thumb;
        $thumb = path::normalize($thumb);
        $thumbDir = dirname($thumb);
        if (!is_dir($thumbDir) && !@mkdir($thumbDir, $this->config['dirPerms'], true))
            return false;

        if (!$overwrite && is_file($thumb))
            return true;

        // Images with smaller resolutions than thumbnails
        /*if (($img->width <= $this->config['thumbWidth']) &&
            ($img->height <= $this->config['thumbHeight'])
        ) {
            list($tmp, $tmp, $type) = @getimagesize($file);
            // Drop only browsable types
            if (in_array($type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
                return true;

        // Resize image
        } else */
        if (!$img->resizeFit($this->config['thumbWidth'], $this->config['thumbHeight']))
            return false;

        if ( $this->imageDriver == 'gd' ) {
            $width  = imagesx( $img->image );
            $height = imagesy( $img->image );
            $back   = image::factory( $this->imageDriver, array( $width, $height ) );
            $tile   = image::factory( $this->imageDriver, __DIR__ . '/../themes/' . $this->config['theme'] . '/img/bg_transparent.png' );

            imagesettile( $back->image, $tile->image );
            imagefilledrectangle( $back->image, 0, 0, $width, $height, IMG_COLOR_TILED );
            imagecopy( $back->image, $img->image, 0, 0, 0, 0, $width, $height );

            $img = $back;
        }

        // Save thumbnail
        return $img->output("jpeg", array(
            'file' => $thumb,
            'quality' => $this->config['jpegQuality']
        ));
    }

    /**
     * @param $langCode
     */
    protected function localize($langCode) {
        require "lang/{$langCode}.php";
        setlocale(LC_ALL, $lang['_locale']);
        $this->charset = $lang['_charset'];
        $this->dateTimeFull = $lang['_dateTimeFull'];
        $this->dateTimeMid = $lang['_dateTimeMid'];
        $this->dateTimeSmall = $lang['_dateTimeSmall'];
        unset($lang['_locale']);
        unset($lang['_charset']);
        unset($lang['_dateTimeFull']);
        unset($lang['_dateTimeMid']);
        unset($lang['_dateTimeSmall']);
        $this->labels = $lang;
    }

    /**
     * @param $string
     * @param array|null $data
     * @return mixed
     */
    protected function label($string, array $data=null) {
        $return = isset($this->labels[$string]) ? $this->labels[$string] : $string;
        if (is_array($data))
            foreach ($data as $key => $val)
                $return = str_replace("{{$key}}", $val, $return);
        return $return;
    }

    /**
     * @param $message
     * @param array|null $data
     */
    protected function backMsg($message, array $data=null) {
        $message = $this->label($message, $data);
        if (isset($this->file['tmp_name']) && file_exists($this->file['tmp_name']))
            @unlink($this->file['tmp_name']);
        $this->callBack("", $message);
        die;
    }

    /**
     * @param $url
     * @param string $message
     */
    protected function callBack($url, $message="") {
        $message = text::jsValue($message);
        $CKfuncNum = isset($this->opener['CKEditor']['funcNum'])
            ? $this->opener['CKEditor']['funcNum'] : 0;
        if (!$CKfuncNum) $CKfuncNum = 0;
        header("Content-Type: text/html; charset={$this->charset}");

?><html>
<body>
<script type='text/javascript'>
var kc_CKEditor = (window.parent && window.parent.CKEDITOR)
    ? window.parent.CKEDITOR.tools.callFunction
    : ((window.opener && window.opener.CKEDITOR)
        ? window.opener.CKEDITOR.tools.callFunction
        : false);
var kc_FCKeditor = (window.opener && window.opener.OnUploadCompleted)
    ? window.opener.OnUploadCompleted
    : ((window.parent && window.parent.OnUploadCompleted)
        ? window.parent.OnUploadCompleted
        : false);
var kc_Custom = (window.parent && window.parent.KCFinder)
    ? window.parent.KCFinder.callBack
    : ((window.opener && window.opener.KCFinder)
        ? window.opener.KCFinder.callBack
        : false);
if (kc_CKEditor)
    kc_CKEditor(<?php echo $CKfuncNum ?>, '<?php echo $url ?>', '<?php echo $message ?>');
if (kc_FCKeditor)
    kc_FCKeditor(<?php echo strlen($message) ? 1 : 0 ?>, '<?php echo $url ?>', '', '<?php echo $message ?>');
if (kc_Custom) {
    if (<?php echo strlen($message) ?>) alert('<?php echo $message ?>');
    kc_Custom('<?php echo $url ?>');
}
if (!kc_CKEditor && !kc_FCKeditor && !kc_Custom)
    alert("<?php echo $message ?>");
</script>
</body>
</html><?php

    }

    /**
     * @return string
     */
    protected function get_htaccess() {
        return "<IfModule mod_php4.c>
  php_value engine off
</IfModule>
<IfModule mod_php5.c>
  php_value engine off
</IfModule>
";
    }
}

?>
