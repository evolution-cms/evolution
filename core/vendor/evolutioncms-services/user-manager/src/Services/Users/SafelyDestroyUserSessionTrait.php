<?php

namespace EvolutionCMS\UserManager\Services\Users;

/** Removes authentication state for one context without destroying unrelated session data. */
trait SafelyDestroyUserSessionTrait
{
    private $userSessionFields = [
        'Shortname',
        'Fullname',
        'Email',
        'Validated',
        'InternalKey',
        'Failedlogins',
        'Lastlogin',
        'Logincount',
        'Role',
        'Permissions',
        'Docgroups',
        'Token',
    ];

    /**
     * Clears this context's identity and, for manager logout, its CSRF credential.
     * Web-context logout must not invalidate a still-authenticated manager session.
     *
     * @return void
     */
    protected function safelyDestroyUserSession()
    {
        if (defined('NO_SESSION')) {
            return;
        }

        foreach ($this->userSessionFields as $field) {
            unset($_SESSION[$this->context . $field]);
        }
        if ($this->context === 'mgr') {
            unset($_SESSION['_token']);
        }
    }
}
