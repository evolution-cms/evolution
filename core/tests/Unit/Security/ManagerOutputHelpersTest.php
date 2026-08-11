<?php

use EvolutionCMS\Controllers\Search;
use EvolutionCMS\ManagerTheme;
use Illuminate\Support\HtmlString;

/*
|--------------------------------------------------------------------------
| The producers that replaced `{!! !!}`
|--------------------------------------------------------------------------
|
| Each of these used to hand a raw string to a template that opted out of escaping. They now
| decide for themselves what is safe and return Htmlable, so the templates can use `{{ }}`.
|
*/

test('icon_markup escapes a class list into the class attribute', function () {
    expect(icon_markup('fa fa-cube'))->toBe('<i class="fa fa-cube"></i>');

    // A value that tries to close the attribute stays inside it.
    expect(icon_markup('fa" onmouseover="alert(1)'))
        ->toBe('<i class="fa&quot; onmouseover=&quot;alert(1)"></i>');
});

test('icon_markup passes ready made markup through and drops empty values', function () {
    $svg = '<svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>';

    expect(icon_markup($svg))->toBe($svg)
        ->and(icon_markup(''))->toBe('')
        ->and(icon_markup(null))->toBe('')
        ->and(icon_markup(['fa']))->toBe('');
});

test('icon_markup appends literal attributes supplied by the template', function () {
    expect(icon_markup('fa fa-link', ' aria-hidden="true"'))
        ->toBe('<i class="fa fa-link" aria-hidden="true"></i>');
});

test('icon_html is the Htmlable form and is not encoded again by Blade', function () {
    $icon = icon_html('fa fa-cube');

    expect($icon)->toBeInstanceOf(HtmlString::class)
        ->and(e($icon))->toBe('<i class="fa fa-cube"></i>');
});

test('sanitize_sort_direction accepts only the two valid keywords', function () {
    expect(sanitize_sort_direction('asc'))->toBe('ASC')
        ->and(sanitize_sort_direction(' DESC '))->toBe('DESC')
        ->and(sanitize_sort_direction('ASC', 'ASC'))->toBe('ASC');

    // Anything else is attacker controlled text and falls back to the default.
    foreach (['DESC"><script>alert(1)</script>', 'ASC, (SELECT 1)', '', null, ['ASC']] as $payload) {
        expect(sanitize_sort_direction($payload))->toBe('DESC');
    }

    expect(sanitize_sort_direction('nonsense', 'ASC'))->toBe('ASC');
});

test('sanitize_sort_column accepts only a plain identifier', function () {
    expect(sanitize_sort_column('pagetitle'))->toBe('pagetitle')
        ->and(sanitize_sort_column('menu_index'))->toBe('menu_index')
        ->and(sanitize_sort_column('X1'))->toBe('X1');

    foreach ([
        'createdon"><img src=x onerror=alert(1)>',
        'id`, (SELECT password FROM users)',
        'site_content.id',
        '1 UNION SELECT 1',
        '',
        null,
    ] as $payload) {
        expect(sanitize_sort_column($payload))->toBe('createdon');
    }

    expect(sanitize_sort_column('bad value', 'menuindex'))->toBe('menuindex');
});

test('the request derived link fragment is inert once the parts are normalised', function () {
    // This is exactly how the resource page assembles the sort suffix it prints back into links.
    $request = [
        'dir' => 'DESC" onmouseover="alert(1)',
        'sort' => 'createdon"><script>alert(1)</script>',
        'page' => '2"><script>alert(1)</script>',
    ];

    $addPath = '&dir=' . sanitize_sort_direction(get_by_key($request, 'dir'))
        . '&sort=' . sanitize_sort_column(get_by_key($request, 'sort'))
        . '&page=' . (int) $request['page'];

    expect($addPath)->toBe('&dir=DESC&sort=createdon&page=2')
        ->and($addPath)->not->toContain('<')
        ->and($addPath)->not->toContain('"');
});

test('lexiconHtml keeps the markup of the entry and escapes what is substituted into it', function () {
    $theme = managerThemeWith([
        'lexicon' => [
            'password_msg' => 'The new password for <b>:username</b> is <b>:password</b><br>',
            'update_tree_time' => 'Updated %s resources in %s seconds',
        ],
    ]);

    $message = $theme->lexiconHtml('password_msg', [
        'username' => '<img src=x onerror=alert(1)>',
        'password' => "p@ss'&<>",
    ]);

    expect($message)->toBeInstanceOf(HtmlString::class);

    // The entry keeps its own markup...
    expect((string) $message)->toContain('<b>')
        ->and((string) $message)->toContain('<br>');

    // ...the substituted username cannot add any: the payload is text inside the <b> element.
    expect((string) $message)->not->toContain('<img')
        ->and((string) $message)->not->toMatch('~<[a-zA-Z][^>]*\s[^>]*>~');

    // ...and the generated password is still readable once the browser decodes it.
    expect(html_entity_decode(strip_tags((string) $message), ENT_QUOTES, 'UTF-8'))
        ->toContain("p@ss'&<>");
});

test('lexiconHtml supports the positional entries that used sprintf', function () {
    $theme = managerThemeWith([
        'lexicon' => ['update_tree_time' => 'Updated %s resources in %s seconds'],
    ]);

    expect((string) $theme->lexiconHtml('update_tree_time', [12, '0.4']))
        ->toBe('Updated 12 resources in 0.4 seconds');
});

test('styleHtml returns theme markup as Htmlable and never an array', function () {
    $theme = managerThemeWith([
        'style' => [
            'actionbuttons' => ['dynamic' => ['save' => '<div id="actions"><a>Save</a></div>']],
            'icon_cube' => 'fa fa-cube',
        ],
    ]);

    expect((string) $theme->styleHtml('actionbuttons.dynamic.save'))
        ->toBe('<div id="actions"><a>Save</a></div>');

    expect(e($theme->styleHtml('icon_cube')))->toBe('fa fa-cube');

    // A key that resolves to an array (or is missing) must not leak "Array" into the page.
    expect((string) $theme->styleHtml('actionbuttons'))->toBe('')
        ->and((string) $theme->styleHtml('nothing_here'))->toBe('');
});

test('search result titles are escaped before the highlight markup is added', function () {
    $search = searchControllerWithCharset('UTF-8');

    $highlight = function (string $text, string $needle) use ($search) {
        $method = new ReflectionMethod(Search::class, 'highlightingCoincidence');
        $method->setAccessible(true);

        return $method->invoke($search, $text, $needle);
    };

    // A stored page title carrying a payload, searched for by a harmless term.
    $result = $highlight('Home <script>alert(1)</script>', 'Home');

    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and((string) $result)->toBe('<span class="text-danger">Home</span> &lt;script&gt;alert(1)&lt;/script&gt;');

    // A payload in the search term itself cannot introduce markup either.
    $reflected = (string) $highlight('Home', '"><img src=x onerror=alert(1)>');

    expect($reflected)->not->toContain('<img')
        ->and($reflected)->toBe('Home');

    // An empty search term returns the escaped text without any highlight element.
    expect((string) $highlight('a & b', ''))->toBe('a &amp; b');
});

/**
 * Build a ManagerTheme with only the presentation data the tested methods need.
 */
function managerThemeWith(array $properties): ManagerTheme
{
    $theme = (new ReflectionClass(ManagerTheme::class))->newInstanceWithoutConstructor();

    foreach ($properties as $name => $value) {
        $property = new ReflectionProperty(ManagerTheme::class, $name);
        $property->setAccessible(true);
        $property->setValue($theme, $value);
    }

    return $theme;
}

/**
 * Build a Search controller whose manager theme stub only answers the charset lookup.
 *
 * The controller is created without running its constructor and the (untyped) managerTheme
 * property is injected directly, so the test needs no container and no database.
 */
function searchControllerWithCharset(string $charset): Search
{
    $core = new class($charset) {
        public function __construct(private string $charset)
        {
        }

        public function getConfig($name = '', $default = null)
        {
            return $name === 'evo_charset' ? $this->charset : $default;
        }
    };

    $theme = new class($core) {
        public function __construct(private object $core)
        {
        }

        public function getCore(): object
        {
            return $this->core;
        }
    };

    $search = (new ReflectionClass(Search::class))->newInstanceWithoutConstructor();

    $property = new ReflectionProperty(Search::class, 'managerTheme');
    $property->setAccessible(true);
    $property->setValue($search, $theme);

    return $search;
}
