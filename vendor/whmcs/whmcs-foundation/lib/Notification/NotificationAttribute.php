<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification;

class NotificationAttribute implements Contracts\NotificationAttributeInterface
{
    protected $label = "";
    protected $value = "";
    protected $url = "";
    protected $style = "";
    protected $icon = "";
    public function setLabel($label)
    {
        $this->label = trim($label);
        return $this;
    }
    public function setValue($value)
    {
        $this->value = trim($value);
        return $this;
    }
    public function setUrl($url)
    {
        $this->url = trim($url);
        return $this;
    }
    public function setStyle($style)
    {
        $this->style = trim($style);
        return $this;
    }
    public function setIcon($icon)
    {
        $this->icon = trim($icon);
        return $this;
    }
    public function getLabel()
    {
        return $this->label;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function getStyle()
    {
        return $this->style;
    }
    public function getIcon()
    {
        return $this->icon;
    }
}

?>