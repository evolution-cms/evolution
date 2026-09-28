<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\ActiveUserSession
 *
 * @property string $sid
 * @property int $internalKey
 * @property int $lasthit
 * @property string $ip
 *
 * @mixin \Eloquent
 */
class ActiveUserSession extends Eloquent\Model
{
    protected $table = 'active_user_sessions';

    protected $primaryKey = 'sid';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'internalKey' => 'int',
        'lasthit'     => 'int'
    ];

    protected $fillable = [
        'sid',
        'internalKey',
        'lasthit',
        'ip'
    ];
}
