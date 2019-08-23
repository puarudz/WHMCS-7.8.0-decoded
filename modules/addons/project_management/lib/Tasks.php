<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Tasks extends BaseProjectEntity
{
    protected function get($taskId = NULL)
    {
        $tasks = array();
        $where = array("projectid" => $this->project->id);
        if ($taskId) {
            $where["id"] = $taskId;
        }
        $result = select_query("mod_projecttasks", "", $where, "order", "ASC");
        $taskTimes = $this->project->timers()->getTaskTimes();
        while ($data = mysql_fetch_array($result)) {
            $taskid = $data["id"];
            $task = $data["task"];
            $taskadminid = $data["adminid"];
            $tasknotes = $data["notes"];
            $taskcompleted = $data["completed"];
            $taskadmin = $taskadminid ? "<span class=\"label label-assigned-user\" data-id=\"" . $data["adminid"] . "\">" . getAdminName($data["adminid"]) . "</span>" : "<span class=\"label label-assigned-user\" data-id=\"0\">Unassigned</span>";
            $taskduedate = "<span class=\"label label-assigned-user\"><i class=\"fas fa-calendar-alt\"></i> " . Helper::getFriendlyDaysToGo($data["duedate"], $this->project->getLanguage()) . ($data["duedate"] != "0000-00-00" ? " (" . fromMySQLDate($data["duedate"]) . ")" : "") . "</span>";
            $tasks[] = array("id" => $taskid, "task" => $task, "adminId" => $taskadminid, "assigned" => $taskadmin, "duedate" => $taskduedate, "rawDueDate" => $data["duedate"] != "0000-00-00" ? fromMySQLDate($data["duedate"]) : "", "notes" => $tasknotes, "completed" => $taskcompleted, "totalTime" => $taskTimes[$taskid] ? $this->project->timers()->formatTimerSecondsToReadableTime($taskTimes[$taskid]) : "00:00:00");
        }
        return $tasks;
    }
    public function listall()
    {
        return $this->get();
    }
    public function getSingle($taskId = NULL)
    {
        if (is_null($taskId)) {
            $taskId = \App::getFromRequest("taskid");
        }
        return array("task" => $this->get($taskId));
    }
    public function getTaskSummary()
    {
        return project_management_tasksstatus($this->project->id, $vars);
    }
    public function add()
    {
        if (!$this->project->permissions()->check("Create Tasks")) {
            throw new Exception("You don't have permission to create tasks.");
        }
        $task = trim(\App::getFromRequest("task"));
        $notes = trim(\App::getFromRequest("notes"));
        $assignedTo = \App::getFromRequest("assignid");
        $dueDate = \App::getFromRequest("duedate");
        if (!$task) {
            throw new Exception("Task is required");
        }
        $maxorder = get_query_val("mod_projecttasks", "MAX(`order`)", array("projectid" => $this->project->id));
        $newTaskId = insert_query("mod_projecttasks", array("projectid" => $this->project->id, "task" => $task, "notes" => $notes, "adminid" => $assignedTo, "created" => "now()", "duedate" => toMySQLDate($dueDate), "completed" => "0", "billed" => "0", "order" => $maxorder + 1));
        $this->project->log()->add("Task Added: " . $task);
        $this->project->notify()->staff(array(array("field" => "Task Added", "oldValue" => "N/A", "newValue" => $task)));
        $data = $this->get($newTaskId);
        return array("newTaskId" => $newTaskId, "newTask" => $data, "summary" => $this->getTaskSummary(), "editPermission" => $this->project->permissions()->check("Edit Tasks"), "deletePermission" => $this->project->permissions()->check("Delete Tasks"));
    }
    public function delete()
    {
        if (!$this->project->permissions()->check("Delete Tasks")) {
            throw new Exception("You don't have permission to delete tasks.");
        }
        $taskId = \App::getFromRequest("taskid");
        $task = get_query_val("mod_projecttasks", "task", array("projectid" => $this->project->id, "id" => $taskId));
        delete_query("mod_projecttasks", array("projectid" => $this->project->id, "id" => $taskId));
        $this->project->log()->add("Task Deleted: " . $task);
        $this->project->notify()->staff(array(array("field" => "Task Deleted", "oldValue" => $task, "newValue" => "")));
        return array("deletedTaskId" => $taskId, "summary" => $this->getTaskSummary());
    }
    public function toggleStatus()
    {
        $taskId = \App::getFromRequest("taskid");
        $where = array("projectid" => $this->project->id, "id" => $taskId);
        $data = get_query_vals("mod_projecttasks", "task, completed", $where);
        $task = $data["task"];
        $status = $data["completed"];
        $newStatus = $status ? "0" : "1";
        update_query("mod_projecttasks", array("completed" => $newStatus), $where);
        $newStatusText = $status ? "Incomplete" : "Completed";
        $this->project->log()->add("Task Marked " . $newStatusText . ": " . $task);
        $this->project->notify()->staff(array(array("field" => "Task \"" . $task . "\" Status Changed", "oldValue" => $status ? "Completed" : "Incomplete", "newValue" => $newStatusText)));
        return array("taskId" => $taskId, "isCompleted" => $newStatus, "summary" => $this->getTaskSummary());
    }
    public function assign()
    {
        if (!$this->project->permissions()->check("Edit Tasks")) {
            throw new Exception("You don't have permission to edit tasks.");
        }
        $taskId = \App::getFromRequest("taskid");
        $adminId = (int) \App::getFromRequest("admin");
        $taskData = get_query_vals("mod_projecttasks", "task,adminid", array("projectid" => $this->project->id, "id" => $taskId));
        $task = $taskData["task"];
        $currentAdminId = (int) $taskData["adminid"];
        $projectChanges = array();
        $admins = Helper::getAdmins();
        if ($adminId) {
            if ($currentAdminId != $adminId) {
                $admin = \WHMCS\User\Admin::findOrFail($adminId);
                $adminId = $admin->id;
                $this->project->log()->add("Task Assigned to " . $admin->fullName . ": " . $task);
                $currentAdmin = "Unassigned";
                if ($currentAdminId && array_key_exists($currentAdminId, $admins)) {
                    $currentAdmin = $admins[$currentAdminId];
                }
                $projectChanges[] = array("field" => "Task Assigned", "oldValue" => $currentAdmin, "newValue" => $admin->fullName);
            }
        } else {
            if ($currentAdminId !== $adminId) {
                $currentAdmin = "Unknown";
                if (array_key_exists($currentAdminId, $admins)) {
                    $currentAdmin = $admins[$currentAdminId];
                }
                $projectChanges[] = array("field" => "Task Unassigned", "oldValue" => $currentAdmin, "newValue" => "");
                $this->project->log()->add("Task Unassigned: " . $task);
            }
        }
        if ($projectChanges) {
            \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", $this->project->id)->where("id", "=", $taskId)->update(array("adminid" => $adminId));
            $this->project->notify()->staff($projectChanges);
        }
        return array("taskId" => $taskId, "isCompleted" => 1);
    }
    public function setDueDate()
    {
        if (!$this->project->permissions()->check("Edit Tasks")) {
            throw new Exception("You don't have permission to edit tasks.");
        }
        $taskId = \App::getFromRequest("taskid");
        $dueDate = \App::getFromRequest("duedate");
        $taskData = get_query_vals("mod_projecttasks", "task,duedate", array("projectid" => $this->project->id, "id" => $taskId));
        $dueDateMySql = toMySQLDate($dueDate);
        $currentDueDate = $taskData["duedate"];
        $task = $taskData["task"];
        if ($dueDateMySql != $currentDueDate) {
            \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", $this->project->id)->where("id", "=", $taskId)->update(array("duedate" => $dueDateMySql));
            $this->project->log()->add("Task Due Date set to " . $dueDate . ": " . $task);
            $this->project->notify()->staff(array(array("field" => "Task \"" . $task . "\" Due Date Changed", "oldValue" => fromMySQLDate($currentDueDate), "newValue" => $dueDate)));
        }
        $daysLeft = Helper::getFriendlyDaysToGo($dueDateMySql, $this->project->getLanguage());
        return array("taskId" => $taskId, "isCompleted" => 1, "dueDate" => "<span class=\"label label-assigned-user\"><i class=\"fas fa-calendar-alt\"></i> " . $daysLeft . " (" . $dueDate . ")</span>");
    }
    public function edit()
    {
        if (!$this->project->permissions()->check("Edit Tasks")) {
            throw new Exception("You don't have permission to edit tasks.");
        }
        $taskId = \App::getFromRequest("taskid");
        $adminId = (int) \App::getFromRequest("admin");
        $task = \App::getFromRequest("task");
        $notes = \App::getFromRequest("notes");
        $taskData = get_query_vals("mod_projecttasks", "", array("projectid" => $this->project->id, "id" => $taskId));
        if ($adminId) {
            $adminId = \WHMCS\User\Admin::findOrFail($adminId, array("id"))->id;
        }
        $dueDate = \App::getFromRequest("duedate");
        if (!$dueDate) {
            $dueDate = fromMySQLDate("0000-00-00");
        }
        $projectChanges = array();
        $permission = new Permission();
        if (!$permission->check("Edit Tasks")) {
            throw new Exception("You don't have permission to edit tasks.");
        }
        if ($taskData["task"] != $task) {
            $projectChanges[] = array("field" => "Task Name Changed", "oldValue" => $task, "newValue" => $taskData["task"]);
        }
        if ($taskData["adminid"] != $adminId) {
            $admins = Helper::getAdmins();
            $newAdmin = "Unassigned";
            if ($adminId) {
                $newAdmin = "Unknown";
                if (array_key_exists($adminId, $admins)) {
                    $newAdmin = $admins[$adminId];
                }
            }
            $currentAdmin = "Unassigned";
            if ($taskData["adminid"]) {
                $currentAdmin = "Unknown";
                if (array_key_exists($taskData["adminid"], $admins)) {
                    $currentAdmin = $admins[$taskData["adminid"]];
                }
            }
            $projectChanges[] = array("field" => "Task Association Changed", "oldValue" => $currentAdmin, "newValue" => $newAdmin);
        }
        if ($taskData["notes"] != $notes) {
            $projectChanges[] = array("field" => "Task Notes Changed", "oldValue" => $taskData["notes"], "newValue" => $notes);
        }
        if ($taskData["duedate"] != toMySQLDate($dueDate)) {
            $projectChanges[] = array("field" => "Task Due Date Changed", "oldValue" => $dueDate, "newValue" => fromMySQLDate($taskData["duedate"]));
        }
        if ($projectChanges) {
            \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", $this->project->id)->where("id", "=", $taskId)->update(array("task" => $task, "notes" => $notes, "adminid" => $adminId, "duedate" => toMySQLDate($dueDate)));
            $this->project->notify()->staff($projectChanges);
            $this->project->log()->add("Task Modified: " . $task);
        }
        return array("taskId" => $taskId, "isCompleted" => 1, "task" => $this->get($taskId));
    }
    public function search()
    {
        $projectId = \App::getFromRequest("project");
        $search = \App::getFromRequest("search");
        $searchResults = array();
        $projects = \WHMCS\Database\Capsule::table("mod_project")->where(function (\Illuminate\Database\Query\Builder $query) use($search) {
            $query->where("id", "like", "%" . $search . "%");
            $query->orWhere("title", "like", "%" . $search . "%");
        })->where("id", "!=", $projectId)->get(array("id", "title"));
        foreach ($projects as $project) {
            $searchResults[] = array("id" => "p" . $project->id, "name" => $project->title);
        }
        $results = \WHMCS\Database\Capsule::table("mod_projecttasktpls")->where("name", "like", "%" . $search . "%")->get(array("id", "name"));
        foreach ($results as $result) {
            $searchResults[] = array("id" => $result->id, "name" => $result->name);
        }
        return array("options" => $searchResults);
    }
    public function select()
    {
        $selectedItem = \App::getFromRequest("selected");
        $return = array();
        if (substr($selectedItem, 0, 1) == "p") {
            $tasks = \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", substr($selectedItem, 1))->get();
            foreach ($tasks as $task) {
                $return["tasks"][] = array("id" => $task->id, "name" => $task->task);
            }
            $return["reference"] = "tasks";
        } else {
            $tasks = \WHMCS\Database\Capsule::table("mod_projecttasktpls")->where("id", "=", $selectedItem)->value("tasks");
            $tasks = safe_unserialize($tasks);
            foreach ($tasks as $key => $task) {
                $return["tasks"][] = array("id" => $key, "name" => $task["task"]);
            }
            $return["reference"] = $selectedItem;
        }
        return $return;
    }
    public function import()
    {
        $reference = \App::getFromRequest("reference");
        $ids = \App::getFromRequest("taskId");
        $return = array();
        $newTasks = array();
        if ($reference == "tasks") {
            $tasks = \WHMCS\Database\Capsule::table("mod_projecttasks")->whereIn("id", $ids)->get();
            $currentMaxOrder = (int) \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", $this->project->id)->max("order");
            foreach ($tasks as $task) {
                $newTasks[] = $task->task;
                $currentMaxOrder++;
                $newTaskId = \WHMCS\Database\Capsule::table("mod_projecttasks")->insertGetId(array("projectid" => $this->project->id, "task" => $task->task, "notes" => $task->notes, "adminid" => 0, "created" => \WHMCS\Carbon::now()->format("YYYY-mm-dd hh:ii:ss"), "duedate" => \WHMCS\Carbon::now()->format("YYYY-mm-dd"), "completed" => 0, "billed" => 0, "order" => $currentMaxOrder));
                list($return["tasks"][]) = $this->get($newTaskId);
            }
        } else {
            $tasks = safe_unserialize(\WHMCS\Database\Capsule::table("mod_projecttasktpls")->where("id", "=", $reference)->value("tasks"));
            $currentMaxOrder = (int) \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", "=", $this->project->id)->max("order");
            foreach ($tasks as $key => $task) {
                if (!in_array($key, $ids)) {
                    continue;
                }
                $newTasks[] = $task->task;
                $currentMaxOrder++;
                $newTaskId = \WHMCS\Database\Capsule::table("mod_projecttasks")->insertGetId(array("projectid" => $this->project->id, "task" => $task["task"], "notes" => $task["notes"], "adminid" => 0, "created" => \WHMCS\Carbon::now()->format("YYYY-mm-dd hh:ii:ss"), "duedate" => \WHMCS\Carbon::now()->format("YYYY-mm-dd"), "completed" => 0, "billed" => 0, "order" => $currentMaxOrder));
                list($return["tasks"][]) = $this->get($newTaskId);
            }
        }
        if ($newTasks) {
            $this->project->notify()->staff(array(array("field" => "Tasks Added", "oldValue" => "N/A", "newValue" => implode(", ", $newTasks))));
        }
        $return["summary"] = $this->getTaskSummary();
        $return["editPermission"] = $this->project->permissions()->check("Edit Tasks");
        $return["deletePermission"] = $this->project->permissions()->check("Delete Tasks");
        return $return;
    }
    public function saveList()
    {
        $taskListName = \App::getFromRequest("name");
        $existing = \WHMCS\Database\Capsule::table("mod_projecttasktpls")->where("name", $taskListName)->count();
        if ($existing && 0 < $existing) {
            throw new Exception("Unique Name Required");
        }
        $tasks = \WHMCS\Database\Capsule::table("mod_projecttasks")->where("projectid", $this->project->id)->get();
        $tasksForList = array();
        foreach ($tasks as $task) {
            $tasksForList[] = array("task" => $task->task, "notes" => $task->notes, "adminid" => $task->adminid, "duedate" => $task->duedate);
        }
        if (!$tasksForList) {
            throw new Exception("No Tasks to Save");
        }
        \WHMCS\Database\Capsule::table("mod_projecttasktpls")->insert(array("name" => $taskListName, "tasks" => safe_serialize($tasksForList)));
        return array("success" => true, "taskListName" => $taskListName);
    }
}

?>