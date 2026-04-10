<?php
@ini_set("display_errors","0");
error_reporting(0);

if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || ! $modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

$version = "0.2.0";
$Store = new Store;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch($action){
case 'saveuser':
	$_SESSION['STORE_USER'] = $_POST['res'];
	break;

case 'exituser':
	$_SESSION['STORE_USER'] = '';
	break;
case 'install2_step':
    $_GET['action'] = 'install';
    require 'installer/index.php';
    break;

case 'install':
case 'install_file':
	if (is_dir(EVO_BASE_PATH . 'assets/cache/store/')) $Store->removeFolder(EVO_BASE_PATH . 'assets/cache/store/');
	$id = (int) $_REQUEST['cid'];
	@mkdir("../assets/cache/store", 0777);
	@mkdir("../assets/cache/store/tmp_install", 0777);
	@mkdir("../assets/cache/store/install", 0777);

	if($action == 'install') {
		$file = $_POST['file']==''? $_GET['file'] : $_POST['file'];
		if ($file!='%url%' && $file!='' && $file!=' '){
			$url = $file;
		} else {
			$url = "https://extras.evo.im/get.php?get=file&cid=" .$id;
		}

		if (!$Store->downloadFile($url ,EVO_BASE_PATH."assets/cache/store/temp.zip")){
			$Store->quit();
		}
	} else {
		$extension = pathinfo($_FILES['install_file']['name'], PATHINFO_EXTENSION);
		if( !in_array($extension, ['zip'])) {
			die('Only ZIP-Files allowed');
		}
		if (!move_uploaded_file($_FILES['install_file']['tmp_name'], EVO_BASE_PATH."assets/cache/store/temp.zip")) {
			die('Uploaded File could not be moved to assets/cache/store/');
		}
		$msg = $Store->lang['install_file_success'];
	}

	$zip = new ZipArchive;
	$res = $zip->open(EVO_BASE_PATH."assets/cache/store/temp.zip");
	if ($res === TRUE) {

		// echo 'Archive open';
		$zip->extractTo(EVO_BASE_PATH."assets/cache/store/tmp_install");
		$zip->close();
		$handle = opendir('../assets/cache/store/tmp_install');
		if ($handle) {
			while (false !== ($name = readdir($handle))) if ($name != "." && $name != "..") $dir = $name;
			closedir($handle);
		}

		$name = strtolower($name);
		$Store->copyFolder('../assets/cache/store/tmp_install/'.$dir, '../assets/cache/store/install');
		$Store->removeFolder('../assets/cache/store/tmp_install/install/');

		$Store->copyFolder('../assets/cache/store/tmp_install/'.$dir, '../');
		$Store->removeFolder('../install/');
		$Store->removeFolder('../assets/cache/store/tmp_install/');

        $arr_dependencies = [];
        if (isset($_GET['dependencies']) && $_GET['dependencies'] != '') {
            $arr_dependencies = explode(',', $_GET['dependencies']);
            $result = \EvolutionCMS\Models\SiteSnippet::query()->whereIn('name', $arr_dependencies)->pluck('name');
            foreach ($result as $value) {
                $key = array_search($value, $arr_dependencies);
                if ($key !== false) {
                    unset($arr_dependencies[$key]);
                }
            }
            if(count($arr_dependencies) > 0){
                $result = \EvolutionCMS\Models\SitePlugin::query()->whereIn('name', $arr_dependencies)->pluck('name');
                foreach ($result as $value) {
                    $key = array_search($value, $arr_dependencies);
                    if ($key !== false) {
                        unset($arr_dependencies[$key]);
                    }
                }
            }
            if(count($arr_dependencies) > 0){
                $result = \EvolutionCMS\Models\SiteModule::query()->whereIn('name', $arr_dependencies)->pluck('name');
                foreach ($result as $value) {
                    $key = array_search($value, $arr_dependencies);
                    if ($key !== false) {
                        unset($arr_dependencies[$key]);
                    }
                }
            }

        }
        $strError = '';
        if(count($arr_dependencies) > 0){
            $bodyClass = ((int)$modx->config['manager_theme_mode'] === 4) ? ' class="darkness"' : '';
            $strError =  '<!DOCTYPE html>
<html><head><title>Install</title>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />
<link rel="stylesheet" href="'.EVO_SITE_URL.'assets/modules/store/installer/style.css" type="text/css" media="screen" /></head>
<body'.$bodyClass.'><div id="contentarea"><div class="container_12"><br>';


            $strError .= '<h2>Error installation</h2><br><br><p>Before install '. htmlspecialchars($_GET['name']).'<br> Please install this packages: <br>'.implode('<br>', $arr_dependencies).'</p>';

            $strError .= "</div><!-- // content --></div><!-- // contentarea --><br /></body></html>";
        }
        if ($_GET['method'] != 'fast') {

            if($strError != ''){
                echo $strError;
                exit();
            }
            $_GET['action'] = 'options';
            require 'installer/index.php';
            die();
        } else {
            if($strError != ''){
                $data = ['result'=> 'error', 'data'=>$strError];
                header('Content-Type: application/json');
                echo json_encode($data);
                exit();
            }
            chdir('../assets/modules/store/installer/');
            ob_start();
            require "instprocessor-fast.php";
            $content = ob_get_contents();
            ob_end_clean();
        }
	} else {

	}

	$Store->removeFolder(EVO_BASE_PATH.'assets/cache/store/');
	if($action == 'install') {
		die('[{"result":"true"}]');
	} else {
		die($msg);
	}

	break;

case 'console_catalog':
	header('Content-Type: application/json; charset=UTF-8');
	$catalog = $Store->getConsoleCatalog();
	echo json_encode([
		'ok' => true,
		'items' => $catalog,
		'count' => count($catalog)
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit();

case 'console_readme':
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(
		$Store->getConsoleReadmePayload(
			isset($_GET['repo']) ? (string) $_GET['repo'] : '',
			isset($_GET['branch']) ? (string) $_GET['branch'] : '',
			isset($_GET['source_url']) ? (string) $_GET['source_url'] : ''
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	exit();

default:
	$legacyInstalled = $Store->getLegacyInstalledState();
	$installedState = [
		'legacy_by_type' => $legacyInstalled['by_type'],
		'legacy_items' => $legacyInstalled['items'],
		'console_by_composer' => $Store->getConsoleInstalledState(),
	];


	$Store->lang['user_email'] = $_SESSION['mgrEmail'];
	$Store->lang['hash'] = isset($_SESSION['STORE_USER']) ? stripslashes( $_SESSION['STORE_USER'] ) : '';
	$Store->lang['lang'] = $Store->language;
	$Store->lang['_type'] = json_encode($legacyInstalled['by_type'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$Store->lang['installed_state'] = json_encode($installedState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$Store->lang['v'] = $version;
	$Store->lang['project_path'] = rtrim(EVO_BASE_PATH, '/\\');
	$Store->lang['core_path'] = defined('EVO_CORE_PATH')
		? rtrim(EVO_CORE_PATH, '/\\')
		: rtrim(EVO_BASE_PATH, '/\\') . '/core';
	if ((int)$modx->config['manager_theme_mode'] == 4 ){
		$Store->lang['body_class_name'] = 'darkness';
	}
	$tpl = Store::parse( $Store->tpl(dirname( __FILE__ ).'/template/main.html') ,$modx->config ) ;
	$tpl = Store::parse( $tpl ,$Store->lang ) ;
	echo $tpl;
	break;
}


class Store{
	public $lang;
	public $language;

	function __construct(){
		$modx = EvolutionCMS();
		$lang = $modx->config['manager_language'];
		if (file_exists( __DIR__ .  '/lang/'.$lang.'.php')){
			include_once(__DIR__ .  '/lang/'.$lang.'.php');
		} else {
			include_once(__DIR__ .  '/lang/en.php');
		}
		$this->lang = $_Lang;
		$this->language = substr($lang,0,2);
	}

	function quit(){
		die('[{"result":"false","error":"'.implode(' \r\n ', $this->errors ).'"}]');
	}
	function get_version($text){
		preg_match('/<strong>(.*)<\/strong>/s',$text, $match);
		return isset($match[1]) ? $match[1] : '';
	}

    static function parse($tpl, $fields){
        $modx = EvolutionCMS();
        $tpl = $modx->parseText($tpl, $fields);
        $evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
        $onManagerMainFrameHeaderHTMLBlock = is_array($evtOut) ? implode("\n", $evtOut) : '';
        $tpl = str_replace('[+onManagerMainFrameHeaderHTMLBlock+]',$onManagerMainFrameHeaderHTMLBlock,$tpl);
        return $tpl;
    }
	function tpl($file){
		$lang = $this->lang;
		ob_start();
		include($file);
		$tpl = ob_get_contents();
		ob_end_clean();
		return $tpl;
	}


	public function downloadFile ($url, $path) {
		$newfname = $path;
		try {
			if (ini_get('allow_url_fopen') == true) {
				$file = fopen ($url, "rb");
				if (! $file) {
					throw new Exception("Could not open the file!");
				}
				if ($file) {
					$newf = fopen ($newfname, "wb");
					if ($newf)
					while(!feof($file)) {
						fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
					}
				}
				if ($file) fclose($file);
				if ($newf) fclose($newf);
				return true;
			} else if (function_exists('curl_init')) {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					$content = curl_exec ($ch);
					file_put_contents($newfname,$content);
				return true;
			} else {
			  $this->errors[] = 'Error:Download: '.$e->getFile(). 'line '.$e->getLine().'): '.$e->getMessage();
			  return false;
			}
		} catch(Exception $e) {
				$this->errors[] = 'Error:Download: '.$e->getFile(). 'line '.$e->getLine().'): '.$e->getMessage();
				return false;
			}
	}

	public function fetchRemoteBody($url) {
		try {
			if (ini_get('allow_url_fopen') == true) {
				$context = stream_context_create([
					'http' => [
						'method' => 'GET',
						'header' => "User-Agent: Evolution CMS Store\r\n",
						'timeout' => 20,
					]
				]);
				$content = @file_get_contents($url, false, $context);
				if ($content !== false) {
					return $content;
				}
			}

			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_TIMEOUT, 20);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Evolution CMS Store');
				$content = curl_exec($ch);
				curl_close($ch);
				if (is_string($content) && $content !== '') {
					return $content;
				}
			}
		} catch(Exception $e) {
			$this->errors[] = 'Error:Fetch: '.$e->getFile(). ' line '.$e->getLine().'): '.$e->getMessage();
		}

		return '';
	}

	public function getConsoleCatalog() {
		$raw = $this->fetchRemoteBody('https://evo.im/extras.json');
		if (!is_string($raw) || trim($raw) === '') {
			return [];
		}

		$data = json_decode($raw, true);
		if (!is_array($data) || !isset($data['packages']) || !is_array($data['packages'])) {
			return [];
		}

		$items = [];
		$consoleInstalled = $this->getConsoleInstalledState();
		foreach ($data['packages'] as $package) {
			if (!is_array($package)) {
				continue;
			}

			$name = isset($package['name']) ? trim((string) $package['name']) : '';
			$composerName = isset($package['composer_name']) ? trim((string) $package['composer_name']) : '';
			if ($name === '' || $composerName === '') {
				continue;
			}

			$latestRelease = isset($package['latest_release']) ? trim((string) $package['latest_release']) : '';
			$defaultBranch = isset($package['default_branch']) ? trim((string) $package['default_branch']) : '';
			$tags = isset($package['tags']) && is_array($package['tags']) ? $package['tags'] : [];
			$installVersion = $latestRelease !== '' ? $latestRelease : $defaultBranch;
			$displayVersion = $installVersion !== '' ? $installVersion : 'main';
			$fullName = isset($package['full_name']) ? trim((string) $package['full_name']) : '';
			$author = '';
			$composerKey = strtolower($composerName);
			if ($fullName !== '' && strpos($fullName, '/') !== false) {
				$author = explode('/', $fullName)[0];
			}
			$installedVersion = '';
			$rawInstalledVersion = '';
			$isInstalled = false;
			if (isset($consoleInstalled[$composerKey])) {
				$isInstalled = !empty($consoleInstalled[$composerKey]['is_installed']);
				$installedVersion = isset($consoleInstalled[$composerKey]['version']) ? (string) $consoleInstalled[$composerKey]['version'] : '';
				$rawInstalledVersion = isset($consoleInstalled[$composerKey]['raw_version']) ? (string) $consoleInstalled[$composerKey]['raw_version'] : '';
			}
			$statusClass = $this->resolveConsoleStatusClass($installedVersion, $displayVersion, $defaultBranch);

			$versionOptions = [];
			$stableTags = [];
			foreach ($tags as $tag) {
				if (!is_string($tag) || trim($tag) === '') {
					continue;
				}
				$tag = trim($tag);
				if (!$this->isStableReleaseVersion($tag)) {
					continue;
				}
				$stableTags[] = $tag;
				$versionOptions[] = [
					'file' => $tag,
					'version' => $tag,
					'date' => '',
				];
			}
			$isDevOnly = count($stableTags) === 0;
			if ($defaultBranch !== '') {
				$hasDefaultBranchOption = false;
				foreach ($versionOptions as $option) {
					if (isset($option['file']) && (string) $option['file'] === $defaultBranch) {
						$hasDefaultBranchOption = true;
						break;
					}
				}
				if (!$hasDefaultBranchOption) {
					$versionOptions[] = [
						'file' => $defaultBranch,
						'version' => $defaultBranch,
						'date' => '',
					];
				}
			}
			if ($versionOptions === [] && $defaultBranch !== '') {
				$versionOptions[] = [
					'file' => $defaultBranch,
					'version' => $defaultBranch,
					'date' => '',
				];
			}

			$items[] = [
				'id' => 'console-' . md5($composerName),
				'title' => $name,
				'name' => $name,
				'name_in_modx' => $name,
				'composer_name' => $composerName,
				'description' => isset($package['description']) ? trim((string) $package['description']) : '',
				'type' => 'package',
				'install_method' => 'console-extra',
				'install_target' => $name,
				'install_command' => '',
				'version' => $displayVersion,
				'current_version' => $installedVersion,
				'raw_current_version' => $rawInstalledVersion,
				'is_installed' => $isInstalled ? 1 : 0,
				'cls' => $statusClass,
				'downloads' => '',
				'author' => $author,
				'date' => '',
				'source_url' => isset($package['html_url']) ? trim((string) $package['html_url']) : '',
				'repo_full_name' => $fullName,
				'readme_branch' => $defaultBranch !== '' ? $defaultBranch : 'main',
				'url' => [
					'fieldValue' => $versionOptions,
				],
				'dependencies' => '',
				'deprecated' => 0,
				'is_dev_package' => $isDevOnly ? 1 : 0,
			];
		}

		usort($items, function($a, $b) {
			return strcasecmp($a['title'], $b['title']);
		});

		return $items;
	}

	private function isStableReleaseVersion($value) {
		$value = trim((string) $value);
		if ($value === '') {
			return false;
		}

		return (bool) preg_match('/^v?\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', $value);
	}

	public function getConsoleReadmePayload($repo, $branch = '', $sourceUrl = '') {
		$repo = $this->sanitizeRepoFullName($repo);
		$branch = $this->sanitizeRefName($branch);
		$repoUrl = trim((string) $sourceUrl);

		if ($repo === '') {
			return [
				'ok' => false,
				'html' => '',
				'message' => $this->lang['popup_readme_missing'],
				'repo_url' => $repoUrl,
			];
		}

		if ($repoUrl === '') {
			$repoUrl = 'https://github.com/' . $repo;
		}

		if ($branch === '') {
			$branch = 'main';
		}

		foreach (['README.md', 'readme.md', 'Readme.md'] as $fileName) {
			$raw = $this->fetchRemoteBody($this->buildGithubRawUrl($repo, $branch, $fileName));
			if (!is_string($raw)) {
				continue;
			}

			$trimmed = trim($raw);
			if ($trimmed === '' || $trimmed === '404: Not Found') {
				continue;
			}

			return [
				'ok' => true,
				'html' => $this->renderMarkdownHtml($raw, $repoUrl, $branch),
				'message' => '',
				'repo_url' => $repoUrl,
			];
		}

		return [
			'ok' => false,
			'html' => '',
			'message' => $this->lang['popup_readme_missing'],
			'repo_url' => $repoUrl,
		];
	}

	private function sanitizeRepoFullName($repo) {
		$repo = trim((string) $repo);
		if ($repo === '') {
			return '';
		}

		if (!preg_match('~^[A-Za-z0-9._-]+/[A-Za-z0-9._-]+$~', $repo)) {
			return '';
		}

		return $repo;
	}

	public function getLegacyInstalledState() {
		$byType = [
			'snippets' => [],
			'plugins' => [],
			'modules' => [],
		];
		$items = [];

		$snippets = \EvolutionCMS\Models\SiteSnippet::query()->get();
		foreach ($snippets as $snippet) {
			$version = $this->get_version($snippet->description);
			$byType['snippets'][$snippet->name] = $version;
			$items[] = [
				'type' => 'snippets',
				'name' => $snippet->name,
				'version' => $version,
				'is_installed' => 1,
			];
		}
		$byType['snippets_writable'] = is_writable(EVO_BASE_PATH . 'assets/snippets');

		$plugins = \EvolutionCMS\Models\SitePlugin::query()->get();
		foreach ($plugins as $plugin) {
			$version = $this->get_version($plugin->description);
			$byType['plugins'][$plugin->name] = $version;
			$items[] = [
				'type' => 'plugins',
				'name' => $plugin->name,
				'version' => $version,
				'is_installed' => 1,
			];
		}
		$byType['plugins_writable'] = is_writable(EVO_BASE_PATH . 'assets/plugins');

		$modules = \EvolutionCMS\Models\SiteModule::query()->get();
		foreach ($modules as $module) {
			$version = $this->get_version($module->description);
			$byType['modules'][$module->name] = $version;
			$items[] = [
				'type' => 'modules',
				'name' => $module->name,
				'version' => $version,
				'is_installed' => 1,
			];
		}
		$byType['modules_writable'] = is_writable(EVO_BASE_PATH . 'assets/modules');

		return [
			'by_type' => $byType,
			'items' => $items,
		];
	}

	public function getConsoleInstalledState() {
		static $state = null;
		if ($state !== null) {
			return $state;
		}

		$state = [];
		if (!class_exists('\\Composer\\InstalledVersions')) {
			return $state;
		}

		try {
			foreach (\Composer\InstalledVersions::getInstalledPackages() as $packageName) {
				if (!is_string($packageName) || $packageName === '') {
					continue;
				}

				$key = strtolower($packageName);
				$prettyVersion = '';
				try {
					$prettyVersion = (string) \Composer\InstalledVersions::getPrettyVersion($packageName);
				} catch (\Throwable $exception) {
					$prettyVersion = '';
				}

				$state[$key] = [
					'is_installed' => true,
					'raw_version' => $prettyVersion,
					'version' => $this->normalizeInstalledComposerVersion($prettyVersion),
				];
			}
		} catch (\Throwable $exception) {
			return [];
		}

		return $state;
	}

	private function normalizeInstalledComposerVersion($version) {
		$version = trim((string) $version);
		if ($version === '') {
			return '';
		}

		if (strpos($version, 'dev-') === 0) {
			return substr($version, 4);
		}

		return $version;
	}

	private function resolveConsoleStatusClass($installedVersion, $catalogVersion, $defaultBranch = '') {
		$installedVersion = trim((string) $installedVersion);
		$catalogVersion = trim((string) $catalogVersion);
		$defaultBranch = trim((string) $defaultBranch);

		if ($installedVersion === '') {
			return 'pack_install';
		}

		$normalizedInstalled = $this->normalizeComparableVersion($installedVersion, $defaultBranch);
		$normalizedCatalog = $this->normalizeComparableVersion($catalogVersion, $defaultBranch);

		if ($normalizedInstalled !== '' && $normalizedCatalog !== '' && $normalizedInstalled === $normalizedCatalog) {
			return 'pack_reinstall';
		}

		if ($this->isComparableSemver($normalizedInstalled) && $this->isComparableSemver($normalizedCatalog)) {
			return version_compare($normalizedInstalled, $normalizedCatalog, '<') ? 'pack_update' : 'pack_reinstall';
		}

		return 'pack_reinstall';
	}

	private function normalizeComparableVersion($version, $defaultBranch = '') {
		$version = trim((string) $version);
		$defaultBranch = trim((string) $defaultBranch);
		if ($version === '') {
			return '';
		}

		if (strpos($version, 'dev-') === 0) {
			$version = substr($version, 4);
		}

		if ($defaultBranch !== '' && $version === $defaultBranch) {
			return $defaultBranch;
		}

		if (preg_match('/^v(\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?)$/', $version, $matches)) {
			return $matches[1];
		}

		return $version;
	}

	private function isComparableSemver($version) {
		return (bool) preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', (string) $version);
	}

	private function sanitizeRefName($ref) {
		$ref = trim((string) $ref);
		if ($ref === '') {
			return '';
		}

		return preg_replace('~[^A-Za-z0-9._/-]~', '', $ref);
	}

	private function buildGithubRawUrl($repo, $branch, $fileName) {
		$parts = explode('/', $repo, 2);
		if (count($parts) !== 2) {
			return '';
		}

		return sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/%s',
			rawurlencode($parts[0]),
			rawurlencode($parts[1]),
			rawurlencode($branch),
			rawurlencode($fileName)
		);
	}

	private function renderMarkdownHtml($markdown, $repoUrl = '', $branch = 'main') {
		$markdown = str_replace(["\r\n", "\r"], "\n", (string) $markdown);
		$markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

		if (class_exists('\\Illuminate\\Support\\Str')) {
			try {
				$html = (string) \Illuminate\Support\Str::markdown($markdown, [
					'html_input' => 'allow',
					'allow_unsafe_links' => false,
					'max_nesting_level' => 20,
				]);

				if (trim($html) !== '') {
					return $this->postProcessRenderedMarkdownHtml($html, $repoUrl, $branch);
				}
			} catch (\Throwable $exception) {
			}
		}

		return $this->renderMarkdownHtmlFallback($markdown, $repoUrl, $branch);
	}

	private function renderMarkdownHtmlFallback($markdown, $repoUrl = '', $branch = 'main') {
		$markdown = str_replace(["\r\n", "\r"], "\n", (string) $markdown);
		$markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

		$codeBlocks = [];
		$markdown = preg_replace_callback('/```([a-zA-Z0-9_-]*)\n(.*?)```/s', function ($matches) use (&$codeBlocks) {
			$language = trim($matches[1]);
			$code = htmlspecialchars(rtrim($matches[2], "\n"), ENT_QUOTES, 'UTF-8');
			$placeholder = '__CODE_BLOCK_' . count($codeBlocks) . '__';
			$codeBlocks[$placeholder] = '<pre><code' . ($language !== '' ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '') . '>' . $code . '</code></pre>';
			return "\n" . $placeholder . "\n";
		}, $markdown);

		$lines = explode("\n", $markdown);
		$html = [];
		$paragraph = [];
		$listType = '';
		$listItems = [];
		$indentedCode = [];

		$flushParagraph = function () use (&$paragraph, &$html, $repoUrl, $branch) {
			if ($paragraph === []) {
				return;
			}

			$text = trim(implode(' ', $paragraph));
			if ($text !== '') {
				$html[] = '<p>' . $this->renderMarkdownInline($text, $repoUrl, $branch) . '</p>';
			}
			$paragraph = [];
		};

		$flushList = function () use (&$listType, &$listItems, &$html, $repoUrl, $branch) {
			if ($listType === '' || $listItems === []) {
				return;
			}

			$itemsHtml = [];
			foreach ($listItems as $item) {
				$itemsHtml[] = '<li>' . $this->renderMarkdownInline($item, $repoUrl, $branch) . '</li>';
			}

			$html[] = '<' . $listType . '>' . implode('', $itemsHtml) . '</' . $listType . '>';
			$listType = '';
			$listItems = [];
		};

		$flushIndentedCode = function () use (&$indentedCode, &$html) {
			if ($indentedCode === []) {
				return;
			}

			$code = htmlspecialchars(rtrim(implode("\n", $indentedCode), "\n"), ENT_QUOTES, 'UTF-8');
			$html[] = '<pre><code>' . $code . '</code></pre>';
			$indentedCode = [];
		};

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if ($indentedCode !== []) {
				if ($trimmed === '') {
					$indentedCode[] = '';
					continue;
				}

				if (preg_match('/^(?: {4}|\t)(.*)$/', $line, $matches)) {
					$indentedCode[] = $matches[1];
					continue;
				}

				$flushIndentedCode();
			}

			if ($trimmed === '') {
				$flushParagraph();
				$flushList();
				continue;
			}

			if (isset($codeBlocks[$trimmed])) {
				$flushParagraph();
				$flushList();
				$flushIndentedCode();
				$html[] = $codeBlocks[$trimmed];
				continue;
			}

			if (preg_match('/^(?: {4}|\t)(.*)$/', $line, $matches)) {
				$flushParagraph();
				$flushList();
				$indentedCode[] = $matches[1];
				continue;
			}

			if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $matches)) {
				$flushParagraph();
				$flushList();
				$flushIndentedCode();
				$level = strlen($matches[1]);
				$html[] = '<h' . $level . '>' . $this->renderMarkdownInline($matches[2], $repoUrl, $branch) . '</h' . $level . '>';
				continue;
			}

			if (preg_match('/^>\s?(.*)$/', $trimmed, $matches)) {
				$flushParagraph();
				$flushList();
				$flushIndentedCode();
				$html[] = '<blockquote><p>' . $this->renderMarkdownInline($matches[1], $repoUrl, $branch) . '</p></blockquote>';
				continue;
			}

			if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $matches)) {
				$flushParagraph();
				$flushIndentedCode();
				if ($listType !== 'ul') {
					$flushList();
					$listType = 'ul';
				}
				$listItems[] = $matches[1];
				continue;
			}

			if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $matches)) {
				$flushParagraph();
				$flushIndentedCode();
				if ($listType !== 'ol') {
					$flushList();
					$listType = 'ol';
				}
				$listItems[] = $matches[1];
				continue;
			}

			$paragraph[] = $trimmed;
		}

		$flushParagraph();
		$flushList();
		$flushIndentedCode();

		return implode("\n", $html);
	}

	private function postProcessRenderedMarkdownHtml($html, $repoUrl = '', $branch = 'main') {
		$html = trim((string) $html);
		if ($html === '' || !class_exists('\\DOMDocument')) {
			return $html;
		}

		$previous = libxml_use_internal_errors(true);
		$document = new \DOMDocument('1.0', 'UTF-8');
		$loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		if (!$loaded) {
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
			return $html;
		}

		$images = $document->getElementsByTagName('img');
		for ($index = $images->length - 1; $index >= 0; $index--) {
			$image = $images->item($index);
			if ($image instanceof \DOMElement && $image->hasAttribute('src')) {
				$image->setAttribute('src', $this->resolveMarkdownUrl($image->getAttribute('src'), $repoUrl, $branch, true));
			}
		}

		$links = $document->getElementsByTagName('a');
		for ($index = $links->length - 1; $index >= 0; $index--) {
			$link = $links->item($index);
			if (!($link instanceof \DOMElement) || !$link->hasAttribute('href')) {
				continue;
			}

			$resolvedHref = $this->resolveMarkdownUrl($link->getAttribute('href'), $repoUrl, $branch, false);
			$link->setAttribute('href', $resolvedHref);

			if (!preg_match('~^#~', $resolvedHref)) {
				$link->setAttribute('target', '_blank');
				$link->setAttribute('rel', 'noopener');
			}
		}

		$result = $document->saveHTML();
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		return preg_replace('/^<\\?xml.+?\\?>/i', '', (string) $result);
	}

	private function renderMarkdownInline($text, $repoUrl = '', $branch = 'main') {
		$text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

		$text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($matches) use ($repoUrl, $branch) {
			$alt = htmlspecialchars_decode($matches[1], ENT_QUOTES);
			$url = htmlspecialchars_decode($matches[2], ENT_QUOTES);
			$resolved = $this->resolveMarkdownUrl($url, $repoUrl, $branch, true);
			return '<img src="' . htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
		}, $text);

		$text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($matches) use ($repoUrl, $branch) {
			$label = htmlspecialchars_decode($matches[1], ENT_QUOTES);
			$url = htmlspecialchars_decode($matches[2], ENT_QUOTES);
			$resolved = $this->resolveMarkdownUrl($url, $repoUrl, $branch, false);
			return '<a href="' . htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
		}, $text);

		$text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
		$text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
		$text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);

		return $text;
	}

	private function resolveMarkdownUrl($url, $repoUrl = '', $branch = 'main', $isImage = false) {
		$url = trim((string) $url);
		if ($url === '') {
			return '#';
		}

		if (preg_match('~^(https?:)?//|^mailto:|^#~i', $url)) {
			return $url;
		}

		if ($repoUrl === '') {
			return $url;
		}

		$path = ltrim(preg_replace('~^\./~', '', $url), '/');
		if ($path === '') {
			return $repoUrl;
		}

		$base = rtrim($repoUrl, '/');
		if ($isImage) {
			return $base . '/raw/' . rawurlencode($branch) . '/' . str_replace('%2F', '/', rawurlencode($path));
		}

		return $base . '/blob/' . rawurlencode($branch) . '/' . str_replace('%2F', '/', rawurlencode($path));
	}



	public function removeFolder($path){
		$dir = realpath($path);
		if ( !is_dir($dir)) return;
		$it = new RecursiveDirectoryIterator($dir);
		$files = new RecursiveIteratorIterator($it,
		RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
			if ($file->getFilename() === '.' || $file->getFilename() === '..') {
				continue;
			}
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($dir);
	}
	public static function copyFolder($src, $dest) {
		$path = realpath($src);
		$dest = realpath($dest);
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
		foreach($objects as $name => $object)
		{
			if (!$objects->getDepth() && $object->isFile()) continue;
			$startsAt = substr(dirname($name), strlen($path));
			self::mkDir($dest.$startsAt);
			if ( $object->isDir() ) {
				@self::mkDir($dest.substr($name, strlen($path)));
			}

			if(is_writable($dest.$startsAt) and $object->isFile())
			{
				copy((string)$name, $dest.$startsAt.DIRECTORY_SEPARATOR.basename($name));
			}
		}
	}

	private static function mkDir($folder, $perm=0777) {
		if(!is_dir($folder)) {
			mkdir($folder, $perm);
		}
	}
}
