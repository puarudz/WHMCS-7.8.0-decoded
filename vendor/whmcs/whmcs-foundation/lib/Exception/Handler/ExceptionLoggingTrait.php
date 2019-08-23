<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Handler;

trait ExceptionLoggingTrait
{
    public function log($exception)
    {
        try {
            $isLogHandlerLoaded = false;
            $logger = \Log::self();
            foreach ($logger->getHandlers() as $logHandler) {
                if ($logHandler instanceof Log\BaseExceptionLoggerHandler) {
                    $isLogHandlerLoaded = true;
                }
            }
            if (!$isLogHandlerLoaded) {
                $logger->pushHandler(new Log\BaseExceptionLoggerHandler());
                $logger->pushHandler(new Log\ErrorExceptionLoggerHandler());
                $logger->pushHandler(new Log\PdoExceptionLoggerHandler());
            }
            $logger->error((string) $exception, array("exception" => $exception));
        } catch (\Exception $e) {
        } catch (\Error $e) {
        }
    }
}

?>