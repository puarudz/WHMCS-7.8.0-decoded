<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function helm_MetaData()
{
    return array("DisplayName" => "Helm", "APIVersion" => "1.0");
}
function helm_ConfigOptions()
{
    $configarray = array("Package ID" => array("Type" => "text", "Size" => "25"), "Package Name" => array("Type" => "text", "Size" => "25"), "Reseller Plan ID" => array("Type" => "text", "Size" => "25", "Description" => "Only if reseller account"), "Helm Username" => array("Type" => "yesno", "Description" => "Let Helm Generate Account User ID"));
    return $configarray;
}
function helm_ClientArea($params)
{
    global $_LANG;
    $form = sprintf("<form action=\"%s://%s/\" method=\"post\" target=\"_blank\">" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $_LANG["helmlogin"]);
    return $form;
}
function helm_AdminLink($params)
{
    $form = sprintf("<form action=\"%s://%s/\" method=\"post\" target=\"_blank\">" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), "Helm");
    return $form;
}
function helm_CreateAccount($params)
{
    if ($params["configoption4"]) {
        $params["username"] = "";
    }
    if ($params["clientsdetails"]["country"] == "UK") {
        $params["clientsdetails"]["country"] = "GB";
    }
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
    $query_string = "action=AddUser&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
    $query_string .= "&FirstName=" . urlencode($params["clientsdetails"]["firstname"]);
    $query_string .= "&LastName=" . urlencode($params["clientsdetails"]["lastname"]);
    $query_string .= "&PrimaryEmail=" . $params["clientsdetails"]["email"];
    $query_string .= "&Address1=" . urlencode($params["clientsdetails"]["address1"]);
    $query_string .= "&PostCode=" . urlencode($params["clientsdetails"]["postcode"]);
    $query_string .= "&CountryCode=" . $params["clientsdetails"]["country"];
    $query_string .= "&CompanyName=" . urlencode($params["clientsdetails"]["companyname"]);
    $query_string .= "&Town=" . urlencode($params["clientsdetails"]["city"]);
    $query_string .= "&County=" . urlencode($params["clientsdetails"]["state"]);
    $query_string .= "&HomePhone=" . $params["clientsdetails"]["phonenumber"];
    $query_string .= "&NewAccountNumber=" . $params["username"];
    $query_string .= "&NewAccountPassword=" . urlencode($params["password"]);
    if ($params["configoption3"]) {
        $query_string .= "&ResellerPlanId=" . $params["configoption3"];
    } else {
        $query_string .= "&ResellerPlanId=0";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    $data = XMLtoARRAY($data);
    if ($data["H1"]) {
        return $data["H1"];
    }
    if ($data["RESULTS"]["RESULTCODE"] == "0") {
        $params["model"]->serviceProperties->save(array("username" => $data["RESULTS"]["RESULTDATA"]));
        $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
        $query_string = "action=AddPackage&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
        $query_string .= "&UserAccountNumber=" . $data["RESULTS"]["RESULTDATA"];
        $query_string .= "&PackageTypeId=" . $params["configoption1"];
        $query_string .= "&FriendlyName=" . $params["configoption2"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $data = curl_error($ch);
        }
        curl_close($ch);
        $data = XMLtoARRAY($data);
        $packageid = $data["RESULTS"]["RESULTDATA"];
        $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
        $query_string = "action=AddDomain&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
        $query_string .= "&PackageId=" . $packageid;
        $query_string .= "&DomainName=" . $params["domain"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            $data = curl_error($ch);
        }
        curl_close($ch);
        $data = XMLtoARRAY($data);
        return "success";
    }
    return $data["RESULTS"]["RESULTCODE"] . " - " . $data["RESULTS"]["RESULTDESCRIPTION"];
}
function helm_TerminateAccount($params)
{
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
    $query_string = "action=DeleteUser&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
    $query_string .= "&UserAccountNumber=" . $params["username"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    $data = XMLtoARRAY($data);
    if ($data["RESULTS"]["RESULTCODE"] == "0") {
        return "success";
    }
    return $data["RESULTS"]["RESULTCODE"] . " - " . $data["RESULTS"]["RESULTDESCRIPTION"];
}
function helm_SuspendAccount($params)
{
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
    $query_string = "action=SuspendUser&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
    $query_string .= "&UserAccountNumber=" . $params["username"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    $data = XMLtoARRAY($data);
    if ($data["RESULTS"]["RESULTCODE"] == "0") {
        return "success";
    }
    return $data["RESULTS"]["RESULTCODE"] . " - " . $data["RESULTS"]["RESULTDESCRIPTION"];
}
function helm_UnsuspendAccount($params)
{
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
    $query_string = "action=UnsuspendUser&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
    $query_string .= "&UserAccountNumber=" . $params["username"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    $data = XMLtoARRAY($data);
    if ($data["RESULTS"]["RESULTCODE"] == "0") {
        return "success";
    }
    return $data["RESULTS"]["RESULTCODE"] . " - " . $data["RESULTS"]["RESULTDESCRIPTION"];
}
function helm_ChangePassword($params)
{
    $url = $params["serverhttpprefix"] . "://" . $params["serverip"] . "/billing_api.asp";
    $query_string = "action=UpdateUserPassword&Username=" . $params["serverusername"] . "&Password=" . $params["serverpassword"];
    $query_string .= "&UserAccountNumber=" . $params["username"];
    $query_string .= "&NewAccountPassword=" . $params["password"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    $data = XMLtoARRAY($data);
    if ($data["RESULTS"]["RESULTCODE"] == "0") {
        return "success";
    }
    return $data["RESULTS"]["RESULTCODE"] . " - " . $data["RESULTS"]["RESULTDESCRIPTION"];
}

?>