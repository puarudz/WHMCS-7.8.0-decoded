<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Edit Clients Details");
$aInt->requiredFiles(array("clientfunctions"));
$aInt->setClientsProfilePresets();
$aInt->valUserID($userid);
$aInt->setClientsProfilePresets($userid);
$aInt->assertClientBoundary($userid);
$whmcs = App::self();
$emailerr = $whmcs->get_req_var("emailerr");
$email = $whmcs->get_req_var("email");
if ($action == "save") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Clients Details");
    if ($subaccount) {
        $subaccount = "1";
        $result = select_query("tblclients", "COUNT(*)", array("email" => $email));
        $data = mysql_fetch_array($result);
        $result = select_query("tblcontacts", "COUNT(*)", array("email" => $email, "id" => array("sqltype" => "NEQ", "value" => $contactid)));
        $data2 = mysql_fetch_array($result);
        if ($data[0] + $data2[0]) {
            $querystring = "";
            foreach ($_REQUEST as $k => $v) {
                if (!is_array($v) && $k != "action") {
                    $querystring .= "&" . $k . "=" . urlencode($v);
                }
            }
            redir("error=" . AdminLang::trans("clients.duplicateemailexp") . $querystring);
        }
    } else {
        $subaccount = "0";
    }
    if ($domainemails) {
        $domainemails = 1;
    }
    if ($generalemails) {
        $generalemails = 1;
    }
    if ($invoiceemails) {
        $invoiceemails = 1;
    }
    if ($productemails) {
        $productemails = 1;
    }
    if ($supportemails) {
        $supportemails = 1;
    }
    if ($affiliateemails) {
        $affiliateemails = 1;
    }
    $taxId = "";
    if (WHMCS\Billing\Tax\Vat::isTaxIdEnabled()) {
        $taxId = App::getFromRequest(WHMCS\Billing\Tax\Vat::getFieldName(true));
    }
    $valErr = "";
    $validate = new WHMCS\Validate();
    $queryStr = "userid=" . $userid . "&contactid=" . $contactid;
    if ($validate->validate("required", "email", "erroremail")) {
        if (!$validate->validate("email", "email", "erroremailinvalid")) {
            $valErr = "erroremailinvalid";
        }
    } else {
        $valErr = "erroremail";
    }
    if (0 < strlen($valErr)) {
        $queryStr .= "&emailerr=" . $valErr;
        redir($queryStr);
    }
    $phonenumber = App::formatPostedPhoneNumber();
    if ($contactid == "addnew") {
        if ($password && $password != $aInt->lang("fields", "password")) {
            $hasher = new WHMCS\Security\Hash\Password();
            $array["password"] = $hasher->hash(WHMCS\Input\Sanitize::decode($password));
        }
        $contactid = addContact($userid, $firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $password, $permissions, $generalemails, $productemails, $domainemails, $invoiceemails, $supportemails, $affiliateemails, $taxId);
        $queryStr = str_replace("addnew", $contactid, $queryStr);
    } else {
        logActivity("Contact Modified - User ID: " . $userid . " - Contact ID: " . $contactid, $userid);
        $oldcontactdata = get_query_vals("tblcontacts", "", array("userid" => $userid, "id" => $contactid));
        if ($permissions) {
            $permissions = implode(",", $permissions);
        }
        $table = "tblcontacts";
        $array = array("firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber, "tax_id" => $taxId, "subaccount" => $subaccount, "permissions" => $permissions, "domainemails" => $domainemails, "generalemails" => $generalemails, "invoiceemails" => $invoiceemails, "productemails" => $productemails, "supportemails" => $supportemails, "affiliateemails" => $affiliateemails);
        $changedPassword = false;
        if ($password && $password != $aInt->lang("fields", "entertochange")) {
            $hasher = new WHMCS\Security\Hash\Password();
            $array["password"] = $hasher->hash($password);
            $changedPassword = true;
        }
        if ($changedPassword) {
            run_hook("ContactChangePassword", array("userid" => $userId, "contactid" => $contactId, "password" => $password));
        }
        $where = array("id" => $contactid);
        update_query($table, $array, $where);
        if (!$subaccount) {
            WHMCS\Authentication\Remote\AccountLink::where("contact_id", "=", $contactid)->where("client_id", "=", $userid)->delete();
        }
        run_hook("ContactEdit", array_merge(array("userid" => $userid, "contactid" => $contactid, "olddata" => $oldcontactdata), $array));
    }
    $queryStr .= "&success=1";
    redir($queryStr);
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $client = new WHMCS\Client($userid);
    $client->deleteContact($contactid);
    redir("userid=" . $userid);
}
if ($resetpw) {
    check_token("WHMCS.admin.default");
    sendMessage("Automated Password Reset", $userid, array("contactid" => $contactid));
    redir("userid=" . $userid . "&contactid=" . $contactid . "&pwreset=1");
}
ob_start();
$infobox = "";
if ($whmcs->get_req_var("pwreset")) {
    infoBox(AdminLang::trans("clients.resetsendpassword"), AdminLang::trans("clients.passwordsuccess"), "success");
}
if ($errorDisplay = $whmcs->get_req_var("error")) {
    infoBox(AdminLang::trans("global.validationerror"), $errorDisplay);
}
if (in_array($whmcs->get_req_var("emailerr"), array("erroremailinvalid", "erroremail"))) {
    $errorKey = $whmcs->get_req_var("emailerr");
    infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("clients." . $errorKey), "error");
}
if ($whmcs->get_req_var("success")) {
    infoBox(AdminLang::trans("global.changesuccess"), AdminLang::trans("global.changesuccessdesc"), "success");
}
echo $infobox;
echo "\n<div class=\"context-btn-container\">\n<div class=\"text-left\">\n<form action=\"";
echo $_SERVER["PHP_SELF"];
echo "\" method=\"get\">\n<input type=\"hidden\" name=\"userid\" value=\"";
echo $userid;
echo "\">\n";
echo $aInt->lang("clientsummary", "contacts");
echo ": <select name=\"contactid\" onChange=\"submit()\" class=\"form-control select-inline\">\n";
$result = select_query("tblcontacts", "", array("userid" => $userid), "firstname` ASC,`lastname", "ASC");
while ($data = mysql_fetch_array($result)) {
    $contactlistid = $data["id"];
    if (!$contactid) {
        $contactid = $contactlistid;
    }
    $contactlistfirstname = $data["firstname"];
    $contactlistlastname = $data["lastname"];
    $contactlistemail = $data["email"];
    echo "<option value=\"" . $contactlistid . "\"";
    if ($contactlistid == $contactid) {
        echo " selected";
    }
    echo ">" . $contactlistfirstname . " " . $contactlistlastname . " - " . $contactlistemail . "</option>";
}
if (!$contactid) {
    $contactid = "addnew";
}
echo "<option value=\"addnew\"";
if ($contactid == "addnew") {
    echo " selected";
}
echo ">";
echo $aInt->lang("global", "addnew");
echo "</option>\n</select>\n<noscript>\n<input type=\"submit\" value=\"";
echo $aInt->lang("global", "go");
echo "\" class=\"btn btn-default\" />\n</noscript>\n</form>\n</div>\n</div>\n\n";
$aInt->deleteJSConfirm("deleteContact", "clients", "deletecontactconfirm", "?action=delete&userid=" . $userid . "&contactid=");
if ($contactid && $contactid != "addnew") {
    $contact = WHMCS\User\Client\Contact::whereUserid($userid)->whereId($contactid)->first();
    if (is_null($contact)) {
        redir("userid=" . $userid);
    }
    $contactid = $contact->id;
    $firstname = $contact->firstname;
    $lastname = $contact->lastname;
    $companyname = $contact->companyname;
    $email = $contact->email;
    $address1 = $contact->address1;
    $address2 = $contact->address2;
    $city = $contact->city;
    $state = $contact->state;
    $postcode = $contact->postcode;
    $country = $contact->country;
    $phonenumber = $contact->phonenumber;
    $taxId = $contact->taxId;
    $subaccount = $contact->subaccount;
    $password = $contact->password;
    $permissions = $contact->permissions;
    $generalemails = $contact->generalemails;
    $productemails = $contact->productemails;
    $domainemails = $contact->domainemails;
    $invoiceemails = $contact->invoiceemails;
    $supportemails = $contact->supportemails;
    $affiliateemails = $contact->affiliateemails;
    $password = $aInt->lang("fields", "entertochange");
    $remoteAuth = new WHMCS\Authentication\Remote\RemoteAuth();
    foreach ($contact->remoteAccountLinks()->get() as $remoteAccountLink) {
        $provider = $remoteAuth->getProviderByName($remoteAccountLink->provider);
        $remoteAccountLinks[$remoteAccountLink->id] = $provider->parseMetadata($remoteAccountLink->metadata);
    }
}
if (!is_array($permissions)) {
    $permissions = array();
}
echo "\n<form method=\"post\" action=\"";
echo $_SERVER["PHP_SELF"];
echo "?action=save&userid=";
echo $userid;
echo "&contactid=";
echo $contactid;
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "firstname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"firstname\" tabindex=\"1\" value=\"";
echo $firstname;
echo "\"></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "address");
echo " 1</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"address1\" tabindex=\"7\" value=\"";
echo $address1;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "lastname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250\" name=\"lastname\" tabindex=\"2\" value=\"";
echo $lastname;
echo "\"></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "address");
echo " 2</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250 input-inline\" name=\"address2\" tabindex=\"8\" value=\"";
echo $address2;
echo "\"> <font color=#cccccc><small>(";
echo $aInt->lang("global", "optional");
echo ")</small></font></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "companyname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-250 input-inline\" name=\"companyname\" tabindex=\"3\" value=\"";
echo $companyname;
echo "\"> <font color=#cccccc><small>(";
echo $aInt->lang("global", "optional");
echo ")</small></font></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "city");
echo "</td><td class=\"fieldarea\"><input type=\"text\" tabindex=\"9\" class=\"form-control input-250\" name=\"city\" value=\"";
echo $city;
echo "\"></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "email");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-300\" name=\"email\" tabindex=\"4\" value=\"";
echo $email;
echo "\">\n    </td>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "state");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-250\" name=\"state\" data-selectinlinedropdown=\"1\" tabindex=\"10\" value=\"";
echo $state;
echo "\">\n    </td>\n</tr>\n\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("clients", "activatesubaccount");
echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" tabindex=\"5\" name=\"subaccount\" id=\"subaccount\" ";
if ($subaccount) {
    echo "checked";
}
echo "> ";
echo $aInt->lang("global", "ticktoenable");
echo "        </label>\n    </td>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "postcode");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" tabindex=\"11\" class=\"form-control input-150\" name=\"postcode\" value=\"";
echo $postcode;
echo "\">\n    </td>\n</tr>\n\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "password");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-150\" name=\"password\" autocomplete=\"off\" tabindex=\"6\" value=\"";
echo $password;
echo "\" onfocus=\"if (this.value == '";
echo $aInt->lang("fields", "entertochange");
echo "') { this.value = '' }\" />\n        ";
if ($contactid != "addnew" && $subaccount == 1) {
    echo "            <a href=\"clientscontacts.php?userid=";
    echo $userid;
    echo "&contactid=";
    echo $contactid;
    echo "&resetpw=true";
    echo generate_token("link");
    echo "\"><img src=\"images/icons/resetpw.png\" border=\"0\" align=\"absmiddle\" /> ";
    echo $aInt->lang("clients", "resetsendpassword");
    echo "</a>\n        ";
}
echo "    </td>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "country");
echo "    </td>\n    <td class=\"fieldarea\">";
echo getCountriesDropDown($country, "", "12");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "emailnotifications");
echo "</td><td class=\"fieldarea\">\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"generalemails\" tabindex=\"14\" ";
if ($generalemails) {
    echo "checked";
}
echo "> General</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"invoiceemails\" tabindex=\"15\" ";
if ($invoiceemails) {
    echo "checked";
}
echo "> Invoice</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"supportemails\" tabindex=\"16\" ";
if ($supportemails) {
    echo "checked";
}
echo "> Support</label><br />\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"productemails\" tabindex=\"17\" ";
if ($productemails) {
    echo "checked";
}
echo "> Product</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"domainemails\" tabindex=\"18\" ";
if ($domainemails) {
    echo "checked";
}
echo "> Domain</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"affiliateemails\" tabindex=\"19\" ";
if ($affiliateemails) {
    echo "checked";
}
echo "> Affiliate</label>\n</td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "phonenumber");
echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-200\" name=\"phonenumber\" tabindex=\"13\" value=\"";
echo $phonenumber;
echo "\"></td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo AdminLang::trans(WHMCS\Billing\Tax\Vat::getLabel("fields"));
echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-250\" name=\"tax_id\" value=\"";
echo $taxId;
echo "\">\n    </td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n<tr id=\"linkedAccountsReport\"";
if ($remoteAuth && !$remoteAuth->getEnabledProviders()) {
    echo " hidden";
}
echo ">\n    <td class=\"fieldlabel\">";
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
echo "            </tbody>\n        </table>\n    </td>\n</tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
echo AdminLang::trans("fields.permissions");
echo "        </td>\n        <td class=\"fieldarea\" colspan=\"3\">\n            <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">\n                <tr id=\"rowPermissions\">\n                    <td width=\"50%\" valign=\"top\">\n                        ";
$taxindex = 20;
foreach (WHMCS\User\Client\Contact::$allPermissions as $perm) {
    $taxindex++;
    $checked = "";
    if (in_array($perm, $permissions)) {
        $checked = "checked=\"checked\" ";
    }
    $permissionName = AdminLang::trans("contactpermissions.perm" . $perm);
    $postPend = "";
    if ($perm == "managedomains") {
        $postPend = "</td><td width=\"50%\" valign=\"top\">";
    }
    echo "<label class=\"checkbox-inline\">\n    <input type=\"checkbox\" name=\"permissions[]\" tabindex=\"" . $taxindex . "\" value=\"" . $perm . "\" " . $checked . "/>\n    " . $permissionName . "\n</label><br />\n" . $postPend;
}
echo "                    </td>\n                </tr>\n                <tr>\n                    <td width=\"50%\" valign=\"top\">\n                        <button type=\"button\" class=\"btn btn-sm btn-check-all\" data-checkbox-container=\"rowPermissions\" data-btn-check-toggle=\"1\" id=\"btnSelectAll-rowPermissions\" data-label-text-select=\"";
echo AdminLang::trans("global.checkall");
echo "\" data-label-text-deselect=\"";
echo AdminLang::trans("global.uncheckAll");
echo "\">\n                            ";
echo AdminLang::trans("global.checkall");
echo "                        </button>\n                    </td>\n                </tr>\n            </table>\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    ";
if ($contactid != "addnew") {
    echo "<input type=\"submit\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"btn btn-primary\" tabindex=\"";
    echo $taxindex++;
    echo "\" /> <input type=\"reset\" value=\"";
    echo $aInt->lang("global", "cancelchanges");
    echo "\" class=\"button btn btn-default\" tabindex=\"";
    echo $taxindex++;
    echo "\" /><br />\n    <a href=\"#\" onClick=\"deleteContact('";
    echo $contactid;
    echo "');return false\" style=\"color:#cc0000\"><b>";
    echo $aInt->lang("global", "delete");
    echo "</b></a>";
} else {
    echo "<input type=\"submit\" value=\"";
    echo $aInt->lang("clients", "addcontact");
    echo "\" class=\"btn btn-primary\" tabindex=\"";
    echo $taxindex++;
    echo "\" /> <input type=\"reset\" value=\"";
    echo $aInt->lang("global", "cancelchanges");
    echo "\" class=\"button btn btn-default\" tabindex=\"";
    echo $taxindex++;
    echo "\" />";
}
echo "</div>\n\n</form>\n\n";
$jscode .= "var stateNotRequired = true;";
$jquerycode .= "\n    jQuery('.removeAccountLink').click(function (e) {\n        e.preventDefault();\n        var authUserID = jQuery(this).data('authid');\n        swal({\n          title: '" . AdminLang::trans("signIn.delCheckTitle") . "',\n          text: '" . AdminLang::trans("signIn.delCheckTitle") . "',\n          type: \"warning\",\n          showCancelButton: true,\n          confirmButtonColor: \"#DD6B55\",\n          confirmButtonText: '" . Lang::trans("remoteAuthn.yesUnlinkIt") . "',\n          closeOnConfirm: false\n        },\n        function(){\n            WHMCS.http.jqClient.post('" . routePath("admin-setup-authn-delete_account_link") . "',\n            {\n                'token': '" . generate_token("plain") . "',\n                'auth_id': authUserID\n            }).done(function(data) {\n                if (data.status == 'success') {\n                    jQuery('#remoteAuth' + authUserID).remove();\n                    if (!jQuery('.removeAccountLink').length) {\n                        jQuery('#linkedAccountsReport').find('tbody').html(\n                            '<tr><td colspan=\"4\">" . AdminLang::trans("signIn.emptyTable") . "</td></tr>'\n                        );\n                    }\n                    swal('" . Lang::trans("remoteAuthn.unlinked") . "', data.message, \"success\");\n                } else {\n                    swal('" . AdminLang::trans("global.error") . "', data.message, \"error\");\n                }\n            });\n        });\n    });\n\n    WHMCS.form.register();\n";
echo WHMCS\View\Asset::jsInclude("StatesDropdown.js");
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>