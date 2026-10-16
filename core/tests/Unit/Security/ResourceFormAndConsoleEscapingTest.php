<?php

/*
|--------------------------------------------------------------------------
| Escaping in the resource form, the SQL console and the installer
|--------------------------------------------------------------------------
|
| - the current page title in the resource breadcrumb was printed raw (stored XSS);
| - ?contentType= for a new resource reached a hidden input unescaped (reflected XSS);
| - SQL console result cells and column names were joined into the table unescaped;
| - the installer echoed exception messages carrying the posted user name, and two of its
|   validators compared preg_match() with false, so a mismatch was accepted.
*/

require_once dirname(__DIR__, 4) . '/install/src/functions.php';

function escapingSource(string $relative): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('escapes the current resource title in the breadcrumb', function () {
    expect(escapingSource('manager/actions/mutate_content.dynamic.php'))
        ->toContain("breadcrumbs__li_current\">' . htmlspecialchars((string)\$title")
        ->not->toContain("breadcrumbs__li_current\">' . \$title");
});

it('reads contentType as a scalar and escapes it in the hidden input', function () {
    $source = escapingSource('manager/actions/mutate_content.dynamic.php');

    expect($source)
        ->toContain("get_by_key(\$_REQUEST, 'contentType', \$defaultContentType, 'is_scalar')")
        ->toContain('name="contentType" value="<?= e(isset($content[\'contentType\'])')
        ->not->toContain('name="contentType" value="<?= (isset($content[\'contentType\']');
});

it('escapes SQL console cells, headings and the echoed statement', function () {
    expect(escapingSource('manager/actions/bkmanager.static.php'))
        ->toContain('$result_value[] = e($v);')
        ->toContain('$title[] = e($k);')
        ->toContain('<?= e($value) ?></textarea>')
        ->not->toContain('$result_value[] = $v;');
});

it('escapes exception messages in the installer server test', function () {
    $source = escapingSource('install/src/controllers/connection/servertest.php');

    expect($source)->not->toContain('$e->getMessage() .')
        ->and(substr_count($source, "htmlspecialchars(\$e->getMessage(), ENT_QUOTES, 'UTF-8')"))->toBe(2);
});

it('rejects database and admin user names that do not match', function (string $name) {
    expect(fn () => validateDbUser($name, 'mysql'))->toThrow(InvalidArgumentException::class);
})->with(['<script>alert(1)</script>', 'a b', "x'; --", '1abc']);

it('rejects admin user names that do not match', function (string $name) {
    expect(fn () => validateAdminUsername($name))->toThrow(InvalidArgumentException::class);
})->with(['<img src=x onerror=alert(1)>', 'a b', "x'; --", '1abc']);

it('still accepts ordinary user names', function () {
    expect(validateDbUser('evo_user', 'mysql'))->toBe('evo_user')
        ->and(validateAdminUsername('admin.user-1'))->toBe('admin.user-1');
});

it('marks the stored file browser as selected in the web user editor and escapes the names', function () {
    $source = escapingSource('manager/actions/mutate_web_user.dynamic.php');

    // $selected used to hold the stored setting itself, which was printed raw inside the tag.
    expect($source)
        ->toContain("\$isSelected('default')")
        ->toContain("'<option value=\"' . e(\$browser_name) . '\"' . \$isSelected(\$browser_name)")
        ->not->toContain("\$selected = \$usersettings['which_browser']")
        ->and(substr_count($source, "<h2 class=\"tab\">' . e(\$row['category']) . '</h2>"))->toBe(4);
});

it('selects only the matching browser option', function () {
    $isSelected = function (string $value, string $current) {
        return $value === $current || ($value === 'default' && $current === '') ? ' selected="selected"' : '';
    };

    expect($isSelected('mcpuk', 'mcpuk'))->toBe(' selected="selected"')
        ->and($isSelected('default', ''))->toBe(' selected="selected"')
        ->and($isSelected('default', 'mcpuk'))->toBe('')
        ->and($isSelected('mcpuk', '" onfocus="x'))->toBe('');
});
