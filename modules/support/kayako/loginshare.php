<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs->load_function("client");
if ($CONFIG["SupportModule"] != "kayako") {
    exit("Kayako Module not Enabled in General Settings > Support");
}
$username = $_REQUEST["username"];
$password = $_REQUEST["password"];
$remote_ip = $_REQUEST["ipaddress"];
$xml = "";
$userId = 0;
$contactId = "";
WHMCS\Session::delete("2faverifyc");
$loginStatus = validateClientLogin($username, $password);
if ($loginStatus) {
    $userId = (int) WHMCS\Session::get("uid");
    $contactId = WHMCS\Session::get("cid");
} else {
    if (WHMCS\Session::get("2faverifyc")) {
        $userId = (int) WHMCS\Session::get("2faclientid");
        $contactId = "";
    }
}
if (0 < $userId) {
    $client = new WHMCS\Client($userId);
    $details = $client->getDetails($contactId);
    $firstname = $details["firstname"];
    $lastname = $details["lastname"];
    $email = $details["email"];
    $phonenumber = $details["phonenumber"];
    if (checkContactPermission("tickets", true)) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<loginshare>\n    <result>1</result>\n    <user>\n        <usergroup>Registered</usergroup>\n        <fullname><![CDATA[" . $firstname . " " . $lastname . "]]></fullname>\n        <emails>\n            <email>" . $email . "</email>\n        </emails>\n        <phone>" . $phonenumber . "</phone>\n    </user>\n</loginshare>";
    }
}
if (!$xml) {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<loginshare>\n    <result>0</result>\n    <message>Invalid Username or Password</message>\n</loginshare>";
}
echo $xml;

?>