<?php namespace FormLister;

use DocumentParser;
use modUsers;

/**
 * Контроллер для редактирования профиля
 * Class Profile
 * @package FormLister
 * @property modUsers $user
 */
class Profile extends Core
{
    use DateConverter;

    public $user;

    /**
     * Profile constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct (DocumentParser $modx, $cfg = [])
    {
        parent::__construct($modx, $cfg);
        $this->lexicon->fromFile('profile');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
        $uid = $modx->getLoginUserId('web');
        if ($uid) {
            /* @var $user \modUsers */
            $user = $this->loadModel(
                $this->getCFGDef('model', '\modUsers'),
                $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
            );
            $this->user = $user->edit($uid);
            $this->config->setConfig([
                'userdata' => $this->user->toArray()
            ]);
        }
        $this->dateFormat = $this->getCFGDef('dateFormat', '');
    }

    /**
     * Загружает в formData данные не из формы
     * @param string $sources список источников
     * @param string $arrayParam название параметра с данными
     * @return $this
     */
    public function setExternalFields ($sources = 'array', $arrayParam = 'defaults')
    {
        parent::setExternalFields($sources, $arrayParam);
        parent::setExternalFields('array', 'userdata');

        return $this;
    }


    /**
     * @return string
     */
    public function render ()
    {
        if (is_null($this->user) || !$this->user->getID()) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->translate('profile.default_skipTpl'));
            $this->setValid(false);
        }
        if (!$this->isSubmitted() && ($dob = $this->getField('dob'))) {
            $this->setField('dob', $this->fromTimestamp($dob));
        }

        return parent::render();
    }


    /**
     * Возвращает результат проверки формы
     * @return bool
     */
    public function validateForm ()
    {
        $password = $this->getField('password');
        if (empty($password) || !is_scalar($password)) {
            $this->forbiddenFields[] = 'password';
            if (isset($this->rules['password'])) {
                unset($this->rules['password']);
            }
            if (isset($this->rules['repeatPassword'])) {
                unset($this->rules['repeatPassword']);
            }
        } else {
            if (isset($this->rules['repeatPassword']['equals'])) {
                $this->rules['repeatPassword']['equals']['params'] = $this->getField('password');
            }
        }

        return parent::validateForm();
    }

    /**
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueEmail ($fl, $value)
    {
        $result = true;
        if (is_scalar($value) && !is_null($fl->user) && ($fl->user->get("email") !== $value)) {
            /* @var $user \modUsers */
            $user = clone($fl->user);
            $user->set('email', $value);
            $result = $user->checkUnique('web_user_attributes', 'email', 'internalKey');
        }

        return $result;
    }

    /**
     * @param $fl
     * @param $value
     * @return bool
     */
    public static function uniqueUsername ($fl, $value)
    {
        $result = true;
        if (is_scalar($value) && !is_null($fl->user) && ($fl->user->get("email") !== $value)) {
            /* @var $user \modUsers */
            $user = clone($fl->user);
            $user->set('username', $value);
            $result = $user->checkUnique('web_users', 'username');
        }

        return $result;
    }

    /**
     *
     */
    public function process ()
    {
        if ($this->user->get('username') == $this->user->get('email') && !empty($this->getField('email')) && empty($this->getField('username'))) {
            $this->setField('username', $this->getField('email'));
            if (!empty($this->allowedFields)) {
                $this->allowedFields[] = 'username';
            }
            if (!empty($this->forbiddenFields)) {
                $_forbidden = array_flip($this->forbiddenFields);
                unset($_forbidden['username']);
                $this->forbiddenFields = array_keys($_forbidden);
            }
        }

        $newpassword = $this->getField('password');
        $password = $this->user->get('password');
        if (!empty($newpassword) && ($password !== $this->user->getPassword($newpassword))) {
            if (!empty($this->allowedFields)) {
                $this->allowedFields[] = 'password';
            }
            if (!empty($this->forbiddenFields)) {
                $_forbidden = array_flip($this->forbiddenFields);
                unset($_forbidden['password']);
                $this->forbiddenFields = array_keys($_forbidden);
            }
        }
        $fields = $this->filterFields($this->getFormData('fields'), $this->allowedFields, $this->forbiddenFields);
        if (isset($fields['username'])) {
            $fields['username'] = is_scalar($fields['username']) ? $fields['username'] : '';
        }
        if (isset($fields['email'])) {
            $fields['email'] = is_scalar($fields['email']) ? $fields['email'] : '';
        }
        if (isset($fields['dob']) && ($dob = $this->toTimestamp($fields['dob']))) {
            $fields['dob'] = $dob;
        }
        $verificationField = $this->getCFGDef('verificationField', 'email');
        if (isset($fields[$verificationField]) && $this->user->get($verificationField) != $fields[$verificationField]) {
            $fields['verified'] = 0;
        }
        $result = $this->user->fromArray($fields)->save(true);
        $this->log('Update profile', [
            'data'   => $fields,
            'result' => $result,
            'log'    => $this->user->getLog()
        ]);
        if ($result) {
            $this->setFormStatus(true);
            $this->user->close();
            $this->setFields($this->user->edit($result)->toArray());
            if ($dob = $this->fromTimestamp($this->getField('dob'))) {
                $this->setField('dob', $dob);
            }
            $this->setField('user.password', $newpassword);
            $this->runPrepare('preparePostProcess');
            $this->runPrepare('prepareAfterProcess');
            $checkActivation = $this->getCFGDef('checkActivation', 0);
            if ($checkActivation && !$this->getField('verified')) {
                $this->user->logOut('WebLoginPE', true);
                $this->redirect('exitTo');
            }
            $this->redirect();
            if ($successTpl = $this->getCFGDef('successTpl')) {
                $this->renderTpl = $successTpl;
            } else {
                $this->addMessage($this->translate('profile.update_success'));
            }
        } else {
            $this->addMessage($this->translate('profile.update_failed'));
        }
    }
}
