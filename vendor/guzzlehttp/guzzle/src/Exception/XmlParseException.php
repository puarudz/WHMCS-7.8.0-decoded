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
 * Exception when a client is unable to parse the response body as XML
 */
class XmlParseException extends ParseException
{
    /** @var \LibXMLError */
    protected $error;
    public function __construct($message = '', ResponseInterface $response = null, \Exception $previous = null, \LibXMLError $error = null)
    {
        parent::__construct($message, $response, $previous);
        $this->error = $error;
    }
    /**
     * Get the associated error
     *
     * @return \LibXMLError|null
     */
    public function getError()
    {
        return $this->error;
    }
}

?>