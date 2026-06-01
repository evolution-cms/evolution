<?php

use Illuminate\Support\Facades\Route;
use EvoUI\Support\LivewireManagerEndpoint;

if (class_exists(LivewireManagerEndpoint::class)) {
    Route::match(['GET', 'POST'], 'evo-ui/{path?}', function (?string $path = null) {
        return app(LivewireManagerEndpoint::class)(request(), $path);
    })->where('path', '.*');
}

Route::match(['GET', 'POST'], '/', 'Actions@handleAction');
