<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function resellercentral_MetaData()
{
    return array("DisplayName" => "Reseller Central", "APIVersion" => "1.0");
}
function resellercentral_ConfigOptions()
{
    $configarray = array("API Key" => array("Type" => "text", "Size" => "60"), "Package Name" => array("Type" => "text", "Size" => "20"), "Location" => array("Type" => "dropdown", "Options" => "US-EAST,US-CENTRAL,US-WEST,UK,ASIA,US-CLOUD"), "Platform" => array("Type" => "dropdown", "Options" => "Linux,Windows"));
    return $configarray;
}
function resellercentral_CreateAccount($params)
{
    $location = $params["configoption3"];
    if ($params["customfields"]["Website Location"]) {
        $location = $params["customfields"]["Website Location"];
    }
    if ($location == "Chicago (USA)") {
        $location = 4;
    } else {
        if ($location == "Georgia (USA)") {
            $location = 4;
        } else {
            if ($location == "Texas (USA)") {
                $location = 4;
            } else {
                if ($location == "Berkshire (UK)") {
                    $location = 5;
                } else {
                    if ($location == "Washington DC (USA)") {
                        $location = 6;
                    } else {
                        if ($location == "New York (USA)") {
                            $location = 6;
                        } else {
                            if ($location == "California (USA)") {
                                $location = 8;
                            } else {
                                if ($location == "Singapore (ASIA)") {
                                    $location = 10;
                                } else {
                                    if ($location == "US-EAST") {
                                        $location = 6;
                                    } else {
                                        if ($location == "US-CENTRAL") {
                                            $location = 4;
                                        } else {
                                            if ($location == "US-WEST") {
                                                $location = 8;
                                            } else {
                                                if ($location == "UK") {
                                                    $location = 5;
                                                } else {
                                                    if ($location == "ASIA") {
                                                        $location = 10;
                                                    } else {
                                                        if ($location == "US-CLOUD") {
                                                            $location = 9;
                                                        } else {
                                                            return "No Matching Location Found";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $fields = array("action" => "create_account", "api_key" => $params["configoption1"], "domain" => $params["domain"], "username" => $params["username"], "password" => $params["password"], "email" => $params["clientsdetails"]["email"], "location" => $location, "package" => $params["configoption2"]);
    if ($params["configoption4"] == "Windows") {
        $fields["platform"] = "2";
    }
    $result = resellercentral_req($fields, $params["packageid"], $params["accountid"], $params);
    return $result;
}
function resellercentral_SuspendAccount($params)
{
    $fields = array("action" => "suspend_account", "api_key" => $params["configoption1"], "domain" => $params["domain"]);
    $result = resellercentral_req($fields);
    return $result;
}
function resellercentral_UnsuspendAccount($params)
{
    $fields = array("action" => "unsuspend_account", "api_key" => $params["configoption1"], "domain" => $params["domain"]);
    $result = resellercentral_req($fields);
    return $result;
}
function resellercentral_req($fields, $packageid = "", $accountid = "", array $params = array())
{
    $action = $fields["action"];
    if ($action == "create_account") {
        $creatingaccount = true;
    }
    $url = "http://cp.hostnine.com/api/" . $action . ".php?";
    unset($fields["action"]);
    $fieldstring = "";
    foreach ($fields as $key => $value) {
        $url .= (string) $key . "=" . urlencode($value) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 200);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $data = curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    if (!$data) {
        $data = "No Response from API";
    }
    logModuleCall("resellercentral", $action, $fields, $data);
    if (strpos($data, "SUCCESS") == true || strpos($data, "account has been suspended") == true || strpos($data, "account is now active") == true) {
        if ($creatingaccount) {
            $tempdata = explode("&", $data);
            $tempdata = explode("=", $tempdata[1]);
            $tempdata = explode("<", $tempdata[1]);
            $ipaddress = $tempdata[0];
            $params["model"]->serviceProperties->save(array("IP Address" => $ipaddress));
        }
        $result = "success";
    } else {
        if (strpos($data, "Account Already Suspended") == true) {
            $result = "Account Already Suspended";
        } else {
            if (strpos($data, "a DNS entry for") == true) {
                $result = "An account already exists for this domain name";
            } else {
                $result = $data;
            }
        }
    }
    return $result;
}

?>