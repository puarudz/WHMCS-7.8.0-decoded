<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

abstract class AbstractViewableResponse extends \Zend\Diactoros\Response\HtmlResponse
{
    protected $getBodyFromPrivateStream = false;
    public function __construct($data = "", $status = 200, array $headers = array())
    {
        parent::__construct($data, $status, $headers);
    }
    public function getBody()
    {
        if ($this->getBodyFromPrivateStream) {
            return parent::getBody();
        }
        $body = new \Zend\Diactoros\Stream("php://temp", "wb+");
        $body->write($this->getOutputContent());
        $body->rewind();
        return $body;
    }
    protected abstract function getOutputContent();
}

?>