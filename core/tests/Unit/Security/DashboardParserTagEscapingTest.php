<?php

/*
|--------------------------------------------------------------------------
| Dashboard widgets are parsed after they are built
|--------------------------------------------------------------------------
|
| The welcome page goes through ManagerTheme::makeTemplate(), which runs the CMS parser over the
| merged placeholders, and the RecentInfo snippet's output is parsed again as well. Stored text
| that was only HTML-escaped (a page title, a username, a session IP, a feed headline) still ran
| [[snippets]], {{chunks}} and [(settings)] in the administrator's request; the online users table
| was not even HTML-escaped.
|
*/

it('escapes HTML and parser tags alike', function (string $input, string $expected) {
    expect(escape_html_and_tags($input))->toBe($expected);
})->with([
    'plain' => ['Home page', 'Home page'],
    'markup' => ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'],
    'attribute' => ['" onmouseover="x', '&quot; onmouseover=&quot;x'],
    'snippet' => ['[[Snippet? &a=`1`]]', '&#91;&#91;Snippet? &amp;a=&#96;1&#96;&#93;&#93;'],
    'uncached snippet' => ['[!Snippet!]', '&#91;!Snippet!&#93;'],
    'chunk' => ['{{chunk}}', '&#123;&#123;chunk&#125;&#125;'],
    'setting' => ['[(site_name)]', '&#91;(site_name)&#93;'],
    'field' => ['[*content*]', '&#91;*content*&#93;'],
    'placeholder' => ['[+ph+]', '&#91;+ph+&#93;'],
    'link' => ['[~1~]', '&#91;~1~&#93;'],
]);

it('returns an empty string for values that are not text', function ($value) {
    expect(escape_html_and_tags($value))->toBe('');
})->with([
    'null' => [null],
    'array' => [[['[[x]]']]],
]);

it('keeps numbers as text', function () {
    expect(escape_html_and_tags(42))->toBe('42');
});

it('escapes every stored value in the online users widget', function () {
    $view = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/2.blade.php');

    expect($view)->toContain("escape_html_and_tags(\$activeUser['username'])")
        ->and($view)->toContain('escape_html_and_tags($ip)')
        ->and($view)->toContain('escape_html_and_tags($currentaction)')
        ->and($view)->toContain("'username' => escape_html_and_tags(evo()->getLoginUserName())")
        ->and($view)->not->toMatch('/^\s*\$activeUser\[\'username\'\],\s*$/m')
        ->and($view)->not->toMatch('/^\s*\$ip,\s*$/m')
        ->and($view)->not->toMatch('/^\s*\$currentaction\s*$/m');
});

it('escapes feed items for the parser as well as for HTML', function () {
    $view = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/2.blade.php');

    expect($view)->toContain('$title = escape_html_and_tags((string) $item->title);')
        ->and($view)->toContain('$description = escape_html_and_tags($description);')
        ->and($view)->not->toContain('$title = e((string) $item->title);');
});

it('escapes editor-supplied fields of the recent resources widget for the parser', function () {
    $snippet = (string) file_get_contents(
        dirname(__DIR__, 4) . '/manager/media/style/default/snippets/welcome/RecentInfo.php'
    );

    foreach (['pagetitle', 'longtitle', 'description', 'introtext', 'alias', 'templatename'] as $field) {
        expect($snippet)->toMatch('/\$ph\[\'' . $field . '\'\] = [^;\n]*escape_html_and_tags\(/');
    }

    expect($snippet)->toContain("\$ph['username'] = escape_html_and_tags(")
        // the htmlentities modifier left the tags in place
        ->and($snippet)->not->toContain(':htmlentities+]')
        ->and($snippet)->not->toContain("entities(\$ph[");
});
