@php
    $menuTree = (new \EvolutionCMS\Support\ManagerMenu())->tree($menuItems, fn ($permission) => (bool) evo()->hasPermission($permission));
    $topbarTheme = app(\EvoUI\Support\ManagerContext::class)->theme();
@endphp
<div id="mainMenu" class="evo-manager-topbar" data-evo-topbar data-theme="{{ $topbarTheme }}">
    <div class="evo-manager-topbar__bar">
        <nav aria-label="{{ ManagerTheme::getLexicon('home') }}">
            <ul id="nav" class="evo-manager-menu">
                @include('manager::frame.topbar-items', ['nodes' => $menuTree])
            </ul>
        </nav>
        @include('manager::frame.topbar-actions')
    </div>
</div>
<dialog id="evo-menu-cache-confirm" class="evo-manager-dialog" aria-labelledby="evo-menu-cache-title" data-theme="{{ $topbarTheme }}">
    <h2 id="evo-menu-cache-title">{{ ManagerTheme::getLexicon('refresh_site') }}</h2>
    <p>{{ ManagerTheme::getLexicon('refresh_title') }}</p>
    <div class="evo-manager-dialog__actions">
        <x-evo::button data-evo-cache-cancel :label="ManagerTheme::getLexicon('cancel')" />
        <x-evo::button data-evo-cache-confirm tone="warning" variant="filled" :label="ManagerTheme::getLexicon('refresh_site')" />
    </div>
</dialog>
