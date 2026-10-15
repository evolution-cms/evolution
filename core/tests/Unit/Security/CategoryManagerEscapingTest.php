<?php

/*
|--------------------------------------------------------------------------
| Category and element names in the category manager (a=120)
|--------------------------------------------------------------------------
|
| Anyone who may save a chunk, template or TV can name it - or type a new category for it - with
| markup (the GPC filter only defuses "<script", so "<img onerror>" passed). The category manager,
| an administrator page, printed those names and the element descriptions raw, and echoed the
| names posted back by the categorize form in its messages. The delete button's data-tooltip goes
| through innerHTML (manager/media/script/main.js), so a single htmlspecialchars() there was not
| enough either: the attribute value is decoded back to markup before it is rendered.
|
| The templates are rendered here with a stub of the module object they receive as $view.
|
*/

const CATEGORY_MANAGER_PAYLOAD = '<img src=x onerror=alert(1)>';

function categoryManagerView(array $elements = []): object
{
    return new class ($elements) {
        public array $elements = ['templates', 'htmlsnippets'];

        public function __construct(private array $assigned)
        {
        }

        public function txt($key)
        {
            return ['cm_delete_element_x_now' => 'Delete %s now', 'cm_no_x_assigned' => 'No %s'][$key] ?? $key;
        }

        public function get($key)
        {
            return ['url' => 'index.php?a=120&amp;id=0', 'request_key' => 'module_categories_manager'][$key] ?? '';
        }

        public function renderView($name, $data = [])
        {
            echo renderCategoryManagerTemplate($name, $data, $this);
        }

        public function getAllAssignedElements($categoryId)
        {
            return $this->assigned;
        }
    };
}

function renderCategoryManagerTemplate(string $name, $data, ?object $view = null): string
{
    $view ??= categoryManagerView();
    $_style = array_fill_keys(
        ['icon_folder_open', 'icon_info_circle', 'icon_info_triangle', 'icon_category', 'icon_refresh',
            'icon_question_circle', 'icon_save'],
        'fa'
    );
    $_lang = ['help' => 'Help'];

    // csrf_field() / csrf_token() read the manager session
    $hadSession = isset($_SESSION);
    $_SESSION ??= [];
    $_SESSION['_token'] ??= 'test-token';

    ob_start();
    try {
        (static function () use ($name, $data, $view, $_style, $_lang) {
            include dirname(__DIR__, 4) . '/manager/actions/category_mgr/skin/' . $name . '.tpl.phtml';
        })();
    } finally {
        $html = (string) ob_get_clean();
        if (!$hadSession) {
            unset($_SESSION);
        }
    }

    return $html;
}

/**
 * Text of the tooltip the manager shows: the attribute is HTML-decoded by the DOM, then assigned
 * to innerHTML.
 */
function categoryManagerTooltipHtml(string $html): string
{
    expect($html)->toMatch('/data-tooltip="([^"]*)"/');
    preg_match('/data-tooltip="([^"]*)"/', $html, $match);

    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

it('escapes the category name in the categorize workbench', function () {
    $html = renderCategoryManagerTemplate('chunks/categorize/category', [
        'id' => '3" onclick="x',
        'category' => CATEGORY_MANAGER_PAYLOAD,
        'elements' => [],
    ]);

    expect($html)->not->toContain('<img')
        ->and($html)->toContain('&lt;img src=x onerror=alert(1)&gt;')
        ->and($html)->toContain('id="categorize-category-3"');
});

it('escapes element names and keeps only inline markup in descriptions', function () {
    $html = renderCategoryManagerTemplate('chunks/categorize/drag_element', [
        'id' => 7,
        'name' => CATEGORY_MANAGER_PAYLOAD,
        'description' => '<b>bold</b>' . CATEGORY_MANAGER_PAYLOAD,
    ]);

    $description = substr($html, strpos($html, '<p '));

    expect($html)->not->toContain('<img')
        ->and($html)->toContain('<h4>&lt;img src=x onerror=alert(1)&gt;</h4>')
        ->and($description)->toContain('<b>bold</b>')
        ->and($description)->not->toContain('onerror');
});

it('escapes assigned element names in both listings', function (string $template) {
    $html = renderCategoryManagerTemplate($template, [
        'templates' => [['templatename' => CATEGORY_MANAGER_PAYLOAD]],
        'htmlsnippets' => [['name' => '<svg onload=alert(2)>']],
    ]);

    expect($html)->not->toContain('<img')
        ->and($html)->not->toContain('<svg')
        ->and($html)->toContain('&lt;img src=x onerror=alert(1)&gt;')
        ->and($html)->toContain('&lt;svg onload=alert(2)&gt;');
})->with(['chunks/edit/assigned_elements', 'chunks/edit/assigned_elements_collapse']);

it('escapes messages that carry posted names', function (string $template) {
    $html = renderCategoryManagerTemplate($template, ['Category "' . CATEGORY_MANAGER_PAYLOAD . '" deleted']);

    expect($html)->not->toContain('<img')
        ->and($html)->toContain('&lt;img src=x onerror=alert(1)&gt;');
})->with(['chunks/messages', 'chunks/global_messages']);

it('escapes category names on the sort tab', function () {
    $html = renderCategoryManagerTemplate('sort', [
        ['id' => 4, 'rank' => '2"><svg onload=alert(3)>', 'category' => CATEGORY_MANAGER_PAYLOAD],
    ]);

    expect($html)->not->toContain('<img')
        ->and($html)->not->toContain('<svg')
        ->and($html)->toContain('&lt;img src=x onerror=alert(1)&gt;');
});

it('keeps the delete tooltip as text after the manager renders it as HTML', function () {
    $html = renderCategoryManagerTemplate('edit', [
        ['id' => 5, 'rank' => 0, 'category' => CATEGORY_MANAGER_PAYLOAD],
    ], categoryManagerView(['templates' => [['templatename' => CATEGORY_MANAGER_PAYLOAD]]]));

    $tooltip = categoryManagerTooltipHtml(substr($html, strpos($html, 'btn-outline-danger')));

    // what reaches innerHTML must still be inert
    expect($tooltip)->not->toContain('<img')
        ->and($tooltip)->toContain('&lt;img src=x onerror=alert(1)&gt;::Delete &lt;img')
        // the editable field shows the name, encoded once
        ->and($html)->toContain('value="&lt;img src=x onerror=alert(1)&gt;"')
        ->and($html)->not->toContain('<img');
});

it('escapes phrases on the translation tab', function () {
    $html = renderCategoryManagerTemplate('translate', [CATEGORY_MANAGER_PAYLOAD => '</textarea><svg onload=alert(4)>']);

    expect($html)->not->toContain('<img')
        ->and($html)->not->toContain('<svg')
        ->and($html)->toContain('&lt;/textarea&gt;');
});
