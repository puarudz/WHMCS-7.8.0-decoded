<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/modulefunctions.php";
$pagetitle = $_LANG["sslconfsslcertificate"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"clientarea.php?action=products\">" . $_LANG["clientareaproducts"] . "</a> > <a href=\"#\">" . $_LANG["clientareaproductdetails"] . "</a> > <a href=\"configuressl.php?cert=" . $cert . "\">" . $_LANG["sslconfsslcertificate"] . "</a>";
$templatefile = "configuressl-stepone";
$displayTitle = Lang::trans("sslconfsslcertificate");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$additionalData = array();
$step = App::getFromRequest("step");
$step = in_array($step, array(2, 3)) ? $step : 1;
if (isset($_SESSION["uid"])) {
    $sslOrder = WHMCS\Service\Ssl::with("service", "service.product", "addon", "addon.productAddon", "client")->where("userid", WHMCS\Session::get("uid"))->where(WHMCS\Database\Capsule::raw("md5(id)"), $cert)->first();
    if (!$sslOrder) {
        $templatefile = "configuressl-stepone";
        $smartyvalues["status"] = "";
        outputClientArea($templatefile);
    }
    $id = $sslOrder->id;
    $countries = new WHMCS\Utility\Country();
    $serviceid = $sslOrder->serviceId;
    $addonId = $sslOrder->addonId;
    $remoteid = $sslOrder->remoteId;
    $module = $sslOrder->module;
    $certtype = $sslOrder->certificateType;
    $configdata = $sslOrder->configurationData;
    $completiondate = $sslOrder->completionDate;
    $status = $sslOrder->status;
    $certificatename = $sslOrder->service->product->name;
    $firstpaymentamount = $sslOrder->service->firstPaymentAmount;
    $domain = $sslOrder->service->domain;
    $regdate = $sslOrder->service->registrationDate;
    if ($sslOrder->addonId) {
        $certificatename = $sslOrder->addon->name ?: $sslOrder->addon->productAddon->name;
        $firstpaymentamount = $sslOrder->addon->recurringFee + $sslOrder->addon->setupFee;
        $regdate = $sslOrder->addon->registrationDate;
    }
    $regdate = fromMySQLDate($regdate);
    $smartyvalues["cert"] = $cert;
    $smartyvalues["serviceid"] = $serviceid;
    $smartyvalues["addonId"] = $addonId;
    $smartyvalues["certtype"] = $certificatename;
    $smartyvalues["date"] = $regdate;
    $smartyvalues["domain"] = $domain;
    $smartyvalues["price"] = formatCurrency($firstpaymentamount);
    $smartyvalues["status"] = $status;
    if (!isValidforPath($module)) {
        exit("Invalid SSL Module Name");
    }
    $modulepath = "modules/servers/" . $module . "/" . $module . ".php";
    if (file_exists($modulepath)) {
        include $modulepath;
    }
    $params = array();
    $params = ModuleBuildParams($serviceid, $addonId);
    $params["remoteid"] = $remoteid;
    $params["certtype"] = $certtype;
    $params["domain"] = $domain;
    $params["configdata"] = $configdata;
    $params["sslOrder"] = $sslOrder;
    $servertype = App::getFromRequest("servertype");
    $csr = App::getFromRequest("csr");
    $firstname = App::getFromRequest("firstname");
    $lastname = App::getFromRequest("lastname");
    $orgname = App::getFromRequest("orgname");
    $email = App::getFromRequest("email");
    $address1 = App::getFromRequest("address1");
    $address2 = App::getFromRequest("address2");
    $city = App::getFromRequest("city");
    $state = App::getFromRequest("state");
    $postcode = App::getFromRequest("postcode");
    $country = App::getFromRequest("country");
    $phonenumber = App::getFromRequest("phonenumber");
    $jobtitle = App::getFromRequest("jobtitle");
    if (!$_POST) {
        $client = $sslOrder->client;
        $firstname = $client->firstName;
        $lastname = $client->lastName;
        $orgname = $client->companyName;
        $email = $client->email;
        $address1 = $client->address1;
        $address2 = $client->address2;
        $city = $client->city;
        $state = $client->state;
        $postcode = $client->postcode;
        $country = $client->country;
        $phonenumber = $client->phoneNumber;
    } else {
        $phonenumber = App::formatPostedPhoneNumber();
    }
    if ($step == "2") {
        check_token();
        $errormessage = "";
        if (!$servertype) {
            $errormessage .= "<li>" . $_LANG["sslerrorselectserver"];
        }
        if (!$csr || nl2br($csr) == "-----BEGIN CERTIFICATE REQUEST-----<br />\n<br />\n-----END CERTIFICATE REQUEST-----") {
            $errormessage .= "<li>" . $_LANG["sslerrorentercsr"];
        }
        $result = call_user_func($module . "_SSLStepOne", $params);
        if (is_array($result["additionalfields"])) {
            foreach ($result["additionalfields"] as $heading => $fieldsconfig) {
                foreach ($fieldsconfig as $key => $configoption) {
                    $fieldvalue = $_POST["fields"][$key];
                    if ($configoption["Required"] && !$fieldvalue) {
                        $errormessage .= "<li>" . $configoption["FriendlyName"] . " " . $_LANG["clientareaerrorisrequired"];
                    }
                }
            }
        }
        if (!$firstname) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorfirstname"];
        }
        if (!$lastname) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorlastname"];
        }
        if (!$email) {
            $errormessage .= "<li>" . $_LANG["clientareaerroremail"];
        }
        if (!$address1) {
            $errormessage .= "<li>" . $_LANG["clientareaerroraddress1"];
        }
        if (!$city) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorcity"];
        }
        if (!$state) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorstate"];
        }
        if (!$postcode) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorpostcode"];
        }
        if (!$phonenumber) {
            $errormessage .= "<li>" . $_LANG["clientareaerrorphonenumber"];
        }
        if (!$errormessage) {
            $configdata = array("servertype" => $servertype, "csr" => $csr, "firstname" => $firstname, "lastname" => $lastname, "orgname" => $orgname, "jobtitle" => $jobtitle, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber);
            if (is_array($fields)) {
                $configdata["fields"] = $fields;
            }
            $sslOrder->configurationData = $configdata;
            $params = array_merge($params, $configdata);
            if (function_exists($module . "_SSLStepTwo")) {
                $result = call_user_func($module . "_SSLStepTwo", $params);
                if ($result["error"]) {
                    $errormessage .= "<li>" . $result["error"];
                }
                if ($result["remoteid"]) {
                    $sslOrder->remoteId = $result["remoteid"];
                }
                if ($result["domain"]) {
                    $sslOrder->service->domain = $result["domain"];
                    $sslOrder->service->save();
                }
            }
            $sslOrder->save();
        }
        if ($errormessage) {
            $smartyvalues["errormessage"] = $errormessage;
            $step = "1";
        }
    }
    if ($step == "3") {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            check_token();
        } else {
            if (WHMCS\Session::get("sslNoApproverEmails")) {
                WHMCS\Session::delete("sslNoApproverEmails");
            } else {
                check_token();
            }
        }
        $approveremail = App::getFromRequest("approveremail");
        $errormessage = "";
        if (is_array($_POST) && count($_POST) && function_exists($module . "_SSLStepTwo") && !$approveremail) {
            $errormessage .= "<li>" . $_LANG["sslerrorapproveremail"];
        }
        if (!$errormessage && function_exists($module . "_SSLStepThree")) {
            $configdata["approveremail"] = $approveremail;
            $sslOrder->configurationData = $configdata;
            $params = array_merge($params, $configdata);
            $result = call_user_func($module . "_SSLStepThree", $params);
            if ($result["error"]) {
                $errormessage .= "<li>" . $result["error"];
            }
            if ($result["remoteid"]) {
                $sslOrder->remoteId = $result["remoteid"];
            }
            if ($result["domain"]) {
                $sslOrder->service->domain = $result["domain"];
                $sslOrder->service->save();
            }
            $sslOrder->save();
        }
        if ($errormessage) {
            $smartyvalues["errormessage"] = $errormessage;
        } else {
            $sslOrder->completionDate = WHMCS\Carbon::now()->toDateTimeString();
            $sslOrder->status = WHMCS\Service\Ssl::STATUS_COMPLETED;
            $sslOrder->save();
            if (!function_exists($module . "_Renew") && !$sslOrder->addonId) {
                $sslOrder->service->domainStatus = "Completed";
                $sslOrder->service->completedDate = WHMCS\Carbon::today()->toDateString();
                $sslOrder->service->save();
            }
        }
    }
    if ($step == 1) {
        $result = call_user_func($module . "_SSLStepOne", $params);
        $additionalfields = array();
        if (is_array($result["additionalfields"])) {
            foreach ($result["additionalfields"] as $heading => $fieldsconfig) {
                $tempfields = array();
                foreach ($fieldsconfig as $key => $configoption) {
                    $fieldvalue = $_POST["fields"][$key];
                    if ($configoption["Type"] == "text") {
                        $input = "<input type=\"text\" name=\"fields[" . $key . "]\" size=\"" . $configoption["Size"] . "\" value=\"" . $fieldvalue . "\" />";
                    } else {
                        if ($configoption["Type"] == "password") {
                            $input = "<input type=\"password\" name=\"fields[" . $key . "]\" size=\"" . $configoption["Size"] . "\" value=\"" . $fieldvalue . "\" />";
                        } else {
                            if ($configoption["Type"] == "yesno") {
                                $input = "<input type=\"checkbox\" name=\"fields[" . $key . "]\"";
                                if ($fieldvalue) {
                                    $input .= " checked";
                                }
                                $input .= " />";
                            } else {
                                if ($configoption["Type"] == "textarea") {
                                    $input = "<textarea name=\"fields[" . $key . "]\" cols=\"60\" rows=\"" . $configoption["Rows"] . "\">" . $fieldvalue . "</textarea>";
                                } else {
                                    if ($configoption["Type"] == "dropdown") {
                                        $input = "<select name=\"fields[" . $key . "]\">";
                                        $options = explode(",", $configoption["Options"]);
                                        foreach ($options as $value) {
                                            $input .= "<option";
                                            if ($value == $fieldvalue) {
                                                $input .= " selected";
                                            }
                                            $input .= ">" . $value . "</option>";
                                        }
                                        $input .= "</select>";
                                    } else {
                                        if ($configoption["Type"] == "country") {
                                            $input = getCountriesDropDown($fieldvalue, "fields[" . $key . "]");
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $tempfields[] = array("name" => $configoption["FriendlyName"], "input" => $input, "description" => $configoption["Description"]);
                }
                $additionalfields[$heading] = $tempfields;
            }
        }
        if (!$csr) {
            $csr = "-----BEGIN CERTIFICATE REQUEST-----\n\n-----END CERTIFICATE REQUEST-----";
        }
        $status = $sslOrder->status;
        $smartyvalues["status"] = $status;
        $smartyvalues["displaydata"] = $result["displaydata"];
        $smartyvalues["webservertypes"] = getSSLWebServerTypes();
        $smartyvalues["servertype"] = $servertype;
        $smartyvalues["csr"] = $csr;
        $smartyvalues["additionalfields"] = $additionalfields;
        $smartyvalues["firstname"] = $firstname;
        $smartyvalues["lastname"] = $lastname;
        $smartyvalues["orgname"] = $orgname;
        $smartyvalues["jobtitle"] = $jobtitle;
        $smartyvalues["email"] = $email;
        $smartyvalues["address1"] = $address1;
        $smartyvalues["address2"] = $address2;
        $smartyvalues["city"] = $city;
        $smartyvalues["state"] = $state;
        $smartyvalues["postcode"] = $postcode;
        $smartyvalues["country"] = $country;
        $smartyvalues["phonenumber"] = $phonenumber;
        $smartyvalues["faxnumber"] = $faxnumber;
        $smartyvalues["countriesdropdown"] = getCountriesDropDown($country);
        $smartyvalues["clientcountries"] = $countries->getCountryNameArray();
    }
    if ($step == "2") {
        if (count($result["approveremails"])) {
            $additionalData = is_array($result["displaydata"]) ? $result["displaydata"] : array();
            $smartyvalues["displaydata"] = $additionalData;
            $smartyvalues["approveremails"] = $result["approveremails"];
            $templatefile = "configuressl-steptwo";
        } else {
            WHMCS\Session::set("sslNoApproverEmails", 1);
            redir("cert=" . $cert . "&step=3");
        }
    }
    if ($step == "3") {
        $templatefile = "configuressl-complete";
    }
} else {
    include "login.php";
}
Menu::addContext("service", $sslOrder->service);
Menu::addContext("addon", $sslOrder->addon);
Menu::addContext("displayData", $additionalData);
Menu::addContext("orderStatus", $sslOrder->status);
Menu::addContext("step", $step);
Menu::primarySidebar("sslCertificateOrderView");
Menu::secondarySidebar("sslCertificateOrderView");
outputClientArea($templatefile, false, array("ClientAreaPageConfigureSSL"));
function getSSLWebServerTypes()
{
    $t = array();
    $t[1001] = "AOL";
    $t[1002] = "Apache +ModSSL";
    $t[1003] = "Apache-SSL (Ben-SSL, not Stronghold)";
    $t[1004] = "C2Net Stronghold";
    $t[1005] = "Cobalt Raq";
    $t[1006] = "Covalent Server Software";
    $t[1031] = "cPanel / WHM";
    $t[1029] = "Ensim";
    $t[1032] = "H-Sphere";
    $t[1007] = "IBM HTTP Server";
    $t[1008] = "IBM Internet Connection Server";
    $t[1009] = "iPlanet";
    $t[1010] = "Java Web Server (Javasoft / Sun)";
    $t[1011] = "Lotus Domino";
    $t[1012] = "Lotus Domino Go!";
    $t[1013] = "Microsoft IIS 1.x to 4.x";
    $t[1014] = "Microsoft IIS 5.x and later";
    $t[1015] = "Netscape Enterprise Server";
    $t[1016] = "Netscape FastTrack";
    $t[1017] = "Novell Web Server";
    $t[1018] = "Oracle";
    $t[1030] = "Plesk";
    $t[1019] = "Quid Pro Quo";
    $t[1020] = "R3 SSL Server";
    $t[1021] = "Raven SSL";
    $t[1022] = "RedHat Linux";
    $t[1023] = "SAP Web Application Server";
    $t[1024] = "Tomcat";
    $t[1025] = "Website Professional";
    $t[1026] = "WebStar 4.x and later";
    $t[1027] = "WebTen (from Tenon)";
    $t[1028] = "Zeus Web Server";
    $t[1000] = "Other (not listed)";
    return $t;
}

?>