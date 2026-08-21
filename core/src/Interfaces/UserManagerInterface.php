<?php namespace EvolutionCMS\Interfaces;

/**
 * The contract behind the `UserManager` container key and the `\UserManager` facade.
 *
 * Until this existed, the key was bound to a bare class with no contract, so a site
 * that swapped the binding — the documented way to replace a service, via
 * core/custom/config/app/providers/Evolution_UserManager.php — could drop a method and
 * only find out when a manager clicked the button that needed it. Implementing this
 * interface moves that failure to class load time.
 *
 * Signatures mirror evolutioncms-services/user-manager 1.x exactly, including the two
 * untyped `$userData` parameters and the absence of return types: declaring a return
 * type here would make every existing implementation invalid.
 *
 * `$userData` is the request payload for the operation, `$events` whether to fire the
 * `On*` plugin events, `$cache` whether to clear the site cache afterwards.
 */
interface UserManagerInterface
{
    /**
     * @param int|string $id
     * @return \EvolutionCMS\Models\User|null
     */
    public function get($id);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function edit(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function delete(array $userData, bool $events = true, bool $cache = true);

    /**
     * @param array $userData
     * @return string the one-time recovery token
     */
    public function repairPassword($userData, bool $events = true, bool $cache = true);

    /**
     * @param array $userData
     * @return mixed
     */
    public function changeManagerPassword($userData, bool $events = true, bool $cache = true);

    /**
     * @param array $userData
     * @return mixed
     */
    public function changePassword($userData, bool $events = true, bool $cache = true);

    /**
     * @param array $userData
     * @return mixed
     */
    public function hashChangePassword($userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function setRole(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function setGroups(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \EvolutionCMS\Exceptions\ServiceActionException when the login is refused
     */
    public function login(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function loginById(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function hashLogin(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function logout(array $userData = [], bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function saveSettings(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function saveValues(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function getValues(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function clearSettings(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function refreshToken(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return string the generated plaintext password, to be shown or mailed once
     */
    public function generateAndSavePassword(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getVerifiedKey(array $userData, bool $events = true, bool $cache = true);

    /**
     * @return mixed
     */
    public function verified(array $userData, bool $events = true, bool $cache = true);
}
