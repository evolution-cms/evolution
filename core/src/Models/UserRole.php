<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\UserRole
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $frames
 * @property int $home
 * @property int $view_document
 * @property int $new_document
 * @property int $save_document
 * @property int $publish_document
 * @property int $delete_document
 * @property int $empty_trash
 * @property int $action_ok
 * @property int $logout
 * @property int $help
 * @property int $messages
 * @property int $new_user
 * @property int $edit_user
 * @property int $logs
 * @property int $edit_parser
 * @property int $save_parser
 * @property int $edit_template
 * @property int $settings
 * @property int $credits
 * @property int $new_template
 * @property int $save_template
 * @property int $delete_template
 * @property int $edit_snippet
 * @property int $new_snippet
 * @property int $save_snippet
 * @property int $delete_snippet
 * @property int $edit_chunk
 * @property int $new_chunk
 * @property int $save_chunk
 * @property int $delete_chunk
 * @property int $empty_cache
 * @property int $edit_document
 * @property int $change_password
 * @property int $error_dialog
 * @property int $about
 * @property int $category_manager
 * @property int $file_manager
 * @property int $assets_files
 * @property int $assets_images
 * @property int $save_user
 * @property int $delete_user
 * @property int $save_password
 * @property int $edit_role
 * @property int $save_role
 * @property int $delete_role
 * @property int $new_role
 * @property int $access_permissions
 * @property int $bk_manager
 * @property int $new_plugin
 * @property int $edit_plugin
 * @property int $save_plugin
 * @property int $delete_plugin
 * @property int $new_module
 * @property int $edit_module
 * @property int $save_module
 * @property int $delete_module
 * @property int $exec_module
 * @property int $view_eventlog
 * @property int $delete_eventlog
 * @property int $new_web_user
 * @property int $edit_web_user
 * @property int $save_web_user
 * @property int $delete_web_user
 * @property int $web_access_permissions
 * @property int $view_unpublished
 * @property int $import_static
 * @property int $export_static
 * @property int $remove_locks
 * @property int $display_locks
 * @property int $change_resourcetype
 *
 * Virtual
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 * @property-read mixed $already_edit_info
 * @property-read mixed $is_already_edit
 *
 * @mixin \Eloquent
 */
class UserRole extends Eloquent\Model
{
    use Traits\Models\ManagerActions;

	public $timestamps = false;

	protected $casts = [
		'frames' => 'int',
		'home' => 'int',
	];


	protected $fillable = [
		'name',
		'description',
	];

    protected $managerActionsMap = [
        'actions.new' => 38,
        'id' => [
            'actions.edit' => 35
        ]
    ];

    /**
     * @return Eloquent\Relations\BelongsToMany
     */
    public function tvs() : Eloquent\Relations\BelongsToMany
    {

        return $this->belongsToMany(
            SiteTmplvar::class,
            (new UserRoleVar())->getTable(),
            'roleid',
            'tmplvarid'
        )->withPivot('rank')
            ->orderBy('pivot_rank', 'ASC');
    }

    public static function getLockedElements()
    {
        return evo()->getLockedElements(8);
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
