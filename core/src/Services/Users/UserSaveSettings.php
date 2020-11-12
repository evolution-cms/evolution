<?php namespace EvolutionCMS\Services\Users;

use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Interfaces\ServiceInterface;
use \EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;

class UserSaveSettings implements ServiceInterface
{
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
     * UserRegistration constructor.
     * @param array $userData
     * @param bool $events
     * @param bool $cache
     */
    public function __construct(array $userData, bool $events = true, bool $cache = true)
    {
        $this->userData = $userData;
        $this->events = $events;
        $this->cache = $cache;
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws ServiceActionException
     * @throws ServiceValidationException
     */
    public function process(): bool
    {
        if (!$this->checkRules()) {
            throw new ServiceActionException(\Lang::get('global.error_no_privileges'));
        }


        if (!$this->validate()) {
            $exception = new ServiceValidationException();
            $exception->setValidationErrors($this->validateErrors);
            throw $exception;
        }

        $ignore = array(
            'a',
            'id',
            'oldusername',
            'oldemail',
            'newusername',
            'fullname',
            'first_name',
            'middle_name',
            'last_name',
            'verified',
            'newpassword',
            'newpasswordcheck',
            'passwordgenmethod',
            'passwordnotifymethod',
            'specifiedpassword',
            'confirmpassword',
            'email',
            'phone',
            'mobilephone',
            'fax',
            'dob',
            'country',
            'street',
            'city',
            'state',
            'zip',
            'gender',
            'photo',
            'comment',
            'role',
            'failedlogincount',
            'blocked',
            'blockeduntil',
            'blockedafter',
            'user_groups',
            'mode',
            'blockedmode',
            'stay',
            'save',
            'theme_refresher'
        );

        // determine which settings can be saved blank (based on 'default_{settingname}' POST checkbox values)
        $defaults = array(
            'upload_images',
            'upload_media',
            'upload_flash',
            'upload_files'
        );

        // get user setting field names
        $settings = array();
        foreach ($this->userData as $n => $v) {
            if (in_array($n, $ignore) || (!in_array($n, $defaults) && is_scalar($v) && trim($v) == '') || (!in_array($n,
                        $defaults) && is_array($v) && empty($v))) {
                continue;
            } // ignore blacklist and empties
            $settings[$n] = $v; // this value should be saved
        }

        foreach ($defaults as $k) {
            if (isset($settings['default_' . $k]) && $settings['default_' . $k] == '1') {
                unset($settings[$k]);
            }
            unset($settings['default_' . $k]);
        }

        \EvolutionCMS\Models\UserSetting::where('user', $this->userData['id'])->delete();

        foreach ($settings as $n => $vl) {
            if (is_array($vl)) {
                $vl = implode(',', $vl);
            }
            if ((string)$vl != '') {
                $f = array();
                $f['user'] = $this->userData['id'];
                $f['setting_name'] = $n;
                $f['setting_value'] = $vl;
                \EvolutionCMS\Models\UserSetting::create($f);
            }
        }

        if ($this->cache) {
            EvolutionCMS()->clearCache('full');
        }

        return true;
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
        return true;
    }

}
