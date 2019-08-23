<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\GettingStarted;

class Enom
{
    const API_URL = "https://www.whmcs.com/api/enom_create_acct.php";
    const API_SECRET_KEY = "10ef2c21b3c3311fca310f65af97cd1308469396";
    public function getTemplateVariables()
    {
        $vars = array();
        $assetHelper = \DI::make("asset");
        $vars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
        return $vars;
    }
    public function getStepContent()
    {
        return "\n<div class=\"alert alert-info info-alert\">{lang key=\"wizard.enomRecommended\"}</div>\n\n<div class=\"clearfix\">\n    <div style=\"float:left;margin-top:-14px;\"><img src=\"{\$BASE_PATH_IMG}/wizard/enom.png\" alt=\"{lang key=\"wizard.registrarEnom\"}\"></div>\n    <div style=\"float:left;padding:6px 20px;width:390px;\">{lang key=\"wizard.registrarEnomCreateAccountDescription\"}</div>\n</div>\n\n<div id=\"enomSignupContainer\">\n\n    <div class=\"signup-frm\">\n        <div class=\"row\">\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"name\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderYourName\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"email\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderEmail\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"address\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderAddress\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"city\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderCity\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"state\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderState\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"postcode\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderPostcode\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"country\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderCountry\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"phone\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderPhoneNumber\"}\" />\n            </div>\n        </div>\n    \n        <p>{lang key=\"wizard.enomCredentials\"}</p>\n    \n        <div class=\"row\">\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"text\" name=\"newusername\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderUsername\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"password\" name=\"newpassword\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderPassword\"}\" />\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <select name=\"securityq\" class=\"form-control\">\n                    <option value=\"\">{lang key=\"wizard.enomSecurityQuestionSelectOne\"}</option>\n                    <option value=\"fteach\">{lang key=\"wizard.enomSecurityQuestionFavoriteTeacher\"}</option>\n                    <option value=\"fvspot\">{lang key=\"wizard.enomSecurityQuestionFavoriteVacationSpot\"}</option>\n                    <option value=\"fpet\">{lang key=\"wizard.enomSecurityQuestionFavoritePet\"}</option>\n                    <option value=\"fmovie\">{lang key=\"wizard.enomSecurityQuestionFavoriteMovie\"}</option>\n                    <option value=\"fbook\">{lang key=\"wizard.enomSecurityQuestionFavoriteBook\"}</option>\n                </select>\n            </div>\n            <div class=\"col-sm-6 bottom-margin-5\">\n                <input type=\"password\" name=\"securitya\" class=\"form-control\" placeholder=\"{lang key=\"wizard.placeholderSecurityQuestionAnswer\"}\" />\n            </div>\n        </div>\n    \n        <div style=\"margin:10px 0 0 0;\">{lang key=\"wizard.enomAlreadyHaveAccount\"} <a href=\"#\" class=\"enomUseExistingAcct\">{lang key=\"wizard.loginUsingExistingAccount\"}</a></div> \n    </div>\n    \n    <div class=\"signup-frm-success hidden\">\n        <div class=\"row\">\n            <div class=\"col-sm-10 col-sm-offset-1\">\n                <div class=\"alert alert-success text-center\">\n                    <h2><i class=\"fas fa-check\"></i> {lang key=\"wizard.enomAccountCreated\"}</h2>\n                    <p>\n                        {lang key=\"wizard.enomAccountManagementUrl\"}\n                    </p>\n                </div>\n            </div>\n        </div>\n    </div>\n    \n</div>\n\n<div id=\"enomLoginContainer\" class=\"hidden\">\n\n    <div style=\"margin:10px 0 0 0;\">\n        {lang key=\"wizard.dontHaveAnEnomAccount\"} <a href=\"#\" class=\"enomCreateAcct\">{lang key=\"wizard.createNewOneNow\"}</a>\n    </div>\n\n    <br>\n\n    <div class=\"row\">\n        <div class=\"col-sm-10 col-sm-offset-1\">\n            <div class=\"form-horizontal\">\n                <div class=\"form-group\">\n                    <label for=\"inputUsername\" class=\"col-sm-4 control-label\">{lang key=\"wizard.enomApiUsername\"}</label>\n                    <div class=\"col-sm-8\">\n                        <input type=\"text\" name=\"username\" class=\"form-control\" id=\"inputUsername\" placeholder=\"{lang key=\"wizard.enomApiUsername\"}\">\n                    </div>\n                </div>\n                <div class=\"form-group\">\n                    <label for=\"inputApiToken\" class=\"col-sm-4 control-label\">{lang key=\"wizard.enomApiToken\"}</label>\n                    <div class=\"col-sm-8\">\n                        <input type=\"password\" name=\"password\" class=\"form-control\" id=\"inputApiToken\" placeholder=\"{lang key=\"wizard.enomApiToken\"}\">\n                        <br>\n                        <div class=\"alert alert-warning info-alert\">\n                            Don't have an API Token? <a href=\"https://www.enom.com/apitokens\" target=\"_blank\">{lang key=\"wizard.enomCreateToken\"}</a>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n\n</div>\n\n<input type=\"hidden\" name=\"accttype\" id=\"inputEnomAccountType\" value=\"new\">\n\n<script>\n\$(document).ready(function() {\n    \$('.enomUseExistingAcct').click(function(e) {\n        e.preventDefault();\n        \$('#enomSignupContainer').slideUp('fast', function() {\n            \$('#enomLoginContainer').hide().removeClass('hidden').slideDown('fast');\n            \$('#inputEnomAccountType').val('existing');\n        });\n    });\n    \$('.enomCreateAcct').click(function(e) {\n        e.preventDefault();\n        \$('#enomLoginContainer').slideUp('fast', function() {\n            \$('#enomSignupContainer').slideDown('fast');\n            \$('#inputEnomAccountType').val('new');\n        });\n    });\n    \$('body').on('click', '.modal-setup-wizard .modal-submit', function() {\n        var accountType = \$('#inputEnomAccountType').val();\n            \n            if (accountType == 'new') {\n                var username = \$('input[name=\"newusername\"]').val(),\n                    password = \$('input[name=\"newpassword\"]').val();\n            } else {\n                var username = \$('#inputUsername').val(),\n                    password = \$('#inputApiToken').val();\n            }\n            if (username && password) {\n                \$('#enomEnabled').removeClass('hidden');\n            }\n        });\n});\n</script>";
    }
    public function save($data)
    {
        $accttype = isset($data["accttype"]) ? trim($data["accttype"]) : "";
        $name = isset($data["name"]) ? trim($data["name"]) : "";
        $email = isset($data["email"]) ? trim($data["email"]) : "";
        $address = isset($data["address"]) ? trim($data["address"]) : "";
        $city = isset($data["city"]) ? trim($data["city"]) : "";
        $state = isset($data["state"]) ? trim($data["state"]) : "";
        $postcode = isset($data["postcode"]) ? trim($data["postcode"]) : "";
        $country = isset($data["country"]) ? trim($data["country"]) : "";
        $phone = isset($data["phone"]) ? trim($data["phone"]) : "";
        $newusername = isset($data["newusername"]) ? trim($data["newusername"]) : "";
        $newpassword = isset($data["newpassword"]) ? trim($data["newpassword"]) : "";
        $securityq = isset($data["securityq"]) ? trim($data["securityq"]) : "";
        $securitya = isset($data["securitya"]) ? trim($data["securitya"]) : "";
        $username = isset($data["username"]) ? trim($data["username"]) : "";
        $password = isset($data["password"]) ? trim($data["password"]) : "";
        if ($accttype == "new") {
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
            if (!$newusername) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldUsername"));
            }
            if (!$newpassword) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldPassword"));
            }
            if (!$securityq) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldSecurityQuestion"));
            }
            if (!$securitya) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldSecurityQuestionAnswer"));
            }
            $postfields = array("name" => $name, "company" => \WHMCS\Config\Setting::getValue("CompanyName"), "email" => $email, "address1" => $address, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phone" => $phone, "username" => $newusername, "password1" => $newpassword, "password2" => $newpassword, "securityq" => $securityq, "securitya" => $securitya, "checksum" => sha1(self::API_SECRET_KEY . md5($name) . md5($email)));
            $response = curlCall(self::API_URL, $postfields);
            $data = json_decode($response, true);
            if (is_null($data)) {
            } else {
                if (!$data["success"]) {
                    throw new \WHMCS\Exception($data["errors"][0]);
                }
            }
            $enomUsername = $newusername;
            $enomPassword = $newpassword;
        } else {
            if (!$username) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldUsername"));
            }
            if (!$password) {
                throw new \WHMCS\Exception(\AdminLang::trans("wizard.requiredFieldPassword"));
            }
            $enomUsername = $username;
            $enomPassword = $password;
        }
        $registrar = new \WHMCS\Module\Registrar();
        $registrar->load("enom");
        $registrar->activate(array("Username" => $enomUsername, "Password" => $enomPassword));
    }
}

?>