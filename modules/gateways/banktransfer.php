<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

# Bank Transfer Payment Gateway Module
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
function banktransfer_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Bank Transfer"), "instructions" => array("FriendlyName" => "Bank Transfer Instructions", "Type" => "textarea", "Rows" => "5", "Value" => "Bank Name:\nPayee Name:\nSort Code:\nAccount Number:", "Description" => "The instructions you want displaying to customers who choose this payment method - the invoice number will be shown underneath the text entered above"));
    return $configarray;
}
function banktransfer_link($params)
{
    global $_LANG;
    $code = '<p>' . nl2br($params['instructions']) . '<br />' . $_LANG['invoicerefnum'] . ': ' . $params['invoiceid'] . '</p>';
    return $code;
}

?>