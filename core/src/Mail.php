<?php namespace EvolutionCMS;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mail extends PHPMailer
{
    /**
     * @var string
     */
    protected $mb_language = 'UNI';

    /**
     * @var string
     */
    protected $encode_header_method = '';

    /**
     * @var
     */
    public $PluginDir;

    /**
     * @var Core $modx
     */
    protected $modx;

    public function init($modx = null)
    {
        if ($modx === null) {
            $modx = evolutionCMS();
        }
        $this->modx = $modx;
        $this->PluginDir = MODX_MANAGER_PATH . 'includes/controls/phpmailer/';

        switch ($modx->getConfig('email_method')) {
            case 'smtp':
                $this->isSMTP();
                $this->SMTPSecure = $modx->getConfig('smtp_secure') === 'none' ? '' : $modx->getConfig('smtp_secure');
                $this->Port = $modx->getConfig('smtp_port');
                $this->Host = $modx->getConfig('smtp_host');
                $this->SMTPAuth = $modx->getConfig('smtp_auth') === '1' ? true : false;
                $this->SMTPAutoTLS = $modx->getConfig('smtp_autotls') === '0' ? false : true;
                $this->Username = $modx->getConfig('smtp_username');
                $this->Password = $modx->getConfig('smtppw');
                if (10 < strlen($this->Password)) {
                    $this->Password = substr($this->Password, 0, -7);
                    $this->Password = str_replace('%', '=', $this->Password);
                    $this->Password = base64_decode($this->Password);
                }
                break;
            case 'mail':
            default:
                $this->isMail();
        }

        $this->From = $modx->getConfig('emailsender');
        if (isset($modx->config['email_sender_method']) && !$modx->config['email_sender_method']) {
            $this->Sender = $modx->getConfig('emailsender');
        }
        $this->FromName = $modx->getPhpCompat()->entities($modx->getConfig('site_name'));
        $this->isHTML(true);

        if (isset($modx->config['mail_charset']) && !empty($modx->config['mail_charset'])) {
            $mail_charset = $modx->getConfig('mail_charset');
        } else {
            if (substr($modx->getConfig('manager_language'), 0, 8) === 'japanese') {
                $mail_charset = 'jis';
            } else {
                $mail_charset = $modx->getConfig('modx_charset');
            }
        }

        switch ($mail_charset) {
            case 'iso-8859-1':
                $this->CharSet = 'iso-8859-1';
                $this->Encoding = 'quoted-printable';
                $this->mb_language = 'English';
                break;
            case 'jis':
                $this->CharSet = 'ISO-2022-JP';
                $this->Encoding = '7bit';
                $this->mb_language = 'Japanese';
                $this->encode_header_method = 'mb_encode_mimeheader';
                $this->isHTML(false);
                break;
            case 'windows-1251':
                $this->CharSet = 'cp1251';
                break;
            case 'utf8':
            case 'utf-8':
            default:
                $this->CharSet = 'UTF-8';
                $this->Encoding = 'base64';
                $this->mb_language = 'UNI';
        }
        if (extension_loaded('mbstring')) {
            mb_language($this->mb_language);
            mb_internal_encoding($modx->getConfig('modx_charset'));
        }
        $exconf = MODX_MANAGER_PATH . 'includes/controls/phpmailer/config.inc.php';
        if (is_file($exconf)) {
            include($exconf);
        }

        return $this;
    }

    /**
     * Encode a header value (not including its label) optimally.
     * Picks shortest of Q, B, or none. Result includes folding if needed.
     * See RFC822 definitions for phrase, comment and text positions.
     *
     * @param string $str The header value to encode
     * @param string $position What context the string will be used in
     *
     * @return string
     */
    public function EncodeHeader($str, $position = 'text')
    {
        $str = removeSanitizeSeed($str);

        if ($this->encode_header_method === 'mb_encode_mimeheader') {
            return mb_encode_mimeheader($str, $this->CharSet, 'B', "\n");
        }

        return parent::EncodeHeader($str, $position);
    }

    /**
     * Create a message and send it.
     * Uses the sending method specified by $Mailer.
     *
     * @throws PHPMailerException
     *
     * @return bool false on error - See the ErrorInfo property for details of the error
     */
    public function Send()
    {
        $this->Body = removeSanitizeSeed($this->Body);
        $this->Subject = removeSanitizeSeed($this->Subject);

        return parent::send();
    }

    /**
     * @param string $header The message headers
     * @param string $body The message body
     *
     * @return bool
     */
    public function MailSend($header, $body)
    {
        $org_body = $body;

        switch ($this->CharSet) {
            case 'ISO-2022-JP':
                $body = mb_convert_encoding($body, 'JIS', $this->modx->getConfig('modx_charset'));
                if (ini_get('safe_mode')) {
                    $mode = 'normal';
                } else {
                    $this->Subject = $this->EncodeHeader($this->Subject);
                    $mode = 'mb';
                }
                break;
            default:
                $mode = 'normal';
        }

        if ($this->modx->debug) {
            $debug_info = 'CharSet = ' . $this->CharSet . "\n";
            $debug_info .= 'Encoding = ' . $this->Encoding . "\n";
            $debug_info .= 'mb_language = ' . $this->mb_language . "\n";
            $debug_info .= 'encode_header_method = ' . $this->encode_header_method . "\n";
            $debug_info .= "send_mode = {$mode}\n";
            $debug_info .= 'Subject = ' . $this->Subject . "\n";
            $log = "<pre>{$debug_info}\n{$header}\n{$org_body}</pre>";
            $this->modx->logEvent(1, 1, $log, 'MODxMailer debug information');

            return true;
        }

        switch ($mode) {
            case 'normal':
                $out = parent::mailSend($header, $body);
                break;
            case 'mb':
                $out = $this->mbMailSend($header, $body);
                break;
            default:
                $out = false;
        }

        return $out;
    }

    /**
     * @param string $header The message headers
     * @param string $body The message body
     *
     * @return bool
     */
    public function mbMailSend($header, $body)
    {
        $rt = false;
        $to = '';
        foreach ($this->to as $i => $iValue) {
            if ($i != 0) {
                $to .= ', ';
            }
            $to .= $this->AddrFormat($iValue);
        }

        $toArr = array_filter(array_map('trim', explode(',', $to)));

        $params = sprintf("-oi -f %s", $this->Sender);
        if ($this->Sender != '' && strlen(ini_get('safe_mode')) < 1) {
            $old_from = ini_get('sendmail_from');
            ini_set('sendmail_from', $this->Sender);
            if ($this->SingleTo === true && count($toArr) > 1) {
                foreach ($toArr as $key => $val) {
                    $rt = @mail($val, $this->Subject, $body, $header, $params);
                }
            } else {
                $rt = @mail($to, $this->Subject, $body, $header, $params);
            }
        } else {
            if ($this->SingleTo === true && count($toArr) > 1) {
                foreach ($toArr as $key => $val) {
                    $rt = @mail($val, $this->Subject, $body, $header, $params);
                }
            } else {
                $rt = @mail($to, $this->Subject, $body, $header);
            }
        }

        if (isset($old_from)) {
            ini_set('sendmail_from', $old_from);
        }
        if (!$rt) {
            $msg = $this->Lang('instantiate') . "<br />\n";
            $msg .= "{$this->Subject}<br />\n";
            $msg .= "{$this->FromName}&lt;{$this->From}&gt;<br />\n";
            $msg .= mb_convert_encoding($body, $this->modx->getConfig('modx_charset'), $this->CharSet);
            $this->SetError($msg);

            return false;
        }

        return true;
    }

    /**
     * Add an error message to the error container.
     *
     * @param string $msg
     */
    public function SetError($msg)
    {
        $classDump = call_user_func('get_object_vars', $this);
        unset($classDump['modx']);
        $this->modx->setConfig('send_errormail', '0');
        $this->modx->logEvent(0, 3, $msg . '<pre>' . print_r($classDump, true) . '</pre>', 'phpmailer');

        return parent::SetError($msg);
    }

    /**
     * @param $address
     *
     * @return array
     */
    public function address_split($address)
    {
        $address = trim($address);
        if (strpos($address, '<') !== false && substr($address, -1) === '>') {
            $address = rtrim($address, '>');
            list($name, $address) = explode('<', $address);
        } else {
            $name = '';
        }
        return array($name, $address);
    }

    /**
     * @return string
     */
    public function getMIMEHeader()
    {
        return $this->MIMEHeader;
    }

    /**
     * @return string
     */
    public function getMIMEBody()
    {
        return $this->MIMEBody;
    }

    /**
     * @param string $header
     *
     * @return $this
     */
    public function setMIMEHeader($header = '')
    {
        $this->MIMEHeader = $header;

        return $this;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setMIMEBody($body = '')
    {
        $this->MIMEBody = $body;

        return $this;
    }

    /**
     * @param string $header
     *
     * @return $this
     */
    public function setMailHeader($header = '')
    {
        $this->mailHeader = $header;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageID()
    {
        return trim($this->lastMessageID, '<>');
    }
}
