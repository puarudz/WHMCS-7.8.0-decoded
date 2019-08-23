<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Error;

trait ErrorLevelTrait
{
    protected $errorLevel = ErrorLevelInterface::ERROR;
    public function isAnError()
    {
        return ErrorLevelInterface::ERROR <= $this->errorLevel;
    }
    public function errorName()
    {
        return ucfirst(strtolower(\Monolog\Logger::getLevelName($this->errorLevel)));
    }
}

?>