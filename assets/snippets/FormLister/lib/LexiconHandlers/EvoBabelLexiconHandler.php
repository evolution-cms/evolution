<?php namespace Helpers\Lexicon;

/**
 * Class EvoBabelLexiconHandler
 */
class EvoBabelLexiconHandler extends AbstractLexiconHandler
{
    /**
     * @param $key
     * @param string $default
     * @return string
     */
    public function get ($key, $default = '')
    {
        $out = $this->modx->runSnippet('lang', array('a' => $key));
        if (empty($out)) {
            $out = $default;
        }

        return $out;
    }
}
