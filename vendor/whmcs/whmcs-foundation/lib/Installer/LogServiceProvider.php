<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer;

class LogServiceProvider extends \WHMCS\Log\LogServiceProvider
{
    public function factoryDefaultChannelLogger()
    {
        return new \Monolog\Logger("WHMCS Installer");
    }
    protected function importLogHandlers($baseDirectory = NULL)
    {
        parent::importLogHandlers();
        parent::importLogHandlers(INSTALLER_DIR);
        return $this;
    }
    public static function getUpdateLogHandler()
    {
        $updateLogHandler = new Update\UpdateLogHandler(\Monolog\Logger::DEBUG);
        $updateLogHandler->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
        $updateLogHandler->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
        $timer = \WHMCS\Carbon::now();
        $updateLogHandler->pushProcessor(function (array $record) use($timer) {
            $now = \WHMCS\Carbon::now();
            $record["extra"]["time_lapse"] = $timer->diffInSeconds();
            return $record;
        });
        return $updateLogHandler;
    }
}

?>