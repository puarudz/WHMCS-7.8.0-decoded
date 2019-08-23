<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\Log;

class Handler extends \Monolog\Handler\AbstractProcessingHandler
{
    protected $isHandling = false;
    public function __construct($level = \Monolog\Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->isHandling = $this->isDebugEnabled();
        $this->pushProcessor(new RequestResponseProcessor());
    }
    public function write(array $record)
    {
        Log::create(array("action" => $record["message"], "request" => $record["extra"]["request_formatted"], "response" => $record["extra"]["response_formatted"], "status" => $record["extra"]["response_status"], "headers" => $record["extra"]["response_headers"], "level" => $record["level"]));
    }
    protected function isDebugEnabled()
    {
        $config = \DI::make("config");
        if (!empty($config["api_enable_logging"]) || \WHMCS\Config\Setting::getValue("ApiDebugMode")) {
            return true;
        }
        return false;
    }
    public function isHandling(array $record)
    {
        if ($this->isHandling) {
            return parent::isHandling($record);
        }
        return false;
    }
}

?>