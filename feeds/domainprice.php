<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Application;
require "../init.php";
/*
*** USAGE SAMPLES ***
<script language="javascript" src="feeds/domainprice.php?tld=.com&type=register&regperiod=1"></script>
<script language="javascript" src="feeds/domainprice.php?tld=.com&type=register&regperiod=1&currency=1&format=1"></script>
*/
$whmcs = Application::getInstance();
$tld = $whmcs->get_req_var('tld');
$type = $whmcs->get_req_var('type');
$regperiod = $whmcs->get_req_var('regperiod');
$format = $whmcs->get_req_var('format') ? true : false;
if (!is_numeric($regperiod) || $regperiod < 1) {
    $regperiod = 1;
}
$result = select_query("tbldomainpricing", "id", array("extension" => $tld));
$data = mysql_fetch_array($result);
$did = $data['id'];
$currency = $currency ? getCurrency('', $currency) : getCurrency();
$validDomainActionRequests = array('register', 'transfer', 'renew');
if (!in_array($type, $validDomainActionRequests)) {
    $type = 'register';
}
$result = select_query("tblpricing", "msetupfee,qsetupfee,ssetupfee,asetupfee,bsetupfee,tsetupfee,monthly,quarterly,semiannually,annually,biennially,triennially", array("type" => "domain" . $type, "currency" => $currency['id'], "relid" => $did));
$data = mysql_fetch_array($result);
if ($regperiod < 6) {
    $regperiod = $regperiod - 1;
}
$price = $data[$regperiod];
if ($format) {
    $price = formatCurrency($price);
}
echo "document.write('" . $price . "');";

?>