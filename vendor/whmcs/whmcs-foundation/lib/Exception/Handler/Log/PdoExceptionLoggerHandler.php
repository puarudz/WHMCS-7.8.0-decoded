<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Handler\Log;

class PdoExceptionLoggerHandler extends \WHMCS\Log\ActivityLogHandler
{
    public function isHandling(array $record)
    {
        if (parent::isHandling($record)) {
            return \WHMCS\Utility\ErrorManagement::isAllowedToLogSqlErrors();
        }
        return false;
    }
    protected function write(array $record)
    {
        if ($record["context"]["exception"] instanceof \PDOException) {
            parent::write($record);
        }
    }
    protected function getDefaultFormatter()
    {
        return new \Monolog\Formatter\LineFormatter("PDO Exception: %message%");
    }
}

?>