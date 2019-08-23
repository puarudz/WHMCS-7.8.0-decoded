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
$jscode .= "function doDelete(id) {\n    if (confirm(\"" . $vars["_lang"]["confirmdeleteproject"] . "\")) {\n        window.location='" . $modulelink . "&action=delete&projectid='+id+'&token=" . generate_token("plain") . "';\n    }\n}\n";
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    if (project_management_checkperm("Delete Projects")) {
        $projectdata = get_query_vals("mod_project", "id,title", array("id" => $project->id));
        $fileList = WHMCSProjectManagement\Models\ProjectFile::whereProjectId($project->id)->get();
        foreach ($fileList as $file) {
            try {
                $project->files()->delete($file);
            } catch (Exception $e) {
            }
        }
        delete_query("mod_project", array("id" => $projectdata["id"]));
        delete_query("mod_projecttasks", array("projectid" => $projectdata["id"]));
        delete_query("mod_projecttimes", array("projectid" => $projectdata["id"]));
        delete_query("mod_projectmessages", array("projectid" => $projectdata["id"]));
        delete_query("mod_projectlog", array("projectid" => $projectdata["id"]));
        project_management_log($projectdata["projectid"], $vars["_lang"]["deletedproject"] . " - " . $projectdata["title"]);
    }
    redir("module=project_management");
}
$q = htmlspecialchars($_REQUEST["q"]);
$view = isset($_REQUEST["view"]) ? $_REQUEST["view"] : "";
$filter = isset($_REQUEST["filter"]) ? $_REQUEST["filter"] : "";
$searchName = isset($_REQUEST["search-name"]) ? $_REQUEST["search-name"] : "";
$searchAssignedTo = isset($_REQUEST["search-assigned-to"]) ? $_REQUEST["search-assigned-to"] : "";
$searchClientName = isset($_REQUEST["search-client-name"]) ? $_REQUEST["search-client-name"] : "";
echo "\n\n<script src=\"../modules/addons/project_management/assets/js/master.min.js\"></script>\n<script>\nvar maximumFileSize = '',\n    lang = " . json_encode($vars["_lang"]["js"]) . ";\n</script>\n\n" . $headeroutput . "\n\n<div class=\"pm-addon\">\n\n<ul class=\"nav nav-tabs pm-tabs\" role=\"tablist\">\n    <li" . ($view == "tasks" ? "" : " class=\"active\"") . ">\n        <a href=\"addonmodules.php?module=project_management\">\n            <i class=\"fas fa-cube fa-fw\"></i>\n            " . $vars["_lang"]["projects"] . "\n        </a>\n    </li>\n    <li" . ($view == "tasks" ? " class=\"active\"" : "") . ">\n        <a href=\"addonmodules.php?module=project_management&view=tasks\">\n            <i class=\"far fa-check-circle fa-fw\"></i>\n            " . $vars["_lang"]["tasks"] . "\n        </a>\n    </li>\n    <li>\n        <a href=\"addonmodules.php?module=project_management&m=reports\">\n            <i class=\"fas fa-chart-area fa-fw\"></i>\n            " . $vars["_lang"]["viewreports"] . "\n        </a>\n    </li>\n    <li>\n        <a href=\"addonmodules.php?module=project_management&m=activity\">\n            <i class=\"far fa-file-alt fa-fw\"></i>\n            " . $vars["_lang"]["recentactivity"] . "\n        </a>\n    </li>\n</ul>\n\n<div class=\"tab-content\">\n    <div role=\"tabpanel\" class=\"tab-pane active\" id=\"home\">\n        <div class=\"project-tab-padding\">\n\n            <div class=\"search\">\n                <form class=\"form-horizontal\" method=\"post\" action=\"addonmodules.php?module=project_management\">\n                    <input type=\"hidden\" name=\"view\" value=\"" . $view . "\">\n                    <input type=\"hidden\" name=\"filter\" value=\"" . $filter . "\" id=\"inputPredefinedFilter\">\n                    <div class=\"form-group\">\n                        <label for=\"inputPredefFilters\" class=\"col-sm-2 control-label\">Predefined Filters</label>\n                        <div class=\"col-sm-8\">\n                            <div class=\"btn-group\" id=\"predefinedFilters\">\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "all" ? " active" : "") . "\" data-filter=\"all\">" . $vars["_lang"]["viewall"] . "</a>\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "incomplete" || !$filter ? " active" : "") . "\" data-filter=\"incomplete\">" . $vars["_lang"]["incomplete"] . "</a>\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "mine" ? " active" : "") . "\" data-filter=\"mine\">" . $vars["_lang"]["assignedtome"] . "</a>\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "mineincomplete" ? " active" : "") . "\" data-filter=\"mineincomplete\">" . $vars["_lang"]["myincomplete"] . "</a>\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "week" ? " active" : "") . "\" data-filter=\"week\">" . $vars["_lang"]["duein7days"] . "</a>\n                                <a href=\"#\" class=\"btn btn-default btn-sm" . ($filter == "closed" ? " active" : "") . "\" data-filter=\"closed\">" . $vars["_lang"]["closed"] . "</a>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputName\" class=\"col-sm-2 control-label\">" . ($view == "tasks" ? $vars["_lang"]["taskname"] : $vars["_lang"]["projectname"]) . "</label>\n                        <div class=\"col-sm-8\">\n                            <input type=\"text\" name=\"search-name\" class=\"form-control\" id=\"inputName\" value=\"" . $searchName . "\">\n                        </div>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"inputAssignedTo\" class=\"col-sm-2 control-label\">" . $vars["_lang"]["assignedto"] . "</label>\n                        <div class=\"col-sm-8\">\n                            <select name=\"search-assigned-to\" class=\"form-control\" id=\"inputAssignedTo\">\n                                <option value=\"\">- " . $vars["_lang"]["any"] . " -</option>\n                                ";
foreach (WHMCSProjectManagement\Helper::getAdmins() as $adminId => $adminName) {
    echo "<option value=" . $adminId . "\"" . ($adminId == $searchAssignedTo ? " selected" : "") . ">" . $adminName . "</option>";
}
echo "\n                            </select>\n                        </div>\n                    </div>";
if ($view != "tasks") {
    echo "\n                    <div class=\"form-group\">\n                        <label for=\"inputClientName\" class=\"col-sm-2 control-label\">" . $vars["_lang"]["clientname"] . "</label>\n                        <div class=\"col-sm-8\">\n                            <input type=\"text\" name=\"search-client-name\" class=\"form-control\" id=\"inputClientName\" value=\"" . $searchClientName . "\">\n                        </div>\n                    </div>\n                        ";
}
echo "\n                    <div class=\"form-group\">\n                        <div class=\"col-sm-offset-2 col-sm-8\">\n                            <button type=\"submit\" class=\"btn btn-primary\">" . AdminLang::trans("global.search") . "</button>\n                        </div>\n                    </div>\n                </form>\n            </div>\n\n";
$tabledata = array();
$aInt->sortableTableInit("duedate", "ASC");
if ($view == "tasks") {
    $where = array();
    if ($filter == "mine") {
        $where["adminid"] = (int) WHMCS\Session::get("adminid");
    } else {
        if ($filter == "mineincomplete") {
            $where["completed"] = "0";
            $where["adminid"] = (int) WHMCS\Session::get("adminid");
        } else {
            if ($filter == "incomplete" || !$filter) {
                $where["completed"] = "0";
            } else {
                if ($filter == "week") {
                    $where["completed"] = "0";
                    $where["duedate"] = array("sqltype" => "<=", "value" => date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 7, date("Y"))));
                } else {
                    if ($filter == "closed") {
                        $where["completed"] = "1";
                    } else {
                        if ($filter == "project" && is_numeric($_REQUEST["projectid"])) {
                            $where["projectid"] = (int) $_REQUEST["projectid"];
                        }
                    }
                }
            }
        }
    }
    if ($searchName) {
        $where["task"] = array("sqltype" => "LIKE", "value" => $searchName);
    }
    if ($searchAssignedTo) {
        $where["adminid"] = $searchAssignedTo;
    }
    if (project_management_checkperm("View Only Assigned Projects") && !project_management_checkperm("View All Projects")) {
        $where["adminid"] = (int) WHMCS\Session::get("adminid");
    }
    if ($_REQUEST["filter"] == "week") {
        $where = "completed=0 AND duedate<='" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 7, date("Y"))) . "'";
        if (project_management_checkperm("View Only Assigned Projects") && !project_management_checkperm("View All Projects")) {
            $where .= " AND adminid = " . (int) WHMCS\Session::get("adminid");
        }
    }
    $numrows = get_query_val("mod_projecttasks", "COUNT(id)", $where);
    $orderby = in_array($orderby, array("task", "created", "duedate")) ? $orderby : "";
    if (!$orderby) {
        $order = "";
    }
    $result = select_query("mod_projecttasks", "id,projectid,task,created,duedate,adminid,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE id=mod_projecttasks.adminid) AS adminuser", $where, $orderby, $order, $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        extract($data);
        $daysleft = $duedate != "0000-00-00" ? project_management_daysleft($duedate, $vars) : "-";
        $created = fromMySQLDate($created);
        $duedate = $duedate != "0000-00-00" ? fromMySQLDate($duedate) : "-";
        $projectdata = get_query_vals("mod_project", "", array("id" => $projectid));
        $projectname = $projectdata["title"];
        $projectadminid = $projectdata["adminid"];
        $show_project = false;
        if (project_management_check_viewproject($projectid)) {
            $show_project = true;
        }
        $projectname = $show_project ? "<a href=\"" . str_replace("m=overview", "m=view", $modulelink) . "&projectid=" . $projectid . "\">" . $projectname . "</a>" : $projectname;
        if (!$adminuser) {
            $adminuser = "-";
        }
        $editprojecthtml = $show_project ? "<a href=\"" . str_replace("m=overview", "m=view", $modulelink) . "&projectid=" . $projectid . "\"><img src=\"images/edit.gif\" border=\"0\" /></a>" : "";
        $deleteprojecthtml = project_management_checkperm("Delete Projects") ? "<a href=\"#\" onclick=\"doDelete('" . $projectid . "');return false\"><img src=\"images/delete.gif\" border=\"0\" /></a>" : "";
        $tabledata[] = array("<div align=\"left\">" . $projectname . "</div>", "<div align=\"left\">" . $task . "</div>", $created, $duedate, $daysleft, $adminuser, $editprojecthtml, $deleteprojecthtml);
    }
    echo $aInt->sortableTable(array(array("project", $vars["_lang"]["projectname"]), array("task", $vars["_lang"]["taskname"]), array("created", $vars["_lang"]["created"]), array("duedate", $vars["_lang"]["duedate"]), array("duedate", $vars["_lang"]["daysleft"]), $vars["_lang"]["assignedto"], "", ""), $tabledata);
} else {
    $query = WHMCS\Database\Capsule::table("mod_project");
    if (is_numeric($q)) {
        $query->where("ticketids", "like", "%" . (int) $q . "%")->orWhere("title", "like", "%" . $q . "%")->orWhere("userid", "=", (int) $q);
    } else {
        if ($q) {
            $query->orWhere("title", "like", "%" . $q . "%");
            $query->leftJoin("tblclients", "tblclients.id", "=", "mod_project.userid");
            $query->orWhere(function ($where) use($q) {
                $where->where(WHMCS\Database\Capsule::raw("CONCAT(tblclients.firstname,' ',tblclients.lastname)"), "like", "%" . $q . "%")->orWhere("tblclients.email", "like", "%" . $q . "%");
            });
        }
    }
    if ($searchName) {
        $query->where("title", "like", "%" . $searchName . "%");
    }
    if ($searchAssignedTo) {
        $query->where("adminid", "=", $searchAssignedTo);
    }
    if ($searchClientName) {
        $query->leftJoin("tblclients", "tblclients.id", "=", "mod_project.userid");
        $query->where(function ($where) use($searchClientName) {
            $where->where(WHMCS\Database\Capsule::raw("CONCAT(tblclients.firstname,' ',tblclients.lastname)"), "like", "%" . $searchClientName . "%");
        });
    }
    if ($filter == "mine") {
        $query->where("adminid", "=", WHMCSProjectManagement\Helper::getCurrentAdminId());
    } else {
        if ($filter == "mineincomplete") {
            $query->where("completed", "=", 0)->where("adminid", "=", WHMCSProjectManagement\Helper::getCurrentAdminId());
        } else {
            if ($filter == "incomplete" || !$filter) {
                $query->where("completed", "=", 0);
            } else {
                if ($filter == "week") {
                    $query->where("duedate", "<=", WHMCS\Carbon::now()->addDays(7)->toDateString())->where("completed", "=", 0);
                } else {
                    if ($filter == "closed") {
                        $query->where("completed", "=", 1);
                    }
                }
            }
        }
    }
    if ($view == "user" && !empty($_REQUEST["userid"])) {
        $userId = (int) App::getFromRequest("userid");
        $query->where("userid", "=", $userId);
    } else {
        if ($view == "ticket" && !empty($_REQUEST["ticketid"])) {
            $ticketId = (int) App::getFromRequest("ticketid");
            $query->where("ticketids", "like", WHMCS\Database\Capsule::table("tbltickets")->find($ticketId)->value("tid"));
        } else {
            if ($view == "closed") {
                $query->where("completed", "=", 1);
            }
        }
    }
    if (project_management_checkperm("View Only Assigned Projects") && !project_management_checkperm("View All Projects")) {
        $query->where("adminid", "=", WHMCSProjectManagement\Helper::getCurrentAdminId());
    }
    $numrows = $query->count();
    if ($orderby && in_array($orderby, array("title", "status", "created", "duedate", "lastmodified"))) {
        $query->orderBy($orderby, $order);
    }
    $recordsToDisplay = WHMCS\Config\Setting::getValue("NumRecordstoDisplay");
    $query->limit($recordsToDisplay);
    if (App::isInRequest("page")) {
        $query->offset($recordsToDisplay * App::getFromRequest("page"));
    }
    $query->leftJoin("tbladmins", "tbladmins.id", "=", "adminid");
    $query->select(array("mod_project.*", WHMCS\Database\Capsule::raw("CONCAT(tbladmins.firstname,' ',tbladmins.lastname) as adminuser")));
    foreach ($query->get() as $data) {
        $data = json_decode(json_encode($data), true);
        $projectid = $data["id"];
        $progressdata = project_management_tasksstatus($projectid, $vars);
        if (($q || $_REQUEST["view"] == "ticket" || $_REQUEST["view"] == "user") && $numrows == 1) {
            redir("module=project_management&m=view&projectid=" . $projectid);
        }
        $title = $data["title"];
        $status = $data["status"];
        $adminid = $data["adminid"];
        $adminuser = $data["adminuser"];
        $created = $data["created"];
        $duedate = $data["duedate"];
        $lastmodified = $data["lastmodified"];
        $daysleft = project_management_daysleft($duedate, $vars);
        $created = fromMySQLDate($created);
        $duedate = fromMySQLDate($duedate);
        $lastmodified = fromMySQLDate($lastmodified, true);
        $show_project = false;
        if (project_management_check_viewproject($projectid)) {
            $show_project = true;
        }
        $title = $show_project ? "<a href=\"" . str_replace("m=overview", "m=view", $modulelink) . "&projectid=" . $projectid . "\">" . $title . "</a>" : $title;
        if (!$adminuser) {
            $adminuser = "-";
        }
        $editprojecthtml = $show_project ? "<a href=\"" . str_replace("m=overview", "m=view", $modulelink) . "&projectid=" . $projectid . "\"><img src=\"images/edit.gif\" border=\"0\" /></a>" : "";
        $deleteprojecthtml = project_management_checkperm("Delete Projects") ? "<a href=\"#\" onclick=\"doDelete('" . $projectid . "');return false\"><img src=\"images/delete.gif\" border=\"0\" /></a>" : "";
        $progressBar = "<div class=\"progress\">\n    <div class=\"progress-bar progress-bar-striped\"\n         role=\"progressbar\" \n         aria-valuenow=\"" . $progressdata["percent"] . "\" \n         aria-valuemin=\"0\" \n         aria-valuemax=\"100\" \n         style=\"width: " . $progressdata["percent"] . "%;\"\n    >\n        <span>" . $progressdata["percent"] . "%</span>\n    </div>\n</div>";
        $tabledata[] = array("<div class=\"text-left\">" . $title . "</div>", $adminuser, $status, $created, $duedate, "<div class=\"project-progress\">" . $progressBar . "</div>", $daysleft, $lastmodified, $editprojecthtml, $deleteprojecthtml);
    }
    echo $aInt->sortableTable(array(array("title", $vars["_lang"]["projectname"]), $vars["_lang"]["assignedto"], array("status", $vars["_lang"]["status"]), array("created", $vars["_lang"]["created"]), array("duedate", $vars["_lang"]["duedate"]), array("progress", $vars["_lang"]["projectprogress"]), array("duedate", $vars["_lang"]["daysleft"]), array("lastmodified", $vars["_lang"]["lastmodified"]), "", ""), $tabledata);
}
echo "\n\n        </div>\n    </div>\n</div>\n\n</div>\n\n";

?>