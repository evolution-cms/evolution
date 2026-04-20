<?php

use Tests\Mocks\MockDocumentParser;

// Set up required constants BEFORE anything else
if (!defined('IN_INSTALL_MODE')) {
    define('IN_INSTALL_MODE', false);
}
if (!defined('EVO_API_MODE')) {
    define('EVO_API_MODE', true);
}
if (!defined('IN_MANAGER_MODE')) {
    define('IN_MANAGER_MODE', false);
}

beforeAll(function () {
    // Load preload functions first
    if (!function_exists('evo')) {
        require_once __DIR__ . '/../../functions/preload.php';
    }

    // Define EVO_CLASS constant BEFORE creating any objects that might call evo()
    if (!defined('EVO_CLASS')) {
        define('EVO_CLASS', 'Tests\\Mocks\\MockDocumentParser');
    }

    // Initialize the global $modx variable
    global $modx;
    $modx = MockDocumentParser::getInstance();
});

beforeEach(function () {
    // Ensure the mock is available for each test
    global $modx;
    if ($modx === null) {
        $modx = MockDocumentParser::getInstance();
    }
});

test('addbreak modifier adds br tags to plain text lines', function () {
    $value = "Line 1\nLine 2\nLine 3";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Line 1<br />");
    expect($result)->toContain("Line 2<br />");
    expect($result)->toContain("Line 3");
    expect($result)->not()->toContain("Line 3<br />"); // Last line shouldn't have br
});

test('addbreak modifier does not add br after block elements', function () {
    $value = "<div>Content</div>\n<p>Paragraph</p>\n<h1>Heading</h1>";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->not()->toContain('</div><br />');
    expect($result)->not()->toContain('</p><br />');
    expect($result)->not()->toContain('</h1><br />');
});

test('addbreak modifier handles mixed content', function () {
    $value = "Text line\n<div>Block</div>\nAnother text line\n<p>Paragraph</p>";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Text line<br />");
    expect($result)->not()->toContain('</div><br />');
    expect($result)->toContain("Another text line<br />");
    expect($result)->not()->toContain('</p><br />');
});

test('addbreak modifier handles different line endings', function () {
    $value = "Line 1\r\nLine 2\rLine 3\nLine 4";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Line 1<br />");
    expect($result)->toContain("Line 2<br />");
    expect($result)->toContain("Line 3<br />");
});

test('addbreak modifier trims trailing whitespace', function () {
    $value = "Line with spaces    \nAnother line";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Line with spaces<br />");
    expect($result)->not()->toContain("    <br />");
});

test('addbreak modifier handles empty lines', function () {
    $value = "Line 1\n\nLine 3";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Line 1<br />");
    expect($result)->toContain("<br />"); // Empty line gets br
});

test('addbreak modifier handles HTML tags in lines', function () {
    $value = "Line with <strong>bold</strong> text\nLine with <em>italic</em> text";

    $result = (function () use ($value) {
        return include(__DIR__ . '/../../modifiers/mdf_addbreak.inc.php');
    })();

    expect($result)->toBeString();
    expect($result)->toContain("Line with <strong>bold</strong> text<br />");
    expect($result)->toContain("Line with <em>italic</em> text");
});
