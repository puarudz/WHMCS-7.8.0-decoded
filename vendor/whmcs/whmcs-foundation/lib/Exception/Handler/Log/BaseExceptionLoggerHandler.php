<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Handler\Log;

class BaseExceptionLoggerHandler extends \WHMCS\Log\ActivityLogHandler
{
    public function isHandling(array $record)
    {
        if (parent::isHandling($record)) {
            return \WHMCS\Utility\ErrorManagement::isAllowedToLogErrors();
        }
        return false;
    }
    protected function write(array $record)
    {
        $exception = $record["context"]["exception"];
        if ($exception instanceof \Exception && !$exception instanceof \PDOException && !$exception instanceof \ErrorException) {
            parent::write($record);
        }
    }
    protected function getDefaultFormatter()
    {
        return new \Monolog\Formatter\LineFormatter("Exception: %message%");
    }
}

?>