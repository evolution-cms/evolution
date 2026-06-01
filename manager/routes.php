<?php use Illuminate\Support\Facades\Route;

if (class_exists(\EvoUI\Support\LivewireManagerEndpoint::class)) {
    $evoUiLivewireEndpoint = static function (\Illuminate\Http\Request $request, ?string $path = null) {
        return app(\EvoUI\Support\LivewireManagerEndpoint::class)($request, $path);
    };

    Route::match(['GET', 'POST'], 'evo-ui/{path?}', $evoUiLivewireEndpoint)->where('path', '.*');
}

Route::match(['GET', 'POST'], '/', 'Actions@handleAction');
