<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud;

class AbstractResponse
{
    protected $data = NULL;
    protected $httpCode = 200;
    public function __construct($jsonData, $httpCode = NULL)
    {
        if (is_null($httpCode)) {
            $httpCode = $this->get("http_response_code");
        }
        if (is_null($httpCode)) {
            $httpCode = 200;
        }
        $this->httpCode = $httpCode;
        $this->data = $this->parseResponseJson($jsonData);
    }
    protected function parseResponseJson($jsonData)
    {
        $decodedData = json_decode($jsonData, true);
        if (!$decodedData || !is_array($decodedData) || json_last_error() !== JSON_ERROR_NONE) {
            return array();
        }
        return $decodedData;
    }
    public function isSuccessful()
    {
        return $this->httpCode == 200;
    }
    public function getHttpCode()
    {
        return $this->httpCode;
    }
    public function isEmpty()
    {
        return count($this->data) == 0;
    }
    public function get($key)
    {
        $keyParts = explode(".", $key);
        if (count($keyParts) == 1) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }
        $value = $this->data;
        foreach ($keyParts as $key) {
            $value = isset($value[$key]) ? $value[$key] : null;
        }
        return $value;
    }
    public function toArray()
    {
        $response = (array) $this->data;
        $response["http_response_code"] = $this->httpCode;
        return $response;
    }
    public function toJson()
    {
        return json_encode($this->data);
    }
}

?>