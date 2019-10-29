<?php namespace Helpers\Lexicon;

use DocumentParser;
use Helpers\Lexicon;

/**
 * Class AbstractLexiconHandler
 * @package Helpers\Lexicon
 */
abstract class AbstractLexiconHandler
{
    protected $modx = null;
    protected $lexicon = null;

    /**
     * AbstractLexiconHandler constructor.
     * @param DocumentParser $modx
     * @param Lexicon $lexicon
     */
    public function __construct (DocumentParser $modx, Lexicon $lexicon)
    {
        $this->modx = $modx;
        $this->lexicon = $lexicon;
    }

    /**
     * @param $key
     * @param string $default
     */
    abstract public function get ($key, $default = '');
}
