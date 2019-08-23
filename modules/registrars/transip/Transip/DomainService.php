<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__FILE__) . "/ApiSettings.php";
require_once dirname(__FILE__) . "/DomainCheckResult.php";
require_once dirname(__FILE__) . "/Domain.php";
require_once dirname(__FILE__) . "/Nameserver.php";
require_once dirname(__FILE__) . "/WhoisContact.php";
require_once dirname(__FILE__) . "/DnsEntry.php";
require_once dirname(__FILE__) . "/DomainBranding.php";
require_once dirname(__FILE__) . "/Tld.php";
require_once dirname(__FILE__) . "/DomainAction.php";
class Transip_DomainService
{
    protected static $_soapClient = NULL;
    const SERVICE = "DomainService";
    const AVAILABILITY_INYOURACCOUNT = "inyouraccount";
    const AVAILABILITY_UNAVAILABLE = "unavailable";
    const AVAILABILITY_NOTFREE = "notfree";
    const AVAILABILITY_FREE = "free";
    const CANCELLATIONTIME_END = "end";
    const CANCELLATIONTIME_IMMEDIATELY = "immediately";
    public static function _getSoapClient($parameters = array())
    {
        $endpoint = Transip_ApiSettings::$endpoint;
        if (self::$_soapClient === NULL) {
            $extensions = get_loaded_extensions();
            $errors = array();
            if (!class_exists("SoapClient") || !in_array("soap", $extensions)) {
                $errors[] = "The PHP SOAP extension doesn't seem to be installed. You need to install the PHP SOAP extension. (See: http://www.php.net/manual/en/book.soap.php)";
            }
            if (!in_array("openssl", $extensions)) {
                $errors[] = "The PHP OpenSSL extension doesn't seem to be installed. You need to install PHP with the OpenSSL extension. (See: http://www.php.net/manual/en/book.openssl.php)";
            }
            if (!empty($errors)) {
                exit("<p>" . implode("</p>\n<p>", $errors) . "</p>");
            }
            $classMap = array("DomainCheckResult" => "Transip_DomainCheckResult", "Domain" => "Transip_Domain", "Nameserver" => "Transip_Nameserver", "WhoisContact" => "Transip_WhoisContact", "DnsEntry" => "Transip_DnsEntry", "DomainBranding" => "Transip_DomainBranding", "Tld" => "Transip_Tld", "DomainAction" => "Transip_DomainAction");
            $options = array("classmap" => $classMap, "encoding" => "utf-8", "features" => SOAP_SINGLE_ELEMENT_ARRAYS, "trace" => false);
            $wsdlUri = "https://" . $endpoint . "/wsdl/?service=" . self::SERVICE;
            try {
                self::$_soapClient = new SoapClient($wsdlUri, $options);
            } catch (SoapFault $sf) {
                throw new Exception("Unable to connect to endpoint '" . $endpoint . "'");
            }
            self::$_soapClient->__setCookie("login", Transip_ApiSettings::$login);
            self::$_soapClient->__setCookie("mode", Transip_ApiSettings::$mode);
        }
        $timestamp = time();
        $nonce = uniqid("", true);
        self::$_soapClient->__setCookie("timestamp", $timestamp);
        self::$_soapClient->__setCookie("nonce", $nonce);
        self::$_soapClient->__setCookie("signature", self::_urlencode(self::_sign(array_merge($parameters, array("__service" => self::SERVICE, "__hostname" => $endpoint, "__timestamp" => $timestamp, "__nonce" => $nonce)))));
        return self::$_soapClient;
    }
    protected static function _sign($parameters)
    {
        $matches = array();
        if (!preg_match("/-----BEGIN(?: RSA|) PRIVATE KEY-----(.*)-----END(?: RSA|) PRIVATE KEY-----/si", Transip_ApiSettings::$privateKey, $matches)) {
            exit("<p>Could not find your private key, please supply your private key in the ApiSettings file. You can request a new private key in your TransIP Controlpanel.</p>");
        }
        $key = $matches[1];
        $key = preg_replace("/\\s*/s", "", $key);
        $key = chunk_split($key, 64, "\n");
        $key = "-----BEGIN PRIVATE KEY-----\n" . $key . "-----END PRIVATE KEY-----";
        $digest = self::_sha512Asn1(self::_encodeParameters($parameters));
        if (!@openssl_private_encrypt($digest, $signature, $key)) {
            exit("<p>Could not sign your request, please supply your private key in the ApiSettings file. You can request a new private key in your TransIP Controlpanel.</p>");
        }
        return base64_encode($signature);
    }
    protected static function _sha512Asn1($data)
    {
        $digest = hash("sha512", $data, true);
        $asn1 = chr(48) . chr(81);
        $asn1 .= chr(48) . chr(13);
        $asn1 .= chr(6) . chr(9);
        $asn1 .= chr(96) . chr(134) . chr(72) . chr(1) . chr(101);
        $asn1 .= chr(3) . chr(4);
        $asn1 .= chr(2) . chr(3);
        $asn1 .= chr(5) . chr(0);
        $asn1 .= chr(4) . chr(64);
        $asn1 .= $digest;
        return $asn1;
    }
    protected static function _encodeParameters($parameters, $keyPrefix = NULL)
    {
        if (!is_array($parameters) && !is_object($parameters)) {
            return self::_urlencode($parameters);
        }
        $encodedData = array();
        foreach ($parameters as $key => $value) {
            $encodedKey = is_null($keyPrefix) ? self::_urlencode($key) : $keyPrefix . "[" . self::_urlencode($key) . "]";
            if (is_array($value) || is_object($value)) {
                $encodedData[] = self::_encodeParameters($value, $encodedKey);
            } else {
                $encodedData[] = $encodedKey . "=" . self::_urlencode($value);
            }
        }
        return implode("&", $encodedData);
    }
    protected static function _urlencode($string)
    {
        $string = rawurlencode($string);
        return str_replace("%7E", "~", $string);
    }
    public static function batchCheckAvailability($domainNames)
    {
        return self::_getSoapClient(array_merge(array($domainNames), array("__method" => "batchCheckAvailability")))->batchCheckAvailability($domainNames);
    }
    public static function checkAvailability($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "checkAvailability")))->checkAvailability($domainName);
    }
    public static function getWhois($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getWhois")))->getWhois($domainName);
    }
    public static function getDomainNames()
    {
        return self::_getSoapClient(array_merge(array(), array("__method" => "getDomainNames")))->getDomainNames();
    }
    public static function getInfo($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getInfo")))->getInfo($domainName);
    }
    public static function batchGetInfo($domainNames)
    {
        return self::_getSoapClient(array_merge(array($domainNames), array("__method" => "batchGetInfo")))->batchGetInfo($domainNames);
    }
    public static function getAuthCode($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getAuthCode")))->getAuthCode($domainName);
    }
    public static function getIsLocked($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getIsLocked")))->getIsLocked($domainName);
    }
    public static function register($domain)
    {
        return self::_getSoapClient(array_merge(array($domain), array("__method" => "register")))->register($domain);
    }
    public static function cancel($domainName, $endTime)
    {
        return self::_getSoapClient(array_merge(array($domainName, $endTime), array("__method" => "cancel")))->cancel($domainName, $endTime);
    }
    public static function transferWithOwnerChange($domain, $authCode)
    {
        return self::_getSoapClient(array_merge(array($domain, $authCode), array("__method" => "transferWithOwnerChange")))->transferWithOwnerChange($domain, $authCode);
    }
    public static function transferWithoutOwnerChange($domain, $authCode)
    {
        return self::_getSoapClient(array_merge(array($domain, $authCode), array("__method" => "transferWithoutOwnerChange")))->transferWithoutOwnerChange($domain, $authCode);
    }
    public static function setNameservers($domainName, $nameservers)
    {
        return self::_getSoapClient(array_merge(array($domainName, $nameservers), array("__method" => "setNameservers")))->setNameservers($domainName, $nameservers);
    }
    public static function setLock($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "setLock")))->setLock($domainName);
    }
    public static function unsetLock($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "unsetLock")))->unsetLock($domainName);
    }
    public static function setDnsEntries($domainName, $dnsEntries)
    {
        return self::_getSoapClient(array_merge(array($domainName, $dnsEntries), array("__method" => "setDnsEntries")))->setDnsEntries($domainName, $dnsEntries);
    }
    public static function setOwner($domainName, $registrantWhoisContact)
    {
        return self::_getSoapClient(array_merge(array($domainName, $registrantWhoisContact), array("__method" => "setOwner")))->setOwner($domainName, $registrantWhoisContact);
    }
    public static function setContacts($domainName, $contacts)
    {
        return self::_getSoapClient(array_merge(array($domainName, $contacts), array("__method" => "setContacts")))->setContacts($domainName, $contacts);
    }
    public static function getAllTldInfos()
    {
        return self::_getSoapClient(array_merge(array(), array("__method" => "getAllTldInfos")))->getAllTldInfos();
    }
    public static function getTldInfo($tldName)
    {
        return self::_getSoapClient(array_merge(array($tldName), array("__method" => "getTldInfo")))->getTldInfo($tldName);
    }
    public static function getCurrentDomainAction($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getCurrentDomainAction")))->getCurrentDomainAction($domainName);
    }
    public static function retryCurrentDomainActionWithNewData($domain)
    {
        return self::_getSoapClient(array_merge(array($domain), array("__method" => "retryCurrentDomainActionWithNewData")))->retryCurrentDomainActionWithNewData($domain);
    }
    public static function retryTransferWithDifferentAuthCode($domain, $newAuthCode)
    {
        return self::_getSoapClient(array_merge(array($domain, $newAuthCode), array("__method" => "retryTransferWithDifferentAuthCode")))->retryTransferWithDifferentAuthCode($domain, $newAuthCode);
    }
    public static function cancelDomainAction($domain)
    {
        return self::_getSoapClient(array_merge(array($domain), array("__method" => "cancelDomainAction")))->cancelDomainAction($domain);
    }
}

?>