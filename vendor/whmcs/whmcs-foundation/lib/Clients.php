<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Clients extends TableModel
{
    private $groups = NULL;
    private $customfieldsfilter = false;
    public function _execute($criteria = NULL)
    {
        return $this->getClients($criteria);
    }
    public function getClients($criteria = array())
    {
        global $disable_clients_list_services_summary;
        $clientgroups = $this->getGroups();
        $filters = $this->buildCriteria($criteria);
        $where = count($filters) ? " WHERE " . implode(" AND ", $filters) : "";
        $tableJoin = $this->customfieldsfilter ? " INNER JOIN tblcustomfieldsvalues ON tblcustomfieldsvalues.relid=tblclients.id" : "";
        if (!empty($criteria["cctype"])) {
            $tableJoin .= " LEFT JOIN tblpaymethods on (tblpaymethods.contact_id=tblclients.id AND tblpaymethods.contact_type=\"Client\")" . " LEFT JOIN tblcreditcards on tblpaymethods.id=tblcreditcards.pay_method_id";
        }
        $result = full_query("SELECT COUNT(*) FROM tblclients" . $tableJoin . $where);
        $data = mysql_fetch_array($result);
        $this->getPageObj()->setNumResults($data[0]);
        $inactiveResult = Database\Capsule::table("tblclients");
        if ($this->customfieldsfilter) {
            $inactiveResult = $inactiveResult->join("tblcustomfieldsvalues", "tblcustomfieldsvalues.relid", "=", "tblclients.id");
        }
        if (!empty($criteria["cctype"])) {
            $inactiveResult = $inactiveResult->leftJoin("tblpaymethods", function (\Illuminate\Database\Query\JoinClause $join) {
                $join->on("tblpaymethods.contact_id", "=", "tblclients.id");
                $join->on("tblpaymethods.contact_type", "=", Database\Capsule::raw("\"Client\""));
            })->leftJoin("tblcreditcards", "tblpaymethods.id", "=", "tblcreditcards.pay_method_id");
        }
        foreach ($filters as $filter) {
            if (substr($filter, 0, 6) == "status") {
                continue;
            }
            $inactiveResult->whereRaw($filter);
        }
        $inactiveResult->whereIn("status", array("Inactive", "Closed"));
        $inactiveCount = $inactiveResult->count();
        $this->getPageObj()->setHiddenCount($inactiveCount);
        $clients = array();
        $query = "SELECT tblclients.id,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.email,tblclients.datecreated,tblclients.groupid,tblclients.status FROM tblclients" . $tableJoin . $where . " ORDER BY " . $this->getPageObj()->getOrderBy() . " " . $this->getPageObj()->getSortDirection() . " LIMIT " . $this->getQueryLimit();
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $email = $data["email"];
            $datecreated = $data["datecreated"];
            $groupid = $data["groupid"];
            $status = $data["status"];
            $datecreated = fromMySQLDate($datecreated);
            $groupcolor = isset($clientgroups[$groupid]["colour"]) ? $clientgroups[$groupid]["colour"] . "\"" : "";
            $services = $totalservices = "-";
            if (!$disable_clients_list_services_summary) {
                $result2 = full_query("SELECT (SELECT COUNT(*) FROM tblhosting WHERE userid=tblclients.id AND domainstatus IN ('Active','Suspended'))+(SELECT COUNT(*) FROM tblhostingaddons WHERE hostingid IN (SELECT id FROM tblhosting WHERE userid=tblclients.id) AND status IN ('Active','Suspended'))+(SELECT COUNT(*) FROM tbldomains WHERE userid=tblclients.id AND status IN ('Active')) AS services,(SELECT COUNT(*) FROM tblhosting WHERE userid=tblclients.id)+(SELECT COUNT(*) FROM tblhostingaddons WHERE hostingid IN (SELECT id FROM tblhosting WHERE userid=tblclients.id))+(SELECT COUNT(*) FROM tbldomains WHERE userid=tblclients.id) AS totalservices FROM tblclients WHERE tblclients.id=" . (int) $id . " LIMIT 1");
                $data = mysql_fetch_array($result2);
                $services = $data["services"];
                $totalservices = $data["totalservices"];
            }
            $clients[] = array("id" => $id, "firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "groupid" => $groupid, "groupcolor" => $groupcolor, "email" => $email, "services" => $services, "totalservices" => $totalservices, "datecreated" => $datecreated, "status" => $status);
        }
        return $clients;
    }
    private function buildCriteria($criteria)
    {
        $filters = array();
        if ($criteria["userid"]) {
            $filters[] = "id=" . (int) $criteria["userid"];
        }
        if ($criteria["name"]) {
            $filters[] = "concat(firstname,' ',lastname,' ',companyname) LIKE '%" . db_escape_string($criteria["name"]) . "%'";
        }
        if ($criteria["address1"]) {
            $filters[] = "address1 LIKE '%" . db_escape_string($criteria["address1"]) . "%'";
        }
        if ($criteria["address2"]) {
            $filters[] = "address2 LIKE '%" . db_escape_string($criteria["address2"]) . "%'";
        }
        if ($criteria["city"]) {
            $filters[] = "city LIKE '%" . db_escape_string($criteria["city"]) . "%'";
        }
        if ($criteria["state"]) {
            $filters[] = "state LIKE '%" . db_escape_string($criteria["state"]) . "%'";
        }
        if ($criteria["postcode"]) {
            $filters[] = "postcode LIKE '%" . db_escape_string($criteria["postcode"]) . "%'";
        }
        if ($criteria["country"]) {
            $filters[] = "country='" . db_escape_string($criteria["country"]) . "'";
        }
        if ($criteria["email"]) {
            $filters[] = "email LIKE '%" . db_escape_string($criteria["email"]) . "%'";
        }
        if ($criteria["email2"]) {
            $filters[] = "email LIKE '%" . db_escape_string($criteria["email2"]) . "%'";
        }
        if ($criteria["phone"]) {
            $rawPhone = $phone = db_escape_string($criteria["phone"]);
            if ($criteria["country-calling-code-phone"]) {
                $phone = "+" . db_escape_string($criteria["country-calling-code-phone"]) . "%" . $rawPhone;
            }
            $filters[] = "(phonenumber LIKE '" . $phone . "%' OR phonenumber LIKE '%" . $rawPhone . "%')";
        }
        if ($criteria["phone2"]) {
            $rawPhone = $phone = db_escape_string($criteria["phone2"]);
            if ($criteria["country-calling-code-phone2"]) {
                $phone = "+" . db_escape_string($criteria["country-calling-code-phone2"]) . "%" . $rawPhone;
            }
            $filters[] = "(phonenumber LIKE '" . $phone . "%' OR phonenumber LIKE '%" . $rawPhone . "%')";
        }
        if ($criteria["status"] && $criteria["status"] != "any") {
            $filters[] = "status='" . db_escape_string($criteria["status"]) . "'";
        } else {
            if ($criteria["status"] != "any" && (\App::isInRequest("show_hidden") && !\App::getFromRequest("show_hidden") || !\App::isInRequest("show_hidden"))) {
                $filters[] = "status='Active'";
            }
        }
        if ($criteria["group"]) {
            $filters[] = "groupid=" . (int) $criteria["group"];
        }
        if ($criteria["group2"]) {
            $filters[] = "groupid=" . (int) $criteria["group2"];
        }
        if ($criteria["paymentmethod"]) {
            $filters[] = "defaultgateway='" . db_escape_string($criteria["paymentmethod"]) . "'";
        }
        if ($criteria["cctype"]) {
            $value = db_escape_string($criteria["cctype"]);
            $filters[] = "(" . " tblclients.cardtype='" . $value . "'" . " OR tblcreditcards.card_type='" . $value . "'" . " )";
        }
        if ($criteria["cclastfour"]) {
            $filters[] = "cardlastfour='" . db_escape_string($criteria["cclastfour"]) . "'";
        }
        if ($criteria["autoccbilling"]) {
            if ($criteria["autoccbilling"] === "true") {
                $filters[] = "disableautocc=1";
            } else {
                $filters[] = "disableautocc!=1";
            }
        }
        if ($criteria["credit"]) {
            $filters[] = "credit='" . db_escape_string($criteria["credit"]) . "'";
        }
        if ($criteria["currency"]) {
            $filters[] = "currency=" . (int) $criteria["currency"];
        }
        if ($criteria["language"]) {
            $filters[] = "language='" . db_escape_string($criteria["language"]) . "'";
        }
        if ($criteria["marketingoptin"]) {
            $filters[] = "marketing_emails_opt_in='" . (int) ($criteria["marketingoptin"] === "true") . "'";
        }
        if ($criteria["emailverification"]) {
            if ($criteria["emailverification"] === "true") {
                $filters[] = "email_verified=1";
            } else {
                $filters[] = "email_verified!=1";
            }
        }
        if ($criteria["autostatus"]) {
            if ($criteria["autostatus"] === "true") {
                $filters[] = "overrideautoclose=1";
            } else {
                $filters[] = "overrideautoclose!=1";
            }
        }
        if ($criteria["taxexempt"]) {
            if ($criteria["taxexempt"] === "true") {
                $filters[] = "taxexempt=1";
            } else {
                $filters[] = "taxexempt!=1";
            }
        }
        if ($criteria["latefees"]) {
            if ($criteria["latefees"] === "true") {
                $filters[] = "latefeeoveride=1";
            } else {
                $filters[] = "latefeeoveride!=1";
            }
        }
        if ($criteria["overduenotices"]) {
            if ($criteria["overduenotices"] === "true") {
                $filters[] = "overideduenotices=1";
            } else {
                $filters[] = "overideduenotices!=1";
            }
        }
        if ($criteria["separateinvoices"]) {
            if ($criteria["separateinvoices"] === "true") {
                $filters[] = "separateinvoices=1";
            } else {
                $filters[] = "separateinvoices!=1";
            }
        }
        if ($criteria["signupdaterange"]) {
            $dateRange = $criteria["signupdaterange"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "datecreated >= '" . $dateFrom->toDateTimeString() . "' " . " AND datecreated <= '" . $dateTo->toDateTimeString() . "'";
        }
        $cfquery = array();
        if (is_array($criteria["customfields"])) {
            foreach ($criteria["customfields"] as $fieldid => $fieldvalue) {
                $fieldvalue = trim($fieldvalue);
                if ($fieldvalue) {
                    $cfquery[] = "(tblcustomfieldsvalues.fieldid='" . db_escape_string($fieldid) . "' AND tblcustomfieldsvalues.value LIKE '%" . db_escape_string($fieldvalue) . "%')";
                    $this->customfieldsfilter = true;
                }
            }
        }
        if (count($cfquery)) {
            $filters[] = implode(" OR ", $cfquery);
        }
        return $filters;
    }
    public function getGroups()
    {
        if (is_array($this->groups)) {
            return $this->groups;
        }
        $this->groups = array();
        $result = select_query("tblclientgroups", "", "");
        while ($data = mysql_fetch_array($result)) {
            $this->groups[$data["id"]] = array("name" => $data["groupname"], "colour" => $data["groupcolour"], "discountpercent" => $data["discountpercent"], "susptermexempt" => $data["susptermexempt"], "separateinvoices" => $data["separateinvoices"]);
        }
        return $this->groups;
    }
    public function getNumberOfOpenCancellationRequests()
    {
        return (int) get_query_val("tblcancelrequests", "COUNT(tblcancelrequests.id)", "(tblhosting.domainstatus!='Cancelled' AND tblhosting.domainstatus!='Terminated')", "", "", "", "tblhosting ON tblhosting.id=tblcancelrequests.relid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid INNER JOIN tblclients ON tblhosting.userid=tblclients.id");
    }
}

?>