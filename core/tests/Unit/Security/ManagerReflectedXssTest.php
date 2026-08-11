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
