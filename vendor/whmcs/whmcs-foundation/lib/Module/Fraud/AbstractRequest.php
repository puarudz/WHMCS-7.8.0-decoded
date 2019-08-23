<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud;

class AbstractRequest
{
    protected $licenseKey = NULL;
    protected function log($action, $request, $response, $processedResponse)
    {
        $namespace = explode("\\", "WHMCS\\Module\\Fraud");
        $moduleName = end($namespace);
        return logModuleCall(strtolower($moduleName), $action, $request, $response, $processedResponse);
    }
    protected function getClient()
    {
        return new \GuzzleHttp\Client();
    }
}

?>