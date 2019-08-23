<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Wizard\Steps\ConfigureSsl;

class Complete
{
    public function getStepContent()
    {
        return "<div class=\"wizard-transition-step\">\n    <div class=\"icon\"><i class=\"fas fa-check\"></i></div>\n    <div class=\"title\">Configuration Complete</div>\n    <div class=\"tag\">The certificate information has been submitted successfully!</div>\n    <div class=\"greyout cert-further-instructions\">You will receive an email with further instructions from the SSL Issuer.</div>\n    <div class=\"cert-file-auth hidden\">\n        <div class=\"greyout\" style=\"margin-top:-5px;\">As you selected File Based Authentication, you must create the following file:</div>\n        <div class=\"input-group\" style=\"margin-top:2px;\">\n            <span class=\"input-group-addon\">Required Filename</span>\n            <input type=\"text\" class=\"form-control cert-file-auth-filename\">\n        </div>\n        <div class=\"input-group\" style=\"margin-top:2px;\">\n            <span class=\"input-group-addon\">Required Contents</span>\n            <input type=\"text\" class=\"form-control cert-file-auth-contents\">\n        </div>\n    </div>\n</div>";
    }
}

?>