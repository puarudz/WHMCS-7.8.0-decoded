<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard;

abstract class Wizard
{
    protected $wizardName = NULL;
    protected $steps = array();
    public abstract function hasRequiredAdminPermissions();
    public static function factory($wizardName)
    {
        $wizardClass = "\\WHMCS\\Admin\\Wizard\\" . $wizardName;
        if (!class_exists($wizardClass)) {
            throw new \WHMCS\Exception("Unrecognized wizard requested");
        }
        $wizard = new $wizardClass();
        if (!$wizard instanceof Wizard) {
            throw new \WHMCS\Exception("Requested wizard is invalid");
        }
        if (!$wizard->hasRequiredAdminPermissions()) {
            throw new \WHMCS\Exception\AccessDenied("Access Denied");
        }
        return $wizard;
    }
    public function getName()
    {
        return $this->wizardName;
    }
    protected function getSteps()
    {
        return $this->steps;
    }
    protected function getStepClass($name)
    {
        return "WHMCS\\Admin\\Wizard\\Steps\\" . $this->getName() . "\\" . $name;
    }
    public function render(\WHMCS\Smarty $smarty)
    {
        foreach ($this->getSteps() as $stepNum => $step) {
            $className = $this->getStepClass($step["name"]);
            $item = new $className();
            $templateVars = array();
            if (method_exists($item, "getTemplateVariables")) {
                $templateVars = $item->getTemplateVariables();
            }
            foreach ($templateVars as $key => $value) {
                $smarty->assign($key, $value);
            }
            $this->steps[$stepNum]["output"] = $smarty->fetch("string:" . $item->getStepContent());
        }
        return $this->getWizardWrap();
    }
    public function handleSubmit($step, $action, $data)
    {
        $steps = $this->getSteps();
        if (!is_array($steps[$step])) {
            throw new \WHMCS\Exception("Invalid step requested");
        }
        $className = $this->getStepClass($steps[$step]["name"]);
        $item = new $className();
        if (method_exists($item, $action)) {
            $response = $item->{$action}($data);
        }
        $postSaveEvent = array_key_exists("postSaveEvent", $steps[$step]) ? $steps[$step]["postSaveEvent"] : null;
        if (is_callable($postSaveEvent)) {
            $postSaveEvent();
        }
        return $response;
    }
    protected function getStepLabels()
    {
        $steps = $this->getSteps();
        foreach ($steps as $stepNum => $step) {
            if (array_key_exists("hidden", $step) && $step["hidden"]) {
                unset($steps[$stepNum]);
            }
        }
        return $steps;
    }
    protected function getWizardWrap()
    {
        $output = "\n    <div class=\"wizard-sidebar\">\n        <ul>";
        foreach ($this->getStepLabels() as $stepNum => $step) {
            $output .= "<li class=\"" . ($stepNum == 0 ? "current" : "") . "\" id=\"wizardStepLabel" . $stepNum . "\">\n    <div>\n        <span class=\"number\">\n            <i class=\"far fa-check-circle\"></i>\n        </span>\n        <span class=\"desc\">\n            <label>" . $step["stepName"] . "</label>\n            <span>" . $step["stepDescription"] . "</span>\n        </span>\n    </div>\n</li>";
        }
        $output .= "\n        </ul>\n    </div>\n    <div class=\"wizard-content\">\n\n        <form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "\" enctype=\"multipart/form-data\" id=\"frmWizardContent\">\n";
        foreach ($this->getSteps() as $stepNum => $step) {
            $output .= "<div id=\"wizardStep" . $stepNum . "\" class=\"wizard-step\" data-step-number=\"" . $stepNum . "\">" . $step["output"] . "</div>";
        }
        $output .= "\n            <input type=\"hidden\" name=\"wizard\" value=\"" . $this->getName() . "\">\n            <input type=\"hidden\" name=\"step\" value=\"0\" id=\"inputWizardStep\">\n            <input type=\"hidden\" name=\"token\" value=\"" . generate_token("plain") . "\">\n        </form>\n\n    </div>\n\n<style>\n";
        if (count($this->getStepLabels()) == 0) {
            $output .= ".wizard-sidebar { display: none; } .wizard-content { margin-left: 0 }";
        }
        $output .= "\n</style>\n<script type=\"text/javascript\">\n    jQuery('.modal-wizard input').iCheck({\n        inheritID: true,\n        checkboxClass: 'icheckbox_flat-blue',\n        radioClass: 'iradio_flat-blue',\n        increaseArea: '20%'\n    });\n    jQuery('#inputCountry').on('change', function() {\n        var selectedCountry = jQuery(this).val();\n        if (jQuery.inArray(selectedCountry, [\"US\"]) < 0) {\n            jQuery('#wizardCreditCardSignup,#wizardCreditCardEnable').addClass('hidden');\n        } else {\n            jQuery('#wizardCreditCardSignup,#wizardCreditCardEnable').removeClass('hidden');\n            jQuery('#ccSignupCountry').find('option[value=\"' + selectedCountry + '\"]').prop('selected', true);\n        }\n    });\n</script>\n";
        return $output;
    }
}

?>