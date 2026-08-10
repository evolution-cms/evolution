<?php namespace EvolutionCMS\Controllers\Users;

use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;

class EditOrNewUser extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.users.message_after_save';

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return $this->managerTheme->getCore()->hasPermission('save_user');
    }

    public function process(): bool
    {
        $userData = $_POST;
        $id = false;
        if (isset($userData['id'])) {
            $id = $userData['id'];
        }
        if ($userData['newpassword'] == 1) {
            if ($userData['passwordgenmethod'] == 'g') {
                $userData['password_confirmation'] = $userData['password'] = generate_password(8);

            } else {
                $userData['password'] = $userData['specifiedpassword'];
                $userData['password_confirmation'] = $userData['confirmpassword'];
            }
        }
        $userData['username'] = $userData['newusername'];
        if (isset($userData['blockeduntil']) && !is_numeric($userData['blockeduntil'])) {
            $userData['blockeduntil'] = strtotime($userData['blockeduntil']);
        }
        if (isset($userData['blockedafter']) && !is_numeric($userData['blockedafter'])) {
            $userData['blockedafter'] = strtotime($userData['blockedafter']);
        }
        try {
            if ($userData['mode'] == 87) {
                $user = \UserManager::create($userData);
            } else {
                $user = \UserManager::edit($userData);
                if (isset($userData['password'])) {
                    $userData['clearPassword'] = $userData['password'];
                    $user->password = EvolutionCMS()->getPasswordHash()->HashPassword($userData['password']);
                    $user->cachepwd = '';
                    $user->save();
                }
            }
        } catch (\EvolutionCMS\Exceptions\ServiceValidationException $exception) {
            foreach ($exception->getValidationErrors() as $errors) {
                foreach ($errors as $error) {
                    webAlertAndQuit($error, $userData['mode'], $id);
                    exit();
                }
            }
            exit();
        }

        $userData['id'] = $user->getKey();

        $tvs = \EvolutionCMS\Models\SiteTmplvar::query()->distinct()
            ->select('site_tmplvars.*', 'user_values.value')
            ->join('user_role_vars', 'user_role_vars.tmplvarid', '=', 'site_tmplvars.id')
            ->leftJoin('user_values', function($query) use ($user) {

                $query->on('user_values.userid', '=', \DB::raw($user->id));
                $query->on('user_values.tmplvarid', '=', 'site_tmplvars.id');
            })
            ->where('user_role_vars.roleid', $userData['role'])
            ->get();

        $values = [];

        foreach ($tvs->toArray() as $row) {
            $value = '';

            if (isset($userData['tv' . $row['id']])) {
                $value = $userData["tv" . $row['id']];

                switch ($row['type']) {

                    case 'url': {
                        if ($userData["tv" . $row['id'] . '_prefix'] != '--') {
                            $value = str_replace([
                                "feed://",
                                "ftp://",
                                "http://",
                                "https://",
                                "mailto:"
                            ], "", $value);
                            $value = $userData["tv" . $row['id'] . '_prefix'] . $value;
                        }
                        break;
                    }

                    default:
                    {

                        if (is_array($value)) {
                            // handles checkboxes & multiple selects elements
                            $feature_insert = [];
                            foreach ($value as $featureValue => $feature_item) {
                                $feature_insert[] = $feature_item;
                            }
                            $value = implode("||", $feature_insert);
                        }

                        break;
                    }
                }
            }

            $values[$row['name']] = $value;
        }

        $userData = array_filter($userData, function ($key) {

            return !preg_match('/^tv\d/', $key);
        }, ARRAY_FILTER_USE_KEY);

        // Save User Values
        $values['id'] = $user->getKey();
        \UserManager::saveValues($values);

        // Save User Settings
        \UserManager::clearSettings($userData);
        \UserManager::saveSettings($userData);

        if (isset($userData['role'])
            && $userData['role'] != $user->attributes->role
            && EvolutionCMS()->hasPermission('save_role')) {
            \UserManager::setRole(['id' => $user->getKey(), 'role' => $userData['role']]);
        }

        if (isset($userData['user_groups']) && is_array($userData['user_groups'])) {
            \UserManager::setGroups(['id' => $user->getKey(), 'groups' => $userData['user_groups']]);
        } else {
            \UserManager::setGroups(['id' => $user->getKey(), 'groups' => []]);
        }

        if ($userData['stay'] != '') {
            $a = ($userData['stay'] == '2') ? "88&id={$user->getKey()}" : "87";
            $this->parameters['url'] = "index.php?a={$a}&r=2&stay=" . $userData['stay'];
        } else {
            $this->parameters['url'] = "index.php?a=88&id={$user->getKey()}";
        }
        $this->parameters['cancel_url'] = "index.php?a=99";
        if ($userData['passwordnotifymethod'] == 'e') {
            $websignupemail_message = EvolutionCMS()->getConfig('websignupemail_message');
            $site_url = EvolutionCMS()->getConfig('site_url');
            sendMailMessageForUser($user->attributes->email, $user->username, $userData['password'], $user->attributes->fullname, $websignupemail_message, $site_url);

        }
        if ($userData['passwordnotifymethod'] == 's' && $userData['newpassword'] == 1) {
            $this->parameters['username'] = $user->username;
            $this->parameters['password'] = $userData['password'];
            // The lexicon entry carries its own <b>/<br> markup, so the message is assembled here
            // with escaped replacements and handed to the view as Htmlable. That keeps the markup
            // working while a username such as `<img src=x onerror=...>` stays inert, and it keeps
            // the exact generated password readable instead of re-escaping it in the template.
            $this->parameters['passwordMessage'] = new \Illuminate\Support\HtmlString(
                \Lang::get('global.password_msg', [
                    'username' => e($user->username),
                    'password' => e($userData['password']),
                ])
            );
            return true;
        }
        header("Location: " . $this->parameters['url']);
        exit();
    }
}
