<?php namespace FormLister;

use DocumentParser;
use modUsers;

/**
 * Class DeleteUser
 * @package FormLister
 * @property modUsers $user
 */
class DeleteUser extends Form
{
    public $user;

    /**
     * Form constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(DocumentParser $modx, array $cfg = [])
    {
        parent::__construct($modx, $cfg);
        $this->lexicon->fromFile('deleteUser');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
        $uid = $modx->getLoginUserId('web');
        $userdata = [];
        if ($uid) {
            $this->user = $this->loadModel(
                $this->getCFGDef('model', '\modUsers'),
                $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
            );
            if (!is_null($this->user)) {
                $userdata = $this->user->edit($uid)->toArray();
                unset($userdata['password']);
            }
            $this->config->setConfig([
                'ignoreMailerResult' => 1,
                'keepDefaults'       => 1,
                'protectSubmit'      => 0,
                'submitLimit'        => 0,
                'userdata'           => $userdata
            ]);
        }
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
    public function render()
    {
        if (!$this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->translate('deleteUser.default_skipTpl'));
            $this->setValid(false);
        };

        return parent::render();
    }


    /**
     *
     */
    public function process()
    {
        $uid = $this->modx->getLoginUserID('web');
        if (!is_null($this->user)) {
            $password = $this->getField('password');
            if ($this->user->testAuth($uid, $password, true)) {
                $result = $this->user->delete($uid, true);
                if ($result) {
                    $this->user->logout();
                    parent::process();
                } else {
                    return $this->addMessage($this->translate('deleteUser.delete_failed'));
                }
            }
        }

        return $this->addMessage($this->translate('deleteUser.delete_failed'));
    }

    /**
     *
     */
    public function postProcess()
    {
        parent::postProcess();
        $this->renderTpl = $this->getCFGDef('successTpl', $this->translate('deleteUser.default_successTpl'));
    }
}
