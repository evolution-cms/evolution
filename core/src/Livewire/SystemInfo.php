<?php

namespace EvolutionCMS\Livewire;

use EvolutionCMS\Controllers\SystemInfo as SystemInfoController;
use EvolutionCMS\Interfaces\ManagerThemeInterface;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SystemInfo extends Component
{
    #[Locked]
    public bool $phpInfoOpen = false;

    #[Locked]
    public string $phpInfoTheme = 'evodark';

    public function mount(): void
    {
        $this->controller();
    }

    public function openPhpInfo(?string $theme = null): void
    {
        $this->controller();
        if (!function_exists('phpinfo')) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        $this->phpInfoTheme = in_array($theme, ['evolight', 'evolightness', 'evodark', 'evodarkness'], true)
            ? $theme
            : app(\EvoUI\Support\ManagerContext::class)->theme();
        $this->phpInfoOpen = true;
    }

    public function closeModal(): void
    {
        $this->controller();
        $this->phpInfoOpen = false;
    }

    public function resetOpcache(): void
    {
        $result = $this->controller()->resetOpcache();
        $this->dispatch('evo-system-info-opcache-reset',
            message: \EvolutionCMS\Facades\ManagerTheme::getLexicon($result),
            successful: $result === 'opcache_reset_complete'
        );
    }

    protected function controller(): SystemInfoController
    {
        $controller = new SystemInfoController(app(ManagerThemeInterface::class));
        if (!$controller->canView()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403);
        }
        return $controller;
    }

    protected function phpInfoDocument(): string
    {
        ob_start();
        try {
            phpinfo();
            $document = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $body = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $document);
        return view()->file(
            EVO_MANAGER_PATH . 'media/style/evo-ui/views/partials/phpinfo-document.blade.php',
            ['content' => new \Illuminate\Support\HtmlString($body), 'theme' => $this->phpInfoTheme]
        )->render();
    }

    public function render()
    {
        // Keep the document out of public Livewire state/snapshots. It is built
        // only after an authorised open action, inside an isolated iframe.
        return view()->file(
            EVO_MANAGER_PATH . 'media/style/evo-ui/views/livewire/system-info.blade.php',
            $this->controller()->getReadOnlyParameters() + [
                'phpInfo' => $this->phpInfoOpen ? $this->phpInfoDocument() : '',
            ]
        );
    }
}
