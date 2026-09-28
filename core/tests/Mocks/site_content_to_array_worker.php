<?php
// Turns a document row into the array the parser renders, in a clean process, and reports
// whether that loaded Carbon. Prints JSON: {"deletedon": ..., "carbonLoaded": bool}
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use EvolutionCMS\Models\SiteContent;

$document = (new SiteContent())->newFromBuilder([
    'id' => 1,
    'pagetitle' => 'Home',
    'template' => 1,
    'createdon' => '1790625541',
    'deleted' => '0',
    'deletedon' => '0',
]);

echo json_encode([
    'deletedon' => $document->toArray()['deletedon'],
    'carbonLoaded' => class_exists(\Carbon\Carbon::class, false),
], JSON_THROW_ON_ERROR);
