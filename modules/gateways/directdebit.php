<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (isset($_GET["invoiceid"])) {
    require "../../init.php";
    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");
    global $_LANG;
    $GATEWAY = getGatewayVariables("directdebit");
    if (!$GATEWAY["type"]) {
        exit("Module Not Activated");
    }
    $invoiceID = (int) $whmcs->get_req_var("invoiceid");
    if (WHMCS\Session::get("adminid")) {
        $result = select_query("tblinvoices", "id, userid", array("id" => $invoiceID));
    } else {
        $result = select_query("tblinvoices", "id, userid", array("id" => $invoiceID, "userid" => (int) WHMCS\Session::get("uid")));
    }
    $data = mysql_fetch_array($result);
    $invoiceID = $data["id"];
    $userID = $data["userid"];
    if (!$invoiceID) {
        exit("Access Denied");
    }
    echo "<!DOCTYPE html>\n<html lang=\"en\">\n    <head>\n        <meta http-equiv=\"content-type\" content=\"text/html; charset=";
    echo $CONFIG["Charset"];
    echo "\" />\n        <title>\n            ";
    echo $_LANG["directDebitPageTitle"];
    echo "        </title>\n        <link href=\"../../templates/default/css/invoice.css\" rel=\"stylesheet\">\n    </head>\n    <body>\n        <div class=\"wrapper\">\n            <p>\n                <img src=\"";
    echo $CONFIG["LogoURL"];
    echo "\" title=\"";
    echo $CONFIG["CompanyName"];
    echo "\" />\n            </p>\n            <h1>\n                ";
    echo $_LANG["directDebitHeader"];
    echo "            </h1>\n        ";
    if ($submit) {
        $errorMessage = "";
        if (!$bankName) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorNoBankName"];
        }
        if (!in_array($bankAccType, array("Checking", "Savings"))) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorAccountType"];
        }
        if (!$bankABACode) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorNoABA"];
        }
        if (!$bankAccNumber) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorAccNumber"];
        }
        if (!$bankAccNumber2) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorConfirmAccNumber"];
        }
        if ($bankAcctNumber != $bankAcctNumber2) {
            $errorMessage .= "<li>" . $_LANG["directDebitErrorAccNumberMismatch"];
        }
        if (!$errorMessage) {
            $payMethod = WHMCS\Payment\PayMethod\Model::where("userid", $userID)->where("payment_type", WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT)->first();
            if (!$payMethod) {
                $client = WHMCS\User\Client::find($userid);
                if ($client) {
                    $payMethod = WHMCS\Payment\PayMethod\Adapter\BankAccount::factoryPayMethod($client, $client, "Default Bank Account");
                }
            }
            if ($payMethod) {
                $payment = $payMethod->payment;
                $payment->setAccountType($bankAccType)->setAccountHolderName($client->firstName . " " . $client->lastName)->setBankName($bankName)->setRoutingNumber($bankABACode)->setAccountNumber($bankAccNumber)->validateRequiredValuesPreSave()->save();
            }
            echo "<p align=\"center\">" . $_LANG["directDebitThanks"] . "</p>\n        <p align=\"center\"><a href=\"#\" onclick=\"window.close()\">" . $_LANG["closewindow"] . "</a></p>\n        ";
        }
    }
    if (!$submit || $errorMessage) {
        echo "            <p>\n                ";
        echo $_LANG["directDebitPleaseSubmit"];
        echo "            </p>\n            <form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?invoiceid=";
        echo $invoiceID;
        echo "\">\n                <input type=\"hidden\" name=\"submit\" value=\"true\" />\n                ";
        if ($errorMessage) {
            echo "<div class=\"creditbox\" style=\"text-align:left;\"><b>" . (string) $_LANG["directDebitFollowingError"] . "</b></p><ul>" . $errorMessage . "</ul></div>";
        }
        if (!$bankAccType || $bankAccType == "Checking") {
            $checkingChecked = " checked";
            $savingsChecked = "";
        } else {
            $checkingChecked = "";
            $savingsChecked = " checked";
        }
        echo "                <table>\n                    <tr>\n                        <td>\n                            ";
        echo $_LANG["directDebitBankName"];
        echo "                        </td>\n                        <td>\n                            <input type=\"text\" name=\"bankName\" size=\"30\" value=\"";
        echo $bankName;
        echo "\" />\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                            ";
        echo $_LANG["directDebitAccountType"];
        echo "                        </td>\n                        <td>\n                            <label>\n                                <input\n                                    type=\"radio\" name=\"bankAccType\" value=\"Checking\"";
        echo $checkingChecked;
        echo " />\n                                ";
        echo $_LANG["directDebitChecking"];
        echo "                            </label>\n                            <label>\n                                <input type=\"radio\" name=\"bankAccType\" value=\"Savings\"";
        echo $savingsChecked;
        echo " />\n                                ";
        echo $_LANG["directDebitSavings"];
        echo "                            </label>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                            ";
        echo $_LANG["directDebitABA"];
        echo "                        </td>\n                        <td>\n                            <input type=\"text\" name=\"bankABACode\" size=\"20\" value=\"";
        echo $bankABACode;
        echo "\" />\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                            ";
        echo $_LANG["directDebitAccNumber"];
        echo "                        </td>\n                        <td>\n                            <input type=\"text\" name=\"bankAccNumber\" size=\"20\" value=\"";
        echo $bankAccNumber;
        echo "\" />\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                            ";
        echo $_LANG["directDebitConfirmAccNumber"];
        echo "                        </td>\n                        <td>\n                            <input type=\"text\" name=\"bankAccNumber2\" size=\"20\" value=\"";
        echo $bankAccNumber2;
        echo "\" />\n                        </td>\n                    </tr>\n                </table>\n                <p align=\"center\">\n                    <img src=\"//cdn.whmcs.com/assets/img/achinfographic.gif\" />\n                </p>\n                <p align=\"center\">\n                    <input type=\"submit\" value=\"";
        echo $_LANG["directDebitSubmit"];
        echo "\" />\n                </p>\n            </form>\n        ";
    }
    echo "        </div>\n    </body>\n</html>\n";
}
function directdebit_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Direct Debit"));
    return $configarray;
}
function directdebit_localbankdetails()
{
}
function directdebit_link($params)
{
    $code = "<form method=\"post\" action=\"modules/gateways/directdebit.php?invoiceid=" . $params["invoiceid"] . "\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\" />\n</form>";
    return $code;
}

?>