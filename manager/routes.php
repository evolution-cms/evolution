<?php

use Illuminate\Support\Facades\Route;
use EvoUI\Support\LivewireManagerEndpoint;

if (class_exists(LivewireManagerEndpoint::class)) {
    // Evo UI also registers the manager-prefixed PATH_INFO alias. Replace both
    // forms inside this mgr group so neither can bypass login/access/CSRF checks.
    $endpointRoutes = ['evo-ui/{path?}'];
    $managerDir = defined('MGR_DIR') ? trim((string) MGR_DIR, '/') : '';
    if ($managerDir !== '') {
        $endpointRoutes[] = $managerDir . '/evo-ui/{path?}';
    }
    foreach ($endpointRoutes as $endpointRoute) {
        Route::match(['GET', 'POST'], $endpointRoute, function (?string $path = null) {
            return app(LivewireManagerEndpoint::class)(request(), $path);
        })->where('path', '.*');
    }
}

Route::match(['GET', 'POST'], '/', 'Actions@handleAction');
