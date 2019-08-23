<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Project
{
    public $id = NULL;
    public $client = NULL;
    protected $commaSeparatedValues = array("ticketids", "invoiceids", "watchers");
    protected $dateValues = array("created", "duedate", "lastmodified");
    protected $language = array();
    public function __construct($projectId, array $language = array())
    {
        if (!$projectId) {
            throw new Exception("Project Id Required");
        }
        $this->id = (int) $projectId;
        $data = \WHMCS\Database\Capsule::table("mod_project")->find($this->id);
        if (!$data) {
            throw new Exception("Invalid Project Id");
        }
        foreach ($data as $key => $value) {
            if (in_array($key, $this->commaSeparatedValues)) {
                $value = $value ? explode(",", $value) : array();
                $value = array_filter($value);
            } else {
                if (in_array($key, $this->dateValues)) {
                    $keyValue = $key . "Formatted";
                    $this->{$keyValue} = fromMySQLDate($value);
                }
            }
            $this->{$key} = $value;
        }
        if ($this->userid) {
            $this->client = \WHMCS\User\Client::find($this->userid);
        }
        if ($language) {
            $this->language = $language;
        }
    }
    public static function create($vars)
    {
        $statuses = explode(",", $vars["statusvalues"]);
        if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"]) {
            if (project_management_checkperm("Create New Projects")) {
                $dates = array();
                foreach ($_REQUEST["input"] as $key => $value) {
                    if ($value["name"] == "ticketnum") {
                        $value["name"] = "ticketids";
                    }
                    if ($value["name"] == "created" || $value["name"] == "duedate") {
                        $dates[$value["name"]] = $value["value"];
                        $value["value"] = toMySQLDate($value["value"]);
                    }
                    $insertarr[$value["name"]] = $value["value"];
                }
                $insertarr["status"] = $statuses[0];
                $insertarr["lastmodified"] = "now()";
                $projectid = insert_query("mod_project", $insertarr);
                return "<tr><td><a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $projectid . "\">" . $projectid . "</a></td><td><a href=\"addonmodules.php?module=project_management&m=view&projectid=" . $projectid . "\">" . $insertarr["title"] . "</a> <span id=\"projecttimercontrol" . $projectid . "\" class=\"tickettimer\"><a href=\"#\" onclick=\"projectstarttimer('" . $projectid . "');return false\"><img src=\"../modules/addons/project_management/images/starttimer.png\" align=\"absmiddle\" border=\"0\" /> Start Tracking Time</a></td><td>" . get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array("id" => $insertarr["adminid"])) . "</td><td>" . $dates["created"] . "</td><td>" . $dates["duedate"] . "</td><td>" . getTodaysDate() . "</td><td>" . $statuses[0] . "</td></tr>";
            } else {
                return "0";
            }
        } else {
            if (project_management_checkperm("Create New Projects") && trim($_REQUEST["title"])) {
                $projectid = insert_query("mod_project", array("title" => $_REQUEST["title"], "userid" => $_REQUEST["userid"], "created" => toMySQLDate($_REQUEST["created"]), "duedate" => toMySQLDate($_REQUEST["duedate"]), "adminid" => $_REQUEST["adminid"], "ticketids" => $_REQUEST["ticketnum"], "status" => $statuses[0]));
                project_management_log($projectid, $vars["_lang"]["createdproject"]);
                $projectChanges = array("projectTitle" => $_REQUEST["title"], "assignedAdminId" => $_REQUEST["adminid"], "dueDate" => $_REQUEST["duedate"]);
                $project = new Project($projectid, $vars["_lang"]);
                $project->notify()->staff($projectChanges, true);
                redir("module=project_management&m=view&projectid=" . $projectid);
            }
        }
    }
    public function save()
    {
        update_query("mod_project", array("ticketids" => implode(",", $this->ticketids), "invoiceids" => implode(",", $this->invoiceids), "watchers" => implode(",", $this->watchers), "lastmodified" => "now()"), array("id" => $this->id));
    }
    public function files()
    {
        return new Files($this);
    }
    public function messages()
    {
        return new Messages($this);
    }
    public function tasks()
    {
        return new Tasks($this);
    }
    public function tickets()
    {
        return new Tickets($this);
    }
    public function timers()
    {
        return new Timers($this);
    }
    public function log()
    {
        return new Log($this);
    }
    public function invoices()
    {
        return new Invoices($this);
    }
    public function watch()
    {
        $this->watchers[] = Helper::getCurrentAdminId();
        $this->save();
        return array();
    }
    public function unwatch()
    {
        $watchers = array_flip($this->watchers);
        unset($watchers[Helper::getCurrentAdminId()]);
        $watchers = array_flip($watchers);
        $this->watchers = $watchers;
        $this->save();
        return array();
    }
    public function isWatcher()
    {
        return in_array(Helper::getCurrentAdminId(), $this->watchers);
    }
    public function notify()
    {
        return new Notify($this);
    }
    public function saveProject()
    {
        $dueDate = toMySQLDate(\App::getFromRequest("dueDate"));
        $title = \App::getFromRequest("title");
        $admin = (int) \App::getFromRequest("admin");
        $client = (int) \App::getFromRequest("client");
        $status = \App::getFromRequest("status");
        $changes = array();
        $admins = Helper::getAdmins();
        if ($dueDate != $this->duedate || $title != $this->title || $admin != $this->adminid || $client != $this->userid) {
            if (!$this->permissions()->check("Edit Project Details")) {
                throw new Exception("You don't have permission to edit a Project");
            }
            if ($dueDate != $this->duedate) {
                $changes[] = array("field" => "Due Date", "oldValue" => fromMySQLDate($this->duedate), "newValue" => fromMySQLDate($dueDate));
            }
            if ($title != $this->title) {
                $changes[] = array("field" => "Project Title", "oldValue" => $this->title, "newValue" => $title);
            }
            if ($admin != $this->adminid) {
                $currentAdmin = array_key_exists($this->adminid, $admins) ? $admins[$this->adminid] : "Unassigned";
                $newAdmin = array_key_exists($admin, $admins) ? $admins[$admin] : "Unassigned";
                $changes[] = array("field" => "Assigned Admin", "oldValue" => $currentAdmin, "newValue" => $newAdmin);
            }
            if ($client != $this->userid) {
                $currentClient = $this->userid ? $this->client->fullName : "N/A";
                $newClient = $client ? \WHMCS\User\Client::find($client)->fullName : "N/A";
                $changes[] = array("field" => "Client", "oldValue" => $currentClient, "newValue" => $newClient);
            }
        }
        if ($status != $this->status) {
            if (!$this->permissions()->check("Update Status")) {
                throw new Exception("You don't have permission to change the status of a Project");
            }
            $changes[] = array("field" => "Status", "oldValue" => $this->status, "newValue" => $status);
        }
        if (0 < count($changes)) {
            $completedStatuses = explode(",", \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "project_management")->where("setting", "completedstatuses")->value("value"));
            $completed = $this->completed;
            if (in_array($status, $completedStatuses) && !$this->completed) {
                $completed = true;
                $changes[] = array("field" => "Project Completed", "oldValue" => "Incomplete", "newValue" => "Complete");
            } else {
                if (!in_array($status, $completedStatuses) && $this->completed) {
                    $completed = false;
                    $changes[] = array("field" => "Project Marked Incomplete", "oldValue" => "Complete", "newValue" => "Incomplete");
                }
            }
            \WHMCS\Database\Capsule::table("mod_project")->where("id", $this->id)->update(array("title" => $title, "userid" => $client, "adminid" => $admin, "status" => $status, "duedate" => $dueDate, "completed" => $completed, "lastmodified" => \WHMCS\Carbon::now()->toDateTimeString()));
            $this->notify()->staff($changes);
        }
        $data = \WHMCS\Database\Capsule::table("mod_project")->find($this->id);
        foreach ($data as $key => $value) {
            if (in_array($key, $this->commaSeparatedValues)) {
                $value = $value ? explode(",", $value) : array();
            } else {
                if (in_array($key, $this->dateValues)) {
                    $keyValue = $key . "Formatted";
                    $this->{$keyValue} = fromMySQLDate($value);
                }
            }
            $this->{$key} = $value;
        }
        $adminName = array_key_exists($this->adminid, $admins) ? $admins[$this->adminid] : "Unassigned";
        return array("due" => Helper::getFriendlyDaysToGo($this->duedate, $this->language), "admin" => $adminName, "client" => Helper::getClientLink($this->userid), "clientName" => $this->userid ? $this->client->fullName : "", "clientId" => (int) $this->userid, "status" => $this->status, "modified" => Helper::getFriendlyDaysToGo($this->lastmodified, $this->language), "title" => $this->title);
    }
    public function permissions()
    {
        return new Permission();
    }
    public function duplicateProject()
    {
        if (!$this->permissions()->check("Create New Project")) {
            throw new Exception("You do not have permission to create a new project.");
        }
        $newProjectId = \WHMCS\Database\Capsule::table("mod_project")->insertGetId(array("userid" => 0, "title" => $this->title, "ticketids" => "", "invoiceids" => "", "notes" => $this->notes, "adminid" => $this->adminid, "status" => "Pending", "created" => \WHMCS\Carbon::now()->toDateString(), "duedate" => $this->duedate, "completed" => 0, "lastmodified" => \WHMCS\Carbon::now()->toDateTimeString(), "watchers" => implode(",", $this->watchers)));
        $tasks = $this->tasks()->listall();
        if ($tasks) {
            $saveTasks = array();
            $order = 1;
            foreach ($tasks as $task) {
                $saveTasks[] = array("projectid" => $newProjectId, "task" => $task["task"], "notes" => $task["notes"], "adminid" => $task["adminId"], "created" => \WHMCS\Carbon::now()->toDateTimeString(), "duedate" => $task["rawDueDate"] ? toMySQLDate($task["rawDueDate"]) : "0000-00-00 00:00:00", "completed" => 0, "billed" => 0, "order" => $order);
                $order++;
            }
            if ($saveTasks) {
                \WHMCS\Database\Capsule::table("mod_projecttasks")->insert($saveTasks);
            }
        }
        return array("newProjectId" => $newProjectId);
    }
    public function getLanguage()
    {
        return $this->language;
    }
}

?>