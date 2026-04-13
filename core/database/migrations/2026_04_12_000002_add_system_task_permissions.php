<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected array $permissions = [
        'system_tasks.view' => 'View System Tasks',
        'system_tasks.manage_packages' => 'Manage System Task Packages',
        'system_tasks.site_update' => 'Run Site Update Tasks',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions_groups') || !Schema::hasTable('permissions')) {
            return;
        }

        $groupId = $this->getOrCreateGroup();
        foreach ($this->permissions as $key => $name) {
            $this->upsertPermission($groupId, $key, $name);
        }
        $this->assignPermissionsToAdmin();
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('role_id', 1)
                ->whereIn('permission', array_keys($this->permissions))
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->whereIn('key', array_keys($this->permissions))
                ->delete();
        }

        if (Schema::hasTable('permissions_groups')) {
            $group = DB::table('permissions_groups')->where('name', 'System Tasks')->first();
            if ($group) {
                $hasPermissions = Schema::hasTable('permissions')
                    && DB::table('permissions')->where('group_id', $group->id)->exists();
                if (!$hasPermissions) {
                    DB::table('permissions_groups')->where('id', $group->id)->delete();
                }
            }
        }
    }

    protected function getOrCreateGroup(): int
    {
        $group = DB::table('permissions_groups')
            ->where('name', 'System Tasks')
            ->first();

        if ($group) {
            return (int) $group->id;
        }

        try {
            return (int) DB::table('permissions_groups')->insertGetId([
                'name' => 'System Tasks',
                'lang_key' => 'system_tasks.permissions_group',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            $this->fixPostgresSequence('permissions_groups');

            $group = DB::table('permissions_groups')->where('name', 'System Tasks')->first();
            if ($group) {
                return (int) $group->id;
            }

            throw $exception;
        }
    }

    protected function upsertPermission(int $groupId, string $key, string $name): void
    {
        $exists = DB::table('permissions')->where('key', $key)->first();

        if ($exists) {
            DB::table('permissions')
                ->where('key', $key)
                ->update([
                    'name' => $name,
                    'lang_key' => '',
                    'group_id' => $groupId,
                    'disabled' => 0,
                    'updated_at' => now(),
                ]);
            return;
        }

        try {
            DB::table('permissions')->insert([
                'key' => $key,
                'name' => $name,
                'lang_key' => '',
                'group_id' => $groupId,
                'disabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            DB::table('permissions')
                ->where('key', $key)
                ->update([
                    'name' => $name,
                    'lang_key' => '',
                    'group_id' => $groupId,
                    'disabled' => 0,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function assignPermissionsToAdmin(): void
    {
        if (!Schema::hasTable('role_permissions')) {
            return;
        }

        foreach (array_keys($this->permissions) as $permission) {
            $exists = DB::table('role_permissions')
                ->where('role_id', 1)
                ->where('permission', $permission)
                ->exists();

            if ($exists) {
                continue;
            }

            try {
                DB::table('role_permissions')->insert([
                    'role_id' => 1,
                    'permission' => $permission,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $exception) {
                // Ignore duplicate race.
            }
        }
    }

    protected function fixPostgresSequence(string $table): void
    {
        try {
            $fullTable = DB::getTablePrefix() . $table;
            $maxId = DB::table($table)->max('id') ?? 0;
            DB::statement("SELECT setval(pg_get_serial_sequence('{$fullTable}', 'id'), " . ($maxId + 1) . ", false)");
        } catch (\Exception $exception) {
            // Ignore if not PostgreSQL or insufficient privileges.
        }
    }
};
