<?php
$text = $modx->getModifiers()->parseDocumentSource($value);
$text = str_replace(array("\r\n","\r"),"\n",$text);

$blockElms  = 'br,table,tbody,tr,td,th,thead,tfoot,caption,colgroup,div';
$blockElms .= ',dl,dd,dt,ul,ol,li,pre,select,option,form,map,area,blockquote';
$blockElms .= ',address,math,style,input,p,h1,h2,h3,h4,h5,h6,hr,object,param,embed';
$blockElms .= ',noframes,noscript,section,article,aside,hgroup,footer,address,code';
$blockElms = explode(',', $blockElms);
$lines = explode("\n",$text);
$c = count($lines);
foreach($lines as $i=>$line)
{
    $line = rtrim($line);
    if($i===$c-1) break;
    foreach($blockElms as $block)
    {
        if(preg_match("@</?{$block}" . '[^>]*>$@',$line))
            continue 2;
    }
    $lines[$i] = "{$line}<br />";
}
return implode("\n", $lines);
