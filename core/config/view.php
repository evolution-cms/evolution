<?php
return [
    'paths' => [
        EVO_BASE_PATH . 'views/'
    ],
    'compiled' => EVO_STORAGE_PATH . 'blade',

    /*
    |--------------------------------------------------------------------------
    | Template file engines
    |--------------------------------------------------------------------------
    |
    | Which view engines the template form offers to scaffold a file for. A
    | document whose template alias resolves to a file under one of the view
    | paths above is rendered by that file's engine instead of by the parser,
    | and Laravel's view factory already resolves any extension registered with
    | it - so this list is not what makes an engine work, only what the manager
    | is willing to create a file for. Engines opt in: an extension nobody
    | declared here stays out of the UI, and one declared without its engine
    | actually being registered is dropped rather than offered.
    |
    | 'processor' names the [(chunk_processor)] value an engine belongs to, and
    | only decides which entry the form preselects. A plugin adds its own from
    | its service provider:
    |
    |   config(['view.template_engines' => array_merge(
    |       config('view.template_engines', []),
    |       ['latte' => ['label' => 'Latte', 'processor' => 'aLatteX']]
    |   )]);
    |
    */
    'template_engines' => [
        'blade.php' => ['label' => 'Blade', 'processor' => null],
        'php' => ['label' => 'PHP', 'processor' => null],
    ],
    'directive' => [
        //----------
        /**
         * @deprecated
         * @since 3.5.3
         *
         * It's not using anywhere.
         *
         * @todo [remove@3.7] Remove in Evolution CMS 3.7
         */
        'csrf' => [EvolutionCMS\Support\BladeDirective::class, 'csrf'],
        'evoLang' => [EvolutionCMS\Support\BladeDirective::class, 'evoLang'],
        'evoStyle' => [EvolutionCMS\Support\BladeDirective::class, 'evoStyle'],
        'evoAdminLang' => [EvolutionCMS\Support\BladeDirective::class, 'evoAdminLang'],
        'evoCharset' => [EvolutionCMS\Support\BladeDirective::class, 'evoCharset'],
        'evoAdminThemeUrl' => [EvolutionCMS\Support\BladeDirective::class, 'evoAdminThemeUrl'],
        'evoAdminThemeName' => [EvolutionCMS\Support\BladeDirective::class, 'evoAdminThemeName'],
        //----------
    ]
];
