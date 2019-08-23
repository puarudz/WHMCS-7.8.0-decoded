<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Slack;

class Attachment
{
    public $fallback = "";
    public $color = "";
    public $pretext = "";
    public $title = "";
    public $title_link = "";
    public $text = "";
    public $fields = NULL;
    public $footer = "";
    public $footer_icon = "";
    public function fallback($fallback)
    {
        $this->fallback = trim($fallback);
        return $this;
    }
    public function color($color)
    {
        $this->color = trim($color);
        return $this;
    }
    public function pretext($pretext)
    {
        $this->pretext = trim($pretext);
        return $this;
    }
    public function title($title)
    {
        $this->title = trim($title);
        return $this;
    }
    public function title_link($title_link)
    {
        $this->title_link = trim($title_link);
        return $this;
    }
    public function text($text)
    {
        $this->text = trim($text);
        return $this;
    }
    public function footer($footer)
    {
        $this->footer = trim($footer);
        return $this;
    }
    public function footer_icon($footer_icon)
    {
        $this->footer_icon = trim($footer_icon);
        return $this;
    }
    public function addField(Field $field)
    {
        $this->fields[] = $field;
        return $this;
    }
    public function toArray()
    {
        $attachment = array("fallback" => $this->fallback, "color" => $this->color, "pretext" => $this->pretext, "title" => $this->title, "title_link" => $this->title_link, "text" => $this->text, "footer" => $this->footer, "footer_icon" => $this->footer_icon);
        if (!empty($this->fields)) {
            $attachment["fields"] = array_map(function (Field $field) {
                return $field->toArray();
            }, $this->fields);
        }
        return $attachment;
    }
}

?>