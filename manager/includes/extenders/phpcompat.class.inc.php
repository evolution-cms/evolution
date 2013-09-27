<?php
// todo file_put_contents(), strftime(), mb_*()
class PHPCOMPAT
{
	function PHPCOMPAT()
	{
	}
	
	function htmlspecialchars($str, $flags = ENT_COMPAT)
	{
		global $modx;
		
		$ent_str = htmlspecialchars($str, $flags, $modx->config['modx_charset']);
		if(!empty($str) && empty($ent_str))
		{
			$detect_order = join(',', mb_detect_order());
			$ent_str = mb_convert_encoding($str,$modx->config['modx_charset'],$detect_order); 
		}
		return $ent_str;
	}
}
