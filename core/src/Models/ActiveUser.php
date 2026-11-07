<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\ActiveUser
 *
 * @property string $sid
 * @property int $internalKey
 * @property string $username
 * @property int $lasthit
 * @property string $action
 * @property int $id
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\ActiveUser locked($action, $id = null, $userId = null, ?int $aliveSince = null)
 *
 * @mixin \Eloquent
 */
class ActiveUser extends BaseModel
{
    protected $table = 'active_users';

    protected $primaryKey = 'sid';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'internalKey' => 'int',
        'lasthit'     => 'int',
        'id'          => 'int'
    ];

    protected $fillable = [
        'internalKey',
        'username',
        'lasthit',
        'action',
        'id'
    ];

    public function scopeLocked(Eloquent\Builder $builder, $action, $id = null, $userId = null, ?int $aliveSince = null)
    {
        if ($userId === null) {
            $userId = evo()->getLoginUserID();
        }
        if ($aliveSince === null) {
            $aliveSince = ActiveUserSession::validSince(time(), (int) evo()->getConfig('session_timeout'));
        }

        // A row stays here after its manager is gone (closed browser, expired session) until they open
        // another page, so only a manager who is still signed in holds the page.
        $builder = $builder->where('action', '=', (int)$action)
            ->where('internalKey', '!=', $userId)
            ->whereIn('internalKey', ActiveUserSession::query()->select('internalKey')->where('lasthit', '>=', $aliveSince))
            ->orderBy('lasthit', 'DESC');

        if ($id !== null) {
            $builder = $builder->where('id', '=', $id);
        }

        return $builder;
    }
}
