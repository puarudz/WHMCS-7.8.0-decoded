<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Orders extends TableModel
{
    private $orderid = 0;
    private $orderdata = NULL;
    private $statusoutputs = NULL;
    public function _execute($criteria = NULL)
    {
        return $this->getOrders($criteria);
    }
    public function getOrders($criteria = array())
    {
        global $aInt;
        global $currency;
        $query = "FROM tblorders INNER JOIN tblclients ON tblclients.id=tblorders.userid";
        if (!empty($criteria["paymentstatus"])) {
            $query .= " INNER JOIN tblinvoices ON tblinvoices.id=tblorders.invoiceid";
        }
        $filters = $this->buildCriteria($criteria);
        if (count($filters)) {
            $query .= " WHERE " . implode(" AND ", $filters);
        }
        $result = full_query("SELECT COUNT(tblorders.id) " . $query);
        $data = mysql_fetch_array($result);
        $this->getPageObj()->setNumResults($data[0]);
        $query .= " ORDER BY tblorders." . $this->getPageObj()->getOrderBy() . " " . $this->getPageObj()->getSortDirection();
        $gateways = new Gateways();
        $invoices = new Invoices();
        $orders = array();
        $query = "SELECT tblorders.id,tblorders.ordernum,tblorders.userid,tblorders.date,tblorders.amount,tblorders.paymentmethod,tblorders.status,tblorders.invoiceid,tblorders.ipaddress,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid,tblclients.currency,(SELECT status FROM tblinvoices WHERE id=tblorders.invoiceid) AS invoicestatus " . $query . " LIMIT " . $this->getQueryLimit();
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $ordernum = $data["ordernum"];
            $userid = $data["userid"];
            $date = $data["date"];
            $amount = $data["amount"];
            $gateway = $data["paymentmethod"];
            $status = $data["status"];
            $invoiceid = $data["invoiceid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $companyname = $data["companyname"];
            $groupid = $data["groupid"];
            $currency = $data["currency"];
            $ipaddress = $data["ipaddress"];
            $invoicestatus = $data["invoicestatus"];
            $date = fromMySQLDate($date, 1);
            $paymentmethod = $gateways->getDisplayName($gateway);
            $statusformatted = $this->formatStatus($status);
            if ($invoiceid == "0") {
                $paymentstatus = "<span class=\"textgreen\">" . $aInt->lang("orders", "noinvoicedue") . "</span>";
            } else {
                if (!$invoicestatus) {
                    $paymentstatus = "<span class=\"textred\">Invoice Deleted</span>";
                } else {
                    if ($invoicestatus == "Paid") {
                        $paymentstatus = "<span class=\"textgreen\">" . $aInt->lang("status", "complete") . "</span>";
                    } else {
                        if ($invoicestatus == "Unpaid") {
                            $paymentstatus = "<span class=\"textred\">" . $aInt->lang("status", "incomplete") . "</span>";
                        } else {
                            $paymentstatus = $invoices->formatStatus($invoicestatus);
                        }
                    }
                }
            }
            $currency = getCurrency("", $currency);
            $amount = formatCurrency($amount);
            $clientname = $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid);
            $orders[] = array("id" => $id, "ordernum" => $ordernum, "date" => $date, "clientname" => $clientname, "gateway" => $gateway, "paymentmethod" => $paymentmethod, "amount" => $amount, "paymentstatus" => strip_tags($paymentstatus), "paymentstatusformatted" => $paymentstatus, "status" => $status, "statusformatted" => $statusformatted);
        }
        return $orders;
    }
    private function buildCriteria($criteria = array())
    {
        $filters = array();
        if (!empty($criteria["status"])) {
            if ($criteria["status"] == "Pending" || $criteria["status"] == "Active" || $criteria["status"] == "Cancelled") {
                $statusfilters = array();
                $where = array("show" . strtolower($criteria["status"]) => "1");
                $result = select_query("tblorderstatuses", "title", $where);
                while ($data = mysql_fetch_array($result)) {
                    $statusfilters[] = $data[0];
                }
                $filters[] = "tblorders.status IN (" . db_build_in_array($statusfilters) . ")";
            } else {
                $filters[] = "tblorders.status='" . db_escape_string($criteria["status"]) . "'";
            }
        }
        if (!empty($criteria["clientid"])) {
            $filters[] = "tblorders.userid='" . db_escape_string($criteria["clientid"]) . "'";
        }
        if (!empty($criteria["amount"])) {
            $filters[] = "tblorders.amount='" . db_escape_string($criteria["amount"]) . "'";
        }
        if (!empty($criteria["orderid"])) {
            $filters[] = "tblorders.id='" . db_escape_string($criteria["orderid"]) . "'";
        }
        if (!empty($criteria["ordernum"])) {
            $filters[] = "tblorders.ordernum='" . db_escape_string($criteria["ordernum"]) . "'";
        }
        if (!empty($criteria["orderip"])) {
            $filters[] = "tblorders.ipaddress='" . db_escape_string($criteria["orderip"]) . "'";
        }
        if (!empty($criteria["orderdate"])) {
            $dateRange = $criteria["orderdate"];
            $dateRange = Carbon::parseDateRangeValue($dateRange);
            $dateFrom = $dateRange["from"];
            $dateTo = $dateRange["to"];
            $filters[] = "tblorders.date >= '" . $dateFrom->toDateTimeString() . "'" . " AND tblorders.date <= '" . $dateTo->toDateTimeString() . "'";
        }
        if (!empty($criteria["clientname"])) {
            $filters[] = "concat(firstname,' ',lastname) LIKE '%" . db_escape_string($criteria["clientname"]) . "%'";
        }
        if (!empty($criteria["paymentstatus"])) {
            $filters[] = "tblinvoices.status='" . db_escape_string($criteria["paymentstatus"]) . "'";
        }
        return $filters;
    }
    public function getPendingCount()
    {
        return (int) Database\Capsule::table("tblorders")->join("tblorderstatuses", "tblorders.status", "=", "tblorderstatuses.title")->where("tblorderstatuses.showpending", "=", 1)->count("tblorders.id");
    }
    public function getStatuses()
    {
        $statuses = array();
        $result = select_query("tblorderstatuses", "title,color", "", "sortorder", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $statuses[$data["title"]] = "<span style=\"color:" . $data["color"] . "\">" . $data["title"] . "</span>";
        }
        $this->statusoutputs = $statuses;
        return $statuses;
    }
    public function formatStatus($status)
    {
        if (!$this->statusoutputs) {
            $this->getStatuses();
        }
        return array_key_exists($status, $this->statusoutputs) ? $this->statusoutputs[$status] : $status;
    }
    public function setID($orderid)
    {
        $this->orderid = (int) $orderid;
        $data = $this->loadData();
        return is_array($data) ? true : false;
    }
    public function loadData()
    {
        $result = select_query("tblorders", "", array("id" => $this->orderid));
        $this->orderdata = mysql_fetch_assoc($result);
        return $this->orderdata;
    }
    public function getData($var = "")
    {
        if (is_array($this->orderdata) && $var) {
            return isset($this->orderdata[$var]) ? $this->orderdata[$var] : "";
        }
    }
    public function getFraudResults()
    {
        $fraudmodule = $this->getData("fraudmodule");
        $fraud = new Module\Fraud();
        if ($fraud->load($fraudmodule)) {
            return $fraud->processResultsForDisplay($this->orderid, $this->getData("fraudoutput"));
        }
        return false;
    }
    public function delete($orderid = 0)
    {
        if (empty($orderid)) {
            if (empty($this->orderid)) {
                return false;
            }
            $orderid = $this->orderid;
        }
        $orderid = (int) $orderid;
        $result = select_query("tblorders", "userid,invoiceid", array("id" => $orderid));
        $data = mysql_fetch_array($result);
        if (empty($data)) {
            return false;
        }
        $userid = $data["userid"];
        $invoiceid = $data["invoiceid"];
        run_hook("DeleteOrder", array("orderid" => $orderid));
        delete_query("tblhostingconfigoptions", "relid IN (SELECT id FROM tblhosting WHERE orderid=" . $orderid . ")");
        delete_query("tblaffiliatesaccounts", "relid IN (SELECT id FROM tblhosting WHERE orderid=" . $orderid . ")");
        delete_query("tblhosting", array("orderid" => $orderid));
        delete_query("tblhostingaddons", array("orderid" => $orderid));
        delete_query("tbldomains", array("orderid" => $orderid));
        delete_query("tblupgrades", array("orderid" => $orderid));
        delete_query("tblorders", array("id" => $orderid));
        delete_query("tblinvoices", array("id" => $invoiceid));
        delete_query("tblinvoiceitems", array("invoiceid" => $invoiceid));
        logActivity("Deleted Order - Order ID: " . $orderid, $userid);
        return true;
    }
    public function setCancelled($orderid = 0)
    {
        if (empty($orderid)) {
            $orderid = $this->orderid;
        }
        return $this->changeStatus($orderid, "Cancelled");
    }
    public function setFraud($orderid = 0)
    {
        if (empty($orderid)) {
            $orderid = $this->orderid;
        }
        return $this->changeStatus($orderid, "Fraud");
    }
    public function setPending($orderid = 0)
    {
        if (empty($orderid)) {
            $orderid = $this->orderid;
        }
        return $this->changeStatus($orderid, "Pending");
    }
    private function changeStatus($orderid, $status)
    {
        if (empty($orderid)) {
            return false;
        }
        $orderid = (int) $orderid;
        if (!get_query_val("tblorders", "id", array("id" => $orderid))) {
            return false;
        }
        if ($status == "Cancelled") {
            run_hook("CancelOrder", array("orderid" => $orderid));
        } else {
            if ($status == "Fraud") {
                run_hook("FraudOrder", array("orderid" => $orderid));
            } else {
                if ($status == "Pending") {
                    run_hook("PendingOrder", array("orderid" => $orderid));
                }
            }
        }
        update_query("tblorders", array("status" => $status), array("id" => $orderid));
        if ($status == "Cancelled" || $status == "Fraud") {
            $result = select_query("tblhosting", "tblhosting.id,tblhosting.userid,tblhosting.domainstatus,tblproducts.servertype," . "tblhosting.packageid,tblproducts.stockcontrol,tblproducts.qty", array("orderid" => $orderid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
            while ($data = mysql_fetch_array($result)) {
                $productid = $data["id"];
                $prodstatus = $data["domainstatus"];
                $module = $data["servertype"];
                $packageid = $data["packageid"];
                $stockcontrol = $data["stockcontrol"];
                $qty = $data["qty"];
                if ($module && ($prodstatus == "Active" || $prodstatus == "Suspended")) {
                    logActivity("Running Module Terminate on Order Cancel", $data["userid"]);
                    if (!isValidforPath($module) || !file_exists(ROOTDIR . "/modules/servers/" . $module . "/" . $module . ".php")) {
                        throw new Exception\Fatal("Invalid Server Module Name");
                    }
                    require_once ROOTDIR . "/modules/servers/" . $module . "/" . $module . ".php";
                    if (!function_exists("ServerTerminateAccount")) {
                        require ROOTDIR . "/includes/modulefunctions.php";
                    }
                    $moduleresult = ServerTerminateAccount($productid);
                    if ($moduleresult == "success") {
                        update_query("tblhosting", array("domainstatus" => $status), array("id" => $productid));
                        if ($stockcontrol) {
                            update_query("tblproducts", array("qty" => "+1"), array("id" => $packageid));
                        }
                    }
                } else {
                    update_query("tblhosting", array("domainstatus" => $status), array("id" => $productid));
                    if ($stockcontrol) {
                        update_query("tblproducts", array("qty" => "+1"), array("id" => $packageid));
                    }
                }
            }
        } else {
            update_query("tblhosting", array("domainstatus" => $status), array("orderid" => $orderid));
        }
        update_query("tblhostingaddons", array("status" => $status), array("orderid" => $orderid));
        if ($status == "Pending") {
            $result = select_query("tbldomains", "id,type", array("orderid" => $orderid));
            while ($data = mysql_fetch_assoc($result)) {
                if ($data["type"] == "Transfer") {
                    $domainStatus = "Pending Transfer";
                } else {
                    $domainStatus = "Pending";
                }
                update_query("tbldomains", array("status" => $domainStatus), array("id" => $data["id"]));
            }
        } else {
            update_query("tbldomains", array("status" => $status), array("orderid" => $orderid));
        }
        $result = select_query("tblorders", "userid,invoiceid", array("id" => $orderid));
        $data = mysql_fetch_array($result);
        $userid = $data["userid"];
        $invoiceid = $data["invoiceid"];
        if ($status == "Pending") {
            update_query("tblinvoices", array("status" => "Unpaid"), array("id" => $invoiceid, "status" => "Cancelled"));
        } else {
            update_query("tblinvoices", array("status" => "Cancelled"), array("id" => $invoiceid, "status" => "Unpaid"));
            run_hook("InvoiceCancelled", array("invoiceid" => $invoiceid));
        }
        logActivity("Order Status set to " . $status . " - Order ID: " . $orderid, $userid);
        return true;
    }
    public function getItems()
    {
        global $aInt;
        $orderid = $this->orderid;
        $items = array();
        if (empty($orderid)) {
            return $items;
        }
        $result = select_query("tblhosting", "", array("orderid" => $orderid));
        while ($data = mysql_fetch_array($result)) {
            $hostingid = $data["id"];
            $domain = $data["domain"];
            $billingcycle = $data["billingcycle"];
            $hostingstatus = $data["domainstatus"];
            $firstpaymentamount = formatCurrency($data["firstpaymentamount"]);
            $recurringamount = $data["amount"];
            $packageid = $data["packageid"];
            $server = $data["server"];
            $regdate = $data["regdate"];
            $nextduedate = $data["nextduedate"];
            $serverusername = $data["username"];
            $serverpassword = decrypt($data["password"]);
            $result2 = select_query("tblproducts", "tblproducts.name,tblproducts.type,tblproducts.welcomeemail,tblproducts.autosetup," . "tblproducts.servertype,tblproductgroups.id AS group_id,tblproductgroups.name as group_name", array("tblproducts.id" => $packageid), "", "", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
            $data = mysql_fetch_array($result2);
            $groupname = Product\Group::getGroupName($data["group_id"], $data["group_name"]);
            $productname = Product\Product::getProductName($packageid, $data["name"]);
            $producttype = $data["type"];
            $welcomeemail = $data["welcomeemail"];
            $autosetup = $data["autosetup"];
            $servertype = $data["servertype"];
            switch ($producttype) {
                case "reselleraccount":
                    $type = $aInt->lang("orders", "resellerhosting");
                    break;
                case "server":
                    $type = $aInt->lang("orders", "server");
                    break;
                case "other":
                    $type = $aInt->lang("orders", "other");
                    break;
                case "hostingaccount":
                default:
                    $type = $aInt->lang("orders", "sharedhosting");
            }
            $items[] = array("type" => "product", "producttype" => $type, "description" => $groupname . " - " . $productname, "domain" => $domain, "billingcycle" => $aInt->lang("billingcycles", str_replace(array("-", "account", " "), "", strtolower($billingcycle))), "amount" => $firstpaymentamount, "status" => $aInt->lang("status", strtolower($hostingstatus)));
        }
        $predefinedaddons = array();
        $result = select_query("tbladdons", "", "");
        while ($data = mysql_fetch_array($result)) {
            $addon_id = $data["id"];
            $addon_name = $data["name"];
            $addon_welcomeemail = $data["welcomeemail"];
            $predefinedaddons[$addon_id] = array("name" => $addon_name, "welcomeemail" => $addon_welcomeemail);
        }
        $result = select_query("tblhostingaddons", "", array("orderid" => $orderid));
        while ($data = mysql_fetch_array($result)) {
            $aid = $data["id"];
            $hostingid = $data["hostingid"];
            $addonid = $data["addonid"];
            $name = $data["name"];
            $billingcycle2 = $data["billingcycle"];
            $addonamount = $data["recurring"] + $data["setupfee"];
            $addonstatus = $data["status"];
            $regdate = $data["regdate"];
            $nextduedate = $data["nextduedate"];
            $addonamount = formatCurrency($addonamount);
            if (!$name) {
                $name = $predefinedaddons[$addonid]["name"];
            }
            $items[] = array("type" => "addon", "producttype" => $aInt->lang("orders", "addon"), "description" => $name, "domain" => "", "billingcycle" => $aInt->lang("billingcycles", str_replace(array("-", "account", " "), "", strtolower($billingcycle2))), "amount" => $addonamount, "status" => $aInt->lang("status", strtolower($addonstatus)));
        }
        $result = select_query("tbldomains", "", array("orderid" => $orderid));
        while ($data = mysql_fetch_array($result)) {
            $domainid = $data["id"];
            $type = $data["type"];
            $domain = $data["domain"];
            $registrationperiod = $data["registrationperiod"];
            $status = $data["status"];
            $regdate = $data["registrationdate"];
            $nextduedate = $data["nextduedate"];
            $domainamount = formatCurrency($data["firstpaymentamount"]);
            $domainregistrar = $data["registrar"];
            $dnsmanagement = $data["dnsmanagement"];
            $emailforwarding = $data["emailforwarding"];
            $idprotection = $data["idprotection"];
            $type = $aInt->lang("domains", strtolower($type));
            if ($dnsmanagement) {
                $type .= " + " . $aInt->lang("domains", "dnsmanagement");
            }
            if ($emailforwarding) {
                $type .= " + " . $aInt->lang("domains", "emailforwarding");
            }
            if ($idprotection) {
                $type .= " + " . $aInt->lang("domains", "idprotection");
            }
            $items[] = array("type" => "domain", "producttype" => $aInt->lang("fields", "domain"), "description" => $type, "domain" => $domain, "billingcycle" => $registrationperiod . " " . $aInt->lang("domains", "year" . (1 < $registrationperiod) ? "s" : ""), "amount" => $domainamount, "status" => $aInt->lang("status", strtolower(str_replace(" ", "", $status))));
        }
        $result = select_query("tblupgrades", "", array("orderid" => $orderid));
        while ($data = mysql_fetch_array($result)) {
            $upgradeid = $data["id"];
            $type = $data["type"];
            $relid = $data["relid"];
            $originalvalue = $data["originalvalue"];
            $newvalue = $data["newvalue"];
            $upgradeamount = formatCurrency($data["amount"]);
            $newrecurringamount = $data["recurringchange"];
            $status = $data["status"];
            $paid = $data["paid"];
            $result2 = select_query("tblhosting", "tblproducts.name AS product_name,domain,userid,packageid", array("tblhosting.id" => $relid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
            $data = mysql_fetch_array($result2);
            $userid = $data["userid"];
            $productname = Product\Product::getProductName($data["packageid"], $data["product_name"]);
            $domain = $data["domain"];
            if ($type == "package") {
                $oldpackagename = Product\Product::getProductName($originalvalue);
                $newvalue = explode(",", $newvalue);
                $newpackageid = $newvalue[0];
                $newpackagename = Product\Product::getProductName($newpackageid);
                $newbillingcycle = $newvalue[1];
                $details = "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $oldpackagename . " => " . $newpackagename . "</a><br />";
                if ($domain) {
                    $details .= $domain;
                }
                $items[] = array("type" => "upgrade", "producttype" => "Product Upgrade", "description" => $details, "domain" => "", "billingcycle" => $aInt->lang("billingcycles", $newbillingcycle), "amount" => $upgradeamount, "status" => $aInt->lang("status", strtolower($status)));
            } else {
                if ($type == "configoptions") {
                    $tempvalue = explode("=>", $originalvalue);
                    list($configid, $oldoptionid) = $tempvalue;
                    $result2 = select_query("tblproductconfigoptions", "", array("id" => $configid));
                    $data = mysql_fetch_array($result2);
                    $configname = $data["optionname"];
                    $optiontype = $data["optiontype"];
                    if ($optiontype == 1 || $optiontype == 2) {
                        $result2 = select_query("tblproductconfigoptionssub", "", array("id" => $oldoptionid));
                        $data = mysql_fetch_array($result2);
                        $oldoptionname = $data["optionname"];
                        $result2 = select_query("tblproductconfigoptionssub", "", array("id" => $newvalue));
                        $data = mysql_fetch_array($result2);
                        $newoptionname = $data["optionname"];
                    } else {
                        if ($optiontype == 3) {
                            if ($oldoptionid) {
                                $oldoptionname = "Yes";
                                $newoptionname = "No";
                            } else {
                                $oldoptionname = "No";
                                $newoptionname = "Yes";
                            }
                        } else {
                            if ($optiontype == 4) {
                                $result2 = select_query("tblproductconfigoptionssub", "", array("configid" => $configid));
                                $data = mysql_fetch_array($result2);
                                $optionname = $data["optionname"];
                                $oldoptionname = $oldoptionid;
                                $newoptionname = $newvalue . " x " . $optionname;
                            }
                        }
                    }
                    $details = "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $relid . "\">" . $productname;
                    $details .= " - " . $domain;
                    $details .= "</a><br />" . $configname . ": " . $oldoptionname . " => " . $newoptionname . "<br>";
                    $items[] = array("type" => "upgrade", "producttype" => "Options Upgrade", "description" => $details, "domain" => "", "billingcycle" => "", "amount" => $upgradeamount, "status" => $aInt->lang("status", strtolower($status)));
                }
            }
        }
        return $items;
    }
}

?>