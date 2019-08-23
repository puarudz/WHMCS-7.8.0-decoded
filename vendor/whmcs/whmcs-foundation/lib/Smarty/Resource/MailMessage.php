<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Smarty\Resource;

class MailMessage extends \Smarty_Resource_Custom
{
    protected $message = NULL;
    public function __construct(\WHMCS\Mail\Message $message)
    {
        $this->setMessage($message);
    }
    protected function setMessage(\WHMCS\Mail\Message $message)
    {
        $this->message = $message;
        return $this;
    }
    protected function getMessage()
    {
        return $this->message;
    }
    protected function fetch($name, &$source, &$mtime)
    {
        $mtime = time();
        switch ($name) {
            case "subject":
                $source = $this->getMessage()->getSubject();
                break;
            case "message":
                $source = $this->getMessage()->getBodyWithoutCSS();
                break;
            case "plaintext":
                $source = $this->getMessage()->getPlainText();
                break;
            default:
                $source = null;
                $mtime = null;
                break;
        }
    }
}

?>