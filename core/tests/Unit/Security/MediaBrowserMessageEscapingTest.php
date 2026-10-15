<?php

/*
| The media browser (KCFinder) shows server messages with browser.alert(), which builds HTML. Copy,
| move and delete errors carry the file name, and the name of an uploaded file is whatever the
| uploader sent (only the extension is checked): "<img src=x onerror=...>.jpg", uploaded by anyone
| allowed to browse images, ran script for the administrator who later copied or moved it. The
| upload folder message also printed the absolute upload path.
*/

function mediaBrowserLabel(string $message, ?array $data = null, array $labels = []): string
{
    if (!class_exists('uploader', false)) {
        require_once dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/uploader.php';
    }
    $uploader = (new ReflectionClass('uploader'))->newInstanceWithoutConstructor();

    return (function () use ($message, $data, $labels) {
        $this->labels = $labels;

        return $this->label($message, $data);
    })->call($uploader);
}

it('escapes file names it puts into messages', function () {
    expect(mediaBrowserLabel("Cannot copy '{file}'.", ['file' => '"><img src=x onerror=alert(1)>.jpg']))
        ->toBe("Cannot copy '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;.jpg'.");
});

it('escapes the values in translated messages too and leaves plain values alone', function () {
    expect(mediaBrowserLabel('The uploaded file exceeds {size} bytes.', ['size' => 2097152],
        ['The uploaded file exceeds {size} bytes.' => 'Datei größer als {size} Bytes.']))
        ->toBe('Datei größer als 2097152 Bytes.');
});

it('escapes the file name prefixes of copy and move errors', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/browser.php');

    expect($source)->not->toContain('"$base: " . $this->label(')
        ->and(substr_count($source, "htmlspecialchars(\$base, ENT_QUOTES, 'UTF-8') . ': ' . \$this->label("))->toBe(6);
});

it('does not print the upload path', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/uploader.php');

    expect($source)->toContain('$this->backMsg("Cannot write to upload folder.");')
        ->and($source)->not->toContain('Cannot write to upload folder. {$this->config');
});
