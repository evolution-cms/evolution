@php
    $menuTree = (new \EvolutionCMS\Support\ManagerMenu())->tree($menuItems, fn ($permission) => (bool) evo()->hasPermission($permission));
    $topbarTheme = app(\EvoUI\Support\ManagerContext::class)->theme();
@endphp
<div id="mainMenu" class="evo-manager-topbar" data-evo-topbar data-theme="{{ $topbarTheme }}"
    data-evo-menu-error="{{ ManagerTheme::getLexicon('error') }}">
    <div class="evo-manager-topbar__bar">
        <nav aria-label="{{ ManagerTheme::getLexicon('home') }}">
            <ul id="nav" class="evo-manager-menu">
                @include('manager::frame.topbar-items', ['nodes' => $menuTree])
            </ul>
        </nav>
        @include('manager::frame.topbar-actions', ['nativeTopbar' => true])
    </div>
</div>
<div id="evo-menu-cache-notice" class="evo-manager-cache-notice" role="status" aria-live="polite" data-theme="{{ $topbarTheme }}" hidden>
    <x-evo::icon name="refresh" class="evo-manager-cache-notice__icon" />
    <span data-evo-cache-message></span>
    <button type="button" data-evo-cache-dismiss aria-label="{{ ManagerTheme::getLexicon('close') }}"><x-evo::icon name="x" /></button>
</div>
