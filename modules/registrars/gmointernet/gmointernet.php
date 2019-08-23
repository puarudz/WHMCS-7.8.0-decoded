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
define(ONAMAEAPI_ACTIONTYPE_DOMAINCHECK, "DomainCheck");
define(ONAMAEAPI_ACTIONTYPE_DOMAININFO, "DomainInfo");
define(ONAMAEAPI_ACTIONTYPE_DOMAINUPDATE, "DomainUpdate");
define(ONAMAEAPI_ACTIONTYPE_DOMAINCREATE, "DomainCreate");
define(ONAMAEAPI_ACTIONTYPE_DOMAINRENEW, "DomainRenew");
define(ONAMAEAPI_ACTIONTYPE_TRANSFERCHECK, "TransferCheck");
define(ONAMAEAPI_ACTIONTYPE_DOMAINTRANSFERREQUEST, "DomainTransferRequest");
define(ONAMAEAPI_ACTIONTYPE_JPREGTRANSFERREQUEST, "JpRegTransferRequest");
define(ONAMAEAPI_ACTIONTYPE_JPDOMTRANSFERREQUEST, "JpDomTransferRequest");
define(ONAMAEAPI_ACTIONTYPE_WHOISPROXY, "WhoisProxy");
define(ONAMAEAPI_KEY_REQ_LOGINID, "loginId");
define(ONAMAEAPI_KEY_REQ_LOGINPASSWORD, "loginPassword");
define(ONAMAEAPI_KEY_REQ_ACTIONTYPE, "actionType");
define(ONAMAEAPI_KEY_REQ_DOMAINNAME, "domainName");
define(ONAMAEAPI_KEY_REQ_AUTORENEW, "autoRenew");
define(ONAMAEAPI_KEY_REQ_PERIOD, "period");
define(ONAMAEAPI_KEY_REQ_NS, "ns");
define(ONAMAEAPI_KEY_REQ_CUREXPDATE, "curExpDate");
define(ONAMAEAPI_KEY_REQ_AUTOCODE, "authcode");
define(ONAMAEAPI_KEY_REQ_TRANSFERLOCK, "transferLock");
define(ONAMAEAPI_KEY_REQ_PROXYFLG, "proxyflg");
define(ONAMAEAPI_KEY_REQ_DOMAIN, "domain");
define(ONAMAEAPI_KEY_REQ_TLD, "tld");
define(ONAMAEAPI_KEY_REQ_REGFNAME, "regFname");
define(ONAMAEAPI_KEY_REQ_REGLNAME, "regLname");
define(ONAMAEAPI_KEY_REQ_REGROLE, "regRole");
define(ONAMAEAPI_KEY_REQ_REGORGANIZATION, "regOrganization");
define(ONAMAEAPI_KEY_REQ_REGCC, "regCc");
define(ONAMAEAPI_KEY_REQ_REGPC, "regPc");
define(ONAMAEAPI_KEY_REQ_REGSP, "regSp");
define(ONAMAEAPI_KEY_REQ_REGCITY, "regCity");
define(ONAMAEAPI_KEY_REQ_REGSTREET1, "regStreet1");
define(ONAMAEAPI_KEY_REQ_REGSTREET2, "regStreet2");
define(ONAMAEAPI_KEY_REQ_REGPHONE, "regPhone");
define(ONAMAEAPI_KEY_REQ_REGFAX, "regFax");
define(ONAMAEAPI_KEY_REQ_REGEMAIL, "regEmail");
define(ONAMAEAPI_KEY_REQ_ADMFNAME, "admFname");
define(ONAMAEAPI_KEY_REQ_ADMLNAME, "admLname");
define(ONAMAEAPI_KEY_REQ_ADMROLE, "admRole");
define(ONAMAEAPI_KEY_REQ_ADMORGANIZATION, "admOrganization");
define(ONAMAEAPI_KEY_REQ_ADMCC, "admCc");
define(ONAMAEAPI_KEY_REQ_ADMPC, "admPc");
define(ONAMAEAPI_KEY_REQ_ADMSP, "admSp");
define(ONAMAEAPI_KEY_REQ_ADMCITY, "admCity");
define(ONAMAEAPI_KEY_REQ_ADMSTREET1, "admStreet1");
define(ONAMAEAPI_KEY_REQ_ADMSTREET2, "admStreet2");
define(ONAMAEAPI_KEY_REQ_ADMPHONE, "admPhone");
define(ONAMAEAPI_KEY_REQ_ADMFAX, "admFax");
define(ONAMAEAPI_KEY_REQ_ADMEMAIL, "admEmail");
define(ONAMAEAPI_KEY_REQ_TECFNAME, "tecFname");
define(ONAMAEAPI_KEY_REQ_TECLNAME, "tecLname");
define(ONAMAEAPI_KEY_REQ_TECROLE, "tecRole");
define(ONAMAEAPI_KEY_REQ_TECORGANIZATION, "tecOrganization");
define(ONAMAEAPI_KEY_REQ_TECCC, "tecCc");
define(ONAMAEAPI_KEY_REQ_TECPC, "tecPc");
define(ONAMAEAPI_KEY_REQ_TECSP, "tecSp");
define(ONAMAEAPI_KEY_REQ_TECCITY, "tecCity");
define(ONAMAEAPI_KEY_REQ_TECSTREET1, "tecStreet1");
define(ONAMAEAPI_KEY_REQ_TECSTREET2, "tecStreet2");
define(ONAMAEAPI_KEY_REQ_TECPHONE, "tecPhone");
define(ONAMAEAPI_KEY_REQ_TECFAX, "tecFax");
define(ONAMAEAPI_KEY_REQ_TECEMAIL, "tecEmail");
define(ONAMAEAPI_KEY_REQ_BILFNAME, "bilFname");
define(ONAMAEAPI_KEY_REQ_BILLNAME, "bilLname");
define(ONAMAEAPI_KEY_REQ_BILROLE, "bilRole");
define(ONAMAEAPI_KEY_REQ_BILORGANIZATION, "bilOrganization");
define(ONAMAEAPI_KEY_REQ_BILCC, "bilCc");
define(ONAMAEAPI_KEY_REQ_BILPC, "bilPc");
define(ONAMAEAPI_KEY_REQ_BILSP, "bilSp");
define(ONAMAEAPI_KEY_REQ_BILCITY, "bilCity");
define(ONAMAEAPI_KEY_REQ_BILSTREET1, "bilStreet1");
define(ONAMAEAPI_KEY_REQ_BILSTREET2, "bilStreet2");
define(ONAMAEAPI_KEY_REQ_BILPHONE, "bilPhone");
define(ONAMAEAPI_KEY_REQ_BILFAX, "bilFax");
define(ONAMAEAPI_KEY_REQ_BILEMAIL, "bilEmail");
define(ONAMAEAPI_RESCODE_SUCCESS, 10000);
define(ONAMAEAPI_RESCODE_SUCCESS_TRANSFER, 11001);
define(ONAMAEAPI_KEY_RES_RESULTCODE, "ResultCode");
define(ONAMAEAPI_KEY_RES_RESULTMESSAGE, "ResultMessage");
define(ONAMAEAPI_KEY_RES_CHECKRESULTCODE, "CheckResultCode");
define(ONAMAEAPI_KEY_RES_CHECKRESULTMESSAGE, "CheckResultMessage");
define(ONAMAEAPI_KEY_RES_EXPDATE, "ExpDate");
define(ONAMAEAPI_KEY_RES_NS, "Ns");
define(ONAMAEAPI_KEY_RES_REGFNAME, "RegFname");
define(ONAMAEAPI_KEY_RES_REGLNAME, "RegLname");
define(ONAMAEAPI_KEY_RES_REGORGANIZATION, "RegOrganization");
define(ONAMAEAPI_KEY_RES_REGCC, "RegCc");
define(ONAMAEAPI_KEY_RES_REGPC, "RegPc");
define(ONAMAEAPI_KEY_RES_REGSP, "RegSp");
define(ONAMAEAPI_KEY_RES_REGCITY, "RegCity");
define(ONAMAEAPI_KEY_RES_REGSTREET1, "RegStreet1");
define(ONAMAEAPI_KEY_RES_REGSTREET2, "RegStreet2");
define(ONAMAEAPI_KEY_RES_REGPHONE, "RegPhone");
define(ONAMAEAPI_KEY_RES_REGFAX, "RegFax");
define(ONAMAEAPI_KEY_RES_REGEMAIL, "RegEmail");
define(ONAMAEAPI_KEY_RES_ADMFNAME, "AdmFname");
define(ONAMAEAPI_KEY_RES_ADMLNAME, "AdmLname");
define(ONAMAEAPI_KEY_RES_ADMORGANIZATION, "AdmOrganization");
define(ONAMAEAPI_KEY_RES_ADMCC, "AdmCc");
define(ONAMAEAPI_KEY_RES_ADMPC, "AdmPc");
define(ONAMAEAPI_KEY_RES_ADMSP, "AdmSp");
define(ONAMAEAPI_KEY_RES_ADMCITY, "AdmCity");
define(ONAMAEAPI_KEY_RES_ADMSTREET1, "AdmStreet1");
define(ONAMAEAPI_KEY_RES_ADMSTREET2, "AdmStreet2");
define(ONAMAEAPI_KEY_RES_ADMPHONE, "AdmPhone");
define(ONAMAEAPI_KEY_RES_ADMFAX, "AdmFax");
define(ONAMAEAPI_KEY_RES_ADMEMAIL, "AdmEmail");
define(ONAMAEAPI_KEY_RES_TECFNAME, "TecFname");
define(ONAMAEAPI_KEY_RES_TECLNAME, "TecLname");
define(ONAMAEAPI_KEY_RES_TECORGANIZATION, "TecOrganization");
define(ONAMAEAPI_KEY_RES_TECCC, "TecCc");
define(ONAMAEAPI_KEY_RES_TECPC, "TecPc");
define(ONAMAEAPI_KEY_RES_TECSP, "TecSp");
define(ONAMAEAPI_KEY_RES_TECCITY, "TecCity");
define(ONAMAEAPI_KEY_RES_TECSTREET1, "TecStreet1");
define(ONAMAEAPI_KEY_RES_TECSTREET2, "TecStreet2");
define(ONAMAEAPI_KEY_RES_TECPHONE, "TecPhone");
define(ONAMAEAPI_KEY_RES_TECFAX, "TecFax");
define(ONAMAEAPI_KEY_RES_TECEMAIL, "TecEmail");
define(ONAMAEAPI_KEY_RES_BILFNAME, "BilFname");
define(ONAMAEAPI_KEY_RES_BILLNAME, "BilLname");
define(ONAMAEAPI_KEY_RES_BILORGANIZATION, "BilOrganization");
define(ONAMAEAPI_KEY_RES_BILCC, "BilCc");
define(ONAMAEAPI_KEY_RES_BILPC, "BilPc");
define(ONAMAEAPI_KEY_RES_BILSP, "BilSp");
define(ONAMAEAPI_KEY_RES_BILCITY, "BilCity");
define(ONAMAEAPI_KEY_RES_BILSTREET1, "BilStreet1");
define(ONAMAEAPI_KEY_RES_BILSTREET2, "BilStreet2");
define(ONAMAEAPI_KEY_RES_BILPHONE, "BilPhone");
define(ONAMAEAPI_KEY_RES_BILFAX, "BilFax");
define(ONAMAEAPI_KEY_RES_BILEMAIL, "BilEmail");
define(ONAMAEAPI_KEY_RES_DOMAINCOUNT, "DomainCount");
define(ONAMAEAPI_KEY_RES_AUTHCODE, "Authcode");
define(ONAMAEAPI_KEY_RES_STATUS, "Status");
define(ONAMAEAPI_KEY_RES_DOMAINNAME, "DomainName");
$DUPLICATON_KEY_ARRAY = array(ONAMAEAPI_KEY_RES_NS => 1, ONAMAEAPI_KEY_RES_STATUS => 1);
function gmointernet_getConfigArray()
{
    $configarray = array("Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your username here"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your password here"), "TestMode" => array("Type" => "yesno", "Description" => "Enable Test Mode"));
    return $configarray;
}
function gmointernet_GetNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAININFO, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (isOnamaeSuccess($resultArray)) {
        global $DUPLICATON_KEY_ARRAY;
        $nsNumMax = $DUPLICATON_KEY_ARRAY[ONAMAEAPI_KEY_RES_NS];
        for ($i = 1; $i < $nsNumMax && $i < 5; $i++) {
            $key = ONAMAEAPI_KEY_RES_NS . $i;
            if (array_key_exists($key, $resultArray)) {
                $values["ns" . $i] = $resultArray[$key];
            }
        }
    } else {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_SaveNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINUPDATE, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $onamaeNsParamArray = array($nameserver1, $nameserver2, $nameserver3, $nameserver4);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray, $onamaeNsParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (!isOnamaeSuccess($resultArray)) {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_GetRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAININFO, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    $lockstatus = "unlocked";
    if (isOnamaeSuccess($resultArray)) {
        global $DUPLICATON_KEY_ARRAY;
        $statusNumMax = $DUPLICATON_KEY_ARRAY[ONAMAEAPI_KEY_RES_STATUS];
        $resultStatusArray = array();
        for ($i = 1; $i < $statusNumMax; $i++) {
            $key = ONAMAEAPI_KEY_RES_STATUS . $i;
            if (array_key_exists($key, $resultArray)) {
                $resultStatusArray[] = $resultArray[$key];
            }
        }
        if (in_array("TRANSFER_LOCK", $resultStatusArray)) {
            $lockstatus = "locked";
        } else {
            $lockstatus = "unlocked";
        }
    }
    return $lockstatus;
}
function gmointernet_SaveRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($params["lockenabled"]) {
        $lockstatus = "yes";
    } else {
        $lockstatus = "no";
    }
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINUPDATE, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_TRANSFERLOCK => $lockstatus);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (!isOnamaeSuccess($resultArray)) {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_RegisterDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["phonenumber"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = $params["adminphonenumber"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINCHECK, ONAMAEAPI_KEY_REQ_DOMAIN => $sld, ONAMAEAPI_KEY_REQ_TLD => $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (!isOnamaeSuccess($resultArray)) {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    } else {
        $domainCheckArray = explode(":", $resultArray[ONAMAEAPI_KEY_RES_DOMAINNAME]);
        $domainCheckResult = -1;
        for ($i = 0; $i < count($domainCheckArray); $i++) {
            if ($sld . "." . $tld === $domainCheckArray[$i]) {
                $domainCheckResult = $domainCheckArray[$i + 1];
                break;
            }
        }
        if ($domainCheckResult == 0 || $domainCheckResult == 1) {
            $onamaeReq2ParamArray = NULL;
            $onamaeReq2BaseParamArray = NULL;
            $onamaeReq2AddParamArray = NULL;
            $result2 = NULL;
            $result2Array = NULL;
            $onamaeNsParamArray = array($nameserver1, $nameserver2, $nameserver3, $nameserver4);
            if ($domainCheckResult == 0) {
                $onamaeReq2AddParamArray = array(ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINUPDATE);
            } else {
                $onamaeReq2AddParamArray = array(ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINCREATE, ONAMAEAPI_KEY_REQ_AUTORENEW => "no", ONAMAEAPI_KEY_REQ_PERIOD => $regperiod);
            }
            $onamaeReq2BaseParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_PERIOD => $regperiod, ONAMAEAPI_KEY_REQ_REGFNAME => $RegistrantFirstName, ONAMAEAPI_KEY_REQ_REGLNAME => $RegistrantLastName, ONAMAEAPI_KEY_REQ_REGROLE => "R", ONAMAEAPI_KEY_REQ_REGORGANIZATION => $RegistrantFirstName . $RegistrantLastName, ONAMAEAPI_KEY_REQ_REGCC => $RegistrantCountry, ONAMAEAPI_KEY_REQ_REGPC => $RegistrantPostalCode, ONAMAEAPI_KEY_REQ_REGSP => $RegistrantStateProvince, ONAMAEAPI_KEY_REQ_REGCITY => $RegistrantCity, ONAMAEAPI_KEY_REQ_REGSTREET1 => $RegistrantAddress1, ONAMAEAPI_KEY_REQ_REGSTREET2 => $RegistrantAddress2, ONAMAEAPI_KEY_REQ_REGPHONE => $RegistrantPhone, ONAMAEAPI_KEY_REQ_REGEMAIL => $RegistrantEmailAddress, ONAMAEAPI_KEY_REQ_ADMFNAME => $AdminFirstName, ONAMAEAPI_KEY_REQ_ADMLNAME => $AdminLastName, ONAMAEAPI_KEY_REQ_ADMROLE => "R", ONAMAEAPI_KEY_REQ_ADMORGANIZATION => $AdminFirstName . $AdminLastName, ONAMAEAPI_KEY_REQ_ADMCC => $AdminCountry, ONAMAEAPI_KEY_REQ_ADMPC => $AdminPostalCode, ONAMAEAPI_KEY_REQ_ADMSP => $AdminStateProvince, ONAMAEAPI_KEY_REQ_ADMCITY => $AdminCity, ONAMAEAPI_KEY_REQ_ADMSTREET1 => $AdminAddress1, ONAMAEAPI_KEY_REQ_ADMSTREET2 => $AdminAddress2, ONAMAEAPI_KEY_REQ_ADMPHONE => $AdminPhone, ONAMAEAPI_KEY_REQ_ADMEMAIL => $AdminEmailAddress, ONAMAEAPI_KEY_REQ_TECFNAME => $AdminFirstName, ONAMAEAPI_KEY_REQ_TECLNAME => $AdminLastName, ONAMAEAPI_KEY_REQ_TECROLE => "R", ONAMAEAPI_KEY_REQ_TECORGANIZATION => $AdminFirstName . $AdminLastName, ONAMAEAPI_KEY_REQ_TECCC => $AdminCountry, ONAMAEAPI_KEY_REQ_TECPC => $AdminPostalCode, ONAMAEAPI_KEY_REQ_TECSP => $AdminStateProvince, ONAMAEAPI_KEY_REQ_TECCITY => $AdminCity, ONAMAEAPI_KEY_REQ_TECSTREET1 => $AdminAddress1, ONAMAEAPI_KEY_REQ_TECSTREET2 => $AdminAddress2, ONAMAEAPI_KEY_REQ_TECPHONE => $AdminPhone, ONAMAEAPI_KEY_REQ_TECEMAIL => $AdminEmailAddress, ONAMAEAPI_KEY_REQ_BILFNAME => $AdminFirstName, ONAMAEAPI_KEY_REQ_BILLNAME => $AdminLastName, ONAMAEAPI_KEY_REQ_BILROLE => "R", ONAMAEAPI_KEY_REQ_BILORGANIZATION => $AdminFirstName . $AdminLastName, ONAMAEAPI_KEY_REQ_BILCC => $AdminCountry, ONAMAEAPI_KEY_REQ_BILPC => $AdminPostalCode, ONAMAEAPI_KEY_REQ_BILSP => $AdminStateProvince, ONAMAEAPI_KEY_REQ_BILCITY => $AdminCity, ONAMAEAPI_KEY_REQ_BILSTREET1 => $AdminAddress1, ONAMAEAPI_KEY_REQ_BILSTREET2 => $AdminAddress2, ONAMAEAPI_KEY_REQ_BILPHONE => $AdminPhone, ONAMAEAPI_KEY_REQ_BILEMAIL => $AdminEmailAddress);
            $onamaeReq2ParamArray = array_merge($onamaeReq2BaseParamArray, $onamaeReq2AddParamArray);
            $result2 = onamaeSendHttpRequest($testmode, $onamaeReq2ParamArray, $onamaeNsParamArray);
            $result2Array = onamaeReplaceResult($result2);
            if (isOnamaeSuccess($result2Array)) {
                $idProtection = !empty($params["idprotection"]) && $params["idprotection"] == 1 ? "y" : "n";
                $onamaeReqParam3Array = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_WHOISPROXY, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_PROXYFLG => $idProtection);
                $result3 = onamaeSendHttpRequest($testmode, $onamaeReqParam3Array);
                $result3Array = onamaeReplaceResult($result3);
                if (!isOnamaeSuccess($result3Array)) {
                    $values["error"] = $result3Array[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
                }
            } else {
                $values["error"] = $result2Array[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
            }
        } else {
            $values["error"] = "Domain not available (domain register check error)";
        }
    }
    return $values;
}
function gmointernet_TransferDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $transfersecret = $params["transfersecret"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_TRANSFERCHECK, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (isOnamaeTransferCheckResult($resultArray)) {
        $onamaeReqParam2Array = array();
        $result2 = "";
        $result2Array = array();
        $onamaeReqParam2Array = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINTRANSFERREQUEST, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_AUTORENEW => "no", ONAMAEAPI_KEY_REQ_AUTOCODE => $transfersecret);
        $result2 = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
        $result2Array = onamaeReplaceResult($result2);
        if (!isOnamaeSuccess($result2Array, ONAMAEAPI_RESCODE_SUCCESS_TRANSFER)) {
            $values["error"] = $result2Array[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
        }
    } else {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE] . "<br>\n" . $resultArray[ONAMAEAPI_KEY_RES_CHECKRESULTMESSAGE];
    }
    return $values;
}
function gmointernet_RenewDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAININFO, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $resultInfo = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($resultInfo);
    $values = array();
    if (!empty($resultArray[ONAMAEAPI_KEY_RES_EXPDATE])) {
        $curexpdate = vsprintf("%d/%02d/%02d", sscanf($resultArray[ONAMAEAPI_KEY_RES_EXPDATE], "%d/%d/%d"));
        $onamaeReqParam2Array = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINRENEW, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_CUREXPDATE => $curexpdate, ONAMAEAPI_KEY_REQ_PERIOD => $regperiod);
        $result2 = onamaeSendHttpRequest($testmode, $onamaeReqParam2Array);
        $result2Array = onamaeReplaceResult($result2);
        if (!isOnamaeSuccess($result2Array)) {
            $values["error"] = $result2Array[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
        }
    } else {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_GetContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAININFO, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (isOnamaeSuccess($resultArray)) {
        $values["Registrant"]["First Name"] = $resultArray[ONAMAEAPI_KEY_RES_REGFNAME];
        $values["Registrant"]["Last Name"] = $resultArray[ONAMAEAPI_KEY_RES_REGLNAME];
        $values["Registrant"]["Email Address"] = $resultArray[ONAMAEAPI_KEY_RES_REGEMAIL];
        $values["Registrant"]["Company Name"] = $resultArray[ONAMAEAPI_KEY_RES_REGORGANIZATION];
        $values["Registrant"]["Address 1"] = $resultArray[ONAMAEAPI_KEY_RES_REGSTREET1];
        $values["Registrant"]["Address 2"] = $resultArray[ONAMAEAPI_KEY_RES_REGSTREET2];
        $values["Registrant"]["City"] = $resultArray[ONAMAEAPI_KEY_RES_REGCITY];
        $values["Registrant"]["State/Region"] = $resultArray[ONAMAEAPI_KEY_RES_REGSP];
        $values["Registrant"]["Postcode"] = $resultArray[ONAMAEAPI_KEY_RES_REGPC];
        $values["Registrant"]["Country Code"] = $resultArray[ONAMAEAPI_KEY_RES_REGCC];
        $values["Registrant"]["Phone Number"] = $resultArray[ONAMAEAPI_KEY_RES_REGPHONE];
        $values["Registrant"]["Fax Number"] = $resultArray[ONAMAEAPI_KEY_RES_REGFAX];
        $values["Admin"]["First Name"] = $resultArray[ONAMAEAPI_KEY_RES_ADMFNAME];
        $values["Admin"]["Last Name"] = $resultArray[ONAMAEAPI_KEY_RES_ADMLNAME];
        $values["Admin"]["Email Address"] = $resultArray[ONAMAEAPI_KEY_RES_ADMEMAIL];
        $values["Admin"]["Company Name"] = $resultArray[ONAMAEAPI_KEY_RES_ADMORGANIZATION];
        $values["Admin"]["Address 1"] = $resultArray[ONAMAEAPI_KEY_RES_ADMSTREET1];
        $values["Admin"]["Address 2"] = $resultArray[ONAMAEAPI_KEY_RES_ADMSTREET2];
        $values["Admin"]["City"] = $resultArray[ONAMAEAPI_KEY_RES_ADMCITY];
        $values["Admin"]["State/Region"] = $resultArray[ONAMAEAPI_KEY_RES_ADMSP];
        $values["Admin"]["Postcode"] = $resultArray[ONAMAEAPI_KEY_RES_ADMPC];
        $values["Admin"]["Country Code"] = $resultArray[ONAMAEAPI_KEY_RES_ADMCC];
        $values["Admin"]["Phone Number"] = $resultArray[ONAMAEAPI_KEY_RES_ADMPHONE];
        $values["Admin"]["Fax Number"] = $resultArray[ONAMAEAPI_KEY_RES_ADMFAX];
        $values["Tech"]["First Name"] = $resultArray[ONAMAEAPI_KEY_RES_TECFNAME];
        $values["Tech"]["Last Name"] = $resultArray[ONAMAEAPI_KEY_RES_TECLNAME];
        $values["Tech"]["Email Address"] = $resultArray[ONAMAEAPI_KEY_RES_TECEMAIL];
        $values["Tech"]["Company Name"] = $resultArray[ONAMAEAPI_KEY_RES_TECORGANIZATION];
        $values["Tech"]["Address 1"] = $resultArray[ONAMAEAPI_KEY_RES_TECSTREET1];
        $values["Tech"]["Address 2"] = $resultArray[ONAMAEAPI_KEY_RES_TECSTREET2];
        $values["Tech"]["City"] = $resultArray[ONAMAEAPI_KEY_RES_TECCITY];
        $values["Tech"]["State/Region"] = $resultArray[ONAMAEAPI_KEY_RES_TECSP];
        $values["Tech"]["Postcode"] = $resultArray[ONAMAEAPI_KEY_RES_TECPC];
        $values["Tech"]["Country Code"] = $resultArray[ONAMAEAPI_KEY_RES_TECCC];
        $values["Tech"]["Phone Number"] = $resultArray[ONAMAEAPI_KEY_RES_TECPHONE];
        $values["Tech"]["Fax Number"] = $resultArray[ONAMAEAPI_KEY_RES_TECFAX];
        $values["Bil"]["First Name"] = $resultArray[ONAMAEAPI_KEY_RES_BILFNAME];
        $values["Bil"]["Last Name"] = $resultArray[ONAMAEAPI_KEY_RES_BILLNAME];
        $values["Bil"]["Email Address"] = $resultArray[ONAMAEAPI_KEY_RES_BILEMAIL];
        $values["Bil"]["Company Name"] = $resultArray[ONAMAEAPI_KEY_RES_BILORGANIZATION];
        $values["Bil"]["Address 1"] = $resultArray[ONAMAEAPI_KEY_RES_BILSTREET1];
        $values["Bil"]["Address 2"] = $resultArray[ONAMAEAPI_KEY_RES_BILSTREET2];
        $values["Bil"]["City"] = $resultArray[ONAMAEAPI_KEY_RES_BILCITY];
        $values["Bil"]["State/Region"] = $resultArray[ONAMAEAPI_KEY_RES_BILSP];
        $values["Bil"]["Postcode"] = $resultArray[ONAMAEAPI_KEY_RES_BILPC];
        $values["Bil"]["Country Code"] = $resultArray[ONAMAEAPI_KEY_RES_BILCC];
        $values["Bil"]["Phone Number"] = $resultArray[ONAMAEAPI_KEY_RES_BILPHONE];
        $values["Bil"]["Fax Number"] = $resultArray[ONAMAEAPI_KEY_RES_BILFAX];
    } else {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_SaveContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $firstname = $params["contactdetails"]["Registrant"]["First Name"];
    $lastname = $params["contactdetails"]["Registrant"]["Last Name"];
    $emailaddress = $params["contactdetails"]["Registrant"]["Email Address"];
    $companyname = $params["contactdetails"]["Registrant"]["Company Name"];
    $address1 = $params["contactdetails"]["Registrant"]["Address 1"];
    $address2 = $params["contactdetails"]["Registrant"]["Address 2"];
    $city = $params["contactdetails"]["Registrant"]["City"];
    $state = $params["contactdetails"]["Registrant"]["State/Region"];
    $postcode = $params["contactdetails"]["Registrant"]["Postcode"];
    $countrycode = $params["contactdetails"]["Registrant"]["Country Code"];
    $phonenumber = $params["contactdetails"]["Registrant"]["Phone Number"];
    $faxnumber = $params["contactdetails"]["Registrant"]["Fax Number"];
    $adminfirstname = $params["contactdetails"]["Admin"]["First Name"];
    $adminlastname = $params["contactdetails"]["Admin"]["Last Name"];
    $adminemailaddress = $params["contactdetails"]["Admin"]["Email Address"];
    $admincompanyname = $params["contactdetails"]["Admin"]["Company Name"];
    $adminaddress1 = $params["contactdetails"]["Admin"]["Address 1"];
    $adminaddress2 = $params["contactdetails"]["Admin"]["Address 2"];
    $admincity = $params["contactdetails"]["Admin"]["City"];
    $adminstate = $params["contactdetails"]["Admin"]["State/Region"];
    $adminpostcode = $params["contactdetails"]["Admin"]["Postcode"];
    $admincountrycode = $params["contactdetails"]["Admin"]["Country Code"];
    $adminphonenumber = $params["contactdetails"]["Admin"]["Phone Number"];
    $adminfaxnumber = $params["contactdetails"]["Admin"]["Fax Number"];
    $techfirstname = $params["contactdetails"]["Tech"]["First Name"];
    $techlastname = $params["contactdetails"]["Tech"]["Last Name"];
    $techemailaddress = $params["contactdetails"]["Tech"]["Email Address"];
    $techcompanyname = $params["contactdetails"]["Tech"]["Company Name"];
    $techaddress1 = $params["contactdetails"]["Tech"]["Address 1"];
    $techaddress2 = $params["contactdetails"]["Tech"]["Address 2"];
    $techcity = $params["contactdetails"]["Tech"]["City"];
    $techstate = $params["contactdetails"]["Tech"]["State/Region"];
    $techpostcode = $params["contactdetails"]["Tech"]["Postcode"];
    $techcountrycode = $params["contactdetails"]["Tech"]["Country Code"];
    $techphonenumber = $params["contactdetails"]["Tech"]["Phone Number"];
    $techfaxnumber = $params["contactdetails"]["Tech"]["Fax Number"];
    $bilfirstname = $params["contactdetails"]["Bil"]["First Name"];
    $billastname = $params["contactdetails"]["Bil"]["Last Name"];
    $bilemailaddress = $params["contactdetails"]["Bil"]["Email Address"];
    $bilcompanyname = $params["contactdetails"]["Bil"]["Company Name"];
    $biladdress1 = $params["contactdetails"]["Bil"]["Address 1"];
    $biladdress2 = $params["contactdetails"]["Bil"]["Address 2"];
    $bilcity = $params["contactdetails"]["Bil"]["City"];
    $bilstate = $params["contactdetails"]["Bil"]["State/Region"];
    $bilpostcode = $params["contactdetails"]["Bil"]["Postcode"];
    $bilcountrycode = $params["contactdetails"]["Bil"]["Country Code"];
    $bilphonenumber = $params["contactdetails"]["Bil"]["Phone Number"];
    $bilfaxnumber = $params["contactdetails"]["Bil"]["Fax Number"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAINUPDATE, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld, ONAMAEAPI_KEY_REQ_REGFNAME => $firstname, ONAMAEAPI_KEY_REQ_REGLNAME => $lastname, ONAMAEAPI_KEY_REQ_REGROLE => "R", ONAMAEAPI_KEY_REQ_REGORGANIZATION => $companyname, ONAMAEAPI_KEY_REQ_REGCC => $countrycode, ONAMAEAPI_KEY_REQ_REGPC => $postcode, ONAMAEAPI_KEY_REQ_REGSP => $state, ONAMAEAPI_KEY_REQ_REGCITY => $city, ONAMAEAPI_KEY_REQ_REGSTREET1 => $address1, ONAMAEAPI_KEY_REQ_REGSTREET2 => $address2, ONAMAEAPI_KEY_REQ_REGPHONE => $phonenumber, ONAMAEAPI_KEY_REQ_REGFAX => $faxnumber, ONAMAEAPI_KEY_REQ_REGEMAIL => $emailaddress, ONAMAEAPI_KEY_REQ_ADMFNAME => $adminfirstname, ONAMAEAPI_KEY_REQ_ADMLNAME => $adminlastname, ONAMAEAPI_KEY_REQ_ADMROLE => "R", ONAMAEAPI_KEY_REQ_ADMORGANIZATION => $admincompanyname, ONAMAEAPI_KEY_REQ_ADMCC => $admincountrycode, ONAMAEAPI_KEY_REQ_ADMPC => $adminpostcode, ONAMAEAPI_KEY_REQ_ADMSP => $adminstate, ONAMAEAPI_KEY_REQ_ADMCITY => $admincity, ONAMAEAPI_KEY_REQ_ADMSTREET1 => $adminaddress1, ONAMAEAPI_KEY_REQ_ADMSTREET2 => $adminaddress2, ONAMAEAPI_KEY_REQ_ADMPHONE => $adminphonenumber, ONAMAEAPI_KEY_REQ_ADMFAX => $adminfaxnumber, ONAMAEAPI_KEY_REQ_ADMEMAIL => $adminemailaddress, ONAMAEAPI_KEY_REQ_TECFNAME => $techfirstname, ONAMAEAPI_KEY_REQ_TECLNAME => $techlastname, ONAMAEAPI_KEY_REQ_TECROLE => "R", ONAMAEAPI_KEY_REQ_TECORGANIZATION => $techcompanyname, ONAMAEAPI_KEY_REQ_TECCC => $techcountrycode, ONAMAEAPI_KEY_REQ_TECPC => $techpostcode, ONAMAEAPI_KEY_REQ_TECSP => $techstate, ONAMAEAPI_KEY_REQ_TECCITY => $techcity, ONAMAEAPI_KEY_REQ_TECSTREET1 => $techaddress1, ONAMAEAPI_KEY_REQ_TECSTREET2 => $techaddress2, ONAMAEAPI_KEY_REQ_TECPHONE => $techphonenumber, ONAMAEAPI_KEY_REQ_TECFAX => $techfaxnumber, ONAMAEAPI_KEY_REQ_TECEMAIL => $techemailaddress, ONAMAEAPI_KEY_REQ_BILFNAME => $bilfirstname, ONAMAEAPI_KEY_REQ_BILLNAME => $billastname, ONAMAEAPI_KEY_REQ_BILROLE => "R", ONAMAEAPI_KEY_REQ_BILORGANIZATION => $bilcompanyname, ONAMAEAPI_KEY_REQ_BILCC => $bilcountrycode, ONAMAEAPI_KEY_REQ_BILPC => $bilpostcode, ONAMAEAPI_KEY_REQ_BILSP => $bilstate, ONAMAEAPI_KEY_REQ_BILCITY => $bilcity, ONAMAEAPI_KEY_REQ_BILSTREET1 => $biladdress1, ONAMAEAPI_KEY_REQ_BILSTREET2 => $biladdress2, ONAMAEAPI_KEY_REQ_BILPHONE => $bilphonenumber, ONAMAEAPI_KEY_REQ_BILFAX => $bilfaxnumber, ONAMAEAPI_KEY_REQ_BILEMAIL => $bilemailaddress);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (!isOnamaeSuccess($resultArray)) {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function gmointernet_GetEPPCode($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $onamaeReqParamArray = array(ONAMAEAPI_KEY_REQ_LOGINID => $username, ONAMAEAPI_KEY_REQ_LOGINPASSWORD => $password, ONAMAEAPI_KEY_REQ_ACTIONTYPE => ONAMAEAPI_ACTIONTYPE_DOMAININFO, ONAMAEAPI_KEY_REQ_DOMAINNAME => $sld . "." . $tld);
    $result = onamaeSendHttpRequest($testmode, $onamaeReqParamArray);
    $resultArray = onamaeReplaceResult($result);
    $values = array();
    if (isOnamaeSuccess($resultArray)) {
        $values["eppcode"] = $resultArray[ONAMAEAPI_KEY_RES_AUTHCODE];
    } else {
        $values["error"] = $resultArray[ONAMAEAPI_KEY_RES_RESULTMESSAGE];
    }
    return $values;
}
function onamaeSendHttpRequest($testMode, $reqParamArray, $nsParamArray)
{
    $url = !empty($testMode) ? "https://test-api.onamae.com/api/Execute.do" : "https://api.onamae.com/api/Execute.do";
    $method = "POST";
    $header = "Content-Type: application/x-www-form-urlencoded; charset=Shift_JIS\r\n";
    $content = replaceArrayToStr($reqParamArray);
    if (is_array($nsParamArray) && 0 < count($nsParamArray)) {
        foreach ($nsParamArray as $nsVal) {
            if (!empty($nsVal)) {
                $content .= "&" . ONAMAEAPI_KEY_REQ_NS . "=" . $nsVal;
            }
        }
    }
    $context = array("http" => array("method" => $method, "header" => $header, "content" => $content));
    $result = file_get_contents($url, false, stream_context_create($context));
    return $result;
}
function onamaeReplaceResult($result)
{
    $resultArray = array();
    if (!empty($result)) {
        $index = 0;
        $limit = strlen($result);
        while ($index < $limit) {
            $pos = strpos($result, "\n", $index);
            if ($pos === false) {
                break;
            }
            $resultStr = substr($result, $index, $pos - $index);
            if ($resultStr === false) {
                break;
            }
            $pos2 = strpos($resultStr, ":");
            if ($pos2 !== false) {
                $key = checkOnamaeDuplicationKey(substr($resultStr, 0, $pos2));
                $value = substr($resultStr, $pos2 + 1);
                if ($key !== false && $value !== false) {
                    $resultArray[$key] = $value;
                }
            }
            $index = $pos + 1;
        }
    }
    return $resultArray;
}
function checkOnamaeDuplicationKey($key)
{
    global $DUPLICATON_KEY_ARRAY;
    $resultKey = $key;
    if (array_key_exists($key, $DUPLICATON_KEY_ARRAY)) {
        $num = $DUPLICATON_KEY_ARRAY[$key];
        $resultKey = $key . $num;
        $DUPLICATON_KEY_ARRAY[$key] = ++$num;
    }
    return $resultKey;
}
function isOnamaeSuccess($resultArray, $responseCode)
{
    $resCode = empty($responseCode) ? ONAMAEAPI_RESCODE_SUCCESS : $responseCode;
    if (is_array($resultArray) && $resultArray[ONAMAEAPI_KEY_RES_RESULTCODE] == $resCode) {
        return true;
    }
    return false;
}
function isOnamaeTransferCheckResult($resultArray)
{
    if (is_array($resultArray) && $resultArray[ONAMAEAPI_KEY_RES_RESULTCODE] == ONAMAEAPI_RESCODE_SUCCESS && $resultArray[ONAMAEAPI_KEY_RES_CHECKRESULTCODE] == ONAMAEAPI_RESCODE_SUCCESS) {
        return true;
    }
    return false;
}
function replaceArrayToStr($paramArray)
{
    $result = "";
    foreach ($paramArray as $key => $val) {
        $result .= $key . "=" . $val . "&";
    }
    $result = rtrim($result, "&");
    return $result;
}

?>