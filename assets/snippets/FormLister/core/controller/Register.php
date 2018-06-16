<?php namespace FormLister;

/**
 * Контроллер для регистрации пользователя
 * Class Register
 * @package FormLister
 */
class Register extends Form
{
    /**
     * @var \modUsers
     */
    public $user = null;

    /**
     * Register constructor.
     * @param \DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(\DocumentParser $modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $this->user = $this->loadModel(
            $this->getCFGDef('model', '\modUsers'),
            $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
        );
        $lang = $this->lexicon->loadLang('register');
        if ($lang) {
            $this->log('Lexicon loaded', array('lexicon' => $lang));
        }
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($id = $this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->user->edit($id);
            $this->setFields($this->user->toArray());
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->lexicon->getMsg('register.default_skipTpl'));
            $this->setValid(false);
        };

        return parent::render();
    }

    /**
     * @param string $param
     * @return array|mixed|\xNop
     */
    public function getValidationRules($param = 'rules')
    {
        $rules = parent::getValidationRules($param);
        if (isset($rules['password']) && isset($rules['repeatPassword']) && !empty($this->getField('password'))) {
            if (isset($rules['repeatPassword']['equals'])) {
                $rules['repeatPassword']['equals']['params'] = $this->getField('password');
            }
        }

        return $rules;
    }

    /**
     * Custom validation rule
     * Проверяет уникальность email
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueEmail($fl, $value)
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
    public static function uniqueUsername($fl, $value)
    {
        $result = true;
        if (is_scalar($value) && !is_null($fl->user)) {
            $fl->user->set('username', $value);
            $result = $fl->user->checkUnique('web_users', 'username');
        }

        return $result ;
    }

    /**
     *
     */
    public function process()
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
        if ($this->checkSubmitProtection()) {
            return;
        }
        //регистрация со случайным паролем
        if ($this->getField('password') == '' && !isset($this->rules['password'])) {
            $this->setField('password', \APIhelpers::genPass($this->getCFGDef('passwordLength', 6)));
        }
        $password = $this->getField('password');
        $fields = $this->filterFields($this->getFormData('fields'), $this->allowedFields, $this->forbiddenFields);
        $checkActivation = $this->getCFGDef('checkActivation', 0);
        if ($checkActivation) {
            $fields['logincount'] = -1;
        }
        $fields['username'] = is_scalar($fields['username']) ? $fields['username'] : '';
        $fields['email'] = is_scalar($fields['email']) ? $fields['email'] : '';
        $this->user->create($fields);
        $this->addWebUserToGroups(0, $this->config->loadArray($this->getCFGDef('userGroups')));
        $result = $this->user->save(true);
        $this->log('Register user', array('data' => $fields, 'result' => $result, 'log' => $this->user->getLog()));
        if (!$result) {
            $this->addMessage($this->lexicon->getMsg('register.registration_failed'));
        } else {
            $this->user->close();
            $userdata = $this->user->edit($result)->toArray();
            $this->setFields($userdata);
            $this->setField('user.password', $password);
            $this->runPrepare('preparePostProcess');
            if ($checkActivation) {
                $hash = md5(json_encode($userdata));
                $uidName = $this->getCFGDef('uidName', $this->user->fieldPKName());
                $query = http_build_query(array(
                    $uidName => $result,
                    'hash'   => $hash
                ));
                $url = $this->getCFGDef('activateTo', $this->modx->config['site_start']);
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
    public function postProcess()
    {
        $this->redirect();
        $this->setFormStatus(true); //результат отправки писем значения не имеет
        $this->renderTpl = $this->getCFGDef('successTpl', $this->lexicon->getMsg('register.default_successTpl'));
    }

    /**
     * Добавляет пользователя в группы
     * @param int $uid
     * @param array $groups
     * @return $this
     */
    public function addWebUserToGroups($uid = 0, $groups = array())
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
