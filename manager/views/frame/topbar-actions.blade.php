                    <ul id="settings" class="nav">
                        <li id="searchform">
                            <form action="index.php?a=71" method="post" target="main">
                                @csrf
                                <input type="hidden" value="Search" name="submitok" />
                                <label for="searchid" class="label_searchid">{{ icon_html($_style['icon_search']) }}</label>
                                <input type="text" id="searchid" name="searchid" size="25" />
                                <div class="mask"></div>
                            </form>
                        </li>
                        @if (evo()->getConfig('show_newresource_btn') && evo()->hasPermission('new_document'))
                            <li id="newresource" class="dropdown newresource">
                                <a href="javascript:;" class="dropdown-toggle" onclick="return false;" title="{{ManagerTheme::getLexicon('add_resource')}}">
                                    {{ icon_html($_style['icon_add']) }}
                                </a>
                                <ul class="dropdown-menu">
                                    @if (evo()->hasPermission('new_document'))
                                        <li>
                                            <a onclick="" href="index.php?a=4" target="main">
                                                {{ icon_html($_style['icon_add']) }} {{ManagerTheme::getLexicon('add_resource')}}
                                            </a>
                                        </li>
                                        <li>
                                            <a onclick="" href="index.php?a=72" target="main">
                                                {{ icon_html($_style['icon_chain']) }} {{ManagerTheme::getLexicon('add_weblink')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))
                                        <li>
                                            <a onclick="" href="media/browser/{{evo()->getConfig('which_browser')}}/browse.php?&type=images" target="main">
                                                {{ icon_html($_style['icon_camera']) }} {{ManagerTheme::getLexicon('images_management')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))
                                        <li>
                                            <a onclick="" href="media/browser/{{$modx->getConfig('which_browser')}}/browse.php?&type=files" target="main">
                                                {{ icon_html($_style['icon_files']) }} {{ManagerTheme::getLexicon('files_management')}}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        <li id="preview">
                            <a href="../" target="_blank" title="{{ManagerTheme::getLexicon('preview')}}">
                                {{ icon_html($_style['icon_desktop']) }}
                            </a>
                        </li>
                        <li id="account" class="dropdown account">
                            <a href="javascript:;" class="dropdown-toggle" onclick="return false;">
                                @if ($user['photo'])
                                    {{-- The value lands in a CSS url(): quote it and drop the characters that could
                                        close the function or start a new declaration, then let {{ }} escape the
                                        attribute itself. --}}
                                    <span class="icon photo" style="background-image: url('{{ EVO_SITE_URL . preg_replace('~[\'"()\\\\;\s]+~', '', (string) $user['photo']) }}');"></span>
                                @else
                                    <span class="icon">{{ icon_html($_style['icon_user']) }}</span>
                                @endif
                                <span class="username">{{ $user['username'] }}</span>
                            </a>
                            <ul class="dropdown-menu">
                                @if (evo()->hasPermission('change_password'))
                                    <li>
                                        <a onclick="" href="index.php?a=28" target="main">
                                            {{ icon_html($_style['icon_lock']) }} {{ManagerTheme::getLexicon('change_password')}}
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a href="index.php?a=8&_token={{ csrf_token() }}">
                                        {{ icon_html($_style['icon_logout']) }} {{ManagerTheme::getLexicon('logout')}}
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li id="theme">
                            <a id="treeMenu_theme_dark" onclick="evo.tree.toggleTheme(event)" title="{{ManagerTheme::getLexicon('manager_theme_mode_title')}}">
                                <span class="icon">{{ icon_html($_style['icon_theme']) }}</span>
                            </a>
                        </li>
                        @if (
                            evo()->hasPermission('settings') ||
                            evo()->hasPermission('view_eventlog') ||
                            evo()->hasPermission('logs') ||
                            evo()->hasPermission('help')
                        )
                            <li id="system" class="dropdown">
                                <a href="javascript:;" class="dropdown-toggle" title="{{ManagerTheme::getLexicon('system')}}" onclick="return false;">
                                    {{ icon_html($_style['icon_cogs']) }}
                                </a>
                                <ul class="dropdown-menu">
                                    @if (evo()->hasPermission('settings'))
                                        <li>
                                            <a href="index.php?a=17" target="main">
                                                {{ icon_html($_style['icon_sliders']) }} {{ManagerTheme::getLexicon('edit_settings')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('view_eventlog'))
                                        <li>
                                            <a href="index.php?a=70" target="main">
                                                {{ icon_html($_style['icon_calendar']) }} {{ManagerTheme::getLexicon('site_schedule')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('view_eventlog'))
                                        <li>
                                            <a href="index.php?a=114" target="main">
                                                {{ icon_html($_style['icon_info_triangle']) }} {{ManagerTheme::getLexicon('eventlog_viewer')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('logs'))
                                        <li>
                                            <a href="index.php?a=13" target="main">
                                                {{ icon_html($_style['icon_user_secret']) }} {{ManagerTheme::getLexicon('view_logging')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('settings'))
                                        <li>
                                            <a href="index.php?a=53" target="main">
                                                {{ icon_html($_style['icon_info_circle']) }} {{ManagerTheme::getLexicon('view_sysinfo')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('help'))
                                        <li>
                                            <a href="index.php?a=9" target="main">
                                                {{ icon_html($_style['icon_question_circle']) }} {{ManagerTheme::getLexicon('help')}}
                                            </a>
                                        </li>
                                    @endif
                                    @php
                                        // Metadata belongs to the active release, including after rollback.
                                        $buildFile = EVO_BASE_PATH . '.deploy-release.json';
                                        $buildData = is_readable($buildFile) ? json_decode(@file_get_contents($buildFile) ?: '', true) : [];
                                        $buildData = is_array($buildData) ? $buildData : [];
                                        $repositoryUrl = $buildData['repository_url'] ?? '';
                                        $repositoryParts = is_string($repositoryUrl) ? parse_url($repositoryUrl) : false;
                                        $validRepository = is_array($repositoryParts)
                                            && ($repositoryParts['scheme'] ?? '') === 'https'
                                            && !empty($repositoryParts['host'])
                                            && !isset($repositoryParts['user']) && !isset($repositoryParts['pass']);

                                        $buildTag = $buildData['tag'] ?? '';
                                        $buildSha = $buildData['sha'] ?? '';
                                        $buildTag = is_string($buildTag) ? trim($buildTag) : '';
                                        $buildSha = is_string($buildSha) && preg_match('/^[a-f0-9]{40}$/D', $buildSha) ? substr($buildSha, 0, 8) : '';
                                    @endphp
                                    @if ($validRepository && $buildTag !== '')
                                        <li>
                                            <a href="{{$repositoryUrl}}" target="_blank" rel="noopener noreferrer"
                                               title="{{$buildSha !== '' ? ManagerTheme::getLexicon('project_commit') . ': ' . $buildSha : ManagerTheme::getLexicon('project_repository')}}">
                                                @svg('tabler-git-branch', '', ['width' => 20, 'height' => 20, 'aria-hidden' => 'true', 'focusable' => 'false'])
                                                {{ManagerTheme::getLexicon('project_version')}} · {{$buildTag}}
                                            </a>
                                        </li>
                                    @endif
                                    @php
                                        $style = evo()->getConfig('settings_version') !== evo()->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
                                        echo '<li><span class="dropdown-item" title="' . evo()->getPhpCompat()->entities(evo()->getConfig('site_name')) . ' &ndash; ' . evo()->getVersionData('full_appname') . '" ' . $style . '>' . evo()->getVersionData('branch') . ' ' . evo()->getConfig('settings_version') . '</span></li>';
                                    @endphp
                                </ul>
                            </li>
                        @endif
                        @if (evo()->getConfig('show_fullscreen_btn'))
                            <li id="fullscreen">
                                <a href="javascript:;" onclick="toggleFullScreen();" id="toggleFullScreen" title="{{ManagerTheme::getLexicon('toggle_fullscreen')}}">
                                    {{ icon_html($_style['icon_expand']) }}
                                </a>
                            </li>
                        @endif
                    </ul>