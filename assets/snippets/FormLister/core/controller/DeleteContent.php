<?php namespace FormLister;

use DocumentParser;

/**
 * Class DeleteUser
 * @package FormLister
 */
class DeleteContent extends Form
{
    public $content = null;
    public $user = null;
    protected $id = 0;

    /**
     * Form constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(DocumentParser $modx, array $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $this->content = $this->loadModel(
            $this->getCFGDef('model', '\modResource'),
            $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modResource.php')
        );
        $this->user = $this->loadModel(
            $this->getCFGDef('model', '\modUsers'),
            $this->getCFGDef('modelPath', 'assets/lib/MODxAPI/modUsers.php')
        );
        $this->lexicon->fromFile('deleteContent');
        $this->log('Lexicon loaded', array('lexicon' => $this->lexicon->getLexicon()));
        $idField = $this->getCFGDef('idField','id');
        if (isset($_REQUEST[$idField]) && is_scalar($_REQUEST[$idField])) {
            $this->id = (int)$_REQUEST[$idField];
        }
        $this->config->setConfig(array(
            'ignoreMailerResult' => 1,
            'protectSubmit'      => 0,
            'submitLimit'        => 0
        ));
    }

    /**
     * @return string
     */
    public function render()
    {
        $flag = false;
        $uid = $this->modx->getLoginUserID('web');
        if (!$uid) {
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->translate('deleteContent.default_skipTpl'));
        } elseif (!$this->id || !$this->content->edit($this->id)->getID() || $this->content->get('deleted')) {
            $this->redirect('badRecordTo');
            $this->renderTpl = $this->getCFGDef('badRecordTpl',
                $this->translate('deleteContent.default_badRecordTpl'));
        }  elseif ($uid != $this->content->edit($this->id)->get($this->getCFGDef('ownerField','aid'))) {
            $this->renderTpl = $this->getCFGDef('badOwnerTpl',
                $this->translate('deleteContent.default_badOwnerTpl'));
        } else {
            $flag = true;
            $this->setFields($this->content->edit($this->id)->toArray());
            $this->setFields($this->user->edit($uid)->toArray(),'user');
        };
        $this->setValid($flag);

        return parent::render();
    }


    /**
     *
     */
    public function process()
    {
        $result = $this->content->delete($this->id, true);
        if ($result) {
            return parent::process();
        } else {
            return $this->addMessage($this->translate('deleteContent.delete_failed'));
        }
    }

    public function postProcess()
    {
        parent::postProcess();
        $this->renderTpl = $this->getCFGDef('successTpl', $this->translate('deleteContent.default_successTpl'));
    }
}
