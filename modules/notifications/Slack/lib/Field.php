<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Slack;

class Field
{
    public $title = "";
    public $value = "";
    public $short = false;
    public function title($title)
    {
        $this->title = trim($title);
        return $this;
    }
    public function value($value)
    {
        $this->value = trim($value);
        return $this;
    }
    public function short()
    {
        $this->short = true;
        return $this;
    }
    public function toArray()
    {
        $field = array("title" => $this->title, "value" => $this->value, "short" => $this->short);
        return $field;
    }
}

?>