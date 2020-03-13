<?php namespace FormLister;

use DocumentParser;
use modUsers;

/**
 * Контроллер для авторизации пользователя
 * Class Login
 * @package FormLister
 * @property modUsers $user
 * @property string $requestUri
 * @property string $context
 */
class Login extends Core
{
    use DateConverter;
    public $user;
    protected $requestUri = '';
    protected $context = '';

    /**
     * Login constructor.
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
        $requestUri = $_SERVER['REQUEST_URI'];
        if (0 === strpos($requestUri, MODX_BASE_URL)) {
            $requestUri = substr($requestUri, strlen(MODX_BASE_URL));
        } 
        $this->requestUri = $this->modx->getConfig('site_url') . $requestUri;
        $this->context = $this->getCFGDef('context', 'web');
        $this->lexicon->fromFile('login');
        $this->log('Lexicon loaded', ['lexicon' => $this->lexicon->getLexicon()]);
        $this->dateFormat = $this->getCFGDef('dateFormat', '');
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($id = $this->modx->getLoginUserID($this->context)) {
            $this->redirect();
            $this->user->edit($id);
            $this->setFields($this->user->toArray());
            $this->renderTpl = $this->getCFGDef('skipTpl', $this->lexicon->getMsg('login.default_skipTpl'));
            $this->setValid(false);
        };

        return parent::render();
    }


    /**
     *
     */
    public function process()
    {
        if (is_null($this->user)) {
            $this->addMessage($this->lexicon->getMsg('login.user_failed'));

            return;
        }
        $login = $this->getField($this->getCFGDef('loginField', 'username'));
        if (!is_scalar($login)) {
            $login = '';
        }
        $password = $this->getField($this->getCFGDef('passwordField', 'password'));
        $remember = $this->getField($this->getCFGDef('rememberField', 'rememberme'));
        if ($this->user->checkBlock($login)) {
            $this->addMessage($this->lexicon->getMsg('login.user_blocked'));

            return;
        }
        $this->user->edit($login);

        if ($this->getCFGDef('checkActivation', 0) && !$this->user->get('verified')) {
            $this->addMessage($this->lexicon->getMsg('login.user_notactivated'));

            return;
        }

        $auth = $this->user->testAuth($login, $password, false, true);
        if (!$auth) {
            $this->addMessage($this->lexicon->getMsg('login.user_failed'));

            return;
        }
        if ($remember) {
            $remember = $this->getCFGDef('cookieLifetime', 60 * 60 * 24 * 365 * 5);
        }
        $loginCookie = $this->getCFGDef('cookieName', 'WebLoginPE');
        $this->user->authUser($login, $remember, $loginCookie, true);
        $this->setFormStatus(true);
        $this->runPrepare('prepareAfterProcess');
        if (isset($this->modx->documentIdentifier) && $this->modx->documentIdentifier == $this->modx->getConfig('unauthorized_page')) {
            $uaPage = $this->modx->makeUrl($this->modx->getConfig('unauthorized_page'), "", "", "full");
            $requested = explode('?', $this->requestUri);
            if (array_shift($requested) != $uaPage) {
                $this->setField('redirectTo', $this->requestUri);
                $this->sendRedirect($this->requestUri);
            } else {
                $this->redirect();
            }
        } else {
            $this->redirect();
        }
        $this->setFields($this->user->toArray());
        if ($dob = $this->fromTimestamp($this->getField('dob'))) {
            $this->setField('dob', $dob);
        }
        $this->renderTpl = $this->getCFGDef('successTpl');
    }
}
