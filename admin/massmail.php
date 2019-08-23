<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Mass Mail");
$aInt->title = $aInt->lang("permissions", "21");
$aInt->sidebar = "clients";
$aInt->icon = "massmail";
$aInt->helplink = "Mass Mail";
$aInt->requiredFiles(array("customfieldfunctions"));
$clientgroups = getClientGroups();
$jscode = "function showMailOptions(type) {\n    \$(\"#product_criteria\").slideUp();\n    \$(\"#addon_criteria\").slideUp();\n    \$(\"#domain_criteria\").slideUp();\n    \$(\"#client_criteria\").slideDown();\n    if (type) \$(\"#\"+type+\"_criteria\").slideDown();\n}";
ob_start();
if (!WHMCS\Marketing\EmailSubscription::isUsingOptInField()) {
    $title = AdminLang::trans("marketingConsent.conversionTitle");
    $link = AdminLang::trans("global.clickhere");
    $url = routePath("admin-marketing-consent-convert");
    $link = "<a href=\"" . $url . "\" class=\"open-modal\" data-modal-title=\"" . $title . "\">" . $link . "</a>";
    $body = AdminLang::trans("marketingConsent.conversionData", array(":clickHere" => $link));
    echo "<div id=\"marketingConsentAlert\" class=\"alert alert-update-banner-grey marketing-consent-alert\"><h2><i class=\"fas fa-sync fa-fw\"></i><strong>" . $title . "</strong></h2>" . $body . "</div>";
}
echo "\n<p>";
echo $aInt->lang("massmail", "pagedesc");
echo "</p>\n\n<form method=\"post\" action=\"sendmessage.php?type=massmail\">\n\n<h2>";
echo $aInt->lang("massmail", "messagetype");
echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">\n            ";
echo $aInt->lang("massmail", "emailtype");
echo "        </td>\n        <td class=\"fieldarea\">\n\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"emailtype\" id=\"typegen\" value=\"General\" onclick=\"showMailOptions('')\" /> ";
echo $aInt->lang("emailtpls", "typegeneral");
echo "</label>\n\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"emailtype\" id=\"typeprod\" value=\"Product/Service\" onclick=\"showMailOptions('product')\" /> ";
echo $aInt->lang("fields", "product");
echo "</label>\n\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"emailtype\" id=\"typeaddon\" value=\"Addon\" onclick=\"showMailOptions('addon')\" /> ";
echo $aInt->lang("fields", "addon");
echo "</label>\n\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"emailtype\" id=\"typedom\" value=\"Domain\" onclick=\"showMailOptions('domain')\" /> ";
echo $aInt->lang("fields", "domain");
echo "</label>\n\n        </td>\n    </tr>\n</table>\n\n<div id=\"client_criteria\" style=\"display:none;\">\n\n<br />\n\n<h2>";
echo $aInt->lang("massmail", "clientcriteria");
echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "clientgroup");
echo "</td><td class=\"fieldarea\"><select name=\"clientgroup[]\" size=\"4\" multiple=\"true\" class=\"form-control\">";
foreach ($clientgroups as $groupid => $data) {
    echo "<option value=\"" . $groupid . "\">" . $data["name"] . "</option>";
}
echo "</select></td></tr>\n";
$customfields = getCustomFields("client", "", "", true);
foreach ($customfields as $customfield) {
    echo "<tr><td class=\"fieldlabel\">" . $customfield["name"] . "</td><td class=\"fieldarea\">";
    if ($customfield["type"] == "tickbox") {
        echo "<input type=\"radio\" name=\"customfield[" . $customfield["id"] . "]\" value=\"\" checked /> No Filter <input type=\"radio\" name=\"customfield[" . $customfield["id"] . "]\" value=\"cfon\" /> Checked Only <input type=\"radio\" name=\"customfield[" . $customfield["id"] . "]\" value=\"cfoff\" /> Unchecked Only";
    } else {
        echo str_replace("\"><option value=\"", "\"><option value=\"\">" . $aInt->lang("global", "any") . "</option><option value=\"", $customfield["input"]);
    }
    echo "</td></tr>";
}
echo "<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "country");
echo "</td>\n    <td class=\"fieldarea\">\n        <select name=\"clientcountry[]\" size=\"4\" multiple=\"true\" class=\"form-control\">";
$countryHelper = new WHMCS\Utility\Country();
foreach (WHMCS\Database\Capsule::table("tblclients")->distinct("country")->orderBy("country")->pluck("country") as $countryCode) {
    if ($countryHelper->isValidCountryCode($countryCode)) {
        echo "<option value=\"" . $countryCode . "\" selected>" . $countryHelper->getName($countryCode) . "</option>";
    }
}
echo "        </select>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("global", "language");
echo "</td><td class=\"fieldarea\"><select name=\"clientlanguage[]\" size=\"4\" multiple=\"true\" class=\"form-control\"><option value=\"\" selected>";
echo $aInt->lang("global", "default");
echo "</option>";
$result = select_query("tblclients", "DISTINCT language", "", "language", "ASC");
while ($data = mysql_fetch_array($result)) {
    $language = $displanguage = $data["language"];
    if (trim($language)) {
        echo "<option value=\"" . $language . "\" selected>" . ucfirst($displanguage) . "</option>";
    }
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("massmail", "clientstatus");
echo "</td><td class=\"fieldarea\"><select name=\"clientstatus[]\" size=\"3\" multiple=\"true\" class=\"form-control\"><option value=\"Active\" selected>";
echo $aInt->lang("status", "active");
echo "</option><option value=\"Inactive\" selected>";
echo $aInt->lang("status", "inactive");
echo "</option><option value=\"Closed\" selected>";
echo $aInt->lang("status", "closed");
echo "</option></select></td></tr>\n</table>\n\n</div>\n<div id=\"product_criteria\" style=\"display:none;\">\n\n<br />\n\n<h2>";
echo $aInt->lang("massmail", "productservicecriteria");
echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "product");
echo "</td><td class=\"fieldarea\"><select name=\"productids[]\" size=\"10\" multiple=\"true\" class=\"form-control\">";
$products = new WHMCS\Product\Products();
$productsList = $products->getProducts();
foreach ($productsList as $data) {
    $id = $data["id"];
    $name = $data["name"];
    $groupname = $data["groupname"];
    echo "<option value=\"" . $id . "\">" . $groupname . " - " . $name . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("massmail", "productservicestatus");
echo "</td><td class=\"fieldarea\"><select name=\"productstatus[]\" size=\"5\" multiple=\"true\" class=\"form-control\">\n<option value=\"Pending\">";
echo $aInt->lang("status", "pending");
echo "</option>\n<option value=\"Active\">";
echo $aInt->lang("status", "active");
echo "</option>\n<option value=\"Suspended\">";
echo $aInt->lang("status", "suspended");
echo "</option>\n<option value=\"Terminated\">";
echo $aInt->lang("status", "terminated");
echo "</option>\n<option value=\"Cancelled\">";
echo $aInt->lang("status", "cancelled");
echo "</option>\n<option value=\"Fraud\">";
echo $aInt->lang("status", "fraud");
echo "</option>\n<option value=\"Completed\">";
echo $aInt->lang("status", "completed");
echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("massmail", "assignedserver");
echo "</td><td class=\"fieldarea\"><select name=\"server[]\" size=\"5\" multiple=\"true\" class=\"form-control\">";
$result = select_query("tblservers", "", "", "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $name = $data["name"];
    echo "<option value=\"" . $id . "\">" . $name . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("massmail", "sendforeachdomain");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"sendforeach\">";
echo $aInt->lang("massmail", "tickboxsendeverymatchingdomain");
echo "</td></tr>\n</table>\n\n</div>\n<div id=\"addon_criteria\" style=\"display:none;\">\n\n<br />\n\n<h2>";
echo $aInt->lang("massmail", "addoncriteria");
echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "addon");
echo "</td><td class=\"fieldarea\"><select name=\"addonids[]\" size=\"10\" multiple=\"true\" class=\"form-control\">";
$result = select_query("tbladdons", "id,name", "", "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $addonname = $data["name"];
    echo "<option value=\"" . $id . "\">" . $addonname . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("massmail", "addonstatus");
echo "</td><td class=\"fieldarea\"><select name=\"addonstatus[]\" size=\"5\" multiple=\"true\" class=\"form-control\">\n<option value=\"Pending\">";
echo $aInt->lang("status", "pending");
echo "</option>\n<option value=\"Active\">";
echo $aInt->lang("status", "active");
echo "</option>\n<option value=\"Suspended\">";
echo $aInt->lang("status", "suspended");
echo "</option>\n<option value=\"Terminated\">";
echo $aInt->lang("status", "terminated");
echo "</option>\n<option value=\"Cancelled\">";
echo $aInt->lang("status", "cancelled");
echo "</option>\n<option value=\"Fraud\">";
echo $aInt->lang("status", "fraud");
echo "</option>\n<option value=\"Completed\">";
echo $aInt->lang("status", "completed");
echo "</option>\n</select></td></tr>\n</table>\n\n</div>\n<div id=\"domain_criteria\" style=\"display:none;\">\n\n<br />\n\n<h2>";
echo $aInt->lang("massmail", "domaincriteria");
echo "</h2>\n\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">";
echo AdminLang::trans("massmail.domainstatus");
echo "</td>\n            <td class=\"fieldarea\">\n                <select name=\"domainstatus[]\" size=\"5\" multiple=\"multiple\" class=\"form-control\">\n                    ";
echo (new WHMCS\Domain\Status())->translatedDropdownOptions();
echo "                </select>\n            </td>\n        </tr>\n    </table>\n\n</div>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("massmail", "composemsg");
echo "\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n<p>";
echo $aInt->lang("massmail", "footnote");
echo "</p>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>