<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Http;

class ServerRequest extends \WHMCS\Http\Message\ServerRequest
{
    public function __clone()
    {
        \DI::make("runtimeStorage")->apiRequest = $this;
    }
    public static function fromGlobals(array $server = NULL, array $query = NULL, array $body = NULL, array $cookies = NULL, array $files = NULL)
    {
        $whmcsPsr7Request = \Zend\Diactoros\ServerRequestFactory::fromGlobals($server, $query, $body, $cookies, $files);
        return (new static($whmcsPsr7Request->getServerParams(), $whmcsPsr7Request->getUploadedFiles(), $whmcsPsr7Request->getUri(), $whmcsPsr7Request->getMethod(), $whmcsPsr7Request->getBody(), $whmcsPsr7Request->getHeaders(), $whmcsPsr7Request->getCookieParams(), $whmcsPsr7Request->getQueryParams(), $whmcsPsr7Request->getParsedBody(), $whmcsPsr7Request->getProtocolVersion()))->seedAttributes();
    }
    protected function seedAttributes()
    {
        $attributeMap = array("action" => array("attributeName" => "action", "default" => ""), "responsetype" => array("attributeName" => "response_format", "default" => null), "identifier" => array("attributeName" => "identifier", "default" => ""), "secret" => array("attributeName" => "secret", "default" => ""), "username" => array("attributeName" => "username", "default" => ""), "password" => array("attributeName" => "password", "default" => ""), "accesskey" => array("attributeName" => "accesskey", "default" => ""));
        $request = $this;
        foreach ($attributeMap as $userInputKey => $attribute) {
            $request = $request->withAttribute($attribute["attributeName"], $request->get($userInputKey, $attribute["default"]));
        }
        return $request;
    }
    public function getAction()
    {
        return $this->getAttribute("action", "");
    }
    public function getResponseFormat()
    {
        return $this->getAttribute("response_format", "");
    }
    public function isDeviceAuthentication()
    {
        return (bool) $this->getAttribute("identifier", false);
    }
    public function getIdentifier()
    {
        return $this->getAttribute("identifier", false);
    }
    public function getSecret()
    {
        return $this->getAttribute("secret", false);
    }
    public function getUsername()
    {
        return $this->getAttribute("username", false);
    }
    public function getPassword()
    {
        return $this->getAttribute("password", false);
    }
    public function getAccessKey()
    {
        return $this->getAttribute("accesskey", "");
    }
}

?>