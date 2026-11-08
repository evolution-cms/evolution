<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\ManagerLog
 *
 * @property int $id
 * @property int $timestamp
 * @property int $internalKey
 * @property string $username
 * @property int $action
 * @property string $itemid
 * @property string $itemname
 * @property string $message
 * @property string $ip
 * @property string $useragent
 *
 * @mixin \Eloquent
 */
class ManagerLog extends BaseModel
{
	protected $table = 'manager_log';
	public $timestamps = false;

	protected $casts = [
		'timestamp' => 'int',
		'internalKey' => 'int',
		'action' => 'int'
	];

	protected $fillable = [
		'timestamp',
		'internalKey',
		'username',
		'action',
		'itemid',
		'itemname',
		'message',
		'ip',
		'useragent'
	];
}
