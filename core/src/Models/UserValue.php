<?php

namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EvolutionCMS\Models\UserValue
 *
 * @property int $id
 * @property int $tmplvarid
 * @property int $userid
 * @property string $value
 *
 * @mixin \Eloquent
 */
class UserValue extends Model
{
	protected $table = 'user_values';

	public $timestamps = false;

	protected $casts = [
		'tmplvarid' => 'int',
		'userid' => 'int'
	];

	protected $fillable = [
		'tmplvarid',
		'userid',
		'value'
	];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'id');
    }

    public function tmplvar()
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
