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
if (!function_exists("resellerclub_GetConfigArray")) {
    require ROOTDIR . "/modules/registrars/resellerclub/resellerclub.php";
}
function netearthone_GetConfigArray()
{
    $vals = resellerclub_GetConfigArray();
    $vals["FriendlyName"]["Value"] = "NetEarthOne";
    unset($vals["Description"]);
    return $vals;
}
function netearthone_GetNameservers($params)
{
    return resellerclub_GetNameservers($params);
}
function netearthone_SaveNameservers($params)
{
    return resellerclub_SaveNameservers($params);
}
function netearthone_GetRegistrarLock($params)
{
    return resellerclub_GetRegistrarLock($params);
}
function netearthone_SaveRegistrarLock($params)
{
    return resellerclub_SaveRegistrarLock($params);
}
function netearthone_RegisterDomain($params)
{
    return resellerclub_RegisterDomain($params);
}
function netearthone_TransferDomain($params)
{
    return resellerclub_TransferDomain($params);
}
function netearthone_RenewDomain($params)
{
    return resellerclub_RenewDomain($params);
}
function netearthone_GetContactDetails($params)
{
    return resellerclub_GetContactDetails($params);
}
function netearthone_SaveContactDetails($params)
{
    return resellerclub_SaveContactDetails($params);
}
function netearthone_GetEPPCode($params)
{
    return resellerclub_GetEPPCode($params);
}
function netearthone_RegisterNameserver($params)
{
    return resellerclub_RegisterNameserver($params);
}
function netearthone_ModifyNameserver($params)
{
    return resellerclub_ModifyNameserver($params);
}
function netearthone_DeleteNameserver($params)
{
    return resellerclub_DeleteNameserver($params);
}
function netearthone_RequestDelete($params)
{
    return resellerclub_RequestDelete($params);
}
function netearthone_GetDNS($params)
{
    return resellerclub_GetDNS($params);
}
function netearthone_SaveDNS($params)
{
    return resellerclub_SaveDNS($params);
}
function netearthone_GetEmailForwarding($params)
{
    return resellerclub_GetEmailForwarding($params);
}
function netearthone_SaveEmailForwarding($params)
{
    return resellerclub_SaveEmailForwarding($params);
}
function netearthone_ReleaseDomain($params)
{
    return resellerclub_ReleaseDomain($params);
}
function netearthone_IDProtectToggle($params)
{
    return resellerclub_IDProtectToggle($params);
}
function netearthone_Sync($params)
{
    return resellerclub_Sync($params);
}
function netearthone_TransferSync($params)
{
    return resellerclub_TransferSync($params);
}
function netearthone_CheckAvailability(array $params)
{
    return resellerclub_CheckAvailability($params);
}
function netearthone_GetDomainSuggestions(array $params)
{
    return resellerclub_GetDomainSuggestions($params);
}
function netearthone_GetPremiumPrice(array $params)
{
    return resellerclub_GetPremiumPrice($params);
}
function netearthone_DomainSuggestionOptions()
{
    return resellerclub_DomainSuggestionOptions();
}
function netearthone_GetDomainInformation(array $params)
{
    return resellerclub_GetDomainInformation($params);
}
function netearthone_ResendIRTPVerificationEmail(array $params)
{
    return resellerclub_ResendIRTPVerificationEmail($params);
}

?>