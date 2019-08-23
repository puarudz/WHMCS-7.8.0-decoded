<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function email_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Email Notifications"), "Description" => array("Type" => "System", "Value" => "This module can be used for any TLDs that have no integrated registrar"), "EmailAddress" => array("Type" => "text", "Size" => "40", "Description" => "Enter the email address notifications should be sent to"));
    return $configarray;
}
function email_GetNameservers($params)
{
    return array('ns1' => '');
}
/**
 * Sends the passed nameservers to the defined email address
 * @param array $params The built array of data to save the nameservers
 */
function email_SaveNameservers($params)
{
    global $CONFIG;
    $command = "Save Nameservers";
    $message = <<<EMAIL
Domain: {$params["sld"]}.{$params["tld"]}<br>
Registration Period: {$params["regperiod"]}<br>
Nameserver 1: {$params["ns1"]}<br>
Nameserver 2: {$params["ns2"]}<br>
Nameserver 3: {$params["ns3"]}<br>
Nameserver 4: {$params["ns4"]}<br>
Nameserver 5: {$params["ns5"]}<br>
EMAIL;
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=" . $CONFIG['Charset'] . "\r\n";
    $headers .= "From: " . $CONFIG["CompanyName"] . " <" . $CONFIG["Email"] . ">\r\n";
    mail($params["EmailAddress"], $command, $message, $headers);
}
function email_RegisterDomain($params)
{
    global $CONFIG;
    $command = "Register Domain";
    $message = "Domain: " . $params["sld"] . "." . $params["tld"] . "<br>Registration Period: " . $params["regperiod"] . "<br>Nameserver 1: " . $params["ns1"] . "<br>Nameserver 2: " . $params["ns2"] . "<br>Nameserver 3: " . $params["ns3"] . "<br>Nameserver 4: " . $params["ns4"] . "<br>RegistrantFirstName: " . $params["firstname"] . "<br>RegistrantLastName: " . $params["lastname"] . "<br>RegistrantOrganizationName: " . $params["companyname"] . "<br>RegistrantAddress1: " . $params["address1"] . "<br>RegistrantAddress2: " . $params["address2"] . "<br>RegistrantCity: " . $params["city"] . "<br>RegistrantStateProvince: " . $params["state"] . "<br>RegistrantCountry: " . $params["country"] . "<br>RegistrantPostalCode: " . $params["postcode"] . "<br>RegistrantPhone: " . $params["phonenumber"] . "<br>RegistrantEmailAddress: " . $params["email"] . "<br>AdminFirstName: " . $params["adminfirstname"] . "<br>AdminLastName: " . $params["adminlastname"] . "<br>AdminOrganizationName: " . $params["admincompanyname"] . "<br>AdminAddress1: " . $params["adminaddress1"] . "<br>AdminAddress2: " . $params["adminaddress2"] . "<br>AdminCity: " . $params["admincity"] . "<br>AdminStateProvince: " . $params["adminstate"] . "<br>AdminCountry: " . $params["admincountry"] . "<br>AdminPostalCode: " . $params["adminpostcode"] . "<br>AdminPhone: " . $params["adminphonenumber"] . "<br>AdminEmailAddress: " . $params["adminemail"] . "";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: " . $CONFIG["CompanyName"] . " <" . $CONFIG["Email"] . ">\r\n";
    mail($params["EmailAddress"], $command, $message, $headers);
}
function email_TransferDomain($params)
{
    global $CONFIG;
    $command = "Transfer Domain";
    $message = "Domain: " . $params["sld"] . "." . $params["tld"] . "<br>Registration Period: " . $params["regperiod"] . "<br>Transfer Secret: " . $params["transfersecret"] . "<br>RegistrantFirstName: " . $params["firstname"] . "<br>RegistrantLastName: " . $params["lastname"] . "<br>RegistrantOrganizationName: " . $params["companyname"] . "<br>RegistrantAddress1: " . $params["address1"] . "<br>RegistrantAddress2: " . $params["address2"] . "<br>RegistrantCity: " . $params["city"] . "<br>RegistrantStateProvince: " . $params["state"] . "<br>RegistrantCountry: " . $params["country"] . "<br>RegistrantPostalCode: " . $params["postcode"] . "<br>RegistrantPhone: " . $params["phonenumber"] . "<br>RegistrantEmailAddress: " . $params["email"] . "";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: " . $CONFIG["CompanyName"] . " <" . $CONFIG["Email"] . ">\r\n";
    mail($params["EmailAddress"], $command, $message, $headers);
}
function email_RenewDomain($params)
{
    global $CONFIG;
    $command = "Renew Domain";
    $message = "Domain: " . $params["sld"] . "." . $params["tld"] . "<br>Registration Period: " . $params["regperiod"] . "";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
    $headers .= "From: " . $CONFIG["CompanyName"] . " <" . $CONFIG["Email"] . ">\r\n";
    mail($params["EmailAddress"], $command, $message, $headers);
}

?>