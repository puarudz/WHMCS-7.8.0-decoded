<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User;

class Alert
{
    protected $message = NULL;
    protected $severity = "info";
    protected $link = NULL;
    protected $linkText = NULL;
    public function __construct($message, $severity = "info", $link = NULL, $linkText = NULL)
    {
        $this->setMessage($message)->setSeverity($severity)->setLink($link)->setLinkText($linkText);
    }
    public function getMessage()
    {
        return $this->message;
    }
    protected function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    public function getSeverity()
    {
        return $this->severity;
    }
    protected function setSeverity($severity = "info")
    {
        if (!in_array($severity, array("success", "info", "warning", "danger"))) {
            throw new \WHMCS\Exception("Please set an alert's severity to either \"success\", \"info\", \"warning\", or \"danger\".");
        }
        $this->severity = $severity;
        return $this;
    }
    public function getLink()
    {
        return $this->link;
    }
    protected function setLink($link)
    {
        $this->link = $link;
        return $this;
    }
    public function getLinkText()
    {
        return $this->linkText;
    }
    protected function setLinkText($linkText)
    {
        $this->linkText = $linkText;
        return $this;
    }
}

?>