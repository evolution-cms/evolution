<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\RolePermissions
 *
 * @property int $id
 * @property string $permission
 * @property string $role_id
 * @property int $createdon
 * @property int $editedon
 *
 * Virtual
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 *
 * @mixin \Eloquent
 */
class RolePermissions extends Eloquent\Model
{
	protected $table = 'role_permissions';

	protected $fillable = [
		'permission',
		'role_id',
	];

}
