<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$GATEWAYMODULE["authorizeecheckname"] = "authorizeecheck";
$GATEWAYMODULE["authorizeecheckvisiblename"] = "Authorize.net Echeck";
$GATEWAYMODULE["authorizeechecktype"] = "Invoices";
if (!empty($_GET["pay"]) && !empty($_GET["invoiceid"])) {
    require "../../init.php";
    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");
    $GATEWAY = getGatewayVariables("authorizeecheck");
    if (!$GATEWAY["type"]) {
        exit("Module Not Activated");
    }
    $where = array("id" => (int) $_GET["invoiceid"], "paymentmethod" => "authorizeecheck");
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
        if (!$bankname) {
            $errormessage .= "<li>You must enter your banks name";
        }
        if (!$bankabacode) {
            $errormessage .= "<li>You must enter your banks ABA code";
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
            $result = select_query("tblinvoices", "tblclients.*,tblinvoices.id,tblinvoices.userid,tblinvoices.total", array("tblinvoices.id" => $_GET["invoiceid"]), "", "", "", "tblclients ON tblinvoices.userid=tblclients.id");
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
            $total = $data["total"];
            $result = select_query("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
            $data = mysql_fetch_array($result);
            $amountpaid = $data[0];
            $balance = round($total - $amountpaid, 2);
            $balance = sprintf("%01.2f", $balance);
            $params = array();
            $result = select_query("tblpaymentgateways", "", array("gateway" => "authorizeecheck"));
            while ($data = mysql_fetch_array($result)) {
                $gVgwsetting = $data["setting"];
                $gVgwvalue = $data["value"];
                $params[$gVgwsetting] = $gVgwvalue;
            }
            if ($params["testmode"] == "on") {
                $gateway_url = "https://test.authorize.net/gateway/transact.dll";
            } else {
                $gateway_url = "https://secure2.authorize.net/gateway/transact.dll";
            }
            $postfields = array();
            $postfields["x_login"] = $params["loginid"];
            $postfields["x_tran_key"] = $params["transkey"];
            $postfields["x_version"] = "3.1";
            $postfields["x_type"] = "AUTH_CAPTURE";
            $postfields["x_echeck_type"] = "WEB";
            $postfields["x_Method"] = "ECHECK";
            $postfields["x_bank_acct_name"] = $bankacctname;
            $postfields["x_bank_acct_type"] = strtoupper($bankaccttype);
            $postfields["x_bank_name"] = $bankname;
            $postfields["x_bank_aba_code"] = $bankabacode;
            $postfields["x_bank_acct_num"] = $bankacctnumber;
            $postfields["x_relay_response"] = "FALSE";
            $postfields["x_delim_data"] = "TRUE";
            $postfields["x_delim_char"] = "|";
            $postfields["x_encap_char"] = "";
            $postfields["x_invoice_num"] = $invoiceid;
            $postfields["x_first_name"] = $firstname;
            $postfields["x_last_name"] = $lastname;
            $postfields["x_address"] = $address1;
            $postfields["x_city"] = $city;
            $postfields["x_state"] = $state;
            $postfields["x_zip"] = $postcode;
            $postfields["x_country"] = $country;
            $postfields["x_phone"] = $phonenumber;
            $postfields["x_email"] = $email;
            $postfields["x_email_customer"] = "FALSE";
            $postfields["x_amount"] = $balance;
            if (!$params["testmode"] && (!isset($params["testurl"]) || !$params["testurl"])) {
                $postfields["x_solution_id"] = "AAA172607";
            } else {
                if (isset($params["testurl"]) && $params["testurl"]) {
                    $postfields["x_solution_id"] = "AAA100302";
                }
            }
            $querystring = "";
            foreach ($postfields as $k => $v) {
                $querystring .= (string) $k . "=" . urlencode($v) . "&";
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gateway_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($querystring, "&"));
            $data = curl_exec($ch);
            if (curl_errno($ch)) {
                $resultsarray["Response Reason Text"] = curl_error($ch);
                $resultsarray["Response Code"] = "3";
            } else {
                curl_close($ch);
            }
            $temp_values = explode("|", $data);
            $temp_keys = array("Response Code", "Response Subcode", "Response Reason Code", "Response Reason Text", "Approval Code", "AVS Result Code", "Transaction ID", "Invoice Number", "Description", "Amount", "Method", "Transaction Type", "Customer ID", "Cardholder First Name", "Cardholder Last Name", "Company", "Billing Address", "City", "State", "Zip", "Country", "Phone", "Fax", "Email", "Ship to First Name", "Ship to Last Name", "Ship to Company", "Ship to Address", "Ship to City", "Ship to State", "Ship to Zip", "Ship to Country", "Tax Amount", "Duty Amount", "Freight Amount", "Tax Exempt Flag", "PO Number", "MD5 Hash", "Card Code (CVV2/CVC2/CID) Response Code", "Cardholder Authentication Verification Value (CAVV) Response Code");
            for ($i = 0; $i <= 27; $i++) {
                array_push($temp_keys, "Reserved Field " . $i);
            }
            for ($i = 0; sizeof($temp_keys) < sizeof($temp_values); $i++) {
                array_push($temp_keys, "Merchant Defined Field " . $i);
            }
            for ($i = 0; $i < sizeof($temp_values); $i++) {
                $resultsarray[(string) $temp_keys[$i]] = $temp_values[$i];
            }
            $debugreport = "";
            foreach ($resultsarray as $k => $v) {
                $debugreport .= (string) $k . " => " . $v . "\n";
            }
            if ($resultsarray["Response Code"] == 1) {
                $payMethod = WHMCS\Payment\PayMethod\Model::where("userid", $userid)->where("payment_type", WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT)->first();
                if (!$payMethod) {
                    $client = WHMCS\User\Client::find($userid);
                    if ($client) {
                        $payMethod = WHMCS\Payment\PayMethod\Adapter\BankAccount::factoryPayMethod($client, $client, "Default Bank Account");
                    }
                }
                if ($payMethod) {
                    $payment = $payMethod->payment;
                    $payment->setAccountType($bankaccttype)->setAccountHolderName($client->firstName . " " . $client->lastName)->setBankName($bankname)->setRoutingNumber($bankabacode)->setAccountNumber($bankacctnumber)->validateRequiredValuesPreSave()->save();
                }
                addInvoicePayment($invoiceid, $resultsarray["Transaction ID"], "", "", "authorizeecheck");
                logTransaction($GATEWAY["paymentmethod"], $debugreport, "Successful");
                echo "<p align=\"center\"><a href=\"#\" onclick=\"window.opener.refresh();window.close();\">Click here to close the window</a></p>\n<script language=\"javascript\">\nwindow.opener.refresh();\nwindow.close();\n</script>";
            } else {
                $errormessage .= "<li>The echeck payment attempt was declined. Please check the supplied details";
                logTransaction($GATEWAY["paymentmethod"], $debugreport, "Failed");
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
        echo "\" /></td></tr>\n<tr><td>Bank Account Type</td><td><input type=\"radio\" name=\"bankaccttype\" value=\"checking\" checked /> Checking<br /><input type=\"radio\" name=\"bankaccttype\" value=\"businesschecking\" checked /> Business Checking<br /><input type=\"radio\" name=\"bankaccttype\" value=\"savings\" /> Savings</td></tr>\n<tr><td>Bank Name</td><td><input type=\"text\" name=\"bankname\" size=\"30\" value=\"";
        echo $bankname;
        echo "\" /></td></tr>\n<tr><td>Bank ABA Code</td><td><input type=\"text\" name=\"bankabacode\" size=\"20\" value=\"";
        echo $bankabacode;
        echo "\" /></td></tr>\n<tr><td>Bank Account Number</td><td><input type=\"text\" name=\"bankacctnumber\" size=\"20\" value=\"";
        echo $bankacctnumber;
        echo "\" /></td></tr>\n<tr><td>Confirm Account Number</td><td><input type=\"text\" name=\"bankacctnumber2\" size=\"20\" value=\"";
        echo $bankacctnumber2;
        echo "\" /></td></tr>\n</table>\n\n<p align=\"center\"><img src=\"//cdn.whmcs.com/assets/img/achinfographic.gif\" /></p>\n\n<p align=\"center\"><input type=\"submit\" value=\"Submit\" /></p>\n\n</form>\n\n";
    }
    echo "\n</body>\n</html>\n";
}
function authorizeecheck_activate()
{
    defineGatewayField("authorizeecheck", "text", "loginid", "", "Login ID", "20", "");
    defineGatewayField("authorizeecheck", "text", "transkey", "", "Transaction Key", "20", "");
}
function authorizeecheck_MetaData()
{
    return array("failedEmail" => "Direct Debit Payment Failed", "successEmail" => "Direct Debit Payment Confirmation", "pendingEmail" => "Direct Debit Payment Pending");
}
function authorizeecheck_link($params)
{
    $code = "<input type=\"button\" value=\"" . $params["langpaynow"] . "\" onClick=\"window.open('modules/gateways/authorizeecheck.php?pay=true&invoiceid=" . $params["invoiceid"] . "','authnetecheck','width=600,height=500,toolbar=0,location=0,menubar=1,resizeable=0,status=1,scrollbars=1')\">";
    return $code;
}
function authorizeecheck_nolocalcc()
{
}
function authorizeecheck_localbankdetails()
{
}
function authorizeecheck_capture($params)
{
    if ($params["testmode"] == "on") {
        $gateway_url = "https://test.authorize.net/gateway/transact.dll";
    } else {
        $gateway_url = "https://secure2.authorize.net/gateway/transact.dll";
    }
    $postfields = array();
    $postfields["x_login"] = $params["loginid"];
    $postfields["x_tran_key"] = $params["transkey"];
    $postfields["x_version"] = "3.1";
    $postfields["x_type"] = "AUTH_CAPTURE";
    $postfields["x_echeck_type"] = "WEB";
    $postfields["x_Method"] = "ECHECK";
    $postfields["x_bank_acct_name"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $postfields["x_bank_acct_type"] = strtoupper($params["banktype"]);
    $postfields["x_bank_name"] = $params["bankname"];
    $postfields["x_bank_aba_code"] = $params["bankcode"];
    $postfields["x_bank_acct_num"] = $params["bankacct"];
    $postfields["x_relay_response"] = "FALSE";
    $postfields["x_delim_data"] = "TRUE";
    $postfields["x_delim_char"] = "|";
    $postfields["x_encap_char"] = "";
    $postfields["x_invoice_num"] = $params["invoiceid"];
    $postfields["x_first_name"] = $params["clientdetails"]["firstname"];
    $postfields["x_last_name"] = $params["clientdetails"]["lastname"];
    $postfields["x_address"] = $params["clientdetails"]["address1"];
    $postfields["x_city"] = $params["clientdetails"]["city"];
    $postfields["x_state"] = $params["clientdetails"]["state"];
    $postfields["x_zip"] = $params["clientdetails"]["postcode"];
    $postfields["x_country"] = $params["clientdetails"]["country"];
    $postfields["x_phone"] = $params["clientdetails"]["phonenumber"];
    $postfields["x_email"] = $params["clientdetails"]["email"];
    $postfields["x_email_customer"] = "FALSE";
    $postfields["x_amount"] = $params["amount"];
    if (!$params["testmode"] && (!isset($params["testurl"]) || !$params["testurl"])) {
        $postfields["x_solution_id"] = "AAA172607";
    } else {
        if (isset($params["testurl"]) && $params["testurl"]) {
            $postfields["x_solution_id"] = "AAA100302";
        }
    }
    $querystring = "";
    foreach ($postfields as $k => $v) {
        $querystring .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $gateway_url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($querystring, "&"));
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $resultsarray["Response Reason Text"] = curl_error($ch);
        $resultsarray["Response Code"] = "3";
    } else {
        curl_close($ch);
    }
    $temp_values = explode("|", $data);
    $temp_keys = array("Response Code", "Response Subcode", "Response Reason Code", "Response Reason Text", "Approval Code", "AVS Result Code", "Transaction ID", "Invoice Number", "Description", "Amount", "Method", "Transaction Type", "Customer ID", "Cardholder First Name", "Cardholder Last Name", "Company", "Billing Address", "City", "State", "Zip", "Country", "Phone", "Fax", "Email", "Ship to First Name", "Ship to Last Name", "Ship to Company", "Ship to Address", "Ship to City", "Ship to State", "Ship to Zip", "Ship to Country", "Tax Amount", "Duty Amount", "Freight Amount", "Tax Exempt Flag", "PO Number", "MD5 Hash", "Card Code (CVV2/CVC2/CID) Response Code", "Cardholder Authentication Verification Value (CAVV) Response Code");
    for ($i = 0; $i <= 27; $i++) {
        array_push($temp_keys, "Reserved Field " . $i);
    }
    for ($i = 0; sizeof($temp_keys) < sizeof($temp_values); $i++) {
        array_push($temp_keys, "Merchant Defined Field " . $i);
    }
    for ($i = 0; $i < sizeof($temp_values); $i++) {
        $resultsarray[(string) $temp_keys[$i]] = $temp_values[$i];
    }
    if ($resultsarray["Response Code"] == 1) {
        return array("status" => "success", "transid" => $resultsarray["Transaction ID"], "rawdata" => $resultsarray);
    }
    return array("status" => "declined", "rawdata" => $resultsarray);
}

?>