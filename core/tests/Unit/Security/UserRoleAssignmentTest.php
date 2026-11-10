<?php

use EvolutionCMS\Support\RoleGuard;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\Mocks\CacheRefreshHarness;

/*
|--------------------------------------------------------------------------
| The role a user is given is the role whose grants were checked
|--------------------------------------------------------------------------
|
| EditOrNewUser checked the reach of (int) $_POST['role'] but stored the posted string. MySQL and
| MariaDB in the sql_mode the installer configures ('strict' => false) coerce it on the way in:
| '0.5' became 1, so a role manager passed the check for "no role" and stored the administrator
| role, on its own account as well; '2.5' became 3, '2abc' 2. The role id is now validated once,
| before anything is saved, and that same int is checked and stored.
|
| The cases run the real EditOrNewUser::process() and UserSetRole in their own process, on SQLite
| and, when EVO_TEST_MYSQL_HOST is set, on MySQL/MariaDB non-strict (EVO_TEST_MYSQL_PORT,
| _DATABASE - an existing database, default evo_test - _USER, _PASSWORD).
|
*/

/** @return array<string, array{0: ?array}> */
function roleAssignmentBackends(): array
{
    $backends = ['sqlite' => [null]];
    $host = getenv('EVO_TEST_MYSQL_HOST');
    if ($host !== false && $host !== '') {
        $backends['mysql non-strict'] = [[
            'host' => $host,
            'port' => (int) (getenv('EVO_TEST_MYSQL_PORT') ?: 3306),
            'database' => getenv('EVO_TEST_MYSQL_DATABASE') ?: 'evo_test',
            'username' => getenv('EVO_TEST_MYSQL_USER') ?: 'root',
            'password' => (string) getenv('EVO_TEST_MYSQL_PASSWORD'),
        ]];
    }

    return $backends;
}

/**
 * The manager of role 2 (user 10) saves the user form; user 11 also has role 2, role 3 holds
 * settings besides edit_document.
 *
 * @return array{quit: ?string, roles: array<int, int>, checked: list<mixed>, tvRoles: list<mixed>}
 */
function roleAssignmentRun(array $post, ?array $mysql, array $permissions = ['save_user', 'save_role', 'edit_document']): array
{
    $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/user-role-assignment.php', [
        base64_encode(json_encode(['permissions' => $permissions, 'post' => $post, 'mysql' => $mysql])),
    ]);
    $decoded = json_decode($output, true);
    expect($decoded)->toBeArray($output);

    return $decoded;
}

function roleAssignmentEdit(int $userId, mixed $role): array
{
    return ['mode' => '12', 'id' => (string) $userId, 'newusername' => 'user' . $userId, 'role' => $role];
}

it('stores exactly the role it checked', function (?array $mysql, int $userId, string $role, array $permissions, int $stored) {
    $result = roleAssignmentRun(roleAssignmentEdit($userId, $role), $mysql, ['save_user', 'save_role', 'edit_document', ...$permissions]);

    expect($result['quit'])->toBeNull()
        ->and($result['roles'][$userId])->toBe($stored)
        ->and($result['checked'])->toBe([(int) $role])
        ->and($result['tvRoles'])->toBe([(int) $role]);
})->with(roleAssignmentBackends())->with([
    'no role for another user'             => [11, '0', [], 0],
    'no role for itself'                   => [10, '0', [], 0],
    'a role within reach'                  => [11, '3', ['settings'], 3],
    'a role out of reach stays unchanged'  => [11, '3', [], 2],
    'the administrator role, refused'      => [10, '1', ['settings'], 2],
]);

it('refuses a role id that is not exactly how the form writes one, before saving anything', function (?array $mysql, mixed $role) {
    foreach ([roleAssignmentEdit(11, $role), roleAssignmentEdit(10, $role), ['mode' => '87', 'newusername' => 'new', 'role' => $role]] as $post) {
        $result = roleAssignmentRun($post, $mysql, ['save_user', 'save_role', 'edit_document', 'settings']);

        expect($result['quit'])->toBe('global.user_role_invalid')
            ->and($result['roles'])->toBe([10 => 2, 11 => 2])
            ->and($result['checked'])->toBe([])
            ->and($result['tvRoles'])->toBe([]);
    }
})->with(roleAssignmentBackends())->with([
    // what MySQL/MariaDB non-strict stores in brackets
    'a fraction under one (1)' => ['0.5'],
    'a fraction (3)'           => ['2.5'],
    'a trailing suffix (2)'    => ['3abc'],
    'a leading space (3)'      => [' 3'],
    'a trailing space (3)'     => ['3 '],
    'a leading zero (3)'       => ['03'],
    'an exponent (1)'          => ['1e0'],
    'a hex string (0)'         => ['0x1'],
    'a negative id'            => ['-1'],
    'an empty value'           => [''],
    'a role that does not exist' => ['4'],
    'an array'                 => [['3']],
]);

it('gives a new user the checked role', function (?array $mysql) {
    $result = roleAssignmentRun(['mode' => '87', 'newusername' => 'new', 'role' => '2'], $mysql);

    expect($result['quit'])->toBeNull()
        ->and($result['roles'])->toHaveCount(3)
        ->and(end($result['roles']))->toBe(2)
        ->and($result['checked'])->toBe([2]);
})->with(roleAssignmentBackends());

describe('RoleGuard::postedRoleId()', function () {
    beforeEach(function () {
        $this->capsule = CacheRefreshHarness::boot(':memory:');
        $this->capsule->getConnection()->getSchemaBuilder()->create('user_roles', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->default('');
        });
        $this->capsule->table('user_roles')->insert([['id' => 1, 'name' => 'Administrator'], ['id' => 3, 'name' => 'Editor']]);
    });

    afterEach(function () {
        Capsule::connection()->disconnect();
    });

    it('accepts no role and existing role ids, as strings or ints', function (mixed $value, int $id) {
        expect(RoleGuard::postedRoleId($value))->toBe($id);
    })->with([['0', 0], [0, 0], ['1', 1], ['3', 3], [3, 3]]);

    it('refuses anything else', function (mixed $value) {
        expect(RoleGuard::postedRoleId($value))->toBeNull();
    })->with(['2', '0.5', '3abc', " 3", "3\n", '03', '1e0', '+3', '-3', -3, '', null, true, 3.0, [[3]], '99999999999']);
});
