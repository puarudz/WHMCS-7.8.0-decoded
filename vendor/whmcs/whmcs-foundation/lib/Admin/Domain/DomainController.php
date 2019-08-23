<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Domain;

class DomainController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $aInt = new \WHMCS\Admin("List Domains");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $aInt->title = \AdminLang::Trans("services.listdomains");
        $aInt->sidebar = "clients";
        $aInt->icon = "domains";
        $aInt->requiredFiles(array("registrarfunctions"));
        $name = "domains";
        $orderby = "domain";
        $sort = "ASC";
        $pageObj = new \WHMCS\Pagination($name, $orderby, $sort);
        $pageObj->digestCookieData();
        $tbl = new \WHMCS\ListTable($pageObj, 0, $aInt);
        $tbl->setColumns(array("checkall", array("id", \AdminLang::trans("fields.id")), array("domain", \AdminLang::trans("fields.domain")), array("clientname", \AdminLang::trans("fields.clientname")), array("registrationperiod", \AdminLang::trans("fields.regperiod")), array("registrar", \AdminLang::trans("fields.registrar")), array("recurringamount", \AdminLang::trans("fields.price")), array("nextduedate", \AdminLang::trans("fields.nextduedate")), array("expirydate", \AdminLang::trans("fields.expirydate"))));
        $domainData = new Table\Domain($pageObj);
        $filter = (new \WHMCS\Filter("admin-domains-index"))->setAllowedVars(array("clientname", "domain", "status", "registrar", "id", "notes", "subscriptionid"));
        $searchCriteria = $filter->store()->getFilterCriteria();
        $domainData->execute($searchCriteria);
        $domainList = $pageObj->getData();
        foreach ($domainList as $data) {
            $id = $data["id"];
            $userId = $data["userid"];
            $domain = $data["domain"];
            $amount = $data["recurringamount"];
            $registrar = $data["registrar"];
            $nextDueDate = $data["nextduedate"];
            $expiryDate = $data["expirydate"];
            $subscriptionId = $data["subscriptionid"];
            $registrationDate = $data["registrationdate"];
            $registrationPeriod = $data["registrationperiod"];
            $status = $data["status"];
            $firstName = $data["firstname"];
            $lastName = $data["lastname"];
            $companyName = $data["companyname"];
            $groupId = $data["groupid"];
            $currency = $data["currency"];
            if (!$domain) {
                $domain = "(" . \AdminLang::trans("addons.nodomain") . ")";
            }
            $amount = formatCurrency($amount, $currency);
            $registrationDate = fromMySQLDate($registrationDate);
            $nextDueDate = fromMySQLDate($nextDueDate);
            $expiryDate = fromMySQLDate($expiryDate);
            $yearOrYears = "domains.year";
            if (1 < $registrationPeriod) {
                $yearOrYears .= "s";
            }
            $registrationPeriod .= " " . \AdminLang::trans($yearOrYears);
            $styleStatus = \WHMCS\View\Helper::generateCssFriendlyClassName($status);
            $checkbox = "<input type=\"checkbox\" name=\"selectedclients[]\"" . " value=\"" . $id . "\" class=\"checkall\" />";
            $domainUri = "clientsdomains.php?userid=" . $userId . "&id=" . $id;
            $domainIdLink = "<a href=\"" . $domainUri . "\">" . $id . "</a>";
            $domainNameLink = "<a href=\"" . $domainUri . "\">" . $domain . "</a>";
            $domainLinkAndStatus = $domainNameLink . " <span class=\"label " . $styleStatus . "\">" . $status . "</span>";
            $registrarInterface = new \WHMCS\Module\Registrar();
            $registrarLabel = ucfirst($registrar);
            if ($registrarInterface->load($registrar)) {
                $registrarLabel = $registrarInterface->getDisplayName();
            }
            $tbl->addRow(array($checkbox, $domainIdLink, $domainLinkAndStatus, $aInt->outputClientLink($userId, $firstName, $lastName, $companyName, $groupId), $registrationPeriod, $registrarLabel, $amount, $nextDueDate, $expiryDate));
        }
        $tbl->setMassActionURL("sendmessage.php?type=domain&multiple=true");
        $tbl->setMassActionBtns("<button type=\"submit\" class=\"btn btn-default\">" . \AdminLang::trans("global.sendmessage") . "</button>");
        $pageObj->setBasePath(routePath("admin-domains-index"));
        $tbl->setShowHidden(\App::getFromRequest("show_hidden"));
        $tableOutput = $tbl->output();
        unset($domainData);
        unset($domainList);
        $aInt->content = view("admin.client.domains.index", array("criteria" => $searchCriteria, "tableOutput" => $tableOutput, "cycles" => $aInt->cyclesDropDown($searchCriteria["billingcycle"], true), "statuses" => (new \WHMCS\Domain\Status())->translatedDropdownOptions(array($searchCriteria["status"])), "registrars" => getRegistrarsDropdownMenu($searchCriteria["registrar"]), "tabStart" => $aInt->beginAdminTabs(array(\AdminLang::trans("global.searchfilter"))), "tabEnd" => $aInt->endAdminTabs()));
        return $aInt->display();
    }
    public function sslCheck(\WHMCS\Http\Message\ServerRequest $request)
    {
        $domain = $request->get("domain");
        $userId = $request->get("userid");
        $sslStatus = \WHMCS\Domain\Ssl\Status::factory($userId, $domain)->syncAndSave();
        $response = array("image" => $sslStatus->getImagePath(), "tooltip" => $sslStatus->getTooltipContent(), "class" => $sslStatus->getClass());
        if ($request->get("details")) {
            $issuerName = "";
            if ($sslStatus->issuerName) {
                $issuerName = $sslStatus->issuerOrg;
                if (!$issuerName) {
                    $issuerName = $sslStatus->issuerName;
                }
            }
            $response["issuerName"] = $issuerName;
            $expiryDate = $sslStatus->expiryDate;
            if ($expiryDate) {
                $expiryDate = $expiryDate->endOfDay()->toAdminDateTimeFormat();
            } else {
                $expiryDate = "-";
            }
            $response["expiryDate"] = $expiryDate;
        }
        return new \WHMCS\Http\Message\JsonResponse($response);
    }
}

?>