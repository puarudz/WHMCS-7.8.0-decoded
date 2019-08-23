<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Addon\Table;

class Addon extends \WHMCS\TableModel
{
    public function _execute($criteria = NULL)
    {
        return $this->getAddons($criteria);
    }
    protected function getAddons(array $criteria = NULL)
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
            } else {
                if ($orderBy == "addon") {
                    $orderBy = "tblhostingaddons.name";
                }
            }
        }
        $query->orderBy($orderBy, $this->getPageObj()->getSortDirection())->limit($this->getRecordLimit())->offset($this->getRecordOffset());
        $result = $query->get(array("tblhostingaddons.*", "tblhostingaddons.name AS addonname", "tblhosting.domain", "tblhosting.userid", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid", "tblclients.currency", "tblproducts.name", "tblproducts.type"));
        return json_decode(json_encode($result), true);
    }
    private function startQuery(array $criteria = NULL)
    {
        $query = \WHMCS\Database\Capsule::table("tblhostingaddons")->join("tblclients", "tblclients.id", "=", "tblhostingaddons.userid")->join("tblhosting", "tblhosting.id", "=", "tblhostingaddons.hostingid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid");
        if (is_array($criteria)) {
            if ($criteria["clientname"]) {
                $query->where(\WHMCS\Database\Capsule::raw("concat(firstname, ' ', lastname)"), "like", "%" . $criteria["clientname"] . "%");
            }
            if ($criteria["addon"]) {
                if (is_numeric($criteria["addon"])) {
                    $query->where("tblhostingaddons.addonid", $criteria["addon"]);
                } else {
                    $query->where("tblhostingaddons.name", $criteria["addon"]);
                }
            }
            if ($criteria["type"]) {
                $query->where("tblproducts.type", $criteria["type"]);
            }
            if ($criteria["package"]) {
                $query->where("tblproducts.id", $criteria["package"]);
            }
            if ($criteria["billingcycle"]) {
                $query->where("tblhostingaddons.billingcycle", $criteria["billingcycle"]);
            }
            if ($criteria["server"]) {
                $query->where(function (\Illuminate\Database\Query\Builder $queryFunction) use($criteria) {
                    $queryFunction->where("tblhostingaddons.server", $criteria["server"])->orWhere("tblhosting.server", $criteria["server"]);
                });
            }
            if ($criteria["paymentmethod"]) {
                $query->where("tblhostingaddons.paymentmethod", $criteria["paymentmethod"]);
            }
            if ($criteria["status"]) {
                $query->where("tblhostingaddons.status", $criteria["status"]);
            }
            if ($criteria["domain"]) {
                $query->where("tblhosting.domain", "like", "%" . $criteria["domain"] . "%");
            }
            if ($criteria["customfieldvalue"]) {
                if ($criteria["customfield"]) {
                    $ids = \WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", (int) $criteria["customfield"])->where("value", "like", "%" . $criteria["customfieldvalue"] . "%")->pluck("relid");
                } else {
                    $ids = \WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->join("tblcustomfields", "tblcustomfields.id", "=", "tblcustomfieldsvalues.fieldid")->where("tblcustomfields.type", "addon")->where("tblcustomfieldsvalues.value", "like", "%" . $criteria["customfieldvalue"] . "%")->pluck("tblcustomfieldsvalues.relid");
                }
                $query->whereIn("tblhostingaddons.id", $ids);
            }
        }
        return $query;
    }
}

?>