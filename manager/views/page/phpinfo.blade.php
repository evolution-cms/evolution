@extends('manager::template.page')
@section('content')
    <style type="text/css">
        body { padding: 1.25rem; }
        pre { margin: 0; font-family: monospace; }
        a:link { color: #000099; text-decoration: none; background-color: #f7f7f7; }
        a:hover { text-decoration: underline; }
        table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; border-radius: 0.15rem; background-color: #fff; box-shadow: 0 0 0.1rem 0 rgba(0, 0, 0, 0.1), 0 0.1rem 0.3rem rgba(0, 0, 0, 0.05); }
        table:last-child { margin-bottom: 0 }
        .center { text-align: center; }
        .center table { margin-left: auto; margin-right: auto; text-align: left; }
        .center th { text-align: center !important; }
        td, th { border: none; vertical-align: baseline; padding: .25rem; }
        h1 { text-align: left; margin: 0 0 .5rem; padding: 0; }
        h2 { text-align: left; margin: 0 0 .5rem; padding: 0; }
        .p { text-align: left; }
        .e { width: 25%; max-width: 22rem; background-color: #efeff6; border-bottom: 1px solid #e0e0ec; color: #333333; }
        .h { background-color: #bcbcd6; border-bottom: 2px solid #aaaac7; font-weight: bold; color: #333333; }
        .h h1 { width: 90%; font-size: 1.25rem; }
        .v { width: auto; color: #333333; border-bottom: 1px solid #eceeef; word-break: break-all; }
        .vr { background-color: #cccccc; text-align: right; color: #333333; }
        tr:hover .e { background-color: #eaeaf5; }
        tr:hover .v { background-color: #fafafa; }
        tr:last-child .e, tr:last-child .v { border-bottom: none }
        img { float: right; border: none; }
        hr { background-color: #cccccc; border: 0px; height: 1px; color: #333333; }
        p { padding: 0; margin: 1rem 1rem 0 }
        p:last-child { margin-bottom: 1rem }
    </style>
    <?php
        ob_start();
        phpinfo();
        $pinfo = ob_get_contents();
        ob_end_clean();
        $pinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $pinfo);
        $pinfo = str_replace('<div class="center">', '<div>', $pinfo);
        $pinfo = str_replace('width="600"', 'width="90%"', $pinfo);
        $pinfo = str_replace('src,input', 'src, input', $pinfo);
        $pinfo = preg_replace_callback('/<a\s+href="([^"]*)"([^>]*)>/i', static function ($matches) {
            $href = $matches[1];
            $attributes = $matches[2];

            if (preg_match('~^(https?:|mailto:)~i', $href) === 1) {
                return '<a href="' . $href . '"' . $attributes . ' target="_blank" rel="noopener noreferrer">';
            }

            return '<a href="javascript:;"' . $attributes . ' onclick="return false;">';
        }, $pinfo);
    ?>
    {!! $pinfo !!}
@endsection
