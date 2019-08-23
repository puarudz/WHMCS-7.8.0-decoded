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
function stargate_GetConfigArray()
{
    $vals = resellerclub_GetConfigArray();
    $vals["FriendlyName"]["Value"] = "StarGate/UK2";
    unset($vals["Description"]);
    return $vals;
}
function stargate_GetNameservers($params)
{
    return resellerclub_GetNameservers($params);
}
function stargate_SaveNameservers($params)
{
    return resellerclub_SaveNameservers($params);
}
function stargate_GetRegistrarLock($params)
{
    return resellerclub_GetRegistrarLock($params);
}
function stargate_SaveRegistrarLock($params)
{
    return resellerclub_SaveRegistrarLock($params);
}
function stargate_RegisterDomain($params)
{
    return resellerclub_RegisterDomain($params);
}
function stargate_TransferDomain($params)
{
    return resellerclub_TransferDomain($params);
}
function stargate_RenewDomain($params)
{
    return resellerclub_RenewDomain($params);
}
function stargate_GetContactDetails($params)
{
    return resellerclub_GetContactDetails($params);
}
function stargate_SaveContactDetails($params)
{
    return resellerclub_SaveContactDetails($params);
}
function stargate_GetEPPCode($params)
{
    return resellerclub_GetEPPCode($params);
}
function stargate_RegisterNameserver($params)
{
    return resellerclub_RegisterNameserver($params);
}
function stargate_ModifyNameserver($params)
{
    return resellerclub_ModifyNameserver($params);
}
function stargate_DeleteNameserver($params)
{
    return resellerclub_DeleteNameserver($params);
}
function stargate_RequestDelete($params)
{
    return resellerclub_RequestDelete($params);
}
function stargate_GetDNS($params)
{
    return resellerclub_GetDNS($params);
}
function stargate_SaveDNS($params)
{
    return resellerclub_SaveDNS($params);
}
function stargate_GetEmailForwarding($params)
{
    return resellerclub_GetEmailForwarding($params);
}
function stargate_SaveEmailForwarding($params)
{
    return resellerclub_SaveEmailForwarding($params);
}
function stargate_ReleaseDomain($params)
{
    return resellerclub_ReleaseDomain($params);
}
function stargate_IDProtectToggle($params)
{
    return resellerclub_IDProtectToggle($params);
}
function stargate_Sync($params)
{
    return resellerclub_Sync($params);
}
function stargate_TransferSync($params)
{
    return resellerclub_TransferSync($params);
}
function stargate_CheckAvailability(array $params)
{
    return resellerclub_CheckAvailability($params);
}
function stargate_GetDomainSuggestions(array $params)
{
    return resellerclub_GetDomainSuggestions($params);
}
function stargate_GetPremiumPrice(array $params)
{
    return resellerclub_GetPremiumPrice($params);
}
function stargate_DomainSuggestionOptions()
{
    return resellerclub_DomainSuggestionOptions();
}
function stargate_GetDomainInformation(array $params)
{
    return resellerclub_GetDomainInformation($params);
}
function stargate_ResendIRTPVerificationEmail(array $params)
{
    return resellerclub_ResendIRTPVerificationEmail($params);
}

?>