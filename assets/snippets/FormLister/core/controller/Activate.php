<?php namespace FormLister;
use APIhelpers;
use DocumentParser;
use jsonHelper;
use modUsers;

/**
 * Контроллер для восстановления паролей
 * Class Reminder
 * @package FormLister
 * @property modUsers $user
 * @property string $mode;
 * @property string $userField
 */
class Activate extends Form
{
    protected $user;

    protected $mode = 'hash';
    protected $userField = '';

    /**
     * Reminder constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(DocumentParser $modx, $cfg = [])
    {
        parent::__construct($modx, $cfg);
        $this->user = $this->loadModel(
            $this->getCFGDef('model', '\modUsers'),
            $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
        );
        $this->lexicon->fromFile('activate');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
        $userField = $this->getCFGDef('userField', 'email');
        $this->userField = $userField;
        $uidName = $this->getCFGDef('uidName', $this->user->fieldPKName());
        if (!isset($_REQUEST['formid']) && !isset($_REQUEST[$userField]) && (isset($_REQUEST['hash']) && !empty($_REQUEST['hash']) && isset($_REQUEST[$uidName]) && !empty($_REQUEST[$uidName]))) {
            $this->setField('hash', $_REQUEST['hash']);
            $this->setField('id', (int)$_REQUEST[$uidName]);
            $this->mode = 'activate';
        }
        $this->log('Activate mode is ' . $this->mode);
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
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->translate('activate.default_skipTpl'));
            $this->setValid(false);
        }

        if ($this->mode == 'activate') {
            $this->renderActivate();
        }

        return parent::render();
    }

    /**
     *
     */
    public function renderActivate()
    {
        $hash = $this->getField('hash');
        $uid = $this->getField('id');
        if (is_scalar($hash) && $hash && $hash == $this->getUserHash($uid)) {
            $this->process();
        } else {
            $this->addMessage($this->translate('activate.update_failed'));
            $this->setValid(false);
        }

        return;
    }

    /**
     * @param $uid
     * @return bool|string
     */
    public function getUserHash($uid)
    {
        if (is_null($this->user)) {
            $hash = false;
        } else {
            $userdata = $this->user->edit($uid)->toArray();
            $hash = $this->user->getID() && !$userdata['verified'] ? md5(jsonHelper::toJson($userdata)) : false;
        }

        return $hash;
    }

    /**
     *
     */
    public function process()
    {
        switch ($this->mode) {
            /**
             * Задаем хэш, отправляем пользователю ссылку для активации
             */
            case "hash":
                $uid = $this->getField($this->userField);
                $password = $this->getField('password');
                if (
                    ($hash = $this->getUserHash($uid))
                    && (
                        empty($password)
                        || ($this->user->get('password') == $this->user->getPassword($password))
                    )
                ) {
                    $this->setFields($this->user->toArray());
                    if (empty($password)) {
                        $password = APIhelpers::genPass($this->getCFGDef('passwordLength', 6));
                        $this->user->set('password', $password)->save(true);
                        $this->setField('user.password', $password);
                        $hash = $this->getUserHash($uid);
                    }
                    $url = $this->getCFGDef('activateTo', isset($this->modx->documentIdentifier) && $this->modx->documentIdentifier > 0 ? $this->modx->documentIdentifier : $this->config['site_start']);
                    $uidName = $this->getCFGDef('uidName', $this->user->fieldPKName());
                    $this->setField('activate.url', $this->modx->makeUrl($url, "",
                        http_build_query([$uidName => $this->getField('id'), 'hash' => $hash]),
                        'full'));
                    $this->mailConfig['to'] = $this->user->get('email');
                    parent::process();
                } else {
                    $this->addMessage($this->translate('activate.no_activation'));
                }
                break;
            /**
             * Отправляем пользователю письмо для активации, если указан шаблон такого письма
             */
            case "activate":
                $uid = $this->getField('id');
                $hash = $this->getField('hash');
                if ($hash && $hash == $this->getUserHash($uid)) {
                    $result = $this->user->edit($uid)->set('verified', 1)->save(true);
                    $this->log('Activate user', ['user' => $uid, 'result' => $result]);
                    if (!$result) {
                        $this->addMessage($this->translate('activate.update_failed'));
                    } else {
                        $this->setFields($this->user->toArray());
                        $this->postProcess();
                    }
                } else {
                    $this->addMessage($this->translate('activate.update_failed'));
                }
                break;
        }
    }

    /**
     * @return string
     */
    public function getMode() {
        return $this->mode;
    }

    /**
     *
     */
    public function postProcess()
    {
        $this->setFormStatus(true);
        $this->runPrepare('prepareAfterProcess');
        switch ($this->mode) {
            case 'hash':
                $tpl = $this->getCFGDef('successTpl',
                    $this->translate('activate.default_successTpl'));
                break;
            case 'activate':
                $this->redirect();
                $tpl = $this->getCFGDef('activateSuccessTpl',
                    $this->translate('activate.default_activateSuccessTpl'));
        }
        if (!empty($tpl)) {
            $this->renderTpl = $tpl;
        }
    }
}
