<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Edit Clients Domains");
$aInt->title = $aInt->lang("domains", "modifycontact");
$aInt->sidebar = "clients";
$aInt->icon = "clientsprofile";
$aInt->requiredFiles(array("clientfunctions", "registrarfunctions"));
ob_start();
$domains = new WHMCS\Domains();
$country = new WHMCS\Utility\Country();
$domain_data = $domains->getDomainsDatabyID($whmcs->get_req_var("domainid"));
$domainid = $domain_data["id"];
if (!$domainid) {
    $aInt->gracefulExit("Domain ID Not Found");
}
$userid = $domain_data["userid"];
$aInt->valUserID($userid);
$domain = $domain_data["domain"];
$registrar = $domain_data["registrar"];
$registrationperiod = $domain_data["registrationperiod"];
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $reDirVars = array();
    $reDirVars["domainid"] = $domainid;
    try {
        $result = $domains->saveContactDetails(new WHMCS\Client($userid), App::getFromRequest("contactdetails") ?: array(), App::getFromRequest("wc"), App::getFromRequest("sel"));
        $reDirVars["success"] = true;
        $reDirVars["pending"] = false;
        if ($result["status"] == "pending") {
            $reDirVars["pending"] = true;
            $reDirVars["success"] = false;
        }
    } catch (Exception $e) {
        $reDirVars["error"] = true;
        WHMCS\Cookie::set("contactEditError", $e->getMessage());
    }
    redir($reDirVars);
}
if (App::getFromRequest("success") == 1) {
    infoBox($aInt->lang("domains", "modifySuccess"), $aInt->lang("domains", "changesuccess"), "success");
} else {
    if (App::getFromRequest("error") == 1) {
        $editError = WHMCS\Input\Sanitize::makeSafeForOutput(WHMCS\Cookie::get("contactEditError"));
        if ($editError) {
            infoBox($aInt->lang("domains", "registrarerror"), $editError, "error");
        }
        WHMCS\Cookie::delete("contactEditError");
    }
}
$success = $domains->moduleCall("GetContactDetails");
$alert = "";
$additionalData = NULL;
$domainInformation = NULL;
if ($success) {
    $contactdetails = $domains->getModuleReturn();
    try {
        $domainInformation = $domains->getDomainInformation();
    } catch (Exception $e) {
    }
    if ($domainInformation instanceof WHMCS\Domain\Registrar\Domain && !App::isInRequest("pending") && $domainInformation->isIrtpEnabled() && $domainInformation->isContactChangePending()) {
        $title = "domains.contactChangePending";
        $description = "domains.contactsChanged";
        $type = "info";
        if ($domainInformation->getPendingSuspension()) {
            $title = "domains.verificationRequired";
            $description = "domains.newRegistration";
            $type = "warning";
        }
        $title = AdminLang::trans($title);
        $description = AdminLang::trans($description);
        $alert = WHMCS\View\Helper::alert("<strong>" . $title . "</strong><br>" . $description, $type);
    }
} else {
    infoBox($aInt->lang("domains", "registrarerror"), $domains->getLastError());
}
if (App::getFromRequest("pending") == 1 && $domainInformation instanceof WHMCS\Domain\Registrar\Domain) {
    $message = "domains.changePending";
    $replacement = array(":email" => $domainInformation->getRegistrantEmailAddress());
    if ($domainInformation->getDomainContactChangeExpiryDate()) {
        $message = "domains.changePendingDate";
        $replacement[":days"] = $domainInformation->getDomainContactChangeExpiryDate()->diffInDays();
    }
    infoBox(AdminLang::trans("domains.modifyPending"), AdminLang::trans($message, $replacement));
}
$jsCode = "var allowSubmit = 0;\nfunction usedefaultwhois(id) {\n    jQuery(\".\" + id.substr(0, id.length - 1) + \"customwhois\").attr(\"disabled\", true);\n    jQuery(\".\" + id.substr(0, id.length - 1) + \"defaultwhois\").attr(\"disabled\", false);\n    jQuery('#' + id.substr(0, id.length - 1) + '1').attr(\"checked\", \"checked\");\n}\nfunction usecustomwhois(id) {\n    jQuery(\".\" + id.substr(0, id.length - 1) + \"customwhois\").attr(\"disabled\", false);\n    jQuery(\".\" + id.substr(0, id.length - 1) + \"defaultwhois\").attr(\"disabled\", true);\n    jQuery('#' + id.substr(0, id.length - 1) + '2').attr(\"checked\", \"checked\");\n}\nfunction irtpSubmit()\n{\n    allowSubmit = true;\n    var optOut = 0,\n        optOutCheckbox = jQuery('#modalIrtpOptOut'),\n        optOutReason = jQuery('#modalReason'),\n        formOptOut = jQuery('#irtpOptOut'),\n        formOptOutReason = jQuery('#irtpOptOutReason');\n    \n    if (optOutCheckbox.is(':checked')) {\n        optOut = 1;\n    }\n    formOptOut.val(optOut);\n    formOptOutReason.val(optOutReason.val());\n    jQuery('#frmDomainContactModification').submit();\n}";
$jQueryCode = "jQuery('#frmDomainContactModification').on('submit', function(){\n    if (!allowSubmit) {\n        var changed = false;\n        jQuery('.irtp-field').each(function() {\n            var value = jQuery(this).val(),\n                originalValue = jQuery(this).data('original-value');\n            if (value !== originalValue) {\n                changed = true;\n            }\n        });\n        if (changed) {\n            jQuery('#modalIRTPConfirmation').modal('show');\n            return false;\n        }\n    }\n    return true;\n});";
$formAction = App::getPhpSelf() . "?domainid=" . $domainid . "&action=save";
echo "<form id=\"frmDomainContactModification\" method=\"post\" action=\"";
echo $formAction;
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "registrar");
echo "</td>\n        <td class=\"fieldarea\">";
echo ucfirst($registrar);
echo "</td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td>\n        <td class=\"fieldarea\">";
echo $domain;
echo "</td>\n    </tr>\n</table>\n\n";
echo $alert;
echo $infobox;
$irtpFields = array();
$modal = "";
if ($success) {
    $contactsarray = array();
    $result = select_query("tblcontacts", "id,firstname,lastname", array("userid" => $userid, "address1" => array("sqltype" => "NEQ", "value" => "")), "firstname` ASC,`lastname", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $contactsarray[] = array("id" => $data["id"], "name" => $data["firstname"] . " " . $data["lastname"]);
    }
    $cols = count($contactdetails) == 3 ? "4" : "6";
    if ($domainInformation && $domainInformation->isIrtpEnabled()) {
        $irtpFields = $domainInformation->getIrtpVerificationTriggerFields();
        $modal = $aInt->modal("IRTPConfirmation", AdminLang::trans("domains.importantReminder"), "<div class=\"col-sm-12 text-center\">\n    <div class=\"row\">\n        <div class=\"col-sm-10 col-sm-offset-1\">\n            " . AdminLang::trans("domains.irtpNotice") . "\n        </div>\n        <div class=\"col-sm-12 text-center\">\n            <div class=\"checkbox-inline\">\n                <label for=\"modalIrtpOptOut\">\n                    <input id=\"modalIrtpOptOut\" class=\"checkbox\" type=\"checkbox\" value=\"1\">\n                    " . AdminLang::trans("domains.optOut") . "\n                </label>\n            </div>\n        </div>\n        <div class=\"col-sm-12 text-center\">\n            <div class=\"row\">\n                <div class=\"col-sm-12 text-left\">\n                    <label for=\"modalReason\">" . AdminLang::trans("domains.optOutReason") . "</label>:\n                </div>\n                <div class=\"col-sm-12 text-center\">\n                    <input id=\"modalReason\" type=\"text\" class=\"form-control input-600\" autocomplete=\"off\">\n                </div>\n            </div>\n        </div>\n    </div>\n</div>", array(array("title" => AdminLang::trans("global.submit"), "onclick" => "irtpSubmit();return false;", "class" => "btn-primary"), array("title" => AdminLang::trans("global.cancel"))));
    }
    echo "\n<div class=\"row\">\n    ";
    foreach ($contactdetails as $contactdetail => $values) {
        echo "        <div class=\"col-sm-6 col-lg-";
        echo $cols;
        echo "\">\n\n            <h2>";
        echo $contactdetail;
        echo "</h2></p>\n\n            <p>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"wc[";
        echo $contactdetail;
        echo "]\" id=\"";
        echo $contactdetail;
        echo "1\" value=\"contact\" onclick=\"usedefaultwhois(id)\" />\n                    ";
        echo $aInt->lang("domains", "domaincontactusexisting");
        echo "                </label>\n            </p>\n\n            <p style=\"padding-left:30px;\">\n                ";
        echo $aInt->lang("domains", "domaincontactchoose");
        echo "                <select name=\"sel[";
        echo $contactdetail;
        echo "]\" id=\"";
        echo $contactdetail;
        echo "3\" class=\"";
        echo $contactdetail;
        echo "defaultwhois form-control select-inline input-300\" onclick=\"usedefaultwhois(id)\">\n                    <option value=\"u";
        echo $userid;
        echo "\">";
        echo $aInt->lang("domains", "domaincontactprimary");
        echo "</option>\n                    ";
        foreach ($contactsarray as $subcontactsarray) {
            echo "                    <option value=\"c";
            echo $subcontactsarray["id"];
            echo "\">";
            echo $subcontactsarray["name"];
            echo "</option>\n                    ";
        }
        echo "                </select>\n            </p>\n\n            <p>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"wc[";
        echo $contactdetail;
        echo "]\" id=\"";
        echo $contactdetail;
        echo "2\" value=\"custom\" onclick=\"usecustomwhois(id)\" checked />\n                    ";
        echo $aInt->lang("domains", "domaincontactusecustom");
        echo "                </label>\n            </p>\n\n            <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"";
        echo $contactdetail;
        echo "customwhois\">\n                ";
        foreach ($values as $name => $value) {
            echo "                    <tr>\n                        <td width=\"20%\" class=\"fieldlabel\">";
            echo $name;
            echo "</td>\n                        <td class=\"fieldarea\">\n                            ";
            $textFieldInput = true;
            if ($name == "Country") {
                if (!$value) {
                    $value = WHMCS\Config\Setting::getValue("DefaultCountry");
                    $countries = $country->getCountryNameArray();
                    $textFieldInput = false;
                } else {
                    if ($country->isValidCountryCode($value)) {
                        $countries = $country->getCountryNameArray();
                        $textFieldInput = false;
                    } else {
                        if ($country->isValidCountryName($value)) {
                            $countries = $country->getCountryNamesOnly();
                            $textFieldInput = false;
                        } else {
                            $textFieldInput = true;
                        }
                    }
                }
                if (!$textFieldInput) {
                    echo "<select name=\"contactdetails[" . $contactdetail . "][" . $name . "]\" class=\"" . $contactdetail . "customwhois form-control\">";
                    foreach ($countries as $k => $v) {
                        echo "<option value=\"" . $k . "\"" . ($k == $value ? " selected" : "") . ">" . $v . "</option>";
                    }
                    echo "</select>";
                }
            }
            if ($textFieldInput) {
                $additionalData = "";
                $classes = array($contactdetail . "customwhois", "form-control", "input-300");
                if (array_key_exists($contactdetail, $irtpFields) && in_array($name, $irtpFields[$contactdetail])) {
                    $additionalData = "data-original-value=\"" . $value . "\"";
                    $classes[] = "irtp-field";
                }
                $type = "type=\"text\"";
                $fieldName = "name=\"contactdetails[" . $contactdetail . "][" . $name . "]\"";
                $value = "value=\"" . WHMCS\Input\Sanitize::encode($value) . "\"";
                $class = "class=\"" . implode(" ", $classes) . "\"";
                echo "<input " . $type . " " . $fieldName . " " . $value . " " . $class . " " . $additionalData . ">";
            }
            echo "                        </td>\n                    </tr>\n                ";
        }
        echo "            </table>\n\n        </div>\n    ";
    }
    if ($domainInformation && $domainInformation->isIrtpEnabled()) {
        echo "        <input id=\"irtpOptOut\" type=\"hidden\" name=\"irtpOptOut\" value=\"0\">\n        <input id=\"irtpOptOutReason\" type=\"hidden\" name=\"irtpOptOutReason\" value=\"\">\n    ";
    }
    echo "</div>\n";
}
echo "\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"button btn btn-primary\">\n        <a href=\"clientsdomains.php?userid=";
echo $userid;
echo "&domainid=";
echo $domainid;
echo "\" class=\"button btn btn-default\">";
echo $aInt->lang("global", "goback");
echo "</a>\n    </div>\n\n</form>\n\n";
echo $modal;
$content = ob_get_contents();
ob_end_clean();
$aInt->jscode .= $jsCode;
$aInt->addHeadJqueryCode($jQueryCode);
$aInt->content = $content;
$aInt->display();

?>