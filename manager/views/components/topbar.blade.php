@props([
    'menuItems',
    'user',
    'icons',
])

@php
    $menuGrouped = [];
    $menuById = [];
    $menuIndex = 0;
    foreach ($menuItems as $item) {
        $item['_index'] = $menuIndex;
        $menuIndex++;
        $menuGrouped[$item[1]][] = $item;
        $menuById[$item[0]] = $item;
    }
    foreach ($menuGrouped as &$items) {
        usort($items, static function ($a, $b) {
            $byIndex = ($a[9] ?? 0) <=> ($b[9] ?? 0);
            if ($byIndex !== 0) {
                return $byIndex;
            }
            return ($a['_index'] ?? 0) <=> ($b['_index'] ?? 0);
        });
    }
    unset($items);

    $renderMenu = function ($parentId, $level = 0, $skip = []) use (&$renderMenu, $menuGrouped) {
        if (!isset($menuGrouped[$parentId])) {
            return '';
        }

        $html = '';
        $togglePatterns = [
            '~<svg[^>]*\\bclass=[\"\'][^\"\']*\\btoggle\\b[^\"\']*[\"\'][^>]*>.*?</svg>~is',
            '~<i[^>]*\\bclass=[\"\'][^\"\']*\\btoggle\\b[^\"\']*[\"\'][^>]*>.*?</i>~is',
            '~<span[^>]*\\bclass=[\"\'][^\"\']*\\btoggle\\b[^\"\']*[\"\'][^>]*>.*?</span>~is',
        ];

        foreach ($menuGrouped[$parentId] as $item) {
            $id = (string) ($item[0] ?? '');
            if ($id !== '' && in_array($id, $skip, true)) {
                continue;
            }
            $title = (string) ($item[4] ?? '');
            $href = (string) ($item[3] ?? 'javascript:;');
            $onclick = trim((string) ($item[5] ?? ''));
            $target = (string) ($item[7] ?? '');
            $itemName = (string) ($item[2] ?? '');
            $extraClass = trim((string) ($item[10] ?? ''));

            $hasChildren = isset($menuGrouped[$id]);
            $liClass = trim(($hasChildren ? 'dropdown ' : '') . $extraClass);
            if ($level > 0) {
                $liClass = trim($liClass . ' w-full');
            }
            $liAttrs = ($id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '')
                . ($liClass !== '' ? ' class="' . htmlspecialchars($liClass, ENT_QUOTES, 'UTF-8') . '"' : '');

            if ($hasChildren) {
                $summaryAttrs = $title !== '' ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
                $summaryClass = $level > 0
                    ? ' class="flex items-center gap-2 w-full list-none pr-1 whitespace-nowrap [&::after]:hidden"'
                    : ' class="flex items-center gap-2 w-full list-none pr-1 [&::after]:hidden"';
                $caretIcon = $level > 0
                    ? svg('tabler-chevron-right', 'h-3.5 w-3.5 text-base-content/70')->toHtml()
                    : '';
                $caret = $level > 0
                    ? '<span class="menu-caret ml-auto pl-2 shrink-0 opacity-70" data-menu-caret aria-hidden="true">' . $caretIcon . '</span>'
                    : '';
                $label = preg_replace($togglePatterns, '', $itemName);
                $summaryTitle = trim(preg_replace('/\\s+/', ' ', strip_tags($label)));
                $summaryData = '';
                $summaryData .= ' data-menu-level="' . $level . '"';
                if ($href !== '') {
                    $summaryData .= ' data-menu-href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
                }
                if ($target !== '') {
                    $summaryData .= ' data-menu-target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
                }
                if ($onclick !== '') {
                    $summaryData .= ' data-menu-onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"';
                }
                if ($summaryTitle !== '') {
                    $summaryData .= ' data-menu-title="' . htmlspecialchars($summaryTitle, ENT_QUOTES, 'UTF-8') . '"';
                }
                $labelAttr = $level > 0 ? ' data-menu-link' : '';
                $summaryInner = '<span class="flex items-center gap-2 flex-1"' . $labelAttr . '>' . $label . '</span>' . $caret;
                $childHtml = $renderMenu($id, $level + 1, $skip);
                $submenuClass = 'menu menu-sm bg-base-300 rounded-box w-max !w-max min-w-max max-w-none !max-w-none p-2 mt-3 shadow';
                $html .= '<li' . $liAttrs . '><details><summary' . $summaryAttrs . $summaryClass . $summaryData . '>'
                    . $summaryInner . '</summary><ul class="' . $submenuClass . '">' . $childHtml . '</ul></details></li>';
                continue;
            }

            $itemName = preg_replace($togglePatterns, '', $itemName);
            $linkAttrs = '';
            if ($href !== '') {
                $linkAttrs .= ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($title !== '') {
                $linkAttrs .= ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($target !== '') {
                $linkAttrs .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
            }
            if ($onclick !== '') {
                $linkAttrs .= ' onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"';
            }

            $optionalHtml = '';
            if (isset($item[11]) && !empty($item[11])) {
                $optionalItems = $item[11];
                if (is_array($optionalItems)) {
                    if (!array_is_list($optionalItems)) {
                        $optionalItems = array_values($optionalItems);
                    }
                } else {
                    $optionalItems = [$optionalItems];
                }

                foreach ($optionalItems as $opt) {
                    if (!is_array($opt) || count($opt) < 6) {
                        continue;
                    }
                    [$tag, $optHref, $optClass, $optOnclick, $optTitle, $optInner] = $opt;
                    $tag = in_array($tag, ['a', 'button'], true) ? $tag : 'a';
                    $btnClass = $id === 'refresh_site'
                        ? trim('btn btn-warning btn-xs px-1.5 h-5 min-h-0 ml-2 ' . $optClass)
                        : trim('btn btn-ghost btn-xs px-1.5 h-5 min-h-0 ml-2 ' . $optClass);
                    $btnAttrs = ' class="' . htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8') . '"';
                    $optHref = $id === 'refresh_site' ? 'javascript:;' : $optHref;
                    $optOnclick = $id === 'refresh_site'
                        ? "document.getElementById('clearCacheModal').showModal(); return false;"
                        : $optOnclick;
                    if ($tag === 'a') {
                        $btnAttrs .= ' href="' . htmlspecialchars($optHref, ENT_QUOTES, 'UTF-8') . '"';
                    }
                    if (!empty($optTitle)) {
                        $btnAttrs .= ' title="' . htmlspecialchars($optTitle, ENT_QUOTES, 'UTF-8') . '"';
                    }
                    if (!empty($optOnclick)) {
                        $btnAttrs .= ' onclick="' . htmlspecialchars($optOnclick, ENT_QUOTES, 'UTF-8') . '"';
                    }
                    $optionalHtml .= '<' . $tag . $btnAttrs . '>' . $optInner . '</' . $tag . '>';
                }
            }

            $needsToggle = $level > 0 && strpos($extraClass, 'dropdown-toggle') !== false;
            $toggleHtml = $needsToggle
                ? '<span class="menu-caret ml-auto pl-2 shrink-0 opacity-70" data-menu-caret aria-hidden="true">' . svg('tabler-chevron-right', 'h-3.5 w-3.5 text-base-content/70')->toHtml() . '</span>'
                : '';

            if ($optionalHtml !== '') {
                $liClass = trim($liClass);
                $liAttrs = ($id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '')
                    . ($liClass !== '' ? ' class="' . htmlspecialchars($liClass, ENT_QUOTES, 'UTF-8') . '"' : '');
                $linkClass = $level > 0 ? 'w-full whitespace-nowrap' : 'flex-1';
                if ($needsToggle) {
                    $linkClass = trim('flex items-center gap-2 w-full ' . $linkClass);
                }
                $linkAttrs .= ' class="' . $linkClass . '"';
                $wrapperClass = $level > 0 ? 'flex items-center gap-2 w-full' : 'flex items-center gap-2 w-full';
                $html .= '<li' . $liAttrs . '><div class="' . $wrapperClass . '">'
                    . '<a' . $linkAttrs . '><span class="flex items-center gap-2 flex-1">' . $itemName . '</span>' . $toggleHtml . '</a>' . $optionalHtml . '</div></li>';
            } else {
                $linkClass = $level > 0 ? 'w-full whitespace-nowrap' : '';
                if ($needsToggle) {
                    $linkClass = trim('flex items-center gap-2 w-full ' . $linkClass);
                }
                if ($linkClass !== '') {
                    $linkAttrs = rtrim($linkAttrs) . ' class="' . $linkClass . '"';
                }
                $html .= '<li' . $liAttrs . '><a' . $linkAttrs . '><span class="flex items-center gap-2 flex-1">' . $itemName . '</span>' . $toggleHtml . '</a></li>';
            }
        }

        return $html;
    };

    $barsItem = $menuById['bars'] ?? null;
    $siteItem = $menuById['site'] ?? null;
    $barsTitle = $barsItem[4] ?? ManagerTheme::getLexicon('home');
    $barsOnclick = $barsItem[5] ?? 'modx.resizer.toggle(); return false;';
    $siteHref = $siteItem[3] ?? 'index.php?a=2';
    $siteTitle = $siteItem[4] ?? ManagerTheme::getLexicon('home');
    $siteTarget = $siteItem[7] ?? 'main';

    $lightThemes = [
        'light',
        'cupcake',
        'bumblebee',
        'emerald',
        'corporate',
        'retro',
        'cyberpunk',
        'valentine',
        'garden',
        'lofi',
        'pastel',
        'fantasy',
        'wireframe',
        'cmyk',
        'autumn',
        'acid',
        'lemonade',
        'winter',
        'caramellatte',
        'nord',
        'silk',
    ];
    $darkThemes = [
        'dark',
        'synthwave',
        'halloween',
        'forest',
        'aqua',
        'black',
        'luxury',
        'dracula',
        'business',
        'night',
        'coffee',
        'dim',
        'sunset',
        'abyss',
    ];
    sort($lightThemes, SORT_NATURAL | SORT_FLAG_CASE);
    sort($darkThemes, SORT_NATURAL | SORT_FLAG_CASE);
    $themes = array_values(array_unique(array_merge($lightThemes, $darkThemes)));
@endphp

<div
    id="mainMenu"
    data-evo-themes='@json($themes)'
    data-evo-themes-light='@json($lightThemes)'
    data-evo-themes-dark='@json($darkThemes)'
    data-evo-theme-default="light"
    data-evo-mode-default="light"
>
    <x-mary-nav full-width class="bg-base-200 shadow-sm">
        <x-slot:brand>
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <ul id="nav" class="nav menu menu-horizontal px-0 items-center">
                        <li id="bars">
                            <x-mary-button
                                class="btn-ghost btn-sm px-2"
                                link="javascript:;"
                                onclick="{{ $barsOnclick }}"
                                title="{{ $barsTitle }}"
                            >
                                <span class="icon-expand">
                                    <x-tabler-layout-sidebar-left-expand class="h-5 w-5" />
                                </span>
                                <span class="icon-collapse">
                                    <x-tabler-layout-sidebar-left-collapse class="h-5 w-5" />
                                </span>
                            </x-mary-button>
                        </li>
                        <li id="site">
                            <x-mary-button
                                class="btn-ghost btn-sm px-2"
                                link="{{ $siteHref }}"
                                target="{{ $siteTarget }}"
                                title="{{ $siteTitle }}"
                            >
                                <svg class="h-7 w-auto text-base-content" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 90.2 85" aria-hidden="true">
                                    <path class="fill-[#80aadc]" d="M90.2 36.1C90.2 16.2 74 0 54.1 0c-3.1 0-6.1.4-9 1.1 5.5 1.4 10.5 4.1 14.7 7.7 1.8.9 3.5 1.9 5.1 3.1C76.3 18 84 30 84 43.8c0 6.7-1.8 13-5.1 18.4 7-6.5 11.3-15.8 11.3-26.1"/>
                                    <path class="fill-[#acc65d]" d="M11.4 25.3C15.7 16.5 23.5 9.7 33 6.8c3.6-2.6 7.6-4.5 12-5.6C42.1.5 39.1.1 36 .1 16.2 0 0 16.2 0 36.1c0 4.7.9 9.3 2.6 13.4V49c0-9.1 3.3-17.4 8.8-23.7"/>
                                    <path class="fill-[#729d4d]" d="M43.8 5.2c5.7 0 11.1 1.3 15.9 3.7-4.2-3.6-9.1-6.3-14.7-7.7-4.4 1.1-8.4 3.1-12 5.6 3.5-1.1 7.1-1.6 10.8-1.6"/>
                                    <path class="fill-[#8bb756]" d="M26.8 73.1c-2.4-1.3-4.7-2.8-6.7-4.6-7.9-4-14.2-10.7-17.5-19C2.9 69.2 18.9 85 38.7 85c8.3 0 15.9-2.8 21.9-7.4-3.9 1.5-8.2 2.3-12.7 2.3-7.8 0-15.1-2.5-21.1-6.8"/>
                                    <path class="fill-[#729d4d]" d="M20.1 68.4C12.5 61.8 7.7 52.1 7.7 41.2c0-5.7 1.3-11.1 3.7-15.9C5.9 31.6 2.6 39.9 2.6 49v.5c3.3 8.3 9.6 15 17.5 18.9"/>
                                    <path class="fill-[#ffd700]" d="M64.9 12c9.1 6.6 15 17.2 15 29.3 0 9.1-3.3 17.3-8.9 23.7-1.8 2.1-3.9 4-6.2 5.6-5.9 4.3-13.2 6.8-21 6.8-6.1 0-11.9-1.5-17-4.2C32.7 77.5 40 80 47.9 80c4.5 0 8.7-.8 12.7-2.3 7.7-2.9 14.2-8.4 18.4-15.4 3.2-5.4 5.1-11.7 5.1-18.4C84 30 76.3 18 64.9 12"/>
                                    <path class="fill-current" d="M31.15,35.32C31.37,35.32,31.9064,35.2048,31.9,34.59C31.8936,33.9752,31.37,33.84,31.15,33.84L21.87,33.84C21.65,33.84,21.1198,33.9726,21.12,34.59L21.12,50.62C21.1272,51.2508,21.65,51.37,21.87,51.37L31.15,51.37C31.37,51.37,31.893,51.2102,31.9,50.64C31.907,50.0698,31.37,49.88,31.15,49.88L22.7,49.88L22.7,43.07L30.01,43.07C30.23,43.07,30.7665,42.9409,30.77,42.34C30.7735,41.7391,30.23,41.59,30.01,41.59L22.7,41.59L22.7,35.3L31.15,35.32z"/>
                                    <path class="fill-current" d="M42.2,48.8L36.11,34.37C35.96,34,35.5591,33.6413,35.0014,33.9076C34.4436,34.1739,34.64,34.85,34.69,34.95L41.42,50.91C41.49,51.06,41.7084,51.3964,42.13,51.39C42.5516,51.3836,42.8,51.04,42.86,50.89L49.61,34.91C49.66,34.78,49.8602,34.204,49.2657,33.9111C48.6711,33.6182,48.28,34.18,48.19,34.33L42.2,48.81L42.2,48.8z"/>
                                    <path class="fill-current" d="M55.34,50.47C59.0451,52.677,62.8,51.25,64.09,50.47C67.7101,48.2261,68.19,44.34,68.19,42.62C68.19,40.9,67.7218,36.8903,64.09,34.75C60.4582,32.6097,56.62,33.98,55.34,34.75C54.06,35.52,51.1824,37.8711,51.24,42.62C51.2608,44.3335,51.6349,48.263,55.34,50.47zM56.16,36.05C59.2342,34.2733,62.23,35.42,63.26,36.05C64.7672,37.1609,66.5437,38.7201,66.56,42.62C66.5763,46.5199,64.3,48.55,63.26,49.19C59.9657,50.9725,57.19,49.82,56.16,49.19C55.3163,48.6515,52.782,46.8951,52.86,42.62C52.938,38.3449,55.12,36.69,56.16,36.05z"/>
                                </svg>
                            </x-mary-button>
                        </li>
                        {!! $renderMenu('main', 0, ['bars', 'site']) !!}
                    </ul>
                </div>
            </div>
        </x-slot:brand>
        <x-slot:actions class="ml-auto">
            <div class="flex items-center gap-2">
                <div id="searchform" class="relative">
                    <form action="index.php?a=71" method="post" target="main" class="flex items-center gap-2">
                        <input type="hidden" value="Search" name="submitok" />
                        <label for="searchid" class="btn btn-ghost btn-sm">
                            {!! $icons['icon_search'] !!}
                        </label>
                        <input type="text" id="searchid" name="searchid" class="input input-sm w-56" />
                        <span class="mask"></span>
                    </form>
                </div>
                @if (evo()->getConfig('show_newresource_btn') && evo()->hasPermission('new_document'))
                    <details class="dropdown dropdown-bottom dropdown-end">
                        <summary id="newresource" class="btn btn-ghost btn-sm" title="{{ ManagerTheme::getLexicon('add_resource') }}">
                            {!! $icons['icon_add'] !!}
                        </summary>
                        <ul class="menu menu-sm bg-base-300 rounded-box w-max min-w-max p-2 mt-3 shadow dropdown-content right-0">
                            @if (evo()->hasPermission('new_document'))
                                <li>
                                    <a href="index.php?a=4" target="main">
                                        {!! $icons['icon_add'] !!} {{ ManagerTheme::getLexicon('add_resource') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="index.php?a=72" target="main">
                                        {!! iconHtml($icons['icon_chain']) !!} {{ ManagerTheme::getLexicon('add_weblink') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))
                                <li>
                                    <a href="media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?&type=images" target="main">
                                        {!! $icons['icon_camera'] !!} {{ ManagerTheme::getLexicon('images_management') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))
                                <li>
                                    <a href="media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?&type=files" target="main">
                                        {!! $icons['icon_files'] !!} {{ ManagerTheme::getLexicon('files_management') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </details>
                @endif
                <x-mary-button id="preview" class="btn-ghost btn-sm" link="../" external title="{{ ManagerTheme::getLexicon('preview') }}">
                    {!! $icons['icon_desktop'] !!}
                </x-mary-button>
                <details class="dropdown dropdown-bottom dropdown-end">
                    <summary id="account" class="btn btn-ghost btn-sm gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-base-200 text-base-content">
                            <x-tabler-user class="h-4 w-4" />
                        </span>
                        <span class="text-sm">{{ entities($user['username'], evo()->getConfig('modx_charset')) }}</span>
                    </summary>
                    <ul class="menu menu-sm bg-base-300 rounded-box w-max min-w-max p-2 mt-3 shadow dropdown-content right-0">
                        @if (evo()->hasPermission('change_password'))
                            <li>
                                <a href="index.php?a=28" target="main">
                                    {!! $icons['icon_lock'] !!} {{ ManagerTheme::getLexicon('change_password') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="index.php?a=8">
                                {!! $icons['icon_logout'] !!} {{ ManagerTheme::getLexicon('logout') }}
                            </a>
                        </li>
                    </ul>
                </details>
                <x-mary-button
                    id="theme"
                    class="btn-ghost btn-sm"
                    data-evo-theme-toggle
                    title="{{ ManagerTheme::getLexicon('manager_theme_mode_title') }}"
                >
                    <span data-evo-icon="moon" style="display:none;">
                        <x-tabler-moon class="swap-on h-5 w-5" />
                    </span>
                    <span data-evo-icon="sun">
                        <x-tabler-sun class="swap-off h-5 w-5" />
                    </span>
                </x-mary-button>
                <details class="dropdown dropdown-bottom dropdown-end">
                    <summary id="themeMenu" class="btn btn-ghost btn-sm" title="{{ ManagerTheme::getLexicon('manager_theme_mode_title') }}">
                        <x-tabler-color-swatch class="h-5 w-5" />
                    </summary>
                    <ul class="menu menu-sm bg-base-300 rounded-box z-1 mt-3 w-max min-w-max p-2 shadow-2xl max-h-[400px] overflow-y-auto overflow-x-hidden flex-col flex-nowrap dropdown-content right-0">
                        @foreach ($lightThemes as $theme)
                            <li data-theme-group="light">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-ghost justify-start gap-3 px-2 w-full theme-item"
                                    data-evo-theme="{{ $theme }}"
                                    data-theme-item="{{ $theme }}"
                                >
                                    <div class="grid shrink-0 grid-cols-2 grid-rows-2 gap-0.5 rounded-md p-1 shadow-sm bg-base-100" data-theme="{{ $theme }}">
                                        <span class="block h-2 w-2 rounded-full bg-primary"></span>
                                        <span class="block h-2 w-2 rounded-full bg-secondary"></span>
                                        <span class="block h-2 w-2 rounded-full bg-accent"></span>
                                        <span class="block h-2 w-2 rounded-full bg-neutral"></span>
                                    </div>
                                    <span class="w-32 truncate text-left">{{ $theme }}</span>
                                </button>
                            </li>
                        @endforeach
                        @foreach ($darkThemes as $theme)
                            <li data-theme-group="dark">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-ghost justify-start gap-3 px-2 w-full theme-item"
                                    data-evo-theme="{{ $theme }}"
                                    data-theme-item="{{ $theme }}"
                                >
                                    <div class="grid shrink-0 grid-cols-2 grid-rows-2 gap-0.5 rounded-md p-1 shadow-sm bg-base-100" data-theme="{{ $theme }}">
                                        <span class="block h-2 w-2 rounded-full bg-primary"></span>
                                        <span class="block h-2 w-2 rounded-full bg-secondary"></span>
                                        <span class="block h-2 w-2 rounded-full bg-accent"></span>
                                        <span class="block h-2 w-2 rounded-full bg-neutral"></span>
                                    </div>
                                    <span class="w-32 truncate text-left">{{ $theme }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </details>
                @if (
                    evo()->hasPermission('settings') ||
                    evo()->hasPermission('view_eventlog') ||
                    evo()->hasPermission('logs') ||
                    evo()->hasPermission('help')
                )
                    <details class="dropdown dropdown-bottom dropdown-end">
                        <summary id="system" class="btn btn-ghost btn-sm" title="{{ ManagerTheme::getLexicon('system') }}">
                            {!! $icons['icon_cogs'] !!}
                        </summary>
                        <ul class="menu menu-sm bg-base-300 rounded-box w-max min-w-max p-2 mt-3 shadow dropdown-content right-0">
                            @if (evo()->hasPermission('settings'))
                                <li>
                                    <a href="index.php?a=17" target="main">
                                        {!! $icons['icon_sliders'] !!} {{ ManagerTheme::getLexicon('edit_settings') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->hasPermission('view_eventlog'))
                                <li>
                                    <a href="index.php?a=70" target="main">
                                        {!! $icons['icon_calendar'] !!} {{ ManagerTheme::getLexicon('site_schedule') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->hasPermission('view_eventlog'))
                                <li>
                                    <a href="index.php?a=114" target="main">
                                        {!! $icons['icon_info_triangle'] !!} {{ ManagerTheme::getLexicon('eventlog_viewer') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->hasPermission('logs'))
                                <li>
                                    <a href="index.php?a=13" target="main">
                                        {!! $icons['icon_user_secret'] !!} {{ ManagerTheme::getLexicon('view_logging') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="index.php?a=53" target="main">
                                        {!! $icons['icon_info_circle'] !!} {{ ManagerTheme::getLexicon('view_sysinfo') }}
                                    </a>
                                </li>
                            @endif
                            @if (evo()->hasPermission('help'))
                                <li>
                                    <a href="index.php?a=9" target="main">
                                        {!! $icons['icon_question_circle'] !!} {{ ManagerTheme::getLexicon('help') }}
                                    </a>
                                </li>
                            @endif
                            @php
                                $style = evo()->getConfig('settings_version') !== evo()->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
                                echo '<li><span class="block w-full text-xs text-right opacity-70 px-2 py-1" title="' . evo()->getPhpCompat()->entities(evo()->getConfig('site_name')) . ' &ndash; ' . evo()->getVersionData('full_appname') . '" ' . $style . '>' . evo()->getVersionData('branch') . ' ' . evo()->getConfig('settings_version') . '</span></li>';
                            @endphp
                        </ul>
                    </details>
                @endif
                @if (evo()->getConfig('show_fullscreen_btn'))
                    <x-mary-button id="fullscreen" class="btn-ghost btn-sm" onclick="toggleFullScreen();" title="{{ ManagerTheme::getLexicon('toggle_fullscreen') }}">
                        {!! $icons['icon_expand'] !!}
                    </x-mary-button>
                @endif
            </div>
        </x-slot:actions>
    </x-mary-nav>

    @php
        $refreshTitle = ManagerTheme::getLexicon('refresh_site');
        $refreshTitleJs = json_encode($refreshTitle, JSON_UNESCAPED_UNICODE);
        $refreshAction = "if (window.modx && modx.config && modx.config.global_tabs && typeof modx.tabs === 'function') { modx.tabs({ url: 'index.php?a=26', title: " . $refreshTitleJs . " }); } else if (window.main && window.main !== window) { window.main.location.href = 'index.php?a=26'; } else { window.location.href = 'index.php?a=26'; } document.getElementById('clearCacheModal').close();";
    @endphp
    <x-mary-modal id="clearCacheModal" :title="$refreshTitle" class="backdrop-blur">
        <p class="text-sm opacity-80">{{ ManagerTheme::getLexicon('refresh_title') }}</p>

        <x-slot:actions>
            <form method="dialog">
                <x-mary-button class="btn-ghost" type="submit" label="{{ ManagerTheme::getLexicon('cancel') }}" />
            </form>
            <x-mary-button class="btn-warning" label="{{ $refreshTitle }}" onclick="{{ $refreshAction }}" />
        </x-slot:actions>
    </x-mary-modal>
</div>
