<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function autorelease_MetaData()
{
    return array("DisplayName" => "Auto Release", "APIVersion" => "1.0", "RequiresServer" => false, "AutoGenerateUsernameAndPassword" => false);
}
function autorelease_ConfigOptions()
{
    $depts = array();
    $depts[] = "0|None";
    $result = select_query("tblticketdepartments", "", "", "order", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = $data["name"];
        $depts[] = (string) $id . "|" . $name;
    }
    $adminUsers = array();
    $adminUsers[] = "0|Please Select";
    $admins = WHMCS\User\Admin::where("disabled", "=", false)->get();
    foreach ($admins as $admin) {
        $adminUsers[] = (string) $admin->id . "|" . $admin->firstName . " " . $admin->lastName . " (" . $admin->username . ")";
    }
    $configarray = array("Create Action" => array("Type" => "dropdown", "Options" => "None,Add To-Do List Item,Create Support Ticket"), "Suspend Action" => array("Type" => "dropdown", "Options" => "None,Add To-Do List Item,Create Support Ticket"), "Unsuspend Action" => array("Type" => "dropdown", "Options" => "None,Add To-Do List Item,Create Support Ticket"), "Terminate Action" => array("Type" => "dropdown", "Options" => "None,Add To-Do List Item,Create Support Ticket"), "Renew Action" => array("Type" => "dropdown", "Options" => "None,Add To-Do List Item,Create Support Ticket"), "Support Dept ID" => array("Type" => "dropdown", "Options" => implode(",", $depts)), "Admin ID" => array("Type" => "dropdown", "Options" => implode(",", $adminUsers), "Description" => " Select the Admin User the API commands will be run as"));
    return $configarray;
}
function autorelease_CreateAccount($params)
{
    if ($params["configoption1"] == "Add To-Do List Item") {
        $todoarray["title"] = "Service Provisioned";
        $todoarray["description"] = "Service ID # " . $params["serviceid"] . " was just auto provisioned";
        $todoarray["status"] = "Pending";
        $todoarray["duedate"] = date("Y-m-d");
        $todoarray["date"] = $todoarray["duedate"];
        insert_query("tbltodolist", $todoarray);
    } else {
        if ($params["configoption1"] == "Create Support Ticket") {
            $params["configoption6"] = explode("|", $params["configoption6"]);
            $params["configoption7"] = explode("|", $params["configoption7"]);
            if ($params["configoption6"][0] == "0") {
                return array("error" => "Please select a Support Department ID in the product Module Settings");
            }
            if ($params["configoption7"][0] == "0") {
                return array("error" => "Please select an Admin ID in the product Module Settings");
            }
            $postfields["action"] = "openticket";
            $postfields["clientid"] = $params["clientsdetails"]["userid"];
            $postfields["deptid"] = $params["configoption6"][0];
            $postfields["subject"] = "Service Provisioned";
            $postfields["message"] = "Service ID # " . $params["serviceid"] . " was just auto provisioned";
            $postfields["priority"] = "Low";
            $response = localAPI($postfields["action"], $postfields, $params["configoption7"][0]);
            if ($response["result"] == "error") {
                return "An Error Occurred communicating with the API: " . $response["message"];
            }
        }
    }
    return "success";
}
function autorelease_SuspendAccount($params)
{
    if ($params["configoption2"] == "Add To-Do List Item") {
        $todoarray["title"] = "Service Suspension";
        $todoarray["description"] = "Service ID # " . $params["serviceid"] . " requires suspension";
        $todoarray["status"] = "Pending";
        $todoarray["duedate"] = date("Y-m-d");
        $todoarray["date"] = $todoarray["duedate"];
        insert_query("tbltodolist", $todoarray);
    } else {
        if ($params["configoption2"] == "Create Support Ticket") {
            $params["configoption6"] = explode("|", $params["configoption6"]);
            $params["configoption7"] = explode("|", $params["configoption7"]);
            if ($params["configoption6"][0] == "0") {
                return array("error" => "Please select a Support Department ID in the product Module Settings");
            }
            if ($params["configoption7"][0] == "0") {
                return array("error" => "Please select an Admin ID in the product Module Settings");
            }
            $postfields["action"] = "openticket";
            $postfields["clientid"] = $params["clientsdetails"]["userid"];
            $postfields["deptid"] = $params["configoption6"][0];
            $postfields["subject"] = "Service Suspension";
            $postfields["message"] = "Service ID # " . $params["serviceid"] . " requires suspension";
            $postfields["priority"] = "Low";
            $response = localAPI($postfields["action"], $postfields, $params["configoption7"][0]);
            if ($response["result"] == "error") {
                return "An Error Occurred communicating with the API: " . $response["message"];
            }
        }
    }
    return "success";
}
function autorelease_UnsuspendAccount($params)
{
    if ($params["configoption3"] == "Add To-Do List Item") {
        $todoarray["title"] = "Service Reactivation";
        $todoarray["description"] = "Service ID # " . $params["serviceid"] . " requires unsuspending";
        $todoarray["status"] = "Pending";
        $todoarray["duedate"] = date("Y-m-d");
        $todoarray["date"] = $todoarray["duedate"];
        insert_query("tbltodolist", $todoarray);
    } else {
        if ($params["configoption3"] == "Create Support Ticket") {
            $params["configoption6"] = explode("|", $params["configoption6"]);
            $params["configoption7"] = explode("|", $params["configoption7"]);
            if ($params["configoption6"][0] == "0") {
                return array("error" => "Please select a Support Department ID in the product Module Settings");
            }
            if ($params["configoption7"][0] == "0") {
                return array("error" => "Please select an Admin ID in the product Module Settings");
            }
            $postfields["action"] = "openticket";
            $postfields["clientid"] = $params["clientsdetails"]["userid"];
            $postfields["deptid"] = $params["configoption6"][0];
            $postfields["subject"] = "Service Reactivation";
            $postfields["message"] = "Service ID # " . $params["serviceid"] . " requires unsuspending";
            $postfields["priority"] = "Low";
            $response = localAPI($postfields["action"], $postfields, $params["configoption7"][0]);
            if ($response["result"] == "error") {
                return "An Error Occurred communicating with the API: " . $response["message"];
            }
        }
    }
    return "success";
}
function autorelease_TerminateAccount($params)
{
    if ($params["configoption4"] == "Add To-Do List Item") {
        $todoarray["title"] = "Service Termination";
        $todoarray["description"] = "Service ID # " . $params["serviceid"] . " requires termination";
        $todoarray["status"] = "Pending";
        $todoarray["duedate"] = date("Y-m-d");
        $todoarray["date"] = $todoarray["duedate"];
        insert_query("tbltodolist", $todoarray);
    } else {
        if ($params["configoption4"] == "Create Support Ticket") {
            $params["configoption6"] = explode("|", $params["configoption6"]);
            $params["configoption7"] = explode("|", $params["configoption7"]);
            if ($params["configoption6"][0] == "0") {
                return array("error" => "Please select a Support Department ID in the product Module Settings");
            }
            if ($params["configoption7"][0] == "0") {
                return array("error" => "Please select an Admin ID in the product Module Settings");
            }
            $postfields["action"] = "openticket";
            $postfields["clientid"] = $params["clientsdetails"]["userid"];
            $postfields["deptid"] = $params["configoption6"][0];
            $postfields["subject"] = "Service Termination";
            $postfields["message"] = "Service ID # " . $params["serviceid"] . " requires termination";
            $postfields["priority"] = "Low";
            $response = localAPI($postfields["action"], $postfields, $params["configoption7"][0]);
            if ($response["result"] == "error") {
                return "An Error Occurred communicating with the API: " . $response["message"];
            }
        }
    }
    return "success";
}
function autorelease_Renew($params)
{
    if ($params["configoption5"] == "Add To-Do List Item") {
        $todoarray["title"] = "Service Renewal";
        $todoarray["description"] = "Service ID # " . $params["serviceid"] . " was just renewed";
        $todoarray["status"] = "Pending";
        $todoarray["duedate"] = date("Y-m-d");
        $todoarray["date"] = $todoarray["duedate"];
        insert_query("tbltodolist", $todoarray);
    } else {
        if ($params["configoption5"] == "Create Support Ticket") {
            $params["configoption6"] = explode("|", $params["configoption6"]);
            $params["configoption7"] = explode("|", $params["configoption7"]);
            if ($params["configoption6"][0] == "0") {
                return array("error" => "Please select a Support Department ID in the product Module Settings");
            }
            if ($params["configoption7"][0] == "0") {
                return array("error" => "Please select an Admin ID in the product Module Settings");
            }
            $postfields["action"] = "openticket";
            $postfields["clientid"] = $params["clientsdetails"]["userid"];
            $postfields["deptid"] = $params["configoption6"][0];
            $postfields["subject"] = "Service Renewal";
            $postfields["message"] = "Service ID # " . $params["serviceid"] . " was just renewed";
            $postfields["priority"] = "Low";
            $response = localAPI($postfields["action"], $postfields, $params["configoption7"][0]);
            if ($response["result"] == "error") {
                return "An Error Occurred communicating with the API: " . $response["message"];
            }
        }
    }
    return "success";
}

?>