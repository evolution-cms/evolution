<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

/*
|--------------------------------------------------------------------------
| safe_html()
|--------------------------------------------------------------------------
|
| safe_html() is the replacement for `{!! !!}` on stored values that are allowed to carry simple
| formatting: everything is escaped first, then a fixed list of attribute-free tags is restored.
| The tests below drive the helper with independent sample data - none of them look at the
| templates that call it.
|
*/

test('formatting written by the CMS survives, injected script markup does not', function () {
    // A message in the shape logEvent() writes: CMS formatting plus a value from the request.
    $stored = 'Import failed<br />'
        . '<pre>row 12: <script>alert(document.cookie)</script></pre>';

    $rendered = (string) safe_html($stored);

    // The formatting the CMS itself produced is still markup...
    expect($rendered)->toContain('<br />')
        ->and($rendered)->toContain('<pre>')
        ->and($rendered)->toContain('</pre>');

    // ...but the injected element is inert text, not an element.
    expect($rendered)->not->toContain('<script>')
        ->and($rendered)->not->toContain('</script>')
        ->and($rendered)->toContain('&lt;script&gt;alert(document.cookie)&lt;/script&gt;');
});

test('attributes cannot survive the allow list', function () {
    $payloads = [
        '<img src=x onerror=alert(1)>',
        '<svg/onload=alert(1)>',
        '<a href="javascript:alert(1)">click</a>',
        '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
        '<body onload=alert(1)>',
        '<div style="background:url(javascript:alert(1))">x</div>',
        '<span onmouseover="alert(1)">hover</span>',
    ];

    foreach ($payloads as $payload) {
        $rendered = (string) safe_html($payload);

        // The payload text may still be readable - it is now content, not markup. What matters is
        // that no element with an attribute is left in the output, because every attack above
        // needs one (onerror=, href=, srcdoc=, style=, onmouseover=).
        expect($rendered)->not->toMatch('~<[a-zA-Z][^>]*\s[^>]*>~');

        // ...and none of the dangerous element names survive as an element.
        foreach (['img', 'svg', 'a', 'iframe', 'body', 'script'] as $tag) {
            expect($rendered)->not->toContain('<' . $tag)
                ->and($rendered)->not->toContain('</' . $tag . '>');
        }
    }
});

test('an allow-listed tag name is only restored when it carries no attributes', function () {
    // `span` and `div` are on the allow list, so an attacker will try to smuggle attributes in.
    $rendered = (string) safe_html('<span class="x" onclick="alert(1)">a</span><span>b</span>');

    expect($rendered)->toBe('&lt;span class=&quot;x&quot; onclick=&quot;alert(1)&quot;&gt;a</span><span>b</span>');
});

test('closing and self-closing forms are restored, in any letter case', function () {
    expect((string) safe_html('a<br>b<BR/>c<br />d<HR>e'))
        ->toBe('a<br>b<BR/>c<br />d<HR>e');
});

test('text is escaped exactly once', function () {
    // Plain text: encoded once, and only once.
    expect((string) safe_html('Tom & Jerry'))->toBe('Tom &amp; Jerry');

    // Text that already carries entities keeps its meaning instead of being encoded again -
    // this is what makes the helper safe to apply to rows written by older versions.
    expect((string) safe_html('Tom &amp; Jerry said &#039;hi&#039;'))
        ->toBe('Tom &amp; Jerry said &#039;hi&#039;');

    // And applying it twice does not change the result either.
    $once = (string) safe_html('5 < 6 & "quoted"');
    expect((string) safe_html($once))->toBe($once);
});

test('a custom allow list is honoured and an empty one escapes everything', function () {
    expect((string) safe_html('<b>bold</b><br>', ['br']))
        ->toBe('&lt;b&gt;bold&lt;/b&gt;<br>');

    expect((string) safe_html('<b>bold</b><br>', []))
        ->toBe('&lt;b&gt;bold&lt;/b&gt;&lt;br&gt;');
});

test('a bogus tag name in the allow list cannot open a hole', function () {
    // If a caller passes something that is not a bare tag name it is dropped rather than
    // spliced into the restore pattern.
    $rendered = (string) safe_html('<script>alert(1)</script>', ['script[^>]*', 'b']);

    expect($rendered)->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('non string input degrades to an empty value instead of leaking a type error', function () {
    expect((string) safe_html(null))->toBe('')
        ->and((string) safe_html(['<script>alert(1)</script>']))->toBe('')
        ->and((string) safe_html(42))->toBe('42');
});

test('the result is Htmlable so Blade prints it without encoding it a second time', function () {
    $result = safe_html('a & b<br>');

    expect($result)->toBeInstanceOf(HtmlString::class)
        ->and($result)->toBeInstanceOf(Htmlable::class);

    // e() is what `{{ }}` compiles to. Htmlable values pass through untouched.
    expect(e($result))->toBe('a &amp; b<br>');
});

test('an already sanitised value is not sanitised twice', function () {
    $once = safe_html('<b>x</b> & <script>alert(1)</script>');

    expect((string) safe_html($once))->toBe((string) $once);
});

/*
|--------------------------------------------------------------------------
| sanitize_rich_html()
|--------------------------------------------------------------------------
|
| Used where the stored value is a whole document rather than a caption - today the Event Log.
| It rebuilds the markup under an allow list instead of escaping it, so the page keeps looking
| the way it did while nothing executable survives.
|
*/

test('presentation attributes are kept', function () {
    $html = '<table class="grid" width="100%"><tr class="gridItem" valign="top">'
        . '<td colspan="2" style="color:red;padding:8px">cell</td></tr></table>';

    $out = (string) sanitize_rich_html($html);

    expect($out)->toContain('class="grid"')
        ->and($out)->toContain('width="100%"')
        ->and($out)->toContain('valign="top"')
        ->and($out)->toContain('colspan="2"')
        ->and($out)->toContain('color:red')
        ->and($out)->toContain('padding:8px')
        ->and($out)->toContain('cell');
});

test('event handlers are removed while the element survives', function () {
    $out = (string) sanitize_rich_html('<div class="x" onclick="alert(1)" onmouseover="alert(2)">text</div>');

    expect($out)->toBe('<div class="x">text</div>');
});

test('executable and resource loading elements are removed with their content', function () {
    foreach (['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg'] as $tag) {
        $out = (string) sanitize_rich_html('<p>before</p><' . $tag . '>payload</' . $tag . '><p>after</p>');

        expect($out)->toBe('<p>before</p><p>after</p>');
    }
});

test('an unknown element is unwrapped so its text stays visible', function () {
    expect((string) sanitize_rich_html('<marquee>still readable</marquee>'))
        ->toBe('still readable');
});

test('only safe link schemes survive', function () {
    expect((string) sanitize_rich_html('<a href="https://example.test/x">ok</a>'))
        ->toContain('href="https://example.test/x"');

    expect((string) sanitize_rich_html('<a href="index.php?a=3">rel</a>'))
        ->toContain('href="index.php?a=3"');

    foreach ([
        'javascript:alert(1)',
        'JaVaScRiPt:alert(1)',
        "java	script:alert(1)",
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
    ] as $href) {
        $out = (string) sanitize_rich_html('<a href="' . htmlspecialchars($href, ENT_QUOTES) . '">x</a>');

        expect($out)->not->toContain('href=')
            ->and($out)->toContain('x');
    }
});

test('style declarations that can fetch or evaluate are dropped', function () {
    foreach ([
        'background:url(javascript:alert(1))',
        'background-image:url(//evil.test/beacon.png)',
        'width:expression(alert(1))',
        'color:red;/*x*/behavior:url(#default#time2)',
    ] as $style) {
        $out = (string) sanitize_rich_html('<div style="' . $style . '">t</div>');

        expect($out)->not->toContain('url(')
            ->and($out)->not->toContain('expression(')
            ->and($out)->toContain('t');
    }

    // A plain presentational declaration is untouched.
    expect((string) sanitize_rich_html('<div style="color:#333;font-weight:bold">t</div>'))
        ->toContain('color:#333;font-weight:bold');
});

test('comments are dropped so conditional markup cannot hide in them', function () {
    expect((string) sanitize_rich_html('<p>a</p><!--[if IE]><script>alert(1)</script><![endif]--><p>b</p>'))
        ->toBe('<p>a</p><p>b</p>');
});

test('already encoded entities are not encoded again', function () {
    $out = (string) sanitize_rich_html('<pre>SQL &gt; SELECT 1 &amp; 2</pre>');

    expect($out)->toBe('<pre>SQL &gt; SELECT 1 &amp; 2</pre>');
});

test('the result is Htmlable and empty input stays empty', function () {
    expect(sanitize_rich_html('<b>x</b>'))->toBeInstanceOf(HtmlString::class)
        ->and((string) sanitize_rich_html(''))->toBe('')
        ->and((string) sanitize_rich_html(null))->toBe('')
        ->and((string) sanitize_rich_html(['<b>x</b>']))->toBe('');
});

test('sanitising twice changes nothing', function () {
    $once = (string) sanitize_rich_html('<table class="grid"><tr><td style="color:red">x</td></tr></table>');

    expect((string) sanitize_rich_html($once))->toBe($once);
});

/**
 * Re-parse sanitised markup and report anything that could still execute or fetch.
 *
 * Asserting on the DOM rather than on the output string means a payload cannot pass by being
 * spelled differently - the check looks at what a browser would actually build.
 *
 * @return array<int, string> Problems found, empty when the markup is inert.
 */
function auditSanitisedMarkup(string $html): array
{
    if (trim($html) === '') {
        return [];
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML(
        '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>'
        . '<body>' . $html . '</body></html>',
        LIBXML_NONET
    );
    libxml_clear_errors();

    $issues = [];

    foreach ((new DOMXPath($document))->query('//body//*') as $element) {
        $tag = strtolower($element->nodeName);

        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
            'form', 'img', 'link', 'meta', 'base', 'template', 'noscript'], true)) {
            $issues[] = 'element <' . $tag . '>';
        }

        foreach ($element->attributes ?? [] as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = (string) $attribute->nodeValue;
            $probe = strtolower(preg_replace('~[\x00-\x20]+~', '', $value));

            if (str_starts_with($name, 'on')) {
                $issues[] = 'handler ' . $name;
            }

            if (preg_match('~^(javascript|data|vbscript|blob|about|file):~', $probe)) {
                $issues[] = 'scheme in ' . $name;
            }

            if ($name === 'style' && preg_match('~url\(|expression\(|behavior|binding|@import|progid~i', $value)) {
                $issues[] = 'css in style';
            }

            if (in_array($name, ['srcdoc', 'formaction', 'xlink:href'], true)) {
                $issues[] = 'attribute ' . $name;
            }
        }
    }

    return array_values(array_unique($issues));
}

test('no handler, scheme or CSS fetch survives any spelling', function () {
    $payloads = [
        // Event handlers, including onload, in every shape the parser accepts.
        '<div onload="alert(1)">x</div>',
        '<div OnLoAd="alert(1)">x</div>',
        "<div on\nload=\"alert(1)\">x</div>",
        "<div\tonload=alert(1)>x</div>",
        '<div onload=alert(1) class="keep">x</div>',
        '<body onload="alert(1)">x</body>',
        '<svg onload="alert(1)"></svg>',
        '<img src=1 onload=alert(1)>',
        '<table onload="alert(1)" class="grid"><tr><td onmouseenter=alert(1)>c</td></tr></table>',
        '<p onpointerover=alert(1) onfocus=alert(1) autofocus>x</p>',
        '<div ONCLICK="alert(1)">x</div>',
        // CSS injection: fetching, evaluating and legacy IE vectors.
        '<div style="background:url(//evil.test/x.png)">x</div>',
        '<div style="background-image:url(&quot;//evil.test&quot;)">x</div>',
        '<div style="width:expression(alert(1))">x</div>',
        '<div style="-moz-binding:url(//evil.test/x.xml#x)">x</div>',
        '<div style="behavior:url(#default#time2)">x</div>',
        '<div style="background:\75rl(//evil.test)">x</div>',
        '<div style="color:red;/**/background:url(//evil.test)">x</div>',
        '<div style="background:URL(//evil.test)">x</div>',
        '<div style="@import url(//evil.test)">x</div>',
        '<div style="list-style-image:url(//evil.test)">x</div>',
        '<div style="cursor:url(//evil.test),auto">x</div>',
        '<div style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=&#39;//evil.test&#39;)">x</div>',
        '<div style="color:red" onload="alert(1)">x</div>',
        // URL schemes, including obfuscated spellings.
        '<a href=" javascript:alert(1)">x</a>',
        '<a href="jav&#x0A;ascript:alert(1)">x</a>',
        '<a href="&#106;avascript:alert(1)">x</a>',
        '<a href="blob:https://x/y">x</a>',
        '<a href="about:blank">x</a>',
        // Mutation XSS classics.
        '<noscript><p title="</noscript><img src=x onerror=alert(1)>">',
        '<form><math><mtext></form><form><mglyph><style></math><img src onerror=alert(1)>',
        '<svg><style><img src=x onerror=alert(1)></style></svg>',
        '<template><script>alert(1)</script></template>',
        '<a href="x">a</a><iframe srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;"></iframe>',
        '<button formaction="javascript:alert(1)">x</button>',
        '<a xlink:href="javascript:alert(1)">x</a>',
        '<div data-x="javascript:alert(1)">x</div>',
    ];

    foreach ($payloads as $payload) {
        $sanitised = (string) sanitize_rich_html($payload);

        expect(auditSanitisedMarkup($sanitised))->toBe([]);

        // Sanitising the result again must not produce new markup: if re-parsing mutated the
        // output, a payload could reappear on a second pass (the mXSS pattern).
        $twice = (string) sanitize_rich_html($sanitised);

        expect($twice)->toBe($sanitised)
            ->and(auditSanitisedMarkup($twice))->toBe([]);
    }
});

test('the sanitiser keeps the harmless parts of an attacked report', function () {
    $out = (string) sanitize_rich_html(
        '<table onload="alert(1)" class="grid"><tr><td onmouseenter=alert(1)>c</td></tr></table>'
    );

    expect($out)->toBe('<table class="grid"><tr><td>c</td></tr></table>');
});

/*
|--------------------------------------------------------------------------
| sanitize_inline_html()
|--------------------------------------------------------------------------
|
| Used for element descriptions and TV captions - single-line fields that are rendered inside a
| table cell or a label, and that used to be printed raw.
|
*/

test('inline formatting an operator may have typed keeps working', function () {
    $description = 'Renders the footer with <b>copyright</b> and a <a href="/docs">manual link</a>';

    expect((string) sanitize_inline_html($description))->toBe($description);
});

test('block elements are unwrapped so a description cannot break the list layout', function () {
    // A block element inside a <td> or after a "- " label would disturb the whole table.
    expect((string) sanitize_inline_html('<h1>huge</h1>'))->toBe('huge')
        ->and((string) sanitize_inline_html('<table><tr><td>cell</td></tr></table>'))->toBe('cell')
        ->and((string) sanitize_inline_html('<div>block</div> tail'))->toBe('block tail');
});

test('an unclosed tag cannot leak out of the cell it was rendered in', function () {
    // The regex based fallback would emit "<div>oops" verbatim and break the surrounding markup.
    expect((string) sanitize_inline_html('<div>oops'))->toBe('oops')
        ->and((string) sanitize_inline_html('<b>bold'))->toBe('<b>bold</b>');
});

test('the inline profile still removes every scripting vector', function () {
    $payloads = [
        '<b onclick="alert(1)">x</b>',
        '<a href="javascript:alert(1)">x</a>',
        '<span style="background:url(//evil.test)">x</span>',
        '<script>alert(1)</script>x',
        '<img src=x onerror=alert(1)>x',
        '<b onload=alert(1)>x</b>',
    ];

    foreach ($payloads as $payload) {
        $out = (string) sanitize_inline_html($payload);

        expect(auditSanitisedMarkup($out))->toBe([])
            ->and($out)->toContain('x');
    }
});

test('a plain description is returned unchanged and encoded exactly once', function () {
    expect((string) sanitize_inline_html('Sends mail & logs the result'))
        ->toBe('Sends mail &amp; logs the result');

    expect((string) sanitize_inline_html('Sends mail &amp; logs the result'))
        ->toBe('Sends mail &amp; logs the result');
});
