<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteModuleRole
 *
 * Per-role module ACL: a module with no rows here is available to every role,
 * a module with rows is available only to the roles listed (plus the admin role).
 *
 * @property int $id
 * @property int $module
 * @property int $role
 *
 * @mixin \Eloquent
 */
class SiteModuleRole extends Eloquent\Model
{
	protected $table = 'site_module_roles';
	public $timestamps = false;

	protected $casts = [
		'module' => 'int',
		'role' => 'int'
	];

	protected $fillable = [
		'module',
		'role'
	];

	/**
	 * Apply the shipped default restriction for a freshly created bundled module.
	 *
	 * Called from every install path so a new site restricts Extras to the
	 * administrator role the same way the migration does for existing sites.
	 * Modules that already carry a restriction are left alone, so an
	 * administrator who opened one up keeps that decision across updates.
	 *
	 * @param int $moduleId
	 * @param string $moduleName
	 * @return void
	 */
	public static function applyDefaultsFor(int $moduleId, string $moduleName): void
	{
		$roleIds = \EvolutionCMS\Support\ModuleAccess::defaultRolesFor($moduleName);
		if ($moduleId <= 0 || $roleIds === []) {
			return;
		}

		if (self::query()->where('module', $moduleId)->exists()) {
			return;
		}

		foreach ($roleIds as $roleId) {
			if (!UserRole::query()->where('id', $roleId)->exists()) {
				continue;
			}

			self::query()->firstOrCreate([
				'module' => $moduleId,
				'role' => (int) $roleId,
			]);
		}
	}
}
