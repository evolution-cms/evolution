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
