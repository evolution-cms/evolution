<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSystemCliTasksTables extends Migration
{
    protected array $permissions = [
        'system_tasks.view' => 'View System Tasks',
        'system_tasks.manage_packages' => 'Manage System Task Packages',
        'system_tasks.site_update' => 'Run Site Update Tasks',
    ];

    public function up()
    {
        if (!Schema::hasTable('system_cli_tasks')) {
            Schema::create('system_cli_tasks', function (Blueprint $table) {
                $indexPrefix = \DB::getTablePrefix() . $table->getTable();

                $table->increments('id');
                $table->string('uuid', 36)->unique("{$indexPrefix}_uuid");
                $table->string('type', 64)->index("{$indexPrefix}_type");
                $table->string('target', 191)->default('')->index("{$indexPrefix}_target");
                $table->string('requested_version', 191)->default('');
                $table->string('status', 32)->default('queued')->index("{$indexPrefix}_status");
                $table->string('step', 64)->default('');
                $table->unsignedSmallInteger('progress')->default(0);
                $table->string('message', 255)->default('');
                $table->longText('payload_json')->nullable();
                $table->longText('result_json')->nullable();
                $table->unsignedInteger('created_by')->nullable()->index("{$indexPrefix}_created_by");
                $table->string('locked_by', 191)->default('');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->dateTime('lease_expires_at')->nullable()->index("{$indexPrefix}_lease_expires_at");
                $table->string('worker_host', 191)->default('');
                $table->integer('worker_pid')->nullable();
                $table->string('error_code', 64)->default('')->index("{$indexPrefix}_error_code");
                $table->string('catalog_snapshot_hash', 64)->default('');
                $table->longText('requested_by_snapshot')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('heartbeat_at')->nullable();
                $table->dateTime('cancellation_requested_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->index(['status', 'created_at'], "{$indexPrefix}_status_created_at");
                $table->index(['type', 'status'], "{$indexPrefix}_type_status");
            });
        }

        if (!Schema::hasTable('system_cli_task_logs')) {
            Schema::create('system_cli_task_logs', function (Blueprint $table) {
                $indexPrefix = \DB::getTablePrefix() . $table->getTable();

                $table->increments('id');
                $table->unsignedInteger('task_id')->index("{$indexPrefix}_task_id");
                $table->unsignedInteger('seq')->default(0);
                $table->string('level', 16)->default('info');
                $table->string('step', 64)->default('');
                $table->text('message');
                $table->longText('context_json')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->index(['task_id', 'seq'], "{$indexPrefix}_task_seq");
            });
        }

        if (!Schema::hasTable('system_scheduler_health')) {
            Schema::create('system_scheduler_health', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->dateTime('last_heartbeat_at')->nullable();
                $table->string('last_heartbeat_host', 191)->default('');
                $table->string('last_heartbeat_mode', 32)->default('');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('system_worker_health')) {
            Schema::create('system_worker_health', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->dateTime('last_worker_run_at')->nullable();
                $table->dateTime('last_worker_pick_at')->nullable();
                $table->dateTime('last_worker_success_at')->nullable();
                $table->dateTime('last_worker_failed_at')->nullable();
                $table->string('last_worker_error_code', 64)->default('');
                $table->string('last_worker_host', 191)->default('');
                $table->integer('last_worker_pid')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!$this->hasAclTables()) {
            return;
        }

        $this->repairAclBaselineIfNeeded();

        $groupId = $this->getOrCreateGroup();
        foreach ($this->permissions as $key => $name) {
            $this->upsertPermission($groupId, $key, $name);
        }
        $this->assignPermissionsToAdmin();
    }

    public function down()
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

        Schema::dropIfExists('system_worker_health');
        Schema::dropIfExists('system_scheduler_health');
        Schema::dropIfExists('system_cli_task_logs');
        Schema::dropIfExists('system_cli_tasks');
    }

    protected function hasAclTables(): bool
    {
        return Schema::hasTable('permissions_groups')
            && Schema::hasTable('permissions')
            && Schema::hasTable('user_roles')
            && Schema::hasTable('role_permissions');
    }

    protected function repairAclBaselineIfNeeded(): void
    {
        if (!$this->needsAclBaselineRepair()) {
            return;
        }

        (new \Database\Seeders\UserPermissionsTableSeeder())->run();
        (new \Database\Seeders\UserRolesTableSeeder())->run();
    }

    protected function needsAclBaselineRepair(): bool
    {
        if (!DB::table('user_roles')->where('id', 1)->exists()) {
            return true;
        }

        if (!DB::table('permissions')->where('key', 'access_permissions')->exists()) {
            return true;
        }

        if (!DB::table('role_permissions')->where('role_id', 1)->where('permission', 'access_permissions')->exists()) {
            return true;
        }

        if (DB::table('permissions_groups')->count() < 14) {
            return true;
        }

        return false;
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
}
