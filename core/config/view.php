<?php
return [
    'paths' => [
        EVO_BASE_PATH . 'views/'
    ],
    'compiled' => EVO_STORAGE_PATH . 'blade',
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
