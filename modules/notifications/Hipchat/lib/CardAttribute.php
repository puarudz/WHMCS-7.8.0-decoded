<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Hipchat;

class CardAttribute
{
    public $value = NULL;
    public $label = NULL;
    public $url = NULL;
    public $style = NULL;
    public $icon = NULL;
    public $icon2 = NULL;
    public function value($value)
    {
        $this->value = trim($value);
        return $this;
    }
    public function label($label)
    {
        $this->label = trim($label);
        return $this;
    }
    public function url($url)
    {
        $this->url = trim($url);
        return $this;
    }
    public function style($style)
    {
        if ($style == "success") {
            $style = "lozenge-success";
        } else {
            if ($style == "danger") {
                $style = "lozenge-error";
            } else {
                if ($style == "warning") {
                    $style = "lozenge-current";
                } else {
                    if ($style == "info") {
                        $style = "lozenge-complete";
                    } else {
                        if ($style == "primary") {
                            $style = "lozenge";
                        } else {
                            return $this;
                        }
                    }
                }
            }
        }
        $this->style = trim($style);
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
    public function toArray()
    {
        $attribute = array("value" => array_filter(array("label" => $this->value, "url" => $this->url, "style" => $this->style)));
        if (!empty($this->icon)) {
            $attribute["value"]["icon"] = array_filter(array("url" => $this->icon, "url@2x" => $this->icon2));
        }
        if (!empty($this->label)) {
            $attribute["label"] = $this->label;
        }
        return $attribute;
    }
}

?>