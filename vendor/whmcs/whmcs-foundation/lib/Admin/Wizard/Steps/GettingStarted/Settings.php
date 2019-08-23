<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class Settings
{
    public function getTemplateVariables()
    {
        $license = \DI::make("license");
        $companyName = \WHMCS\Config\Setting::getValue("CompanyName");
        if (in_array($companyName, array("Company Name", ""))) {
            $companyName = $license->getKeyData("reseller") ? "" : $license->getRegisteredName();
        }
        if (\App::isInRequest("CompanyName")) {
            $companyName = \App::getFromRequest("CompanyName");
        }
        $vars = array();
        $vars["CompanyName"] = $companyName;
        $vars["Email"] = \WHMCS\Config\Setting::getValue("Email");
        $vars["Address"] = \WHMCS\Config\Setting::getValue("InvoicePayTo");
        $vars["DefaultCountry"] = \WHMCS\Config\Setting::getValue("DefaultCountry");
        $vars["Language"] = \WHMCS\Language\ClientLanguage::getValidLanguageName(\WHMCS\Config\Setting::getValue("Language"));
        $vars["AvailableLanguages"] = \WHMCS\Language\ClientLanguage::getLanguages();
        $country = new \WHMCS\Utility\Country();
        $vars["AvailableCountries"] = $country->getCountryNameArray();
        $vars["allowedCcSignupCountries"] = array("US");
        return $vars;
    }
    public function getStepContent()
    {
        return "<div class=\"alert alert-info info-alert\">{lang key=\"wizard.settingsIntro\"}</div>\n\n<div class=\"form-horizontal\">\n    <div class=\"form-group\">\n        <label for=\"inputCompanyName\" class=\"col-sm-3 control-label\">{lang key=\"fields.companyname\"}</label>\n        <div class=\"col-sm-9\">\n            <input id=\"inputCompanyName\" type=\"text\" name=\"CompanyName\" class=\"form-control\" value=\"{\$CompanyName}\">\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputLogo\" class=\"col-sm-3 control-label\">{lang key=\"fields.logo\"}</label>\n        <div class=\"col-sm-9\">\n            <input type=\"file\" id=\"inputLogo\" name=\"Logo\" class=\"form-control\" accept=\"image/*\">\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputEmail\" class=\"col-sm-3 control-label\">{lang key=\"fields.email\"}</label>\n        <div class=\"col-sm-9\">\n            <input id=\"inputEmail\" type=\"email\" name=\"Email\" class=\"form-control\" value=\"{\$Email}\">\n            <p class=\"help-block\">{lang key=\"wizard.settingsEmailDescription\"}</p>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputAddress\" class=\"col-sm-3 control-label\">{lang key=\"fields.address\"}</label>\n        <div class=\"col-sm-9\">\n            <textarea id=\"inputAddress\" name=\"Address\" class=\"form-control\" style=\"height:68px;\">{\$Address}</textarea>\n            <p class=\"help-block\">{lang key=\"wizard.settingsAddressDescription\"}</p>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputCountry\" class=\"col-sm-3 control-label\">{lang key=\"fields.country\"}</label>\n        <div class=\"col-sm-9\">\n            <select name=\"Country\" id=\"inputCountry\" class=\"form-control select-inline\">\n                {foreach \$AvailableCountries as \$code => \$country}\n                    <option value=\"{\$code}\"{if \$code == \$DefaultCountry} selected=\"selected\"{/if}>{\$country}</option>\n                {/foreach}\n            </select>\n            <p class=\"help-block\">{lang key=\"wizard.settingsCountryDescription\"}</p>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputLanguage\" class=\"col-sm-3 control-label\">{lang key=\"fields.language\"}</label>\n        <div class=\"col-sm-9\">\n            <select name=\"Language\" id=\"inputLanguage\" class=\"form-control select-inline\">\n                {foreach \$AvailableLanguages as \$discard => \$language}\n                    <option value=\"{\$language}\"{if \$language == \$Language} selected=\"selected\"{/if}>{\$language|ucfirst}</option>\n                {/foreach}\n            </select>\n            <p class=\"help-block\">{lang key=\"wizard.settingsLanguageDescription\"}</p>\n        </div>\n    </div>\n</div>\n\n<script type=\"text/javascript\">\n    jQuery(document).ready(function(){\n        jQuery('.company').val(jQuery('#fieldCompanyName').val());\n    });\n    jQuery('#fieldCompanyName').keydown(function() {\n        jQuery('.company').val(jQuery(this).val());\n    });\n    jQuery('#inputCountry').change(function() {\n        var allowedCountries = [{foreach \$allowedCcSignupCountries as \$country}'{\$country}'{if !\$country@last},{/if}{/foreach}],\n            isAllowed = jQuery.inArray(jQuery(this).val(), allowedCountries);\n            \n        if (isAllowed < 0) {\n            jQuery('.wizard-content').find('.credit-card').addClass('hidden');\n            jQuery('#checkboxCreditCardEnable').iCheck('uncheck');\n        } else {\n            jQuery('.wizard-content').find('credit-card').removeClass('hidden');\n            jQuery('#checkboxCreditCardEnable').iCheck('check');\n        }\n    });\n</script>";
    }
    public function save($data)
    {
        $companyName = isset($data["CompanyName"]) ? trim($data["CompanyName"]) : "";
        $email = isset($data["Email"]) ? trim($data["Email"]) : "";
        $address = isset($data["Address"]) ? trim($data["Address"]) : "";
        $country = isset($data["Country"]) ? trim($data["Country"]) : "";
        $language = isset($data["Language"]) ? trim($data["Language"]) : "";
        if (!$companyName) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldCompanyName"));
        }
        if (!$email) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldEmail"));
        }
        if (!$address) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldAddress"));
        }
        if (!$country) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldCountry"));
        }
        if (!$language) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldLanguage"));
        }
        $logoUrl = "";
        $logoUploaded = false;
        if (isset($_FILES["Logo"]["error"])) {
            $logoUploaded = $_FILES["Logo"]["error"] === UPLOAD_ERR_OK;
        }
        if ($logoUploaded) {
            try {
                $file = new \WHMCS\File\Upload("Logo");
                $fileExtension = strtolower($file->getExtension());
                $logoFileValidationFunctions = array(".png" => "imagecreatefrompng", ".jpg" => "imagecreatefromjpeg");
                $logoFileVerified = false;
                if (isset($logoFileValidationFunctions[$fileExtension])) {
                    $validationFunction = $logoFileValidationFunctions[$fileExtension];
                    if (function_exists($validationFunction)) {
                        $hImage = $validationFunction($file->getFileTmpName());
                        $logoFileVerified = is_resource($hImage);
                        imagedestroy($hImage);
                    }
                }
                if ($logoFileVerified) {
                    $logoUrl = "assets/img/logo" . $fileExtension;
                    $fileContents = $file->contents();
                    $logoFile = ROOTDIR . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "logo" . $fileExtension;
                    $file = new \WHMCS\File($logoFile);
                    $file->create($fileContents);
                } else {
                    throw new \WHMCS\Exception(\AdminLang::trans("wizard.invalidLogoImage"));
                }
            } catch (\WHMCS\Exception\File\NotCreated $e) {
            }
        }
        $generalSettings = new \WHMCS\Admin\Setup\GeneralSettings();
        $generalSettings->autoSetInitialConfiguration($companyName, $email, $address, $country, $language, $logoUrl);
    }
}

?>