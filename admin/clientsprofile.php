<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Edit Clients Details", false);
$aInt->requiredFiles(array("clientfunctions", "customfieldfunctions", "gatewayfunctions"));
$aInt->setClientsProfilePresets();
$userid = $whmcs->get_req_var("userid");
$aInt->valUserID($userid);
$aInt->assertClientBoundary($userid);
$client = WHMCS\User\Client::find($userid);
if ($action == "resendVerificationEmail") {
    check_token("WHMCS.admin.default");
    if (!is_null($client)) {
        $client->sendEmailAddressVerification();
    }
    WHMCS\Terminus::getInstance()->doExit();
}
if ($whmcs->get_req_var("save")) {
    check_token("WHMCS.admin.default");
    $email = trim($email);
    $password = trim($password);
    $password = WHMCS\Input\Sanitize::decode($password);
    $result = select_query("tblclients", "COUNT(*)", "email='" . db_escape_string($email) . "' AND id!='" . db_escape_string($userid) . "'");
    $data = mysql_fetch_array($result);
    if ($data[0]) {
        redir("userid=" . $userid . "&emailexists=1");
    } else {
        $where = array("email" => $email, "subaccount" => 1);
        $result = select_query("tblcontacts", "COUNT(*)", $where);
        $data = mysql_fetch_array($result);
        if ($data[0]) {
            redir("userid=" . $userid . "&emailexists=1");
        }
        $queryString = "userid=" . $userid . "&";
        $validate = new WHMCS\Validate();
        run_validate_hook($validate, "ClientDetailsValidation", $_POST);
        $errormessage = $validate->getErrors();
        if (count($errormessage)) {
            $_SESSION["profilevalidationerror"] = $errormessage;
            redir("userid=" . $userid);
        }
        $oldclientsdetails = getClientsDetails($userid);
        $emailWasUpdated = false;
        if ($email != $oldclientsdetails["email"]) {
            $emailWasUpdated = true;
        }
        $uuid = "";
        if (empty($oldclientsdetails["uuid"])) {
            $uuid = Ramsey\Uuid\Uuid::uuid4();
            $uuid = $uuid->toString();
        } else {
            $uuid = $oldclientsdetails["uuid"];
        }
        $table = "tblclients";
        $phonenumber = App::formatPostedPhoneNumber();
        $array = array("uuid" => $uuid, "firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber, "tax_id" => $tax_id, "currency" => $_POST["currency"], "notes" => $notes, "status" => $status, "taxexempt" => (bool) $taxexempt, "latefeeoveride" => (bool) $latefeeoveride, "overideduenotices" => (bool) $overideduenotices, "separateinvoices" => (bool) $separateinvoices, "disableautocc" => (bool) $disableautocc, "overrideautoclose" => (bool) $overrideautoclose, "language" => $language, "billingcid" => $billingcid, "securityqid" => $securityqid, "securityqans" => encrypt($securityqans), "groupid" => $groupid, "allow_sso" => (bool) $whmcs->get_req_var("allowsinglesignon"));
        if (!$twofaenabled) {
            $array["authmodule"] = "";
            $array["authdata"] = "";
        }
        if ($emailWasUpdated) {
            $array["email_verified"] = 0;
            if (WHMCS\Config\Setting::getValue("EnableEmailVerification")) {
                $queryString .= "emailUpdated=true&";
            }
        }
        $changedpw = false;
        if ($password && $password != $aInt->lang("fields", "entertochange")) {
            $hasher = new WHMCS\Security\Hash\Password();
            $array["password"] = $hasher->hash($password);
            $changedpw = true;
        }
        $where = array("id" => $userid);
        update_query($table, $array, $where);
        if ($changedpw) {
            run_hook("ClientChangePassword", array("userid" => $userid, "password" => $password));
        }
        $customfields = getCustomFields("client", "", $userid, "on", "");
        foreach ($customfields as $v) {
            $k = $v["id"];
            $customfieldsarray[$k] = $_POST["customfield"][$k];
        }
        $updatefieldsarray = array("firstname" => "First Name", "lastname" => "Last Name", "companyname" => "Company Name", "email" => "Email Address", "address1" => "Address 1", "address2" => "Address 2", "city" => "City", "state" => "State", "postcode" => "Postcode", "country" => "Country", "phonenumber" => "Phone Number", "tax_id" => "Tax ID", "securityqid" => "Security Question", "billingcid" => "Billing Contact", "groupid" => "Client Group", "language" => "Language", "currency" => "Currency", "status" => "Status");
        $updatedtickboxarray = array("latefeeoveride" => "Late Fees Override", "overideduenotices" => "Overdue Notices", "taxexempt" => "Tax Exempt", "separateinvoices" => "Separate Invoices", "disableautocc" => "Disable CC Processing", "overrideautoclose" => "Auto Close");
        $changelist = array();
        foreach ($updatefieldsarray as $field => $displayname) {
            $oldvalue = $oldclientsdetails[$field];
            $newvalue = $array[$field];
            if ($field == "phonenumber" && $newvalue) {
                $newvalue = str_replace(array(" ", "-"), "", App::formatPostedPhoneNumber());
                $oldvalue = $oldclientsdetails["phonenumberformatted"];
            }
            if ($newvalue != $oldvalue) {
                $log = true;
                if ($field == "groupid") {
                    $oldvalue = $oldvalue ? get_query_val("tblclientgroups", "groupname", array("id" => $oldvalue)) : AdminLang::trans("global.none");
                    $newvalue = $newvalue ? get_query_val("tblclientgroups", "groupname", array("id" => $newvalue)) : AdminLang::trans("global.none");
                } else {
                    if ($field == "currency") {
                        $oldvalue = get_query_val("tblcurrencies", "code", array("id" => $oldvalue));
                        $newvalue = get_query_val("tblcurrencies", "code", array("id" => $newvalue));
                    } else {
                        if ($field == "securityqid") {
                            $oldvalue = decrypt(get_query_val("tbladminsecurityquestions", "question", array("id" => $oldvalue)));
                            $newvalue = decrypt(get_query_val("tbladminsecurityquestions", "question", array("id" => $newvalue)));
                            if ($oldvalue == $newvalue) {
                                $log = false;
                            }
                        }
                    }
                }
                if ($log) {
                    $changelist[] = (string) $displayname . ": '" . $oldvalue . "' to '" . $newvalue . "'";
                }
            }
            if ($field == "securityqid" && $securityqans != $oldclientsdetails["securityqans"]) {
                $changelist[] = "Security Question Answer Changed";
            }
        }
        foreach ($updatedtickboxarray as $field => $displayname) {
            if ($field == "overideduenotices") {
                $oldfield = $oldclientsdetails[$field] ? "Disabled" : "Enabled";
                $newfield = $array[$field] ? "Disabled" : "Enabled";
            } else {
                $oldfield = $oldclientsdetails[$field] ? "Enabled" : "Disabled";
                $newfield = $array[$field] ? "Enabled" : "Disabled";
            }
            if ($oldfield != $newfield) {
                $changelist[] = (string) $displayname . ": '" . $oldfield . "' to '" . $newfield . "'";
            }
        }
        $marketing_emails_opt_in = (int) App::getFromRequest("marketing_emails_opt_in");
        if ($client->isOptedInToMarketingEmails() && !$marketing_emails_opt_in) {
            $client->marketingEmailOptOut();
            $changelist[] = "Opted Out of Marketing Emails";
        } else {
            if (!$client->isOptedInToMarketingEmails() && $marketing_emails_opt_in) {
                $client->marketingEmailOptIn();
                $changelist[] = "Opted In to Marketing Emails";
            }
        }
        clientChangeDefaultGateway($userid, $paymentmethod);
        if ($oldclientsdetails["defaultgateway"] != $paymentmethod) {
            $changelist[] = "Default Payment Method: '" . $oldclientsdetails["defaultgateway"] . "' to '" . $paymentmethod . "'";
        }
        if ($changedpw) {
            $changelist[] = "Password Changed";
        }
        if (!$twofaenabled && $oldclientsdetails["twofaenabled"] == true) {
            $changelist[] = "Disabled Two-Factor Authentication";
        }
        foreach ($customfields as $customfield) {
            $fieldid = $customfield["id"];
            if ($customfield["rawvalue"] != $customfieldsarray[$fieldid]) {
                $changelist[] = "Custom Field " . $customfield["name"] . ": '" . $customfield["rawvalue"] . "' to '" . $customfieldsarray[$fieldid] . "'";
            }
        }
        saveCustomFields($userid, $customfieldsarray, "client", true);
        if (!count($changelist)) {
            $changelist[] = "No Changes";
        }
        logActivity("Client Profile Modified - " . implode(", ", $changelist) . " - User ID: " . $userid, $userid);
        run_hook("AdminClientProfileTabFieldsSave", $_REQUEST);
        if (WHMCS\Config\Setting::getValue("TaxEUTaxValidation")) {
            $client = WHMCS\User\Client::find($userid);
            $taxExempt = WHMCS\Billing\Tax\Vat::setTaxExempt($client);
            $client->save();
            if ($taxExempt != $array["taxexempt"]) {
                $array["taxexempt"] = $taxExempt;
            }
        }
        run_hook("ClientEdit", array_merge(array("userid" => $userid, "isOptedInToMarketingEmails" => $client->isOptedInToMarketingEmails(), "olddata" => $oldclientsdetails), $array));
        $queryString .= "success=true";
        redir($queryString);
    }
}
if ($whmcs->get_req_var("resetpw")) {
    check_token("WHMCS.admin.default");
    sendMessage("Automated Password Reset", $userid);
    redir("userid=" . $userid . "&pwreset=1");
}
ob_start();
if ($whmcs->get_req_var("emailexists")) {
    infoBox($aInt->lang("clients", "duplicateemail"), $aInt->lang("clients", "duplicateemailexp"), "error");
} else {
    if ($_SESSION["profilevalidationerror"]) {
        infoBox($aInt->lang("global", "validationerror"), implode("<br />", $_SESSION["profilevalidationerror"]), "error");
        unset($_SESSION["profilevalidationerror"]);
    } else {
        if ($whmcs->get_req_var("success")) {
            $successDescription = $aInt->lang("global", "changesuccessdesc");
            if ($whmcs->get_req_var("emailUpdated")) {
                $successDescription .= "  " . "<a href=\"#\" id=\"hrefEmailVerificationSendNew\">" . $aInt->lang("general", "emailVerificationSendNew") . "</a>";
            }
            infoBox($aInt->lang("global", "changesuccess"), $successDescription, "success");
        } else {
            if ($whmcs->get_req_var("pwreset")) {
                infoBox($aInt->lang("clients", "resetsendpassword"), $aInt->lang("clients", "passwordsuccess"), "success");
            }
        }
    }
}
WHMCS\Session::release();
echo $infobox;
$legacyClient = new WHMCS\Client($client);
$clientsdetails = $legacyClient->getDetails();
$firstname = $clientsdetails["firstname"];
$lastname = $clientsdetails["lastname"];
$companyname = $clientsdetails["companyname"];
$email = $clientsdetails["email"];
$address1 = $clientsdetails["address1"];
$address2 = $clientsdetails["address2"];
$city = $clientsdetails["city"];
$state = $clientsdetails["state"];
$postcode = $clientsdetails["postcode"];
$country = $clientsdetails["country"];
$phonenumber = $clientsdetails["telephoneNumber"];
$taxId = $clientsdetails["tax_id"];
$currency = $clientsdetails["currency"];
$notes = $clientsdetails["notes"];
$status = $clientsdetails["status"];
$defaultgateway = $clientsdetails["defaultgateway"];
$taxexempt = $clientsdetails["taxexempt"];
$latefeeoveride = $clientsdetails["latefeeoveride"];
$overideduenotices = $clientsdetails["overideduenotices"];
$separateinvoices = $clientsdetails["separateinvoices"];
$disableautocc = $clientsdetails["disableautocc"];
$marketingEmailsOptIn = $legacyClient->getClientModel()->isOptedInToMarketingEmails();
$overrideautoclose = $clientsdetails["overrideautoclose"];
$language = $clientsdetails["language"];
$billingcid = $clientsdetails["billingcid"];
$securityqid = $clientsdetails["securityqid"];
$securityqans = $clientsdetails["securityqans"];
$groupid = $clientsdetails["groupid"];
$twofaenabled = $clientsdetails["twofaenabled"];
$allowSingleSignOn = $clientsdetails["allowSingleSignOn"];
$password = $aInt->lang("fields", "entertochange");
$questions = getSecurityQuestions("");
$remoteAuth = new WHMCS\Authentication\Remote\RemoteAuth();
foreach ($client->remoteAccountLinks()->get() as $remoteAccountLink) {
    $provider = $remoteAuth->getProviderByName($remoteAccountLink->provider);
    $remoteAccountLinks[$remoteAccountLink->id] = $provider->parseMetadata($remoteAccountLink->metadata);
}
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?save=true&userid=";
echo $userid;
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
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
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-150 input-inline\" name=\"password\" autocomplete=\"off\" value=\"";
echo $password;
echo "\" onfocus=\"if(this.value=='";
echo $aInt->lang("fields", "entertochange");
echo "')this.value=''\" tabindex=\"5\" /> <a href=\"clientsprofile.php?userid=";
echo $userid;
echo "&resetpw=true";
echo generate_token("link");
echo "\"><img src=\"images/icons/resetpw.png\" border=\"0\" align=\"absmiddle\" /> ";
echo $aInt->lang("clients", "resetsendpassword");
echo "</a></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "postcode");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-150\" name=\"postcode\" value=\"";
echo $postcode;
echo "\" tabindex=\"12\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "securityquestion");
echo "</td><td class=\"fieldarea\"><select name=\"securityqid\" class=\"form-control select-inline\" tabindex=\"6\"><option value=\"\" selected>";
echo $aInt->lang("global", "none");
echo "</option>";
foreach ($questions as $quest => $ions) {
    echo "<option value=" . $ions["id"] . "";
    if ($ions["id"] == $securityqid) {
        echo " selected";
    }
    echo ">" . $ions["question"] . "</option>";
}
echo "</select></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "country");
echo "</td><td class=\"fieldarea\">";
echo getCountriesDropDown($country, "", 13);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "securityanswer");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"securityqans\" class=\"form-control input-250\" value=\"";
echo $securityqans;
echo "\" tabindex=\"7\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "phonenumber");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-200\" name=\"phonenumber\" value=\"";
echo $phonenumber;
echo "\" tabindex=\"14\"></td></tr>\n";
if (WHMCS\Billing\Tax\Vat::isUsingNativeField()) {
    echo "    <tr>\n        <td class=\"fieldlabel\">";
    echo AdminLang::trans(WHMCS\Billing\Tax\Vat::getLabel("fields"));
    echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"tax_id\" class=\"form-control input-250\" value=\"";
    echo $taxId;
    echo "\" tabindex=\"7\">\n        </td>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\"></td>\n    </tr>\n    ";
}
echo "<tr><td class=\"fieldlabel\"><br /></td><td class=\"fieldarea\"></td><td class=\"fieldlabel\"></td><td class=\"fieldarea\"></td></tr>\n<tr><td class=\"fieldlabel\">";
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
$paymentmethod = $defaultgateway;
echo paymentMethodsSelection($aInt->lang("clients", "changedefault"), 22);
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
echo "</td><td class=\"fieldarea\"><select name=\"billingcid\" class=\"form-control select-inline\" tabindex=\"23\"><option value=\"0\">";
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
echo "</td><td class=\"fieldarea\"><select name=\"language\" class=\"form-control select-inline\" tabindex=\"24\"><option value=\"\">";
echo $aInt->lang("global", "default");
echo "</option>";
foreach (WHMCS\Language\ClientLanguage::getLanguages() as $lang) {
    echo "<option value=\"" . $lang . "\"";
    if ($language && $lang == WHMCS\Language\ClientLanguage::getValidLanguageName($language)) {
        echo " selected=\"selected\"";
    }
    echo ">" . ucfirst($lang) . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "separateinvoices");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"separateinvoices\"";
if ($separateinvoices == "on" || $separateinvoices == 1) {
    echo " checked";
}
echo " tabindex=\"18\"> ";
echo $aInt->lang("clients", "separateinvoicesdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "status");
echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\" tabindex=\"25\">\n<option value=\"Active\"";
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
echo " tabindex=\"19\"> ";
echo $aInt->lang("clients", "disableccprocessingdesc");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("currencies", "currency");
echo "</td><td class=\"fieldarea\"><select name=\"currency\" class=\"form-control select-inline\" tabindex=\"26\">";
$result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
while ($data = mysql_fetch_array($result)) {
    echo "<option value=\"" . $data["id"] . "\"";
    if ($data["id"] == $currency) {
        echo " selected";
    }
    echo ">" . $data["code"] . "</option>";
}
echo "</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("clients", "marketingEmailsOptIn");
echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"marketing_emails_opt_in\"";
echo $marketingEmailsOptIn ? " checked=\"checked\"" : "";
echo " value=\"1\" tabindex=\"20\">\n            ";
echo $aInt->lang("clients", "enableMarketingEmails");
echo "        </label>\n        <a href=\"";
echo routePath("admin-client-consent-history", $userid);
echo "\" class=\"btn btn-default btn-sm open-modal\" data-modal-title=\"Consent History\">\n            ";
echo AdminLang::trans("marketingConsent.viewHistory");
echo "        </a>\n    </td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "clientgroup");
echo "</td>\n    <td class=\"fieldarea\"><select name=\"groupid\" class=\"form-control select-inline\" tabindex=\"27\"><option value=\"0\">";
echo $aInt->lang("global", "none");
echo "</option>\n";
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
echo "</select></td></tr>\n<tr height=\"40\"><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "overrideautoclose");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"overrideautoclose\"";
if ($overrideautoclose == "1") {
    echo " checked";
}
echo " value=\"1\" tabindex=\"20\"> ";
echo $aInt->lang("clients", "overrideautocloseinfo");
echo "</label></td><td class=\"fieldlabel\">";
echo $aInt->lang("twofa", "title");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"twofaenabled\"";
if ($twofaenabled) {
    echo " checked";
} else {
    echo " disabled";
}
echo " value=\"1\" tabindex=\"28\"> ";
echo $aInt->lang("clients", "2faenabled");
echo "</label></td></tr>\n<tr height=\"40\">\n    <td class=\"fieldlabel\">";
echo AdminLang::trans("clients.allowSSO");
echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"allowsinglesignon\" value=\"1\" tabindex=\"21\"";
if ($allowSingleSignOn) {
    echo " checked";
}
echo ">\n            ";
echo AdminLang::trans("clients.allowSSODescription");
echo "        </label>\n    </td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n<tr id=\"linkedAccountsReport\"";
if (!$remoteAuth->getEnabledProviders()) {
    echo " hidden";
}
echo " >\n    <td class=\"fieldlabel\">";
echo AdminLang::trans("signIn.linkedTableTitle");
echo "</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <table class=\"clientssummarystats\">\n            <thead>\n                <tr><th>";
echo AdminLang::trans("signIn.provider");
echo "</th><th>";
echo AdminLang::trans("signIn.name");
echo "</th><th>";
echo AdminLang::trans("signIn.emailAddress");
echo "</th><th></th></tr>\n            </thead>\n            <tbody>\n            ";
if ($remoteAccountLinks) {
    foreach ($remoteAccountLinks as $id => $metadata) {
        echo "<tr class=\"alternating\" id=\"remoteAuth" . $id . "\">";
        echo "<td>" . $metadata->getProviderName() . "</td>";
        echo "<td>";
        echo $metadata->getFullname() ?: "n/a";
        echo "</td><td>";
        echo $metadata->getEmailAddress() ?: "n/a";
        echo "</td>";
        echo "<td><a href=\"#\" class=\"removeAccountLink\" data-authid=\"" . $id . "\"><img src=\"images/icons/delete.png\"></a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan=\"4\">" . AdminLang::trans("signIn.emptyTable") . "</td></tr>";
}
echo "            </tbody>\n        </table>\n    </td>\n</tr>\n<tr>";
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
$hookret = run_hook("AdminClientProfileTabFields", $clientsdetails);
foreach ($hookret as $hookdat) {
    foreach ($hookdat as $k => $v) {
        echo "<td class=\"fieldlabel\">" . $k . "</td><td class=\"fieldarea\" colspan=\"3\">" . $v . "</td></tr>";
    }
}
echo "<td class=\"fieldlabel\">";
echo $aInt->lang("fields", "adminnotes");
echo "</td><td class=\"fieldarea\" colspan=\"3\"><textarea name=\"notes\" rows=\"4\" class=\"form-control\" tabindex=\"";
echo $taxindex++;
echo "\">";
echo $notes;
echo "</textarea></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" tabindex=\"";
echo $taxindex++;
echo "\">\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"button btn btn-default\" tabindex=\"";
echo $taxindex++;
echo "\">\n</div>\n\n</form>\n\n";
$jqueryCode = "\n    jQuery('#hrefEmailVerificationSendNew').click(function() {\n        WHMCS.http.jqClient.post('" . $whmcs->getPhpSelf() . "',\n        {\n            'token': '" . generate_token("plain") . "',\n            'action': 'resendVerificationEmail',\n            'userid': '" . $userid . "'\n        }).done(function(data) {\n            jQuery('#hrefEmailVerificationSendNew').text('" . $aInt->lang("global", "emailSent") . "');\n        });\n    });\n    jQuery('.removeAccountLink').click(function (e) {\n        e.preventDefault();\n        var authUserID = jQuery(this).data('authid');\n        swal({\n          title: '" . AdminLang::trans("signIn.delCheckTitle") . "',\n          text: '" . AdminLang::trans("signIn.delCheckTitle") . "',\n          type: \"warning\",\n          showCancelButton: true,\n          confirmButtonColor: \"#DD6B55\",\n          confirmButtonText: '" . Lang::trans("remoteAuthn.yesUnlinkIt") . "',\n          closeOnConfirm: false\n        },\n        function(){\n            WHMCS.http.jqClient.post('" . routePath("admin-setup-authn-delete_account_link") . "',\n            {\n                'token': '" . generate_token("plain") . "',\n                'auth_id': authUserID\n            }).done(function(data) {\n                if (data.status == 'success') {\n                    jQuery('#remoteAuth' + authUserID).remove();\n                    if (!jQuery('.removeAccountLink').length) {\n                        jQuery('#linkedAccountsReport').find('tbody').html(\n                            '<tr><td colspan=\"4\">" . AdminLang::trans("signIn.emptyTable") . "</td></tr>'\n                        );\n                    }\n                    swal('" . Lang::trans("remoteAuthn.unlinked") . "', data.message, \"success\");\n                } else {\n                    swal('" . AdminLang::trans("global.error") . "', data.message, \"error\");\n                }\n            });\n        });\n    });\n";
$jsCode = "var stateNotRequired = true;\n";
echo WHMCS\View\Asset::jsInclude("StatesDropdown.js");
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jqueryCode;
$aInt->jscode = $jsCode;
$aInt->display();

?>