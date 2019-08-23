<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Domain\Table;

class Domain extends \WHMCS\TableModel
{
    public function _execute($criteria = NULL)
    {
        return $this->getDomains($criteria);
    }
    public function getDomains(array $criteria = NULL)
    {
        $query = $this->startQuery($criteria);
        $inactiveClients = $this->startQuery($criteria);
        $inactiveClients->whereIn("tblclients.status", array("Inactive", "Closed"))->distinct();
        $this->getPageObj()->setHiddenCount($inactiveClients->count(array("tblclients.id")));
        if (\App::isInRequest("show_hidden") && !\App::getFromRequest("show_hidden") || !\App::isInRequest("show_hidden")) {
            $query->where("tblclients.status", "Active");
        }
        $this->getPageObj()->setNumResults($query->count());
        $orderBy = $this->getPageObj()->getOrderBy();
        if ($orderBy == "clientname") {
            $query->orderBy("tblclients.firstname", $this->getPageObj()->getSortDirection());
            $orderBy = "tblclients.lastname";
        }
        $query->orderBy($orderBy, $this->getPageObj()->getSortDirection())->limit($this->getRecordLimit())->offset($this->getRecordOffset());
        $result = $query->get(array("tbldomains.*", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid", "tblclients.currency"));
        return json_decode(json_encode($result), true);
    }
    private function startQuery(array $criteria = NULL)
    {
        $query = \WHMCS\Database\Capsule::table("tbldomains")->join("tblclients", "tblclients.id", "=", "tbldomains.userid");
        if (is_array($criteria)) {
            if ($criteria["clientname"]) {
                $query->where(\WHMCS\Database\Capsule::raw("concat(firstname, ' ', lastname)"), "like", "%" . $criteria["clientname"] . "%");
            }
            if ($criteria["domain"]) {
                $query->where("tbldomains.domain", "like", "%" . $criteria["domain"] . "%");
            }
            if ($criteria["status"]) {
                $query->where("tbldomains.status", $criteria["status"]);
            }
            if ($criteria["registrar"]) {
                $query->where("tbldomains.registrar", $criteria["registrar"]);
            }
            if ($criteria["id"]) {
                $query->where("tbldomains.id", $criteria["id"]);
            }
            if ($criteria["notes"]) {
                $query->where("tbldomains.additionalnotes", "like", "%" . $criteria["notes"] . "%");
            }
            if ($criteria["subscriptionid"]) {
                $query->where("tbldomains.subscriptionid", "like", "%" . $criteria["subscriptionid"] . "%");
            }
        }
        return $query;
    }
}

?>