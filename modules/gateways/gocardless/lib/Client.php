<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\GoCardless;

class Client
{
    protected $sandboxMode = false;
    protected $accessToken = "";
    const URLS = array("sandbox" => "https://api-sandbox.gocardless.com/", "live" => "https://api.gocardless.com/");
    const API_VERSION = "2015-07-06";
    public function __construct($accessToken)
    {
        $this->setSandboxMode(substr($accessToken, 0, 7) == "sandbox");
        $this->setAccessToken($accessToken);
    }
    public static function factory($accessToken)
    {
        $client = new static($accessToken);
        return new Api\Client($client->getDefaultOptions());
    }
    protected function getUrl()
    {
        $type = "live";
        if ($this->isSandbox()) {
            $type = "sandbox";
        }
        return self::URLS[$type];
    }
    protected function isSandbox()
    {
        return $this->sandboxMode;
    }
    protected function getUserAgent()
    {
        $uAgent = array();
        $uAgent[] = "whmcs/" . \App::getVersion()->getMajor();
        $uAgent[] = "schema-version/" . self::API_VERSION;
        $uAgent[] = "GuzzleHttp/" . \GuzzleHttp\Client::VERSION;
        $uAgent[] = "php/" . phpversion();
        if (extension_loaded("curl") && function_exists("curl_version")) {
            $curlInfo = curl_version();
            $uAgent[] = "curl/" . $curlInfo["version"];
            $uAgent[] = "curl/" . $curlInfo["host"];
        }
        return implode(" ", $uAgent);
    }
    protected function setSandboxMode($sandbox)
    {
        $this->sandboxMode = $sandbox;
    }
    protected function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
    protected function getAccessToken()
    {
        return $this->accessToken;
    }
    protected function getDefaultOptions()
    {
        return array("base_url" => $this->getUrl(), "defaults" => array("headers" => array("GoCardless-Version" => self::API_VERSION, "Accept" => "application/json", "Content-Type" => "application/json", "Authorization" => "Bearer " . $this->getAccessToken(), "User-Agent" => $this->getUserAgent()), "exceptions" => false));
    }
}

?>