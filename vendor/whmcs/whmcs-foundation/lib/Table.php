<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Table
{
    private $fields = array();
    private $labelwidth = "20";
    public function __construct($width = "20")
    {
        $this->labelwidth = $width;
        return $this;
    }
    public function add($name, $field, $fullwidth = false)
    {
        if ($fullwidth) {
            $fullwidth = true;
        }
        $this->fields[] = array("name" => $name, "field" => $field, "fullwidth" => $fullwidth);
        return $this;
    }
    public function output()
    {
        $code = "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\"><tr>";
        $i = 0;
        foreach ($this->fields as $k => $v) {
            $colspan = "";
            if ($v["fullwidth"]) {
                $colspan = "3";
                if ($colspan && $i != 0) {
                    $code .= "</tr><tr>";
                    $i = 0;
                }
                $i++;
            }
            $code .= "<td class=\"fieldlabel\" width=\"" . $this->labelwidth . "%\">" . $v["name"] . "</td>" . "<td class=\"fieldarea\"" . ($colspan ? " colspan=\"" . $colspan . "\"" : " width=\"" . (50 - $this->labelwidth) . "%\"") . ">" . $v["field"] . "</td>";
            $i++;
            if ($i == 2) {
                $code .= "</tr><tr>";
                $i = 0;
            }
        }
        $code .= "</tr></table>";
        return $code;
    }
}

?>