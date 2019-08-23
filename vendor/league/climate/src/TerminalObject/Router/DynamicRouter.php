<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Router;

use League\CLImate\Util\OutputImporter;
class DynamicRouter extends BaseRouter
{
    use OutputImporter;
    /**
     * @return string
     */
    public function pathPrefix()
    {
        return 'Dynamic';
    }
    /**
     * Execute a dynamic terminal object using given arguments
     *
     * @param \League\CLImate\TerminalObject\Dynamic\DynamicTerminalObject $obj
     *
     * @return \League\CLImate\TerminalObject\Dynamic\DynamicTerminalObject
     */
    public function execute($obj)
    {
        $obj->output($this->output);
        return $obj;
    }
}

?>