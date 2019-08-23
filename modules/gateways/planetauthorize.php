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
class planetauthorize_class
{
    public $gateway_url = NULL;
    public $field_string = NULL;
    public $fields = array();
    public $gatewayurls = array();
    public $response_string = NULL;
    public $response = array();
    public function seturl($url)
    {
        $this->gateway_url = $url;
    }
    public function add_field($field, $value)
    {
        $this->fields[(string) $field] = urlencode($value);
    }
    public function process()
    {
        foreach ($this->fields as $key => $value) {
            $this->field_string .= (string) $key . "=" . $value . "&";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gateway_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($this->field_string, "& "));
        $this->response_string = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->response["Response Reason Text"] = curl_error($ch);
            return 3;
        }
        curl_close($ch);
        $temp_values = explode("|", $this->response_string);
        $temp_keys = array("Response Code", "Response Subcode", "Response Reason Code", "Response Reason Text", "Approval Code", "AVS Result Code", "Transaction ID", "Invoice Number", "Description", "Amount", "Method", "Transaction Type", "Customer ID", "Cardholder First Name", "Cardholder Last Name", "Company", "Billing Address", "City", "State", "Zip", "Country", "Phone", "Fax", "Email", "Ship to First Name", "Ship to Last Name", "Ship to Company", "Ship to Address", "Ship to City", "Ship to State", "Ship to Zip", "Ship to Country", "Tax Amount", "Duty Amount", "Freight Amount", "Tax Exempt Flag", "PO Number", "MD5 Hash", "Card Code (CVV2/CVC2/CID) Response Code", "Cardholder Authentication Verification Value (CAVV) Response Code");
        for ($i = 0; $i <= 27; $i++) {
            array_push($temp_keys, "Reserved Field " . $i);
        }
        for ($i = 0; sizeof($temp_keys) < sizeof($temp_values); $i++) {
            array_push($temp_keys, "Merchant Defined Field " . $i);
        }
        for ($i = 0; $i < sizeof($temp_values); $i++) {
            $this->response[(string) $temp_keys[$i]] = $temp_values[$i];
        }
        return $this->response["Response Code"];
    }
    public function get_response_reason_text()
    {
        return $this->response["Response Reason Text"];
    }
    public function dump_response()
    {
        foreach ($this->response as $key => $value) {
            if ($value != "") {
                $response .= (string) $key . " => " . $value . "\n";
            }
        }
        return $response;
    }
}
function planetauthorize_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "PlanetAuthorize"), "submissionurl" => array("FriendlyName" => "Submission URL", "Type" => "text", "Size" => "80", "Value" => "https://secure.planetauthorizegateway.com/gateway/transact.dll", "Description" => "Any Auth.net Emulation URL"), "loginid" => array("FriendlyName" => "Login ID", "Type" => "text", "Size" => "20"), "transkey" => array("FriendlyName" => "Transaction Key", "Type" => "text", "Size" => "20"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function planetauthorize_capture($params)
{
    global $CONFIG;
    $auth = new planetauthorize_class();
    $gateway_url = $params["submissionurl"];
    $auth->seturl($gateway_url);
    $auth->add_field("x_login", $params["loginid"]);
    $auth->add_field("x_tran_key", $params["transkey"]);
    $auth->add_field("x_version", "3.1");
    $auth->add_field("x_type", "AUTH_CAPTURE");
    if ($params["testmode"] == "on") {
        $auth->add_field("x_test_request", "TRUE");
    }
    $auth->add_field("x_relay_response", "FALSE");
    $auth->add_field("x_delim_data", "TRUE");
    $auth->add_field("x_delim_char", "|");
    $auth->add_field("x_encap_char", "");
    $auth->add_field("x_invoice_num", $params["invoiceid"]);
    $auth->add_field("x_description", $CONFIG["CompanyName"] . " Invoice #" . $params["invoiceid"]);
    $auth->add_field("x_first_name", $params["clientdetails"]["firstname"]);
    $auth->add_field("x_last_name", $params["clientdetails"]["lastname"]);
    $auth->add_field("x_address", $params["clientdetails"]["address1"]);
    $auth->add_field("x_city", $params["clientdetails"]["city"]);
    $auth->add_field("x_state", $params["clientdetails"]["state"]);
    $auth->add_field("x_zip", $params["clientdetails"]["postcode"]);
    $auth->add_field("x_country", $params["clientdetails"]["country"]);
    $auth->add_field("x_phone", $params["clientdetails"]["phonenumber"]);
    $auth->add_field("x_email", $params["clientdetails"]["email"]);
    $auth->add_field("x_email_customer", "FALSE");
    $auth->add_field("x_method", "CC");
    $auth->add_field("x_card_num", $params["cardnum"]);
    $auth->add_field("x_amount", $params["amount"]);
    $auth->add_field("x_exp_date", $params["cardexp"]);
    $auth->add_field("x_card_code", $params["cccvv"]);
    $auth->add_field("x_currency_code", $params["currency"]);
    if (!$params["testmode"]) {
        $auth->add_field("x_solution_id", "AAA172609");
    }
    switch ($auth->process()) {
        case 1:
            return array("status" => "success", "transid" => $auth->response["Transaction ID"], "rawdata" => $auth->dump_response());
        case 2:
            return array("status" => "declined", "rawdata" => $auth->dump_response());
    }
    return array("status" => "error", "rawdata" => $auth->dump_response());
}
function planetauthorize_refund($params)
{
    global $CONFIG;
    $auth = new planetauthorize_class();
    $gateway_url = $params["submissionurl"];
    $auth->seturl($gateway_url);
    $auth->add_field("x_login", $params["loginid"]);
    $auth->add_field("x_tran_key", $params["transkey"]);
    $auth->add_field("x_version", "3.1");
    $auth->add_field("x_type", "CREDIT");
    if ($params["testmode"] == "on") {
        $auth->add_field("x_test_request", "TRUE");
    }
    $auth->add_field("x_relay_response", "FALSE");
    $auth->add_field("x_delim_data", "TRUE");
    $auth->add_field("x_delim_char", "|");
    $auth->add_field("x_encap_char", "");
    $auth->add_field("x_invoice_num", $params["invoiceid"]);
    $auth->add_field("x_description", $CONFIG["CompanyName"] . " Invoice #" . $params["invoiceid"]);
    $auth->add_field("x_first_name", $params["clientdetails"]["firstname"]);
    $auth->add_field("x_last_name", $params["clientdetails"]["lastname"]);
    $auth->add_field("x_address", $params["clientdetails"]["address1"]);
    $auth->add_field("x_city", $params["clientdetails"]["city"]);
    $auth->add_field("x_state", $params["clientdetails"]["state"]);
    $auth->add_field("x_zip", $params["clientdetails"]["postcode"]);
    $auth->add_field("x_country", $params["clientdetails"]["country"]);
    $auth->add_field("x_phone", $params["clientdetails"]["phonenumber"]);
    $auth->add_field("x_email", $params["clientdetails"]["email"]);
    $auth->add_field("x_email_customer", "FALSE");
    $auth->add_field("x_method", "CC");
    $auth->add_field("x_card_num", $params["cardnum"]);
    $auth->add_field("x_amount", $params["amount"]);
    $auth->add_field("x_exp_date", $params["cardexp"]);
    $auth->add_field("x_card_code", $params["cccvv"]);
    $auth->add_field("x_trans_id", $params["transid"]);
    switch ($auth->process()) {
        case 1:
            return array("status" => "success", "transid" => $auth->response["Transaction ID"], "rawdata" => $auth->dump_response());
        case 2:
            return array("status" => "declined", "rawdata" => $auth->dump_response());
    }
    return array("status" => "error", "rawdata" => $auth->dump_response());
}

?>