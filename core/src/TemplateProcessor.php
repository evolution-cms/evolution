<?php namespace EvolutionCMS;

use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Support\TemplateFileEngines;
use Illuminate\Support\Facades\Log;

class TemplateProcessor
{
    /** Template code lives in the database; files are not consulted. */
    public const SOURCE_DATABASE = 'db';

    /** Template code lives in a view file. */
    public const SOURCE_FILE = 'file';

    /**
     * @var Interfaces\CoreInterface
     */
    protected $core;


    public function __construct(Interfaces\CoreInterface $core)
    {
        $this->core = $core;
    }

    /**
     * Absolute path of the file the current document must render from, when its
     * template names one, or '' otherwise.
     *
     * Resolving by view name asks the view factory, which tries extensions in
     * registration order - so the last engine to boot would decide for every
     * template on the site. A template that recorded an engine when it was
     * saved gets that file instead, and only falls back to the factory when the
     * file it named is gone.
     *
     * @var string
     */
    protected $documentViewPath = '';

    public function getDocumentViewPath(): string
    {
        return $this->documentViewPath;
    }

    public function getBladeDocumentContent()
    {
        $this->documentViewPath = '';
        $template = false;
        $doc = $this->core->documentObject;
        if(isset($this->core->documentObject['templatealias']) && $this->core->documentObject['templatealias'] != ''){
            $templateAlias = $this->core->documentObject['templatealias'];
        }else {
            if($doc['template'] === 0) {
                $templateAlias = '_blank';
            } else {
                $tpl = SiteTemplate::select('templatealias')->find((int)$doc['template']);
                $templateAlias = (string)($tpl ? $tpl->templatealias : '');
                if ($templateAlias === '') {
                    $templateAlias = '_blank';
                }
            }
        }

        // "Database" is an answer, not a starting point: no view path is walked,
        // no extension is tried, and a file that happens to share the alias has
        // no say. It is also the cheapest branch on the page - the lookups
        // below are a filesystem probe per extension per view path.
        if ($this->templateSource($doc) === self::SOURCE_DATABASE) {
            return false;
        }

        $pinned = $this->pinnedTemplateFile($doc, $templateAlias);
        if ($pinned !== '') {
            $this->documentViewPath = $pinned;

            return $templateAlias;
        }

        switch (true) {
            case $this->core['view']->exists('tpl-' . $doc['template'] . '_doc-' . $doc['id']):
                $template = 'tpl-' . $doc['template'] . '_doc-' . $doc['id'];
                break;
            case $this->core['view']->exists('doc-' . $doc['id']):
                $template = 'doc-' . $doc['id'];
                break;
            case $this->core['view']->exists('tpl-' . $doc['template']):
                $template = 'tpl-' . $doc['template'];
                break;
            case $this->core['view']->exists($templateAlias):
                $namespace = trim($this->core->getConfig('ControllerNamespace') ?? '');
                if (!empty($namespace)) {
                    $baseClassName = $namespace . 'BaseController';
                    if (class_exists($baseClassName)) { //Проверяем есть ли Base класс
                        $classArray = explode('.', $templateAlias);
                        $classArray = array_map(
                            function ($item) {
                                return $this->setPsrClassNames($item);
                            },
                            $classArray
                        );
                        $classViewPart = implode('.', $classArray);
                        $className = str_replace('.', '\\', $classViewPart);
                        $className = $namespace . ucfirst($className) . 'Controller';
                        if (!class_exists(
                            $className
                        )) { //Проверяем есть ли контроллер по алиасу, если нет, то помещаем Base
                            $className = $baseClassName;
                        }
                        $controller = $this->core->make($className);
                        if (method_exists($controller, 'main')) {
                            $this->core->call([$controller, 'main']);
                        }
                    } else {
                        $this->core->logEvent(0, 3, $baseClassName . ' not exists!');
                    }
                }
                $template = $templateAlias;
                break;
            default:
                $content = $doc['template'] ? $this->core->documentContent : $doc['content'];
                if (!$content) {
                    $content = $doc['content'];
                }
                if (strpos($content, '@FILE:') === 0) {
                    $template = str_replace('@FILE:', '', trim($content));
                    if (!$this->core['view']->exists($template)) {
                        $this->core->documentObject['template'] = 0;
                        $this->core->documentContent = $doc['content'];
                        // Returning the name of a view that is not there sends
                        // the caller to $view->make() anyway, which throws:
                        // "View [x] not found", a 500 where this branch was
                        // written to degrade instead.
                        $template = false;
                    }
                }
        }
        return $template;
    }

    /**
     * Where the current document's template says its code lives.
     *
     * '' is every template that predates the setting, and means "decide the old
     * way": a matching file wins if one happens to exist.
     */
    private function templateSource(array $doc): string
    {
        $row = $this->templateRow((int) get_by_key($doc, 'template', 0));

        return (string) ($row->templatesource ?? '');
    }

    /** @var array<int, SiteTemplate|null> */
    private array $templateRows = [];

    /** The template row, read once: two columns are wanted at different points. */
    private function templateRow(int $templateId): ?SiteTemplate
    {
        if ($templateId === 0) {
            return null;
        }

        if (!array_key_exists($templateId, $this->templateRows)) {
            $this->templateRows[$templateId] = SiteTemplate::whereKey($templateId)
                ->first(['id', 'templatesource', 'templatefileextension']);
        }

        return $this->templateRows[$templateId];
    }

    /**
     * The file a template pinned to an engine when it was saved, if that file is
     * still there.
     *
     * The document specific views (tpl-N_doc-M, doc-M, tpl-N) are deliberately
     * not overridden: those are per document overrides of the template, and a
     * template pinning its own engine says nothing about them.
     */
    private function pinnedTemplateFile(array $doc, string $templateAlias): string
    {
        if ($templateAlias === '') {
            return '';
        }

        foreach (['tpl-' . get_by_key($doc, 'template') . '_doc-' . get_by_key($doc, 'id'),
                     'doc-' . get_by_key($doc, 'id'),
                     'tpl-' . get_by_key($doc, 'template')] as $override) {
            if ($this->core['view']->exists($override)) {
                return '';
            }
        }

        $row = $this->templateRow((int) get_by_key($doc, 'template', 0));

        $extension = (string) ($row->templatefileextension ?? '');
        if ($extension === '') {
            return '';
        }

        return (string) (TemplateFileEngines::make()->pathFor($templateAlias, $extension) ?? '');
    }

    /**
     * @param $templateID
     * @return mixed
     */
    public function getTemplateCodeFromDB($templateID)
    {
        $templateId = (int)$templateID;
        $tpl = SiteTemplate::query()->find($templateId);
        if ($tpl) {
            return (string)$tpl->content;
        }

        Log::warning('Missing SiteTemplate. Inline template fallback was used.', [
            'template_id' => $templateId,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        ]);

        return $this->inlineTemplate($templateId);
    }

    private function inlineTemplate(?int $expectedId = null): string
    {
        $msg = $expectedId ? "Expected template ID={$expectedId} is missing." : "Template is missing.";
        return '<!doctype html><html><head><meta charset="utf-8"><title>Template missing</title></head><body>'
            . '<h1>Template missing</h1>'
            . '<p>' . htmlspecialchars($msg, ENT_QUOTES) . '</p>'
            . '<p>Please assign a valid template in Manager (System Configuration / document template).</p>'
            . '<hr><div>[*content*]</div>'
            . '</body></html>';
    }

    /**
     * @param string $templateAlias
     * @return string
     */
    private function setPsrClassNames(string $templateAlias): string
    {
        $explodedTplName = explode('_', $templateAlias);
        $explodedTplName = array_map(
            function ($item) {
                return ucfirst(trim($item));
            },
            $explodedTplName
        );

        return join($explodedTplName);
    }
}
