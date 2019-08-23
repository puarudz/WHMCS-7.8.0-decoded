<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<h3>Settings</h3>\n\n";
if ($saveSuccess) {
    echo "    <div class=\"alert alert-success\">\n        Settings updated successfully!\n    </div>\n";
} else {
    if ($errorMsg) {
        echo "    <div class=\"alert alert-danger\">\n        ";
        echo $errorMsg;
        echo "    </div>\n";
    }
}
echo "\n<div class=\"form-group\">\n    <label for=\"inputApiKey\">API Integration Key</label>\n    <input type=\"text\" name=\"api_key\" class=\"form-control\" id=\"inputApiKey\" placeholder=\"Enter to change\">\n    <p class=\"help-block\">Navigate to Account > Extras > API Keys to create one.</p>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputConnectedList\">Connected List</label>\n    <input type=\"text\" class=\"form-control\" id=\"inputConnectedList\" value=\"";
echo $connectedListName;
echo "\" disabled=\"disabled\">\n    <p class=\"help-block\">To change the mailing list, you must disconnect and re-connect so an e-commerce integration can be established for the new list.</p>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputUserOptIn\">";
echo AdminLang::trans("general.marketingEmailsRequireOptIn");
echo "</label>\n    <br>\n    <label class=\"radio-inline\">\n        <input type=\"radio\" name=\"require_user_optin\" value=\"1\"";
if ($requireUserOptIn) {
    echo " checked";
}
echo ">\n        ";
echo AdminLang::trans("general.marketingEmailsRequireOptInEnabled");
echo "    </label>\n    <br>\n    <label class=\"radio-inline\">\n        <input type=\"radio\" name=\"require_user_optin\" value=\"0\"";
if (!$requireUserOptIn) {
    echo " checked";
}
echo ">\n        ";
echo AdminLang::trans("general.marketingEmailsRequireOptInDisabled");
echo "    </label>\n</div>\n\n<div class=\"form-group\">\n    <label for=\"inputOptInAgreementMsg\">";
echo AdminLang::trans("general.marketingEmailsOptInMessaging");
echo "</label>\n    <textarea rows=\"2\" name=\"optin_agreement_msg\" class=\"form-control\" id=\"inputOptInAgreementMsg\">";
echo $optInAgreementMsg;
echo "</textarea>\n    <p class=\"help-block\">This message will be displayed to users during registration &amp; checkout.</p>\n</div>\n\n<p>\n    <button type=\"submit\" class=\"btn btn-primary\">\n        Save Changes\n    </button>\n</p>\n\n<input type=\"hidden\" name=\"action\" value=\"savesettings\">\n";

?>