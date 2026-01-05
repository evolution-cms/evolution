<?php namespace EvolutionCMS\Support\Formatter;

/**
 * Class HtmlFormatter
 * @package Formatter
 */
class HtmlFormatter
{

    /**
     * @param string $string
     * @param bool $decode
     * @return string
     */
    public static function format($string, $decode = true)
    {
        $tag = '#0000ff';
        $att = '#ff0000';
        $val = '#8000ff';
        $com = '#34803a';
        $find = [
            '~(\s[a-z].*?=)~',                    // Highlight the attributes
            '~(&lt;\!--.*?--&gt;)~s',            // Hightlight comments
            '~(&quot;[a-zA-Z0-9\/].*?&quot;)~',    // Highlight the values
            '~(&lt;[a-z].*?&gt;)~',                // Highlight the beginning of the opening tag
            '~(&lt;/[a-z].*?&gt;)~',            // Highlight the closing tag
            '~(&amp;.*?;)~',                    // Stylize HTML entities
        ];
        $replace = [
            '<span style="color:' . $att . ';">$1</span>',
            '<span style="color:' . $com . ';">$1</span>',
            '<span style="color:' . $val . ';">$1</span>',
            '<span style="color:' . $tag . ';">$1</span>',
            '<span style="color:' . $tag . ';">$1</span>',
            '<span style="font-style:italic;">$1</span>',
        ];
        if ($decode) {
            $string = htmlentities($string);
        }

        return '<pre>' . preg_replace($find, $replace, $string) . '</pre>';
    }
}
