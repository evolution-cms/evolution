<?php namespace FormLister;

use DocumentParser;

/**
 * Class MailChimp
 * @package FormLister
 */
class MailChimp extends Core
{
    /**
     * MailChimp constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(DocumentParser $modx, $cfg = [])
    {
        parent::__construct($modx, $cfg);
        $this->lexicon->fromFile('mailchimp');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
    }

    /**
     * @return bool
     */
    public function process()
    {
        $errorMessage = $this->translate('mc.subscription_failed');
        if (!$this->getCFGDef('apiKey')) {
            $this->addMessage($errorMessage);

            return false;
        }
        $MailChimp = new \DrewM\MailChimp\MailChimp($this->getCFGDef('apiKey'));
        $list_id = $this->getCFGDef('listId');
        if (!$list_id) {
            $this->addMessage($errorMessage);

            return false;
        }

        $MailChimp->post("lists/{$list_id}/members", [
            'email_address' => $this->getField('email'),
            'merge_fields'  => ['NAME' => $this->getField('name')],
            'status'        => 'pending',
        ]);
        if (!$MailChimp->getLastError()) {
            $this->addMessage($errorMessage);

            return false;
        } else {
            $this->setFormStatus(true);
            $this->runPrepare('prepareAfterProcess');
            $this->renderTpl = $this->getCFGDef('successTpl', $this->translate('mc.default_successTpl'));

            return true;
        }
    }
}
