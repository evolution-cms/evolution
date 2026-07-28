<?php namespace EvolutionCMS\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent;
use Illuminate\Support\Carbon;
use EvolutionCMS\Traits;
use EvolutionCMS\Support\SiteTimezone;

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

    public const MAIL_SENT_SOURCE_FALLBACK = 'Mailer';
    public const SOURCE_DISPLAY_LIMIT = 50;

    private const MAIL_BODY_MARKER = '<!-- EvolutionCMS mail body: ';

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

    /**
     * Prepare a mail subject for storage in the Event Log source field.
     *
     * @since 3.5.8
     */
    public static function mailSentListSource(string $subject): string
    {
        $subject = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $subject);
        if (!is_string($subject)) {
            return static::MAIL_SENT_SOURCE_FALLBACK;
        }

        $subject = preg_replace('/\s+/u', ' ', trim($subject));
        if (!is_string($subject) || $subject === '') {
            return static::MAIL_SENT_SOURCE_FALLBACK;
        }

        if (extension_loaded('mbstring')) {
            if (mb_strlen($subject, 'UTF-8') <= static::SOURCE_DISPLAY_LIMIT) {
                return $subject;
            }

            return mb_substr($subject, 0, static::SOURCE_DISPLAY_LIMIT - 3, 'UTF-8') . '...';
        }

        if (strlen($subject) <= static::SOURCE_DISPLAY_LIMIT) {
            return $subject;
        }

        return substr($subject, 0, static::SOURCE_DISPLAY_LIMIT - 3) . '...';
    }

    /**
     * Append the successful message body in a format safe for the event description.
     *
     * @param string $description Existing event description.
     * @param string $body Exact mail body accepted by the transport.
     * @return string Description with the encoded mail body.
     *
     * @since 3.5.8
     */
    public static function appendMailBody(string $description, string $body): string
    {
        return $description . "\n" . static::MAIL_BODY_MARKER . base64_encode($body) . ' -->';
    }

    /**
     * Return the encoded body for a successful mail event when present.
     *
     * @return string|null Exact accepted mail body or null for legacy/non-mail events.
     *
     * @since 3.5.8
     */
    public function mailBody(): ?string
    {
        if (!$this->isMailSentType()) {
            return null;
        }

        $description = (string)$this->getRawOriginal('description');
        $markerPosition = strrpos($description, static::MAIL_BODY_MARKER);
        if ($markerPosition === false) {
            return null;
        }

        $encodedBody = substr($description, $markerPosition + strlen(static::MAIL_BODY_MARKER));
        if (!is_string($encodedBody) || !str_ends_with($encodedBody, ' -->')) {
            return null;
        }

        $body = base64_decode(substr($encodedBody, 0, -4), true);

        return is_string($body) ? $body : null;
    }

    /**
     * Safely present a selected source alias in the legacy Event Viewer DataGrid.
     *
     * Non-mail rows intentionally retain their historical rendering behavior.
     *
     * @since 3.5.8
     */
    public function getListSourceAttribute(?string $source): string
    {
        if (!$this->isMailSentType()) {
            return (string)$source;
        }

        $source = trim((string)$source);
        if ($source === '') {
            $source = static::MAIL_SENT_SOURCE_FALLBACK;
        }

        return htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format the stored legacy event timestamp in the selected site timezone.
     *
     * Event timestamps historically include server_offset_time when they are written,
     * so presentation must not add that legacy offset a second time.
     *
     * @since 3.5.8
     */
    public static function formatStoredTimestamp(
        int|string|DateTimeInterface|null $timestamp,
        ?string $timezone,
        string $format
    ): string {
        if ($timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (is_string($timestamp)) {
            $timestamp = trim($timestamp);
            if ($timestamp === '' || !ctype_digit($timestamp)) {
                return '-';
            }
        }

        if (empty($timestamp)) {
            return '-';
        }

        return Carbon::createFromTimestamp(
            (int)$timestamp,
            SiteTimezone::resolve($timezone)
        )->format($format);
    }

    /**
     * Present a selected timestamp alias in the Event Viewer DataGrid.
     *
     * @since 3.5.8
     */
    public function getListCreatedAtAttribute(int|string|null $timestamp): string
    {
        return static::formatStoredTimestamp(
            $timestamp,
            evo()->getConfig('site_timezone'),
            evo()->normalizeFormat()
        );
    }

    /**
     * Escape an authenticated username selected for Event Viewer presentation.
     *
     * An empty result is intentionally left for the view's localized System fallback.
     *
     * @since 3.5.8
     */
    public function getListUsernameAttribute(?string $username): string
    {
        $username = trim((string)$username);

        return $username === ''
            ? ''
            : htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
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
