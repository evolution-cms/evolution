<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteModuleDepobj
 *
 * @property int $id
 * @property int $module
 * @property int $resource
 * @property int $type
 *
 * @mixin \Eloquent
 */
class SiteModuleDepobj extends BaseModel
{
	protected $table = 'site_module_depobj';
	public $timestamps = false;

	protected $casts = [
		'module' => 'int',
		'resource' => 'int',
		'type' => 'int'
	];

	protected $fillable = [
		'module',
		'resource',
		'type'
	];
}
