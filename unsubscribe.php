<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "init.php";
$redirectUrl = routePath("subscription-manage");
if (strpos($redirectUrl, "?") === false) {
    $redirectUrl .= "?";
} else {
    $redirectUrl .= "&";
}
$redirectUrl .= "action=optout" . "&email=" . App::getFromRequest("email") . "&key=" . App::getFromRequest("key");
header("Location: " . $redirectUrl);

?>