<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\WebUserSetting
 *
 * @property int $webuser
 * @property string $setting_name
 * @property string $setting_value
 *
 * @mixin \Eloquent
 */
class UserSetting extends Eloquent\Model
{
    protected $table = 'user_settings';

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'user' => 'int'
    ];

    protected $fillable = [
        'user',
        'setting_name',
        'setting_value'
    ];

    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('user', $this->attributes['user'])
            ->where('setting_name', $this->attributes['setting_name']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user','id');
    }
}
