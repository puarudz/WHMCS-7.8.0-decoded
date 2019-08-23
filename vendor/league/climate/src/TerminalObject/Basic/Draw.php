<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Basic;

use League\CLImate\TerminalObject\Helper\Art;
class Draw extends BasicTerminalObject
{
    use Art;
    public function __construct($art)
    {
        // Add the default art directory
        $this->addDir(__DIR__ . '/../../ASCII');
        $this->art = $art;
    }
    /**
     * Return the art
     *
     * @return array
     */
    public function result()
    {
        $file = $this->artFile($this->art) ?: $this->artFile($this->default_art);
        return $this->parse($file);
    }
}

?>