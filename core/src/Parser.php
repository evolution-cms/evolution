<?php namespace EvolutionCMS;

use EvolutionCMS\Legacy\Phx;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;

/**
 */
class Parser
{
    public const TV_BINDINGS = [
        'FILE',
        'CHUNK',
        'DOCUMENT',
        'SELECT',
        'EVAL',
        'INHERIT',
        'DIRECTORY',
    ];

    /**
     * Объект Core - основной класс MODX
     * @var Core $modx
     * @access protected
     */
    protected $modx;

    /**
     * @var Parser cached reference to singleton instance
     */
    protected static $instance;

    protected $templatePath = 'assets/templates/';

    protected $templateExtension = 'html';

    public $blade;

    protected $bladeEnabled = true;

    protected $templateData = [];

    public $phx;

    /**
     * gets the instance via lazy initialization (created on first usage)
     *
     * @return self
     */
    public static function getInstance (Core $modx)
    {

        if (null === self::$instance) {
            self::$instance = new self($modx);
        }

        return self::$instance;
    }

    /**
     * is not allowed to call from outside: private!
     *
     */
    private function __construct (Core $modx)
    {
        $this->modx = $modx;
        $this->loadBlade();
    }

    /**
     * prevent the instance from being cloned
     *
     * @return void
     */
    private function __clone ()
    {
    }

    /**
     * prevent from being unserialized
     *
     * @return void
     */
    public function __wakeup ()
    {
    }

    /**
     * @return string
     */
    public function getTemplatePath ()
    {
        return $this->templatePath;
    }

    /**
     * Задает относительный путь к папке с шаблонами
     *
     * @param string $path
     * @param bool $supRoot
     * @return $this
     */
    public function setTemplatePath ($path, $supRoot = false)
    {
        $path = trim($path);
        if ($supRoot === false) {
            $path = $this->cleanPath($path);
        }

        if (!empty($path)) {
            $this->templatePath = $path;
            if ($this->blade) {
                $filesystem = new Filesystem;
                $viewFinder = new FileViewFinder($filesystem, [EVO_BASE_PATH . $path]);
                $this->blade->setFinder($viewFinder);
            }
        }

        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function cleanPath ($path)
    {
        return preg_replace(['/\.*[\/|\\\]/i', '/[\/|\\\]+/i'], ['/', '/'], $path);
    }

    public function getTemplateExtension ()
    {
        return $this->templateExtension;
    }

    /**
     * Задает расширение файла с шаблоном
     *
     * @param $ext
     * @return $this
     */
    public function setTemplateExtension ($ext)
    {
        $ext = $this->cleanPath(trim($ext, ". \t\n\r\0\x0B"));

        if (!empty($ext)) {
            $this->templateExtension = $ext;
        }

        return $this;
    }

    /**
     * Additional data for external templates
     *
     * @param array $data
     * @return $this
     */
    public function setTemplateData ($data = [])
    {
        if (is_array($data)) {
            $this->templateData = $data;
        }

        return $this;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getTemplateData ($data = [])
    {
        $plh = array_merge($this->modx->getDataForView(), $this->templateData);
        $plh['data'] = $data;
        $plh['modx'] = $this->modx;

        return $plh;
    }

    /**
     * Сохранение данных в массив плейсхолдеров
     *
     * @param mixed $data данные
     * @param int $set устанавливать ли глобальнй плейсхолдер MODX
     * @param string $key ключ локального плейсхолдера
     * @param string $prefix префикс для ключей массива
     * @return string
     */
    public function toPlaceholders ($data, $set = 0, $key = 'contentPlaceholder', $prefix = '')
    {
        $out = '';
        if ($set != 0) {
            $this->modx->toPlaceholder($key, $data, $prefix);
        } else {
            $out = $data;
        }

        return $out;
    }

    /**
     * refactor $modx->getChunk();
     *
     * @param string $name Template: chunk name || @CODE: template || @FILE: file with template
     * @return string html template with placeholders without data
     */
    public function getChunk ($name)
    {
        $tpl = '';
        $ext = null;
        $this->bladeEnabled = substr($name, 0, 3) == '@B_';//(0 === strpos($name, '@B_'));
        if ($name != '' && !isset($this->modx->chunkCache[$name])) {
            $mode = (preg_match(
                    '/^((@[A-Z_]+)[:]{0,1})(.*)/Asu',
                    trim($name),
                    $tmp
                ) && isset($tmp[2], $tmp[3])) ? $tmp[2] : false;
            $subTmp = (isset($tmp[3])) ? trim($tmp[3]) : null;
            if ($this->bladeEnabled) {
                $ext = $this->getTemplateExtension();
                $this->setTemplateExtension('blade.php');
            }
            switch ($mode) {
                case '@T_CODE':
                case '@T_FILE':
                    break;
                case '@B_FILE':
                    if ($subTmp != '' && $this->bladeEnabled) {
                        $tpl = $this->blade->make($this->cleanPath($subTmp));
                    }
                    break;
                case '@B_CODE':
                    $cache = md5($name) . '-' . sha1($subTmp);
                    $filesystem = $this->modx['filesystem']->drive('storage');
                    if (!$filesystem->exists('blade/' . $cache . '.blade.php')) {
                        $filesystem->put('blade/' . $cache . '.blade.php', $subTmp);
                    }
                    $this->blade->addNamespace('cache', $filesystem->path('blade/'));
                    $tpl = $this->blade->make('cache::' . $cache);
                    break;
                case '@FILE':
                    if ($subTmp != '') {
                        $real = realpath(EVO_BASE_PATH . $this->templatePath);
                        $path = realpath(EVO_BASE_PATH . $this->templatePath . $this->cleanPath($subTmp) . '.' . $this->templateExtension);
                        if (basename($path, '.' . $this->templateExtension) !== '' &&
                            0 === strpos($path, $real) &&
                            file_exists($path)
                        ) {
                            $tpl = file_get_contents($path);
                        }
                    }
                    break;
                case '@INLINE':
                case '@TPL':
                case '@CODE':
                    $tpl = $tmp[3]; //without trim
                    break;
                case '@DOCUMENT':
                case '@DOC':
                    switch (true) {
                        case ((int)$subTmp > 0):
                            $tpl = $this->modx->getPageInfo((int)$subTmp, 0, "content");
                            $tpl = isset($tpl['content']) ? $tpl['content'] : '';
                            break;
                        case ((int)$subTmp == 0):
                            $tpl = $this->modx->documentObject['content'];
                            break;
                    }
                    break;
                case '@PLH':
                case '@PLACEHOLDER':
                    if ($subTmp != '') {
                        $tpl = $this->modx->getPlaceholder($subTmp);
                    }
                    break;
                case '@CFG':
                case '@CONFIG':
                case '@OPTIONS':
                    if ($subTmp != '') {
                        $tpl = $this->modx->getConfig($subTmp);
                    }
                    break;
                case '@SNIPPET':
                    if ($subTmp != '') {
                        $tpl = $this->modx->runSnippet($subTmp, $this->modx->event->params);
                    }
                    break;
                case '@RENDERPAGE':
                    $tpl = $this->renderDoc($subTmp, false);
                    break;
                case '@LOADPAGE':
                    $tpl = $this->renderDoc($subTmp, true);
                    break;
                case '@TEMPLATE':
                    $tpl = $this->getTemplate($subTmp);
                    break;
                case '@CHUNK':
                    $tpl = $this->getBaseChunk($subTmp);
                    break;
                default:
                    $tpl = $this->getBaseChunk($name);
            }
            $this->modx->chunkCache[$name] = $tpl;
        } else {
            $tpl = $this->getBaseChunk($name);
        }

        if ($ext !== null) {
            $this->setTemplateExtension($ext);
        }

        return $tpl;
    }

    public function getBaseChunk ($name)
    {
        if (empty($name)) {
            return null;
        }

        if (array_key_exists($name, $this->modx->chunkCache)) {
            $tpl = $this->modx->chunkCache[$name];
        } else {
            /** @var \Illuminate\Database\Eloquent\Collection $chunk */
            $chunk = Models\SiteHtmlsnippet::where('name', '=', $name)
                ->where('disabled', '=', 0)
                ->get();

            $tpl = ($chunk->count() === 1) ? $chunk->first()->snippet : null;
            $this->modx->chunkCache[$name] = $tpl;
        }

        return $tpl;
    }

    /**
     * Рендер документа с подстановкой плейсхолдеров и выполнением сниппетов
     *
     * @param int $id ID документа
     * @param bool $events Во время рендера документа стоит ли вызывать события OnLoadWebDocument и OnLoadDocumentObject (внутри метода getDocumentObject).
     * @param mixed $tpl Шаблон с которым необходимо отрендерить документ. Возможные значения:
     *                       null - Использовать шаблон который назначен документу
     *                       int(0-n) - Получить шаблон из базы данных с указанным ID и применить его к документу
     *                       string - Применить шаблон указанный в строке к документу
     * @return string
     *
     * Событие OnLoadWebDocument дополнительно передает параметры:
     *       - с источиком от куда произошел вызов события
     *       - оригинальный экземпляр класса Core
     */
    public function renderDoc ($id, $events = false, $tpl = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return '';
        }

        $m = clone $this->modx; //Чтобы была возможность вызывать события
        $m->documentIdentifier = $id;
        $m->documentObject = $m->getDocumentObject('id', (int)$id, $events ? 'prepareResponse' : null);
        if ($m->documentObject['type'] === 'reference') {
            if (is_numeric($m->documentObject['content']) && $m->documentObject['content'] > 0) {
                $m->documentObject['content'] = $this->renderDoc($m->documentObject['content'], $events);
            }
        }
        switch (true) {
            case is_integer($tpl):
                $tpl = $this->getTemplate($tpl);
                break;
            case is_string($tpl):
                break;
            case $tpl === null:
            default:
                $tpl = $this->getTemplate($m->documentObject['template']);
        }
        $m->documentContent = $tpl;
        if ($events) {
            $m->invokeEvent("OnLoadWebDocument", [
                'source'   => 'DLTemplate',
                'mainModx' => $this->modx,
            ]);
        }

        return $this->parseDocumentSource($m->documentContent, $m);
    }

    /**
     * Получить содержимое шаблона с определенным номером
     * @param int $id Номер шаблона
     * @return string HTML код шаблона
     */
    public function getTemplate ($id)
    {
        $tpl = null;
        $id = (int)$id;
        if ($id > 0) {
            $tpl = $this->modx->db->getValue(
                    'SELECT `content` FROM '.$this->modx->getFullTableName('site_templates').' WHERE `id` = '.$id
            );
        }
        if ($tpl === null) {
            $tpl = '[*content*]';
        }

        return $tpl;
    }

    /**
     * refactor $modx->parseChunk();
     *
     * @param string $name Template: chunk name || @CODE: template || @FILE: file with template
     * @param array $data paceholder
     * @param bool $parseDocumentSource render html template via Core::parseDocumentSource()
     * @return string html template with data without placeholders
     */
    public function parseChunk ($name, $data = [], $parseDocumentSource = false, $disablePHx = false)
    {
        $out = $this->getChunk($name);
        $blade = strpos($name, '@B_') === 0 && $this->bladeEnabled;
        switch (true) {
            case $blade:
                if (!empty($out)) {
                    $out = $out->with($this->getTemplateData($data))->render();
                }
                break;
            case is_array($data) && ($out != ''):
                if (preg_match("/\[\+[A-Z0-9\.\_\-]+\+\]/is", $out)) {
                    $item = $this->renameKeyArr($data, '[', ']', '+');
                    $out = str_replace(array_keys($item), array_values($item), $out);
                }
                if (!$disablePHx && preg_match("/:([^:=]+)(?:=`(.*?)`(?=:[^:=]+|$))?/is", $out)) {
                    if (is_null($this->phx) || !($this->phx instanceof Phx)) {
                        $this->phx = $this->createPHx(0, 1000);
                    }
                    $this->phx->placeholders = [];
                    $this->setPHxPlaceholders($data);
                    $out = $this->phx->Parse($out);
                }
                break;
        }
        if ($parseDocumentSource && !$blade) {
            $out = $this->parseDocumentSource($out);
        }

        return $out;
    }

    /**
     *
     * @param string|array $value
     * @param string $key
     * @param string $path
     */
    public function setPHxPlaceholders ($value = '', $key = '', $path = '')
    {
        $keypath = !empty($path) ? $path . "." . $key : $key;
        $this->phx->curPass = 0;
        if (is_array($value)) {
            foreach ($value as $subkey => $subval) {
                $this->setPHxPlaceholders($subval, $subkey, $keypath);
            }
        } else {
            $this->phx->setPHxVariable($keypath, $value);
        }
    }

    /**
     *
     */
    protected function loadBlade ()
    {
        try {
            $this->blade = clone $this->modx['view'];
        } catch (\Exception $exception) {
            $this->modx->messageQuit($exception->getMessage());
        }
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public function cleanPHx ($string)
    {
        preg_match_all('~\[(\+|\*|\()([^:\+\[\]]+)([^\[\]]*?)(\1|\))\]~s', $string, $matches);
        if ($matches[0]) {
            $string = str_replace($matches[0], '', $string);
        }

        return $string;
    }

    /**
     * @param int $debug
     * @param int $maxpass
     * @return Phx
     */
    public function createPHx ($debug = 0, $maxpass = 50)
    {
        return new Phx($this->modx, $debug, $maxpass);
    }

    /**
     * Переменовывание элементов массива
     *
     * @param array $data массив с данными
     * @param string $prefix префикс ключей
     * @param string $suffix суффикс ключей
     * @param string $sep разделитель суффиксов, префиксов и ключей массива
     * @return array массив с переименованными ключами
     */
    public function renameKeyArr ($data, $prefix = '', $suffix = '', $sep = '.')
    {
        return rename_key_arr($data, $prefix, $suffix, $sep);
    }

    /**
     * @param $out
     * @param Core|null $modx
     * @return mixed|string
     */
    public function parseDocumentSource ($out, $modx = null)
    {
        if (!is_object($modx)) {
            $modx = $this->modx;
        }
        $minParserPasses = $modx->minParserPasses;
        $maxParserPasses = $modx->maxParserPasses;

        $modx->minParserPasses = 2;
        $modx->maxParserPasses = 10;

        $site_status = $modx->getConfig('site_status');
        $modx->config['site_status'] = 0;

        for ($i = 1; $i <= $modx->maxParserPasses; $i++) {
            $html = $out;
            if (preg_match('/\[\!(.*)\!\]/us', $out)) {
                $out = str_replace(['[!', '!]'], ['[[', ']]'], $out);
            }
            if ($i <= $modx->minParserPasses || $out != $html) {
                $out = $modx->parseDocumentSource($out);
            } else {
                break;
            }
        }
        
        $out = $modx->rewriteUrls($out);
        $out = $this->cleanPHx($out);

        $modx->config['site_status'] = $site_status;

        $modx->minParserPasses = $minParserPasses;
        $modx->maxParserPasses = $maxParserPasses;

        return $out;
    }
}
