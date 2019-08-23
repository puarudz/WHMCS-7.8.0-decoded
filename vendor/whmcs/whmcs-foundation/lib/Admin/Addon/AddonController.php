<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Addon;

class AddonController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $aInt = new \WHMCS\Admin("List Addons");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $aInt->title = \AdminLang::Trans("services.listaddons");
        $aInt->sidebar = "clients";
        $aInt->icon = "productaddons";
        $aInt->requiredFiles(array("gatewayfunctions"));
        $name = "addons";
        $orderby = "id";
        $sort = "DESC";
        $pageObj = new \WHMCS\Pagination($name, $orderby, $sort);
        $pageObj->digestCookieData();
        $tbl = new \WHMCS\ListTable($pageObj, 0, $aInt);
        $tbl->setColumns(array("checkall", array("id", \AdminLang::trans("fields.id")), array("addon", \AdminLang::trans("fields.addon")), array("product", \AdminLang::trans("fields.product")), array("clientname", \AdminLang::trans("fields.clientname")), array("billingcycle", \AdminLang::trans("fields.billingcycle")), array("recurring", \AdminLang::trans("fields.price")), array("nextduedate", \AdminLang::trans("fields.nextduedate"))));
        $predefinedAddonsList = \WHMCS\Database\Capsule::table("tbladdons")->pluck("name", "id");
        $addonData = new Table\Addon($pageObj);
        $filter = (new \WHMCS\Filter("admin-addons-index"))->setAllowedVars(array("clientname", "addon", "type", "package", "billingcycle", "server", "paymentmethod", "status", "domain", "customfieldvalue", "customfield"));
        $searchCriteria = $filter->store()->getFilterCriteria();
        $addonData->execute($searchCriteria);
        $addonList = $pageObj->getData();
        foreach ($addonList as $data) {
            $aId = $data["id"];
            $id = $data["hostingid"];
            $addonId = $data["addonid"];
            $userId = $data["userid"];
            $addonName = $data["addonname"];
            $domain = $data["domain"];
            $dType = $data["type"];
            $dPackage = $data["name"];
            $upgrades = $data["upgrades"];
            $dPaymentMethod = $data["paymentmethod"];
            $amount = $data["recurring"];
            $billingCycle = $data["billingcycle"];
            $nextDueDate = $data["nextduedate"];
            $status = $data["status"];
            if (!$addonName) {
                $addonName = $predefinedAddonsList[$addonId];
            }
            $nextDueDate = fromMySQLDate($nextDueDate);
            $firstName = $data["firstname"];
            $lastName = $data["lastname"];
            $companyName = $data["companyname"];
            $groupId = $data["groupid"];
            $currency = $data["currency"];
            if (!$domain) {
                $domain = "(" . \AdminLang::trans("addons.nodomain") . ")";
            }
            $amount = formatCurrency($amount, $currency);
            if (in_array($billingCycle, array("One Time", "Free Account", "Free"))) {
                $nextDueDate = "-";
            }
            $billingCycle = \AdminLang::trans("billingcycles." . str_replace(array("-", "account", " "), "", strtolower($billingCycle)));
            $checkbox = "<input type=\"checkbox\" name=\"selectedclients[]\"" . " value=\"" . $id . "\" class=\"checkall\" />";
            $addonUri = "clientsservices.php?userid=" . $userId . "&id=" . $id . "&aid=" . $aId;
            $addonIdLink = "<a href=\"" . $addonUri . "\">" . $aId . "</a>";
            $addonAndStatus = $addonName . " <span class=\"label " . strtolower($status) . "\">" . $status . "</span>";
            $hostingLink = "<a href=\"clientsservices.php?userid=" . $userId . "&id=" . $id . "\">" . $dPackage . "</a>";
            $tbl->addRow(array($checkbox, $addonIdLink, $addonAndStatus, $hostingLink, $aInt->outputClientLink($userId, $firstName, $lastName, $companyName, $groupId), $billingCycle, $amount, $nextDueDate));
        }
        $predefinedAddonsList += \WHMCS\Database\Capsule::table("tblhostingaddons")->where("name", "!=", "")->pluck("name", "name");
        $tbl->setMassActionURL("sendmessage.php?type=product&multiple=true");
        $tbl->setMassActionBtns("<button type=\"submit\" class=\"btn btn-default\">" . \AdminLang::trans("global.sendmessage") . "</button>");
        $pageObj->setBasePath(routePath("admin-addons-index"));
        $tbl->setShowHidden(\App::getFromRequest("show_hidden"));
        $tableOutput = $tbl->output();
        unset($addonData);
        unset($addonList);
        $serverData = \WHMCS\View\Helper::getServerDropdownOptions($searchCriteria["server"]);
        $servers = $serverData["servers"];
        $disabledServers = $serverData["disabledServers"];
        $aInt->content = view("admin.client.addons.index", array("criteria" => $searchCriteria, "tableOutput" => $tableOutput, "products" => $aInt->productDropDown((int) $searchCriteria["package"], false, true), "addonsList" => $predefinedAddonsList, "paymentMethods" => paymentMethodsSelection(\AdminLang::trans("global.any")), "cycles" => $aInt->cyclesDropDown($searchCriteria["billingcycle"], true), "servers" => $servers . $disabledServers, "statuses" => $aInt->productStatusDropDown($searchCriteria["status"], true), "customFields" => \WHMCS\CustomField::where("type", "addon")->get(), "tabStart" => $aInt->beginAdminTabs(array(\AdminLang::trans("global.searchfilter"))), "tabEnd" => $aInt->endAdminTabs()));
        return $aInt->display();
    }
}

?>