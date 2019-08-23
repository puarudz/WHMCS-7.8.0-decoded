<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class JsonAttachmentResponse extends AbstractAttachmentResponse
{
    public function __construct($data, $attachmentFilename, $status = 200, array $headers = array(), $encodingOptions = \Zend\Diactoros\Response\JsonResponse::DEFAULT_JSON_FLAGS)
    {
        if (is_array($data)) {
            json_encode(null);
            $data = json_encode($data, $encodingOptions);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(sprintf("Unable to encode data to JSON in %s: %s", "WHMCS\\Http\\Message\\JsonAttachmentResponse", json_last_error_msg()));
            }
        }
        parent::__construct($data, $attachmentFilename, $status, $headers);
    }
    protected function createDataStream()
    {
        $body = new \Zend\Diactoros\Stream("php://temp", "wb+");
        $body->write($this->getData());
        $body->rewind();
        return $body;
    }
    protected function getDataContentType()
    {
        return "application/json";
    }
    protected function getDataContentLength()
    {
        return strlen($this->getData());
    }
}

?>