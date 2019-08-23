<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../init.php";
require "../includes/domainfunctions.php";
/*
*** USAGE SAMPLE ***
<script language="javascript" src="feeds/domainchecker.php"></script>
*/
$systemurl = App::getSystemUrl();
$currency = getCurrency();
$tlds = getTLDList();
$code = '<form action="%sdomainchecker.php" method="post"><input type="hidden" name="direct" value="true">' . \Lang::trans('orderForm.www') . ' <input type="text" name="domain" size="30"> <select name="ext">';
$code = sprintf($code, htmlspecialchars($systemurl, ENT_QUOTES, 'UTF-8'));
foreach ($tlds as $tld) {
    $code .= '<option>' . htmlspecialchars($tld, ENT_QUOTES, 'UTF-8') . '</option>';
}
$code .= '</select> <input type="submit" value="Go"></form>';
echo "document.write('" . $code . "');";

?>