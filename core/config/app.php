<?php return [
    'env' => 'production',
    'debug' => false,

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */
    'fallback_locale' => 'en',
    'providers' => [
        /**
         * Keys are needed only for the convenience of replace ServiceProvider class
         * via custom/config/app/providers/*.php
         */
        'Bootstrap_ExceptionHandler' => EvolutionCMS\Providers\ExceptionHandlerServiceProvider::class,
        'Console_Artisan' => EvolutionCMS\Providers\ArtisanServiceProvider::class,
        'Console_Migration' => Illuminate\Database\MigrationServiceProvider::class,
        'Console_Composer' => EvolutionCMS\Providers\ComposerServiceProvider::class,
        'Laravel_View' => Illuminate\View\ViewServiceProvider::class,
        'Laravel_Database' => Illuminate\Database\DatabaseServiceProvider::class,
        'Laravel_Filesystem' => Illuminate\Filesystem\FilesystemServiceProvider::class,
        'Laravel_Cache' =>  Illuminate\Cache\CacheServiceProvider::class,
        'Laravel_Redis' => Illuminate\Redis\RedisServiceProvider::class,
        'Laravel_Lang' => Illuminate\Translation\TranslationServiceProvider::class,
        'Laravel_Validator' => Illuminate\Validation\ValidationServiceProvider::class,
        'Evolution_LogContext' => EvolutionCMS\Providers\LogContextServiceProvider::class,
        'Evolution_Auth' => EvolutionCMS\Providers\AuthServiceProvider::class,
        'Evolution_Observers' => EvolutionCMS\Providers\ObserversServiceProvider::class,
        'Evolution_Pagination' => EvolutionCMS\Providers\PaginationServiceProvider::class,
        'Evolution_Events' => EvolutionCMS\Providers\EventServiceProvider::class,
        'Evolution_DBAPI' => EvolutionCMS\Providers\DatabaseServiceProvider::class,
        'Evolution_DEPRECATED' => EvolutionCMS\Providers\DeprecatedCoreServiceProvider::class,
        'Evolution_MODxMailer' => EvolutionCMS\Providers\MailServiceProvider::class,
        'Evolution_makeTable' => EvolutionCMS\Providers\MakeTableServiceProvider::class,
        'Evolution_ManagerAPI' => EvolutionCMS\Providers\ManagerApiServiceProvider::class,
        'Evolution_MODIFIERS' => EvolutionCMS\Providers\ModifiersServiceProvider::class,
        'Evolution_phpass' => EvolutionCMS\Providers\PasswordHashServiceProvider::class,
        'Evolution_PHPCOMPAT' => EvolutionCMS\Providers\PhpCompatServiceProvider::class,
        'Evolution_DocBlock' => EvolutionCMS\Providers\DocBlockServiceProvider::class,
        'Evolution_ManagerTheme' => EvolutionCMS\Providers\ManagerThemeServiceProvider::class,
        'Evolution_UrlProcessor' => EvolutionCMS\Providers\UrlProcessorServiceProvider::class,
        'Evolution_TemplateProcessor' => EvolutionCMS\Providers\TemplateProcessorServiceProvider::class,
        'Evolution_HelperProcessor' => EvolutionCMS\Providers\HelperProcessorServiceProvider::class,
        'Evolution_Blade' => EvolutionCMS\Providers\BladeServiceProvider::class,
        'Evolution_UserManager' => EvolutionCMS\UserManager\Providers\UserManagerServiceProvider::class,
        'Evolution_DocumentManager' => EvolutionCMS\DocumentManager\Providers\DocumentManagerServiceProvider::class,
        'Evolution_Routing' => EvolutionCMS\Providers\RoutingServiceProvider::class,
        'Evolution_Config' => EvolutionCMS\Providers\ConfigServiceProvider::class,
        'Evolution_Session' => EvolutionCMS\Providers\SessionServiceProvider::class,
        'Evolution_SystemTasks' => EvolutionCMS\Providers\SystemTasksServiceProvider::class,
        'Evolution_Tailwind' => EvolutionCMS\Providers\TailwindServiceProvider::class,
        'Fix_DLTemplate' => EvolutionCMS\Providers\DLTemplateServiceProvider::class,
        'Fix_Phx' => EvolutionCMS\Providers\PhxServiceProvider::class,
        'Fix_ModResource' => EvolutionCMS\Providers\ModResourceServiceProvider::class,
        'Fix_ModUsers' => EvolutionCMS\Providers\ModUsersServiceProvider::class,
        'Fix_Fs' => EvolutionCMS\Providers\FsServiceProvider::class,
        'Blade_Icons' => EvolutionCMS\Providers\BladeIconsAdapterServiceProvider::class,
        'Blade_Tabler' => secondnetwork\TablerIcons\BladeTablerIconsServiceProvider::class,
    ],

    'aliases' => [
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Str' => Illuminate\Support\Str::class,

        /**
         * EvolutionCMS
         * @TODO DBAPI, MakeTable and other will be added at version 3.x
         */
        'Auth' => EvolutionCMS\Facades\AuthServices::class,
        'Config' => EvolutionCMS\Facades\ConfigService::class,
        'Evo' => Illuminate\Support\Facades\App::class,
        'DocBlock' => EvolutionCMS\Facades\DocBlock::class,
        'ManagerTheme' => EvolutionCMS\Facades\ManagerTheme::class,
        'UrlProcessor' => EvolutionCMS\Facades\UrlProcessor::class,
        'TemplateProcessor' => EvolutionCMS\Facades\TemplateProcessor::class,
        'Helper' => EvolutionCMS\Facades\HelperProcessor::class,
        'UserManager' => EvolutionCMS\UserManager\Facades\UserManager::class,
        'DocumentManager' => EvolutionCMS\DocumentManager\Facades\DocumentManager::class,
        'Tailwind' => EvolutionCMS\Facades\Tailwind::class,
    ],

    'middleware' => [
        'mgr' => [
            Illuminate\Session\Middleware\StartSession::class,
            EvolutionCMS\Middleware\SessionProxy::class,
            EvolutionCMS\Middleware\VerifyCsrfToken::class,
            EvolutionCMS\Middleware\Manager::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | The application's global HTTP middleware stack
        |--------------------------------------------------------------------------
        |
        | These core middleware are run during every request to your application.
        | You should not edit this list,
        | for custom middleware see file core/custom/config/middleware.php.
        |
        */
        'global' => [
            Illuminate\Session\Middleware\StartSession::class,
            EvolutionCMS\Middleware\SessionProxy::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Route middleware
        |--------------------------------------------------------------------------
        |
        | These middleware may be assigned to groups or used individually.
        | You should not edit this list,
        | for custom aliases see file core/custom/config/middleware.php.
        |
        */
        'aliases' => [
            'csrf' => EvolutionCMS\Middleware\VerifyCsrfToken::class,
            'authtoken' => EvolutionCMS\Middleware\CheckAuthToken::class,
            'managerauth' => EvolutionCMS\Middleware\CheckManagerAuth::class,
            'bindings' => Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ],
];
