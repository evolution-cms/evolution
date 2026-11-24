<?php

/*
 * Runs a manager edit through document-manager in a process of its own, because the EvolutionCMS()
 * it calls is replaced here, and prints what was stored as JSON.
 */

namespace EvolutionCMS\DocumentManager\Services\Documents;

use EvolutionCMS\DocumentManager\Services\DocumentManager;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Tests\Support\DocumentSaveDatabase;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__) . '/Support/DocumentSaveDatabase.php';

function EvolutionCMS(): object
{
    static $cms;

    return $cms ??= new class {
        public function timestamp(int $timestamp): int
        {
            return $timestamp;
        }

        public function getLoginUserID(): int
        {
            return 7;
        }

        public function getConfig(string $key): int
        {
            return 0;
        }

        public function getParentIds(int $id): array
        {
            return [];
        }

        public function hasPermission(string $permission): bool
        {
            return true;
        }

        public function invokeEvent(string $name, array $args): void
        {
            if ($name !== 'OnBeforeDocFormSave') {
                return;
            }
            Capsule::table('site_content')->where('id', $args['user'])
                ->update(['pagetitle' => 'Plugin title', 'description' => 'Plugin description']);
            if ($args['user'] === 3) {
                throw new \RuntimeException('Stop the save');
            }
        }
    };
}

$capsule = DocumentSaveDatabase::boot();
$translator = new Translator(new ArrayLoader(), 'en');
$capsule->getContainer()->instance('translator', $translator);
$capsule->getContainer()->instance('validator', new Factory($translator));
foreach (['Validator', 'Lang'] as $facade) {
    if (!class_exists($facade)) {
        class_alias('Illuminate\\Support\\Facades\\' . $facade, $facade);
    }
}
Capsule::table('site_content')->insert([
    ['id' => 2, 'pagetitle' => 'Manager title', 'description' => 'Original description', 'published' => 1],
    ['id' => 3, 'pagetitle' => 'Original title', 'description' => 'Original description', 'published' => 1],
]);

$stored = static fn (int $id): array => array_intersect_key(
    (array) Capsule::table('site_content')->find($id),
    ['pagetitle' => true, 'description' => true]
);
$form = ['pagetitle' => 'Manager title', 'alias' => '', 'published' => 1];

$service = new DocumentManager();
$result = ['returned' => $service->edit(['id' => 2] + $form, true, false)->pagetitle, 'edited' => $stored(2)];
try {
    $service->edit(['id' => 3] + $form, true, false);
} catch (\RuntimeException $exception) {
    $result['stopped'] = $exception->getMessage();
}
$result['refused'] = $stored(3);

echo json_encode($result);
