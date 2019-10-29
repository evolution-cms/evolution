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
    use SubmitProtection;
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
        $this->setFiles($this->filesToArray($_FILES));
        $this->mailConfig = array(
            'isHtml'   => $this->getCFGDef('isHtml', 1),
            'to'       => $this->getCFGDef('to'),
            'from'     => $this->getCFGDef('from', $this->modx->getConfig('emailsender')),
            'fromName' => $this->getCFGDef('fromName', $this->modx->getConfig('site_name')),
            'subject'  => $this->getCFGDef('subject'),
            'replyTo'  => $this->getCFGDef('replyTo'),
            'cc'       => $this->getCFGDef('cc'),
            'bcc'      => $this->getCFGDef('bcc'),
            'noemail'  => $this->getCFGDef('noemail', false)
        );
        $this->lexicon->fromFile('form');
        $this->log('Lexicon loaded', array('lexicon' => $this->lexicon->getLexicon()));
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
        $tpl = $this->getCFGDef($tplParam, 'reportTpl');
        if (empty($tpl) && $tplParam == 'reportTpl') {
            $tpl = '@CODE:';
            foreach ($this->getFormData('fields') as $key => $value) {
                $tpl .= \APIhelpers::e($key) . ": [+{$key}.value+]" . PHP_EOL;
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
        $out = $mailer->send($report) || $this->getCFGDef('ignoreMailerResult', 0);
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

        $config = $this->getMailSendConfig($to, 'autosenderFromName', 'autoSubject');
        $asConfig = $this->config->loadArray($this->getCFGDef('autoMailConfig'));
        if (!empty($asConfig) && is_array($asConfig)) {
            $asConfig = $this->parseMailerParams($asConfig);
            $config = array_merge($config, $asConfig);
        }
        $mailer = new Mailer($this->modx, $config);
        $report = $this->renderReport('automessageTpl');
        $out = empty($to) ? true : $mailer->send($report);
        $this->log(
            'Mail autosender report',
            array(
                'report'        => $report,
                'mailer_config' => $mailer->config,
                'result'        => $out
            )
        );

        return $out;
    }

    /**
     * Отправляет копию письма на адрес из поля email
     * @return mixed
     */
    public function sendCCSender()
    {
        $to = $this->getField($this->getCFGDef('ccSenderField', 'email'));

        if ($this->getCFGDef('ccSender', 0)) {
            $config = $this->getMailSendConfig($to, 'ccSenderFromName', 'ccSubject');
            $ccConfig = $this->config->loadArray($this->getCFGDef('ccMailConfig'));
            if (!empty($ccConfig) && is_array($ccConfig)) {
                $ccConfig = $this->parseMailerParams($ccConfig);
                $config = array_merge($config, $ccConfig);
            }
            $mailer = new Mailer($this->modx, $config);
            $report = $this->renderReport('ccSenderTpl');
            $out = empty($to) ? true : $mailer->send($report);
            $this->log(
                'Mail CC report',
                array(
                    'report' => $report,
                    'mailer_config' => $mailer->config,
                    'result' => $out
                )
            );
        } else {
            $out = true;
        }

        return $out;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->isSubmitted() && ($this->checkSubmitLimit() || $this->checkSubmitProtection())) {
            return $this->renderForm();
        }

        return parent::render();
    }

    /**
     *
     */
    public function process()
    {
        $now = time() + $this->modx->getConfig('server_offset_time');
        $this->setField('form.date', date($this->getCFGDef('dateFormat', $this->translate('form.dateFormat')), $now));
        $this->setFileFields();
        $this->mailConfig = $this->parseMailerParams($this->mailConfig);
        if ($this->sendReport()) {
            $this->sendCCSender();
            $this->sendAutosender();
            $this->setSubmitProtection()->postProcess();
        } else {
            $this->addMessage($this->translate('form.form_failed'));
        }
    }

    /**
     * @param array $cfg
     * @return array
     */
    public function parseMailerParams($cfg = array())
    {
        if ($this->getCFGDef('parseMailerParams', 0) && !empty($cfg)) {
            $plh = \APIhelpers::renameKeyArr($this->prerenderForm(true), '[', ']', '+');
            $search = array_keys($plh);
            $replace = array_values($plh);
            foreach ($cfg as $key => &$value) {
                $value = str_replace($search, $replace, $value);
            }
        }

        return $cfg;
    }

    /**
     *
     */
    public function postProcess()
    {
        $this->setFormStatus(true);
        if ($this->getCFGDef('deleteAttachments', 0)) {
            $this->deleteAttachments();
        }
        $this->runPrepare('prepareAfterProcess');
        $this->redirect();
        $this->renderTpl = $this->getCFGDef('successTpl', $this->translate('form.default_successTpl'));
    }

    /**
     * @param string $to
     * @param string $fromParam
     * @param string $subjectParam
     * @return array
     */
    public function getMailSendConfig($to, $fromParam, $subjectParam = 'subject')
    {
        $subject = empty($this->getCFGDef($subjectParam . 'Tpl'))
            ? $this->renderSubject()
            : $this->renderSubject($subjectParam);
        $out = array_merge(
            $this->mailConfig,
            array(
                'subject'  => $subject,
                'to'       => $to,
                'fromName' => $this->getCFGDef($fromParam, $this->modx->getConfig('site_name'))
            )
        );
        $out = $this->parseMailerParams($out);

        return $out;
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
