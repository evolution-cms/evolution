<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;

/**
 * EvolutionCMS\Models\SiteTmplvarContentvalue
 *
 * @property int $id
 * @property int $tmplvarid
 * @property int $contentid
 * @property string $value
 *
 * @mixin \Eloquent
 */
class SiteTmplvarContentvalue extends Eloquent\Model
{
	protected $table = 'site_tmplvar_contentvalues';

	public $timestamps = false;

	protected $casts = [
		'tmplvarid' => 'int',
		'contentid' => 'int'
	];

	protected $fillable = [
		'tmplvarid',
		'contentid',
		'value'
	];

    public function resource()
    {
        return $this->belongsTo(SiteContent::class, 'contentid', 'id');
    }

    public function tmplvar()
    {
        return $this->belongsTo(SiteTmplvar::class, 'tmplvarid', 'id');
    }
}
