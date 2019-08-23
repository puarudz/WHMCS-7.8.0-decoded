<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Router;

use League\CLImate\Util\Helper;
use League\CLImate\Util\OutputImporter;
class BasicRouter extends BaseRouter
{
    use OutputImporter;
    /**
     * @return string
     */
    public function pathPrefix()
    {
        return 'Basic';
    }
    /**
     * Execute a basic terminal object
     *
     * @param \League\CLImate\TerminalObject\Basic\BasicTerminalObject $obj
     * @return void
     */
    public function execute($obj)
    {
        $results = Helper::toArray($obj->result());
        $this->output->persist();
        foreach ($results as $result) {
            if ($obj->sameLine()) {
                $this->output->sameLine();
            }
            $this->output->write($obj->getParser()->apply($result));
        }
        $this->output->persist(false);
    }
}

?>