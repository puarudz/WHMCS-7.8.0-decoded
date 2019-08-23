<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Hipchat;

class Card
{
    public $id = NULL;
    public $title = "";
    public $style = "application";
    public $content = "";
    public $format = "html";
    public $cardFormat = NULL;
    public $url = "";
    public $activity = NULL;
    public $activityIcon = NULL;
    public $activityIcon2 = NULL;
    public $icon = NULL;
    public $icon2 = NULL;
    public $attributes = array();
    public function __construct()
    {
        $this->id = str_random();
    }
    public function title($title)
    {
        $this->title = trim($title);
        return $this;
    }
    public function id($id)
    {
        $this->id = trim($id);
        return $this;
    }
    public function style($style)
    {
        $this->style = $style;
        return $this;
    }
    public function message($content = "")
    {
        $this->content = $content;
        return $this;
    }
    public function cardFormat($cardFormat)
    {
        $this->cardFormat = trim($cardFormat);
        return $this;
    }
    public function url($url)
    {
        $this->url = trim($url);
        return $this;
    }
    public function activity($html, $icon = NULL, $icon2 = NULL)
    {
        $this->activity = trim($html);
        if (!empty($icon)) {
            $this->activityIcon = trim($icon);
        }
        if (!empty($icon2)) {
            $this->activityIcon2 = trim($icon2);
        }
        return $this;
    }
    public function icon($icon, $icon2 = NULL)
    {
        $this->icon = trim($icon);
        if (!empty($icon2)) {
            $this->icon2 = trim($icon2);
        }
        return $this;
    }
    public function addAttribute(CardAttribute $attribute)
    {
        $this->attributes[] = $attribute;
        return $this;
    }
    public function toArray()
    {
        $card = array_filter(array("id" => $this->id, "style" => $this->style, "format" => $this->cardFormat, "title" => $this->title, "url" => $this->url));
        if (!empty($this->content)) {
            $card["description"] = array("value" => $this->content, "format" => $this->format);
        }
        if (!empty($this->activity)) {
            $card["activity"] = array_filter(array("html" => $this->activity, "icon" => array_filter(array("url" => $this->activityIcon, "url@2x" => $this->activityIcon2))));
        }
        if (!empty($this->icon)) {
            $card["icon"] = array_filter(array("url" => $this->icon, "url@2x" => $this->icon2));
        }
        if (!empty($this->attributes)) {
            $card["attributes"] = array_map(function (CardAttribute $attribute) {
                return $attribute->toArray();
            }, $this->attributes);
        }
        return $card;
    }
}

?>