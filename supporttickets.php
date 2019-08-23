<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/ticketfunctions.php";
$pagetitle = $_LANG["supportticketspagetitle"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"supporttickets.php\">" . $_LANG["supportticketspagetitle"] . "</a>";
$templatefile = "supportticketslist";
$pageicon = "images/supporttickets_big.gif";
$displayTitle = Lang::trans("clientareanavsupporttickets");
$tagline = Lang::trans("ticketsyourhistory");
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
if (isset($_SESSION["uid"])) {
    checkContactPermission("tickets");
    $usingsupportmodule = false;
    if ($CONFIG["SupportModule"]) {
        if (!isValidforPath($CONFIG["SupportModule"])) {
            exit("Invalid Support Module");
        }
        $supportmodulepath = "modules/support/" . $CONFIG["SupportModule"] . "/supporttickets.php";
        if (file_exists($supportmodulepath)) {
            $usingsupportmodule = true;
            $templatefile = "";
            require $supportmodulepath;
            outputClientArea($templatefile);
            exit;
        }
    }
    $result = select_query("tbltickets", "COUNT(id)", array("userid" => (int) WHMCS\Session::get("uid"), "status" => array("sqltype" => "NEQ", "value" => "Closed"), "merged_ticket_id" => "0"));
    $data = mysql_fetch_array($result);
    $smartyvalues["numopentickets"] = $data[0];
    $ticketStatuses = array();
    $result = select_query("tblticketstatuses", "title", "", "sortorder", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $ticketStatuses[$data[0]] = 0;
    }
    if ($searchterm = $whmcs->get_req_var("searchterm")) {
        check_token();
        $smartyvalues["q"] = $searchterm;
        $smartyvalues["searchterm"] = $smartyvalues["q"];
        $searchterm = mysql_real_escape_string(trim($searchterm));
        $where = "tbltickets.userid=" . (int) WHMCS\Session::get("uid") . " AND (tbltickets.tid='" . $searchterm . "' OR (tbltickets.title LIKE '%" . $searchterm . "%' " . "OR tbltickets.message LIKE '%" . $searchterm . "%' OR tblticketreplies.message LIKE '%" . $searchterm . "%'))";
        $result = full_query("SELECT COUNT(DISTINCT tbltickets.id) FROM tbltickets LEFT JOIN tblticketreplies ON tbltickets.id = tblticketreplies.tid WHERE " . $where);
        $data = mysql_fetch_array($result);
        $numtickets = $data[0];
        $smartyvalues["numtickets"] = $numtickets;
        list($orderby, $sort, $limit) = clientAreaTableInit("tickets", "lastreply", "DESC", $numtickets);
        $smartyvalues["orderby"] = $orderby;
        $smartyvalues["sort"] = strtolower($sort);
        if ($orderby == "date") {
            $orderby = "tbltickets.date";
        } else {
            if ($orderby == "dept") {
                $orderby = "did";
            } else {
                if ($orderby == "subject") {
                    $orderby = "title";
                } else {
                    if ($orderby == "status") {
                        $orderby = "status";
                    } else {
                        if ($orderby == "urgency") {
                            $orderby = "urgency";
                        } else {
                            if ($orderby == "priority") {
                                $orderby = "urgency";
                            } else {
                                $orderby = "lastreply";
                            }
                        }
                    }
                }
            }
        }
        if (!in_array($sort, array("ASC", "DESC"))) {
            $sort = "ASC";
        }
        if (strpos($limit, ",")) {
            $limit = explode(",", $limit);
            $limit = (int) $limit[0] . "," . (int) $limit[1];
        } else {
            $limit = (int) $limit;
        }
        $tickets = array();
        $result = full_query("SELECT DISTINCT tbltickets.id FROM tbltickets LEFT JOIN tblticketreplies ON tbltickets.id = tblticketreplies.tid WHERE " . $where . " ORDER BY " . $orderby . " " . $sort . " LIMIT " . $limit);
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $result2 = select_query("tbltickets", "", array("userid" => $_SESSION["uid"], "id" => $id));
            $data = mysql_fetch_array($result2);
            $tid = $data["tid"];
            $c = $data["c"];
            $deptid = $data["did"];
            $date = $data["date"];
            $date = fromMySQLDate($date, 1, 1);
            $subject = $data["title"];
            $status = $data["status"];
            $ticketStatuses[$status]++;
            $urgency = $data["urgency"];
            $lastreply = $data["lastreply"];
            $lastreply = fromMySQLDate($lastreply, 1, 1);
            $clientunread = $data["clientunread"];
            $htmlFormattedStatus = getStatusColour($status);
            $dept = getDepartmentName($deptid);
            $urgency = $_LANG["supportticketsticketurgency" . strtolower($urgency)];
            $statusColor = NULL;
            if (!in_array($status, array("Open", "Answered", "Customer-Reply", "Closed"))) {
                $statusColor = getStatusColour($status, false);
            }
            $tickets[] = array("id" => $id, "tid" => $tid, "c" => $c, "date" => $date, "department" => $dept, "subject" => $subject, "status" => $htmlFormattedStatus, "statusClass" => WHMCS\View\Helper::generateCssFriendlyClassName($status), "statusColor" => $statusColor, "urgency" => $urgency, "lastreply" => $lastreply, "unread" => $clientunread);
        }
    } else {
        $result = select_query("tbltickets", "COUNT(id)", array("userid" => $_SESSION["uid"], "merged_ticket_id" => "0"));
        $data = mysql_fetch_array($result);
        $numtickets = $data[0];
        $smartyvalues["numtickets"] = $numtickets;
        list($orderby, $sort, $limit) = clientAreaTableInit("tickets", "lastreply", "DESC", $numtickets);
        $smartyvalues["orderby"] = $orderby;
        $smartyvalues["sort"] = strtolower($sort);
        if ($orderby == "date") {
            $orderby = "date";
        } else {
            if ($orderby == "dept") {
                $orderby = "deptname";
            } else {
                if ($orderby == "subject") {
                    $orderby = "title";
                } else {
                    if ($orderby == "status") {
                        $orderby = "status";
                    } else {
                        if ($orderby == "urgency") {
                            $orderby = "urgency";
                        } else {
                            if ($orderby == "priority") {
                                $orderby = "urgency";
                            } else {
                                $orderby = "lastreply";
                            }
                        }
                    }
                }
            }
        }
        $tickets = array();
        $result = select_query("tbltickets", "tbltickets.*,tblticketdepartments.name AS deptname", array("userid" => $_SESSION["uid"], "merged_ticket_id" => "0"), $orderby, $sort, $limit, " tblticketdepartments ON tblticketdepartments.id=tbltickets.did");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $tid = $data["tid"];
            $c = $data["c"];
            $deptid = $data["did"];
            $date = $data["date"];
            $normalisedDate = $date;
            $date = fromMySQLDate($date, 1, 1);
            $subject = $data["title"];
            $status = $data["status"];
            $ticketStatuses[$status]++;
            $urgency = $data["urgency"];
            $lastreply = $data["lastreply"];
            $normalisedLastReply = $lastreply;
            $lastreply = fromMySQLDate($lastreply, 1, 1);
            $clientunread = $data["clientunread"];
            $htmlFormattedStatus = getStatusColour($status);
            $dept = getDepartmentName($deptid);
            $urgency = $_LANG["supportticketsticketurgency" . strtolower($urgency)];
            $statusColor = NULL;
            if (!in_array($status, array("Open", "Answered", "Customer-Reply", "Closed"))) {
                $statusColor = getStatusColour($status, false);
            }
            $tickets[] = array("id" => $id, "tid" => $tid, "c" => $c, "date" => $date, "normalisedDate" => $normalisedDate, "department" => $dept, "subject" => $subject, "status" => $htmlFormattedStatus, "statusClass" => WHMCS\View\Helper::generateCssFriendlyClassName($status), "statusColor" => $statusColor, "urgency" => $urgency, "lastreply" => $lastreply, "normalisedLastReply" => $normalisedLastReply, "unread" => $clientunread);
        }
    }
    foreach ($ticketStatuses as $status => $count) {
        if ($count == 0 && !in_array($status, array("Open", "Answered", "Customer-Reply", "Closed"))) {
            unset($ticketStatuses[$status]);
        }
    }
    $smarty->assign("tickets", $tickets);
    $smartyvalues = array_merge($smartyvalues, clientAreaTablePageNav($numtickets));
} else {
    $goto = "supporttickets";
    include "login.php";
}
Menu::addContext("ticketStatusCounts", $ticketStatuses);
Menu::primarySidebar("ticketList");
Menu::secondarySidebar("ticketList");
outputClientArea($templatefile, false, array("ClientAreaPageSupportTickets"));

?>