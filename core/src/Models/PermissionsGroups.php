<?php namespace EvolutionCMS\Models;

use EvolutionCMS\Traits\Models\ManagerActions;
use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\PermissionsGroups
 *
 * @property int $id
 * @property string $name
 * @property string $lang_key
 * @property int $createdon
 * @property int $editedon
 *
 * Virtual
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 *
 * @mixin \Eloquent
 */
class PermissionsGroups extends Eloquent\Model
{
    use ManagerActions;

    protected $table = 'permissions_groups';

    protected $managerActionsMap = [
        'actions.cancel' => 86,
        'actions.new' => 136,
        'id' => [
            'actions.edit' => 136,
            'actions.save' => 136,
            'actions.delete' => 136
        ]
    ];

	protected $fillable = [
		'name',
		'lang_key',
	];

    public function permissions()
    {
        return $this->hasMany(Permissions::class,'group_id','id');
    }
}
