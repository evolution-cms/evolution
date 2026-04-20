<?php namespace EvolutionCMS;

use EvolutionCMS\Models\SiteTemplate;
use Illuminate\Support\Facades\Log;

class TemplateProcessor
{
    /**
     * @var Interfaces\CoreInterface
     */
    protected $core;


    public function __construct(Interfaces\CoreInterface $core)
    {
        $this->core = $core;
    }

    public function getBladeDocumentContent()
    {
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
                    }
                }
        }
        return $template;
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
