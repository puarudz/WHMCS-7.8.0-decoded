<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!empty($_GET["pay"]) && !empty($_GET["invoiceid"])) {
    require "../../init.php";
    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");
    $GATEWAY = getGatewayVariables("bluepayecheck");
    if (!$GATEWAY["type"]) {
        exit("Module Not Activated");
    }
    $invoiceid = (int) App::getFromRequest("invoiceid");
    $where = array("id" => $invoiceid, "paymentmethod" => "bluepayecheck");
    if (!isset($_SESSION["adminid"])) {
        $where["userid"] = $_SESSION["uid"];
    }
    $invoiceid = get_query_val("tblinvoices", "id", $where);
    if (!$invoiceid) {
        exit("Access Denied");
    }
    echo "<html>\n<head>\n<title>Echeck Payment</title>\n<style>\nbody,td,input {\n    font-family: Tahoma;\n    font-size: 11px;\n}\nh1 {\n    font-family: Tahoma;\n    font-weight: normal;\n    font-size: 18px;\n    color: #000066;\n}\n</style>\n</head>\n<body>\n\n<h1>Echeck Payment</h1>\n\n";
    if ($submit) {
        $errormessage = "";
        if (!$bankacctname) {
            $errormessage .= "<li>You must enter your account name";
        }
        if (!$bankroutingcode) {
            $errormessage .= "<li>You must enter your banks routing code";
        }
        if (!$bankacctnumber) {
            $errormessage .= "<li>You must enter your bank account number";
        }
        if (!$bankacctnumber2) {
            $errormessage .= "<li>You must confirm your bank account number";
        }
        if ($bankacctnumber != $bankacctnumber2) {
            $errormessage .= "<li>Your bank account number & confirmation don't match";
        }
        if (!$errormessage) {
            $result = select_query("tblinvoices", "tblclients.*,tblinvoices.id,tblinvoices.total,tblinvoices.userid", array("tblinvoices.id" => $invoiceid), "", "", "", "tblclients ON tblinvoices.userid=tblclients.id");
            $data = mysql_fetch_array($result);
            $invoiceid = $data["id"];
            $userid = $data["userid"];
            $firstname = $data["firstname"];
            $lastname = $data["lastname"];
            $address1 = $data["address1"];
            $city = $data["city"];
            $state = $data["state"];
            $postcode = $data["postcode"];
            $country = $data["country"];
            $phonenumber = $data["phonenumber"];
            $email = $data["email"];
            $result = select_query("tblinvoices", "total", array("id" => $invoiceid));
            $data = mysql_fetch_array($result);
            $total = $data[0];
            $result = select_query("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
            $data = mysql_fetch_array($result);
            $amountpaid = $data[0];
            $balance = round($total - $amountpaid, 2);
            $balance = sprintf("%01.2f", $balance);
            $params = getGatewayVariables("bluepayecheck");
            $url = "https://secure.bluepay.com/interfaces/bp20post";
            $postfields = array();
            $postfields["ACCOUNT_ID"] = $params["bpaccountid"];
            $postfields["USER_ID"] = $params["bpuserid"];
            $postfields["TRANS_TYPE"] = "SALE";
            $postfields["PAYMENT_TYPE"] = "ACH";
            $postfields["MODE"] = $params["testmode"] ? "TEST" : "LIVE";
            $postfields["AMOUNT"] = $balance;
            $postfields["INVOICE_ID"] = $invoiceid;
            $postfields["NAME1"] = $firstname;
            $postfields["NAME2"] = $lastname;
            $postfields["COMPANY_NAME"] = $companyname;
            $postfields["ADDR1"] = $address1;
            $postfields["ADDR2"] = $address2;
            $postfields["CITY"] = $city;
            $postfields["STATE"] = $state;
            $postfields["ZIP"] = $postcode;
            $postfields["COUNTRY"] = $country;
            $postfields["PHONE"] = $phonenumber;
            $postfields["EMAIL"] = $email;
            $postfields["PAYMENT_ACCOUNT"] = strtoupper(substr($bankaccttype, 0, 1)) . ":" . $bankroutingcode . ":" . $bankacctnumber;
            $postfields["DOC_TYPE"] = "WEB";
            $postfields["TAMPER_PROOF_SEAL"] = md5($params["bpsecretkey"] . $params["bpaccountid"] . $postfields["TRANS_TYPE"] . $postfields["AMOUNT"] . $postfields["MASTER_ID"] . $postfields["NAME1"] . $postfields["PAYMENT_ACCOUNT"]);
            $data = curlCall($url, $postfields);
            $result = explode("&", $data);
            foreach ($result as $res) {
                $res = explode("=", $res);
                $resultarray[$res[0]] = $res[1];
            }
            if ($resultarray["STATUS"] == "1") {
                try {
                    $invoice = WHMCS\Billing\Invoice::findOrFail($invoiceid);
                    $invoice->addPayment($invoice->balance, $resultarray["TRANS_ID"], 0, "bluepayecheck");
                    $invoice->setPayMethodRemoteToken($resultarray["TRANS_ID"]);
                    logTransaction($params["paymentmethod"], $resultarray, "Successful");
                    echo "<br /><h1 class=\"sucessfull\">Payment Successful</h1>";
                } catch (Exception $e) {
                    echo "<br /><h1 class=\"failed\">Payment Processing Error - Please Contact Support</h1>";
                    logTransaction($params["paymentmethod"], $resultarray, "Error");
                }
                echo "<p align=\"center\"><a href=\"#\" onclick=\"close_child_window();\">Click here to close the window</a></p>\n<script language=\"javascript\">\nfunction close_child_window(){\n  window.opener.location.reload()\n  window.close();\n}\n</script>";
            } else {
                $errormessage .= "<li>The echeck payment attempt was declined. Please check the supplied details";
                logTransaction($params["paymentmethod"], $resultarray, "Failed");
            }
        }
    }
    if (!$submit || $errormessage) {
        echo "\n<form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?pay=true&invoiceid=";
        echo $_GET["invoiceid"];
        echo "\">\n<input type=\"hidden\" name=\"submit\" value=\"true\" />\n\n";
        if ($errormessage) {
            echo "<p style=\"color:#cc0000;\"><b>The following errors occurred:</b></p><ul>" . $errormessage . "</ul>";
        }
        echo "\n<table>\n<tr><td>Bank Account Name</td><td><input type=\"text\" name=\"bankacctname\" size=\"30\" value=\"";
        echo $bankacctname;
        echo "\" /></td></tr>\n<tr><td>Bank Account Type</td><td><input type=\"radio\" name=\"bankaccttype\" value=\"checking\" checked /> Checking<br /><input type=\"radio\" name=\"bankaccttype\" value=\"savings\" /> Savings</td></tr>\n<tr><td>Bank Routing Code</td><td><input type=\"text\" name=\"bankroutingcode\" size=\"20\" value=\"";
        echo $bankroutingcode;
        echo "\" /></td></tr>\n<tr><td>Bank Account Number</td><td><input type=\"text\" name=\"bankacctnumber\" size=\"20\" value=\"";
        echo $bankacctnumber;
        echo "\" /></td></tr>\n<tr><td>Confirm Account Number</td><td><input type=\"text\" name=\"bankacctnumber2\" size=\"20\" value=\"";
        echo $bankacctnumber2;
        echo "\" /></td></tr>\n</table>\n\n<p align=\"center\"><img src=\"//cdn.whmcs.com/assets/img/achinfographic.gif\" /></p>\n\n<p align=\"center\"><input type=\"submit\" value=\"Submit\" /></p>\n\n</form>\n\n";
    }
    echo "\n</body>\n</html>\n";
}
function bluepayecheck_MetaData()
{
    return array("failedEmail" => "Direct Debit Payment Failed", "successEmail" => "Direct Debit Payment Confirmation", "pendingEmail" => "Direct Debit Payment Pending");
}
function bluepayecheck_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "BluePay Echeck"), "bpaccountid" => array("FriendlyName" => "Account ID", "Type" => "text", "Size" => "20"), "bpuserid" => array("FriendlyName" => "User ID", "Type" => "text", "Size" => "20"), "bpsecretkey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "30"), "testmode" => array("FriendlyName" => "Test Module", "Type" => "yesno"));
    return $configarray;
}
function bluepayecheck_link($params)
{
    $code = "<input type=\"button\" value=\"" . $params["langpaynow"] . "\" onClick=\"window.open('modules/gateways/bluepayecheck.php?pay=true&invoiceid=" . $params["invoiceid"] . "','authnetecheck','width=600,height=500,toolbar=0,location=0,menubar=1,resizeable=0,status=1,scrollbars=1')\">";
    return $code;
}
function bluepayecheck_nolocalcc()
{
}
function bluepayecheck_capture($params)
{
    $url = "https://secure.bluepay.com/interfaces/bp20post";
    $postfields = array();
    $postfields["ACCOUNT_ID"] = $params["bpaccountid"];
    $postfields["USER_ID"] = $params["bpuserid"];
    $postfields["TRANS_TYPE"] = "SALE";
    $postfields["PAYMENT_TYPE"] = "ACH";
    $postfields["MODE"] = $params["testmode"] ? "TEST" : "LIVE";
    $postfields["AMOUNT"] = $params["amount"];
    $postfields["INVOICE_ID"] = $params["invoiceid"];
    $postfields["NAME1"] = $params["clientdetails"]["firstname"];
    $postfields["NAME2"] = $params["clientdetails"]["lastname"];
    $postfields["COMPANY_NAME"] = $params["clientdetails"]["companyname"];
    $postfields["ADDR1"] = $params["clientdetails"]["address1"];
    $postfields["ADDR2"] = $params["clientdetails"]["address2"];
    $postfields["CITY"] = $params["clientdetails"]["city"];
    $postfields["STATE"] = $params["clientdetails"]["state"];
    $postfields["ZIP"] = $params["clientdetails"]["postcode"];
    $postfields["COUNTRY"] = $params["clientdetails"]["country"];
    $postfields["PHONE"] = $params["clientdetails"]["phonenumber"];
    $postfields["EMAIL"] = $params["clientdetails"]["email"];
    $postfields["MASTER_ID"] = $params["gatewayid"];
    $postfields["TAMPER_PROOF_SEAL"] = md5($params["bpsecretkey"] . $params["bpaccountid"] . $postfields["TRANS_TYPE"] . $postfields["AMOUNT"] . $postfields["MASTER_ID"] . $postfields["NAME1"] . $postfields["PAYMENT_ACCOUNT"]);
    $data = curlCall($url, $postfields);
    $result = explode("&", $data);
    foreach ($result as $res) {
        $res = explode("=", $res);
        $resultarray[$res[0]] = $res[1];
    }
    if ($resultarray["STATUS"] == "1") {
        return array("status" => "success", "transid" => $resultarray["TRANS_ID"], "rawdata" => $resultarray);
    }
    return array("status" => "error", "rawdata" => $resultarray);
}

?>