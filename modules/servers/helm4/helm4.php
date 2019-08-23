<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function helm4_MetaData()
{
    return array("DisplayName" => "Helm 4", "APIVersion" => "1.0", "DefaultNonSSLPort" => "8086");
}
function helm4_ConfigOptions()
{
    $configarray = array("Account Role ID" => array("Type" => "text", "Size" => "5", "Description" => ""), "Plan ID" => array("Type" => "text", "Size" => "5", "Description" => ""));
    return $configarray;
}
function helm4_ClientArea($params)
{
    global $_LANG;
    $form = sprintf("<form action=\"http://%s:%s/\" method=\"post\" target=\"_blank\">" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], $_LANG["helmlogin"]);
    return $form;
}
function helm4_AdminLink($params)
{
    $form = sprintf("<form action=\"http://%s:%s/\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"txtAccountName\" value=\"%s\" />" . "<input type=\"hidden\" name=\"txtUserName\" value=\"%s\" />" . "<input type=\"hidden\" name=\"txtPassword\" value=\"%s\" />" . "<input type=\"hidden\" name=\"btnLogin\" value=\"Login\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "Helm");
    return $form;
}
function helm4_CreateAccount($params)
{
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetAccountByName", "UserAccountName" => $params["clientsdetails"]["email"], "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $helmaccountid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["ACCOUNTID"];
    if ($helmaccountid) {
        $helmaccountusername = $result["RESULTS"]["RESULTDATA"]["RECORD"]["PRIMARYLOGINNAME"];
        $data = get_query_vals("tblhosting", "username,password", array("username" => $helmaccountusername));
        if (!$data) {
            $addons = WHMCS\Service\Addon::whereHas("customFieldValues.customField", function ($query) {
                $query->whereIn("fieldname", array("Username", "Password"));
            })->with("customFieldValues", "customFieldValues.customField")->get();
            foreach ($addons as $addon) {
                $updatedUsername = $updatedPassword = "";
                foreach ($addon->customFieldValues as $customFieldValue) {
                    if (!$customFieldValue->customField) {
                        continue;
                    }
                    if ($updatedUsername && $updatedPassword) {
                        break;
                    }
                    if ($customFieldValue->customField->fieldName == "Username") {
                        $updatedUsername = $customFieldValue->value;
                    } else {
                        if ($customFieldValue->customField->fieldName == "Password") {
                            $updatedPassword = $customFieldValue->value;
                        }
                    }
                }
                if ($updatedUsername && $updatedPassword) {
                    $params["model"]->serviceProperties->save(array("username" => $updatedUsername, "password" => $updatedPassword));
                    break;
                }
            }
        } else {
            $params["model"]->serviceProperties->save(array("username" => $data["username"], "password" => $data["password"]));
        }
    }
    if (!$helmaccountid) {
        $country = $params["clientsdetails"]["country"];
        if ($country == "UK") {
            $country = "GB";
        }
        $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "CreateAccount", "AccountRoleId" => $params["configoption1"], "NewAccountName" => $params["clientsdetails"]["email"], "CompanyName" => $params["clientsdetails"]["firstname"] . " " . $params["clientsdetails"]["lastname"], "AccountEmailAddress" => $params["clientsdetails"]["email"], "AdminLoginName" => $params["username"], "AdminLoginPassword" => $params["password"], "AdminEmailAddress" => $params["clientsdetails"]["email"], "FirstName" => $params["clientsdetails"]["firstname"], "LastName" => $params["clientsdetails"]["lastname"], "Address1" => $params["clientsdetails"]["address1"], "Address2" => $params["clientsdetails"]["address2"], "Address3" => "", "Town" => $params["clientsdetails"]["city"], "PostCode" => $params["clientsdetails"]["postcode"], "CountryCode" => $country, "CountyName" => $params["clientsdetails"]["county"]);
        $result = helm4_connect($params, $fields);
        $resultcode = $result["RESULTS"]["RESULTCODE"];
        $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
        $helmaccountid = $result["RESULTS"]["RESULTDATA"];
        if ($resultcode != "0") {
            return $resultdescription;
        }
    }
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "AddPackage", "UserAccountID" => $helmaccountid, "PlanID" => $params["configoption2"], "PackageName" => $params["domain"], "Quantity" => "1");
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    $helmpackageid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["PACKAGEID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "AddDomainToPackage", "UserAccountID" => $helmaccountid, "PackageID" => $helmpackageid, "DomainName" => $params["domain"], "IsPark" => "false");
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    return "success";
}
function helm4_SuspendAccount($params)
{
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetAccountByName", "UserAccountName" => $params["clientsdetails"]["email"], "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $helmaccountid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["ACCOUNTID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "SuspendAccount", "UserAccountId" => $helmaccountid, "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    return "success";
}
function helm4_UnsuspendAccount($params)
{
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetAccountByName", "UserAccountName" => $params["clientsdetails"]["email"], "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $helmaccountid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["ACCOUNTID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "UnsuspendAccount", "UserAccountId" => $helmaccountid, "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    return "success";
}
function helm4_TerminateAccount($params)
{
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetAccountByName", "UserAccountName" => $params["clientsdetails"]["email"], "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $helmaccountid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["ACCOUNTID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetPackages", "UserAccountId" => $helmaccountid);
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    $rawxml = $result["raw"];
    $output = explode("<Record>", $rawxml);
    foreach ($output as $data) {
        $data = XMLtoARRAY("<Record>" . $data);
        $data = $data["RECORD"];
        if ($data) {
            $helmpackagesarray[$data["NAME"]] = $data;
        }
    }
    $helmpackageid = $helmpackagesarray[$params["domain"]]["PACKAGEID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetDomainsByPackageID", "UserAccountId" => $helmaccountid, "PackageID" => $helmpackageid);
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    $rawxml = $result["raw"];
    $output = explode("<Record>", $rawxml);
    foreach ($output as $data) {
        $data = XMLtoARRAY("<Record>" . $data);
        $data = $data["RECORD"];
        if ($data) {
            $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "DeleteDomain", "DomainID" => $data["DOMAINID"]);
            $result = helm4_connect($params, $fields);
        }
    }
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "DeletePackage", "UserAccountId" => $helmaccountid, "PackageID" => $helmpackageid);
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    return "success";
}
function helm4_ChangePackage($params)
{
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetAccountByName", "UserAccountName" => $params["clientsdetails"]["email"], "IncludeChildren" => "false");
    $result = helm4_connect($params, $fields);
    $helmaccountid = $result["RESULTS"]["RESULTDATA"]["RECORD"]["ACCOUNTID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "GetPackages", "UserAccountId" => $helmaccountid);
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    $rawxml = $result["raw"];
    $output = explode("<Record>", $rawxml);
    foreach ($output as $data) {
        $data = XMLtoARRAY("<Record>" . $data);
        $data = $data["RECORD"];
        if ($data) {
            $helmpackagesarray[$data["NAME"]] = $data;
        }
    }
    $helmpackageid = $helmpackagesarray[$params["domain"]]["PACKAGEID"];
    $fields = array("AccountName" => $params["serverusername"], "Username" => $params["serverusername"], "Password" => $params["serverpassword"], "action" => "UpgradePackage", "UserAccountId" => $helmaccountid, "PackageID" => $helmpackageid, "NewPlanID" => $params["configoption2"]);
    $result = helm4_connect($params, $fields);
    $resultcode = $result["RESULTS"]["RESULTCODE"];
    $resultdescription = $result["RESULTS"]["RESULTDESCRIPTION"];
    if ($resultcode != "0") {
        return $resultdescription;
    }
    return "success";
}
function helm4_connect($params, $fields)
{
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . ":" . $params["serverport"] . "/ServiceAPI/HttpAPI.aspx";
    $query_string = http_build_query($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = " Curl Error - " . curl_error($ch) . " (" . curl_errno($ch) . ")";
    }
    curl_close($ch);
    $result = XMLtoARRAY($data);
    logModuleCall("helm4", $fields["action"], $fields, $data, $result, array($fields["AccountName"], $fields["Username"], $fields["Password"]));
    $result["raw"] = $data;
    return $result;
}

?>