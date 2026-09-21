<?php

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Support\MoveDocumentTargetGuard;

test('move document target guard blocks missing or deleted parents', function () {
    $deletedParent = new SiteContent();
    $deletedParent->deleted = 1;

    $activeParent = new SiteContent();
    $activeParent->deleted = 0;

    expect(MoveDocumentTargetGuard::blocksParent(null))->toBeTrue()
        ->and(MoveDocumentTargetGuard::blocksParent($deletedParent))->toBeTrue()
        ->and(MoveDocumentTargetGuard::blocksParent($activeParent))->toBeFalse();
});

class MoveGuardCoreStub
{
    public array $config = [];
    public function getConfig($name = '', $default = null) { return $this->config[$name] ?? $default; }
    public function getLoginUserID($context = '') { return 1; }
}

function moveGuardTree(): void
{
    $capsule = new Illuminate\Database\Capsule\Manager();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    Illuminate\Database\Eloquent\Model::setConnectionResolver($capsule->getDatabaseManager());
    $capsule->getConnection()->getSchemaBuilder()->create('site_content', function (Illuminate\Database\Schema\Blueprint $t) {
        $t->increments('id');
        $t->unsignedInteger('parent')->default(0);
        $t->unsignedInteger('deleted')->default(0);
        $t->boolean('privatemgr')->default(0);
    });
    // 1 > 2 > 3 > 4, 5 at root, 6 > 7 forms a corrupt cycle
    $capsule->table('site_content')->insert([
        ['id' => 1, 'parent' => 0], ['id' => 2, 'parent' => 1], ['id' => 3, 'parent' => 2], ['id' => 4, 'parent' => 3],
        ['id' => 5, 'parent' => 0], ['id' => 6, 'parent' => 7], ['id' => 7, 'parent' => 6],
    ]);
}

test('a document cannot be moved onto itself or into its own subtree', function () {
    moveGuardTree();

    expect(MoveDocumentTargetGuard::isInsideItself(2, 2))->toBeTrue()
        ->and(MoveDocumentTargetGuard::isInsideItself(2, 3))->toBeTrue()
        ->and(MoveDocumentTargetGuard::isInsideItself(2, 4))->toBeTrue()
        ->and(MoveDocumentTargetGuard::isInsideItself(2, 1))->toBeFalse()
        ->and(MoveDocumentTargetGuard::isInsideItself(2, 5))->toBeFalse()
        ->and(MoveDocumentTargetGuard::isInsideItself(2, 0))->toBeFalse()
        ->and(MoveDocumentTargetGuard::isInsideItself(3, 999))->toBeFalse()
        ->and(MoveDocumentTargetGuard::isInsideItself(5, 6))->toBeFalse();
});

test('target permission check is skipped without udperms and passes for administrators', function () {
    moveGuardTree();
    defined('IN_MANAGER_MODE') || define('IN_MANAGER_MODE', false);
    defined('IN_INSTALL_MODE') || define('IN_INSTALL_MODE', false);
    defined('EVO_API_MODE') || define('EVO_API_MODE', true);
    defined('EVO_CLASS') || define('EVO_CLASS', MoveGuardCoreStub::class);
    require_once dirname(__DIR__, 2) . '/functions/preload.php';
    global $evo;
    $evo = new MoveGuardCoreStub();
    $_SESSION['mgrRole'] = 2;

    expect(MoveDocumentTargetGuard::deniedForUser(1))->toBeFalse();

    $evo->config['use_udperms'] = 1;
    $_SESSION['mgrRole'] = 1;
    expect(MoveDocumentTargetGuard::deniedForUser(1))->toBeFalse();

    $evo = null;
    unset($_SESSION['mgrRole']);
});

test('tree drag-and-drop and the move action both run the cycle and target permission guards', function () {
    $root = dirname(__DIR__, 3);
    $ajax = file_get_contents("$root/manager/media/style/default/ajax.php");
    $move = substr($ajax, strpos($ajax, "case 'movedocument'"), strpos($ajax, "case 'getLockedElements'") - strpos($ajax, "case 'movedocument'"));
    $controller = file_get_contents("$root/core/src/Controllers/MoveDocument.php");

    expect($move)->toContain('MoveDocumentTargetGuard::isInsideItself($id, $parent)')->toContain('MoveDocumentTargetGuard::deniedForUser($parent)')
        ->and(strpos($move, 'isInsideItself'))->toBeGreaterThan(strpos($move, '$parent = $eventParent;'))
        ->and(strpos($move, 'isInsideItself'))->toBeLessThan(strpos($move, '$document->parent = $parent;'))
        ->and(substr_count($controller, 'MoveDocumentTargetGuard::isInsideItself('))->toBe(2)
        ->and($controller)->not->toContain('allChildren(')->not->toContain('getParentIds(');
});
