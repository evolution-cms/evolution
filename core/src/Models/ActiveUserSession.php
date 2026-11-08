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
class ActiveUserSession extends BaseModel
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

    /**
     * Oldest lasthit of a session that still counts as signed in: session_timeout minutes, at least 2.
     * An open manager pings every half timeout (evo.keepMeAlive), so it never falls out of this window.
     */
    public static function validSince(int $now, int $timeoutMinutes): int
    {
        return $now - max(2, $timeoutMinutes) * 60;
    }
}
