<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

trait JavascriptTrait
{
    protected $jquery = array();
    protected $javascript = array();
    protected $javascriptChart = array();
    public function addJquery($code)
    {
        if (!is_array($code)) {
            $code = array($code);
        }
        $this->setJquery(array_merge($this->getJquery(), $code));
        return $this;
    }
    public function addJavascript($code)
    {
        if (!is_array($code)) {
            $code = array($code);
        }
        $this->setJavascript(array_merge($this->getJavascript(), $code));
        return $this;
    }
    public function addJavascriptChart($name)
    {
        if (!is_array($name)) {
            $name = array($name);
        }
        $this->setJavascriptChart(array_merge($this->getJavascriptChart(), $name));
        return $this;
    }
    public function getFormattedJquery()
    {
        return implode("\n", $this->getJquery());
    }
    public function getFormattedJavascript()
    {
        return implode("\n", $this->getJavascript()) . "\n" . $this->getChartRedrawJavascript();
    }
    protected function getChartRedrawJavascript()
    {
        $redraw = "function redrawCharts() { ";
        foreach ($this->getJavascriptChart() as $chart) {
            $redraw .= $chart . "();\n";
        }
        $redraw .= "}\n";
        return $redraw . "\$(window).bind(\"resize\", function(event) { redrawCharts(); });";
    }
    public function getJquery()
    {
        return $this->jquery;
    }
    public function setJquery($jquery)
    {
        $this->jquery = $jquery;
        return $this;
    }
    public function getJavascript()
    {
        return $this->javascript;
    }
    public function setJavascript($javascript)
    {
        $this->javascript = $javascript;
        return $this;
    }
    public function getJavascriptChart()
    {
        return $this->javascriptChart;
    }
    public function setJavascriptChart($javascriptChart)
    {
        $this->javascriptChart = $javascriptChart;
        return $this;
    }
    public function modal($name, $title, $message, array $buttons = array(), $size = "", $panelType = "primary")
    {
        switch ($size) {
            case "small":
                $dialogClass = "modal-dialog modal-sm";
                break;
            case "large":
                $dialogClass = "modal-dialog modal-lg";
                break;
            default:
                $dialogClass = "modal-dialog";
        }
        switch ($panelType) {
            case "default":
            case "primary":
            case "success":
            case "info":
            case "warning":
            case "danger":
                $panel = "panel-" . $panelType;
                break;
            default:
                $panel = "panel-primary";
        }
        $buttonsOutput = "";
        foreach ($buttons as $button) {
            $id = \WHMCS\View\Helper::generateCssFriendlyId($name, $button["title"]);
            $onClick = isset($button["onclick"]) ? "onclick='" . $button["onclick"] . "'" : "data-dismiss=\"modal\"";
            $class = isset($button["class"]) ? $button["class"] : "btn-default";
            $type = isset($button["type"]) ? $button["type"] : "button";
            $buttonsOutput .= "<button type=\"" . $type . "\" id=\"" . $id . "\" class=\"btn " . $class . "\" " . $onClick . ">\n    " . $button["title"] . "\n</button>";
        }
        $modalOutput = "<div class=\"modal fade\" id=\"modal" . $name . "\" role=\"dialog\" aria-labelledby=\"" . $name . "Label\" aria-hidden=\"true\">\n    <div class=\"" . $dialogClass . "\">\n        <div class=\"modal-content panel " . $panel . "\">\n            <div id=\"modal" . $name . "Heading\" class=\"modal-header panel-heading\">\n                <button type=\"button\" class=\"close\" data-dismiss=\"modal\">\n                    <span aria-hidden=\"true\">&times;</span>\n                    <span class=\"sr-only\">{AdminLang::trans('global.close')}</span>\n                </button>\n                <h4 class=\"modal-title\" id=\"" . $name . "Label\">" . $title . "</h4>\n            </div>\n            <div id=\"modal" . $name . "Body\" class=\"modal-body panel-body\">\n                " . $message . "\n            </div>\n            <div id=\"modal" . $name . "Footer\" class=\"modal-footer panel-footer\">\n                " . $buttonsOutput . "\n            </div>\n        </div>\n    </div>\n</div>";
        return $modalOutput;
    }
    public function modalWithConfirmation($name, $question, $url, $jsVariable = "invoice")
    {
        $name = \WHMCS\View\Helper::generateCssFriendlyId($name);
        $token = generate_token("link");
        $okOnClick = "window.location='" . $url . "' + " . $jsVariable . " + '" . $token . "';";
        $modalOutput = "<div class=\"modal fade\" id=\"" . $name . "\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"" . $name . "Label\" aria-hidden=\"true\">\n    <div class=\"modal-dialog\">\n        <div class=\"modal-content panel\">\n            <div class=\"modal-body panel-body\">\n                " . $question . "\n            </div>\n            <div class=\"modal-footer panel-footer\">\n                <button type='button' id='" . $name . "-cancel' class='btn btn-default' data-dismiss='modal'>\n                    {AdminLang::trans('global.cancel')}\n                </button>\n                <button type='button' id='" . $name . "-ok' class='btn btn-primary' data-dismiss='modal' onclick=\"" . $okOnClick . "\">\n                    {AdminLang::trans('global.ok')}\n                </button>\n            </div>\n        </div>\n    </div>\n</div>";
        $js = "function " . $name . "(id) {\n    " . $jsVariable . " = id;\n    \$('#" . $name . "').modal('show');\n}";
        $this->addJavascript($js);
        return $modalOutput;
    }
}

?>