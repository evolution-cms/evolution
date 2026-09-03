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
    /*
    |--------------------------------------------------------------------------
    | Chunk files
    |--------------------------------------------------------------------------
    |
    | Where a chunk keeps its code.
    |
    | Under views/, not assets/: the root .htaccess passes ^assets/ straight to
    | the filesystem, and chunks hold extras' configuration, credentials
    | included. views/ ships denied and already holds template files.
    | (@FILE still searches assets/chunks/; existing files are not moved.)
    |
    | A chunk is HTML with placeholders some parser may know - the file decides
    | neither which parser nor whether one runs. Hence one format, and a form
    | that only offers the list once something adds a second entry:
    |
    |   config(['view.chunk_formats' => array_merge(
    |       config('view.chunk_formats', []),
    |       ['tpl' => 'Template']
    |   )]);
    |
    */
    'chunk_path' => EVO_BASE_PATH . 'views/chunks/',
    'chunk_formats' => [
        'html' => 'HTML',
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
