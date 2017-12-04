<?php namespace FormLister;

use Helpers\Mailer;

/**
 * Контроллер для обычных форм с отправкой, типа обратной связи
 */

/**
 * Class Form
 * @package FormLister
 */
class Form extends Core
{
    /**
     * Настройки для отправки почты
     * @var array
     */
    public $mailConfig = array();

    /**
     * Правила валидации файлов
     * @var array
     */
    protected $fileRules = array();

    /**
     * Form constructor.
     * @param \DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(\DocumentParser $modx, array $cfg = array())
    {
        parent::__construct($modx, $cfg);
        if ($files = $this->getCFGDef('attachments')) {
            $this->setFiles($this->filesToArray($_FILES, $this->config->loadArray($files)));
        }
        $this->mailConfig = array(
            'isHtml'   => $this->getCFGDef('isHtml', 1),
            'to'       => $this->getCFGDef('to'),
            'from'     => $this->getCFGDef('from', $this->modx->config['emailsender']),
            'fromName' => $this->getCFGDef('fromName', $this->modx->config['site_name']),
            'subject'  => $this->getCFGDef('subject'),
            'replyTo'  => $this->getCFGDef('replyTo'),
            'cc'       => $this->getCFGDef('cc'),
            'bcc'      => $this->getCFGDef('bcc'),
            'noemail'  => $this->getCFGDef('noemail', false)
        );
        $lang = $this->lexicon->loadLang('form');
        if ($lang) {
            $this->log('Lexicon loaded', array('lexicon' => $lang));
        }
    }

    /**
     * Проверка повторной отправки формы
     * @return bool
     */
    public function checkSubmitProtection()
    {
        $result = false;
        if ($this->isSubmitted() && $this->getCFGDef('protectSubmit', 1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid . '_hash']) && $_SESSION[$this->formid . '_hash'] == $hash && $hash != '') {
                $result = true;
                $this->addMessage($this->lexicon->getMsg('form.protectSubmit'));
                $this->log('Submit protection enabled');
            }
        }

        return $result;
    }

    /**
     * Проверка повторной отправки в течение определенного времени, в секундах
     * @return bool
     */
    public function checkSubmitLimit()
    {
        $submitLimit = $this->getCFGDef('submitLimit', 60);
        $result = false;
        if (isset($_SESSION[$this->formid . '_limit']) && $this->isSubmitted() && $submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$this->formid . '_limit']) {
                $result = true;
                $this->addMessage('[%form.submitLimit%] ' .
                    ($submitLimit >= 60
                        ? round($submitLimit / 60, 0) . ' [%form.minutes%].'
                        : $submitLimit . ' [%form.seconds%].'
                    ));
                $this->log('Submit limit enabled');
            } else {
                unset($_SESSION[$this->formid . '_limit']);
            } //time expired
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function setSubmitProtection()
    {
        if ($this->getCFGDef('protectSubmit', 1)) {
            $_SESSION[$this->formid . '_hash'] = $this->getFormHash();
        } //hash is set earlier
        if ($this->getCFGDef('submitLimit', 60) > 0) {
            $_SESSION[$this->formid . '_limit'] = time();
        }

        return $this;
    }

    /**
     * @return array|string
     */
    public function getFormHash()
    {
        $hash = array();
        $protectSubmit = $this->getCFGDef('protectSubmit', 1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = $this->config->loadArray($protectSubmit);
            foreach ($protectSubmit as $field) {
                $hash[] = $this->getField(trim($field));
            }
        } else //all required fields
        {
            foreach ($this->rules as $field => $rules) {
                foreach ($rules as $rule => $description) {
                    if ($rule == 'required') {
                        $hash[] = $this->getField($field);
                    }
                }
            }
        }
        if ($hash) {
            $hash = md5(json_encode($hash));
        }

        return $hash;
    }

    /**
     * @return bool
     */
    public function validateForm()
    {
        parent::validateForm();
        if (!$this->getCFGDef('attachments')) {
            return $this->isValid();
        }
        $validator = $this->getCFGDef('fileValidator', '\FormLister\FileValidator');
        $validator = $this->loadModel($validator, '', array());
        $fields = $this->getFormData('files');
        $rules = $this->getValidationRules('fileRules');
        $this->fileRules = array_merge($this->fileRules, $rules);
        $this->log('Prepare to validate files', array('fields' => $fields, 'rules' => $this->fileRules));
        $result = $this->validate($validator, $this->fileRules, $fields);
        if ($result !== true) {
            foreach ($result as $item) {
                $this->addError($item[0], $item[1], $item[2]);
            }
            $this->log('File validation errors', $this->getFormData('errors'));
        }

        return $this->isValid();
    }

    /**
     * Формирует текст письма для отправки
     * Если основной шаблон письма не задан, то формирует список полей формы
     * @param string $tplParam имя параметра с шаблоном письма
     * @return null|string
     */
    public function renderReport($tplParam = 'reportTpl')
    {
        $tpl = $this->getCFGDef($tplParam);
        if (empty($tpl) && $tplParam == 'reportTpl') {
            $tpl = '@CODE:';
            foreach ($this->getFormData('fields') as $key => $value) {
                $tpl .=  \APIhelpers::e($key) . ": [+{$key}.value+]" . PHP_EOL;
            }
        }
        $out = $this->parseChunk($tpl, $this->prerenderForm(true));

        return $out;
    }

    /**
     * Получает тему письма из шаблона или строки
     * @param string $param
     * @return mixed|null|string
     */
    public function renderSubject($param = 'subject')
    {
        $subject = $this->getCFGDef($param . 'Tpl');
        if (!empty($subject)) {
            $subject = $this->parseChunk($subject, $this->prerenderForm(true));
        } else {
            $subject = $this->getCFGDef($param);
        }

        return $subject;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        $attachments = array();
        foreach ($this->getFormData('files') as $files) {
            if (!isset($files[0])) {
                $files = array($files);
            }
            foreach ($files as $file) {
                if ($file['error'] === 0) {
                    $attachments[] = array('filepath' => $file['tmp_name'], 'filename' => $file['name']);
                }
            }
        }

        $userfiles = $this->config->loadArray($this->getCFGDef('attachFiles'));
        foreach ($userfiles as $field => $files) {
            if (!isset($files[0])) {
                $files = array($files);
            }
            foreach ($files as $file) {
                if (isset($file['filepath']) && isset($file['filename'])) {
                    $attachments[] = array(
                        'filepath' => MODX_BASE_PATH . $file['filepath'],
                        'filename' => $file['filename']
                    );
                }
            }
        }

        return $attachments;
    }

    /**
     * @return $this
     */
    public function setFileFields()
    {
        $fields = array();
        foreach ($this->getFormData('files') as $field => $files) {
            if (!isset($files[0])) {
                $files = array($files);
            }
            foreach ($files as $file) {
                if ($file['error'] === 0) {
                    $fields[$field][] = $file['name'];
                }
            }
        }

        $userfiles = $this->config->loadArray($this->getCFGDef('attachFiles'));
        foreach ($userfiles as $field => $files) {
            if (!isset($files[0])) {
                $files = array($files);
            }
            foreach ($files as $file) {
                if (isset($file['filename']) && isset($file['filepath'])) {
                    $fields[$field][] = $file['filename'];
                }
            }
        }

        if (!empty($fields)) {
            $this->setFields($fields);
        }

        return $this;
    }

    /**
     * Оправляет письмо
     * @return mixed
     */
    public function sendReport()
    {
        $mailer = new Mailer($this->modx, array_merge(
            $this->mailConfig,
            array('subject' => $this->renderSubject())
        ));
        $attachments = $this->getAttachments();
        if ($attachments) {
            $mailer->attachFiles($attachments);
            $this->log('Attachments', $attachments);
            $field = array();
            foreach ($attachments as $file) {
                $field[] = $file['filename'];
            }
            $this->setField('attachments', $field);
        }
        $report = $this->renderReport();
        $out = $mailer->send($report) || $this->getCFGDef('ignoreMailerResult',0);
        $this->log('Mail report', array('report' => $report, 'mailer_config' => $mailer->config, 'result' => $out));

        return $out;
    }

    /**
     * Оправляет копию письма на указанный адрес
     * @return mixed
     */
    public function sendAutosender()
    {
        $to = $this->getCFGDef('autosender');
        if (empty($to)) {
            $out = true;
        } else {
            $mailer = new Mailer($this->modx, $this->getMailSendConfig($to, 'autosenderFromName', 'autoSubject'));
            $report = $this->renderReport('automessageTpl');
            $out = $mailer->send($report);
            $this->log('Mail autosender report', array(
                    'report'        => $report,
                    'mailer_config' => $mailer->config,
                    'result'        => $out
                )
            );
        }

        return $out;
    }

    /**
     * Отправляет копию письма на адрес из поля email
     * @return mixed
     */
    public function sendCCSender()
    {
        $to = $this->getField($this->getCFGDef('ccSenderField', 'email'));
        if (empty($to)) {
            $out = true;
        } else {
            if ($this->getCFGDef('ccSender', 0)) {
                $mailer = new Mailer($this->modx, $this->getMailSendConfig($to, 'ccSenderFromName', 'ccSubject'));
                $report = $this->renderReport('ccSenderTpl');
                $out = $mailer->send($report);
                $this->log('Mail CC report',
                    array('report' => $report, 'mailer_config' => $mailer->config, 'result' => $out));
            } else {
                $out = true;
            }
        }

        return $out;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->isSubmitted() && $this->checkSubmitLimit()) {
            return $this->renderForm();
        }

        return parent::render();
    }

    /**
     *
     */
    public function process()
    {
        $this->setField('form.date', date($this->getCFGDef('dateFormat', $this->lexicon->getMsg('form.dateFormat'))));
        $this->setFileFields();
        //если защита сработала, то ничего не отправляем
        if ($this->checkSubmitProtection()) {
            return;
        }
        if ($this->sendReport()) {
            $this->sendCCSender();
            $this->sendAutosender();
            $this->setSubmitProtection()->postProcess();
        } else {
            $this->addMessage($this->lexicon->getMsg('form.form_failed'));
        }
    }

    /**
     *
     */
    public function postProcess()
    {
        $this->setFormStatus(true);
        if ($this->getCFGDef('deleteAttachments',0)) {
            $this->deleteAttachments();
        }
        $this->runPrepare('prepareAfterProcess');
        $this->redirect();
        $this->renderTpl = $this->getCFGDef('successTpl', $this->lexicon->getMsg('form.default_successTpl'));
    }

    /**
     * @param string $to
     * @param string $fromParam
     * @param string $subjectParam
     * @return array
     */
    public function getMailSendConfig($to, $fromParam, $subjectParam = 'subject')
    {
        $subject = empty($this->getCFGDef($subjectParam)) ? $this->renderSubject() : $this->renderSubject($subjectParam);

        return array_merge(
            $this->mailConfig,
            array(
                'subject'  => $subject,
                'to'       => $to,
                'fromName' => $this->getCFGDef($fromParam, $this->modx->config['site_name'])
            )
        );
    }

    /**
     * @return $this
     */
    public function deleteAttachments()
    {
        $files = $this->getAttachments();
        foreach ($files as $file) {
            $this->fs->delete($file['filepath']);
        }

        return $this;
    }
}
