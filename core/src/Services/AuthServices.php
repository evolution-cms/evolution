<?php namespace EvolutionCMS\Services;

use EvolutionCMS\Exceptions\ServiceActionException;
use \EvolutionCMS\Models\User;


class AuthServices
{
    /** @var User */
    public $user;

    public function __construct()
    {
        $this->user = User::find(EvolutionCMS()->getLoginUserID());
        if (!is_null($this->user)) {
            $this->user->email = $this->user->attributes->email;
            $this->user->phone = $this->user->attributes->phone;
            $this->user->name = $this->user->attributes->first_name;
            $this->user->full_name = $this->user->attributes->full_name;
        }
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user);
    }

    /**
     * Determine if the guard has a user instance.
     *
     * @return bool
     */
    public function hasUser()
    {
        return !is_null($this->user);
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Determine if the current user is a guest.
     */
    public function id()
    {
        if ($this->user) {
            return $this->user->getKey();
        }
    }

    /**
     * Get User
     *
     * @return User
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Logout User
     *
     * @return void
     */
    public function logout()
    {
        \UserManager::logout();
    }

    /**
     * If you are "remembering" users
     *
     * @return bool
     */
    public function viaRemember()
    {
        return isset($_COOKIE['evo_remember_manager']);
    }

    public function attempt($checked = [])
    {
        foreach ($checked as $key => $value) {
            if (isset($this->user->{$key})) {
                if ($this->user->{$key} == $value) {
                    unset($checked[$key]);
                }
            }
            if (isset($this->user->attributes->{$key})) {
                if ($this->user->attributes->{$key} == $value) {
                    unset($checked[$key]);
                }
            }

            if ($key == 'password') {
                $matchPassword = false;

                // check user password - local authentication
                $hashType = evo()->getManagerApi()->getHashType($this->user->password);

                if ($hashType == 'phpass') {
                    $matchPassword = login($this->user->username, $value, $this->user->password);
                } elseif ($hashType == 'md5') {
                    $matchPassword = loginMD5($this->user->getKey(), $value, $this->user->password, $this->user->username);
                } elseif ($hashType == 'v1') {
                    $matchPassword = loginV1($this->user->getKey(), $value, $this->user->password, $this->user->username);
                } else {
                    $matchPassword = false;
                }


                if ($matchPassword) {
                    unset($checked[$key]);
                }
            }
        }
        if (count($checked) > 0) return false;
        else return true;
    }

    public function login($user, $remember = false)
    {
        return $this->loginUsingId($user->getKey(), $remember);
    }

    public function loginUsingId($userId, $remember = false)
    {
        return \UserManager::loginById(['id' => $userId, 'rememberme' => $remember]);
    }

}
