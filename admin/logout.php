<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$auth = new WHMCS\Auth();
if ($auth->logout()) {
    redir("logout=1", "login.php");
}
redir("", "login.php");

?>