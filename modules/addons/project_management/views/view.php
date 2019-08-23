<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<script src=\"../modules/addons/project_management/assets/js/master.min.js\"></script>\n";
$whmcsVersion = App::getVersion();
$versionWithGulp = new WHMCS\Version\SemanticVersion("7.1.0-release.1");
if (WHMCS\Version\SemanticVersion::compare($versionWithGulp, $whmcsVersion, ">")) {
    echo WHMCS\View\Asset::cssInclude("icheck/line/blue.css");
    echo WHMCS\View\Asset::cssInclude("lightbox.css");
    echo WHMCS\View\Asset::cssInclude("bootstrap-markdown.min.css");
    echo WHMCS\View\Asset::jsInclude("icheck.min.js");
    echo WHMCS\View\Asset::jsInclude("bootstrap-markdown.js");
} else {
    if (WHMCS\Version\SemanticVersion::compare($versionWithGulp, $whmcsVersion, "<") || WHMCS\Version\SemanticVersion::compare($versionWithGulp, $whmcsVersion, "==")) {
        echo WHMCS\View\Asset::jsInclude("lightbox.js");
    }
}
echo WHMCS\View\Asset::jsInclude("Sortable.min.js");
echo WHMCS\View\Asset::jsInclude("jquery.dataTables.js");
echo WHMCS\View\Asset::jsInclude("dataTables.bootstrap.js");
echo WHMCS\View\Asset::jsInclude("dataTables.responsive.js");
echo WHMCS\View\Asset::cssInclude("dataTables.bootstrap.css");
echo WHMCS\View\Asset::cssInclude("dataTables.responsive.css");
echo "\n<input type=\"hidden\" id=\"csrfToken\" value=\"";
echo generate_token("plain");
echo "\">\n<input type=\"hidden\" id=\"projectId\" value=\"";
echo $projectId;
echo "\">\n\n<div class=\"project-management\">\n\n<div class=\"project-title-container\">\n    <h1 id=\"projectTitle\">\n        ";
echo $project->title;
echo "        <a href=\"#\" data-toggle=\"modal\" data-target=\"#modalSaveProject\" class=\"btn btn-sm btn-link\">\n            <i class=\"fas fa-pencil-alt\" aria-hidden=\"true\"></i>\n            <span class=\"sr-only\">Edit</span>\n        </a>\n    </h1>\n    <div class=\"buttons-container pull-right\">\n        <button type=\"button\" class=\"btn btn-success btn-sm";
if ($output["openTimerId"]) {
    echo " hidden";
}
echo "\" id=\"btnStartTimer\">\n            <i class=\"far fa-clock\" aria-hidden=\"true\"></i>\n            ";
echo $language["startTimer"];
echo "        </button>\n        <button type=\"button\" class=\"btn btn-warning btn-sm";
if (!$output["openTimerId"]) {
    echo " hidden";
}
echo "\" id=\"btnEndTimer\" data-timerid=\"";
echo $output["openTimerId"];
echo "\">\n            <i class=\"far fa-clock\" aria-hidden=\"true\"></i>\n            ";
echo $language["endTimer"];
echo "        </button>\n        ";
if ($project->permissions()->check("Post Messages")) {
    echo "            <button id=\"btnAddComment\" type=\"button\" class=\"btn btn-primary btn-sm\">\n                <i class=\"fas fa-comment\" aria-hidden=\"true\"></i>\n                ";
    echo $language["addComment"];
    echo "            </button>\n        ";
}
echo "\n        <button id=\"btnMainUploadFile\" type=\"button\" class=\"btn btn-default btn-sm\">\n            <i class=\"fas fa-upload\" aria-hidden=\"true\"></i>\n            ";
echo $language["uploadFile"];
echo "        </button>\n        <button id=\"btnSendEmail\" type=\"button\" class=\"btn btn-default btn-sm";
echo !$project->userid ? " hidden" : "";
echo "\" data-toggle=\"modal\" data-target=\"#modalSendEmail\">\n            <i class=\"far fa-envelope\" aria-hidden=\"true\"></i>\n            ";
echo $language["sendEmail"];
echo "        </button>\n        <input type=\"checkbox\" id=\"inputWatch\"";
echo $project->isWatcher() ? " checked" : "";
echo ">\n    </div>\n</div>\n\n<ul class=\"project-details\">\n    <li data-toggle=\"modal\" data-target=\"#modalSaveProject\">";
echo $language["created"];
echo " <span id=\"detailsCreated\">";
echo WHMCSProjectManagement\Helper::getFriendlyDaysToGo($project->created, $language);
echo "</span></li>\n    <li data-toggle=\"modal\" data-target=\"#modalSaveProject\">";
echo $language["duedate"];
echo " <span id=\"detailsDue\">";
echo WHMCSProjectManagement\Helper::getFriendlyDaysToGo($project->duedate, $language);
echo "</span></li>\n    <li data-toggle=\"modal\" data-target=\"#modalSaveProject\">";
echo $language["assignedto"];
echo " <span id=\"detailsAssigned\" class=\"text-grey\">";
echo $output["adminName"];
echo "</span></li>\n    <li id=\"detailsClientLi\" data-toggle=\"";
echo $project->userid ? "" : "modal";
echo "\" data-target=\"#modalSaveProject\">";
echo $language["client"];
echo " <span id=\"detailsClient\" class=\"text-grey\">";
echo WHMCSProjectManagement\Helper::getClientLink($project->userid);
echo "</span></li>\n    <li data-toggle=\"modal\" data-target=\"#modalSaveProject\">";
echo $language["status"];
echo " <span id=\"detailsStatus\" class=\"text-grey\">";
echo $project->status;
echo "</span></li>\n    <li data-toggle=\"modal\" data-target=\"#modalSaveProject\">";
echo $language["lastUpdated"];
echo " <span id=\"detailsUpdated\">";
echo WHMCSProjectManagement\Helper::getFriendlyDaysToGo($project->lastmodified, $language);
echo "</span></li>\n</ul>\n\n<div>\n    <ul class=\"nav nav-tabs pm-tabs\" role=\"tablist\">\n        <li id=\"tabHome\" role=\"presentation\" class=\"active\">\n            <a href=\"#home\" aria-controls=\"home\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"far fa-check-circle\"></i>\n                ";
echo $language["tasks"];
echo "            </a>\n        </li>\n        <li id=\"tabMessages\" role=\"presentation\">\n            <a href=\"#messages\" aria-controls=\"messages\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"far fa-comments\"></i>\n                ";
echo $language["messages"];
echo "                <span class=\"badge\">";
echo count($output["messages"]);
echo "</span>\n            </a>\n        </li>\n        <li id=\"tabTime\" role=\"presentation\">\n            <a href=\"#time\" aria-controls=\"time\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"far fa-clock\"></i>\n                ";
echo $language["timetracking"];
echo "            </a>\n        </li>\n        <li id=\"tabTickets\" role=\"presentation\">\n            <a href=\"#tickets\" aria-controls=\"tickets\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-ticket-alt\"></i>\n                ";
echo $language["tickets"];
echo "                <span id=\"ticketCount\" class=\"badge\">";
echo count($output["tickets"]);
echo "</span>\n            </a>\n        </li>\n        <li id=\"tabBilling\" role=\"presentation\">\n            <a href=\"#billing\" aria-controls=\"billing\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-calculator\"></i>\n                ";
echo $language["billing"];
echo "            </a>\n        </li>\n        <li id=\"tabFiles\" role=\"presentation\">\n            <a href=\"#files\" aria-controls=\"files\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"far fa-file-alt\"></i>\n                ";
echo $language["files"];
echo "                <span id=\"fileCount\" class=\"badge\">";
echo count($output["files"]);
echo "</span>\n            </a>\n        </li>\n        <li id=\"tabLog\" role=\"presentation\">\n            <a href=\"#log\" aria-controls=\"log\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-cog\"></i>\n                ";
echo $language["log"];
echo "            </a>\n        </li>\n    </ul>\n\n<!-- Tab panes -->\n<div class=\"tab-content\">\n<div role=\"tabpanel\" class=\"tab-pane active\" id=\"home\">\n\n<div class=\"project-tab-padding\">\n\n<div class=\"row\">\n    <div class=\"col-md-9\">\n        <h2><span id=\"totalTasks\">";
echo $output["tasksSummary"]["total"];
echo "</span> ";
echo $language["tasks"];
echo " / <span id=\"completedTasks\">";
echo $output["tasksSummary"]["completed"];
echo "</span> ";
echo $language["completed"];
echo "</h2>\n\n        <br>\n\n<table class=\"table tasks\">\n\n";
echo "<tr id=\"noTasks\" class=\"empty-table" . ($output["tasks"] ? " hidden" : "") . "\"><td class=\"empty-table\">" . $language["noTasksFound"] . "</td></tr>";
foreach ($output["tasks"] as $task) {
    $editTask = $deleteTask = "";
    if ($project->permissions()->check("Edit Tasks")) {
        $editTask = "<i class=\"task-edit far fa-pencil-alt\"></i>";
    }
    if ($project->permissions()->check("Delete Tasks")) {
        if ($editTask != "") {
            $editTask .= "&nbsp;";
        }
        $deleteTask = "<i class=\"task-delete far fa-trash-alt\"></i>";
    }
    echo "<tr id=\"task-" . $task["id"] . "\" class=\"task-line-item " . ($task["completed"] ? "task-line-item-completed" : "") . "\">\n                <td>\n                    <i class=\"task-status-indicator far fa-check-circle\"></i>\n                    <span class=\"description\">" . $task["task"] . "</span>\n                    <span id=\"assigned-admin-task-" . $task["id"] . "\" class=\"assigned-admin\" data-id=\"" . $task["id"] . "\">" . $task["assigned"] . "</span>\n                    <span id=\"task-due-date-" . $task["id"] . "\" data-id=\"" . $task["id"] . "\" class=\"task-due-date\"> " . $task["duedate"] . " </span>\n                    <div class=\"pull-right actions\">" . $editTask . $deleteTask . "</div>\n                    <span id=\"total-time-task-" . $task["id"] . "\" class=\"pull-right label label-assigned-user total-time\" data-task-id=\"" . $task["id"] . "\">" . $task["totalTime"] . "</span>\n                    <br /><span class=\"text-grey task-notes\">" . $task["notes"] . "</span>\n                </td>\n            </tr>";
}
echo "</table>\n";
if ($project->permissions()->check("Create Tasks")) {
    echo "    <div class=\"post-message\">\n        <form method=\"post\" action=\"frmPostReply\" class=\"ajaxfrm\" data-action=\"addtask\">\n            <div class=\"alert alert-danger error-feedback hidden\"></div>\n            <input type=\"hidden\" name=\"projectid\" value=\"";
    echo $projectId;
    echo "\">\n            <input id=\"inputAddTask\" type=\"text\" name=\"task\" class=\"form-control\" placeholder=\"";
    echo $language["placeholders"]["addNewTask"];
    echo "\">\n            <div class=\"row padding-top-10\">\n                <div class=\"col-sm-4\">\n                    <select id=\"inputAssignId\" name=\"assignid\" class=\"form-control\">\n                        <option value=\"0\">";
    echo $language["assignToDropdown"];
    echo "</option>\n                        ";
    foreach ($output["admins"] as $adminId => $adminName) {
        echo "<option value=\"" . $adminId . "\">" . $adminName . "</option>";
    }
    echo "                    </select>\n                </div>\n                <div id=\"inputDueDateDiv\" class=\"col-sm-5\">\n                    ";
    echo $language["duedate"];
    echo ": &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n                    <input name=\"duedate\" type=\"text\" class=\"form-control input-inline date\" value=\"";
    echo getTodaysDate();
    echo "\" />\n                </div>\n                <div class=\"col-sm-3 text-right\">\n                    <input type=\"submit\" value=\"";
    echo $language["addtask"];
    echo "\" class=\"btn btn-primary\">\n                </div>\n            </div>\n        </form>\n    </div>\n";
}
echo "\n    </div>\n    <div class=\"col-md-3\">\n\n        ";
if ($project->permissions()->check("Edit Tasks")) {
    echo "            <h3>";
    echo $language["assignTo"];
    echo " <small>";
    echo $language["dragToAssign"];
    echo "</small></h3>\n            <span id=\"adminList\" class=\"list-admins\">\n                <span class=\"label label-assigned-user\" data-id=\"0\">";
    echo $language["unassigned"];
    echo "</span>\n                ";
    foreach ($output["admins"] as $adminId => $adminName) {
        echo "<span class=\"label label-assigned-user\" data-id=\"" . $adminId . "\">" . $adminName . "</span>";
    }
    echo "            </span><br><br>\n            <h3>";
    echo $language["duedate"];
    echo " <small>";
    echo $language["dragToAssign"];
    echo "</small></h3>\n            <div id=\"dueDatePicker\" style=\"border:1px solid #ddd;padding:10px;\">\n                <div id=\"due-date-select\">\n                    <i class=\"handle fas fa-arrows-alt fa-fw\" aria-hidden=\"true\"></i>\n                    <input type=\"text\" class=\"form-control input-inline date\" value=\"";
    echo getTodaysDate();
    echo "\" />\n                </div>\n            </div><br>\n        ";
}
echo "        <h3>";
echo $language["taskTemplates"];
echo "</h3>\n        <button class=\"btn btn-default btn-block\" data-toggle=\"modal\" data-target=\"#modalSaveTaskList\">\n            <i class=\"fas fa-save\"></i>\n            ";
echo $language["saveTaskList"];
echo "        </button>\n        <button class=\"btn btn-default btn-block\" data-toggle=\"modal\" data-target=\"#modalImportTasks\">\n            <i class=\"fas fa-sign-in-alt\"></i>\n            ";
echo $language["importTasks"];
echo "        </button>\n<br>\n        <h3>";
echo $language["hideCompletedTasks"];
echo "</h3>\n        <input type=\"checkbox\" id=\"toggleCompleteHide\" class=\"icheck-button checkbox-inline\"/>\n        <label for=\"toggleCompleteHide\">";
echo $language["hideCompleted"];
echo "?</label>\n\n    </div>\n</div>\n\n</div>\n\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"messages\">\n\n<div class=\"project-tab-padding\">\n\n<h2>Comments</h2>\n\n";
if ($project->permissions()->check("Post Messages")) {
    echo "    <div class=\"post-message\">\n        <form id=\"addMessage\" method=\"post\" action=\"frmPostReply\" data-action=\"addmessage\" enctype=\"multipart/form-data\">\n            <div class=\"alert alert-danger error-feedback hidden\"></div>\n            <input type=\"hidden\" name=\"projectid\" value=\"";
    echo $projectId;
    echo "\">\n            <input id=\"addMessageValidate\" type=\"hidden\" name=\"validated\" value=\"0\">\n            <textarea id=\"inputAddMessage\" name=\"message\" class=\"form-control\" rows=\"5\" placeholder=\"";
    echo $language["placeholders"]["typeMessage"];
    echo "\"></textarea>\n            <div class=\"row\">\n                <div class=\"col-sm-8 padding-top-10\">\n                    <input id=\"addMessageFiles\" type=\"file\" name=\"attachments[]\" multiple=\"multiple\" class=\"form-control\">\n                </div>\n                <div class=\"col-sm-4 text-right padding-top-10\">\n                    <input type=\"submit\" value=\"";
    echo $language["postReply"];
    echo "\" class=\"btn btn-primary\">\n                </div>\n            </div>\n        </form>\n    </div>\n";
}
echo "\n<div class=\"messages\">\n";
foreach ($output["messages"] as $message) {
    $attachments = "";
    foreach ($message["attachment"] as $key => $value) {
        $attachments .= "<a href=\"#\" class=\"message-file\" data-key=\"" . $key . "\">" . $value["displayFilename"] . "</a>";
    }
    $deleteButton = "";
    if ($project->permissions()->check("Delete Messages")) {
        $deleteButton = "<div class=\"pull-right\">\n    <input type=\"button\" value=\"" . $language["delete"] . "\" class=\"btn btn-danger btn-xs\" onclick=\"ProjectManager.confirm('deletemsg', 'msgid=" . $message["id"] . "')\">\n</div>";
    }
    echo "\n            <div class=\"message\" id=\"message-" . $message["id"] . "\">\n                <div class=\"user-gravatar\">\n                    <img src=\"" . $message["gravatarUrl"] . "\">\n                </div>\n                <div class=\"number\">\n                " . $message["number"] . ".\n                </div>\n                <div class=\"content\">\n                    <span class=\"user\">" . $message["name"] . "</span>\n                    <span class=\"date\">" . $message["date"] . "</span>\n                    " . $deleteButton . "\n                    <span class=\"msg\">" . $message["message"] . "</span>\n                    " . $attachments . "\n                </div>\n            </div>\n        ";
    $i++;
}
echo "</div>\n\n\n\n</div>\n\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"time\">\n\n<div class=\"project-tab-padding\">\n\n    <div class=\"pull-right\">\n        <button id=\"btnInvoiceSelected\" type=\"button\" class=\"btn btn-default";
echo !$project->userid ? " disabled\" disabled=\"disabled" : "";
echo "\">\n            <i></i> ";
echo $language["invoiceSelectedItems"];
echo "        </button>\n        <button type=\"button\" class=\"btn btn-success\" data-toggle=\"modal\" data-target=\"#modalAddTimeEntry\">\n            <i class=\"fas fa-plus\"></i>\n            ";
echo $language["addTimeEntry"];
echo "        </button>\n    </div>\n    <h2>";
echo $language["timetracking"];
echo "</h2>\n\n    <p>\n        ";
echo $language["totalLogged"];
echo ": <strong id=\"time-logged\">";
echo $output["timerStats"]["totalTime"];
echo "</strong>\n    &nbsp;&nbsp;\n        ";
echo $language["totalBilled"];
echo ": <strong id=\"time-billed\">";
echo $output["timerStats"]["totalBilled"];
echo "</strong>\n    </p>\n    <table id=\"timersTable\" class=\"table table-striped table-pm timers\">\n        <thead>\n            <tr>\n                <td></td>\n                <td>";
echo $language["date"];
echo "</td>\n                <td>";
echo $language["user"];
echo "</td>\n                <td>";
echo $language["associatedTask"];
echo "</td>\n                <td>";
echo $language["start"];
echo "</td>\n                <td>";
echo $language["end"];
echo "</td>\n                <td>";
echo $language["billed"];
echo "</td>\n                <td>";
echo $language["totaltime"];
echo "</td>\n                <td></td>\n                <td></td>\n            </tr>\n        </thead>\n        <tbody>\n            ";
echo "<tr id=\"noTimers\" class=\"empty-table" . ($output["timers"] ? " hidden" : "") . "\"><td colspan=\"10\" class=\"empty-table\">" . $language["notimesrecorded"] . "</td></tr>";
foreach ($output["timers"] as $timer) {
    $disabled = "";
    $class = "";
    if ($timer["endTime"] == "-" || $timer["billed"]) {
        $disabled = " disabled=\"disabled\"";
        $class = " class=\"disabled\"";
    }
    echo "                <tr id=\"timer-";
    echo $timer["id"];
    echo "\">\n                    <td><input title=\"";
    echo $language["timer"];
    echo " ";
    echo $timer["id"];
    echo "\" type=\"checkbox\" name=\"timerId[]\" value=\"";
    echo $timer["id"];
    echo "\"";
    echo $disabled . $class;
    echo "/></td>\n                    <td>";
    echo $timer["date"];
    echo "</td>\n                    <td>";
    echo $timer["adminName"];
    echo "</td>\n                    <td>";
    echo $timer["taskName"];
    echo "</td>\n                    <td>";
    echo $timer["startTime"];
    echo "</td>\n                    <td";
    echo $timer["endTime"] != "-" ? "" : " class=\"timer-end-time\"";
    echo ">";
    echo $timer["endTime"];
    echo "</td>\n                    <td>";
    echo $timer["billed"] ? "<i class=\"fas fa-check-circle\"></i>" : "<i class=\"fas fa-times-circle\"></i>";
    echo "</td>\n                    <td";
    echo $timer["endTime"] != "-" ? "" : " class=\"timer-duration\"";
    echo ">";
    echo $timer["duration"];
    echo "</td>\n                    <td>\n                        <a href=\"#\" class=\"timer-edit\" onclick=\"return false;\"><i class=\"fas fa-pencil-alt\"></i></a>\n                    </td>\n                    <td>\n                        ";
    echo $timer["billed"] ? "" : "<a href=\"#\" class=\"timer-delete\" onclick=\"return false;\" data-timer-id=\"" . $timer["id"] . "\"><i class=\"fas fa-times text-danger\"></i></a>";
    echo "                    </td>\n                </tr>\n            ";
}
echo "        </tbody>\n    </table>\n\n</div>\n\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"tickets\">\n\n    <div class=\"project-tab-padding\">\n        ";
if ($project->permissions()->check("Associate Tickets")) {
    echo "            <div class=\"pull-right\">\n                <button class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#modalAssociateTicket\">\n                    <i class=\"fas fa-sync\"></i>\n                    ";
    echo $language["associateTicket"];
    echo "                </button>\n                <button class=\"btn btn-success\" data-toggle=\"modal\" data-target=\"#modalOpenTicket\">\n                    <i class=\"fas fa-plus\"></i>\n                    ";
    echo $language["openNewTicket"];
    echo "                </button>\n            </div>\n        ";
}
echo "        <h2><strong id=\"associatedTicketCount\">";
echo count($output["tickets"]);
echo "</strong> ";
echo $language["associatedTickets"];
echo "</h2>\n\n\n    <div class=\"tickets\">\n        ";
echo "<div id=\"noTickets\" class=\"empty-table" . ($output["tickets"] ? " hidden" : "") . "\">" . $language["noTicketsFound"] . "</div>";
echo "        ";
foreach ($output["tickets"] as $ticket) {
    echo "            <div class=\"ticket\" id=\"ticket-";
    echo $ticket->id;
    echo "\">\n                <div class=\"pull-right\">\n                    <button type=\"button\" class=\"btn btn-default view-ticket\" data-ticket-id=\"";
    echo $ticket->id;
    echo "\">\n                        <i class=\"fas fa-search\"></i>\n                        ";
    echo $language["view"];
    echo "                    </button>\n                    <button type=\"button\" class=\"btn btn-danger unlink-ticket\" data-ticket-tid=\"";
    echo $ticket->tid;
    echo "\">\n                        ";
    echo $language["unlink"];
    echo "                    </button>\n                </div>\n                <span class=\"ticketnum\">#";
    echo $ticket->tid;
    echo "</span>\n                <span class=\"subject\">";
    echo $ticket->title;
    echo "                    <span class=\"label\" style=\"background-color: ";
    echo $ticket->statusColour;
    echo "; color: ";
    echo $ticket->statusTextColour;
    echo ";\">";
    echo $ticket->status;
    echo "</span>\n                </span>\n                <span class=\"info\">\n                    ";
    echo $language["ticketUser"];
    echo ": ";
    echo $ticket->userDetails;
    echo "<br>\n                    ";
    echo $language["department"];
    echo ": ";
    echo $ticket->departmentName;
    echo "<br>\n                    ";
    echo $language["lastReplyBy"];
    echo " <strong>";
    echo $ticket->lastReplyUser;
    echo "</strong>";
    echo $ticket->isAdminReply ? " (" . $language["staff"] . ")" : "";
    echo " - ";
    echo $ticket->lastreply;
    echo "                </span>\n            </div>\n        ";
}
echo "    </div>\n\n</div>\n\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"billing\">\n\n    <div class=\"project-tab-padding\">\n        <div class=\"pull-right\">\n            <button class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#modalAssociateInvoice\">\n                <i class=\"fas fa-sync\"></i>\n                ";
echo $language["associateInvoice"];
echo "            </button>\n            <button id=\"createInvoice\" class=\"btn btn-success";
echo !$project->userid ? " disabled\" disabled=\"disabled" : "";
echo "\" data-toggle=\"modal\" data-target=\"#modalCreateInvoice\">\n                <i class=\"fas fa-plus\"></i>\n                ";
echo $language["createInvoice"];
echo "            </button>\n        </div>\n        <h2><strong id=\"associatedInvoiceCount\">";
echo count($output["invoices"]);
echo "</strong> ";
echo $language["invoices"];
echo "</h2>\n\n        <table id=\"invoices\" class=\"table table-striped table-pm invoices\">\n            <thead>\n                <tr>\n                    <td></td>\n                    <td>";
echo $language["created"];
echo "</td>\n                    <td>";
echo $language["due"];
echo "</td>\n                    <td>";
echo $language["total"];
echo "</td>\n                    <td>";
echo $language["balance"];
echo "</td>\n                    <td>";
echo $language["status"];
echo "</td>\n                    <td></td>\n                </tr>\n            </thead>\n            <tbody>\n            ";
echo "<tr id=\"noInvoices\" class=\"empty-table" . ($output["invoices"] ? " hidden" : "") . "\"><td colspan=\"7\" class=\"empty-table\">" . $language["noInvoicesFound"] . "</td></tr>";
echo "            ";
foreach ($output["invoices"] as $invoice) {
    echo "                <tr id=\"invoice-";
    echo $invoice["id"];
    echo "\" class=\"invoice\">\n                    <td class=\"invoice-num\">";
    echo $language["invoicenumberhash"];
    echo $invoice["invoicenum"] ?: $invoice["id"];
    echo "</td>\n                    <td class=\"invoice-data\">\n                        ";
    echo fromMySQLDate($invoice["date"]);
    echo "                    </td>\n                    <td class=\"invoice-data\">\n                        ";
    echo fromMySQLDate($invoice["duedate"]);
    echo "                    </td>\n                    <td class=\"invoice-data\">\n                        ";
    echo formatCurrency($invoice["total"], $invoice["currencyId"]);
    echo "                    </td>\n                    <td class=\"invoice-data\">\n                        ";
    echo formatCurrency($invoice["balance"], $invoice["currencyId"]);
    echo "                    </td>\n                    <td>\n                        <span class=\"label ";
    echo strtolower($invoice["status"]);
    echo "\">\n                            ";
    echo $invoice["status"];
    echo "                        </span>\n                    </td>\n                    <td class=\"text-right\">\n                        <button type=\"button\" class=\"btn btn-default view-invoice\" data-invoice-id=\"";
    echo $invoice["id"];
    echo "\">\n                            <i class=\"fas fa-search\"></i>\n                            ";
    echo $language["view"];
    echo "                        </button>\n                        <button type=\"button\" class=\"btn btn-danger unlink-invoice\" data-invoice-id=\"";
    echo $invoice["id"];
    echo "\">\n                            ";
    echo $language["unlink"];
    echo "                        </button>\n                    </td>\n                </tr>\n            ";
}
echo "            </tbody>\n        </table>\n    </div>\n\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"files\">\n\n    <div class=\"project-tab-padding\">\n        <h2>";
echo $language["files"];
echo "</h2>\n        <form method=\"post\" action=\"addonmodules.php?module=project_management\" class=\"dropzone\" id=\"myProjectUploads\" enctype=\"multipart/form-data\">\n            <input type=\"hidden\" name=\"ajax\" value=\"1\">\n            <input type=\"hidden\" name=\"action\" value=\"uploadfile\">\n            <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n        </form>\n        <div class=\"files\" id=\"fileList\">\n            ";
foreach ($output["files"] as $key => $file) {
    $browserViewable = "\"";
    $isImage = $messageId = "";
    if ($file["browserViewable"]) {
        $browserViewable = "&amp;view=inline\" target=\"_blank\"";
    }
    if ($file["isImage"]) {
        $isImage = " data-lightbox=\"project-image-{\$projectId}\"";
    }
    if ($file["messageId"]) {
        $messageId = "&amp;msg=" . $file["messageId"];
    }
    $viewHref = "addonmodules.php?module=project_management&amp;action=dl";
    $viewHref .= "&amp;projectid=" . $projectId;
    $viewHref .= "&amp;i=" . $key . $messageId;
    $dlHref = $viewHref;
    $viewHref .= $browserViewable;
    echo "                <div class=\"file\" id=\"file-";
    echo $key;
    echo "\">\n                    <div class=\"pull-right\">\n                        <a id=\"fileView";
    echo $key;
    echo "\" class=\"btn btn-default\" href=\"";
    echo $viewHref;
    echo $isImage;
    echo ">\n                            <i class=\"fas fa-search\"></i>\n                            ";
    echo $language["view"];
    echo "                        </a>\n                        <a id=\"fileDownload";
    echo $key;
    echo "\" href=\"";
    echo $dlHref;
    echo "\" class=\"btn btn-default\">\n                            ";
    echo $language["download"];
    echo "                        </a>\n                        <button type=\"button\" class=\"btn btn-danger\" onclick=\"ProjectManager.confirm('deletefile', 'num=";
    echo $key;
    echo "')\">\n                            ";
    echo $language["delete"];
    echo "                        </button>\n                    </div>\n                    <span class=\"title\">";
    echo $file["filename"];
    echo "</span><span class=\"extension\">";
    echo $file["extension"];
    echo "</span>\n                    <span class=\"info\">";
    echo $language["by"];
    echo " <strong>";
    echo $file["admin"];
    echo "</strong> x ";
    echo $file["when"];
    echo " ";
    echo $language["daysago"];
    echo " X ";
    echo $file["filesize"];
    echo "</span>\n                </div>\n            ";
}
echo "        </div>\n    </div>\n</div>\n<div role=\"tabpanel\" class=\"tab-pane\" id=\"log\">\n\n    <div class=\"project-tab-padding\">\n        <h2>";
echo $language["log"];
echo "</h2>\n\n        <table id=\"tableLog\" class=\"table table-striped table-pm\">\n            <thead>\n                <tr>\n                    <td>";
echo $language["date"];
echo "</td>\n                    <td>";
echo $language["description"];
echo "</td>\n                    <td>";
echo $language["user"];
echo "</td>\n                </tr>\n            </thead>\n            <tbody>\n                ";
foreach ($output["log"] as $log) {
    echo "                    <tr>\n                        <td width=\"15%\">";
    echo $log["date"];
    echo "</td>\n                        <td width=\"65%\">";
    echo $log["message"];
    echo "</td>\n                        <td>";
    echo $log["adminName"];
    echo "</td>\n                    </tr>\n                ";
}
echo "            </tbody>\n        </table>\n\n    </div>\n\n</div>\n</div>\n\n</div>\n\n<form id=\"frmModalConfirm\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal-confirm\" tabindex=\"-1\" role=\"dialog\" id=\"modalConfirm\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-body\" id=\"modalConfirmMsg\"></div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["no"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["yes"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmModalEditTask\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"taskedit\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <input type=\"hidden\" name=\"taskid\" id=\"inputTaskId\" value=\"0\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalTaskEdit\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["editTask"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <input type=\"text\" name=\"task\" class=\"form-control\" placeholder=\"";
echo $language["placeholders"]["addNewTask"];
echo "\" id=\"inputTaskTitle\">\n                    <div class=\"padding-top-10\">\n                        <textarea name=\"notes\" class=\"form-control\" rows=\"3\" placeholder=\"";
echo $language["placeholders"]["additionalNotes"];
echo "\" id=\"inputTaskNotes\"></textarea>\n                    </div>\n                    <div class=\"row padding-top-10\">\n                        <div class=\"col-sm-6\">\n                            <strong>";
echo $language["assignTo"];
echo "</strong><br />\n                            <select name=\"admin\" class=\"form-control\" id=\"inputTaskEditAdminAssignment\">\n                                <option value=\"0\">";
echo $language["assignToDropdown"];
echo "</option>\n                                ";
foreach ($output["admins"] as $adminId => $adminName) {
    echo "<option value=\"" . $adminId . "\">" . $adminName . "</option>";
}
echo "                            </select>\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <div class=\"form-group date-picker-prepend-icon\">\n                                <label for=\"inputTaskDue\">";
echo $language["duedate"];
echo "</label>\n                                <label for=\"inputTaskDue\" class=\"field-icon\">\n                                    <i class=\"fal fa-calendar-alt\"></i>\n                                </label>\n                                <input id=\"inputTaskDue\"\n                                       type=\"text\"\n                                       name=\"duedate\"\n                                       value=\"\"\n                                       class=\"form-control date-picker-single future\"\n                                       placeholder=\"";
echo getTodaysDate();
echo "\"\n                                />\n                            </div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    ";
if ($project->permissions()->check("Delete Tasks")) {
    echo "                        <div class=\"pull-left\">\n                            <button type=\"button\" class=\"task-delete-button btn btn-danger\">";
    echo $language["delete"];
    echo "</button>\n                        </div>\n                    ";
}
echo "                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["save"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmAddTimeEntry\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"taskTimeAdd\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalAddTimeEntry\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["addTimeEntry"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <strong>";
echo $language["chooseTask"];
echo "</strong>\n                    <select name=\"taskId\" class=\"form-control\" id=\"inputTaskAssignment\">\n                    ";
foreach ($output["tasks"] as $task) {
    echo "<option value=\"" . $task["id"] . "\">" . $task["task"] . "</option>";
}
echo "                    </select>\n                    <div class=\"padding-top-10\">\n                        <strong>";
echo $language["adminuser"];
echo "</strong><br />\n                        <select name=\"adminId\" class=\"form-control\" id=\"inputTaskAdminAssignment\">\n                            <option>";
echo $language["assignToDropdown"];
echo "</option>\n                            ";
foreach ($output["admins"] as $adminId => $adminName) {
    echo "<option value=\"" . $adminId . "\">" . $adminName . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div id=\"addTimers\" class=\"row padding-top-10\">\n                        <div class=\"col-sm-6\">\n                            <strong>";
echo $language["starttime"];
echo "</strong><br />\n                            <input id=\"addTimerStartDate\" name=\"start\" type=\"text\" class=\"form-control input-inline input-200 date start\" data-date-format=\"dd/mm/yy\" value=\"";
echo getTodaysDate();
echo "\" placeholder=\"";
echo getTodaysDate();
echo "\" tabindex=\"-1\" />\n                            <input id=\"addTimerStartTime\" name=\"startTime\" type=\"text\" class=\"form-control input-inline input-75 time start\" value=\"9:00am\" placeholder=\"9:00am\" tabindex=\"-1\" />\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <strong>";
echo $language["endTime"];
echo "</strong><br />\n                            <input id=\"addTimerEndDate\" name=\"end\" type=\"text\" class=\"form-control input-inline input-200 date end\" value=\"";
echo getTodaysDate();
echo "\" placeholder=\"";
echo getTodaysDate();
echo "\" tabindex=\"-1\" />\n                            <input id=\"addTimerEndTime\" name=\"endTime\" type=\"text\" class=\"form-control input-inline input-75 time end\" value=\"9:30am\" placeholder=\"9:00am\" tabindex=\"-1\" />\n                        </div>\n                    </div>\n                </div>\n\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["save"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmAddTicket\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"addticket\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalAssociateTicket\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["associateTicket"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <input type=\"text\" id=\"associatedTicketSearch\" name=\"ticketmask\" class=\"form-control\" placeholder=\"";
echo $language["placeholders"]["ticketNumberOrName"];
echo "\" />\n                    <div class=\"padding-top-10\">\n                        <div style=\"background-color:#efefef;text-align:center;padding:80px 0;\">\n                            <div class=\"list-group tickets\" id=\"associatedTicketResults\">\n                                <span id=\"resultInfo\">\n                                    ";
echo $language["enterSearchTerm"];
echo "                                </span>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["close"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmOpenTicket\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"openticket\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalOpenTicket\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["openNewTicket"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <div id=\"openTicketClient\" class=\"form-group";
echo !$project->userid ? " hidden" : "";
echo "\">\n                        <div class=\"row\">\n                            <div class=\"col-md-6\">\n                                <label for=\"newTicketClient\">";
echo $language["client"];
echo ":</label>\n                                <input type=\"text\" class=\"form-control\" id=\"newTicketClientRO\" value=\"";
echo $output["client"]->fullName;
echo "\" readonly=\"readonly\" />\n                            </div>\n                            ";
if ($output["contacts"]) {
    echo "                                <div class=\"col-md-6\">\n                                    <label for=\"newTicketContact\">";
    echo $language["contact"];
    echo ":</label>\n                                    <select name=\"contact\" class=\"form-control\" id=\"newTicketContact\">\n                                        <option value=\"0\">";
    echo $language["selectContact"];
    echo "</option>\n                                        ";
    foreach ($output["contacts"] as $contactId => $contactName) {
        echo "<option value=\"" . $contactId . "\">" . $contactName . "</option>";
    }
    echo "                                    </select>\n                                </div>\n                            ";
}
echo "                        </div>\n                    </div>\n                    <div id=\"openTicketNoClient\" class=\"form-group";
echo $project->userid ? " hidden" : "";
echo "\">\n                        <div class=\"row\">\n                            <div class=\"col-md-6\">\n                                <label for=\"newTicketClient\">";
echo $language["name"];
echo ":</label>\n                                <input type=\"text\" class=\"form-control\" name=\"name\" id=\"newTicketClient\" placeholder=\"Client Name\" />\n                            </div>\n                            <div class=\"col-md-6\">\n                                <label for=\"newTicketEmail\">";
echo $language["email"];
echo ":</label>\n                                <input type=\"email\" class=\"form-control\" name=\"email\" id=\"newTicketEmail\" placeholder=\"Client Email\" />\n                            </div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"modal-body\">\n                    <div class=\"form-group\">\n                        <label for=\"newTicketSubject\">";
echo $language["subject"];
echo "</label>\n                        <input type=\"text\" class=\"form-control\" name=\"subject\" id=\"newTicketSubject\" placeholder=\"Ticket Subject\" required=\"required\">\n                    </div>\n                    <div class=\"form-group\">\n                        <div class=\"row\">\n                            <div class=\"col-md-6\">\n                                <label for=\"newTicketDepartment\">";
echo $language["department"];
echo "</label>\n                                <select class=\"form-control\" name=\"department\" id=\"newTicketDepartment\">\n                                    ";
foreach ($output["departments"] as $departmentId => $departmentName) {
    echo "<option value=\"" . $departmentId . "\">" . $departmentName . "</option>";
}
echo "                                </select>\n                            </div>\n                            <div class=\"col-md-6\">\n                                <label for=\"newTicketPriority\">";
echo $language["priority"];
echo "</label>\n                                <select class=\"form-control\" name=\"priority\" id=\"newTicketPriority\">\n                                    <option value=\"High\">";
echo $language["high"];
echo "</option>\n                                    <option value=\"Medium\" selected=\"selected\">";
echo $language["medium"];
echo "</option>\n                                    <option value=\"Low\">";
echo $language["low"];
echo "</option>\n                                </select>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"form-group\">\n                        <label for=\"newTicketMessage\">";
echo $language["ticketMessage"];
echo "</label>\n                        <textarea class=\"form-control\" id=\"newTicketMessage\" name=\"message\" placeholder=\"";
echo $language["placeholders"]["ticketMessage"];
echo "\" data-no-clear=\"false\" required=\"required\"></textarea>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["openTicket"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmAddInvoice\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"addinvoice\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalAssociateInvoice\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["associateInvoice"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <input type=\"text\" id=\"associatedInvoiceSearch\" name=\"invoiceId\" class=\"form-control\" placeholder=\"";
echo $language["placeholders"]["invoiceId"];
echo "\" />\n                    <div class=\"padding-top-10\">\n                        <div style=\"background-color:#efefef;text-align:center;padding:80px 0;\">\n                            <div class=\"list-group invoices\" id=\"associatedInvoiceResults\">\n                                <span id=\"invoiceResultInfo\">\n                                    ";
echo $language["enterSearchTerm"];
echo "                                </span>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["close"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmCreateInvoice\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"createInvoice\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalCreateInvoice\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["createInvoice"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <textarea class=\"form-control\" name=\"description\" placeholder=\"";
echo $language["placeholders"]["invoiceDescription"];
echo "\" rows=\"3\" required=\"required\" id=\"createInvoiceDescription\"></textarea>\n                    <div class=\"padding-top-10\">\n                        <input type=\"text\" class=\"form-control\" name=\"amount\" placeholder=\"";
echo $language["placeholders"]["invoiceAmount"];
echo "\" required=\"required\" id=\"createInvoiceAmount\" />\n                    </div>\n                    <div class=\"row padding-top-10\">\n                        <div class=\"col-sm-6\">\n                            <div class=\"form-group date-picker-prepend-icon\">\n                                <label for=\"createInvoiceCreated\">";
echo $language["createdDate"];
echo "</label>\n                                <label for=\"createInvoiceCreated\" class=\"field-icon\">\n                                    <i class=\"fal fa-calendar-alt\"></i>\n                                </label>\n                                <input id=\"createInvoiceCreated\"\n                                       type=\"text\"\n                                       name=\"created\"\n                                       value=\"\"\n                                       class=\"form-control date-picker-single\"\n                                       placeholder=\"";
echo getTodaysDate();
echo "\"\n                                />\n                            </div>\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <div class=\"form-group date-picker-prepend-icon\">\n                                <label for=\"createInvoiceDue\">";
echo $language["duedate"];
echo "</label>\n                                <label for=\"createInvoiceDue\" class=\"field-icon\">\n                                    <i class=\"fal fa-calendar-alt\"></i>\n                                </label>\n                                <input id=\"createInvoiceDue\"\n                                       type=\"text\"\n                                       name=\"due\"\n                                       value=\"\"\n                                       class=\"form-control date-picker-single future\"\n                                       placeholder=\"";
echo getTodaysDate();
echo "\"\n                                />\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"row padding-top-10\">\n                        <div class=\"col-sm-6\">\n                            <label for=\"createInvoiceApplyTax\"><strong>";
echo $language["applyTask"];
echo "</strong></label><br />\n                            <input id=\"createInvoiceApplyTax\" name=\"applyTax\" type=\"checkbox\" />\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <label for=\"createInvoiceSendEmail\"><strong>";
echo $language["sendEmail"];
echo "</strong></label><br />\n                            <input id=\"createInvoiceSendEmail\" name=\"sendEmail\" type=\"checkbox\" />\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["createInvoice"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"frmTimeTracking\" class=\"modalajaxfrm\" method=\"post\" action=\"\" data-action=\"updateTimer\" data-requestvars=\"\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <input id=\"frmTimeTrackingTimerId\" type=\"hidden\" name=\"timerId\" value=\"0\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalEditTimer\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["editTimeRecord"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <div class=\"padding-top-10\">\n                        <select id=\"editTimerTaskId\" class=\"form-control\" name=\"taskId\">\n                            <option value=\"0\">";
echo $language["unassigned"];
echo "</option>\n                            ";
foreach ($output["tasks"] as $task) {
    echo "<option value=\"" . $task["id"] . "\">" . $task["task"] . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div class=\"padding-top-10\">\n                        <strong>";
echo $language["adminuser"];
echo "</strong><br />\n                        <select name=\"adminId\" class=\"form-control\" id=\"editTimerAdminId\">\n                            <option>";
echo $language["assignToDropdown"];
echo "</option>\n                            ";
foreach ($output["admins"] as $adminId => $adminName) {
    echo "<option value=\"" . $adminId . "\">" . $adminName . "</option>";
}
echo "                        </select>\n                    </div>\n                    <div id=\"editTimers\" class=\"row padding-top-10\">\n                        <div class=\"col-sm-6\">\n                            <strong>";
echo $language["starttime"];
echo "</strong><br />\n                            <input id=\"editTimerStartDate\" name=\"start\" type=\"text\" class=\"form-control input-inline date-picker-single time\" placeholder=\"";
echo $output["now"];
echo "\" />\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <strong>";
echo $language["endTime"];
echo "</strong><br />\n                            <input id=\"editTimerEndDate\" name=\"end\" type=\"text\" class=\"form-control input-inline date-picker-single time\" placeholder=\"";
echo $output["oneWeek"];
echo "\" />\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button id=\"modalEditTimerSave\" type=\"submit\" class=\"btn btn-primary\">";
echo $language["save"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n<form id=\"formImportTasks\" class=\"modalajaxfrm\" method=\"post\" data-action=\"importTasks\">\n    <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n    <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalImportTasks\">\n        <div class=\"modal-dialog\">\n            <div class=\"modal-content\">\n                <div class=\"modal-header\">\n                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                    <h4 class=\"modal-title\">";
echo $language["importProjectTasks"];
echo "</h4>\n                </div>\n                <div class=\"modal-body\">\n                    <select id=\"selectImportTasks\" name=\"taskList\" class=\"selectize-select-pm\" data-value-field=\"id\" data-allow-empty-option=\"1\" data-search-field=\"name|id\" data-pm-action=\"selectTaskList\" placeholder=\"";
echo $language["placeholders"]["enterProjectSearchTerm"];
echo "\">\n                        <option value=\"\">";
echo $language["enterProjectSearchTerm"];
echo "</option>\n                        ";
foreach ($output["taskTemplates"] as $id => $taskTemplate) {
    echo "<option value=\"" . (int) $id . "\">" . $taskTemplate . "</option>";
}
echo "                    </select>\n                    <div class=\"padding-top-10\">\n                        <div style=\"background-color:#efefef;text-align:center;padding:80px 0;\">\n                            <div class=\"list-group tickets\" id=\"importTaskResults\">\n                                <span id=\"tasksResultInfo\">\n                                    ";
echo $language["enterSearchTerm"];
echo "                                </span>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                <div class=\"modal-footer\">\n                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                    <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["import"];
echo "</button>\n                </div>\n            </div>\n        </div>\n    </div>\n</form>\n\n    <form id=\"formSaveTaskList\" class=\"modalajaxfrm\" method=\"post\" data-action=\"saveTaskList\">\n        <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n        <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalSaveTaskList\">\n            <div class=\"modal-dialog\">\n                <div class=\"modal-content\">\n                    <div class=\"modal-header\">\n                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                        <h4 class=\"modal-title\">";
echo $language["saveProjectTaskList"];
echo "</h4>\n                    </div>\n                    <div class=\"modal-body\">\n                        <div class=\"padding-top-10\">\n                            <input type=\"text\" name=\"name\" class=\"form-control\" placeholder=\"";
echo $language["placeholders"]["taskListName"];
echo "\" />\n                        </div>\n                    </div>\n                    <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                    <div class=\"modal-footer\">\n                        <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                        <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["save"];
echo "</button>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </form>\n    <form id=\"formInvoiceItems\" class=\"modalajaxfrm\" method=\"post\" data-action=\"invoiceItems\" data-requestvars=\"\">\n        <input id=\"iIProjectId\" type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n        <input id=\"defaultRate\" type=\"hidden\" name=\"defaultRate\" value=\"";
echo $output["hourlyRate"];
echo "\"/>\n        <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalInvoiceItems\">\n            <div class=\"modal-dialog\">\n                <div class=\"modal-content\">\n                    <div class=\"modal-header\">\n                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                        <h4 class=\"modal-title\">";
echo $language["placeholders"]["invoiceSelected"];
echo "</h4>\n                    </div>\n                    <div class=\"modal-body\">\n                        <div class=\"padding-top-10 text-center\">\n                            ";
echo $language["invoiceAreYouSure"];
echo "                        </div>\n                        <div id=\"timersToInvoice\" class=\"row padding-top-10\">\n                        </div>\n                        <div id=\"timersToInvoiceSample\" class=\"hidden\">\n                            <div class=\"col-md-12\"><hr></div>\n                            <div class=\"col-md-12 text-center bottom-margin-5\">\n                                ";
echo $language["description"];
echo ":\n                                <input type=\"text\" class=\"form-control input-inline input-400 description\" value=\"\">\n                            </div>\n                            <div class=\"col-md-4 text-center\">\n                                <input type=\"hidden\" class=\"hours\" value=\"\">\n                                <input type=\"text\" class=\"form-control input-135 input-inline displayHours\">\n                            </div>\n                            <div class=\"col-md-4 text-center\">\n                                <div class=\"input-group\">\n                                    <span class=\"input-group-addon currency\"></span>\n                                    <input type=\"text\" class=\"form-control input-135 itemRate\">\n                                    <span class=\"input-group-addon currency-suffix\"></span>\n                                </div>\n                            </div>\n                            <div class=\"col-md-4 text-center\">\n                                <div class=\"input-group\">\n                                    <span class=\"input-group-addon currency\"></span>\n                                    <input type=\"text\" class=\"form-control input-135 invoiceAmount\" readonly=\"readonly\">\n                                    <span class=\"input-group-addon currency-suffix\"></span>\n                                </div>\n                            </div>\n                        </div>\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-12\"><hr></div>\n                            <div class=\"col-md-3\">";
echo $language["sendEmail"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <input type=\"checkbox\" name=\"sendInvoiceCreatedEmail\" checked=\"checked\" />\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                    <div class=\"alert alert-info text-center\">";
echo $language["invoiceItems"];
echo "</div>\n                    <div class=\"modal-footer\">\n                        <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["no"];
echo "</button>\n                        <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["yes"];
echo "</button>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </form>\n    <form id=\"formSaveProject\" class=\"modalajaxfrm\" method=\"post\" data-action=\"saveProject\">\n        <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n        <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalSaveProject\">\n            <div class=\"modal-dialog\">\n                <div class=\"modal-content\">\n                    <div class=\"modal-header\">\n                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                        <h4 class=\"modal-title\">";
echo $language["saveProject"];
echo "</h4>\n                    </div>\n                    <div class=\"modal-body\">\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-3\">";
echo $language["title"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <input type=\"text\" name=\"title\" class=\"form-control\" value=\"";
echo $project->title;
echo "\" />\n                            </div>\n                        </div>\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-3\">";
echo $language["duedate"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <input type=\"text\" name=\"dueDate\" class=\"form-control date\" value=\"";
echo fromMySQLDate($project->duedate);
echo "\" />\n                            </div>\n                        </div>\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-3\">";
echo $language["assignedto"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <select name=\"admin\" class=\"form-control select-inline\">\n                                    <option value=\"0\"";
echo $project->adminid == 0 ? " selected=\"selected\"" : "";
echo ">";
echo $language["none"];
echo "</option>\n                                    ";
foreach ($output["admins"] as $adminId => $admin) {
    $selected = "";
    if ($project->adminid == $adminId) {
        $selected = "selected=\"selected\"";
    }
    echo "<option value=\"" . $adminId . "\" " . $selected . ">" . $admin . "</option>";
}
echo "                                </select>\n                            </div>\n                        </div>\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-3\">";
echo $language["client"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <select name=\"client\"\n                                        class=\"selectize selectize-client-search\"\n                                        data-value-field=\"id\"\n                                        data-allow-empty-option=\"1\"\n                                        placeholder=\"";
echo $language["placeholders"]["enterClientSearchTerm"];
echo "\"\n                                        data-active-label=\"";
echo AdminLang::trans("status.active");
echo "\"\n                                        data-inactive-label=\"";
echo AdminLang::trans("status.inactive");
echo "\"\n                                >\n                                    <option value=\"0\"";
echo $project->userid == 0 ? " selected=\"selected\"" : "";
echo ">";
echo $language["none"];
echo "</option>\n                                    ";
if ($project->userid) {
    echo "<option value=\"" . $project->userid . "\" selected=\"selected\">" . $output["client"]->fullName . "</option>";
}
echo "                                </select>\n                            </div>\n                        </div>\n                        <div class=\"row padding-top-10\">\n                            <div class=\"col-md-3\">";
echo $language["status"];
echo ":</div>\n                            <div class=\"col-md-9\">\n                                <select name=\"status\" class=\"form-control select-inline\">\n                                    ";
foreach ($output["statuses"] as $status) {
    $selected = "";
    if ($status == $project->status) {
        $selected = "selected=\"selected\"";
    }
    echo "<option value=\"" . $status . "\" " . $selected . ">" . $status . "</option>";
}
echo "                                </select>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                    <div class=\"modal-footer\">\n                        <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                        <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["save"];
echo "</button>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </form>\n    <form id=\"formSendEmail\" class=\"modalajaxfrm\" method=\"post\" data-action=\"sendEmail\">\n        <input type=\"hidden\" name=\"projectid\" value=\"";
echo $projectId;
echo "\">\n        <div class=\"modal fade pm-modal\" tabindex=\"-1\" role=\"dialog\" id=\"modalSendEmail\">\n            <div class=\"modal-dialog\">\n                <div class=\"modal-content\">\n                    <div class=\"modal-header\">\n                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"";
echo $language["close"];
echo "\"><span aria-hidden=\"true\">&times;</span></button>\n                        <h4 class=\"modal-title\">";
echo $language["sendEmail"];
echo "</h4>\n                    </div>\n                    <div class=\"modal-body\">\n                        <div class=\"padding-top-10\">\n                            <select name=\"email\" class=\"form-control\">\n                                ";
foreach ($output["emailTemplates"] as $emailId => $emailTemplate) {
    echo "<option value=\"" . $emailId . "\">" . $emailTemplate . "</option>";
}
echo "                            </select>\n                        </div>\n                    </div>\n                    <div class=\"alert alert-danger error-feedback text-center hidden\"></div>\n                    <div class=\"modal-footer\">\n                        <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">";
echo $language["cancel"];
echo "</button>\n                        <button type=\"submit\" class=\"btn btn-primary\">";
echo $language["sendEmail"];
echo "</button>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </form>\n</div>\n\n<script type=\"text/javascript\">\n    var maximumFileSize = '";
echo $output["maxFileSize"];
echo "',\n        lang = ";
echo json_encode($language["js"]);
echo ";\n    function getClientSearchPostUrl() {\n        return '";
echo routePath("admin-search-client");
echo "';\n    }\n</script>\n";

?>