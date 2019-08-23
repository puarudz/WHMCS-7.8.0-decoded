<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Decorator\Parser;

class NonAnsi extends Parser
{
    /**
     * Strip the string of any tags
     *
     * @param  string $str
     *
     * @return string
     */
    public function apply($str)
    {
        return preg_replace($this->tags->regex(), '', $str);
    }
}

?>