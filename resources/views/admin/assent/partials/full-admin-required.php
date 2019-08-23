<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

echo "<h2>Insufficient Permissions</h2>\n<p>This installation of WHMCS requires administrative action.</p>\n<p>Please ask a\n    user with full administrative privileges to login and complete these actions so that normal administrative\n    operations can be resumed.\n</p>\n<a href=\"";
echo ${$adminBaseRoutePath};
echo "logout.php\" class=\"btn btn-default\">\n    Logout\n</a>\n";

?>