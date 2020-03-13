<?php namespace FormLister;

use APIhelpers;
use jsonHelper;
use modUsers;

/**
 * Контроллер для регистрации пользователя
 * Class Register
 * @package FormLister
 * @property modUsers $user
 */
class Register extends Form
{
    use DateConverter;
    public $user;

    /**
     * Register constructor.
     * @param \DocumentParser $modx
     * @param array $cfg
     */
    public function __construct (\DocumentParser $modx, $cfg = [])
    {
        parent::__construct($modx, $cfg);
        $this->user = $this->loadModel(
            $this->getCFGDef('model', '\modUsers'),
            $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
        );
        $this->lexicon->fromFile('register');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
        $this->dateFormat = $this->getCFGDef('dateFormat', '');
    }

    /**
     * @return string
     */
    public function render ()
    {
        if ($id = $this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->user->edit($id);
            $this->setFields($this->user->toArray());
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->translate('register.default_skipTpl'));
            $this->setValid(false);
        };

        return parent::render();
    }

    /**
     * Возвращает результат проверки формы
     * @return bool
     */
    public function validateForm ()
    {
        if (isset($this->rules['password']) && isset($this->rules['repeatPassword']) && !empty($this->getField('password')) && isset($this->rules['repeatPassword']['equals'])) {
            $this->rules['repeatPassword']['equals']['params'] = $this->getField('password');
        }

        return parent::validateForm();
    }

    /**
     * Custom validation rule
     * Проверяет уникальность email
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueEmail ($fl, $value)
    {
        $result = true;
        if (is_scalar($value) && !is_null($fl->user)) {
            $fl->user->set('email', $value);
            $result = $fl->user->checkUnique('web_user_attributes', 'email', 'internalKey');
        }

        return $result;
    }

    /**
     * Custom validation rule
     * Проверяет уникальность имени пользователя
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueUsername ($fl, $value)
    {
        $result = true;
        if (is_scalar($value) && !is_null($fl->user)) {
            $fl->user->set('username', $value);
            $result = $fl->user->checkUnique('web_users', 'username');
        }

        return $result;
    }

    /**
     *
     */
    public function process ()
    {
        if (!empty($this->allowedFields)) {
            $this->allowedFields[] = 'username';
            $this->allowedFields[] = 'password';
            $this->allowedFields[] = 'email';
        }
        if (!empty($this->forbiddenFields)) {
            $_forbidden = array_flip($this->forbiddenFields);
            unset($_forbidden['username'], $_forbidden['password'], $_forbidden['email']);
            $this->forbiddenFields = array_keys($_forbidden);
        }

        //регистрация без логина, по емейлу
        if ($this->getField('username') == '') {
            $this->setField('username', $this->getField('email'));
        }
        //регистрация со случайным паролем
        if ($this->getField('password') == '' && !isset($this->rules['password'])) {
            $this->setField('password', APIhelpers::genPass($this->getCFGDef('passwordLength', 6)));
        }
        $password = $this->getField('password');
        $fields = $this->filterFields($this->getFormData('fields'), $this->allowedFields, $this->forbiddenFields);
        if (isset($fields['dob']) && ($dob = $this->toTimestamp($fields['dob']))) {
            $fields['dob'] = $dob;
        }
        $checkActivation = $this->getCFGDef('checkActivation', 0);
        $fields['verified'] = (int)!$checkActivation;
        $fields['username'] = is_scalar($fields['username']) ? $fields['username'] : '';
        $fields['email'] = is_scalar($fields['email']) ? $fields['email'] : '';
        $this->user->create($fields);
        $this->addWebUserToGroups(0, $this->config->loadArray($this->getCFGDef('userGroups')));
        $result = $this->user->save(true);
        $this->log('Register user', [
            'data'   => $fields,
            'result' => $result,
            'log'    => $this->user->getLog()
        ]);
        if (!$result) {
            $this->addMessage($this->translate('register.registration_failed'));
        } else {
            $this->user->close();
            $userdata = $this->user->edit($result)->toArray();
            $this->setFields($userdata);
            if ($dob = $this->fromTimestamp($this->getField('dob'))) {
                $this->setField('dob', $dob);
            }
            $this->setField('user.password', $password);
            $this->runPrepare('preparePostProcess');
            if ($checkActivation) {
                $hash = md5(jsonHelper::toJSON($userdata));
                $uidName = $this->getCFGDef('uidName', $this->user->fieldPKName());
                $query = http_build_query([
                    $uidName => $result,
                    'hash'   => $hash
                ]);
                $url = $this->getCFGDef('activateTo', $this->modx->getConfig('site_start'));
                $this->setField(
                    'activate.url',
                    $this->modx->makeUrl($url, '', $query, 'full')
                );
            }
            parent::process();
        }
    }

    /**
     *
     */
    public function postProcess ()
    {
        parent::postProcess();
        $tpl = $this->getCFGDef('successTpl', $this->translate('register.default_successTpl'));
        if (!empty($tpl)) {
            $this->renderTpl = $tpl;
        }
    }

    /**
     * Добавляет пользователя в группы
     * @param int $uid
     * @param array $groups
     * @return $this
     */
    public function addWebUserToGroups ($uid = 0, $groups = [])
    {
        if (!$groups) {
            return $this;
        }
        foreach ($groups as &$group) {
            $group = $this->modx->db->escape(trim($group));
        }
        $groups = "'" . implode("','", $groups) . "'";
        $groupNames = $this->modx->db->query("SELECT `id` FROM " . $this->modx->getFullTableName('webgroup_names') . " WHERE `name` IN (" . $groups . ")");
        $webGroups = $this->modx->db->getColumn('id', $groupNames);
        if ($webGroups) {
            $this->user->setUserGroups($uid, $webGroups);
        }

        return $this;
    }
}
