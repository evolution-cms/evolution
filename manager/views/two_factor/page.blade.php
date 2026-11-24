{{-- Stand-alone page: the website, and a manager login template without a recognizable form. A site overrides it for the website with views/two_factor.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('global.two_factor_title') }} - {{ evo()->getConfig('site_name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="same-origin">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; padding: 8vh 1rem 2rem; color: #333; background: #f2f3f5; font: 1rem/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { box-sizing: border-box; max-width: 30rem; margin: 0 auto; padding: 2rem; border-radius: .5rem; background: #fff; box-shadow: 0 .25rem 1.5rem rgba(0, 0, 0, .08); }
        .site { margin: 0 0 1.5rem; font-size: .9rem; color: #666; }
        .form-control { box-sizing: border-box; padding: .6rem 1rem; border: 1px solid #c9ced6; background: #fff; color: inherit; }
        .btn { display: inline-block; padding: .55rem 1.25rem; border: 0; border-radius: 20px; font: inherit; color: #fff; background: #2e7d32; text-decoration: none; cursor: pointer; }
        .btn:hover { background: #256528; }
        .btn-secondary { color: #333; background: #e4e7eb; }
        .btn-secondary:hover { background: #d5dae0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        td { padding: .5rem .5rem .5rem 0; border-bottom: 1px solid #e4e7eb; }
    </style>
</head>
<body>
<main>
    <p class="site">{{ evo()->getConfig('site_name') }}</p>
    {!! $content !!}
</main>
</body>
</html>
