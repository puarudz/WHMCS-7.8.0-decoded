<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Registrar\GoDaddy\Api;

class Response
{
    public $headers = NULL;
    public $status_code = NULL;
    public $body = NULL;
    public function __construct(\GuzzleHttp\Message\ResponseInterface $response)
    {
        $this->headers = $response->getHeaders();
        $this->status_code = $response->getStatusCode();
        $this->body = json_decode($response->getBody());
    }
}

?>