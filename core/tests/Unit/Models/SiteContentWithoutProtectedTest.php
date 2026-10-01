<?php

use EvolutionCMS\Models\SiteContent;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * Public document 1 is in groups 7 and 8, public document 2 in none,
 * private document 3 in group 5.
 */
beforeEach(function () {
    // evo() needs a class name before it hands out the global set below.
    defined('EVO_CLASS') || define('EVO_CLASS', \Illuminate\Container\Container::class);
    defined('IN_MANAGER_MODE') || define('IN_MANAGER_MODE', false);
    defined('IN_INSTALL_MODE') || define('IN_INSTALL_MODE', false);
    defined('EVO_API_MODE') || define('EVO_API_MODE', true);

    $capsule = new Capsule();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'evo_']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Model::setConnectionResolver($capsule->getDatabaseManager());

    $schema = $capsule->getConnection()->getSchemaBuilder();
    $schema->create('site_content', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('parent')->default(0);
        $table->integer('privateweb')->default(0);
        $table->integer('privatemgr')->default(0);
        $table->integer('deleted')->default(0);
    });
    $schema->create('document_groups', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('document_group');
        $table->integer('document');
    });
    Capsule::table('site_content')->insert([
        ['id' => 1, 'privateweb' => 0],
        ['id' => 2, 'privateweb' => 0],
        ['id' => 3, 'privateweb' => 1],
    ]);
    Capsule::table('document_groups')->insert([
        ['document_group' => 7, 'document' => 1],
        ['document_group' => 8, 'document' => 1],
        ['document_group' => 5, 'document' => 3],
    ]);
});

afterEach(function () {
    global $evo;
    $evo = null;
    Capsule::connection()->disconnect();
});

/** A front-end visitor in the given document groups, as evo() hands it out. */
function frontendVisitorInGroups(array $groups): void
{
    global $evo;
    $evo = new class($groups) {
        public function __construct(private array $groups)
        {
        }

        public function getUserDocGroups()
        {
            return $this->groups ?: '';
        }

        public function isFrontend(): bool
        {
            return true;
        }
    };
}

test('a visitor without document groups gets public documents without the group join', function () {
    frontendVisitorInGroups([]);
    $query = SiteContent::query()->select('site_content.id')->withoutProtected()->orderBy('site_content.id');

    expect($query->toSql())->not->toContain('document_groups')
        // Document 1 is in two groups: the join used to list it twice.
        ->and($query->pluck('id')->all())->toBe([1, 2]);
});

test('a visitor in a document group also gets the private documents of that group', function () {
    frontendVisitorInGroups([5]);
    $query = SiteContent::query()->select('site_content.id')->withoutProtected()->orderBy('site_content.id');

    expect($query->toSql())->toContain('document_groups')
        ->and(array_values(array_unique($query->pluck('id')->all())))->toBe([1, 2, 3]);
});
