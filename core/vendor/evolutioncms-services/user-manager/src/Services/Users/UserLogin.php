<?php namespace EvolutionCMS\UserManager\Services\Users;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\UserManager\Interfaces\UserServiceInterface;
use \EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserLogin implements UserServiceInterface
{
    use SafelyDestroyUserSessionTrait;

    /**
     * @var \string[][]
     */
    public $validate;

    /**
     * @var array
     */
    public $messages;

    /**
     * @var array
     */
    public $userData;

    /**
     * @var bool
     */
    public $events;

    /**
     * @var bool
     */
    public $cache;

    /**
     * @var array $validateErrors
     */
    public $validateErrors;

    /**
     * @var User
     */
    public $user;
    /**
     * @var int
     */
    protected $blockedMinutes;
    /**
     * @var int
     */
    protected $failedLoginAttempts;

    /**
     * @var
     */
    protected $userSettings;

    /**
     * @var string
     */
    protected $context;

    /**
     * UserRegistration constructor.
     * @param array $userData
     * @param bool $events
     * @param bool $cache
     */
    public function __construct(array $userData, bool $events = true, bool $cache = true)
    {
        $this->context = evo()->getContext();
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
        $this->blockedMinutes = EvolutionCMS()->getConfig('blocked_minutes');
        $this->failedLoginAttempts = EvolutionCMS()->getConfig('failed_login_attempts');
        $this->userData = $userData;
        $this->events = $events;
        $this->cache = $cache;
    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [
            'username' => ['required'],
            'password' => ['required'],
            'context'  => ['nullable', 'in:web,mgr'],
        ];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [
            'username.required' => Lang::get("global.required_field", ['field' => 'username']),
            'password.required' => Lang::get("global.required_field", ['field' => 'password']),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws ServiceActionException
     * @throws ServiceValidationException
     */
    public function process(): \Illuminate\Database\Eloquent\Model
    {
        if (!$this->checkRules()) {
            throw new ServiceActionException(\Lang::get('global.error_no_privileges'));
        }

        if (!$this->validate()) {
            $exception = new ServiceValidationException();
            $exception->setValidationErrors($this->validateErrors);
            throw $exception;
        }

        if (isset($this->userData['context'])) {
            $this->context = $this->userData['context'];
        }

        if ($this->events) {
            // invoke OnBeforeManagerLogin event
            EvolutionCMS()->invokeEvent('OnBeforeManagerLogin', array(
                'username' => $this->userData['username'],
                'userpassword' => $this->userData['password'],
                'rememberme' => $this->userData['rememberme'] ?? false
            ));
        }

        $this->user = \EvolutionCMS\Models\User::query()
            ->where('username', $this->userData['username'])->first();
        if (is_null($this->user)) {
            throw new ServiceActionException(\Lang::get('global.login_processor_unknown_user'));
        }
        $this->userSettings = $this->user->settings->pluck('setting_value', 'setting_name')->toArray();

        $this->checkPassword();
        $this->validateAuth();
        $this->authProcess();
        $this->checkRemember();
        $this->clearActiveUsers();

        if ($this->events) {
            // invoke OnManagerLogin event
            EvolutionCMS()->invokeEvent('OnManagerLogin', array(
                'userid' => $this->user->getKey(),
                'username' => $this->user->username,
                'userpassword' => $this->userData['password'],
                'rememberme' => $this->userData['rememberme'] ?? false
            ));
        }

        return $this->user;
    }

    /**
     * @return bool
     */
    public function checkRules(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $validator = \Validator::make($this->userData, $this->validate, $this->messages);
        $this->validateErrors = $validator->errors()->toArray();
        return !$validator->fails();
    }

    /**
     * @return bool
     */
    public function validateAuth(): bool
    {
        // blocked due to number of login errors, but get to try again
        if ($this->user->attributes->failedlogincount >= $this->failedLoginAttempts
            && $this->user->attributes->blockeduntil < time()) {
            $this->user->attributes->failedlogincount = 0;
            $this->user->attributes->blockeduntil = time() - 1;
            $this->user->attributes->save();
        }

        try {
            // this user has been blocked by an admin, so no way he's loggin in!
            if ($this->user->attributes->blocked == '1') {
                throw new ServiceActionException(\Lang::get('global.login_processor_blocked1'));
            }

            if ($this->user->attributes->verified != 1) {
                throw new ServiceActionException(\Lang::get('global.login_processor_verified'));
            }

            // blockuntil: this user has a block until date
            if ($this->user->attributes->blockeduntil > time()) {
                throw new ServiceActionException(\Lang::get('global.login_processor_blocked2'));
            }

            // blockafter: this user has a block after date
            if ($this->user->attributes->blockedafter > 0 && $this->user->attributes->blockedafter < time()) {
                throw new ServiceActionException(\Lang::get('global.login_processor_blocked2'));
            }

            if (!$this->isUserHostCorrespondsToIP()) {
                throw new ServiceActionException(\Lang::get('global.login_processor_remotehost_ip'));
            }

            if (!$this->isUserHasAllowedIP()) {
                throw new ServiceActionException(\Lang::get('global.login_processor_remote_ip'));
            }

            if (!$this->isUserAllowedToLogInToday()) {
                throw new ServiceActionException(\Lang::get('global.login_processor_date'));
            }
        } catch (ServiceActionException $e) {
            $this->safelyDestroyUserSession();
            throw $e;
        }

        return true;
    }

    protected function isUserHostCorrespondsToIP(): bool
    {
        if (!isset($this->userSettings['allowed_ip'])) {
            return true;
        }

        $remoteAddress = request()->server('REMOTE_ADDR');
        $hostname = gethostbyaddr($remoteAddress);

        if (!$hostname || $hostname == $remoteAddress) {
            return false;
        }

        if (gethostbyname($hostname) == $remoteAddress) {
            return false;
        }

        return true;
    }

    protected function isUserHasAllowedIP()
    {
        if (!isset($this->userSettings['allowed_ip'])) {
            return true;
        }

        $ips = array_filter(array_map('trim', explode(',', $this->userSettings['allowed_ip'])));

        return in_array(request()->server('REMOTE_ADDR'), $ips);
    }

    protected function isUserAllowedToLogInToday()
    {
        if (!isset($this->userSettings['allowed_days'])) {
            return true;
        }

        $date = getdate();
        $day = $date['wday'] + 1;

        return in_array($day, explode(',', $this->userSettings['allowed_days']));
    }

    public function authProcess()
    {

        EvolutionCMS()->cleanupExpiredLocks();
        EvolutionCMS()->cleanupMultipleActiveUsers();
        if(!defined('NO_SESSION')) {
            $this->writeSession();
        }
        // successful login so reset fail count and update key values
        $this->user->attributes->failedlogincount = 0;
        $this->user->attributes->logincount += 1;
        $this->user->attributes->thislogin = time();
        $this->user->attributes->lastlogin = time();
        $this->user->attributes->save();

        $this->user->refresh_token = hash('sha256', Str::random(32));
        $this->user->access_token = hash('sha256', Str::random(32));
        $this->user->valid_to = Carbon::now()->addHours(11);
        $this->user->save();

        // get user's document groups
        $i = 0;


    }

    public function writeSession()
    {
        $currentsessionid = session_regenerate_id();

        $_SESSION['usertype'] = 'manager'; // user is a backend user
        // get permissions
        $_SESSION[$this->context . 'Shortname'] = $this->user->username;
        $_SESSION[$this->context . 'Fullname'] = $this->user->attributes->fullname;
        $_SESSION[$this->context . 'Email'] = $this->user->attributes->email;
        $_SESSION[$this->context . 'Validated'] = 1;
        $_SESSION[$this->context . 'InternalKey'] = $this->user->getKey();
        $_SESSION[$this->context . 'Failedlogins'] = $this->user->attributes->failedlogincount;
        $_SESSION[$this->context . 'Lastlogin'] = $this->user->attributes->lastlogin;
        $_SESSION[$this->context . 'Logincount'] = $this->user->attributes->logincount; // login count
        $_SESSION[$this->context . 'Role'] = $this->user->attributes->role;
        $_SESSION[$this->context . 'Permissions'] = [];
        $mgrPermissions = \EvolutionCMS\Models\UserRole::find($this->user->attributes->role);
        if (!is_null($mgrPermissions)) {
            $permissionsRole = $mgrPermissions->toArray();
            $roleArray = \EvolutionCMS\Models\RolePermissions::query()->where('role_id', $this->user->attributes->role)->pluck('permission')->toArray();
            foreach ($roleArray as $role) {
                $permissionsRole[$role] = 1;
            }
            $_SESSION[$this->context . 'Permissions'] = $permissionsRole;
        }
        $this->user->attributes->sessionid = $currentsessionid;

        $_SESSION[$this->context . 'Docgroups'] = \EvolutionCMS\Models\MemberGroup::query()
            ->join('membergroup_access', 'membergroup_access.membergroup', '=', 'member_groups.user_group')
            ->where('member_groups.member', $this->user->getKey())->pluck('documentgroup')->toArray();


        $_SESSION[$this->context . 'Token'] = md5($currentsessionid);

    }

    public function checkRemember()
    {

        if (isset($this->userData['rememberme']) && $this->userData['rememberme'] == 1) {
            $_SESSION['modx.' . $this->context . '.session.cookie.lifetime'] = (int)EvolutionCMS()->getConfig('session.cookie.lifetime');

            // Set a cookie separate from the session cookie with the username in it.
            // Are we using secure connection? If so, make sure the cookie is secure
            global $https_port;

            $secure = ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port);
            if (version_compare(PHP_VERSION, '5.2', '<')) {
                setcookie('modx_remember_manager', $_SESSION[$this->context . 'Shortname'], time() + 60 * 60 * 24 * 365, MODX_BASE_URL, '; HttpOnly', $secure);
            } else {
                setcookie('modx_remember_manager', $_SESSION[$this->context . 'Shortname'], time() + 60 * 60 * 24 * 365, MODX_BASE_URL, NULL, $secure, true);
            }
        } else {
            $_SESSION['modx.' .$this->context . '.session.cookie.lifetime'] = 0;

            // Remove the Remember Me cookie
            setcookie('modx_remember_manager', '', time() - 3600, MODX_BASE_URL);
        }
    }

    public function clearActiveUsers()
    {
        // Check if user already has an active session, if not check if user pressed logout end of last session
        $activeSession = \EvolutionCMS\Models\ActiveUserSession::where('internalKey', $this->user->getKey())->first();
        if (!is_null($activeSession)) {
            $lastHit = \EvolutionCMS\Models\ActiveUser::select('lasthit')->where('internalKey', $this->user->getKey())->where('action', '!=', 8)->first();
            if (!is_null($lastHit)) {
                $_SESSION['show_logout_reminder'] = array(
                    'type' => 'logout_reminder',
                    'lastHit' => $lastHit->lasthit
                );
            }
        }
    }


    public function incrementFailedLoginCount(): void
    {
        $this->user->attributes->failedlogincount += 1;

        if ($this->user->attributes->failedlogincount >= $this->failedLoginAttempts) //block user for too many fail attempts
        {
            $this->user->attributes->blockeduntil = time() + ($this->blockedMinutes * 60);
        }
        $this->user->attributes->save();

        if ($this->user->attributes->failedlogincount < $this->failedLoginAttempts) {
            //sleep to help prevent brute force attacks
            $sleep = (int)$this->user->attributes->failedlogincount / 2;
            if ($sleep > 5) {
                $sleep = 5;
            }
            sleep($sleep);
        }
    }

    public function checkPassword()
    {
        if ($this->events) {
            // invoke OnManagerAuthentication event
            $rt = EvolutionCMS()->invokeEvent('OnManagerAuthentication', array(
                'userid' => $this->user->getKey(),
                'username' => $this->user->username,
                'userpassword' => $this->userData['password'],
                'savedpassword' => $this->user->password,
                'rememberme' => $this->userData['rememberme']
            ));
        }

        // check if plugin authenticated the user
        $matchPassword = false;
        if (!isset($rt) || !$rt || (is_array($rt) && !in_array(true, $rt))) {
            // check user password - local authentication
            $hashType = EvolutionCMS()->getManagerApi()->getHashType($this->user->password);

            if ($hashType == 'phpass') {
                $matchPassword = login($this->user->username, $this->userData['password'], $this->user->password);
            } elseif ($hashType == 'md5') {
                $matchPassword = loginMD5($this->user->getKey(), $this->userData['password'], $this->user->password, $this->user->username);
            } elseif ($hashType == 'v1') {
                $matchPassword = loginV1($this->user->getKey(), $this->userData['password'], $this->user->password, $this->user->username);
            } else {
                $matchPassword = false;
            }

        } else if ($rt === true || (is_array($rt) && in_array(true, $rt))) {
            $matchPassword = true;
        }

        if (!$matchPassword) {
            $this->incrementFailedLoginCount();
            throw new ServiceActionException(\Lang::get('global.login_processor_wrong_password'));
        }
    }

}
