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
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\ActiveUser locked($action, $id = null, $userId = null)
 *
 * @mixin \Eloquent
 */
class ActiveUser extends Eloquent\Model
{
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

    public function scopeLocked(Eloquent\Builder $builder, $action, $id = null, $userId = null)
    {
        if ($userId === null) {
            $userId = evo()->getLoginUserID();
        }

        $builder = $builder->where('action', '=', (int)$action)
            ->where('internalKey', '!=', $userId)
            ->orderBy('lasthit', 'DESC');

        if ($id !== null) {
            $builder = $builder->where('id', '=', $id);
        }

        return $builder;
    }
}
