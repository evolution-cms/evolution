<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteModuleAccess
 *
 * @property int $id
 * @property int $module
 * @property int $usergroup
 *
 * @mixin \Eloquent
 */
class SiteModuleAccess extends BaseModel
{
	protected $table = 'site_module_access';
	public $timestamps = false;

	protected $casts = [
		'module' => 'int',
		'usergroup' => 'int'
	];

	protected $fillable = [
		'module',
		'usergroup'
	];
}
