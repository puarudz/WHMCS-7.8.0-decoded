<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Update;

class UpdateLogHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    protected function write(array $record)
    {
        $instanceId = "not defined";
        if (isset($record["context"]["instance_id"])) {
            $instanceId = $record["context"]["instance_id"];
        } else {
            if ($storedId = \WHMCS\Config\Setting::getValue("UpdaterUpdateToken")) {
                $instanceId = $storedId;
            }
        }
        if (!isset($record["extra"])) {
            $record["extra"] = array();
        }
        if (trim($record["formatted"])) {
            $logEntry = new UpdateLog();
            $logEntry->message = $record["formatted"];
            $logEntry->instance_id = $instanceId;
            $logEntry->level = $record["level"];
            $logEntry->extra = json_encode($record["extra"]);
            $logEntry->save();
        }
    }
    protected function getDefaultFormatter()
    {
        return new \Monolog\Formatter\LineFormatter("%message%");
    }
}

?>