<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Add New Client", false);
$aInt->title = $aInt->lang("clients", "addnew");
$aInt->sidebar = "clients";
$aInt->icon = "clientsadd";
$aInt->requiredFiles(array("clientfunctions", "customfieldfunctions", "gatewayfunctions"));
$allowSingleSignOn = $whmcs->isInRequest("token") ? (int) $whmcs->getFromRequest("allowsinglesignon") : 1;
$marketing_emails_opt_in = (int) App::getFromRequest("marketing_emails_opt_in");
$taxId = "";
if ($action == "add") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblclients", "COUNT(*)", array("email" => $email));
    $data = mysql_fetch_array($result);
    $taxId = App::getFromRequest("tax_id");
    if ($data[0]) {
        infoBox($aInt->lang("clients", "duplicateemail"), $aInt->lang("clients", "duplicateemailexp"), "error");
    } else {
        if (!trim($email) && !$cccheck) {
            infoBox($aInt->lang("global", "validationerror"), $aInt->lang("clients", "invalidemail"), "error");
        } else {
            if (!$cccheck && trim($email)) {
                $emaildomain = explode("@", $email, 2);
                $emaildomain = $emaildomain[1];
                $validate = new WHMCS\Validate();
                if (!$validate->validate("email", "email", "clientareaerroremailinvalid")) {
                    $errormessage .= $validate->getHTMLErrorOutput();
                    infoBox($aInt->lang("global", "validationerror"), $aInt->lang("clients", "invalidemail"), "error");
                } else {
                    $query = "subaccount=1 AND email='" . mysql_real_escape_string($email) . "'";
                    $result = select_query("tblcontacts", "COUNT(*)", $query);
                    $data = mysql_fetch_array($result);
                    if ($data[0]) {
                        infoBox($aInt->lang("clients", "duplicateemail"), $aInt->lang("clients", "duplicateemailexp"), "error");
                    }
                }
                if (!$infobox) {
                    $validate = new WHMCS\Validate();
                    run_validate_hook($validate, "ClientDetailsValidation", $_POST);
                    $errormessage = $validate->getErrors();
                    if (count($errormessage)) {
                        infoBox($aInt->lang("global", "validationerror"), implode("<br/>", $errormessage), "error");
                    }
                }
            }
            if (!$infobox) {
                $_SESSION["currency"] = $currency;
                $phonenumber = App::formatPostedPhoneNumber();
                $userid = addClient($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $password, $securityqid, $securityqans, $sendemail, array("notes" => $notes, "status" => $status, "taxexempt" => $taxexempt, "latefeeoveride" => $latefeeoveride, "overideduenotices" => $overideduenotices, "language" => $language, "billingcid" => $billingcid, "lastlogin" => "00000000000000", "groupid" => $groupid, "separateinvoices" => $separateinvoices, "disableautocc" => $disableautocc, "defaultgateway" => $paymentmethod, "emailoptout" => !$marketing_emails_opt_in, "overrideautoclose" => (int) $whmcs->get_req_var("overrideautoclose"), "allow_sso" => $allowSingleSignOn, "credit" => (double) $whmcs->get_req_var("credit"), "tax_id" => $taxId), "", true, $marketing_emails_opt_in);
                unset($_SESSION["uid"]);
                unset($_SESSION["upw"]);
                redir("userid=" . $userid, "clientssummary.php");
            }
        }
    }
}
WHMCS\Session::release();
ob_start();
$questions = getSecurityQuestions("");
echo $infobox;
echo "\n<form id=\"frmAddUser\" method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=add\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"17%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "firstname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"firstname\" value=\"";
echo $firstname;
echo "\" tabindex=\"1\"></td><td class=\"fieldlabel\" width=\"15%\">";
echo $aInt->lang("fields", "address1");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"address1\" value=\"";
echo $address1;
echo "\" tabindex=\"8\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "lastname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"lastname\" value=\"";
echo $lastname;
echo "\" tabindex=\"2\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "address2");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250 input-inline\" name=\"address2\" value=\"";
echo $address2;
echo "\" tabindex=\"9\"> <font color=#cccccc><small>(";
echo $aInt->lang("global", "optional");
echo ")</small></font></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "companyname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250 input-inline\" name=\"companyname\" value=\"";
echo $companyname;
echo "\" tabindex=\"3\"> <font color=#cccccc><small>(";
echo $aInt->lang("global", "optional");
echo ")</small></font></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "city");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"city\" value=\"";
echo $city;
echo "\" tabindex=\"10\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "email");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-300\" name=\"email\" value=\"";
echo $email;
echo "\" tabindex=\"4\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "state");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"state\" data-selectinlinedropdown=\"1\" value=\"";
echo $state;
echo "\" tabindex=\"11\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "password");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-150\" name=\"password\" autocomplete=\"off\" value=\"";
echo $password;
echo "\" tabindex=\"5\" /></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "postcode");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-150\" name=\"postcode\" value=\"";
echo $postcode;
echo "\" tabindex=\"12\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "securityquestion");
echo "</td><td class=\"fieldarea\"><select name=\"securityqid\" class=\"form-control select-inline\" tabindex=\"6\" ";
if (empty($questions)) {
    echo "disabled";
}
echo "><option value=\"\" selected ";
echo $aInt->lang("global", "none");
echo "</option>";
foreach ($questions as $quest => $ions) {
    echo "<option value=" . $ions["id"] . "";
    if ($ions["id"] == $securityqid) {
        echo " selected";
    }
    echo ">" . $ions["question"] . "</option>";
}
echo "</select>";
if (empty($questions)) {
    echo "  <i class=\"fas fa-info-circle\" aria-hidden=\"true\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"" . $aInt->lang("setup", "activatesecurityqs") . "\"></i>";
}
echo "</td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "country");
echo "</td><td class=\"fieldarea\">";
echo getCountriesDropDown($country, "", 13);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "securityanswer");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"securityqans\" class=\"form-control input-250\" value=\"";
echo $securityqans;
echo "\" tabindex=\"7\" ";
if (empty($questions)) {
    echo "disabled";
}
echo "></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "phonenumber");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-200\" name=\"phonenumber\" value=\"";
echo $phonenumber;
echo "\" tabindex=\"14\"></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo AdminLang::trans(WHMCS\Billing\Tax\Vat::getLabel("fields"));
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"tax_id\" value=\"";
echo $taxId;
echo "\" class=\"form-control input-250\">\n    </td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\"><br/></td>\n    <td class=\"fieldarea\"></td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "latefees");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"latefeeoveride\"";
if ($latefeeoveride == "on" || $latefeeoveride == 1) {
    echo " checked";
}
echo " tabindex=\"15\"> ";
echo $aInt->lang("clients", "latefeesdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "paymentmethod");
echo "</td><td class=\"fieldarea\">";
echo paymentMethodsSelection($aInt->lang("clients", "changedefault"), 23);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "overduenotices");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"overideduenotices\"";
if ($overideduenotices == "on" || $overideduenotices == 1) {
    echo " checked";
}
echo " tabindex=\"16\"> ";
echo $aInt->lang("clients", "overduenoticesdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "billingcontact");
echo "</td><td class=\"fieldarea\"><select name=\"billingcid\" class=\"form-control select-inline\" tabindex=\"24\"><option value=\"\">";
echo $aInt->lang("global", "default");
echo "</option>";
$result = select_query("tblcontacts", "", array("userid" => $userid), "firstname` ASC,`lastname", "ASC");
while ($data = mysql_fetch_array($result)) {
    echo "<option value=\"" . $data["id"] . "\"";
    if ($data["id"] == $billingcid) {
        echo " selected";
    }
    echo ">" . $data["firstname"] . " " . $data["lastname"] . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "taxexempt");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"taxexempt\"";
if ($taxexempt == "on" || $taxexempt == 1) {
    echo " checked";
}
echo " tabindex=\"17\"> ";
echo $aInt->lang("clients", "taxexemptdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("global", "language");
echo "</td><td class=\"fieldarea\"><select name=\"language\" class=\"form-control select-inline\" tabindex=\"25\"><option value=\"\">";
echo $aInt->lang("global", "default");
echo "</option>";
foreach (WHMCS\Language\ClientLanguage::getLanguages() as $lang) {
    echo "<option value=\"" . $lang . "\">" . ucfirst($lang) . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "separateinvoices");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"separateinvoices\"";
if ($separateinvoices == "on" || $separateinvoices == 1) {
    echo " checked";
}
echo " tabindex=\"18\">";
echo $aInt->lang("clients", "separateinvoicesdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "status");
echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\" tabindex=\"26\">\n<option value=\"Active\"";
if ($status == "Active") {
    echo " selected";
}
echo ">";
echo $aInt->lang("status", "active");
echo "</option>\n<option value=\"Inactive\"";
if ($status == "Inactive") {
    echo " selected";
}
echo ">";
echo $aInt->lang("status", "inactive");
echo "</option>\n<option value=\"Closed\"";
if ($status == "Closed") {
    echo " selected";
}
echo ">";
echo $aInt->lang("status", "closed");
echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "disableccprocessing");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"disableautocc\"";
if ($disableautocc == "on" || $disableautocc == 1) {
    echo " checked";
}
echo " tabindex=\"19\">";
echo $aInt->lang("clients", "disableccprocessingdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("currencies", "currency");
echo "</td><td class=\"fieldarea\"><select name=\"currency\" class=\"form-control select-inline\" tabindex=\"27\">";
$result = select_query("tblcurrencies", "id,code,`default`", "", "code", "ASC");
while ($data = mysql_fetch_array($result)) {
    echo "<option value=\"" . $data["id"] . "\"";
    if ($currency && $data["id"] == $currency || !$currency && $data["default"]) {
        echo " selected";
    }
    echo ">" . $data["code"] . "</option>";
}
echo "</select></td></tr>\n    <tr>\n        <td class=\"fieldlabel\">";
echo $aInt->lang("clients", "marketingEmailsOptIn");
echo "</td>\n        <td class=\"fieldarea\"><label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"marketing_emails_opt_in\" tabindex=\"20\"";
echo $marketing_emails_opt_in == 1 ? " checked=\"checked\"" : "";
echo " value=\"1\" tabindex=\"20\">\n                ";
echo $aInt->lang("clients", "enableMarketingEmails");
echo "            </label>\n        </td>\n        <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "clientgroup");
echo "</td>\n        <td class=\"fieldarea\"><select name=\"groupid\" class=\"form-control select-inline\" tabindex=\"28\">\n                <option value=\"0\">";
echo $aInt->lang("global", "none");
echo "</option>\n                ";
$result = select_query("tblclientgroups", "", "", "groupname", "ASC");
while ($data = mysql_fetch_assoc($result)) {
    $group_id = $data["id"];
    $group_name = $data["groupname"];
    $group_colour = $data["groupcolour"];
    echo "<option style=\"background-color:" . $group_colour . "\" value=" . $group_id . "";
    if ($group_id == $groupid) {
        echo " selected";
    }
    echo ">" . $group_name . "</option>";
}
echo "</select></td></tr>\n<tr height=\"40\">\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("clients", "overrideautoclose");
echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"overrideautoclose\"";
if ($overrideautoclose == "1") {
    echo " checked";
}
echo " value=\"1\" tabindex=\"21\">\n            ";
echo $aInt->lang("clients", "overrideautocloseinfo");
echo "        </label>\n    </td>\n    <td class=\"fieldlabel\">\n        ";
echo AdminLang::trans("clients.creditbalance");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"credit\" class=\"form-control input-100\" tabindex=\"28\" value=\"";
echo $credit ?: "0.00";
echo "\"/>\n    </td>\n</tr>\n<tr height=\"40\">\n    <td class=\"fieldlabel\">\n        ";
echo AdminLang::trans("clients.allowSSO");
echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"allowsinglesignon\" value=\"1\" tabindex=\"22\"";
if ($allowSingleSignOn) {
    echo " checked";
}
echo ">\n            ";
echo AdminLang::trans("clients.allowSSODescription");
echo "        </label>\n    </td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n<tr>";
$taxindex = 29;
$customfields = getCustomFields("client", "", $userid, "on", "");
$x = 0;
foreach ($customfields as $customfield) {
    $x++;
    echo "<td class=\"fieldlabel\">" . $customfield["name"] . "</td><td class=\"fieldarea\">" . str_replace(array("<input", "<select", "<textarea"), array("<input tabindex=\"" . $taxindex . "\"", "<select tabindex=\"" . $taxindex . "\"", "<textarea tabindex=\"" . $taxindex . "\""), $customfield["input"]) . "</td>";
    if ($x % 2 == 0 || $x == count($customfields)) {
        echo "</tr><tr>";
    }
    $taxindex++;
}
echo "<td class=\"fieldlabel\">";
echo $aInt->lang("fields", "adminnotes");
echo "</td><td class=\"fieldarea\" colspan=\"3\"><textarea name=\"notes\" rows=\"4\" class=\"form-control\" tabindex=\"";
echo $taxindex++;
echo "\">";
echo $notes;
echo "</textarea></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sendemail\" checked tabindex=\"";
echo $taxindex++;
echo "\" /> ";
echo $aInt->lang("clients", "newaccinfoemail");
echo "</label>\n</div>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("clients", "addclient");
echo "\" tabindex=\"";
echo $taxindex++;
echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
$jsCode = "var stateNotRequired = true;\n";
echo WHMCS\View\Asset::jsInclude("StatesDropdown.js");
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jsCode;
$aInt->display();

?>