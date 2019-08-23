<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class LegacyWidget extends AbstractWidget
{
    protected $bodyOutput = NULL;
    protected $jsOutput = NULL;
    protected $jqueryOutput = NULL;
    public static function factory($title, $bodyOutput, $jsOutput, $jqueryOutput)
    {
        $widget = new self();
        $widget->setTitle($title)->setBodyOutput($bodyOutput)->setJsOutput($jsOutput)->setJqueryOutput($jqueryOutput);
        return $widget;
    }
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    public function setBodyOutput($bodyOutput)
    {
        $this->bodyOutput = $bodyOutput;
        return $this;
    }
    public function setJsOutput($jsOutput)
    {
        $this->jsOutput = $jsOutput;
        return $this;
    }
    public function setJqueryOutput($jqueryOutput)
    {
        $this->jqueryOutput = $jqueryOutput;
        return $this;
    }
    public function getId()
    {
        return str_replace(" ", "", strtolower($this->title));
    }
    public function getData()
    {
        return array();
    }
    public function generateOutput($data)
    {
        $output = $this->bodyOutput;
        if ($this->jsOutput) {
            $output .= "<script>" . $this->jsOutput . "</script>";
        }
        if ($this->jqueryOutput) {
            $output .= "<script>\$(document).ready(function(){setTimeout(function(){" . $this->jqueryOutput . "}, 2000);});</script>";
        }
        return $output;
    }
}

?>