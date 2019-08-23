<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Registrar\GoDaddy\Api;

class Client
{
    protected $client = NULL;
    protected $options = array();
    public function __construct(array $options)
    {
        $this->options = $options;
        $this->client = $this->getClient();
    }
    protected function getClient()
    {
        if (is_null($this->client)) {
            return new \GuzzleHttp\Client($this->options);
        }
        return $this->client;
    }
    public function get($path, $params = array())
    {
        if (is_array($params) && array_key_exists("query", $params)) {
            $params["query"] = $this->castBooleanValuesToStrings($params["query"]);
        }
        $response = $this->getClient()->get($path, $params);
        $this->handleErrors($response);
        return $response->getBody();
    }
    public function put($path, $params)
    {
        $response = $this->getClient()->put($path, $params);
        $this->handleErrors($response, true);
        return $response->getBody();
    }
    public function post($path, $params)
    {
        $response = $this->getClient()->post($path, $params);
        $this->handleErrors($response);
        return $response->getBody();
    }
    public function patch($path, $params)
    {
        $response = $this->getClient()->patch($path, $params);
        $this->handleErrors($response, true);
        return $response->getBody();
    }
    public function delete($path, $params)
    {
        $response = $this->getClient()->patch($path, $params);
        $this->handleErrors($response, true);
        return $response->getBody();
    }
    protected function handleErrors($response, $emptyJsonOk = false)
    {
        $json = json_decode($response->getBody());
        $statusCode = $response->getStatusCode();
        if ($json === null && (!$emptyJsonOk || $emptyJsonOk && 400 <= $statusCode)) {
            $msg = ": Malformed response received from server";
            throw new \WHMCS\Module\Registrar\GoDaddy\Exception\MalformedResponseException($response->getStatusCode() . $msg);
        }
        if ($statusCode < 400) {
            return NULL;
        }
        $api_response = new Response($response);
        $message = $api_response->body->message;
        $message .= "<ul>";
        foreach ($api_response->body->fields as $errors) {
            $message .= "<li>" . $errors->path . " " . $errors->message . "</li>";
        }
        $message .= "</ul>";
        throw new \WHMCS\Module\Registrar\GoDaddy\Exception\ApiException($message);
    }
    protected function castBooleanValuesToStrings($query)
    {
        return array_map(function ($value) {
            if ($value === true) {
                return "true";
            }
            if ($value === false) {
                return "false";
            }
            if (is_array($value)) {
                return $this->castBooleanValuesToStrings($value);
            }
            return $value;
        }, $query);
    }
}

?>