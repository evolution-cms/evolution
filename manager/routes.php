<?php

use Illuminate\Support\Facades\Route;
use EvoUI\Support\LivewireManagerEndpoint;

if (class_exists(LivewireManagerEndpoint::class)) {
    Route::match(['GET', 'POST'], 'evo-ui/{path?}', LivewireManagerEndpoint::class)->where('path', '.*');
}

Route::match(['GET', 'POST'], '/', 'Actions@handleAction');
