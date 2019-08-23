<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Router
{
    protected $routes = array("watch" => "project@watch", "unwatch" => "project@unwatch", "saveProject" => "project@saveProject", "clientSearch" => "project@clientSearch", "duplicateProject" => "project@duplicateProject", "deletefile" => "files@delete", "uploadfile" => "files@upload", "addInvoice" => "invoices@associate", "createInvoice" => "invoices@create", "searchInvoices" => "invoices@search", "unlinkInvoice" => "invoices@unlink", "addmessage" => "messages@add", "deletemsg" => "messages@delete", "uploadFileForMessage" => "messages@uploadFile", "addtask" => "tasks@add", "assigntask" => "tasks@assign", "deletetask" => "tasks@delete", "gettaskinfo" => "tasks@getSingle", "importTasks" => "tasks@import", "saveTaskList" => "tasks@saveList", "selectTaskList" => "tasks@select", "taskedit" => "tasks@edit", "taskduedate" => "tasks@setDueDate", "taskSearch" => "tasks@search", "taskstatustoggle" => "tasks@toggleStatus", "addticket" => "tickets@associate", "openticket" => "tickets@open", "parseMarkdown" => "tickets@parseMarkdown", "searchTickets" => "tickets@search", "unlinkTicket" => "tickets@unlink", "taskTimeAdd" => "timers@add", "deleteTimer" => "timers@delete", "endtimer" => "timers@end", "gettimerinfo" => "timers@getSingle", "invoiceItems" => "timers@invoiceItems", "prepareInvoiceTimers" => "timers@prepareInvoiceTimers", "starttimer" => "timers@start", "updateTimer" => "timers@update", "notify" => "notify@staff", "sendEmail" => "notify@sendEmail");
    public function dispatch($action, $project)
    {
        $action = $this->routes[$action];
        if (!$action) {
            throw new Exception("Invalid action requested");
        }
        $action = explode("@", $action);
        list($class, $method) = $action;
        if ($class == "project") {
            $response = $project->{$method}();
        } else {
            $response = $project->{$class}()->{$method}();
        }
        if (is_array($response)) {
            $response = array_merge(array("status" => "1"), $response);
        } else {
            $response = array("status" => "0", "error" => "Unexpected response");
        }
        return $response;
    }
}

?>