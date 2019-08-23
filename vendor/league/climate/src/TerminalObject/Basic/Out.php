<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

class Out extends BasicTerminalObject
{
    /**
     * The content to output
     *
     * @var string $content
     */
    protected $content;
    public function __construct($content)
    {
        $this->content = $content;
    }
    /**
     * Return the content to output
     *
     * @return string
     */
    public function result()
    {
        return $this->content;
    }
}

?>