<?php

use EvolutionCMS\Support\ModuleAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-role module ACL.
 *
 * Until now a module could only be restricted by user group, which is a
 * different axis than the role a manager user actually has: any user holding
 * exec_module could run any unrestricted module, the bundled Extras installer
 * included. This table answers "which modules may this role run" directly,
 * and is edited from the role screen where the question is asked.
 *
 * No rows for a module means unrestricted, so nothing changes for existing
 * modules. The one exception is Extras, restricted to Administrator here for
 * sites that already have it - fresh installs get the same defaults when the
 * installer creates the module.
 */
class CreateSiteModuleRolesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('site_module_roles')) {
            Schema::create('site_module_roles', function (Blueprint $table) {
                $table->comment('Module role access control - defines which user roles may run specific modules');
                $table->increments('id');
                $table->unsignedInteger('module')->default(0);
                $table->unsignedInteger('role')->default(0);
                $table->unique(['module', 'role'], 'site_module_roles_module_role');
            });
        }

        $this->applyDefaultRestrictions();
    }

    public function down()
    {
        Schema::dropIfExists('site_module_roles');
    }

    /**
     * Restrict bundled modules on sites that already have them installed.
     *
     * Only modules with no restriction at all are touched, so an administrator
     * who has opened a module up keeps that decision.
     */
    protected function applyDefaultRestrictions(): void
    {
        if (!Schema::hasTable('site_modules') || !Schema::hasTable('user_roles')) {
            return;
        }

        foreach (ModuleAccess::DEFAULT_RESTRICTIONS as $name => $roleIds) {
            $moduleId = (int) DB::table('site_modules')->where('name', $name)->value('id');
            if ($moduleId <= 0) {
                continue;
            }

            if (DB::table('site_module_roles')->where('module', $moduleId)->exists()) {
                continue;
            }

            foreach (ModuleAccess::normalizeRoleIds($roleIds) as $roleId) {
                if (!DB::table('user_roles')->where('id', $roleId)->exists()) {
                    continue;
                }

                DB::table('site_module_roles')->insert([
                    'module' => $moduleId,
                    'role' => $roleId,
                ]);
            }
        }
    }
}
