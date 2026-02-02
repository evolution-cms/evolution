@extends('manager::template.page')
@section('content')
@php
    unset($_SESSION['itemname'], $_SESSION['itemaction']);

    if (evo()->hasPermission('settings') && evo()->getConfig('settings_version') !== evo()->getVersionData('version')) {
        exit('<script type="text/javascript">document.location.href="index.php?a=17";</script>');
    }

    $_style = EvolutionCMS\Facades\ManagerTheme::getStyle();
    $which_browser = evo()->getConfig('which_browser');

    $icons = [
        'home' => svg('tabler-home')->toHtml(),
        'users' => svg('tabler-users')->toHtml(),
        'pencil' => svg('tabler-pencil')->toHtml(),
        'rss' => svg('tabler-rss')->toHtml(),
        'alert' => svg('tabler-alert-triangle')->toHtml(),
        'add' => svg('tabler-file-plus')->toHtml(),
        'link' => svg('tabler-link')->toHtml(),
        'camera' => svg('tabler-camera')->toHtml(),
        'folder' => svg('tabler-folder-open')->toHtml(),
        'database' => svg('tabler-database')->toHtml(),
        'lock' => svg('tabler-lock')->toHtml(),
        'logout' => svg('tabler-logout')->toHtml(),
        'edit' => svg('tabler-file-pencil')->toHtml(),
        'eye' => svg('tabler-eye')->toHtml(),
        'trash' => svg('tabler-trash')->toHtml(),
        'undo' => svg('tabler-arrow-back-up')->toHtml(),
        'check' => svg('tabler-check')->toHtml(),
        'close' => svg('tabler-x')->toHtml(),
        'info' => svg('tabler-info-square-rounded')->toHtml(),
        'world' => svg('tabler-world')->toHtml(),
    ];

    $quickActions = [];
    if (evo()->hasPermission('new_document')) {
        $quickActions[] = ['label' => $_lang['add_resource'], 'href' => 'index.php?a=4', 'target' => 'main', 'icon' => $icons['add']];
        $quickActions[] = ['label' => $_lang['add_weblink'], 'href' => 'index.php?a=72', 'target' => 'main', 'icon' => $icons['link']];
    }
    if (evo()->hasPermission('assets_images')) {
        $quickActions[] = [
            'label' => $_lang['images_management'],
            'href' => 'media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=images',
            'target' => 'main',
            'icon' => $icons['camera']
        ];
    }
    if (evo()->hasPermission('assets_files')) {
        $quickActions[] = [
            'label' => $_lang['files_management'],
            'href' => 'media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=files',
            'target' => 'main',
            'icon' => $icons['folder']
        ];
    }
    if (evo()->hasPermission('bk_manager')) {
        $quickActions[] = ['label' => $_lang['bk_manager'], 'href' => 'index.php?a=93', 'target' => 'main', 'icon' => $icons['database']];
    }
    if (evo()->hasPermission('change_password')) {
        $quickActions[] = ['label' => $_lang['change_password'], 'href' => 'index.php?a=28', 'target' => 'main', 'icon' => $icons['lock']];
    }
    $quickActions[] = ['label' => $_lang['logout'], 'href' => 'index.php?a=8', 'target' => '_top', 'icon' => $icons['logout']];

    $configDisplay = false;
    $configCheckResults = '';
    if (evo()->getConfig('warning_visibility') || $_SESSION['mgrRole'] == 1) {
        include_once MODX_MANAGER_PATH . 'includes/config_check.inc.php';
        if ($config_check_results != $_lang['configcheck_ok']) {
            $configCheckResults = $config_check_results;
            $configDisplay = true;
        }
    }

    $logoutReminderMsg = '';
    $showLogoutReminder = false;
    if (isset($_SESSION['show_logout_reminder'])) {
        if ($_SESSION['show_logout_reminder']['type'] === 'logout_reminder') {
            $date = evo()->toDateFormat($_SESSION['show_logout_reminder']['lastHit'], 'dateOnly');
            $logoutReminderMsg = str_replace('[+date+]', $date, $_lang['logout_reminder_msg']);
        }
        $showLogoutReminder = true;
        unset($_SESSION['show_logout_reminder']);
    }

    $showMultipleSessions = false;
    $multipleSessionsMsg = '';

    $onlineRows = [];
    $onlineNow = '';
    $onlineMessage = '';

    $activeUsers = \EvolutionCMS\Models\ActiveUserSession::query()
        ->join('active_users', 'active_users.sid', '=', 'active_user_sessions.sid')
        ->where('active_users.action', '<>', 8)
        ->orderBy('username', 'ASC')
        ->orderBy('active_users.sid', 'ASC');

    if ($activeUsers->count() > 0) {
        $now = evo()->now()->unix();
        if (extension_loaded('intl')) {
            $formatter = new IntlDateFormatter(evolutionCMS()->getConfig('manager_language'), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, null, null, 'HH:mm:ss');
            $onlineNow = $formatter->format($now);
        } else {
            $onlineNow = date('H:i:s', $now);
        }
        $onlineMessage = $_lang['onlineusers_message'];
        $timetocheck = $now - 60 * 20;

        $userCount = [];
        $userList = [];
        foreach ($activeUsers->get()->toArray() as $activeUser) {
            $userCount[$activeUser['internalKey']] = isset($userCount[$activeUser['internalKey']]) ? $userCount[$activeUser['internalKey']] + 1 : 1;

            $isIdle = ($activeUser['lasthit'] + evo()->getConfig('server_offset_time')) < $timetocheck;
            $ip = $activeUser['ip'] === '::1' ? '127.0.0.1' : $activeUser['ip'];
            $currentaction = EvolutionCMS\Legacy\LogHandler::getAction($activeUser['action'], $activeUser['id']);
            if ($activeUser['action'] == 112 && $activeUser['id'] == 0) {
                $managerLog = EvolutionCMS\Models\ManagerLog::where('internalKey', $activeUser['internalKey'])
                    ->where('action', $activeUser['action'])
                    ->orderByDesc('timestamp')
                    ->first();
                if ($managerLog) {
                    $currentaction = $managerLog->itemname . ' - ' . str_replace($managerLog->itemname, '', $managerLog->message);
                }
            }
            if (extension_loaded('intl')) {
                $formatter = new IntlDateFormatter(evolutionCMS()->getConfig('manager_language'), IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, null, null, 'HH:mm:ss');
                $lasthit = $formatter->format(evo()->timestamp($activeUser['lasthit']));
            } else {
                $lasthit = date('H:i:s', evo()->timestamp($activeUser['lasthit']));
            }
            $userList[] = [
                'idle' => $isIdle,
                'username' => $activeUser['username'],
                'is_web' => $activeUser['internalKey'] < 0,
                'id' => abs($activeUser['internalKey']),
                'ip' => $ip,
                'lasthit' => $lasthit,
                'action' => $currentaction,
                'internalKey' => $activeUser['internalKey']
            ];
        }

        foreach ($userList as $params) {
            $params['multi'] = $userCount[$params['internalKey']] > 1;
            $onlineRows[] = $params;
        }
    }

    $recentItems = [];
    $recentQuery = \EvolutionCMS\Models\SiteContent::query()
        ->leftJoin('site_templates', 'site_content.template', '=', 'site_templates.id')
        ->select('site_content.*', 'site_templates.templatename')
        ->orderBy('editedon', 'DESC')
        ->limit(10);

    $notSetHtml = '<span class="italic text-base-content/60">' . htmlspecialchars($_lang['not_set'], ENT_QUOTES, 'UTF-8') . '</span>';

    if ($recentQuery->count() > 0) {
        foreach ($recentQuery->get()->toArray() as $ph) {
            $docid = $ph['id'];
            $_ = evo()->getUserInfo($ph['editedby']);
            $username = isset($_['username']) ? $_['username'] : '';

            if ($ph['deleted'] == 1) {
                $statusClass = 'text-error';
            } elseif ($ph['published'] == 0) {
                $statusClass = 'text-warning';
            } else {
                $statusClass = 'text-base-content';
            }

            $actions = [];
            if (evo()->hasPermission('edit_document')) {
                $actions[] = [
                    'title' => $_lang['edit_resource'],
                    'href' => 'index.php?a=27&id=' . $docid,
                    'target' => 'main',
                    'icon' => $icons['edit']
                ];
            }

            $previewDisabled = ($ph['deleted'] == 1);
            $actions[] = [
                'title' => $_lang['preview_resource'],
                'href' => '../index.php?&id=' . $docid,
                'target' => '_blank',
                'icon' => $icons['eye'],
                'disabled' => $previewDisabled
            ];

            if (evo()->hasPermission('delete_document')) {
                if ($ph['deleted'] == 0) {
                    $actions[] = [
                        'title' => $_lang['delete_resource'],
                        'href' => 'index.php?a=6&id=' . $docid,
                        'target' => 'main',
                        'icon' => $icons['trash'],
                        'confirm' => $_lang['confirm_delete_record']
                    ];
                } else {
                    $actions[] = [
                        'title' => $_lang['undelete_resource'],
                        'href' => 'index.php?a=63&id=' . $docid,
                        'target' => 'main',
                        'icon' => $icons['undo'],
                        'confirm' => $_lang['confirm_undelete']
                    ];
                }
            }

            if ($ph['deleted'] == 1 && $ph['published'] == 0) {
                $publish = ['disabled' => true, 'icon' => $icons['check']];
            } elseif ($ph['deleted'] == 1 && $ph['published'] == 1) {
                $publish = ['disabled' => true, 'icon' => $icons['close']];
            } elseif ($ph['deleted'] == 0 && $ph['published'] == 0) {
                $publish = ['href' => 'index.php?a=61&id=' . $docid, 'icon' => $icons['check']];
            } else {
                $publish = ['href' => 'index.php?a=62&id=' . $docid, 'icon' => $icons['close']];
            }

            $actions[] = [
                'title' => $ph['published'] == 1 ? $_lang['unpublish_resource'] : $_lang['publish_resource'],
                'href' => $publish['href'] ?? '#',
                'target' => 'main',
                'icon' => $publish['icon'],
                'disabled' => $publish['disabled'] ?? false
            ];

            $actions[] = [
                'title' => $_lang['resource_overview'],
                'href' => '#',
                'target' => '',
                'icon' => $icons['info'],
                'noop' => true
            ];

            $recentItems[] = [
                'id' => $docid,
                'title' => $ph['pagetitle'],
                'title_url' => 'index.php?a=3&id=' . $docid,
                'status_class' => $statusClass,
                'edit_date' => evo()->toDateFormat(evo()->timestamp($ph['editedon'])),
                'username' => $username,
                'actions' => $actions,
                'details' => [
                    'longtitle' => $ph['longtitle'] !== '' ? htmlspecialchars($ph['longtitle'], ENT_QUOTES, 'UTF-8') : $notSetHtml,
                    'description' => $ph['description'] !== '' ? htmlspecialchars($ph['description'], ENT_QUOTES, 'UTF-8') : $notSetHtml,
                    'introtext' => $ph['introtext'] !== '' ? htmlspecialchars($ph['introtext'], ENT_QUOTES, 'UTF-8') : $notSetHtml,
                    'doctype' => $ph['type'] == 'reference' ? $_lang['weblink'] : $_lang['resource'],
                    'alias' => $ph['alias'] !== '' ? htmlspecialchars($ph['alias'], ENT_QUOTES, 'UTF-8') : $notSetHtml,
                    'cacheable' => $ph['cacheable'] == 1 ? $_lang['yes'] : $_lang['no'],
                    'hidemenu' => $ph['hidemenu'] == 1 ? $_lang['no'] : $_lang['yes'],
                    'template' => $ph['templatename'] ? htmlspecialchars($ph['templatename'], ENT_QUOTES, 'UTF-8') : $notSetHtml,
                ]
            ];
        }
    }

    $rssNewsItems = [];
    $rssSecurityItems = [];

    $urls['evo_news_content'] = evo()->getConfig('rss_url_news');
    $urls['evo_security_notices_content'] = evo()->getConfig('rss_url_security');

    $itemsNumber = 3;

    $feed = new \SimplePie\SimplePie();
    $feedCache = evolutionCMS()->getCachePath() . 'rss/';
    \Illuminate\Support\Facades\File::ensureDirectoryExists($feedCache);
    $feed->set_cache_location($feedCache);
    foreach ($urls as $section => $url) {
        if (empty($url)) {
            continue;
        }
        $feed->set_feed_url($url);
        $feed->init();
        $items = $feed->get_items(0, $itemsNumber);
        if (empty($items)) {
            continue;
        }
        foreach ($items as $item) {
            $href = $item->get_link();
            $title = $item->get_title();
            $pubdate = $item->get_date();
            $pubdate = evo()->toDateFormat(strtotime($pubdate));
            $description = strip_tags($item->get_content());
            if (strlen($description) > 199) {
                $description = \Illuminate\Support\Str::words($description, 15, '...');
            }
            $payload = [
                'title' => $title,
                'href' => $href,
                'pubdate' => $pubdate,
                'description' => $description,
            ];
            if ($section === 'evo_security_notices_content') {
                $rssSecurityItems[] = $payload;
            } else {
                $rssNewsItems[] = $payload;
            }
        }
    }

    $ph = $_lang;
    $ph['theme'] = evo()->getConfig('manager_theme');
    $ph['site_name'] = evo()->getPhpCompat()->entities(evo()->getConfig('site_name'));
    $ph['home'] = $_lang['home'];
    $ph['logo_slogan'] = $_lang['logo_slogan'];
    $ph['welcome_title'] = $_lang['welcome_title'];
    $ph['search'] = $_lang['search'];
    $ph['settings_config'] = $_lang['settings_config'];
    $ph['configcheck_title'] = $_lang['configcheck_title'];
    $ph['online'] = $_lang['online'];
    $ph['onlineusers_title'] = $_lang['onlineusers_title'];
    $ph['recent_docs'] = $_lang['recent_docs'];
    $ph['activity_title'] = $_lang['activity_title'];
    $ph['info'] = $_lang['info'];
    $ph['yourinfo_title'] = $_lang['yourinfo_title'];
    $ph['modx_security_notices'] = $_lang['security_notices_tab'];
    $ph['modx_security_notices_title'] = $_lang['security_notices_title'];
    $ph['modx_news'] = $_lang['modx_news_tab'];
    $ph['modx_news_title'] = $_lang['modx_news_title'];
    evo()->toPlaceholders($ph);

    $script = evo()->getChunk('manager#welcome\StartUpScript');
    evo()->regClientScript($script);

    $welcomePrerender = '';
    $evtOut = evo()->invokeEvent('OnManagerWelcomePrerender');
    if (is_array($evtOut)) {
        $welcomePrerender = implode('', $evtOut);
    }

    $welcomeRender = '';
    $evtOut = evo()->invokeEvent('OnManagerWelcomeRender');
    if (is_array($evtOut)) {
        $welcomeRender = implode('', $evtOut);
    }
    $stripLegacyContainer = static function (string $html): string {
        if ($html === '') {
            return $html;
        }
        return preg_replace_callback('/class=(["\'])(.*?)\1/', static function ($matches) {
            $classes = preg_split('/\s+/', trim($matches[2]));
            $classes = array_values(array_filter($classes, static function ($class) {
                return $class !== 'container' && $class !== 'container-body';
            }));
            if (count($classes) === 0) {
                return '';
            }
            return 'class="' . implode(' ', $classes) . '"';
        }, $html);
    };
    $welcomePrerender = $stripLegacyContainer($welcomePrerender);
    $welcomeRender = $stripLegacyContainer($welcomeRender);

    $legacyWidgetsForEvent = [
        [
            'menuindex' => '10',
            'id' => 'welcome',
            'cols' => 'col-lg-6',
            'icon' => 'tabler-home',
            'title' => $_lang['welcome_title'],
            'body' => '',
            'hide' => '0',
        ],
        [
            'menuindex' => '20',
            'id' => 'onlineinfo',
            'cols' => 'col-lg-6',
            'icon' => 'tabler-users',
            'title' => $_lang['onlineusers_title'],
            'body' => '',
            'hide' => '0',
        ],
        [
            'menuindex' => '30',
            'id' => 'recent_widget',
            'cols' => 'col-sm-12',
            'icon' => 'tabler-pencil',
            'title' => $_lang['activity_title'],
            'body' => '',
            'hide' => '0',
        ],
    ];
    if (evo()->getConfig('rss_url_news')) {
        $legacyWidgetsForEvent[] = [
            'menuindex' => '40',
            'id' => 'news',
            'cols' => 'col-sm-6',
            'icon' => 'tabler-rss',
            'title' => $_lang['modx_news_title'],
            'body' => '',
            'hide' => '0',
        ];
    }
    if (evo()->getConfig('rss_url_security')) {
        $legacyWidgetsForEvent[] = [
            'menuindex' => '50',
            'id' => 'security',
            'cols' => 'col-sm-6',
            'icon' => 'tabler-alert-triangle',
            'title' => $_lang['security_notices_title'],
            'body' => '',
            'hide' => '0',
        ];
    }

    $customWidgets = [];
    $sitewidgets = evo()->invokeEvent('OnManagerWelcomeHome', ['widgets' => $legacyWidgetsForEvent]);
    if (is_array($sitewidgets)) {
        foreach ($sitewidgets as $widget) {
            $customWidgets = array_merge($customWidgets, unserialize($widget));
        }
    }
    if (count($customWidgets) > 1) {
        usort($customWidgets, function ($a, $b) {
            return ($a['menuindex'] ?? 0) <=> ($b['menuindex'] ?? 0);
        });
    }
@endphp

<div class="drawer-content w-full mx-auto min-h-full bg-base-100 p-5 lg:px-5 lg:py-5 space-y-4">
    {!! $welcomePrerender !!}

        @if($showLogoutReminder && $logoutReminderMsg)
            <div class="alert alert-warning">
                <span class="text-warning [&_svg]:h-5 [&_svg]:w-5">{!! $icons['alert'] !!}</span>
                <div class="text-sm">{!! $logoutReminderMsg !!}</div>
            </div>
        @endif

        @if($showMultipleSessions && $multipleSessionsMsg)
            <div class="alert alert-warning">
                <span class="text-warning [&_svg]:h-5 [&_svg]:w-5">{!! $icons['alert'] !!}</span>
                <div class="text-sm">{!! $multipleSessionsMsg !!}</div>
            </div>
        @endif

        @if($configDisplay && $configCheckResults)
            <div class="alert alert-warning">
                <span class="text-warning [&_svg]:h-5 [&_svg]:w-5">{!! $icons['alert'] !!}</span>
                <div class="text-sm">{!! $configCheckResults !!}</div>
            </div>
        @endif

        @if(count($customWidgets) > 0)
            <div class="grid grid-cols-12 gap-4">
                @php
                    $widgetIndex = 0;
                @endphp
                @foreach($customWidgets as $widget)
                    @if((bool) ($widget['hide'] ?? false) === true)
                        @continue
                    @endif
                    @php
                        $widgetIndex++;
                        if ($widgetIndex === 3) {
                            $colSpan = 'col-span-12';
                        } else {
                            $colSpan = 'col-span-12 lg:col-span-6';
                        }
                        if (isset($widget['icon']) && strpos($widget['icon'], 'tabler-') === 0) {
                            $iconHtml = svg($widget['icon'])->toHtml();
                        } else {
                            $iconHtml = isset($widget['icon']) ? '<i class="fa ' . $widget['icon'] . '"></i>' : '';
                        }
                        $titleHtml = $widget['title'] ?? '';
                    @endphp
                    <div class="{{ $colSpan }}">
                        <x-mary-card separator class="bg-base-200" body-class="pt-3">
                            <x-slot:title>
                                @if($iconHtml !== '')
                                    <span class="inline-flex items-center gap-2">
                                        {!! $iconHtml !!}
                                        <span>{!! $titleHtml !!}</span>
                                    </span>
                                @else
                                    {!! $titleHtml !!}
                                @endif
                            </x-slot:title>
                            {!! $widget['body'] ?? '' !!}
                        </x-mary-card>
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-6">
                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                    <x-slot:title>
                        <span class="inline-flex items-center gap-2">
                            {!! $icons['home'] !!}
                            <span>{{ $_lang['welcome_title'] }}</span>
                        </span>
                    </x-slot:title>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($quickActions as $action)
                            <a
                                class="btn btn-ghost btn-sm h-auto py-3 flex flex-col items-center gap-2 text-center"
                                href="{{ $action['href'] }}"
                                target="{{ $action['target'] }}"
                            >
                                <span class="text-primary [&_svg]:h-5 [&_svg]:w-5">{!! $action['icon'] !!}</span>
                                <span class="text-xs leading-tight">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    </x-mary-card>
                </div>

                <div class="col-span-12 lg:col-span-6">
                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                    <x-slot:title>
                        <span class="inline-flex items-center gap-2">
                            {!! $icons['users'] !!}
                            <span>{{ $_lang['onlineusers_title'] }}</span>
                        </span>
                    </x-slot:title>
                    @if(count($onlineRows) === 0)
                        <div class="text-sm text-base-content/60">{{ $_lang['no_active_users_found'] }}</div>
                    @else
                        <div class="text-xs text-base-content/60 mb-2">{!! $onlineMessage !!} <span class="font-semibold">{{ $onlineNow }}</span>):</div>
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead class="text-xs text-base-content/60">
                                    <tr>
                                        <th>{{ $_lang['onlineusers_user'] }}</th>
                                        <th>ID</th>
                                        <th>{{ $_lang['onlineusers_ipaddress'] }}</th>
                                        <th>{{ $_lang['onlineusers_lasthit'] }}</th>
                                        <th>{{ $_lang['onlineusers_action'] }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($onlineRows as $row)
                                        @php
                                            $rowClass = $row['idle'] ? 'text-base-content/60' : '';
                                            $rowClass = $row['multi'] ? trim($rowClass . ' font-semibold') : $rowClass;
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1">
                                                    {{ $row['username'] }}
                                                </span>
                                            </td>
                                            <td class="text-xs text-base-content/70 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1">
                                                    @if($row['is_web'])
                                                        <span class="text-primary [&_svg]:h-3.5 [&_svg]:w-3.5">{!! $icons['world'] !!}</span>
                                                    @endif
                                                    {{ $row['id'] }}
                                                </span>
                                            </td>
                                            <td class="text-xs text-base-content/70 whitespace-nowrap">{{ $row['ip'] }}</td>
                                            <td class="text-xs text-base-content/70 whitespace-nowrap">{{ $row['lasthit'] }}</td>
                                            <td class="text-xs max-w-[280px]">
                                                <span class="block truncate">{{ $row['action'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    </x-mary-card>
                </div>

                <div class="col-span-12">
                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>
                            <span class="inline-flex items-center gap-2">
                                {!! $icons['pencil'] !!}
                                <span>{{ $_lang['activity_title'] }}</span>
                            </span>
                        </x-slot:title>
                        @if(count($recentItems) === 0)
                            <div class="text-sm text-base-content/60">{{ $_lang['no_activity_message'] }}</div>
                        @else
                            <div class="space-y-2">
                                @foreach($recentItems as $item)
                                    <div class="collapse collapse-arrow bg-base-100 border border-base-content/10">
                                        <input type="checkbox" />
                                        <div class="collapse-title font-semibold">
                                            <div class="flex flex-wrap items-center gap-2 w-full">
                                                <span class="badge badge-sm">{{ $item['id'] }}</span>
                                                <a
                                                    class="flex-1 min-w-0 truncate {{ $item['status_class'] }}"
                                                    title="{{ $_lang['edit_resource'] }}"
                                                    href="{{ $item['title_url'] }}"
                                                    target="main"
                                                    onclick="event.stopPropagation();"
                                                >
                                                    {{ $item['title'] }}
                                                </a>
                                                <span class="text-xs text-base-content/60 whitespace-nowrap">{{ $item['edit_date'] }}</span>
                                                <span class="text-xs text-base-content/60 whitespace-nowrap">{{ $item['username'] }}</span>
                                                <span class="flex items-center gap-1">
                                                    @foreach($item['actions'] as $action)
                                                        @php
                                                            $disabled = $action['disabled'] ?? false;
                                                            $isNoop = $action['noop'] ?? false;
                                                            $confirm = $action['confirm'] ?? '';
                                                            $btnClass = $disabled ? 'btn-disabled' : '';
                                                            $href = $isNoop ? '#' : ($action['href'] ?? '#');
                                                            $onclick = 'event.stopPropagation();';
                                                            if ($disabled || $isNoop) {
                                                                $onclick .= 'return false;';
                                                            } elseif ($confirm) {
                                                                $onclick .= 'return confirm(' . json_encode($confirm) . ');';
                                                            }
                                                        @endphp
                                                        <a
                                                            class="btn btn-ghost btn-xs {{ $btnClass }}"
                                                            title="{{ $action['title'] }}"
                                                            href="{{ $href }}"
                                                            target="{{ $action['target'] ?? '' }}"
                                                            onclick="{{ $onclick }}"
                                                        >
                                                            <span class="[&_svg]:h-4 [&_svg]:w-4">{!! $action['icon'] !!}</span>
                                                        </a>
                                                    @endforeach
                                                </span>
                                            </div>
                                        </div>
                                        <div class="collapse-content text-sm">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                                <div><span class="font-semibold">{{ $_lang['long_title'] }}</span>: {!! $item['details']['longtitle'] !!}</div>
                                                <div><span class="font-semibold">{{ $_lang['description'] }}</span>: {!! $item['details']['description'] !!}</div>
                                                <div><span class="font-semibold">{{ $_lang['resource_summary'] }}</span>: {!! $item['details']['introtext'] !!}</div>
                                                <div><span class="font-semibold">{{ $_lang['type'] }}</span>: {{ $item['details']['doctype'] }}</div>
                                                <div><span class="font-semibold">{{ $_lang['resource_alias'] }}</span>: {!! $item['details']['alias'] !!}</div>
                                                <div><span class="font-semibold">{{ $_lang['page_data_cacheable'] }}</span>: {{ $item['details']['cacheable'] }}</div>
                                                <div><span class="font-semibold">{{ $_lang['resource_opt_show_menu'] }}</span>: {{ $item['details']['hidemenu'] }}</div>
                                                <div><span class="font-semibold">{{ $_lang['page_data_template'] }}</span>: {!! $item['details']['template'] !!}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-mary-card>
                </div>

                @if(count($rssNewsItems) > 0)
                    <div class="col-span-12 lg:col-span-6">
                        <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>
                            <span class="inline-flex items-center gap-2">
                                {!! $icons['rss'] !!}
                                <span>{{ $_lang['modx_news_title'] }}</span>
                            </span>
                        </x-slot:title>
                        <div class="space-y-3 max-h-[240px] overflow-y-auto pr-1">
                            @foreach($rssNewsItems as $item)
                                <div class="text-sm">
                                    <a class="font-semibold" href="{{ $item['href'] }}" target="_blank">{{ $item['title'] }}</a>
                                    <div class="text-xs text-base-content/60">{{ $item['pubdate'] }}</div>
                                    <div class="text-xs text-base-content/70">{{ $item['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        </x-mary-card>
                    </div>
                @endif

                @if(count($rssSecurityItems) > 0)
                    <div class="col-span-12 lg:col-span-6">
                        <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>
                            <span class="inline-flex items-center gap-2">
                                {!! $icons['alert'] !!}
                                <span>{{ $_lang['security_notices_title'] }}</span>
                            </span>
                        </x-slot:title>
                        <div class="space-y-3 max-h-[240px] overflow-y-auto pr-1">
                            @foreach($rssSecurityItems as $item)
                                <div class="text-sm">
                                    <a class="font-semibold" href="{{ $item['href'] }}" target="_blank">{{ $item['title'] }}</a>
                                    <div class="text-xs text-base-content/60">{{ $item['pubdate'] }}</div>
                                    <div class="text-xs text-base-content/70">{{ $item['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        </x-mary-card>
                    </div>
                @endif
            </div>
        @endif

    {!! $welcomeRender !!}
</div>
@endsection
