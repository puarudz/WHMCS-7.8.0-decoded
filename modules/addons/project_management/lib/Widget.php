<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Widget extends \WHMCS\Module\AbstractWidget
{
    protected $title = "Project Management";
    protected $description = "Project Management Activity.";
    protected $weight = 100;
    protected $cache = true;
    protected $cachePerUser = true;
    protected $requiredPermission = "Addon Modules";
    protected $columns = 2;
    public function getId()
    {
        return str_replace("WHMCSProjectManagement\\", "WHMCSProjectManagement", get_class($this));
    }
    public function getData()
    {
        $adminId = (int) \WHMCS\Session::get("adminid");
        $permissions = new Permission();
        $onlyMine = false;
        if (!$permissions->check("View All Projects") && $permissions->check("View Only Assigned Projects")) {
            $onlyMine = true;
        }
        $data = array();
        $projectLogs = \WHMCS\Database\Capsule::table("mod_projectlog")->join("mod_project", "mod_project.id", "=", "mod_projectlog.projectid");
        if ($onlyMine) {
            $projectLogs = $projectLogs->where("mod_project.adminid", "=", $adminId);
        }
        $projectLogs = $projectLogs->orderBy("date", "desc")->limit(5)->get(array("mod_projectlog.projectid", "mod_projectlog.date", "mod_project.title", "mod_projectlog.msg", "mod_projectlog.adminid"));
        $data["logs"] = array();
        foreach ($projectLogs as $projectLog) {
            $data["logs"][$projectLog->projectid] = array("projectId" => $projectLog->projectid, "date" => $projectLog->date, "title" => $projectLog->title, "msg" => $projectLog->msg, "admin" => getAdminName($projectLog->adminid));
        }
        $myAssignedProjects = \WHMCS\Database\Capsule::table("mod_project")->where("completed", "=", 0)->where("adminid", "=", $adminId)->orderBy("duedate")->get();
        $data["mine"] = array();
        foreach ($myAssignedProjects as $myAssignedProject) {
            $data["mine"][$myAssignedProject->id] = array("id" => $myAssignedProject->id, "title" => $myAssignedProject->title, "dueDate" => $myAssignedProject->duedate, "daysLeft" => project_management_hook_daysleft($myAssignedProject->duedate), "status" => $myAssignedProject->status);
        }
        $upcomingProjects = \WHMCS\Database\Capsule::table("mod_project")->where("completed", "=", 0)->where("duedate", "<=", \WHMCS\Carbon::now()->addDays(7)->toDateString());
        if ($onlyMine) {
            $upcomingProjects = $upcomingProjects->where("adminid", "=", $adminId);
        }
        $upcomingProjects = $upcomingProjects->orderBy("duedate")->get();
        $data["upcoming"] = array();
        foreach ($upcomingProjects as $upcomingProject) {
            $data["upcoming"][$upcomingProject->id] = array("id" => $upcomingProject->id, "title" => $upcomingProject->title, "dueDate" => $upcomingProject->duedate, "daysLeft" => project_management_hook_daysleft($upcomingProject->duedate), "status" => $upcomingProject->status);
        }
        return $data;
    }
    private function convertDueDate($date)
    {
        $dateNum = (int) preg_replace("/[^\\d]+/i", "", $date);
        if ($dateNum === 0) {
            return "N/A";
        }
        return $date;
    }
    public function generateOutput($data)
    {
        $logs = "<tr><td colspan=\"3\" align=\"center\">No Records Found</td></tr>";
        if ($data["logs"]) {
            $logs = "";
            foreach ($data["logs"] as $log) {
                $date = fromMySQLDate($log["date"], true);
                $logs .= "<tr bgcolor=\"#ffffff\">\n    <td align=\"center\">" . $date . "</td>\n    <td align=\"center\">\n        <a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $log["projectId"] . "\">" . $log["title"] . "</a> - " . $log["msg"] . "\n    </td>\n    <td align=\"center\">" . $log["admin"] . "</td>\n</tr>";
            }
        }
        $mine = $upcoming = "<tr><td colspan=\"4\" align=\"center\">No Records Found</td></tr>";
        if ($data["mine"]) {
            $mine = "";
            foreach ($data["mine"] as $myProjects) {
                $dueDate = $this->convertDueDate(fromMySQLDate($myProjects["dueDate"]));
                $mine .= "<tr bgcolor=\"#ffffff\">\n    <td align=\"center\">\n        <a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $myProjects["id"] . "\">" . $myProjects["title"] . "</a>\n    </td>\n    <td align=\"center\">" . $dueDate . "</td>\n    <td align=\"center\">" . $myProjects["daysLeft"] . "</td>\n    <td align=\"center\">" . $myProjects["status"] . "</td>\n</tr>";
            }
        }
        if ($data["upcoming"]) {
            $upcoming = "";
            foreach ($data["upcoming"] as $upcomingProjects) {
                $dueDate = $this->convertDueDate(fromMySQLDate($upcomingProjects["dueDate"]));
                $upcoming .= "<tr bgcolor=\"#ffffff\">\n    <td align=\"center\">\n        <a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $upcomingProjects["id"] . "\">" . $upcomingProjects["title"] . "</a>\n    </td>\n    <td align=\"center\">" . $dueDate . "</td>\n    <td align=\"center\">" . $upcomingProjects["daysLeft"] . "</td>\n    <td align=\"center\">" . $upcomingProjects["status"] . "</td>\n</tr>";
            }
        }
        $output = "<ul class=\"nav nav-tabs\" role=\"tablist\">\n    <li role=\"presentation\" class=\"active\"><a href=\"#myAssigned\" aria-controls=\"myAssigned\" role=\"tab\" data-toggle=\"tab\">My Assigned</a></li>\n    <li role=\"presentation\"><a href=\"#dueProjects\" aria-controls=\"dueProjects\" role=\"tab\" data-toggle=\"tab\">Due Projects</a></li>\n    <li role=\"presentation\"><a href=\"#recentActivity\" aria-controls=\"recentActivity\" role=\"tab\" data-toggle=\"tab\">Recent Activity</a></li>\n</ul>\n<div class=\"tab-content\">\n    <div role=\"tabpanel\" class=\"tab-pane active\" id=\"myAssigned\">\n        <table class=\"table table-striped\">\n            <tr style=\"text-align:center;font-weight:bold;\"><td>Title</td><td>Due Date</td><td>Days Left / Due In</td><td>Status</td></tr>\n            " . $mine . "\n        </table>\n    </div>\n    <div role=\"tabpanel\" class=\"tab-pane\" id=\"dueProjects\">\n        <table class=\"table table-striped\">\n            <tr style=\"text-align:center;font-weight:bold;\"><td>Title</td><td>Due Date</td><td>Days Left / Due In</td><td>Status</td></tr>\n            " . $upcoming . "\n        </table>\n    </div>\n    <div role=\"tabpanel\" class=\"tab-pane\" id=\"recentActivity\">\n        <table class=\"table table-striped\">\n            <tr style=\"text-align:center;font-weight:bold;\"><td>Date</td><td>Log Entry</td><td>Admin User</td></tr>\n            " . $logs . "\n        </table>\n    </div>\n</div>";
        return "<div class=\"widget-content-padded pm-widget-nav\">\n    " . $output . "\n</div>";
    }
}

?>