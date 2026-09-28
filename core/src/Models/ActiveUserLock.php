<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\ActiveUserLock
 *
 * @property int $id
 * @property string $sid
 * @property int $internalKey
 * @property int $elementType
 * @property int $elementId
 * @property int $lasthit
 *
 * @mixin \Eloquent
 */
class ActiveUserLock extends Eloquent\Model
{
	protected $table = 'active_user_locks';

	public $timestamps = false;

	protected $casts = [
		'internalKey' => 'int',
		'elementType' => 'int',
		'elementId' => 'int',
		'lasthit' => 'int'
	];

	protected $fillable = [
		'sid',
		'internalKey',
		'elementType',
		'elementId',
		'lasthit'
	];
}
