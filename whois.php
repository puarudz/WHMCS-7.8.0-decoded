<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$whmcs = App::self();
$domain = $whmcs->get_req_var("domain");
$userId = (int) WHMCS\Session::get("uid");
$domainCheckerSearches = (array) WHMCS\Session::get("domaincheckerwhois");
if (!in_array($domain, $domainCheckerSearches) && $userId && !WHMCS\User\Client::find($userId)->hasDomain($domain)) {
    throw new WHMCS\Exception\Fatal("WHOIS Information can only be retrieved for domains you own or that have been returned in an availability search. To view the whois data for this domain, please use the domain checker.");
}
$ca = new WHMCS\ClientArea();
$ca->setPageTitle(Lang::trans("whoisinfo"));
$ca->addToBreadCrumb("index.php", $whmcs->get_lang("globalsystemname"));
$ca->addToBreadCrumb("whois.php?domain=" . $domain, Lang::trans("whoisinfo"));
$ca->initPage();
$domainparts = explode(".", $domain, 2);
$sld = $domainparts[0];
$tld = "." . $domainparts[1];
$whois = new WHMCS\WHOIS();
if ($whois->canLookup($tld)) {
    $result = $whois->lookup(array("sld" => $sld, "tld" => $tld));
    if ($result["result"] == "available" && !isset($result["whois"])) {
        $whoisInformation = (string) $domain . " " . Lang::trans("domainavailable2");
    } else {
        $whoisInformation = $result["whois"];
    }
} else {
    $whoisInformation = "Unable to lookup whois information for " . $domain;
}
$ca->assign("domain", $domain);
$ca->assign("whois", $whoisInformation);
$ca->setTemplate("whois");
$ca->disableHeaderFooterOutput();
$ca->addOutputHookFunction("ClientAreaPageViewWHOIS");
$ca->output();

?>