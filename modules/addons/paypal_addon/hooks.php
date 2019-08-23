<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
add_hook("AdminHomeWidgets", 1, "widget_paypal_addon");
function widget_paypal_addon($vars)
{
    $title = "PayPal Overview";
    $params = array();
    $result = select_query("tbladdonmodules", "setting,value", array("module" => "paypal_addon"));
    while ($data = mysql_fetch_array($result)) {
        $params[$data[0]] = $data[1];
    }
    $content = "";
    $adminroleid = get_query_val("tbladmins", "roleid", array("id" => $_SESSION["adminid"]));
    if ($params["showbalance" . $adminroleid]) {
        $url = "https://api-3t.paypal.com/nvp";
        $postfields = $resultsarray = array();
        $postfields["USER"] = $params["username"];
        $postfields["PWD"] = $params["password"];
        $postfields["SIGNATURE"] = $params["signature"];
        $postfields["METHOD"] = "GetBalance";
        $postfields["RETURNALLCURRENCIES"] = "1";
        $postfields["VERSION"] = "56.0";
        $result = curlCall($url, $postfields);
        $resultsarray2 = explode("&", $result);
        foreach ($resultsarray2 as $line) {
            $line = explode("=", $line);
            $resultsarray[$line[0]] = urldecode($line[1]);
        }
        $paypalbal = array();
        if (strtolower($resultsarray["ACK"]) != "success") {
            $paypalbal[] = "Error: " . $resultsarray["L_LONGMESSAGE0"];
        } else {
            for ($i = 0; $i <= 20; $i++) {
                if (isset($resultsarray["L_AMT" . $i])) {
                    $paypalbal[] = number_format($resultsarray["L_AMT" . $i], 2, ".", ",") . " " . $resultsarray["L_CURRENCYCODE" . $i];
                }
            }
        }
        $content .= "<div style=\"margin:10px;padding:10px;background-color:#EFFAE4;text-align:center;font-size:16px;color:#000;\">PayPal Balance: <b>" . implode(" ~ ", $paypalbal) . "</b></div>";
    }
    $content .= "<form method=\"post\" action=\"addonmodules.php?module=paypal_addon\">\n    <div align=\"center\" style=\"margin:10px 40px;font-size:16px;\">\n        <div class=\"input-group\">\n            <input type=\"text\" name=\"transid\" placeholder=\"PayPal Transaction ID\" value=\"" . $_POST["transid"] . "\" class=\"form-control\" />\n            <span class=\"input-group-btn\">\n                <input type=\"submit\" name=\"search\" value=\"Lookup\" class=\"btn btn-primary\" />\n            </span>\n        </div>\n    </div>\n    <div class=\"text-center\" style=\"margin-bottom:10px;\">\n        <a href=\"addonmodules.php?module=paypal_addon\" class=\"btn btn-default btn-sm\">Advanced Search</a>\n    </div>\n</form>";
    return array("title" => $title, "content" => $content);
}

?>