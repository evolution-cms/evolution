<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\WebUser
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $cachepwd
 * @property string $refresh_token
 * @property string $access_token
 * @property string $valid_to
 *
 * @mixin \Eloquent
 */
class User extends Eloquent\Model
{
    use Traits\Models\ManagerActions;

	public $timestamps = false;

	protected $hidden = [
		'password',
        'cachepwd',
        'cachepwd_valid_to',
        'verified_key',
	];

	// `cachepwd_valid_to` is deliberately absent: the recovery token's deadline is only
	// ever set by PasswordRecoveryService through direct assignment, so there is no
	// reason to expose it to mass assignment from request data.
	protected $fillable = [
		'username',
		'password',
		'cachepwd',
		'verified_key',
		'refresh_token',
		'access_token',
		'valid_to'
	];

    public function attributes()
    {
        return $this->hasOne(UserAttribute::class,'internalKey','id');
    }

    public function memberGroups()
    {
        return $this->hasMany(MemberGroup::class,'member','id');
    }

    public function settings()
    {
        return $this->hasMany(UserSetting::class,'user','id');
    }

    public function values()
    {
        return $this->hasMany(UserValue::class,'userid','id');
    }

    public function delete()
    {
        $this->memberGroups()->delete();
        $this->attributes()->delete();
        $this->settings()->delete();

        return parent::delete();
    }
}
