<?php
/*******************************************************
 *
 * MODxMailer Class extends PHPMailer
 * Created by ZeRo (http://www.petit-power.com/)
 * updated by yama (http://kyms.jp/)
 *
 *******************************************************
 */

include_once MODX_MANAGER_PATH . 'includes/controls/phpmailer/class.phpmailer.php';

/* ------------------------------------------------------------------
 *
 * MODxMailer - Extended PHPMailer
 *
 * -----------------------------------------------------------------
 */

class MODxMailer extends PHPMailer
{
	var $mb_language          = null;
	var $encode_header_method = null;
	
	function MODxMailer()
	{
		global $modx;
		
		$this->mb_language = 'UNI';
		$this->encode_header_method = '';

		$this->PluginDir = MODX_MANAGER_PATH . 'includes/controls/phpmailer/';

		switch($modx->config['email_method'])
		{
		    case 'smtp':
                $this->IsSMTP();
                $this->Host      = $modx->config['smtp_host'] . ':' . $modx->config['smtp_port'];
                $this->SMTPAuth  = $modx->config['smtp_auth']==='1' ? true : false;
                $this->Username  = $modx->config['smtp_username'];
                $this->Password  = $modx->config['smtppw'];
                if(10<strlen($this->Password))
                {
                	$this->Password = substr($this->Password,0,-7);
                	$this->Password = str_replace('%','=',$this->Password);
                	$this->Password = base64_decode($this->Password);
                }
                break;
		    case 'mail':
		    default:
		    	$this->IsMail();
		}
		
		$this->From     = $modx->config['emailsender'];
		$this->Sender   = $modx->config['emailsender']; 
		$this->FromName = $modx->config['site_name'];
		$this->IsHTML(true);
		
		if(isset($modx->config['mail_charset']) && !empty($modx->config['mail_charset'])) {
			$mail_charset = $modx->config['mail_charset'];
		}
		else {
			$mail_charset = strtolower($modx->config['manager_language']);
    		if(substr($mail_charset,0,8)==='japanese') $mail_charset = 'jis';
    		else                                       $mail_charset = 'utf8';
		}
		
		switch($mail_charset)
		{
			case 'iso-8859-1':
				$this->CharSet     = 'iso-8859-1';
				$this->Encoding    = 'quoted-printable';
				$this->mb_language = 'English';
				break;
			case 'jis':
				$this->CharSet     = 'ISO-2022-JP';
				$this->Encoding    = '7bit';
				$this->mb_language = 'Japanese';
				$this->encode_header_method = 'mb_encode_mimeheader';
				$this->IsHTML(false);
				break;
			case 'utf8':
			case 'utf-8':
			default:
				$this->CharSet     = 'UTF-8';
				$this->Encoding    = 'base64';
				$this->mb_language = 'UNI';
		}
	    if(extension_loaded('mbstring'))
		{
			mb_language($this->mb_language);
			mb_internal_encoding($modx->config['modx_charset']);
		}
		$exconf = $modx->config['base_path'] . 'manager/includes/controls/phpmailer/config.inc.php';
		if(is_file($exconf)) include_once($exconf);
	}
	
	function EncodeHeader($str, $position = 'text')
	{
		global $modx, $sanitize_seed;
		if(strpos($str, $sanitize_seed)!==false) $str = str_replace($sanitize_seed, '', $str);
		if($this->encode_header_method=='mb_encode_mimeheader')
			return mb_encode_mimeheader($str, $this->CharSet, 'B', "\n");
		else return parent::EncodeHeader($str, $position);
	}
	
    public function Send() {
		global $sanitize_seed;
		
		if(strpos($this->Body, $sanitize_seed)!==false)    $this->Body = str_replace($sanitize_seed, '', $this->Body);
		if(strpos($this->Subject, $sanitize_seed)!==false) $this->Subject = str_replace($sanitize_seed, '', $this->Subject);
		
        try {
            if(!$this->PreSend()) return false;
            return $this->PostSend();
        } catch (phpmailerException $e) {
            $this->mailHeader = '';
            $this->SetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

	function MailSend($header, $body)
	{
		global $modx;
		
		$org_body = $body;
		
		switch($this->CharSet)
		{
			case 'ISO-2022-JP':
				$body = mb_convert_encoding($body, 'JIS', $modx->config['modx_charset']);
				if(ini_get('safe_mode')) $mode = 'normal';
				else {
					                     $this->Subject = $this->EncodeHeader($this->Subject);
					                     $mode = 'mb';
				}
				break;
			default:
				                         $mode = 'normal';
		}
		
		if($modx->debug)
		{
			$debug_info  = 'CharSet = ' . $this->CharSet . "\n";
			$debug_info .= 'Encoding = ' . $this->Encoding . "\n";
			$debug_info .= 'mb_language = ' . $this->mb_language . "\n";
			$debug_info .= 'encode_header_method = ' . $this->encode_header_method . "\n";
			$debug_info .= "send_mode = {$mode}\n";
			$debug_info .= 'Subject = ' . $this->Subject . "\n";
			$log = "<pre>{$debug_info}\n{$header}\n{$org_body}</pre>";
			$modx->logEvent(1, 1, $log, 'MODxMailer debug information');
			return true;
		}
		switch($mode)
		{
			case 'normal':
				return parent::MailSend($header, $body);
				break;
			case 'mb':
				return $this->mbMailSend($header, $body);
				break;
		}
	}
		
	function mbMailSend($header, $body)
	{
		global $modx;
		
		$to = '';
		for($i = 0; $i < count($this->to); $i++)
		{
			if($i != 0) { $to .= ', '; }
			$to .= $this->AddrFormat($this->to[$i]);
		}
		
		$toArr = explode(',', $to);
		
		$params = sprintf("-oi -f %s", $this->Sender);
		if ($this->Sender != '' && strlen(ini_get('safe_mode')) < 1)
		{
			$old_from = ini_get('sendmail_from');
			ini_set('sendmail_from', $this->Sender);
			if ($this->SingleTo === true && count($toArr) > 1)
			{
				foreach ($toArr as $key => $val)
				{
					$rt = @mail($val, $this->Subject, $body, $header, $params); 
				}
			}
			else
			{
				$rt = @mail($to, $this->Subject, $body, $header, $params);
			}
		}
		else
		{
			if ($this->SingleTo === true && count($toArr) > 1)
			{
				foreach ($toArr as $key => $val)
				{
					$rt = @mail($val, $this->Subject, $body, $header, $params);
				}
			}
			else
			{
				$rt = @mail($to, $this->Subject, $body, $header);
			}
		}
		
		if (isset($old_from))
		{
			ini_set('sendmail_from', $old_from);
		}
		if(!$rt)
		{
			$msg  = $this->Lang('instantiate') . "<br />\n";
			$msg .= "{$this->Subject}<br />\n";
			$msg .= "{$this->FromName}&lt;{$this->From}&gt;<br />\n";
			$msg .= mb_convert_encoding($body,$modx->config['modx_charset'],$this->CharSet);
			$this->SetError($msg);
			return false;
		}
		
		return true;
	}
	
	function SetError($msg)
	{
		global $modx;
		$modx->config['send_errormail'] = '0';
		$modx->logEvent(0, 3, $msg,'phpmailer');
		return parent::SetError($msg);
	}
	
	function address_split($address)
	{
		$address = trim($address);
		if(strpos($address,'<')!==false && substr($address,-1)==='>')
		{
			$address = rtrim($address,'>');
			list($name,$address) = explode('<',$address);
		}
		else $name = '';
		$result = array($name,$address);
		return $result;
	}
}
