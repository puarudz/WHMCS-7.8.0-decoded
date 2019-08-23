<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Admin\HealthCheck;

class HealthCheckResult
{
    protected $name = NULL;
    protected $type = NULL;
    protected $title = NULL;
    protected $severityLevel = NULL;
    protected $body = NULL;
    public function __construct($name, $type, $title, $severityLevel, $body)
    {
        $this->setName($name)->setType($type)->setTitle($title)->setSeverityLevel($severityLevel)->setBody($body);
    }
    public function getName()
    {
        return $this->name;
    }
    protected function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getType()
    {
        return $this->type;
    }
    protected function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    public function getTitle()
    {
        return $this->title;
    }
    protected function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function getSeverityLevel()
    {
        return $this->severityLevel;
    }
    protected function setSeverityLevel($severityLevel)
    {
        if (!in_array($severityLevel, array(\Psr\Log\LogLevel::EMERGENCY, \Psr\Log\LogLevel::ALERT, \Psr\Log\LogLevel::CRITICAL, \Psr\Log\LogLevel::ERROR, \Psr\Log\LogLevel::WARNING, \Psr\Log\LogLevel::NOTICE, \Psr\Log\LogLevel::INFO, \Psr\Log\LogLevel::DEBUG))) {
            throw new \WHMCS\Exception("Please provide a valid PSR-3 log level");
        }
        $this->severityLevel = $severityLevel;
        return $this;
    }
    public function getBody()
    {
        return $this->body;
    }
    protected function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    public function toArray()
    {
        return array("name" => $this->getName(), "type" => $this->getType(), "severityLevel" => $this->getSeverityLevel(), "body" => $this->getBody());
    }
}

?>