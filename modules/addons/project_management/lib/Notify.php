<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Notify extends BaseProjectEntity
{
    protected function getProjectRelatedAdmins()
    {
        $project = $this->project;
        $admins = array();
        if ($project->adminid) {
            $admins[] = $project->adminid;
        }
        foreach ($project->tasks()->listall() as $task) {
            if ($task["adminId"]) {
                $admins[] = $task["adminId"];
            }
        }
        return array_values(array_unique($admins));
    }
    public function staff(array $projectChanges, $isNewProject = false)
    {
        if (!$projectChanges) {
            throw new Exception("No Changes to Notify");
        }
        $changes = array();
        $mergeFields = array();
        $projectId = $this->project->id;
        $admins = Helper::getAdmins();
        $adminId = (int) \WHMCS\Session::get("adminid");
        $adminName = "";
        if (array_key_exists($adminId, $admins)) {
            $adminName = $admins[$adminId];
        }
        $systemUrl = \App::getSystemURL();
        $adminFolder = \App::get_admin_folder_name();
        $projectAdminLink = $systemUrl . $adminFolder . DIRECTORY_SEPARATOR . "addonmodules.php?module=project_management&m=view&projectid=" . $projectId;
        if ($isNewProject) {
            $assignedAdminId = $projectChanges["assignedAdminId"];
            $assignedAdminName = "";
            if (array_key_exists($assignedAdminId, $admins)) {
                $assignedAdminName = $admins[$assignedAdminId];
            }
            $mergeFields["newProject"] = $isNewProject;
            $mergeFields["project_name"] = $projectChanges["projectTitle"];
            $mergeFields["assigned_admin"] = $assignedAdminName;
            $mergeFields["due_date"] = $projectChanges["dueDate"];
        } else {
            foreach ($projectChanges as $value) {
                $changes[] = array("field" => $value["field"], "oldValue" => $value["oldValue"], "newValue" => $value["newValue"]);
            }
            $mergeFields["changes"] = $changes;
            $mergeFields["project_name"] = $this->project()->title;
        }
        $mergeFields["project_id"] = $projectId;
        $mergeFields["project_url"] = $projectAdminLink;
        $mergeFields["change_by"] = $adminName;
        $notifyAdmins = $this->getProjectRelatedAdmins();
        $notifyAdmins = array_filter(array_values(array_unique(array_merge($notifyAdmins, $this->project->watchers))));
        if ($notifyAdmins) {
            sendAdminMessage("Project Management: Admin Change Notification", $mergeFields, "project_changes", 0, $notifyAdmins);
        }
        return array("success" => true);
    }
    public function sendEmail()
    {
        check_token("WHMCS.admin.default");
        if (!$this->project->userid) {
            throw new Exception("Client Assignment is Required to Send Email");
        }
        $email = (int) \App::getFromRequest("email");
        $template = \WHMCS\Mail\Template::find($email);
        $emailSent = sendMessage($template, $this->project->userid);
        if ($emailSent === true) {
            return array();
        }
        $this->project->log()->add("Email: " . $template->name . " sent to Client");
        $this->staff(array(array("field" => "Email Sent", "oldValue" => "", "newValue" => "Sent " . $template->name . " to client")));
        throw new Exception($emailSent ?: "Email Sending Failed - Check Activity Log");
    }
}

?>