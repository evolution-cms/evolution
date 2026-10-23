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
    $this->originalSession = $_SESSION ?? null;
    $_SESSION = [];
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
        ['id' => 1, 'privateweb' => 0, 'privatemgr' => 0],
        ['id' => 2, 'privateweb' => 0, 'privatemgr' => 0],
        ['id' => 3, 'privateweb' => 1, 'privatemgr' => 1],
    ]);
    Capsule::table('document_groups')->insert([
        ['document_group' => 7, 'document' => 1],
        ['document_group' => 8, 'document' => 1],
        ['document_group' => 5, 'document' => 3],
    ]);
});

afterEach(function () {
    if ($this->originalSession === null) {
        unset($_SESSION);
    } else {
        $_SESSION = $this->originalSession;
    }
    global $evo;
    $evo = null;
    Capsule::connection()->disconnect();
});

/** Manager/API mode, including the anonymous error-page path of theme AJAX. */
function managerVisitorInGroups(array $groups): void
{
    global $evo;
    $evo = new class($groups) {
        public function __construct(private array $groups) {}
        public function getUserDocGroups() { return $this->groups ?: ''; }
        public function isFrontend(): bool { return false; }
    };
}

test('manager resource scope handles absent or malformed roles without SQL or admin access', function (bool $present, mixed $role, array $expected) {
    managerVisitorInGroups([]);
    if ($present) {
        $_SESSION['mgrRole'] = $role;
    }

    $query = SiteContent::query()->select('site_content.id')->withoutProtected()->orderBy('site_content.id');
    expect($query->pluck('id')->all())->toBe($expected);
})->with([
    'anonymous, no role' => [false, null, [1, 2]],
    'null role' => [true, null, [1, 2]],
    'regular manager' => [true, 2, [1, 2]],
    'integer administrator' => [true, 1, [1, 2, 3]],
    'string administrator' => [true, '1', [1, 2, 3]],
    'empty string' => [true, '', [1, 2]],
    'SQL expression' => [true, '1 OR 1=1', [1, 2]],
    'numeric prefix' => [true, '1invalid', [1, 2]],
    'array role' => [true, [1], [1, 2]],
    'boolean role' => [true, true, [1, 2]],
]);

test('a non-admin manager retains access through their document groups', function () {
    managerVisitorInGroups([5]);
    $_SESSION['mgrRole'] = 2;
    $ids = SiteContent::query()->select('site_content.id')->withoutProtected()->orderBy('site_content.id')->pluck('id');
    expect($ids->unique()->values()->all())->toBe([1, 2, 3]);
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
