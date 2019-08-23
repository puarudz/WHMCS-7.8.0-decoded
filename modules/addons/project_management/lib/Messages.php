<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Messages extends BaseProjectEntity
{
    public function get($messageId = 0)
    {
        $messages = array();
        $where = array("projectid" => $this->project->id);
        $totalMessages = get_query_val("mod_projectmessages", "COUNT(id)", $where);
        if ($messageId) {
            $where["mod_projectmessages.id"] = $messageId;
        }
        $messageNumber = $totalMessages;
        for ($result = select_query("mod_projectmessages", "*,(SELECT CONCAT(firstname,' ',lastname,'|',email) FROM tbladmins WHERE tbladmins.id=mod_projectmessages.adminid) AS adminuser", $where, "date", "DESC"); $data = mysql_fetch_array($result); $messageNumber--) {
            $msgid = $data["id"];
            $date = $data["date"];
            $message = strip_tags($data["message"]);
            $adminuser = $data["adminuser"];
            $adminuser = explode("|", $adminuser, 2);
            list($adminuser, $adminemail) = $adminuser;
            $dates = explode(" ", $date);
            $dates2 = explode("-", $dates[0]);
            $dates = $dates[1];
            $dates = explode(":", $dates);
            $date = date("D, F jS, g:ia", mktime($dates[0], $dates[1], $dates[2], $dates2[1], $dates2[2], $dates2[0]));
            $attachments = $this->project->files()->get($msgid);
            require_once ROOTDIR . "/includes/ticketfunctions.php";
            $messages[] = array("id" => $msgid, "date" => $date, "name" => $adminuser, "email" => $adminemail, "gravatarUrl" => pm_get_gravatar($adminemail, "70"), "message" => nl2br(ticketAutoHyperlinks($message)), "attachment" => $attachments, "number" => $messageNumber);
        }
        return $messages;
    }
    public function add()
    {
        if (!$this->project->permissions()->check("Post Messages")) {
            throw new Exception("You don't have permission to post messages.");
        }
        $message = trim(\App::getFromRequest("message"));
        $fileIds = \App::getFromRequest("fileId");
        if (!$message) {
            throw new Exception("Message is required");
        }
        $newMessageId = insert_query("mod_projectmessages", array("projectid" => $this->project->id, "date" => "now()", "message" => $message, "adminid" => \WHMCS\Session::get("adminid")));
        $this->project->log()->add("Message Posted");
        if ($fileIds) {
            Models\ProjectFile::whereIn("id", $fileIds)->update(array("message_id" => $newMessageId));
        }
        $projectChanges[] = array("field" => "Message Posted", "oldValue" => "N/A", "newValue" => $message);
        $this->project->notify()->staff($projectChanges);
        $data = $this->get($newMessageId);
        return array("newMessageId" => $newMessageId, "newMessage" => $data, "projectId" => $this->project->id, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count(), "messageCount" => count($this->get()), "deletePermission" => $this->project->permissions()->check("Delete Messages"));
    }
    public function delete()
    {
        $projectChanges = array();
        $msgId = trim(\App::getFromRequest("msgid"));
        if (!$this->project->permissions()->check("Delete Messages")) {
            throw new Exception("You don't have permission to delete messages.");
        }
        delete_query("mod_projectmessages", array("projectid" => $this->project->id, "id" => $msgId));
        $attachmentCollection = Models\ProjectFile::whereProjectId($this->project->id)->where("message_id", "=", $msgId)->get();
        $deletedFiles = array();
        if (is_array($attachmentCollection)) {
            foreach ($attachmentCollection as $attach) {
                $deletedFiles[] = $attach->id;
                $projectChanges[] = array("field" => "File Deleted on Message Delete", "oldValue" => substr($attach->filename, 7), "newValue" => "");
                $this->project->files()->delete($attach);
            }
        }
        $this->project->log()->add("Message Deleted");
        $projectChanges[] = array("field" => "Message Deleted", "oldValue" => $msgId, "newValue" => "");
        $this->project->notify()->staff($projectChanges);
        return array("deletedMessageId" => $msgId, "deletedFiles" => $deletedFiles, "fileCount" => Models\ProjectFile::where("project_id", $this->project->id)->count(), "messageCount" => count($this->get()));
    }
    public function uploadFile()
    {
        $newFiles = array();
        foreach (\WHMCS\File\Upload::getUploadedFiles("attachments") as $uploadedFile) {
            $file = new Models\ProjectFile();
            $file->projectId = $this->project->id;
            $file->filename = $uploadedFile->storeAsProjectFile($this->project->id);
            $file->adminId = \WHMCS\Session::get("adminid");
            $file->messageId = 0;
            $file->save();
            $this->project->log()->add("File Uploaded: " . $this->project->files()->formatFilenameForDisplay($uploadedFile->getCleanName()));
            $newFiles[] = $file->id;
        }
        return array("uploaded" => true, "newFiles" => $newFiles);
    }
}

?>