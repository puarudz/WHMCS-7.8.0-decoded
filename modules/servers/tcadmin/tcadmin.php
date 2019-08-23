<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function tcadmin_MetaData()
{
    return array("DisplayName" => "TCAdmin", "APIVersion" => "1.0");
}
function tcadmin_ConfigOptions()
{
    $configarray = array("Game ID" => array("Type" => "text", "Size" => "25", "Description" => "Leave blank for none"), "Game Slots" => array("Type" => "text", "Size" => "10", "Description" => ""), "Game Private" => array("Type" => "yesno", "Description" => "Tick if Private"), "Game Additional Slots" => array("Type" => "text", "Size" => "10"), "Game Is Branded" => array("Type" => "yesno", "Description" => "Tick for Branded"), "Additional Arguments" => array("Type" => "text", "Size" => "25"), "Voice ID" => array("Type" => "text", "Size" => "25", "Description" => "Leave blank for none"), "Voice Slots" => array("Type" => "text", "Size" => "25"), "Voice Private" => array("Type" => "yesno", "Description" => "Tick if Private"), "Voice Additional Slots" => array("Type" => "text", "Size" => "10"), "Game Datacenter" => array("Type" => "text", "Size" => "20", "Description" => "Only required for automatic installation"));
    return $configarray;
}
function tcadmin_CreateAccount($params)
{
    $datacenter = $params["configoption11"];
    if ($params["configoptions"]["Game Slots"]) {
        $params["configoption2"] = $params["configoptions"]["Game Slots"];
    }
    if ($params["configoptions"]["Game Private"]) {
        $params["configoption3"] = $params["configoptions"]["Game Private"];
    }
    if ($params["configoptions"]["Game Additional Slots"]) {
        $params["configoption4"] = $params["configoptions"]["Game Additional Slots"];
    }
    if ($params["configoptions"]["Game Branded"]) {
        $params["configoption5"] = $params["configoptions"]["Game Branded"];
    }
    if ($params["configoptions"]["Voice Slots"]) {
        $params["configoption8"] = $params["configoptions"]["Voice Slots"];
    }
    if ($params["configoptions"]["Voice Private"]) {
        $params["configoption9"] = $params["configoptions"]["Voice Private"];
    }
    if ($params["configoptions"]["Voice Additional Slots"]) {
        $params["configoption10"] = $params["configoptions"]["Voice Additional Slots"];
    }
    if ($params["customfields"]["Username"]) {
        $params["username"] = $params["customfields"]["Username"];
    }
    if ($params["customfields"]["Password"]) {
        $params["password"] = $params["customfields"]["Password"];
    }
    if ($params["customfields"]["Datacenter"]) {
        $datacenter = $params["customfields"]["Datacenter"];
    }
    if ($params["customfields"]["Host Name"]) {
        $hostname = $params["customfields"]["Host Name"];
    }
    if ($params["customfields"]["RCON Password"]) {
        $rconpw = $params["customfields"]["RCON Password"];
    }
    if ($params["customfields"]["Private Password"]) {
        $privatepw = $params["customfields"]["Private Password"];
    }
    if ($params["customfields"]["Voice Host Name"]) {
        $voicehostname = $params["customfields"]["Voice Host Name"];
    }
    if ($params["customfields"]["Voice RCON Password"]) {
        $voicerconpw = $params["customfields"]["Voice RCON Password"];
    }
    if ($params["customfields"]["Voice Private Password"]) {
        $voiceprivatepw = $params["customfields"]["Voice Private Password"];
    }
    if ($params["customfields"]["Game ID"]) {
        $params["configoption1"] = $params["customfields"]["Game ID"];
    }
    if ($params["customfields"]["Voice ID"]) {
        $params["configoption7"] = $params["customfields"]["Voice ID"];
    }
    if ($params["configoption7"] && !$params["configoption8"]) {
        $params["configoption8"] = $params["configoption2"];
    }
    $userid = $params["clientsdetails"]["userid"];
    $command = "tcadmin_username=" . $params["serverusername"] . "&";
    $command .= "tcadmin_password=" . $params["serverpassword"] . "&";
    $command .= "function=AddPendingSetup&";
    $command .= "response_type=XML&";
    $command .= "game_package_id=" . $params["serviceid"] . "&";
    $command .= "voice_package_id=" . $params["serviceid"] . "&";
    $command .= "client_id=" . $userid . "&";
    $command .= "user_email=" . $params["clientsdetails"]["email"] . "&";
    $command .= "user_fname=" . $params["clientsdetails"]["firstname"] . "&";
    $command .= "user_lname=" . $params["clientsdetails"]["lastname"] . "&";
    $command .= "user_address1=" . $params["clientsdetails"]["address1"] . "&";
    $command .= "user_address2=" . $params["clientsdetails"]["address2"] . "&";
    $command .= "user_city=" . $params["clientsdetails"]["city"] . "&";
    $command .= "user_state=" . $params["clientsdetails"]["state"] . "&";
    $command .= "user_zip=" . $params["clientsdetails"]["postcode"] . "&";
    $command .= "user_country=" . $params["clientsdetails"]["country"] . "&";
    $command .= "user_phone1=" . $params["clientsdetails"]["phonenumber"] . "&";
    $command .= "user_name=" . $params["username"] . "&";
    $command .= "user_password=" . $params["password"] . "&";
    if ($params["configoption3"]) {
        $params["configoption3"] = 1;
    } else {
        $params["configoption3"] = 0;
    }
    if ($params["configoption5"]) {
        $params["configoption5"] = 1;
    } else {
        $params["configoption5"] = 0;
    }
    if ($params["configoption9"]) {
        $params["configoption9"] = 1;
    } else {
        $params["configoption9"] = 0;
    }
    $command .= "game_id=" . $params["configoption1"] . "&";
    $command .= "game_slots=" . $params["configoption2"] . "&";
    $command .= "game_private=" . $params["configoption3"] . "&";
    $command .= "game_additional_slots=" . $params["configoption4"] . "&";
    $command .= "game_branded=" . $params["configoption5"] . "&";
    $command .= "game_additional_arguments=" . WHMCS\Input\Sanitize::decode($params["configoption6"]) . "&";
    $command .= "voice_id=" . $params["configoption7"] . "&";
    $command .= "voice_slots=" . $params["configoption8"] . "&";
    $command .= "voice_private=" . $params["configoption9"] . "&";
    $command .= "voice_additional_slots=" . $params["configoption10"] . "&";
    if ($datacenter) {
        $command .= "skip_page=1&";
        $command .= "game_datacenter=" . $datacenter . "&";
        $command .= "game_hostname=" . $hostname . "&";
        $command .= "game_rcon_password=" . $rconpw . "&";
        $command .= "game_private_password=" . $privatepw . "&";
        $command .= "voice_hostname=" . $voicehostname . "&";
        $command .= "voice_rcon_password=" . $voicerconpw . "&";
        $command .= "voice_private_password=" . $voiceprivatepw . "&";
    } else {
        $command .= "skip_page=0&";
    }
    if ($params["serverhostname"]) {
        $domain = $params["serverhostname"];
    } else {
        $domain = $params["serverip"];
    }
    $retval = tcadminsendrequest($domain, $command);
    if (!isset($retval["RESULTS"]["ERRORCODE"])) {
        $result = "An unknown error occurred while connecting";
    } else {
        if ($retval["RESULTS"]["ERRORCODE"] != 0) {
            $result = $retval["RESULTS"]["ERRORCODE"] . " - " . $retval["RESULTS"]["ERRORTEXT"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function tcadmin_SuspendAccount($params)
{
    $command = "tcadmin_username=" . $params["serverusername"] . "&";
    $command .= "tcadmin_password=" . $params["serverpassword"] . "&";
    $command .= "function=SuspendGameAndVoiceByBillingID&";
    $command .= "response_type=XML&";
    $command .= "client_package_id=" . $params["serviceid"] . "&";
    if ($params["serverhostname"]) {
        $domain = $params["serverhostname"];
    } else {
        $domain = $params["serverip"];
    }
    $retval = tcadminsendrequest($domain, $command);
    if (!isset($retval["RESULTS"]["ERRORCODE"])) {
        $result = "An unknown error occurred while connecting";
    } else {
        if ($retval["RESULTS"]["ERRORCODE"] != 0) {
            $result = $retval["RESULTS"]["ERRORCODE"] . " - " . $retval["RESULTS"]["ERRORTEXT"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function tcadmin_UnsuspendAccount($params)
{
    $command = "tcadmin_username=" . $params["serverusername"] . "&";
    $command .= "tcadmin_password=" . $params["serverpassword"] . "&";
    $command .= "function=UnSuspendGameAndVoiceByBillingID&";
    $command .= "response_type=XML&";
    $command .= "client_package_id=" . $params["serviceid"] . "&";
    if ($params["serverhostname"]) {
        $domain = $params["serverhostname"];
    } else {
        $domain = $params["serverip"];
    }
    $retval = tcadminsendrequest($domain, $command);
    if (!isset($retval["RESULTS"]["ERRORCODE"])) {
        $result = "An unknown error occurred while connecting";
    } else {
        if ($retval["RESULTS"]["ERRORCODE"] != 0) {
            $result = $retval["RESULTS"]["ERRORCODE"] . " - " . $retval["RESULTS"]["ERRORTEXT"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function tcadmin_TerminateAccount($params)
{
    $command = "tcadmin_username=" . $params["serverusername"] . "&";
    $command .= "tcadmin_password=" . $params["serverpassword"] . "&";
    $command .= "function=DeleteGameAndVoiceByBillingID&";
    $command .= "response_type=XML&";
    $command .= "client_package_id=" . $params["serviceid"] . "&";
    if ($params["serverhostname"]) {
        $domain = $params["serverhostname"];
    } else {
        $domain = $params["serverip"];
    }
    $retval = tcadminsendrequest($domain, $command);
    if (!isset($retval["RESULTS"]["ERRORCODE"])) {
        $result = "An unknown error occurred while connecting";
    } else {
        if ($retval["RESULTS"]["ERRORCODE"] != 0) {
            $result = $retval["RESULTS"]["ERRORCODE"] . " - " . $retval["RESULTS"]["ERRORTEXT"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function tcadminsendrequest($url, $command)
{
    $url = "http://" . $url . "/billingapi.aspx";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
    $retval = curl_exec($ch);
    if (curl_errno($ch)) {
        $result["RESULTS"]["ERRORCODE"] = "1";
        $result["RESULTS"]["ERRORTEXT"] = "Curl Error: " . curl_error($ch);
    } else {
        if ($retval) {
            $result = XMLtoARRAY($retval);
        } else {
            $result["RESULTS"]["ERRORCODE"] = "99";
            $result["RESULTS"]["ERRORTEXT"] = "No response received";
        }
    }
    curl_close($ch);
    if ($result["H1"]) {
        $result["RESULTS"]["ERRORCODE"] = "1";
        $result["RESULTS"]["ERRORTEXT"] = "Billing API File Not Found - Check Server IP Address";
    }
    $action = explode("function=", $command);
    $action = $action[1];
    $action = explode("&", $action);
    $action = $action[0];
    logModuleCall("tcadmin", $action, $command, $retval, $result);
    return $result;
}

?>