<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure OpenID Connect");
$aInt->title = AdminLang::trans("setup.openIdConnect");
$aInt->sidebar = "config";
$aInt->icon = "otherconfig";
$aInt->helplink = "OpenID Connect";
$aInt->requireAuthConfirmation();
$action = $whmcs->get_req_var("action");
$id = (int) $whmcs->get_req_var("id");
$orderby = $whmcs->get_req_var("orderby");
$content = "";
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $name = $whmcs->get_req_var("name");
    $description = $whmcs->get_req_var("description");
    $logo_uri = $whmcs->get_req_var("logo_uri");
    $authorized_uris = $whmcs->get_req_var("authorized_uris");
    $api = new WHMCS\Api();
    try {
        if ($id) {
            $api->setAction("UpdateOAuthCredential")->setParam("credentialId", $id)->setParam("name", $name)->setParam("description", $description)->setParam("logoUri", $logo_uri)->setParam("redirectUri", $authorized_uris)->call();
            $aInt->flash(AdminLang::trans("global.changesuccess"), AdminLang::trans("global.changesuccessdesc"), "success");
            redir();
        } else {
            $credentialId = $api->setAction("CreateOAuthCredential")->setParam("name", $name)->setParam("description", $description)->setParam("logoUri", $logo_uri)->setParam("redirectUri", $authorized_uris)->setParam("scope", "openid profile email")->setParam("grantType", "authorization_code")->call()->get("credentialId");
            $aInt->flash(AdminLang::trans("global.success"), AdminLang::trans("openid.newApiSuccess"), "success");
            redir("action=manage&id=" . $credentialId);
        }
    } catch (Exception $e) {
        $aInt->flash(AdminLang::trans("global.erroroccurred"), $e->getMessage(), "error");
        WHMCS\Session::setAndRelease("openIdValidationData", array("name" => $name, "description" => $description, "logo_uri" => $logo_uri, "authorized_uris" => $authorized_uris));
        redir("action=manage&id=" . $id);
    }
} else {
    if ($action == "reset") {
        check_token("WHMCS.admin.default");
        $api = new WHMCS\Api();
        try {
            $credentialId = $api->setAction("UpdateOAuthCredential")->setParam("credentialId", $id)->setParam("resetSecret", true)->call()->get("credentialId");
            $aInt->flash(AdminLang::trans("global.success"), AdminLang::trans("openid.newSecretSuccess"), "success");
            redir("action=manage&id=" . $credentialId);
        } catch (Exception $e) {
            $aInt->flash("API Credential Reset Failed", $e->getMessage(), "error");
            redir("action=manage&id=" . $id);
        }
    } else {
        if ($action == "delete") {
            check_token("WHMCS.admin.default");
            $api = new WHMCS\Api();
            try {
                $api->setAction("DeleteOAuthCredential")->setParam("credentialId", $id)->call();
                $aInt->flash(AdminLang::trans("openid.apiCredDeleted"), AdminLang::trans("openid.apiCredDeletedMsg"), "success");
                redir();
            } catch (Exception $e) {
                $aInt->flash(AdminLang::trans("openid.apiCredDeleteFailed"), $e->getMessage(), "error");
                redir();
            }
        } else {
            if (!$action) {
                $httpEnvironment = new WHMCS\Environment\Http();
                if (!$httpEnvironment->siteHasVerifiedSslCert()) {
                    $content .= infoBox(AdminLang::trans("openid.sslNotDetected"), AdminLang::trans("openid.sslNotDetectedMsg", array(":site" => App::getSystemUrl())), "warning");
                }
                $content .= $aInt->getFlashAsInfobox();
                $content .= "\n<div>" . AdminLang::trans("openid.createAndManageCredBlurb") . "</div>\n\n<div class=\"btn-group margin-top-bottom-20\" role=\"group\">\n    <a href=\"configopenid.php?action=manage\" class=\"btn btn-success\" id=\"btnCreate\">\n        <i class=\"fas fa-plus\"></i>\n        " . AdminLang::trans("openid.generateNewCreds") . "\n    </a>\n</div>\n";
                $aInt->sortableTableInit("name", "ASC");
                $numrows = WHMCS\ApplicationLink\Client::whereUserId("")->whereServiceId(0)->count();
                $orderByForLookup = $orderby;
                if ($orderby == "created") {
                    $orderByForLookup = "created_at";
                } else {
                    if ($orderby == "updated") {
                        $orderByForLookup = "updated_at";
                    }
                }
                $api = new WHMCS\Api();
                try {
                    $api->setAction("ListOAuthCredentials")->setParam("grantType", "authorization_code")->setParam("sortField", $orderByForLookup)->setParam("sortOrder", $order)->setParam("limit", $limit)->call();
                    $tabledata = array();
                    foreach ($api->get("clients") as $client) {
                        $tabledata[] = array($client["name"], $client["description"], $client["updatedAt"], "<a href=\"configopenid.php?action=manage&id=" . $client["credentialId"] . "\" class=\"btn btn-default btn-sm\">" . AdminLang::trans("home.manage") . "</a>");
                    }
                    $content .= $aInt->sortableTable(array(array("name", AdminLang::trans("fields.name")), array("description", AdminLang::trans("global.description")), array("updated", AdminLang::trans("global.lastUpdated")), ""), $tabledata);
                } catch (Exception $e) {
                    $content .= infoBox("Error", AdminLang::trans("openid.unableToListCreds"), "error");
                }
            } else {
                if ($action == "manage") {
                    if ($id) {
                        $content = "<h2>" . AdminLang::trans("openid.manageExistingCreds") . "</h2>";
                        $client = WHMCS\ApplicationLink\Client::find($id);
                        if (is_null($client)) {
                            $aInt->gracefulExit(AdminLang::trans("openid.invalidCredsRequested"));
                        }
                    } else {
                        $content = "<h2>" . AdminLang::trans("openid.createNewCreds") . "</h2>";
                        $client = new WHMCS\ApplicationLink\Client();
                    }
                    $content .= $aInt->getFlashAsInfobox();
                    if ($sessionData = WHMCS\Session::get("openIdValidationData")) {
                        $client->name = $sessionData["name"];
                        $client->description = $sessionData["description"];
                        $client->logoUri = $sessionData["logo_uri"];
                        $client->redirectUri = $sessionData["authorized_uris"];
                        WHMCS\Session::start();
                        WHMCS\Session::delete("openIdValidationData");
                    }
                    $content .= "\n\n<form method=\"post\" action=\"?action=save\">\n    <input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />\n\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . AdminLang::trans("fields.name") . "\n            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"name\" id=\"inputName\" class=\"form-control input-600\" placeholder=\"" . AdminLang::trans("openid.applicationName") . "\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($client->name) . "\" />\n            </td>\n        </tr>\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . AdminLang::trans("global.description") . "\n            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"description\" id=\"inputDescription\" class=\"form-control input-600\" placeholder=\"" . AdminLang::trans("openid.descriptionPlaceholder") . "\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($client->description) . "\" />\n            </td>\n        </tr>\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . AdminLang::trans("openid.clientApiCreds") . "\n            </td>\n            <td class=\"fieldarea\">";
                    if ($id) {
                        $content .= "\n                <div style=\"margin:15px 20px;background-color:#fff;border-radius:4px;padding:10px;max-width:900px;\">\n                    <table width=\"100%\">\n                        <tr>\n                            <td width=\"140\" class=\"text-right\">" . AdminLang::trans("fields.clientid") . "</td>\n                            <td><input type=\"text\" id=\"inputClientId\" class=\"form-control input-700\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($client->identifier) . "\" readonly=\"readonly\" /></td>\n                        </tr>\n                        <tr>\n                            <td class=\"text-right\">" . AdminLang::trans("openid.clientSecret") . "</td>\n                            <td>\n                                <div class=\"input-group input-700\">\n                                    <input type=\"text\" id=\"inputClientSecret\" class=\"form-control input-700\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($client->decryptedSecret) . "\" readonly=\"readonly\" />\n                                    <span class=\"input-group-btn\">\n                                        <button type=\"button\" class=\"btn btn-default\" style=\"width:180px;\" onclick=\"doReset('" . $id . "')\" id=\"btnDelete\">\n                                            " . AdminLang::trans("openid.resetClientSecret") . "\n                                        </button>\n                                    </span>\n                                </div>\n                            </td>\n                        </tr>\n                        <tr>\n                            <td class=\"text-right\">" . AdminLang::trans("openid.creationDate") . "</td>\n                            <td><input type=\"text\" class=\"form-control input-400\" value=\"" . $client->createdAt->format("l, F jS, Y g:i:sa") . "\" readonly=\"readonly\" /></td>\n                        </tr>\n                    </table>\n                </div>";
                    } else {
                        $content .= "\n                <div class=\"alert alert-warning\" style=\"margin:15px 20px\">\n                    <i class=\"fas fa-exclamation-triangle\"></i>\n                    " . AdminLang::trans("openid.creationOnFirstSave") . "\n                </div>";
                    }
                    $content .= "\n            </td>\n        </tr>";
                    $content .= "\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . AdminLang::trans("openid.logoUrl") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"bottom-margin-5\">\n                    " . AdminLang::trans("openid.logoUrlMsg") . "\n                </div>\n                <input type=\"text\" name=\"logo_uri\" id=\"inputLogoUri\" class=\"form-control input-700\" placeholder=\"" . AdminLang::trans("openid.logoUrlEg") . "\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($client->logo_uri) . "\" />\n            </td>\n        </tr>\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . AdminLang::trans("openid.authorizedRedirectUris") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"bottom-margin-5\">\n                    " . AdminLang::trans("openid.authorizedRedirectUrisMsg") . "\n                </div>\n                <div id=\"containerAuthorizedUris\">";
                    if (count($client->redirectUri) == 0) {
                        $content .= "\n                    <span style=\"display:block;\">\n                        <input type=\"text\" name=\"authorized_uris[]\" class=\"form-control input-inline input-600 bottom-margin-5\" placeholder=\"" . AdminLang::trans("openid.exampleUrl") . "\" />\n                        <button type=\"button\" class=\"btn btn-danger btn-xs input-inline btn-remove-uri\" disabled=\"disabled\">\n                            <i class=\"fas fa-times\"></i>\n                            " . AdminLang::trans("global.remove") . "\n                        </button>\n                    </span>";
                    }
                    foreach ($client->redirectUri as $i => $redirectUri) {
                        $content .= "\n                    <span style=\"display:block;\">\n                        <input type=\"text\" name=\"authorized_uris[]\" class=\"form-control input-inline input-600 bottom-margin-5\" placeholder=\"" . AdminLang::trans("openid.exampleUrl") . "\" value=\"" . WHMCS\Input\Sanitize::makeSafeForOutput($redirectUri) . "\" />\n                        <button type=\"button\" class=\"btn btn-danger btn-xs input-inline btn-remove-uri\">\n                            <i class=\"fas fa-times\"></i>\n                            " . AdminLang::trans("global.remove") . "\n                        </button>\n                    </span>";
                    }
                    $content .= "\n                </div>\n                <button type=\"button\" class=\"btn btn-default btn-sm\" id=\"btnAddAuthorizedUri\">\n                    <i class=\"fas fa-plus\"></i>\n                    " . AdminLang::trans("global.addAnother") . "\n                </button>\n            </td>\n        </tr>\n    </table>\n\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"" . ($id ? AdminLang::trans("global.savechanges") : AdminLang::trans("openid.generateCreds")) . "\" class=\"btn btn-primary\" id=\"btnSubmit\" />\n        <a href=\"" . $whmcs->getPhpSelf() . "\" class=\"btn btn-default\" id=\"btnCancel\">" . AdminLang::trans("global.cancelchanges") . "</a>\n        " . ($id ? "<button type=\"button\" value=\"Delete\" class=\"btn btn-danger\" onclick=\"doDelete('" . $id . "')\" id=\"btnDelete\">" . AdminLang::trans("openid.deleteCreds") . "</button>" : "") . "\n    </div>\n\n</form>\n\n    ";
                    $aInt->jquerycode = "\njQuery(\"body\").on(\"click\", \"#containerAuthorizedUris .btn-remove-uri\", function() {\n    jQuery(this).parents(\"span\").remove();\n    if (jQuery(\".btn-remove-uri\").size() <= 1) {\n        jQuery(\".btn-remove-uri\").attr(\"disabled\", \"disabled\");\n    } else {\n        jQuery(\".btn-remove-uri\").removeAttr(\"disabled\");\n    }\n});\njQuery(\"#btnAddAuthorizedUri\").click(function() {\n    var \$row = jQuery(\"#containerAuthorizedUris span:first\").clone();\n    \$row.find(\"input\").val(\"\");\n    jQuery(\"#containerAuthorizedUris\").append(\$row);\n    jQuery(\".btn-remove-uri\").removeAttr(\"disabled\");\n});\n    ";
                    $content .= $aInt->modalWithConfirmation("doDelete", AdminLang::trans("openid.doDeleteWarning") . "<br /><em>" . AdminLang::trans("openid.doDeleteWarningMsg") . "</em>", "?action=delete&id=") . $aInt->modalWithConfirmation("doReset", AdminLang::trans("openid.doResetWarning") . "<br /><em>" . AdminLang::trans("openid.doResetWarningMsg") . "</em>", "?action=reset&id=");
                }
            }
        }
    }
}
$aInt->content = $content;
$aInt->display();

?>