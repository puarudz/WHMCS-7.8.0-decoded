<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

abstract class AbstractAttachmentResponse extends \Zend\Diactoros\Response
{
    use \Zend\Diactoros\Response\InjectContentTypeTrait;
    protected $data = NULL;
    protected $attachmentFilename = NULL;
    public function __construct($data, $attachmentFilename, $status = 200, array $headers = array())
    {
        $this->setData($data);
        $this->setAttachmentFilename($attachmentFilename);
        $headers = array_replace($headers, array("content-length" => $this->getDataContentLength(), "content-disposition" => $this->getDataContentDisposition()));
        parent::__construct($this->createDataStream(), $status, $this->injectContentType($this->getDataContentType(), $headers));
    }
    public function getData()
    {
        return $this->data;
    }
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
    public function getAttachmentFilename()
    {
        return $this->attachmentFilename;
    }
    public function setAttachmentFilename($attachmentFilename)
    {
        $this->attachmentFilename = $attachmentFilename;
        return $this;
    }
    protected abstract function createDataStream();
    protected abstract function getDataContentType();
    protected abstract function getDataContentLength();
    protected function getDataContentDisposition()
    {
        return sprintf("attachment; filename=\"%s\"", $this->getAttachmentFilename());
    }
}

?>