<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Service\Table;

class Service extends \WHMCS\TableModel
{
    public function _execute($criteria = NULL)
    {
        return $this->getServices($criteria);
    }
    protected function getServices($criteria = NULL)
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
        if ($orderBy == "product") {
            $orderBy = "tblproducts.name";
        } else {
            if ($orderBy == "clientname") {
                $query->orderBy("tblclients.firstname", $this->getPageObj()->getSortDirection());
                $orderBy = "tblclients.lastname";
            }
        }
        $query->orderBy($orderBy, $this->getPageObj()->getSortDirection())->limit($this->getRecordLimit())->offset($this->getRecordOffset());
        $result = $query->get(array("tblhosting.*", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid", "tblclients.currency", "tblproducts.name", "tblproducts.type", "tblproducts.servertype"));
        return json_decode(json_encode($result), true);
    }
    private function startQuery(array $criteria = NULL)
    {
        $query = \WHMCS\Database\Capsule::table("tblhosting")->join("tblclients", "tblclients.id", "=", "tblhosting.userid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid");
        if (is_array($criteria)) {
            if ($criteria["clientname"]) {
                $query->where(\WHMCS\Database\Capsule::raw("concat(firstname, ' ', lastname)"), "like", "%" . $criteria["clientname"] . "%");
            }
            if ($criteria["type"]) {
                $query->where("tblproducts.type", $criteria["type"]);
            }
            if ($criteria["package"]) {
                $query->where("tblproducts.id", $criteria["package"]);
            }
            if ($criteria["productname"]) {
                $query->where("tblproducts.name", $criteria["productname"]);
            }
            if ($criteria["billingcycle"]) {
                $query->where("billingcycle", $criteria["billingcycle"]);
            }
            if ($criteria["server"]) {
                $query->where("server", $criteria["server"]);
            }
            if ($criteria["paymentmethod"]) {
                $query->where("paymentmethod", $criteria["paymentmethod"]);
            }
            if ($criteria["nextduedate"]) {
                $query->where("nextduedate", toMySQLDate($criteria["nextduedate"]));
            }
            if ($criteria["status"]) {
                $query->where("domainstatus", $criteria["status"]);
            }
            if ($criteria["domain"]) {
                $query->where("domain", "like", "%" . $criteria["domain"] . "%");
            }
            if ($criteria["username"]) {
                $query->where("username", $criteria["username"]);
            }
            if ($criteria["dedicatedip"]) {
                $query->where("dedicatedip", $criteria["dedicatedip"]);
            }
            if ($criteria["assignedips"]) {
                $query->where("assignedips", "like", "%" . $criteria["assignedips"] . "%");
            }
            if ($criteria["id"]) {
                $query->where("tblhosting.id", $criteria["id"]);
            }
            if ($criteria["subscriptionid"]) {
                $query->where("subscriptionid", $criteria["subscriptionid"]);
            }
            if ($criteria["notes"]) {
                $query->where("tblhosting.notes", "like", "%" . $criteria["notes"] . "%");
            }
            if ($criteria["customfieldvalue"]) {
                if ($criteria["customfield"]) {
                    $ids = \WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", (int) $criteria["customfield"])->where("value", "like", "%" . $criteria["customfieldvalue"] . "%")->pluck("relid");
                } else {
                    $ids = \WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->join("tblcustomfields", "tblcustomfields.id", "=", "tblcustomfieldsvalues.fieldid")->where("tblcustomfields.type", "product")->where("tblcustomfieldsvalues.value", "like", "%" . $criteria["customfieldvalue"] . "%")->pluck("tblcustomfieldsvalues.relid");
                }
                $query->whereIn("tblhosting.id", $ids);
            }
        }
        return $query;
    }
}

?>