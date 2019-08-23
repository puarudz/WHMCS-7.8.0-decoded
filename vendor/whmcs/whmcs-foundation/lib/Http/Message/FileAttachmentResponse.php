<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class FileAttachmentResponse extends AbstractAttachmentResponse
{
    public function __construct($file, $attachmentFilename = NULL, $status = 200, array $headers = array())
    {
        $file = new \SplFileInfo($file);
        if (!$attachmentFilename) {
            $attachmentFilename = $file->getFilename();
        }
        parent::__construct($file, $attachmentFilename, $status, $headers);
    }
    protected function createDataStream()
    {
        return new \Zend\Diactoros\Stream($this->getData()->getRealPath(), "r");
    }
    protected function getDataContentType()
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($this->getData()->getRealPath());
    }
    protected function getDataContentLength()
    {
        return $this->getData()->getSize();
    }
}

?>