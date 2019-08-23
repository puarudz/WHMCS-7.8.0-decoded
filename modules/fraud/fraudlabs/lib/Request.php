<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud\FraudLabs;

class Request extends \WHMCS\Module\Fraud\AbstractRequest implements \WHMCS\Module\Fraud\RequestInterface
{
    const URL = "https://api.fraudlabspro.com/v1/order/screen";
    public function setLicenseKey($licenseKey)
    {
        $this->licenseKey = $licenseKey;
        return $this;
    }
    public function call($data)
    {
        $data["key"] = $this->licenseKey;
        $client = $this->getClient();
        $response = $client->post($this->getApiEndpointUrl(), array("exceptions" => false, "body" => $data));
        $fraudResponse = new Response($response->getBody(), $response->getStatusCode());
        $this->log("check", $data, $response, $fraudResponse->toArray());
        if ($fraudResponse->isEmpty()) {
            throw new \WHMCS\Exception\Http\ConnectionError($response->getBody());
        }
        return $fraudResponse;
    }
    protected function getApiEndpointUrl()
    {
        return self::URL;
    }
}

?>