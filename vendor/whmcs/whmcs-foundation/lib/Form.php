<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Form
{
    private $frmname = "";
    public function __construct($name = "frm1")
    {
        $this->name($name);
        return $this;
    }
    public function name($name)
    {
        $this->frmname = $name;
        return $this;
    }
    public function getname()
    {
        return $this->frmname;
    }
    public function form($url = "", $files = false, $target = "", $method = "post", $nosubmitvar = false)
    {
        if (!$url) {
            $url = $_SERVER["PHP_SELF"];
        }
        $code = "<form method=\"" . $method . "\" action=\"" . $url . "\" name=\"" . $this->frmname . "\" id=\"" . $this->frmname . "\"";
        if ($files) {
            $code .= " enctype=\"multipart/form-data\"";
        }
        if ($target) {
            $code .= " target=\"_" . $target . "\"";
        }
        $code .= ">";
        if (!$nosubmitvar) {
            $code .= $this->hidden("__fp" . $this->frmname, "1");
        }
        return $code;
    }
    public function issubmitted($skiptoken = false)
    {
        if (isset($_POST["__fp" . $this->frmname])) {
            if (!$skiptoken) {
                check_token();
            }
            return true;
        }
        return false;
    }
    public function text($name, $value = "", $size = 30, $disabled = false, $class = "", $type = "text")
    {
        $inputId = "input" . ucfirst($name);
        $code = "<input type=\"" . $type . "\" name=\"" . $name . "\" value=\"" . $value . "\" size=\"" . $size . "\"";
        if ($disabled) {
            $code .= " disabled=\"disabled\"";
        }
        if ($class) {
            $code .= " class=\"" . $class . "\"";
        }
        $code .= " id=\"" . $inputId . "\" />";
        return $code;
    }
    public function password($name, $value = "", $size = 30, $disabled = false, $class = "")
    {
        return $this->text($name, $value, $size, $disabled, $class, "password");
    }
    public function date($name, $value = "", $size = 12, $disabled = false, $class = "form-control date-picker-single")
    {
        $inputId = "input" . ucfirst($name);
        $field = $this->text($name, $value, $size, $disabled, $class);
        $return = "<div class=\"form-group date-picker-prepend-icon\">\n    <label for=\"" . $inputId . "\" class=\"field-icon\">\n        <i class=\"fal fa-calendar-alt\"></i>\n    </label>\n    " . $field . "\n</div>";
        return $return;
    }
    public function textarea($name, $value, $rows = 3, $cols = 50, $class = "form-control")
    {
        $code = "<textarea name=\"" . $name . "\" rows=\"" . $rows . "\"";
        if (substr($cols, -1, 1) == "%") {
            $code .= " style=\"width:" . $cols . "\"";
        } else {
            $code .= " cols=\"" . $cols . "\"";
        }
        if ($class) {
            $code .= " class=\"" . $class . "\"";
        }
        $code .= ">" . $value . "</textarea>";
        return $code;
    }
    public function checkbox($name, $label = "", $checked = false, $value = "1", $class = "")
    {
        $code = "";
        if ($label) {
            $code .= "<label class=\"checkbox-inline\">";
        }
        $code .= "<input type=\"checkbox\" name=\"" . $name . "\" value=\"" . $value . "\"" . ($checked ? " checked=\"checked\"" : "") . ($class ? " class=\"" . $class . "\"" : "") . " />";
        if ($label) {
            $code .= " " . $label . "</label>";
        }
        return $code;
    }
    public function dropdown($name, $values = array(), $selected = "", $onchange = "", $anyopt = "", $noneopt = "", $size = "1", $id = "", $cssClass = "form-control select-inline")
    {
        global $aInt;
        $code = "<select name=\"" . $name . "\"";
        if (1 < $size) {
            $code .= " size=\"" . $size . "\"";
        }
        if ($onchange) {
            $code .= " onchange=\"" . $onchange . "\"";
        }
        if ($cssClass) {
            $code .= " class=\"" . $cssClass . "\"";
        }
        if ($id) {
            $code .= " id=\"" . $id . "\"";
        }
        $code .= ">";
        if ($anyopt) {
            $code .= "<option value=\"0\">" . $aInt->lang("global", "any") . "</option>";
        }
        if ($noneopt) {
            $code .= "<option value=\"0\">" . $aInt->lang("global", "none") . "</option>";
        }
        if (is_array($values)) {
            foreach ($values as $k => $v) {
                $color = "";
                $colorData = "";
                if (is_array($v)) {
                    list($color, $v) = $v;
                    if (stristr($cssClass, "selectize")) {
                        $colorData = " data-data='{\"colour\":\"" . $color . "\"}'";
                    }
                }
                $code .= "<option value=\"" . $k . "\"" . ($k == $selected ? " selected=\"selected\"" : "") . ($color ? " style=\"background-color:" . $color . "\"" : "") . $colorData . ">" . $v . "</option>";
            }
        } else {
            $code .= $values;
        }
        $code .= "</select>";
        return $code;
    }
    public function radio($name, $values = array(), $selected = "", $spacer = "<br />")
    {
        $code = "";
        foreach ($values as $k => $v) {
            $code .= "<label class=\"radio-inline\"><input type=\"radio\" name=\"" . $name . "\" value=\"" . $k . "\"" . ($k == $selected ? " checked=\"checked\"" : "") . " /> " . $v . "</label>" . $spacer;
        }
        return $code;
    }
    public function hidden($name, $value)
    {
        $code = "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . $value . "\" />";
        return $code;
    }
    public function submit($text, $class = "btn btn-primary")
    {
        $code = "<input type=\"submit\" value=\"" . $text . "\" class=\"" . $class . "\" />";
        return $code;
    }
    public function button($text, $onclick = "", $class = "btn btn-default")
    {
        $onclick = $onclick ? " onclick=\"" . $onclick . "\"" : "";
        $buttonIDName = View\Helper::generateCssFriendlyId("btn" . $text);
        $code = "<button type=\"button\" class=\"" . $class . "\"" . $onclick . "id=\"" . $buttonIDName . "\">" . $text . "</button>";
        return $code;
    }
    public function modalButton($text, $dataTarget, $class = "btn btn-default")
    {
        $buttonCode = "<button id='btn" . $dataTarget . "' type='button' class='" . $class . "' data-toggle='modal' data-target='#" . $dataTarget . "'>\n    " . $text . "\n</button>";
        return $buttonCode;
    }
    public function reset($text, $class = "btn btn-default")
    {
        $code = "<input type=\"reset\" value=\"" . $text . "\" class=\"" . $class . "\" />";
        return $code;
    }
    public function savereset()
    {
        global $aInt;
        $code = "<p align=\"center\">" . $this->submit($aInt->lang("global", "savechanges"), "btn btn-primary") . " " . $this->reset($aInt->lang("global", "cancelchanges")) . "</p>";
        return $code;
    }
    public function close()
    {
        $code = "</form>";
        return $code;
    }
}

?>