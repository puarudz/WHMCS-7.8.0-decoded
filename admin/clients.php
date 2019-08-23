<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("List Clients");
$aInt->title = $aInt->lang("clients", "viewsearch");
$aInt->sidebar = "clients";
$aInt->icon = "clients";
$limitClientId = 0;
$licensing = DI::make("license");
if ($licensing->isClientLimitsEnabled()) {
    $limitClientId = $licensing->getClientBoundaryId();
}
$name = "clients";
$orderby = "id";
$sort = "DESC";
$pageObj = new WHMCS\Pagination($name, $orderby, $sort);
$pageObj->digestCookieData();
$tbl = new WHMCS\ListTable($pageObj, 0, $aInt);
$tbl->setColumns(array("checkall", array("id", $aInt->lang("fields", "id")), array("firstname", $aInt->lang("fields", "firstname")), array("lastname", $aInt->lang("fields", "lastname")), array("companyname", $aInt->lang("fields", "companyname")), array("email", $aInt->lang("fields", "email")), $aInt->lang("fields", "services"), array("datecreated", $aInt->lang("fields", "created")), array("status", $aInt->lang("fields", "status"))));
$clientsModel = new WHMCS\Clients($pageObj);
$filter = (new WHMCS\Filter())->setAllowedVars(array("userid", "name", "email", "country-calling-code-phone", "phone", "group", "status", "address1", "address2", "city", "state", "postcode", "country", "paymentmethod", "cctype", "cclastfour", "autoccbilling", "credit", "currency", "signupdaterange", "language", "marketingoptin", "emailverification", "autostatus", "taxexempt", "latefees", "overduenotices", "separateinvoices", "customfields", "email2", "country-calling-code-phone2", "phone2", "group2"));
$searchCriteria = $filter->store()->getFilterCriteria();
$clientsModel->execute($searchCriteria);
$tableOutput = "";
$numresults = $pageObj->getNumResults();
if ($filter->isActive() && $numresults == 1) {
    $client = $pageObj->getOne();
    redir("userid=" . $client["id"], "clientssummary.php");
} else {
    $clientlist = $pageObj->getData();
    foreach ($clientlist as $client) {
        $clientId = $client["id"];
        $linkopen = "<a href=\"clientssummary.php?userid=" . $client["id"] . "\"" . ($client["groupcolor"] ? " style=\"background-color:" . $client["groupcolor"] . "\"" : "") . ">";
        $linkclose = "</a>";
        $checkbox = "<input type=\"checkbox\" name=\"selectedclients[]\" value=\"" . $client["id"] . "\" class=\"checkall\" />";
        if (0 < $limitClientId && $limitClientId <= $clientId) {
            $checkbox = array("trAttributes" => array("class" => "grey-out"), "output" => $checkbox);
        }
        $tbl->addRow(array($checkbox, $linkopen . $client["id"] . $linkclose, $linkopen . $client["firstname"] . $linkclose, $linkopen . $client["lastname"] . $linkclose, $client["companyname"], "<a href=\"mailto:" . $client["email"] . "\">" . $client["email"] . "</a>", $client["services"] . " (" . $client["totalservices"] . ")", $client["datecreated"], "<span class=\"label " . strtolower($client["status"]) . "\">" . $client["status"] . "</span>"));
    }
    $tbl->setMassActionURL("sendmessage.php?type=general&multiple=true");
    $tbl->setMassActionBtns("<input type=\"submit\" value=\"" . $aInt->lang("global", "sendmessage") . "\" class=\"btn btn-default\" />");
    $tableOutput = $tbl->output();
    unset($clientsModel);
    unset($clientlist);
}
$displaySearchCriteria = $searchCriteria;
$displaySearchCriteria["phone"] = str_replace(".", "", App::formatPostedPhoneNumber("phone"));
$aInt->content = view("admin.client.index", array("searchActive" => $filter->isActive(), "searchCriteria" => $displaySearchCriteria, "clientsModel" => $clientsModel, "tableOutput" => $tableOutput, "searchEnabledOptions" => array("" => "Any", "true" => "Enabled", "false" => "Disabled"), "searchEnabledOptionsInverse" => array("" => "Any", "false" => "Enabled", "true" => "Disabled"), "countries" => (new WHMCS\Utility\Country())->getCountryNameArray(), "clientLanguages" => WHMCS\Language\ClientLanguage::getLanguages(), "clientGroups" => WHMCS\User\Client::getGroups(), "clientStatuses" => WHMCS\User\Client::getStatuses(), "paymentMethods" => WHMCS\Database\Capsule::table("tblpaymentgateways")->where("setting", "name")->orderBy("value")->pluck("value", "gateway"), "currencies" => WHMCS\Billing\Currency::defaultSorting()->pluck("code", "id"), "cardTypes" => WHMCS\User\Client::getUsedCardTypes(), "customFields" => WHMCS\CustomField::where("type", "client")->get()));
$aInt->display();

?>