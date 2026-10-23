<!doctype html>
<html class="evo-ui-page" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Info</title>
    <link rel="stylesheet" href="{{ rtrim(EVO_SITE_URL, '/') }}/assets/modules/evo-ui/evo-ui.css">
    <style>
        body { padding: 12px; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; margin-bottom: 16px; }
        td, th { padding: 6px 9px; border-bottom: 1px solid var(--evo-ui-border); overflow-wrap: anywhere; vertical-align: top; }
        .e { width: 35%; color: var(--evo-ui-muted); }
        .h { background: var(--evo-ui-surface); }
        h1 { font-size: 20px; } h2 { font-size: 17px; }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; }
        img { max-width: 100%; height: auto; }
        a { color: var(--evo-ui-primary); }
    </style>
</head>
<body class="evo-ui-page">{{ $content }}</body>
</html>
