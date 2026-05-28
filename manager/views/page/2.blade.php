@extends('manager::template.page')
@section('content')
    <style>
        .widgets #welcome .wm_button a svg {
            display: block;
            width: 2rem;
            height: 2rem;
            margin: 0 auto 0.5rem auto;
        }
        .widgets .card-header svg {
            width: 18px;
            height: 18px;
            vertical-align: middle;
            margin-right: 0.25em;
        }
        .widgets .card-header i {
            font-size: 18px;
            line-height: 18px;
        }
    </style>
    <?php /*include_once evolutionCMS()->get('ManagerTheme')->getFileProcessor("actions/welcome.static.php");*/
    unset($_SESSION['itemname'], $_SESSION['itemaction']); // clear this, because it's only set for logging purposes

    if (evo()->hasPermission('settings') && evo()->getConfig('settings_version') !== evo()->getVersionData('version')) {
        // seems to be a new install - send the user to the configuration page
        exit('<script type="text/javascript">document.location.href="index.php?a=17";</script>');
    }

    $_style = EvolutionCMS\Facades\ManagerTheme::getStyle();
    $which_browser = evo()->getConfig('which_browser');

    // Tabler SVG overrides for dashboard icons
    $_style['icon_user'] = svg('tabler-user-cog')->toHtml();
    $_style['icon_web_user'] = svg('tabler-users')->toHtml();
    $_style['icon_modules'] = svg('tabler-packages')->toHtml();
    $_style['icon_elements'] = svg('tabler-blocks')->toHtml();
    $_style['icon_database'] = svg('tabler-database')->toHtml();
    $_style['icon_question_circle'] = svg('tabler-help')->toHtml();
    $_style['icon_add'] = svg('tabler-file-plus')->toHtml();
    $_style['icon_chain'] = svg('tabler-link')->toHtml();
    $_style['icon_camera'] = svg('tabler-camera')->toHtml();
    $_style['icon_folder_open'] = svg('tabler-folder-open')->toHtml();
    $_style['icon_lock'] = svg('tabler-lock')->toHtml();
    $_style['icon_logout'] = svg('tabler-logout')->toHtml();
    $_style['icon_home'] = svg('tabler-home')->toHtml();
    $_style['icon_users'] = svg('tabler-users')->toHtml();
    $_style['icon_pencil'] = svg('tabler-pencil')->toHtml();
    $_style['icon_rss'] = svg('tabler-rss')->toHtml();
    $_style['icon_alert_triangle'] = svg('tabler-alert-triangle')->toHtml();
    $_style['icon_rocket'] = svg('tabler-rocket')->toHtml();
    $_style['icon_package'] = svg('tabler-package')->toHtml();

    // set placeholders
    $ph = $_lang;

    $iconTpl = evo()->getChunk('manager#welcome\WrapIcon');
    // setup icons
    if (evo()->hasPermission('new_user') || evo()->hasPermission('edit_user')) {
        $icon = $_style['icon_user'] . ' [%user_management_title%]';
        $ph['SecurityIcon'] = sprintf($iconTpl, $icon, 75);
    }
    if (evo()->hasPermission('new_user') || evo()->hasPermission('edit_user')) {
        $icon = $_style['icon_web_user'] . ' [%web_user_management_title%]';
        $ph['WebUserIcon'] = sprintf($iconTpl, $icon, 99);
    }
    if (evo()->hasPermission('new_module') || evo()->hasPermission('edit_module')) {
        $icon = $_style['icon_modules'] . ' [%modules%]';
        $ph['ModulesIcon'] = sprintf($iconTpl, $icon, 106);
    }
    if (evo()->hasPermission('new_template') || evo()->hasPermission('edit_template') || evo()->hasPermission('new_snippet') || evo()->hasPermission('edit_snippet') || evo()->hasPermission('new_plugin') || evo()->hasPermission('edit_plugin') || evo()->hasPermission('manage_metatags')) {
        $icon = $_style['icon_elements'] . ' [%elements%]';
        $ph['ResourcesIcon'] = sprintf($iconTpl, $icon, 76);
    }
    if (evo()->hasPermission('bk_manager')) {
        $icon = $_style['icon_database'] . ' [%backup%]';
        $ph['BackupIcon'] = sprintf($iconTpl, $icon, 93);
    }
    if (evo()->hasPermission('help')) {
        $icon = $_style['icon_question_circle'] . ' [%help%]';
        $ph['HelpIcon'] = sprintf($iconTpl, $icon, 9);
    }

    if (evo()->hasPermission('new_document')) {
        $icon = $_style['icon_add'] . '[%add_resource%]';
        $ph['ResourceIcon'] = sprintf($iconTpl, $icon, 4);
        $icon = $_style['icon_chain'] . '[%add_weblink%]';
        $ph['WeblinkIcon'] = sprintf($iconTpl, $icon, 72);
    }
    if (evo()->hasPermission('assets_images')) {
        $icon = $_style['icon_camera'] . '[%images_management%]';
        $ph['ImagesIcon'] = sprintf($iconTpl, $icon, 72);
    }
    if (evo()->hasPermission('assets_files')) {
        $icon = $_style['icon_folder_open'] . '[%files_management%]';
        $ph['FilesIcon'] = sprintf($iconTpl, $icon, 72);
    }
    if (evo()->hasPermission('change_password')) {
        $icon = $_style['icon_lock'] . '[%change_password%]';
        $ph['PasswordIcon'] = sprintf($iconTpl, $icon, 28);
    }
    $icon = $_style['icon_logout'] . '[%logout%]';
    $ph['LogoutIcon'] = sprintf($iconTpl, $icon, 8);

    // do some config checks
    if (evo()->getConfig('warning_visibility') || $_SESSION['mgrRole'] == 1) {
        include_once EVO_MANAGER_PATH . 'includes/config_check.inc.php';
        if ($config_check_results != $_lang['configcheck_ok']) {
            $ph['config_check_results'] = $config_check_results;
            $ph['config_display'] = 'block';
        } else {
            $ph['config_display'] = 'none';
        }
    } else {
        $ph['config_display'] = 'none';
    }

    // Check logout-reminder
    if (isset($_SESSION['show_logout_reminder'])) {
        switch ($_SESSION['show_logout_reminder']['type']) {
            case 'logout_reminder':
                $date = evo()->toDateFormat($_SESSION['show_logout_reminder']['lastHit'], 'dateOnly');
                $ph['logout_reminder_msg'] = str_replace('[+date+]', $date, $_lang['logout_reminder_msg']);
                break;
        }
        $ph['show_logout_reminder'] = 'block';
        unset($_SESSION['show_logout_reminder']);
    } else {
        $ph['show_logout_reminder'] = 'none';
    }

    // Check multiple sessions

    $ph['show_multiple_sessions'] = 'none';

    if (evo()->hasPermission('widget_recent_info')) {
        $ph['RecentInfo'] = evo()->getChunk('manager#welcome\RecentInfo');
    }

    $tpl = '
    <table class="table data">
    	<tr>
    		<td width="150">[%yourinfo_username%]</td>
    		<td><b>[+username+]</b></td>
    	</tr>
    	<tr>
    		<td>[%yourinfo_role%]</td>
    		<td><b>[+role+]</b></td>
    	</tr>
    	<tr>
    		<td>[%yourinfo_previous_login%]</td>
    		<td><b>[+lastlogin+]</b></td>
    	</tr>
    	<tr>
    		<td>[%yourinfo_total_logins%]</td>
    		<td><b>[+logincount+]</b></td>
    	</tr>
    </table>';

    $ph['UserInfo'] = evo()->parseText($tpl, [
        'username' => evo()->getLoginUserName(),
        'role' => $_SESSION['mgrPermissions']['name'],
        'lastlogin' => evo()->toDateFormat(evo()->timestamp($_SESSION['mgrLastlogin'])),
        'logincount' => $_SESSION['mgrLogincount'] + 1,
    ]);
    if (evo()->hasPermission('widget_online_info')) {
        $activeUsers = \EvolutionCMS\Models\ActiveUserSession::query()
            ->join('active_users', 'active_users.sid', '=', 'active_user_sessions.sid')
            ->where('active_users.action', '<>', 8)
            ->orderBy('username', 'ASC')
            ->orderBy('active_users.sid', 'ASC');
        if ($activeUsers->count() < 1) {
            $html = '<p>[%no_active_users_found%]</p>';
        } else {
            $now = evo()->now()->unix();
            if (extension_loaded('intl')) {
                // https://www.php.net/manual/en/class.intldateformatter.php
                // https://www.php.net/manual/en/datetime.createfromformat.php
                $formatter = new IntlDateFormatter(
                    evolutionCMS()->getConfig('manager_language'),
                    IntlDateFormatter::MEDIUM,
                    IntlDateFormatter::MEDIUM,
                    null,
                    null,
                    'HH:mm:ss'
                );
                $ph['now'] = $formatter->format($now);
            } else {
                $ph['now'] = date('H:i:s', $now);
            }
            $timetocheck = $now - 60 * 20; //+$server_offset_time;
            $html = '
    	<div class="card-body">
    		[%onlineusers_message%]
    		<b>[+now+]</b>):
    	</div>
    	<div class="table-responsive">
    	<table class="table data">
    	<thead>
    		<tr>
    			<th>[%onlineusers_user%]</th>
    			<th>ID</th>
    			<th>[%onlineusers_ipaddress%]</th>
    			<th>[%onlineusers_lasthit%]</th>
    			<th>[%onlineusers_action%]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
    		</tr>
    	</thead>
    	<tbody>';

            $userList = [];
            $userCount = [];
            // Create userlist with session-count first before output
            foreach ($activeUsers->get()->toArray() as $activeUser) {
                $userCount[$activeUser['internalKey']] = isset($userCount[$activeUser['internalKey']])
                    ? $userCount[$activeUser['internalKey']] + 1 :
                    1;

                $idle = ($activeUser['lasthit'] + evo()->getConfig('server_offset_time')) < $timetocheck
                    ? ' class="userIdle"'
                    : '';
                $webicon = $activeUser['internalKey'] < 0 ? '<i class="[&icon_globe&]"></i>' : '';
                $ip = $activeUser['ip'] === '::1' ? '127.0.0.1' : $activeUser['ip'];
                $currentaction = EvolutionCMS\Legacy\LogHandler::getAction($activeUser['action'], $activeUser['id']);
                if ($activeUser['action'] == 112 && $activeUser['id'] == 0) {
                    $managerLog = EvolutionCMS\Models\ManagerLog::where(
                        'internalKey',
                        $activeUser['internalKey']
                    )->where('action', $activeUser['action'])->orderByDesc('timestamp')->first();
                    if ($managerLog) {
                        $currentaction = $managerLog->itemname . ' - ' . str_replace(
                                $managerLog->itemname,
                                '',
                                $managerLog->message
                            );
                    }
                }
                if (extension_loaded('intl')) {
                    $formatter = new IntlDateFormatter(
                        evo()->getConfig('manager_language'),
                        IntlDateFormatter::MEDIUM,
                        IntlDateFormatter::MEDIUM,
                        null,
                        null,
                        'HH:mm:ss'
                    );
                    $lasthit = $formatter->format(evo()->timestamp($activeUser['lasthit']));
                } else {
                    $lasthit = date('H:i:s', evo()->timestamp($activeUser['lasthit']));
                }
                $userList[] = [
                    $idle,
                    '',
                    $activeUser['username'],
                    $webicon,
                    abs($activeUser['internalKey']),
                    $ip,
                    $lasthit,
                    $currentaction
                ];
            }
            foreach ($userList as $params) {
                $params[1] = $userCount[$params[4]] > 1 ? ' class="userMultipleSessions"' : '';
                $html .= "\n\t\t" . vsprintf(
                        '<tr%s><td><strong%s>%s</strong></td><td>%s%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                        $params
                    );
            }

            $html .= '
    	</tbody>
    	</table>
    </div>
    ';
        }
        $ph['OnlineInfo'] = $html;
    }
    // Include rss feeds for important forum topics
    // Here you can set the urls to retrieve the RSS from. Add a $urls line following the numbering progress in the square brackets.

    $urls['evo_news_content'] = evo()->getConfig('rss_url_releases');
    $urls['evo_extras_releases_content'] = evo()->getConfig('rss_url_extras');

    // How many items per Feed?
    $itemsNumber = 5;
    $feedData = [];

    // create Feed
    $feed = new \SimplePie\SimplePie();
    $feedCache = evolutionCMS()->getCachePath() . 'rss/';
    \Illuminate\Support\Facades\File::ensureDirectoryExists($feedCache);
    $feed->set_cache_location($feedCache);
    foreach ($urls as $section => $url) {
        if (empty($url)) {
            continue;
        }
        $output = '';
        $feed->set_feed_url($url);
        $feed->init();
        $items = $feed->get_items(0, $itemsNumber);
        if (empty($items)) {
            $feedData[$section] = 'Failed to retrieve ' . $url;
            continue;
        }
        $output = '<ul>';
        foreach ($items as $item) {
            $href = $item->get_link();
            $title = $item->get_title();
            $pubdate = $item->get_date();
            $pubdate = evo()->toDateFormat(strtotime($pubdate));
            $description = strip_tags($item->get_content());
            if (strlen($description) > 199) {
                $description = \Illuminate\Support\Str::words($description, 15, '...');
                $description .= '<br />Read <a href="' . $href . '" target="_blank">more</a>.';
            }
            $output .= '<li><a href="' . $href . '" target="_blank">' . $title . '</a> - <b>' . $pubdate . '</b><br />' . $description . '</li>';
        }
        $output .= '</ul>';
        $feedData[$section] = $output;
    }

    $ph['evo_extras_releases_content'] = $feedData['evo_extras_releases_content'] ?? '';
    $ph['evo_news_content'] = $feedData['evo_news_content'] ?? '';

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

    $ph['extras_release_tab'] = $_lang['extras_release_tab'];
    $ph['extras_release_title'] = $_lang['extras_release_title'];
    $ph['evo_release_title'] = $_lang['evo_release_title'];

    evo()->toPlaceholders($ph);

    $script = evo()->getChunk('manager#welcome\StartUpScript');
    evo()->regClientScript($script);

    // invoke event OnManagerWelcomePrerender
    $evtOut = evo()->invokeEvent('OnManagerWelcomePrerender');
    if (is_array($evtOut)) {
        $output = implode('', $evtOut);
        $ph['OnManagerWelcomePrerender'] = $output;
    }

    $widgets['welcome'] = [
        'menuindex' => '10',
        'id' => 'welcome',
        'cols' => 'col-lg-6',
        'icon' => 'tabler-home',
        'title' => '[%welcome_title%]',
        'body' =>
            '
                <div class="wm_buttons card-body">' .
            (evo()->hasPermission('new_document')
                ? '
                    <span class="wm_button">
                        <a target="main" href="index.php?a=4">
                            ' .
                $_style['icon_add'] .
                '
                            <span>[%add_resource%]</span>
                        </a>
                    </span>
                    <span class="wm_button">
                        <a target="main" href="index.php?a=72">
                            ' .
                $_style['icon_chain'] .
                '
                            <span>[%add_weblink%]</span>
                        </a>
                    </span>
                    '
                : '') .
            (evo()->hasPermission('assets_images')
                ? '
                    <span class="wm_button">
                        <a target="main" href="media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=images">
                            ' .
                $_style['icon_camera'] .
                '
                            <span>[%images_management%]</span>
                        </a>
                    </span>
                    '
                : '') .
            (evo()->hasPermission('assets_files')
                ? '
                    <span class="wm_button">
                        <a target="main" href="media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=files">
                            ' .
                $_style['icon_folder_open'] .
                '
                            <span>[%files_management%]</span>
                        </a>
                    </span>
                    '
                : '') .
            (evo()->hasPermission('bk_manager')
                ? '
                    <span class="wm_button">
                        <a target="main" href="index.php?a=93">
                            ' .
                $_style['icon_database'] .
                '
                            <span>[%bk_manager%]</span>
                        </a>
                    </span>
                    '
                : '') .
            (evo()->hasPermission('change_password')
                ? '
                    <span class="wm_button">
                        <a target="main" href="index.php?a=28">
                            ' .
                $_style['icon_lock'] .
                '
                            <span>[%change_password%]</span>
                        </a>
                    </span>
                    '
                : '') .
            '
                    <span class="wm_button">
                        <a target="_top" href="index.php?a=8">
                            ' .
            $_style['icon_logout'] .
            '
                            <span>[%logout%]</span>
                        </a>
                    </span>
                </div>
    		',
        'hide' => '0',
    ];
    if (evo()->hasPermission('widget_online_info')) {
        $widgets['onlineinfo'] = [
            'menuindex' => '20',
            'id' => 'onlineinfo',
            'cols' => 'col-lg-6',
            'icon' => 'tabler-users',
            'title' => '[%onlineusers_title%]',
            'body' => '<div class="userstable">[+OnlineInfo+]</div>',
            'hide' => '0',
        ];
    }
    if (evo()->hasPermission('widget_recent_info')) {
        $widgets['recentinfo'] = [
            'menuindex' => '30',
            'id' => 'recent_widget',
            'cols' => 'col-sm-12',
            'icon' => 'tabler-pencil',
            'title' => '[%activity_title%]',
            'body' => '<div class="widget-stage">[+RecentInfo+]</div>',
            'hide' => '0',
        ];
    }
    if (evo()->getConfig('rss_url_releases')) {
        $widgets['news'] = [
            'menuindex' => '40',
            'id' => 'news',
            'cols' => 'col-sm-6',
            'icon' => 'tabler-rocket',
            'title' => '[%evo_release_title%]',
            'body' => '<div style="max-height:200px;overflow-y: scroll;padding: 1rem .5rem">[+evo_news_content+]</div>',
            'hide' => '0',
        ];
    }
    if (evo()->getConfig('rss_url_extras')) {
        $widgets['extras'] = [
            'menuindex' => '50',
            'id' => 'extras',
            'cols' => 'col-sm-6',
            'icon' => 'tabler-package',
            'title' => '[%extras_release_title%]',
            'body' => '<div style="max-height:200px;overflow-y: scroll;padding: 1rem .5rem">[+evo_extras_releases_content+]</div>',
            'hide' => '0',
        ];
    }

    // invoke OnManagerWelcomeHome event
    $sitewidgets = evo()->invokeEvent('OnManagerWelcomeHome', ['widgets' => $widgets]);
    if (is_array($sitewidgets)) {
        $newwidgets = [];
        foreach ($sitewidgets as $widget) {
            $newwidgets = array_merge($newwidgets, unserialize($widget));
        }
        $widgets = count($newwidgets) > 0 ? $newwidgets : $widgets;
    }

    usort($widgets, function ($a, $b) {
        return $a['menuindex'] - $b['menuindex'];
    });

    $tpl = evo()->getChunk('manager#welcome\Widget');
    $output = '';
    foreach ($widgets as $widget) {
        if ((bool) get_by_key($widget, 'hide', false) !== true) {
            if (isset($widget['icon']) && strpos($widget['icon'], 'tabler-') === 0) {
                $styleKey = 'icon_' . str_replace('-', '_', substr($widget['icon'], 7));
                $widget['icon_html'] = $_style[$styleKey] ?? '';
            } else {
                $widget['icon_html'] = '<i class="fa ' . ($widget['icon'] ?? '') . '"></i>';
            }
            $output .= evo()->parseText($tpl, $widget);
        }
    }
    $ph['widgets'] = $output;
    ?>
    {!! ManagerTheme::makeTemplate('welcome', 'manager_welcome_tpl', $ph, false) !!}
@endsection
