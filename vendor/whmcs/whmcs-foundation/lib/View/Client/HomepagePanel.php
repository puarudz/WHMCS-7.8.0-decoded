<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client;

class HomepagePanel
{
    protected $name = NULL;
    protected $label = NULL;
    protected $icon = NULL;
    protected $color = "blue";
    protected $order = 0;
    protected $bodyHtml = NULL;
    protected $buttonLink = NULL;
    protected $buttonText = NULL;
    protected $buttonIcon = NULL;
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }
    public function setBodyHtml($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }
    public function setHeaderButton($link, $text, $icon = "")
    {
        $this->buttonLink = $link;
        $this->buttonText = $text;
        $this->buttonIcon = $icon;
        return $this;
    }
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }
    public function toArray()
    {
        return array("name" => $this->getName(), "label" => $this->label, "icon" => $this->icon, "order" => $this->order, "bodyHtml" => $this->getBodyHtml(), "extras" => array("color" => $this->color, "btn-link" => $this->buttonLink, "btn-text" => $this->buttonText, "btn-icon" => $this->buttonIcon));
    }
}

?>