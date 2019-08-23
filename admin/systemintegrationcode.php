<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Integration Code");
$aInt->title = $aInt->lang("system", "integrationcode");
$aInt->sidebar = "utilities";
$aInt->icon = "integrationcode";
$systemurl = App::getSystemUrl();
ob_start();
echo "\n<p>";
echo $aInt->lang("system", "integrationinfo");
echo "</p>\n\n<p>\n    ";
echo $aInt->lang("system", "widgetsinfo");
echo "    &nbsp;<a href=\"https://docs.whmcs.com/Widgets\" target=\"_blank\">\n        https://docs.whmcs.com/Widgets\n    </a>\n</p>\n\n<br />\n\n<h2>";
echo $aInt->lang("system", "intclientlogin");
echo "</h2>\n<p>";
echo $aInt->lang("system", "intclientlogininfo");
echo "</p>\n<textarea rows=\"6\" class=\"form-control\"><form method=\"post\" action=\"";
echo $systemurl;
echo "dologin.php\">\nEmail Address: <input type=\"text\" name=\"username\" size=\"50\" /><br />\nPassword: <input type=\"password\" name=\"password\" size=\"20\" autocomplete=\"off\" /><br />\n<input type=\"submit\" value=\"Login\" />\n</form></textarea>\n<br /><br />\n\n<h2>";
echo $aInt->lang("system", "intdo");
echo "</h2>\n<p>";
echo $aInt->lang("system", "intdoinfo");
echo "</p>\n<textarea rows=\"5\" class=\"form-control\"><form action=\"";
echo $systemurl;
echo "cart.php?a=add&domain=register\" method=\"post\">\nFind your Domain: <input type=\"text\" name=\"query\" size=\"20\" />\n<input type=\"submit\" value=\"Go\" />\n</form>\n</textarea>\n<br /><br />\n\n<h2>";
echo $aInt->lang("system", "intuserreg");
echo "</h2>\n<p>";
echo $aInt->lang("system", "intuserreginfo");
echo "</p>\n<textarea rows=\"2\" class=\"form-control\"><a href=\"";
echo $systemurl;
echo "register.php\">Click here to register with us</a></textarea>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>