<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Decorator\Parser;

use League\CLImate\Decorator\Tags;
abstract class Parser
{
    /**
     * An array of the currently applied codes
     *
     * @var array $current;
     */
    protected $current = [];
    /**
     * An array of the tags that should be searched for
     * and their corresponding replacements
     *
     * @var \League\CLImate\Decorator\Tags $tags
     */
    public $tags;
    public function __construct(array $current, Tags $tags)
    {
        $this->current = $current;
        $this->tags = $tags;
    }
    /**
     * Wrap the string in the current style
     *
     * @param  string $str
     *
     * @return string
     */
    public abstract function apply($str);
}

?>