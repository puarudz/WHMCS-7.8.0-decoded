<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class Payments
{
    public function getTemplateVariables()
    {
        $vars = array();
        $assetHelper = \DI::make("asset");
        $vars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
        return $vars;
    }
    public function getStepContent()
    {
        return "\n<div class=\"alert alert-warning info-alert\">{lang key=\"wizard.gatewaysIntro\"}</div>\n\n<div class=\"clearfix\">\n    <div style=\"float:left;\"><img src=\"{\$BASE_PATH_IMG}/wizard/paypal.png\" alt=\"{lang key=\"wizard.paypal\"}\"></div>\n    <div style=\"float:left;padding:20px;width:390px;\">{lang key=\"wizard.paypalDescription\"}</div>\n</div>\n\n<div class=\"row\">\n    <div class=\"col-sm-3 text-right\">\n        <label>\n            <input id=\"checkboxPayPalEnable\" type=\"checkbox\" name=\"PayPalEnable\" checked>\n            {lang key=\"wizard.enable\"}\n        </label>\n    </div>\n    <div class=\"col-sm-9\">\n        <input id=\"fieldPayPalEmailAddress\" type=\"email\" name=\"PayPalEmailAddress\" class=\"form-control\" placeholder=\"{lang key=\"wizard.paypalEnterEmail\"}\">\n    </div>\n</div>\n<div style=\"padding:15px 0 20px;font-size:0.9em;font-style:italic;\">\n    {lang key=\"wizard.paypalDontHaveAccount\"}\n</div>\n\n<div id=\"wizardCreditCardSignup\" class=\"credit-card clearfix{if !in_array(\$DefaultCountry, \$allowedCcSignupCountries)} hidden{/if}\">\n    <div style=\"float:left;\"><img src=\"{\$BASE_PATH_IMG}/wizard/creditcard.png\" alt=\"{lang key=\"wizard.creditCard\"}\"></div>\n    <div style=\"float:left;padding:20px;width:390px;\">{lang key=\"wizard.creditCardDescription\"}</div>\n</div>\n\n<div id=\"wizardCreditCardEnable\" class=\"row bottom-margin-5 credit-card{if !in_array(\$DefaultCountry, \$allowedCcSignupCountries)} hidden{/if}\">\n    <div class=\"col-sm-3 text-right\">\n        <label>\n            <input id=\"checkboxCreditCardEnable\" type=\"checkbox\" name=\"CreditCardEnable\" {if in_array(\$DefaultCountry, \$allowedCcSignupCountries)} checked{/if}>\n            {lang key=\"wizard.enable\"}\n        </label>\n    </div>\n    <div class=\"col-sm-9\">\n        {lang key=\"wizard.creditCardApply\"}\n    </div>\n</div>\n\n<div class=\"clearfix\" style=\"margin-top:22px;\">\n    <div style=\"float:left;\"><img src=\"{\$BASE_PATH_IMG}/wizard/mailin.png\" alt=\"{lang key=\"wizard.mailIn\"}\"></div>\n    <div style=\"float:left;padding:10px 20px;width:390px;\">\n        <label>\n            <input id=\"checkboxMailInEnable\" type=\"checkbox\" name=\"MailInEnable\" checked>\n            {lang key=\"wizard.enable\"}\n        </label>\n        <div style=\"display:inline-block;padding-left:25px;\">{lang key=\"wizard.mailInDescription\"}</div>\n    </div>\n</div>";
    }
    public function save($data)
    {
        $enablePayPal = isset($data["PayPalEnable"]) ? trim($data["PayPalEnable"]) : "";
        $paypalEmail = isset($data["PayPalEmailAddress"]) ? trim($data["PayPalEmailAddress"]) : "";
        $enableCreditCard = isset($data["CreditCardEnable"]) ? trim($data["CreditCardEnable"]) : "";
        $enableMailIn = isset($data["MailInEnable"]) ? trim($data["MailInEnable"]) : "";
        if ($enablePayPal) {
            if (!$paypalEmail) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.paypalMustProvideEmailAddress"));
            }
            if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.emailFailedValidation"));
            }
            try {
                $gateway = new \WHMCS\Module\Gateway();
                $gateway->load("paypal");
                $gateway->activate(array("email" => $paypalEmail));
            } catch (\WHMCS\Exception\Module\NotActivated $e) {
            }
        }
        if ($enableMailIn) {
            try {
                $gateway = new \WHMCS\Module\Gateway();
                $gateway->load("mailin");
                $gateway->activate();
            } catch (\WHMCS\Exception\Module\NotActivated $e) {
            }
        }
        if (!$enableCreditCard) {
            return array("skipNextStep" => true);
        }
    }
}

?>