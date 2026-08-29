<?php namespace EvolutionCMS\Models;

use EvolutionCMS\Support\ModuleAccess;
use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SiteModule
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $editor_type
 * @property int $disabled
 * @property int $category
 * @property int $wrap
 * @property int $locked
 * @property string $icon
 * @property int $enable_resource
 * @property string $resourcefile
 * @property int $createdon
 * @property int $editedon
 * @property string $guid
 * @property int $enable_sharedparams
 * @property string $properties
 * @property string $modulecode
 *
 * BelongsTo
 * @property null|Category $categories
 *
 * Virtual
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 * @property-read mixed $already_edit_info
 * @property-read mixed $is_already_edit
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteModule lockedView()
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteModule withoutProtected()
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteModule allowedForRole(int $roleId)
 *
 * @mixin \Eloquent
 */
class SiteModule extends Eloquent\Model
{
    use Traits\Models\ManagerActions,
        Traits\Models\TimeMutator;

	const CREATED_AT = 'createdon';
	const UPDATED_AT = 'editedon';
    protected $dateFormat = 'U';

	protected $casts = [
		'editor_type' => 'int',
		'disabled' => 'int',
		'category' => 'int',
		'wrap' => 'int',
		'locked' => 'int',
		'enable_resource' => 'int',
		'createdon' => 'int',
		'editedon' => 'int',
		'enable_sharedparams' => 'int'
	];

	protected $fillable = [
		'name',
		'description',
		'editor_type',
		'disabled',
		'category',
		'wrap',
		'locked',
		'icon',
		'enable_resource',
		'resourcefile',
		'guid',
		'enable_sharedparams',
		'properties',
		'modulecode'
	];

	protected $managerActionsMap = [
        'actions.cancel' => 76,
        'actions.new' => 107,
        'id' => [
            'actions.edit' => 108,
            'actions.save' => 109,
            'actions.enable' => 109,
            'actions.disable' => 109,
            'actions.delete' => 110,
            'actions.duplicate' => 111,
            'actions.run' => 112,
            'actions.dependency' => 113
        ]
    ];

    public function categories() : Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    public function categoryName($default = '')
    {
        return $this->categories === null ? $default : $this->categories->category;
    }

    public function categoryId()
    {
        return $this->categories === null ? null : $this->categories->getKey();
    }

    public function getCreatedAtAttribute()
    {
        return $this->convertTimestamp($this->createdon);
    }

    public function getUpdatedAtAttribute()
    {
        return $this->convertTimestamp($this->editedon);
    }

    public function scopeLockedView(Eloquent\Builder $builder)
    {
        return evo()->getLoginUserID('mgr') !== 1 ?
            $builder->where('locked', '=', 0) : $builder;
    }

    /**
     * Roles this module is restricted to. No rows means every role may run it.
     *
     * @return Eloquent\Relations\HasMany
     */
    public function roles()
    {
        return $this->hasMany(SiteModuleRole::class, 'module', 'id');
    }

    /**
     * Hide modules the current manager user may not run.
     *
     * Two independent axes, both of which must pass: the user-group ACL
     * (site_module_access, only when use_udperms is on) and the role ACL
     * (site_module_roles, which is a property of the role and therefore
     * applies whether or not user-document permissions are enabled).
     */
    public function scopeWithoutProtected(Eloquent\Builder $builder)
    {
        $roleId = (int) get_by_key($_SESSION, 'mgrRole', 0);
        if ($roleId === ModuleAccess::ADMIN_ROLE) {
            return $builder;
        }

        if (evo()->getConfig('use_udperms')) {
            // the joins below bring in columns of their own, so make sure a
            // caller that did not name any gets the module row, not a mix of
            // module and member_groups columns sharing the id name
            if (empty($builder->getQuery()->columns)) {
                $builder->select('site_modules.*');
            }

            $builder->leftJoin('site_module_access', 'site_module_access.module', '=', 'site_modules.id')
                ->leftJoin('member_groups', 'member_groups.user_group', '=', 'site_module_access.usergroup')
                ->where(function (Eloquent\Builder $query) {
                    $query->whereNull('site_module_access.usergroup')
                        ->orWhere('member_groups.member', '=', (int)evo()->getLoginUserID('mgr'));
                });
        }

        return $builder->allowedForRole($roleId);
    }

    /**
     * Restrict to modules the given role may run: those with no role
     * restriction at all, plus those the role is explicitly listed on.
     */
    public function scopeAllowedForRole(Eloquent\Builder $builder, int $roleId)
    {
        if ($roleId === ModuleAccess::ADMIN_ROLE) {
            return $builder;
        }

        return $builder->where(function (Eloquent\Builder $query) use ($roleId) {
            $query->whereNotExists(function ($sub) {
                $sub->selectRaw(1)
                    ->from('site_module_roles')
                    ->whereColumn('site_module_roles.module', 'site_modules.id');
            })->orWhereExists(function ($sub) use ($roleId) {
                $sub->selectRaw(1)
                    ->from('site_module_roles')
                    ->whereColumn('site_module_roles.module', 'site_modules.id')
                    ->where('site_module_roles.role', '=', $roleId);
            });
        });
    }

    public static function getLockedElements()
    {
        return evo()->getLockedElements(6);
    }

    public function getIsAlreadyEditAttribute()
    {
        return array_key_exists($this->getKey(), self::getLockedElements());
    }

    public function getAlreadyEditInfoAttribute() :? array
    {
        return $this->isAlreadyEdit ? self::getLockedElements()[$this->getKey()] : null;
    }
}
