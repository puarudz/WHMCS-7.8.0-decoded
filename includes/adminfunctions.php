<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getAdminPermsArray()
{
    return WHMCS\User\Admin\Permission::all();
}
function checkPermission($action, $noredirect = "")
{
    static $AdminRoleID = 0;
    static $AdminRolePerms = array();
    $permid = array_search($action, getadminpermsarray());
    if (isset($_SESSION["adminid"])) {
        if (!$AdminRoleID) {
            $result = select_query("tbladmins", "roleid", array("id" => $_SESSION["adminid"]));
            $data = mysql_fetch_array($result);
            $roleid = $data["roleid"];
            $AdminRoleID = $roleid;
        }
        if (!count($AdminRolePerms)) {
            $result = select_query("tbladminperms", "permid", array("roleid" => $AdminRoleID));
            while ($data = mysql_fetch_array($result)) {
                $AdminRolePerms[] = $data[0];
            }
        }
    }
    $match = in_array($permid, $AdminRolePerms) ? true : false;
    if ($noredirect) {
        if ($match) {
            return true;
        }
        return false;
    }
    if (!$match) {
        redir("permid=" . $permid, "accessdenied.php");
    }
}
function infoBox($title, $description, $status = "info")
{
    global $infobox;
    if ($status == "error" || $status == "success") {
        $class = $status . "box";
    } else {
        $class = "infobox";
    }
    $infobox = sprintf("<div class=\"%s\"><strong><span class=\"title\">%s</span></strong><br />%s</div>", $class, $title, $description);
    return $infobox;
}
function getAdminName($adminId = 0)
{
    static $adminNames = NULL;
    if (!$adminNames) {
        $adminNames = array();
    }
    $adminId = $adminId ?: WHMCS\Session::get("adminid");
    if (!empty($adminNames[$adminId])) {
        return $adminNames[$adminId];
    }
    $data = get_query_vals("tbladmins", "firstname,lastname", array("id" => $adminId));
    $adminName = trim($data["firstname"] . " " . $data["lastname"]);
    $adminNames[$adminId] = $adminName;
    return $adminName;
}
function getAdminHomeStats($type = "")
{
    global $currency;
    $stats = array();
    $currency = getCurrency(0, 1);
    if (!$type || in_array($type, array("income", "api"))) {
        $todaysincome = get_query_val("tblaccounts", "SUM((amountin-fees-amountout)/rate)", "date LIKE '" . date("Y-m-d") . "%'");
        $stats["income"]["today"] = formatCurrency($todaysincome);
        $todaysincome = get_query_val("tblaccounts", "SUM((amountin-fees-amountout)/rate)", "date LIKE '" . date("Y-m-") . "%'");
        $stats["income"]["thismonth"] = formatCurrency($todaysincome);
        $todaysincome = get_query_val("tblaccounts", "SUM((amountin-fees-amountout)/rate)", "date LIKE '" . date("Y-") . "%'");
        $stats["income"]["thisyear"] = formatCurrency($todaysincome);
        $todaysincome = get_query_val("tblaccounts", "SUM((amountin-fees-amountout)/rate)", "");
        $stats["income"]["alltime"] = formatCurrency($todaysincome);
        if ($type == "income") {
            return $stats;
        }
    }
    $result = full_query("SELECT SUM(total)-COALESCE(SUM((SELECT SUM(amountin) FROM tblaccounts WHERE tblaccounts.invoiceid=tblinvoices.id)),0) FROM tblinvoices WHERE tblinvoices.status='Unpaid' AND duedate<'" . date("Ymd") . "'");
    $data = mysql_fetch_array($result);
    list($overdueinvoices, $stats["invoices"]["overduebalance"]) = $data;
    $result = full_query("SELECT COUNT(*) FROM tblcancelrequests INNER JOIN tblhosting ON tblhosting.id=tblcancelrequests.relid WHERE (tblhosting.domainstatus!='Cancelled' AND tblhosting.domainstatus!='Terminated')");
    $data = mysql_fetch_array($result);
    $stats["cancellations"]["pending"] = $data[0];
    $stats["orders"]["today"]["cancelled"] = 0;
    $stats["orders"]["today"]["pending"] = $stats["orders"]["today"]["cancelled"];
    $stats["orders"]["today"]["fraud"] = $stats["orders"]["today"]["pending"];
    $stats["orders"]["today"]["active"] = $stats["orders"]["today"]["fraud"];
    $query = "SELECT status,COUNT(*) FROM tblorders WHERE date LIKE '" . date("Y-m-d") . "%' GROUP BY status";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $stats["orders"]["today"][preg_replace("/[^a-z0-9_]+/", "_", strtolower($data[0]))] = $data[1];
    }
    $stats["orders"]["today"]["total"] = $stats["orders"]["today"]["active"] + $stats["orders"]["today"]["fraud"] + $stats["orders"]["today"]["pending"] + $stats["orders"]["today"]["cancelled"];
    $stats["orders"]["yesterday"]["cancelled"] = 0;
    $stats["orders"]["yesterday"]["pending"] = $stats["orders"]["yesterday"]["cancelled"];
    $stats["orders"]["yesterday"]["fraud"] = $stats["orders"]["yesterday"]["pending"];
    $stats["orders"]["yesterday"]["active"] = $stats["orders"]["yesterday"]["fraud"];
    $query = "SELECT status,COUNT(*) FROM tblorders WHERE date LIKE '" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"))) . "%' GROUP BY status";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $stats["orders"]["yesterday"][preg_replace("/[^a-z0-9_]+/", "_", strtolower($data[0]))] = $data[1];
    }
    $stats["orders"]["yesterday"]["total"] = $stats["orders"]["yesterday"]["active"] + $stats["orders"]["yesterday"]["fraud"] + $stats["orders"]["yesterday"]["pending"] + $stats["orders"]["yesterday"]["cancelled"];
    $query = "SELECT COUNT(*) FROM tblorders WHERE date LIKE '" . date("Y-m-") . "%'";
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $stats["orders"]["thismonth"]["total"] = $data[0];
    $query = "SELECT COUNT(*) FROM tblorders WHERE date LIKE '" . date("Y-") . "%'";
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $stats["orders"]["thisyear"]["total"] = $data[0];
    global $disable_admin_ticket_page_counts;
    if (!$disable_admin_ticket_page_counts) {
        $ticketStats = localAPI("GetTicketCounts", $type == "api" ? array("includeCountsByStatus" => true) : array());
        $stats["tickets"]["allactive"] = $ticketStats["allActive"];
        $stats["tickets"]["awaitingreply"] = $ticketStats["awaitingReply"];
        $stats["tickets"]["flaggedtickets"] = $ticketStats["flaggedTickets"];
        foreach ($ticketStats["status"] as $status => $count) {
            $stats["tickets"][$status] = $count;
        }
    }
    $query = "SELECT COUNT(*) FROM tbltodolist WHERE status!='Completed' AND status!='Postponed' AND duedate<='" . date("Y-m-d") . "'";
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $stats["todoitems"]["due"] = $data[0];
    $query = "SELECT COUNT(*) FROM tblnetworkissues WHERE status!='Scheduled' AND status!='Resolved'";
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $stats["networkissues"]["open"] = $data[0];
    $result = select_query("tblbillableitems", "COUNT(*)", array("invoicecount" => "0"));
    $data = mysql_fetch_array($result);
    $stats["billableitems"]["uninvoiced"] = $data[0];
    $result = select_query("tblquotes", "COUNT(*)", array("validuntil" => array("sqltype" => ">", "value" => date("Ymd"))));
    $data = mysql_fetch_array($result);
    $stats["quotes"]["valid"] = $data[0];
    return $stats;
}
function replacePasswordWithMasks($password)
{
    if (0 < strlen($password)) {
        return str_pad("", strlen($password), "*");
    }
    return "";
}
function hasMaskedPasswordChanged($newPassword, $originalPassword)
{
    $passwordInputIsOnlyMask = str_replace("*", "", $newPassword) == "";
    $passwordInputIsMaskExactlyAsLongAsPreviousPassword = strlen($newPassword) == strlen($originalPassword);
    $previousPasswordIsOnlyMaskMarks = str_replace("*", "", $originalPassword) == "";
    if (!$originalPassword && $newPassword || !($passwordInputIsMaskExactlyAsLongAsPreviousPassword && $passwordInputIsOnlyMask) || $originalPassword && !$passwordInputIsMaskExactlyAsLongAsPreviousPassword && !$passwordInputIsOnlyMask) {
        return true;
    }
    return false;
}
function interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword)
{
    if (!$newPassword) {
        return "";
    }
    if (hasmaskedpasswordchanged($newPassword, $originalPassword)) {
        return encrypt(WHMCS\Input\Sanitize::decode($newPassword));
    }
    return false;
}
function logAdminActivity($description)
{
    logActivity($description);
}

?>