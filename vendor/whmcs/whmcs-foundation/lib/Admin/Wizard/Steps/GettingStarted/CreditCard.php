<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class CreditCard
{
    const API_URL = "https://www.whmcs.com/api/merchant_gateway_signup.php";
    const API_SECRET_KEY = "561fd73b5a43e453444004a543f0b4731c05cd1e";
    public function getStepContent()
    {
        return "\n<div class=\"clearfix\">\n    <div style=\"float:left;\"><img src=\"{\$BASE_PATH_IMG}/wizard/creditcard.png\" alt=\"{lang key=\"wizard.creditCard\"}\"></div>\n    <div style=\"float:left;padding:20px;width:390px;\">{lang key=\"wizard.creditCardSignup\"}</div>\n</div>\n\n<p>{lang key=\"wizard.creditCardSignupIntro\"}</p>\n\n<div class=\"signup-frm\">\n    <div class=\"alert alert-warning info-alert\">{lang key=\"wizard.creditCardSignupContact\"}</div>\n    \n    <div class=\"row bottom-margin-5\">\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"name\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderYourName\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"email\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderEmail\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"address\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderAddress\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"city\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderCity\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"state\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderState\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"postcode\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderPostcode\"}\" />\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <select id=\"ccSignupCountry\" name=\"country\" class=\"form-control\">\n                <option value=\"US\">United States</option>\n            </select>\n        </div>\n        <div class=\"col-sm-6 bottom-margin-5\">\n            <input type=\"text\" name=\"phone\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderPhoneNumber\"}\" />\n        </div>\n    </div>\n    \n    <p class=\"small\">{lang key=\"wizard.creditCardAgreeInfoSharing\"}</p>\n</div>\n\n<div class=\"signup-frm-success hidden\">\n    <div class=\"row\">\n        <div class=\"col-sm-10 col-sm-offset-1\">\n            <div class=\"alert alert-success text-center\">\n                <h2><i class=\"fas fa-check\"></i> {lang key=\"wizard.creditCardApplicationStarted\"}</h2>\n                <p>\n                    {lang key=\"wizard.creditCardApplicationNextSteps\"}\n                </p>\n            </div>\n        </div>\n    </div>\n</div>\n";
    }
    public function save($data)
    {
        $name = isset($data["name"]) ? trim($data["name"]) : "";
        $email = isset($data["email"]) ? trim($data["email"]) : "";
        $address = isset($data["address"]) ? trim($data["address"]) : "";
        $city = isset($data["city"]) ? trim($data["city"]) : "";
        $state = isset($data["state"]) ? trim($data["state"]) : "";
        $postcode = isset($data["postcode"]) ? trim($data["postcode"]) : "";
        $country = isset($data["country"]) ? trim($data["country"]) : "";
        $phone = isset($data["phone"]) ? trim($data["phone"]) : "";
        if (!$name) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldYourName"));
        }
        if (!$email) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldEmail"));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.emailFailedValidation"));
        }
        if (!$address) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldAddress"));
        }
        if (!$city) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldCity"));
        }
        if (!$state) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldState"));
        }
        if (!$postcode) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldPostcode"));
        }
        if (!$country) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldCountry"));
        }
        if (!$phone) {
            throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldPhoneNumber"));
        }
        $postfields = array("name" => $name, "companyname" => \WHMCS\Config\Setting::getValue("CompanyName"), "email" => $email, "address" => $address, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phone" => $phone, "checksum" => sha1(self::API_SECRET_KEY . md5($name) . md5($email)));
        $response = curlCall(self::API_URL, $postfields);
        $data = json_decode($response, true);
        if (is_null($data)) {
        } else {
            if (!$data["success"]) {
                throw new \WHMCS\Exception($data["errorMsg"]);
            }
        }
    }
}

?>