<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Router;

interface RouterInterface
{
    /**
     * @param $class
     * @return string
     */
    public function path($class);
    /**
     * @param $class
     * @return boolean
     */
    public function exists($class);
    /**
     * @param $obj
     * @return null|\League\CLImate\TerminalObject\Dynamic\DynamicTerminalObject
     */
    public function execute($obj);
    /**
     * @return string
     */
    public function pathPrefix();
}

?>