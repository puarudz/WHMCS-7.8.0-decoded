<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Log;

class ActivityLogHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    protected function write(array $record)
    {
        if ($record["formatted"]) {
            try {
                $event = array("date" => (string) \WHMCS\Carbon::now()->format("YmdHis"), "description" => $record["formatted"], "user" => "", "userid" => "", "ipaddr" => "");
                \WHMCS\Database\Capsule::table("tblactivitylog")->insertGetId($event);
            } catch (\Exception $e) {
            }
        }
    }
}

?>