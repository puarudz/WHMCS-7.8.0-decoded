<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

spl_autoload_register(function ($class_name) {
    $parts = explode("\\", $class_name);
    if ($parts[0] == "WHMCSProjectManagement") {
        unset($parts[0]);
        include __DIR__ . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . ".php";
    }
});
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "dl") {
    if (!function_exists("gracefulCoreRequiredFileInclude")) {
        require_once dirname(dirname(dirname(__DIR__))) . "/init.php";
    }
    $projectid = isset($_REQUEST["projectid"]) ? (int) $_REQUEST["projectid"] : 0;
    $msg = isset($_REQUEST["msg"]) ? (int) $_REQUEST["msg"] : 0;
    $i = isset($_REQUEST["i"]) ? (int) $_REQUEST["i"] : 0;
    $adminid = isset($_SESSION["adminid"]) ? (int) $_SESSION["adminid"] : 0;
    $userid = isset($_SESSION["uid"]) ? (int) $_SESSION["uid"] : 0;
    if ($adminid) {
        $result = select_query("tbladdonmodules", "value", array("module" => "project_management", "setting" => "access"));
        $data = mysql_fetch_array($result);
        $allowedroles = explode(",", $data[0]);
        $result = select_query("tbladmins", "roleid", array("id" => $adminid));
        $data = mysql_fetch_array($result);
        $adminroleid = $data[0];
        if (!in_array($adminroleid, $allowedroles)) {
            exit("Access Denied");
        }
        if (!project_management_check_viewproject($projectid)) {
            exit("Access Denied");
        }
    } else {
        if ($userid) {
            $accessallowed = get_query_val("mod_project", "id", array("id" => $projectid, "userid" => $userid));
            if (!$accessallowed) {
                exit("Access Denied");
            }
        } else {
            exit("Access Denied");
        }
    }
    if ($msg) {
        if (!$adminid) {
            exit("Access Denied");
        }
        $file = WHMCSProjectManagement\Models\ProjectFile::whereProjectId($projectid)->where("message_id", "=", $msg)->where("id", "=", $i)->first(array("filename"));
        if (!$file) {
            exit("Invalid Project or Message ID");
        }
        $filename = $file->filename;
    } else {
        $file = WHMCSProjectManagement\Models\ProjectFile::whereProjectId($projectid)->where("message_id", "=", 0)->where("id", "=", $i)->first();
        if (!$file) {
            exit("Invalid Project ID");
        }
        $filename = $file->filename;
    }
    $storage = Storage::projectManagementFiles($projectid);
    try {
        $fileSize = $storage->getSizeStrict($filename);
    } catch (Exception $e) {
        if (WHMCS\Admin::getID()) {
            $extraMessage = "This could indicate that the file is missing or that <a href=\"" . routePath("admin-setup-storage-index") . "\" target=\"_blank\">storage configuration settings" . "</a> are misconfigured. " . "<a href=\"https://docs.whmcs.com/Storage_Settings#Troubleshooting_a_File_Not_Found_Error\" target=\"_blank\">" . "Learn more</a>";
        } else {
            $extraMessage = "Please contact support.";
        }
        exit("File not found. " . $extraMessage);
    }
    $disposition = App::getFromRequest("view") == "inline" ? "inline" : "attachment";
    header("Pragma: public");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
    header("Content-Disposition: " . $disposition . "; filename=\"" . substr($filename, 7) . "\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $fileSize);
    $stream = $storage->readStream($filename);
    echo stream_get_contents($stream);
    fclose($stream);
    exit;
}
if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (defined("PMADDONLICENSE")) {
    exit("License Hacking Attempt Detected");
}
global $whmcs;
global $licensing;
if ($whmcs->get_req_var("pmrefresh")) {
    $licensing->forceRemoteCheck();
}
define("PMADDONLICENSE", $licensing->isActiveAddon("Project Management Addon"));
function project_management_MetaData()
{
    return array("addonLicenseRequired" => true, "addonLicenseName" => "Project Management Addon");
}
function project_management_config()
{
    $configarray = array("name" => "Project Management", "version" => "2.1.0", "author" => "WHMCS", "language" => "english", "description" => "Track & manage projects, tasks & time with ease using the Official Project Management Addon for WHMCS.<br />Find out more & purchase @ <a href=\"http://go.whmcs.com/90/project-management\" target=\"_blank\">www.whmcs.com/addons/project-management</a>", "premium" => true, "fields" => array());
    if (!PMADDONLICENSE) {
        $configarray["fields"]["license"] = array("FriendlyName" => "License Check Failed", "Type" => "", "Description" => "You need to purchase the project management addon from <a href=\"http://go.whmcs.com/90/project-management\" target=\"_blank\">www.whmcs.com/addons/project-management</a> before you can use this functionality. If you just purchased it recently, please <a href=\"configaddonmods.php?pmrefresh=1#project_management\">click here</a> to refresh this message");
    }
    $fieldname = "Master Admin Users";
    $result = select_query("tbladminroles", "", "", "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $configarray["fields"]["masteradmin" . $data["id"]] = array("FriendlyName" => $fieldname, "Type" => "yesno", "Description" => "Allow Access to Settings for <strong>" . $data["name"] . "</strong> users");
        $fieldname = "";
    }
    return $configarray;
}
function project_management_activate()
{
    $query = "CREATE TABLE IF NOT EXISTS `mod_project` (`id` int(10) NOT NULL AUTO_INCREMENT,`userid` int(10) NOT NULL,`title` text NOT NULL,`ticketids` text NOT NULL,`invoiceids` text NOT NULL,`notes` text NOT NULL,`adminid` int(10) NOT NULL,`status` VARCHAR( 255 ) NOT NULL, `created` date NOT NULL,`duedate` date NOT NULL,`completed` int(1) NOT NULL,`lastmodified` datetime NOT NULL, `watchers` TEXT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    $query = "CREATE TABLE IF NOT EXISTS `mod_projectmessages` (`id` int(10) NOT NULL AUTO_INCREMENT, `projectid` int(10) NOT NULL, `date` datetime NOT NULL, `message` text NOT NULL, `adminid` int(10) NOT NULL, PRIMARY KEY (`id`) ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    $query = "CREATE TABLE IF NOT EXISTS `mod_projecttasks` ( `id` int(10) NOT NULL AUTO_INCREMENT, `projectid` int(10) NOT NULL, `task` text NOT NULL, `notes` TEXT NOT NULL, `adminid` int(11) NOT NULL, `created` DATETIME NOT NULL, `duedate` date NOT NULL, `completed` int(1) NOT NULL, `billed` INT(1) NOT NULL, `order` INT(3) NOT NULL, PRIMARY KEY (`id`) ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    $query = "CREATE TABLE IF NOT EXISTS `mod_projecttimes` ( `id` int(10) NOT NULL AUTO_INCREMENT, `projectid` int(10) NOT NULL, `taskid` int(10) NOT NULL, `adminid` VARCHAR(255) NOT NULL, `start` VARCHAR(255) NOT NULL, `end` VARCHAR(255) NOT NULL, `donotbill` INT(1) NOT NULL, PRIMARY KEY (`id`) ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    $query = "CREATE TABLE IF NOT EXISTS `mod_projecttasktpls` (`id` int(10) NOT NULL AUTO_INCREMENT, `name` text NOT NULL, `tasks` text NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    $query = "CREATE TABLE IF NOT EXISTS `mod_projectlog` (`id` INT(255) NOT NULL AUTO_INCREMENT PRIMARY KEY, `projectid` INT(11) NOT NULL, `date` DATETIME NOT NULL, `msg` VARCHAR(255) NOT NULL, `adminid` INT(11) NOT NULL) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $result = full_query($query);
    full_query("INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES('project_management', 'hourlyrate', '100.00')");
    full_query("INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES('project_management', 'statusvalues', 'Pending,In Progress,Awaiting,Abandoned,Completed')");
    full_query("INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES('project_management', 'completedstatuses', 'Abandoned,Completed')");
    full_query("INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES('project_management', 'perms', 'a:13:{i:0;a:3:{i:1;s:1:\"1\";i:2;s:1:\"1\";i:3;s:1:\"1\";}i:1;a:3:{i:1;s:1:\"1\";i:2;s:1:\"1\";i:3;s:1:\"1\";}i:2;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:3;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:4;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:5;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:6;a:1:{i:1;s:1:\"1\";}i:7;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:8;a:3:{i:1;s:1:\"1\";i:2;s:1:\"1\";i:3;s:1:\"1\";}i:9;a:3:{i:1;s:1:\"1\";i:2;s:1:\"1\";i:3;s:1:\"1\";}i:10;a:2:{i:1;s:1:\"1\";i:2;s:1:\"1\";}i:11;a:1:{i:1;s:1:\"1\";}i:12;a:1:{i:1;s:1:\"1\";}}')");
    project_management_email_templates();
    $files = new WHMCSProjectManagement\Models\ProjectFile();
    $files->createTable();
}
function project_management_deactivate()
{
    $query = "DROP TABLE `mod_project`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_projectmessages`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_projecttasks`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_projecttimes`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_projecttasktpls`";
    $result = full_query($query);
    $query = "DROP TABLE `mod_projectlog`";
    $result = full_query($query);
    $files = new WHMCSProjectManagement\Models\ProjectFile();
    $files->dropTable();
    WHMCS\Database\Capsule::table("tblemailtemplates")->where("name", "=", "Project Management: Admin Change Notification")->delete();
}
function project_management_email_templates()
{
    $emailTemplate = "{if \$newProject}\n    <p>Project <a href=\"{\$project_url}\">#{\$project_id}</a> has been created.</p>\n\n    <table class=\"keyvalue-table\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;\">\n        <tbody>\n            <tr>\n                <td>Project Name:</td>\n                <td>{\$project_name}</td>\n            </tr>\n            <tr>\n                <td>Assigned To:</td>\n                <td>{\$assigned_admin}</td>\n            </tr>\n            <tr>\n                <td>Due Date:</td>\n                <td>{\$due_date}</td>\n            </tr>\n            <tr>\n                <td>Created By:</td>\n                <td>{\$change_by}</td>\n            </tr>\n        </tbody>\n    </table>\n{else}\n    <p>Project <a href=\"{\$project_url}\">#{\$project_id}</a> has been updated.</p>\n\n    {if \$changes}\n        <table class=\"keyvalue-table\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;\">\n            <tbody>\n                <tr>\n                    <td>Change By</td>\n                    <td>{\$change_by}</td>\n                </tr>\n                {foreach \$changes as \$change}\n                    <tr>\n                        <td>{\$change.field}:</td>\n                        <td>\n                            <span style=\"background-color:#ffe7e7;text-decoration:line-through;\">{\$change.oldValue}</span>\n                            &nbsp;\n                            <span style=\"background-color:#ddfade;\">{\$change.newValue}</span>\n                        </td>\n                    </tr>\n                {/foreach}\n            </tbody>\n        </table>\n    {/if}\n\n{/if}\n\n{if \$message}\n    <div class=\"quoted-content\">\n        {\$message}\n    </div>\n{/if}";
    $email = WHMCS\Mail\Template::master()->where("name", "Project Management: Admin Change Notification")->first();
    if (!$email) {
        $email = new WHMCS\Mail\Template();
        $email->type = "admin";
        $email->name = "Project Management: Admin Change Notification";
        $email->subject = "[Project ID: {\$project_id}] {\$project_name}";
        $email->message = $emailTemplate;
        $email->custom = false;
        $email->save();
    }
}
function project_management_upgrade(array $vars = array())
{
    $version = $vars["version"];
    if ($version < 1.1) {
        $result = full_query("ALTER TABLE `mod_project`  ADD `invoiceids` TEXT NOT NULL AFTER `ticketids`");
        $result = full_query("ALTER TABLE `mod_projecttasks`  ADD `duedate` DATE NOT NULL AFTER `created`");
        $result = full_query("ALTER TABLE `mod_projecttasks`  ADD `notes` TEXT NOT NULL AFTER `task`");
        $result = full_query("ALTER TABLE `mod_projecttasks`  ADD `adminid` INT(11) NOT NULL AFTER `notes`");
        $result = full_query("ALTER TABLE `mod_projecttasks`  ADD `billed` INT(1) NOT NULL AFTER `completed`");
        $result = full_query("ALTER TABLE `mod_projecttasks`  ADD `order` INT(3) NOT NULL AFTER `billed`");
        $result = full_query("ALTER TABLE `mod_projecttimes`  ADD `donotbill` INT(1) NOT NULL");
        $query = "CREATE TABLE IF NOT EXISTS `mod_projecttasktpls` (`id` int(10) NOT NULL AUTO_INCREMENT, `name` text NOT NULL, `tasks` text NOT NULL, PRIMARY KEY (`id`))";
        $result = full_query($query);
    }
    if ($version < 2) {
        $result = full_query("ALTER TABLE `mod_project`  ADD `watchers` TEXT NOT NULL");
        $files = new WHMCSProjectManagement\Models\ProjectFile();
        $files->createTable();
        $existing = WHMCS\Database\Capsule::table("mod_project")->where("attachments", "!=", "")->orWhere("attachments", "!=", ",")->get(array("id", "attachments", "adminid", "invoiceids", "ticketids"));
        foreach ($existing as $existingFile) {
            $fileList = explode(",", $existingFile->attachments);
            foreach ($fileList as $singleFile) {
                if ($singleFile) {
                    $files = new WHMCSProjectManagement\Models\ProjectFile();
                    $files->projectId = $existingFile->id;
                    $files->adminId = $existingFile->adminid;
                    $files->filename = $singleFile;
                    $files->messageId = 0;
                    $files->save();
                }
            }
            $invoiceIds = array_filter(explode(",", $existingFile->invoiceids));
            $ticketIds = array_filter(explode(",", $existingFile->ticketids));
            WHMCS\Database\Capsule::table("mod_project")->where("id", $existingFile->id)->update(array("invoiceids" => implode(",", $invoiceIds), "ticketids" => implode(",", $ticketIds)));
        }
        $existingFiles = WHMCS\Database\Capsule::table("mod_projectmessages")->where("attachments", "!=", "")->orWhere("attachments", "!=", ",")->get(array("id", "projectid", "attachments", "adminid"));
        foreach ($existingFiles as $existingFile) {
            $fileList = explode(",", $existingFile->attachments);
            foreach ($fileList as $singleFile) {
                if ($singleFile) {
                    $files = new WHMCSProjectManagement\Models\ProjectFile();
                    $files->projectId = $existingFile->projectid;
                    $files->adminId = $existingFile->adminid;
                    $files->filename = $singleFile;
                    $files->messageId = $existingFile->id;
                    $files->save();
                }
            }
        }
    }
    project_management_email_templates();
}
function project_management_output($vars)
{
    global $whmcs;
    global $licensing;
    global $CONFIG;
    global $aInt;
    global $numrows;
    global $page;
    global $limit;
    global $order;
    global $orderby;
    global $jquerycode;
    global $jscode;
    global $attachments_dir;
    require ROOTDIR . "/includes/clientfunctions.php";
    require ROOTDIR . "/includes/invoicefunctions.php";
    $modulelink = $vars["modulelink"];
    $perms = safe_unserialize($vars["perms"]);
    $m = $_REQUEST["m"];
    $a = $_REQUEST["a"];
    $action = $_REQUEST["action"];
    if (!PMADDONLICENSE) {
        if ($whmcs->get_req_var("refresh")) {
            $licensing->forceRemoteCheck();
            redir("module=project_management");
        }
        echo "<div class=\"gracefulexit\">\nYour WHMCS license key is not enabled to use the Project Management Addon yet.<br /><br />\nYou can find out more about it and purchase @ <a href=\"http://go.whmcs.com/90/project-management\" target=\"_blank\">www.whmcs.com/addons/project-management</a><br /><br />\nIf you have only recently purchased the addon, please <a href=\"addonmodules.php?module=project_management&refresh=1\">click here</a> to perform a license refresh.\n</div>";
        return false;
    }
    $project = NULL;
    $projectId = (int) App::getFromRequest("projectid");
    if ($projectId) {
        $project = new WHMCSProjectManagement\Project($projectId, $vars["_lang"]);
    }
    if ($_REQUEST["createproj"]) {
        check_token("WHMCS.admin.default");
        echo WHMCSProjectManagement\Project::create($vars);
        exit;
    }
    $ajax = App::getFromRequest("ajax");
    if ($ajax) {
        check_token("WHMCS.admin.default");
        $action = App::getFromRequest("action");
        $router = new WHMCSProjectManagement\Router();
        try {
            $response = $router->dispatch($action, $project);
        } catch (Exception $e) {
            $response = array("status" => "0", "error" => $e->getMessage());
        }
        $debug = false;
        if ($debug) {
            $response = array_merge($response, array("debug" => print_r($_REQUEST, 1)));
        }
        echo json_encode($response);
        exit;
    }
    $jscode = "function createnewproject() {\n    \$(\"#createnewcont\").slideDown();\n}\nfunction cancelnewproject() {\n    \$(\"#createnewcont\").slideUp();\n}\nfunction searchselectclient(userid,name,email) {\n    \$(\"#clientname\").val(name);\n    \$(\"#userid\").val(userid);\n    \$(\"#cpclientname\").val(name);\n    \$(\"#cpuserid\").val(userid);\n    \$(\"#cpclientsearchcancel\").fadeOut();\n    \$(\"#cpticketclientsearchresults\").slideUp(\"slow\");\n}\n";
    $jquerycode = "\$(\"#cpclientname\").keyup(function () {\n    var ticketuseridsearchlength = \$(\"#cpclientname\").val().length;\n    if (ticketuseridsearchlength>2) {\n    WHMCS.http.jqClient.post(\"search.php\", { ticketclientsearch: 1, value: \$(\"#cpclientname\").val(), token: \"" . generate_token("plain") . "\" },\n        function(data){\n            if (data) {\n                \$(\"#cpticketclientsearchresults\").html(data);\n                \$(\"#cpticketclientsearchresults\").slideDown(\"slow\");\n                \$(\"#cpclientsearchcancel\").fadeIn();\n            }\n        });\n    }\n});\n\$(\"#cpclientsearchcancel\").click(function () {\n    \$(\"#cpticketclientsearchresults\").slideUp(\"slow\");\n    \$(\"#cpclientsearchcancel\").fadeOut();\n});";
    $headeroutput = "\n<link href=\"../modules/addons/project_management/css/style.css?v=5\" rel=\"stylesheet\" type=\"text/css\" />\n<link href=\"../modules/addons/project_management/assets/css/styles.min.css?v=5\" rel=\"stylesheet\" type=\"text/css\" />\n\n<div class=\"projectmanagement\">";
    if (project_management_checkperm("Create New Projects")) {
        $headeroutput .= "\n<div id=\"createnewcont\" style=\"display:none;\">\n    <div class=\"createnewcont2\">\n        <div class=\"createnewproject\">\n            <div class=\"title\">" . $vars["_lang"]["createnewproject"] . "</div>\n            <form method=\"post\" action=\"" . $modulelink . "&createproj=1\">\n                <div class=\"row\">\n                    <div class=\"col-sm-8 leftCol\">\n                        <div class=\"form-group\">\n                            <label for=\"inputTitle\">" . $vars["_lang"]["title"] . "</label>\n                            <input type=\"text\" name=\"title\" id=\"inputTitle\" class=\"form-control\" placeholder=\"" . $vars["_lang"]["title"] . "\" />\n                        </div>\n                    </div>\n                    <div class=\"col-sm-4 rightCol\">\n                        <div class=\"form-group\">\n                            <label for=\"inputTicketNumber\">" . $vars["_lang"]["ticketnumberhash"] . "</label>\n                            <input type=\"text\" name=\"ticketnum\" id=\"inputTicketNumber\" class=\"form-control\" placeholder=\"" . $vars["_lang"]["ticketnumberhash"] . "\" />\n                        </div>\n                    </div>\n                </div>\n                <div class=\"row\">\n                    <div class=\"col-sm-6 leftCol\">\n                        <div class=\"form-group\">\n                            <label for=\"inputAssignedTo\">" . $vars["_lang"]["assignedto"] . "</label>\n                                <select class=\"form-control\" name=\"adminid\" id=\"inputAssignedTo\">";
        $headeroutput .= "<option value=\"0\">" . $vars["_lang"]["none"] . "</option>";
        $result = select_query("tbladmins", "id,firstname,lastname", array("disabled" => "0"), "firstname` ASC,`lastname", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $aid = $data["id"];
            $adminfirstname = $data["firstname"];
            $adminlastname = $data["lastname"];
            $headeroutput .= "<option value=\"" . $aid . "\"";
            if ($aid == $adminid) {
                echo " selected";
            }
            $headeroutput .= ">" . $adminfirstname . " " . $adminlastname . "</option>";
        }
        $headeroutput .= "</select>\n                        </div>\n                    </div>\n                    <div class=\"col-sm-6 rightCol\">\n                        <div class=\"form-group\">\n                            <label for=\"cpclientname\">" . $vars["_lang"]["associatedclient"] . "</label>\n                            <input type=\"hidden\" name=\"userid\" id=\"cpuserid\" />\n                            <input type=\"text\" id=\"cpclientname\" value=\"" . $clientname . "\" class=\"form-control\" placeholder=\"" . $vars["_lang"]["associatedclient"] . "\" onfocus=\"if(this.value=='" . addslashes($clientname) . "')this.value=''\" /> <img src=\"images/icons/delete.png\" alt=\"" . $vars["_lang"]["cancel"] . "\" align=\"right\" id=\"clientsearchcancel\" height=\"16\" width=\"16\"><div id=\"cpticketclientsearchresults\" style=\"z-index:2000;\"></div>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"row\">\n                    <div class=\"col-sm-6 leftCol\">\n                        <div class=\"form-group date-picker-prepend-icon\">\n                            <label for=\"inputCreatedDate\">" . $vars["_lang"]["created"] . "</label>\n                            <label for=\"inputCreatedDate\" class=\"field-icon\">\n                                <i class=\"fal fa-calendar-alt\"></i>\n                            </label>\n                            <input id=\"inputCreatedDate\"\n                                   type=\"text\"\n                                   name=\"created\"\n                                   value=\"" . getTodaysDate() . "\"\n                                   class=\"form-control date-picker-single\"\n                            />\n                        </div>\n                    </div>\n                    <div class=\"col-sm-6 rightCol\">\n                        <div class=\"form-group date-picker-prepend-icon\">\n                            <label for=\"inputDueDate\">" . $vars["_lang"]["duedate"] . "</label>\n                            <label for=\"inputDueDate\" class=\"field-icon\">\n                                <i class=\"fal fa-calendar-alt\"></i>\n                            </label>\n                            <input id=\"inputDueDate\"\n                                   type=\"text\"\n                                   name=\"duedate\"\n                                   value=\"" . getTodaysDate() . "\"\n                                   class=\"form-control date-picker-single future\"\n                            />\n                        </div>\n                    </div>\n                </div>\n                <div class=\"text-center\">\n                    <input type=\"submit\" value=\"" . $vars["_lang"]["create"] . "\" class=\"btn btn-success\" />\n                    <input type=\"button\" value=\"" . $vars["_lang"]["cancel"] . "\" class=\"btn btn-default\" onclick=\"cancelnewproject();return false\" />\n                </div>\n            </form>\n        </div>\n    </div>\n</div>";
    }
    $headeroutput .= "\n\n<div class=\"pm-addon\">\n\n    <div class=\"top-nav-container\">\n        <div class=\"btn-group\">\n            <a href=\"" . $modulelink . "\" class=\"btn btn-default\"><i class=\"fas fa-home fa-fw\"></i> " . $vars["_lang"]["home"] . "</a>\n            " . (project_management_checkperm("Create New Projects") ? "<a href=\"#\" onclick=\"createnewproject();return false\" class=\"btn btn-default create\"><i class=\"fas fa-plus fa-fw\"></i> " . $vars["_lang"]["newproject"] . "</a>" : "") . "\n            " . ($m == "view" && project_management_checkperm("Create New Projects") ? "<button type=\"button\" id=\"btnDuplicateProject\" class=\"btn btn-default\" onclick=\"ProjectManager.confirm('duplicateProject')\"><i class=\"fas fa-clone fa-fw\"></i> " . $vars["_lang"]["duplicate"] . "</button>" : "") . "\n            " . (project_management_check_masteradmin() ? "<a href=\"" . $modulelink . "&m=settings\" class=\"btn btn-default\"><i class=\"fas fa-cog fa-fw\"></i> " . $vars["_lang"]["settings"] . "</a>" : "") . "\n            <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n                <span class=\"caret\"></span>\n                <span class=\"sr-only\">Toggle Dropdown</span>\n            </button>\n            <ul class=\"dropdown-menu dropdown-menu-right\">" . (project_management_checkperm("View Reports") ? "<li><a href=\"reports.php?moduletype=addons&modulename=project_management&subdir=reports&report=project_staff_logs\" target=\"_blank\">" . $vars["_lang"]["viewstafflogs"] . "</a></li>\n                <li><a href=\"reports.php?moduletype=addons&modulename=project_management&subdir=reports&report=project_summary\" target=\"_blank\">" . $vars["_lang"]["viewprojectssummary"] . "</a></li>\n                <li><a href=\"reports.php?moduletype=addons&modulename=project_management&subdir=reports&report=project_time_logs\" target=\"_blank\">" . $vars["_lang"]["viewtimelogs"] . "</a></li>\n                <li role=\"separator\" class=\"divider\"></li>" : "") . "\n                <li><a href=\"https://docs.whmcs.com/Project_Management\" target=\"_blank\">" . $vars["_lang"]["help"] . "</a></li>\n            </ul>\n        </div>\n    </div>\n\n</div>\n\n";
    if (!in_array($m, array("view", "activity", "reports", "settings"))) {
        $m = "overview";
    }
    $modulelink .= "&m=" . $m;
    require ROOTDIR . "/modules/addons/project_management/" . $m . ".php";
    echo "</div>";
}
function project_management_daysleft($duedate, $vars)
{
    if ($duedate == "0000-00-00") {
        return "<span style=\"color:#73BC10\">" . $vars["_lang"]["noduedate"] . "</span>";
    }
    $datetime = strtotime("now");
    $date2 = strtotime($duedate);
    $days = ceil(($date2 - $datetime) / 86400);
    if ($days == "-0") {
        $days = 0;
    }
    $dueincolor = $days < 2 ? "cc0000" : "73BC10";
    if (0 <= $days) {
        return "<span style=\"color:#" . $dueincolor . "\">" . $vars["_lang"]["duein"] . " " . $days . " " . $vars["_lang"]["days"] . "</span>";
    }
    return "<span style=\"color:#" . $dueincolor . "\">" . $vars["_lang"]["due"] . " " . $days * -1 . " " . $vars["_lang"]["daysago"] . "</span>";
}
function project_management_tasksstatus($projectid, $vars)
{
    $totaltasks = get_query_val("mod_projecttasks", "COUNT(id)", array("projectid" => $projectid));
    $completed = get_query_val("mod_projecttasks", "COUNT(id)", array("projectid" => $projectid, "completed" => "1"));
    $html = "<span class=\"" . ($totaltasks == $completed ? "green" : "red") . "\">" . $totaltasks . " " . $vars["_lang"]["tasks"] . "</span> / " . $completed . " " . $vars["_lang"]["completed"];
    $percent = $totaltasks <= 0 ? 0 : round($completed / $totaltasks * 100);
    return array("completed" => $completed, "total" => $totaltasks, "percent" => $percent, "html" => $html);
}
function project_management_log($projectid, $msg)
{
    insert_query("mod_projectlog", array("projectid" => $projectid, "date" => "now()", "msg" => $msg, "adminid" => $_SESSION["adminid"]));
    update_query("mod_project", array("lastmodified" => "now()"), array("id" => $projectid));
}
function project_management_sec2hms($sec, $padHours = false)
{
    if ($sec <= 0) {
        $sec = 0;
    }
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= $padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":";
    $minutes = intval($sec / 60 % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT);
    return $hms;
}
function project_management_checkperm($perm)
{
    $permissions = new WHMCSProjectManagement\Permission();
    return $permissions->check($perm);
}
function project_management_permslist()
{
    return WHMCSProjectManagement\Permission::getPermissionList();
}
function project_management_check_viewproject($projectid, $adminid = "")
{
    if (!$adminid) {
        $adminid = $_SESSION["adminid"];
    }
    if (project_management_checkperm("View All Projects")) {
        return true;
    }
    $projectid = get_query_val("mod_project", "id", array("id" => $projectid));
    if (!$projectid) {
        return false;
    }
    if (project_management_checkperm("View Only Assigned Projects")) {
        $projectadminid = get_query_val("mod_project", "adminid", array("id" => $projectid));
        if ($adminid == $projectadminid) {
            return true;
        }
        $tasksresult = select_query("mod_projecttasks", "adminid", array("projectid" => $projectid));
        while ($tasksdata = mysql_fetch_assoc($tasksresult)) {
            if ($adminid == $tasksdata["adminid"]) {
                return true;
            }
        }
    }
    return false;
}
function project_management_check_masteradmin($PMRoleID = "", $adminid = "")
{
    if (!$PMRoleID) {
        $PMRoleID = get_query_val("tbladmins", "roleid", array("id" => $adminid ? $adminid : $_SESSION["adminid"]));
    }
    if (get_query_val("tbladdonmodules", "value", array("module" => "project_management", "setting" => "masteradmin" . $PMRoleID)) == "on") {
        return true;
    }
    return false;
}
function project_management_clientarea($vars)
{
    $pageTitle = "";
    $tagline = "";
    $breadcrumb = array("clientarea.php" => Lang::trans("clientareatitle"), "index.php?m=project_management" => $vars["_lang"]["projectsoverview"]);
    $tplfile = "";
    $tplvars = array();
    require ROOTDIR . "/modules/addons/project_management/clientarea.php";
    return array("pagetitle" => $pageTitle, "tagline" => $tagline, "breadcrumb" => $breadcrumb, "templatefile" => $tplfile, "vars" => $tplvars, "forcessl" => true, "requirelogin" => true);
}
function pm_get_gravatar($email, $s = 80, $d = "mm", $r = "g", $img = false, $atts = array())
{
    $url = "https://www.gravatar.com/avatar/";
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=" . $s . "&d=" . $d . "&r=" . $r;
    if ($img) {
        $url = "<img src=\"" . $url . "\"";
        foreach ($atts as $key => $val) {
            $url .= " " . $key . "=\"" . $val . "\"";
        }
        $url .= " />";
    }
    return $url;
}

?>