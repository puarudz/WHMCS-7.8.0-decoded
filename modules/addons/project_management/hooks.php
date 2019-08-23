<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
add_hook("ClientAreaPrimaryNavbar", -1, function ($primaryNavbar) {
    $client = Menu::context("client");
    if (is_null($client)) {
        return false;
    }
    $clientAccessEnabled = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "project_management")->where("setting", "=", "clientenable")->first(array("value"));
    if (!(bool) $clientAccessEnabled->value) {
        return false;
    }
    $primaryNavbar->addChild("pm-addon-overview", array("label" => Lang::trans("clientareaprojects"), "uri" => "index.php?m=project_management", "order" => "65"));
});
add_hook("ClientAreaHomepagePanels", -1, function (WHMCS\View\Menu\Item $homePagePanels) {
    $clientAccessEnabled = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "project_management")->where("setting", "=", "clientenable")->first(array("value"));
    if (!(bool) $clientAccessEnabled->value) {
        return false;
    }
    $client = Menu::context("client");
    $projects = array();
    $completedStatuses = get_query_val("tbladdonmodules", "value", array("module" => "project_management", "setting" => "completedstatuses"));
    $result = select_query("mod_project", "", "userid=" . (int) $client->id . " AND status NOT IN (" . db_build_in_array(explode(",", $completedStatuses)) . ")", "lastmodified", "DESC");
    while ($data = mysql_fetch_array($result)) {
        $projects[] = array("id" => $data["id"], "title" => $data["title"], "lastmodified" => fromMySQLDate($data["lastmodified"], 1, 1), "status" => $data["status"]);
    }
    if (count($projects) == 0) {
        return NULL;
    }
    $projectPanel = $homePagePanels->addChild("pm-addon", array("name" => "Project Management Addon Active Projects", "label" => Lang::trans("projectManagement.activeProjects"), "icon" => "fas fa-calendar-alt", "order" => "225", "extras" => array("color" => "silver", "btn-link" => "index.php?m=project_management", "btn-text" => Lang::trans("manage"), "btn-icon" => "fas fa-arrow-right")));
    foreach ($projects as $i => $project) {
        $projectPanel->addChild("pm-addon-" . $project["id"], array("label" => $project["title"] . " <span class=\"label status-" . strtolower(str_replace(" ", "", $project["status"])) . "\">" . $project["status"] . "</span><br />" . "<small>" . Lang::trans("supportticketsticketlastupdated") . ": " . $project["lastmodified"] . "</small>", "uri" => "index.php?m=project_management&a=view&id=" . $project["id"], "order" => ($i + 1) * 10));
    }
});
add_hook("AdminAreaClientSummaryActionLinks", 1, "hook_project_management_csoactions");
add_hook("AdminAreaPage", 1, function ($vars) {
    $jQueryCode = $vars["jquerycode"];
    if ($vars["filename"] == "supporttickets") {
        $action = App::get_req_var("action");
        $ticketId = (int) App::get_req_var("id");
        if (($action == "viewticket" || $action == "view") && $ticketId) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . "project_management" . DIRECTORY_SEPARATOR . "project_management.php";
            if (project_management_checkperm("Create New Projects")) {
                $jQueryCode .= "jQuery('ul.nav.nav-tabs.admin-tabs').append(\n    '<li>'\n        + '<a id=\"createProject\" href=\"#\" onclick=\"createnewproject();return false;\" class=\"create\">'\n        + 'Create New Project</a></li>'\n);";
            }
        }
    }
    return array("jquerycode" => $jQueryCode);
});
add_hook("AdminAreaViewTicketPage", 1, "hook_project_management_adminticketinfo");
add_hook("AdminHomeWidgets", 1, function () {
    spl_autoload_register(function ($class_name) {
        $parts = explode("\\", $class_name);
        if ($parts[0] == "WHMCSProjectManagement") {
            unset($parts[0]);
            include __DIR__ . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . ".php";
        }
    });
    return new WHMCSProjectManagement\Widget();
});
add_hook("CalendarEvents", "0", "hook_project_management_calendar");
add_hook("CalendarEvents", "0", "hook_project_management_calendar_tasks");
add_hook("IntelligentSearch", 0, function ($vars) {
    $searchResults = array();
    $searchTerm = $vars["searchTerm"];
    $query = WHMCS\Database\Capsule::table("mod_project");
    if (is_numeric($searchTerm)) {
        $query->where("ticketids", "like", "%" . (int) $searchTerm . "%")->orWhere("title", "like", "%" . $searchTerm . "%")->orWhere("userid", "=", (int) $searchTerm);
    } else {
        if ($searchTerm) {
            $query->orWhere("title", "like", "%" . $searchTerm . "%");
            $query->leftJoin("tblclients", "tblclients.id", "=", "mod_project.userid");
            $query->orWhere(function ($where) use($searchTerm) {
                $where->where(WHMCS\Database\Capsule::raw("CONCAT(tblclients.firstname,' ',tblclients.lastname)"), "like", "%" . $searchTerm . "%")->orWhere("tblclients.email", "like", "%" . $searchTerm . "%");
            });
        }
    }
    foreach ($query->get(array("mod_project.*")) as $project) {
        $searchResults[] = "<a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $project->id . "\">\n    <strong>" . $project->title . "</strong> Project #" . $project->id . "\n</a>";
    }
    return $searchResults;
});
function hook_project_management_csoactions($vars)
{
    return array("<a href=\"addonmodules.php?module=project_management&view=user&userid=" . $_REQUEST["userid"] . "\"><img src=\"images/icons/invoices.png\" border=\"0\" align=\"absmiddle\" /> View Projects</a>");
}
function hook_project_management_adminticketinfo($vars)
{
    global $aInt;
    global $jscode;
    global $jquerycode;
    $ticketid = $vars["ticketid"];
    $ticketdata = get_query_vals("tbltickets", "userid,title,tid", array("id" => $ticketid));
    $tid = $ticketdata["tid"];
    $userid = $ticketdata["userid"];
    $clientData = $userid ? WHMCS\User\Client::find($userid) : NULL;
    require ROOTDIR . "/modules/addons/project_management/project_management.php";
    $projectrows = "";
    $result = select_query("mod_project", "mod_project.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE id=mod_project.adminid) AS adminname", "ticketids LIKE '%" . mysql_real_escape_string($tid) . "%'");
    while ($data = mysql_fetch_array($result)) {
        $timerid = get_query_val("mod_projecttimes", "id", array("projectid" => $data["id"], "end" => "", "adminid" => $_SESSION["adminid"]), "start", "DESC");
        $timetrackinglink = $timerid ? "<a href=\"#\" onclick=\"projectendtimer('" . $data["id"] . "');return false\"><img src=\"../modules/addons/project_management/images/notimes.png\" align=\"absmiddle\" border=\"0\" /> Stop Tracking Time</a>" : "<a href=\"#\" onclick=\"projectstarttimer('" . $data["id"] . "');return false\"><img src=\"../modules/addons/project_management/images/starttimer.png\" align=\"absmiddle\" border=\"0\" /> Start Tracking Time</a>";
        $projectrows .= "<tr><td><a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $data["id"] . "\">" . $data["id"] . "</a></td><td><a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $data["id"] . "\">" . $data["title"] . "</a> <span id=\"projecttimercontrol" . $data["id"] . "\" class=\"tickettimer\">" . $timetrackinglink . "</span></td><td>" . $data["adminname"] . "</td><td>" . fromMySQLDate($data["created"]) . "</td><td>" . fromMySQLDate($data["duedate"]) . "</td><td>" . fromMySQLDate($data["lastmodified"]) . "</td><td>" . $data["status"] . "</td></tr>";
    }
    $code = "<link href=\"../modules/addons/project_management/css/style.css\" rel=\"stylesheet\" type=\"text/css\" />\n\n<div id=\"projectscont\" style=\"margin:0 0 10px 0;padding:5px;border:2px dashed #e0e0e0;background-color:#fff;-moz-border-radius: 6px;-webkit-border-radius: 6px;-o-border-radius: 6px;border-radius: 6px;" . ($projectrows ? "" : "display:none;") . "\">\n\n<h2 style=\"margin:0 0 5px 0;text-align:center;background-color:#f2f2f2;-moz-border-radius: 6px;-webkit-border-radius: 6px;-o-border-radius: 6px;border-radius: 6px;\">Projects</h2>\n\n<div class=\"tablebg\" style=\"padding:0 20px;\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\" id=\"ticketprojectstbl\">\n<tr><th>Project ID</th><th>Title</th><th>Assigned To</th><th>Created</th><th>Due Date</th><th>Last Updated</th><th>Status</th></tr>\n" . $projectrows . "\n</table>\n</div>\n\n</div>\n\n";
    $code .= "\n<script>\n\$(document).on(\"keyup\",\"#cpclientname\",function () {\n    var ticketuseridsearchlength = \$(\"#cpclientname\").val().length;\n    if (ticketuseridsearchlength>2) {\n    WHMCS.http.jqClient.post(\"search.php\", { ticketclientsearch: 1, value: \$(\"#cpclientname\").val(), token: \"" . generate_token("plain") . "\" },\n        function(data){\n            if (data) {\n                \$(\"#cpticketclientsearchresults\").html(data.replace(\"searchselectclient(\",\"projectsearchselectclient(\"));\n                \$(\"#cpticketclientsearchresults\").slideDown(\"slow\");\n                \$(\"#cpclientsearchcancel\").fadeIn();\n            }\n        });\n    }\n});\nfunction projectsearchselectclient(userid,name,email) {\n    \$(\"#cpclientname\").val(name);\n    \$(\"#cpuserid\").val(userid);\n    \$(\"#cpclientsearchcancel\").fadeOut();\n    \$(\"#cpticketclientsearchresults\").slideUp(\"slow\");\n}\n\nfunction createnewproject() {\n    \$(\"#popupcreatenew\").show();\n    \$(\"#popupstarttimer\").hide();\n    \$(\"#popupendtimer\").hide();\n    \$(\"#createnewcont\").slideDown();\n}\nfunction createproject() {\n    inputs = \$(\"#ajaxcreateprojectform\").serializeArray();\n    WHMCS.http.jqClient.post(\"addonmodules.php?module=project_management&createproj=1&ajax=1\",\n        {\n            input : inputs,\n            token: \"" . generate_token("plain") . "\"\n        },\n        function (data) {\n            if(data == \"0\"){\n                alert(\"You do not have permission to create project\");\n            } else {\n                \$(\"#createnewcont\").slideUp();\n                \$(\"#ticketprojectstbl\").append(data);\n                \$(\"#projectscont\").slideDown();\n            }\n        });\n}\n\nfunction projectstarttimer(projectid) {\n    \$(\"#ajaxstarttimerformprojectid\").val(projectid);\n    \$(\"#popupcreatenew\").hide();\n    \$(\"#popupstarttimer\").show();\n    \$(\"#popupendtimer\").hide();\n    \$(\"#createnewcont\").slideDown();\n}\n\nfunction projectendtimer(projectid) {\n    \$(\"#popupcreatenew\").hide();\n    \$(\"#popupstarttimer\").hide();\n    \$(\"#popupendtimer\").show();\n    \$(\"#createnewcont\").slideDown();\n}\n\nfunction projectstarttimersubmit() {\n    WHMCS.http.jqClient.post(\"addonmodules.php?module=project_management&m=view\", \"a=hookstarttimer&\"+\$(\"#ajaxstarttimerform\").serialize(),\n        function (data) {\n            if(data == \"0\"){\n                alert(\"Could not start timer.\");\n            } else {\n                \$(\"#createnewcont\").slideUp();\n                var projid = \$(\"#ajaxstarttimerformprojectid\").val();\n                \$(\"#projecttimercontrol\"+projid).html(\"<a href=\\\"#\\\" onclick=\\\"projectendtimer('\"+projid+\"');return false\\\"><img src=\\\"../modules/addons/project_management/images/notimes.png\\\" align=\\\"absmiddle\\\" border=\\\"0\\\" /> Stop Tracking Time</a>\");\n                \$(\"#activetimers\").html(data);\n            }\n        });\n}\nfunction projectendtimersubmit(projectid,timerid) {\n    WHMCS.http.jqClient.post(\"addonmodules.php?module=project_management&m=view\",\n        {\n            a: \"hookendtimer\",\n            timerid: timerid,\n            ticketnum: \"" . $tid . "\",\n            token: \"" . generate_token("plain") . "\"\n        },\n        function (data) {\n            if (data == \"0\") {\n                alert(\"Could not stop timer.\");\n            } else {\n                \$(\"#createnewcont\").slideUp();\n                \$(\"#projecttimercontrol\"+projectid).html(\"<a href=\\\"#\\\" onclick=\\\"projectstarttimer('\"+projectid+\"');return false\\\"><img src=\\\"../modules/addons/project_management/images/starttimer.png\\\" align=\\\"absmiddle\\\" border=\\\"0\\\" /> Start Tracking Time</a>\");\n                \$(\"#activetimers\").html(data);\n            }\n        }\n    );\n}\n\nfunction projectpopupcancel() {\n    \$(\"#createnewcont\").slideUp();\n}\n\n</script>\n\n<div class=\"projectmanagement\">\n\n<div id=\"createnewcont\" style=\"display:none;\">\n\n<div class=\"createnewcont2\">\n\n<div class=\"createnewproject\" id=\"popupcreatenew\" style=\"display:none\">\n    <div class=\"title\">Create New Project</div>\n    <form id=\"ajaxcreateprojectform\">\n        <div class=\"row\">\n            <div class=\"col-sm-8 leftCol\">\n                <div class=\"form-group\">\n                    <label for=\"inputTitle\">Title</label>\n                    <input type=\"text\" name=\"title\" id=\"inputTitle\" class=\"form-control\" placeholder=\"Title\" />\n                </div>\n            </div>\n            <div class=\"col-sm-4 rightCol\">\n                <div class=\"form-group\">\n                    <label for=\"inputTicketNumber\">Ticket #</label>\n                    <input type=\"text\" name=\"ticketnum\" id=\"inputTicketNumber\" class=\"form-control\" value=\"" . get_query_val("tbltickets", "tid", array("id" => $vars["ticketid"])) . "\" />\n                </div>\n            </div>\n        </div>\n        <div class=\"row\">\n            <div class=\"col-sm-6 leftCol\">\n                <div class=\"form-group\">\n                    <label for=\"inputAssignedTo\">Assigned To</label>\n                    <select class=\"form-control\" name=\"adminid\" id=\"inputAssignedTo\">";
    $adminid = (int) WHMCS\Session::get("adminid");
    $code .= "<option value=\"0\">None</option>";
    $result = select_query("tbladmins", "id,firstname,lastname", array("disabled" => "0"), "firstname` ASC,`lastname", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $aid = $data["id"];
        $adminfirstname = $data["firstname"];
        $adminlastname = $data["lastname"];
        $selected = "";
        if ($aid == $adminid) {
            $selected = " selected=\"selected\"";
        }
        $code .= "<option value=\"" . $aid . "\"" . $selected . ">" . $adminfirstname . " " . $adminlastname . "</option>";
    }
    $clientname = $clientData ? $clientData->fullName : "";
    $code .= "\n                    </select>\n                </div>\n            </div>\n            <div class=\"col-sm-6 rightCol\">\n                <div class=\"form-group\">\n                    <label for=\"cpclientname\">Associated Client</label>\n                    <input type=\"hidden\" name=\"userid\" id=\"cpuserid\" value=\"" . (int) $userid . "\" />\n                    <input type=\"text\" id=\"cpclientname\" value=\"" . $clientname . "\" class=\"form-control\" placeholder=\"Associated Client\" onfocus=\"if(this.value=='" . addslashes($clientname) . "')this.value=''\" /> <img src=\"images/icons/delete.png\" alt=\"" . $vars["_lang"]["cancel"] . "\" align=\"right\" id=\"clientsearchcancel\" height=\"16\" width=\"16\"><div id=\"cpticketclientsearchresults\" style=\"z-index:2000;\"></div>\n                </div>\n            </div>\n        </div>\n        <div class=\"row\">\n            <div class=\"col-sm-6 leftCol\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputCreatedDate\">Created</label>\n                    <label for=\"inputCreatedDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputCreatedDate\"\n                           type=\"text\"\n                           name=\"created\"\n                           value=\"" . getTodaysDate() . "\"\n                           class=\"form-control date-picker-single\"\n                    />\n                </div>\n            </div>\n            <div class=\"col-sm-6 rightCol\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDueDate\">Due Date</label>\n                    <label for=\"inputDueDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDueDate\"\n                           type=\"text\"\n                           name=\"duedate\"\n                           value=\"" . getTodaysDate() . "\"\n                           class=\"form-control date-picker-single future\"\n                    />\n                </div>\n            </div>\n        </div>\n        <div class=\"text-center\">\n            <input id=\"btnCreateProject\" type=\"button\" value=\"Create\" class=\"btn btn-success\" onclick=\"createproject()\" />\n            <input type=\"button\" value=\"Cancel\" class=\"btn btn-default\" onclick=\"projectpopupcancel();return false\" />\n        </div>\n    </form>\n</div>\n\n<div class=\"createnewproject\" id=\"popupstarttimer\" style=\"display:none\">\n<div class=\"title\">Start Time Tracking</div>\n<form id=\"ajaxstarttimerform\">\n" . generate_token() . "\n<input type=\"hidden\" id=\"ajaxstarttimerformprojectid\" name=\"projectid\">\n<input type=\"hidden\" name=\"ticketnum\" value=\"" . $tid . "\" />\n<div class=\"label\" style=\"margin-bottom: 3px;display: block;text-align: left;\">Select Existing Task</div>\n<select class=\"form-control\" name=\"taskid\">";
    $code .= "<option value=\"\">Choose one...</option>";
    $result = select_query("mod_projecttasks", "mod_project.title, mod_projecttasks.id, mod_projecttasks.projectid, mod_projecttasks.task", array("mod_project.ticketids" => array("sqltype" => "LIKE", "value" => (int) $tid)), "", "", "", "mod_project ON mod_projecttasks.projectid=mod_project.id", "", "", "", "mod_project ON mod_projecttasks.projectid=mod_project.id");
    while ($data = mysql_fetch_array($result)) {
        $code .= "<option value=\"" . $data["id"] . "\"";
        $code .= ">" . $data["projectid"] . " - " . $data["title"] . " - " . $data["task"] . "</option>";
    }
    $code .= "</select><br />\n<div class=\"label\" style=\"margin-bottom: 3px;display: block;text-align: left;\">Or Create New Task</div>\n<input type=\"text\" name=\"title\" class=\"form-control\" />\n<br />\n<div align=\"center\">\n    <input type=\"button\" value=\"Start\" onclick=\"projectstarttimersubmit();return false\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"Cancel\" class=\"btn btn-default\" onclick=\"projectpopupcancel();return false\" />\n</div>\n</form>\n</div>\n</div>\n\n<div class=\"createnewproject\" id=\"popupendtimer\" style=\"display:none\">\n<div class=\"title\">Stop Time Tracking</div>\n<form id=\"ajaxendtimerform\">\n<input type=\"hidden\" id=\"ajaxendtimerformprojectid\" name=\"projectid\">\n<h4 style=\"margin:20px 0 10px;\">Active Timers</h4>\n<div id=\"activetimers\">\n";
    $result = select_query("mod_projecttimes", "mod_projecttimes.id, mod_projecttimes.projectid, mod_project.title, mod_projecttimes.taskid, mod_projecttasks.task, mod_projecttimes.start", array("mod_projecttimes.adminid" => $_SESSION["adminid"], "mod_projecttimes.end" => "", "mod_project.ticketids" => array("sqltype" => "LIKE", "value" => (int) $tid)), "", "", "", "mod_projecttasks ON mod_projecttimes.taskid=mod_projecttasks.id INNER JOIN mod_project ON mod_projecttimes.projectid=mod_project.id");
    while ($data = mysql_fetch_array($result)) {
        $code .= "<div class=\"stoptimer" . $data["id"] . "\" style=\"padding:10px;border-top:1px dashed #ccc;border-bottom:1px dashed #ccc;\"><a href=\"#\" onclick=\"projectendtimersubmit('" . $data["projectid"] . "','" . $data["id"] . "');return false\" class=\"btn btn-info btn-sm pull-right\">Stop Timer</a><em>" . $data["title"] . " - Project ID " . $data["projectid"] . "</em><br />&nbsp;&raquo; " . $data["task"] . "<br />Started at " . fromMySQLDate(date("Y-m-d H:i:s", $data["start"]), 1) . ":" . date("s", $data["start"]) . "</div>";
    }
    $code .= "\n</div>\n<br />\n<div align=\"center\"><input type=\"button\" value=\"Cancel\" class=\"btn btn-default\" onclick=\"projectpopupcancel();return false\" /></div>\n</form>\n</div>\n\n</div>\n\n</div>\n\n";
    return $code;
}
function project_management_hook_daysleft($duedate)
{
    if ($duedate == "0000-00-00") {
        return "N/A";
    }
    $datetime = strtotime("now");
    $date2 = strtotime($duedate);
    $days = ceil(($date2 - $datetime) / 86400);
    if ($days == "-0") {
        $days = 0;
    }
    $dueincolor = $days < 2 ? "cc0000" : "73BC10";
    if (0 <= $days) {
        return "<span style=\"color:#" . $dueincolor . "\">Due In " . $days . " Days</span>";
    }
    return "<span style=\"color:#" . $dueincolor . "\">Due " . $days * -1 . " Days Ago</span>";
}
function hook_project_management_calendar($vars)
{
    $events = array();
    if ($vars["start"] == 0 || !$vars["start"]) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0 || !$vars["end"]) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("mod_project", "", "duedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'");
    while ($data = mysql_fetch_assoc($result)) {
        $projecttitle = "Project Due: " . $data["title"] . "\nStatus: " . $data["status"];
        if ($data["adminid"]) {
            $projecttitle .= " (" . getAdminName($data["adminid"]) . ")";
        }
        $events[] = array("id" => "prj" . $data["id"], "title" => $projecttitle, "start" => $data["duedate"], "allDay" => true, "url" => "addonmodules.php?module=project_management&m=view&projectid=" . $data["id"]);
    }
    return $events;
}
function hook_project_management_calendar_tasks($vars)
{
    $events = array();
    if ($vars["start"] == 0 || !$vars["start"]) {
        $vars["start"] = date("Y");
    }
    if ($vars["end"] == 0 || !$vars["end"]) {
        $vars["end"] = date("Y");
    }
    $queryStart = mktime("0", "0", "0", "1", "1", $vars["start"]);
    $queryEnd = mktime("23", "59", "59", "12", "31", $vars["end"]);
    $result = select_query("mod_projecttasks", "mod_projecttasks.*,(SELECT title FROM mod_project WHERE mod_project.id=mod_projecttasks.projectid) AS projecttitle", "duedate BETWEEN '" . date("Y-m-d", $queryStart) . "' AND '" . date("Y-m-d", $queryEnd) . "'");
    while ($data = mysql_fetch_assoc($result)) {
        $projecttitle = "Task Due: " . $data["task"] . "\n" . "Project: " . $data["projecttitle"] . "\nStatus: " . ($data["completed"] ? "Completed" : "Pending");
        if ($data["adminid"]) {
            $projecttitle .= " (" . getAdminName($data["adminid"]) . ")";
        }
        $events[] = array("id" => "prj" . $data["projectid"], "title" => $projecttitle, "start" => $data["duedate"], "allDay" => true, "url" => "addonmodules.php?module=project_management&m=view&projectid=" . $data["projectid"]);
    }
    return $events;
}

?>