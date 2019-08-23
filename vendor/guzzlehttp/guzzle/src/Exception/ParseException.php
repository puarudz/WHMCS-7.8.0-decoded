<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Exception;

use GuzzleHttp\Message\ResponseInterface;
/**
 * Exception when a client is unable to parse the response body as XML or JSON
 */
class ParseException extends TransferException
{
    /** @var ResponseInterface */
    private $response;
    public function __construct($message = '', ResponseInterface $response = null, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->response = $response;
    }
    /**
     * Get the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}

?>