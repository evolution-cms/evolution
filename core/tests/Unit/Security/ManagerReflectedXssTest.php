<?php

/*
|--------------------------------------------------------------------------
| Request values echoed back into manager pages
|--------------------------------------------------------------------------
|
| The legacy manager actions build HTML by string concatenation, so a request value that reaches
| an echo unescaped is reflected XSS - and several of them land inside <script> blocks, where
| attribute escaping would not have been enough anyway.
|
| These assertions read the shipped sources. They are deliberately about the *source* of each
| value rather than the sink: constraining it where it is read covers every sink it feeds.
|
*/

function managerSource(string $relative): string
{
    return (string)file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('casts the backup manager refresh count before writing it into a script block', function () {
    // ?r= was concatenated straight into doRefresh(...) inside <script>.
    $source = managerSource('manager/actions/bkmanager.static.php');

    expect($source)
        ->toContain("doRefresh(\" . (int)\$_REQUEST['r']")
        ->and($source)->not->toContain("doRefresh(\" . \$_REQUEST['r']");
});

it('constrains every resource selector parameter at the point it is read', function () {
    // $cb and $rt are echoed inside <script>; the rest land in value="" attributes.
    $source = managerSource('manager/actions/resource_selector.static.php');

    expect($source)
        // callback is looked up on window.opener, so only an identifier path is allowed
        ->toContain("\$cb = preg_replace('/[^A-Za-z0-9_$.]/', '',")
        ->toContain("\$rt = preg_replace('/[^a-z0-9_]/', '',")
        ->toContain("\$sm = strtolower((string)get_by_key(\$_REQUEST, 'sm', '', 'is_scalar')) === 'm' ? 'm' : 's'")
        ->toContain("\$listmode = (int)get_by_key(\$_REQUEST, 'listmode', 0, 'is_scalar')");

    expect($source)
        ->not->toContain("\$cb = \$_REQUEST['cb']")
        ->not->toContain("\$_REQUEST['listmode'] ?>")
        ->not->toContain('value="<?= $query ?>"');
});

it('constrains the resource sort parameters that are concatenated into javascript', function () {
    // $add_path is appended to several document.location.href assignments.
    $source = managerSource('manager/actions/mutate_content.dynamic.php');

    expect($source)
        ->toContain("\$sd = strtoupper((string)get_by_key(\$_REQUEST, 'dir', '', 'is_scalar')) === 'ASC'")
        ->toContain("preg_replace('/[^A-Za-z0-9_]/', '', (string)get_by_key(\$_REQUEST, 'sort', '', 'is_scalar'))")
        ->and($source)
        ->not->toContain("'&dir=' . \$_REQUEST['dir']")
        ->not->toContain("'&sort=' . \$_REQUEST['sort']");
});

it('casts reflected element ids written into javascript string literals', function (string $file) {
    $source = managerSource('manager/actions/' . $file);

    expect($source)->not->toMatch("/<\?=\s*\(?isset\(\\\$_REQUEST\['id'\]\)\)?\s*\?\s*\\\$_REQUEST\['id'\]/");
})->with([
    'mutate_content.dynamic.php',
    'mutate_module.dynamic.php',
]);

it('escapes the manager log search filters', function () {
    $source = managerSource('manager/actions/logging.static.php');

    expect($source)
        ->not->toContain("value=\"<?= isset(\$_REQUEST['datefrom']) ? \$_REQUEST['datefrom'] : \"\" ?>\"")
        ->not->toContain("value=\"<?= isset(\$_REQUEST['dateto']) ? \$_REQUEST['dateto'] : \"\" ?>\"");

    expect(substr_count($source, "entities("))->toBeGreaterThanOrEqual(8);
});

it('escapes the manager log row, including the header controlled columns', function () {
    // LogHandler stores $_SERVER['HTTP_USER_AGENT'] verbatim, and getUserIP() trusts
    // X-Forwarded-For, so both columns carry attacker-chosen text into an admin-only page.
    $source = managerSource('manager/actions/logging.static.php');

    expect($source)
        ->not->toContain("<td class=\"text-nowrap\"><?= \$logentry['ip'] ?></td>")
        ->not->toContain("<td class=\"text-nowrap\"><?= \$logentry['useragent'] ?></td>")
        ->not->toContain("<td class=\"text-xs-right\"><?= \$logentry['itemid'] ?></td>")
        ->not->toContain("<td><?= \$item ?></td>");
});

it('escapes the file manager path fields', function () {
    // A path that does not resolve falls back to the file manager root and passes the access
    // check, so the raw request value is still in scope when the forms are rendered.
    $source = managerSource('manager/actions/files.dynamic.php');

    expect($source)
        ->not->toContain('value="<?= $relative_path ?>"')
        ->not->toContain('value="<?= $requested_path ?>"');
});

it('escapes the event log search box', function () {
    // A Blade view is not automatically safe: this one used a raw short-echo tag.
    $source = managerSource('manager/views/page/eventlog.blade.php');

    expect($source)
        ->toContain('value="{{ $query }}"')
        ->and($source)->not->toContain('value="<?= $query ?>"');
});

it('escapes every value attribute in the web user editor', function () {
    // These are stored fields: a low privilege user sets them, an admin renders them.
    $source = managerSource('manager/actions/mutate_web_user.dynamic.php');

    preg_match_all('/value="<\?php echo ((?:(?!\?>).)*?);\s*\?>"/s', $source, $matches);

    expect($matches[1])->not->toBeEmpty();

    $unescaped = array_values(array_filter(
        $matches[1],
        static fn ($expr) => !str_contains($expr, 'htmlspecialchars') && !str_contains($expr, 'entities(')
    ));

    expect($unescaped)->toBe([]);
});

it('escapes database errors echoed by the installer', function () {
    // The installer runs before authentication exists, so its error paths are public.
    $source = managerSource('install/src/controllers/connection/databasetest.php');

    expect($source)
        ->not->toContain("' ' . \$e->getMessage() . '</span>'")
        ->not->toContain("' ' . print_r(\$result->errorInfo(), true) . '</span>'");
});

it('escapes the manager log filters echoed back into the search form', function () {
    // ?message= was written raw into value="", and the sanitizer only rewrites "<script".
    $source = managerSource('manager/actions/logging.static.php');

    expect($source)
        ->toContain("value=\"<?= entities((string)get_by_key(\$_REQUEST, 'message', '', 'is_scalar')")
        ->not->toContain("value=\"<?= get_by_key(\$_REQUEST, 'message') ?>\"");

    // No request value may be echoed without going through entities().
    preg_match_all('/<\?=((?:(?!\?>).)*\$_REQUEST(?:(?!\?>).)*)\?>/s', $source, $matches);

    $unescaped = array_values(array_filter(
        $matches[1],
        static fn ($expr) => !str_contains($expr, 'entities(') && !str_contains($expr, '(int)')
    ));

    expect($matches[1])->not->toBeEmpty()
        ->and($unescaped)->toBe([]);
});

it('encodes the manager log filters carried into the pagination links', function () {
    // Every filter used to be concatenated raw into $extargv, which Paginate writes into href="".
    $source = managerSource('manager/actions/logging.static.php');

    expect($source)
        ->toContain("\$extargv = '&' . str_replace('%', '%25', http_build_query([")
        ->toContain("], '', '&', PHP_QUERY_RFC3986));")
        ->not->toContain("\"&message=\" . get_by_key(\$_REQUEST, 'message')")
        ->not->toContain("\"&dateto=\" . \$_REQUEST['dateto']");
});

it('escapes stored log values listed in the manager log filter dropdowns', function () {
    // Item names are document titles and element names, set by lower privileged editors.
    $source = managerSource('manager/actions/logging.static.php');

    expect($source)
        ->not->toContain("'>' . \$row['username'] . \"</option>")
        ->not->toContain("'>' . \$row['itemname'] . \"</option>")
        ->not->toContain("'<option value=\"' . \$row['itemname'] . '\"'")
        ->toContain("entities((string)\$row['itemname']")
        ->toContain("entities((string)\$row['username']");
});

/**
 * Every link Paginate renders for a given extra query string.
 */
function paginateLinks(string $extargv): array
{
    $paginate = new EvolutionCMS\Support\Paginate(100, 50, 10, $extargv);
    $paging = $paginate->getPagingArray();

    return array_merge(
        [$paging['first_link'], $paging['previous_link'], $paging['next_link'], $paging['last_link']],
        array_values(array_filter($paginate->getPagingRowArray(), static fn ($link) => str_starts_with($link, '<a ')))
    );
}

it('keeps a hostile extra query string inside the pagination href', function (string $extargv) {
    // Paginate urldecodes its argument, so URL-encoding by the caller alone is undone.
    foreach (paginateLinks($extargv) as $link) {
        expect($link)
            ->toMatch('/^<a href="[^"<>\']*">/')
            ->not->toContain('<img');
    }
})->with([
    'raw' => ['&message="><img src=x onerror=alert(1)>'],
    'url-encoded' => ['&message=' . rawurlencode('"><img src=x onerror=alert(1)>')],
    'single quote' => ["&itemname=' autofocus onfocus=alert(1) x='"],
]);

it('round-trips manager log filters through the pagination links', function () {
    // Mirrors the $extargv construction in logging.static.php.
    $extargv = '&' . str_replace('%', '%25', http_build_query([
        'a' => 13,
        'message' => 'a&b=c "d"',
        'itemname' => 'Home page',
    ], '', '&', PHP_QUERY_RFC3986));

    $link = paginateLinks($extargv)[2];
    preg_match('/href="\?([^"]*)"/', $link, $match);
    parse_str(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), $query);

    expect($query)->toMatchArray([
        'int_cur_position' => '60',
        'a' => '13',
        'message' => 'a&b=c "d"',
        'itemname' => 'Home page',
    ]);
});
