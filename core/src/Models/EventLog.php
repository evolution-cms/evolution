<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\EventLog
 *
 * @property int $id
 * @property int $eventid
 * @property int $createdon
 * @property int $type
 * @property int $user
 * @property int $usertype
 * @property string $source
 * @property string $description
 *
 * BelongsTo
 * @property null|ManagerUser $mgruser
 * @property null|User $webuser
 *
 * Virtual
 * @property-read \Carbon\Carbon $created_at
 *
 * @mixin \Eloquent
 */
class EventLog extends Eloquent\Model
{
    use Traits\Models\ManagerActions,
        Traits\Models\TimeMutator;

	protected $table = 'event_log';

    const CREATED_AT = 'createdon';
    const UPDATED_AT = null;
    protected $dateFormat = 'U';

	protected $casts = [
		'eventid' => 'int',
		'type' => 'int',
		'user' => 'int',
		'usertype' => 'int'
	];

	protected $fillable = [
		'eventid',
		'type',
		'user',
		'usertype',
		'source',
		'description'
	];

    public const TYPE_INFORMATION = 1;
    public const TYPE_WARNING = 2;
    public const TYPE_ERROR = 3;
    public const TYPE_MAIL_SENT = 4;

    public const USER_MGR = 0;
    public const USER_WEB = 1;

    public function isInformationType() : bool
    {
        return $this->type === static::TYPE_INFORMATION;
    }

    public function isWarningType() : bool
    {
        return $this->type === static::TYPE_WARNING;
    }

    public function isErrorType() : bool
    {
        return $this->type === static::TYPE_ERROR;
    }

    /**
     * Determine whether the event confirms that a mail transport accepted a message.
     *
     * @since 3.5.8
     */
    public function isMailSentType(): bool
    {
        return $this->type === static::TYPE_MAIL_SENT;
    }

    public function getCreatedAtAttribute()
    {
        return $this->convertTimestamp($this->createdon);
    }

    public function getUser()
    {
        $out = null;
        switch ($this->usertype) {
            case static::USER_WEB:
                $out = $this->webuser;
                break;
            case static::USER_MGR:
                $out = $this->mgruser;
                break;
        }
        return $out;
    }

    public function webuser() : Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }

    public function mgruser() : Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }
}
