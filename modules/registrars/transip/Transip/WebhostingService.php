<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__FILE__) . "/ApiSettings.php";
require_once dirname(__FILE__) . "/WebhostingPackage.php";
require_once dirname(__FILE__) . "/WebHost.php";
require_once dirname(__FILE__) . "/Cronjob.php";
require_once dirname(__FILE__) . "/MailBox.php";
require_once dirname(__FILE__) . "/Db.php";
require_once dirname(__FILE__) . "/MailForward.php";
require_once dirname(__FILE__) . "/SubDomain.php";
class Transip_WebhostingService
{
    protected static $_soapClient = NULL;
    const SERVICE = "WebhostingService";
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
            $classMap = array("WebhostingPackage" => "Transip_WebhostingPackage", "WebHost" => "Transip_WebHost", "Cronjob" => "Transip_Cronjob", "MailBox" => "Transip_MailBox", "Db" => "Transip_Db", "MailForward" => "Transip_MailForward", "SubDomain" => "Transip_SubDomain");
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
    public static function getWebhostingDomainNames()
    {
        return self::_getSoapClient(array_merge(array(), array("__method" => "getWebhostingDomainNames")))->getWebhostingDomainNames();
    }
    public static function getAvailablePackages()
    {
        return self::_getSoapClient(array_merge(array(), array("__method" => "getAvailablePackages")))->getAvailablePackages();
    }
    public static function getInfo($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getInfo")))->getInfo($domainName);
    }
    public static function order($domainName, $webhostingPackage)
    {
        return self::_getSoapClient(array_merge(array($domainName, $webhostingPackage), array("__method" => "order")))->order($domainName, $webhostingPackage);
    }
    public static function getAvailableUpgrades($domainName)
    {
        return self::_getSoapClient(array_merge(array($domainName), array("__method" => "getAvailableUpgrades")))->getAvailableUpgrades($domainName);
    }
    public static function upgrade($domainName, $newWebhostingPackage)
    {
        return self::_getSoapClient(array_merge(array($domainName, $newWebhostingPackage), array("__method" => "upgrade")))->upgrade($domainName, $newWebhostingPackage);
    }
    public static function cancel($domainName, $endTime)
    {
        return self::_getSoapClient(array_merge(array($domainName, $endTime), array("__method" => "cancel")))->cancel($domainName, $endTime);
    }
    public static function setFtpPassword($domainName, $newPassword)
    {
        return self::_getSoapClient(array_merge(array($domainName, $newPassword), array("__method" => "setFtpPassword")))->setFtpPassword($domainName, $newPassword);
    }
    public static function createCronjob($domainName, $cronjob)
    {
        return self::_getSoapClient(array_merge(array($domainName, $cronjob), array("__method" => "createCronjob")))->createCronjob($domainName, $cronjob);
    }
    public static function deleteCronjob($domainName, $cronjob)
    {
        return self::_getSoapClient(array_merge(array($domainName, $cronjob), array("__method" => "deleteCronjob")))->deleteCronjob($domainName, $cronjob);
    }
    public static function createMailBox($domainName, $mailBox)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailBox), array("__method" => "createMailBox")))->createMailBox($domainName, $mailBox);
    }
    public static function modifyMailBox($domainName, $mailBox)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailBox), array("__method" => "modifyMailBox")))->modifyMailBox($domainName, $mailBox);
    }
    public static function setMailBoxPassword($domainName, $mailBox, $newPassword)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailBox, $newPassword), array("__method" => "setMailBoxPassword")))->setMailBoxPassword($domainName, $mailBox, $newPassword);
    }
    public static function deleteMailBox($domainName, $mailBox)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailBox), array("__method" => "deleteMailBox")))->deleteMailBox($domainName, $mailBox);
    }
    public static function createMailForward($domainName, $mailForward)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailForward), array("__method" => "createMailForward")))->createMailForward($domainName, $mailForward);
    }
    public static function modifyMailForward($domainName, $mailForward)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailForward), array("__method" => "modifyMailForward")))->modifyMailForward($domainName, $mailForward);
    }
    public static function deleteMailForward($domainName, $mailForward)
    {
        return self::_getSoapClient(array_merge(array($domainName, $mailForward), array("__method" => "deleteMailForward")))->deleteMailForward($domainName, $mailForward);
    }
    public static function createDatabase($domainName, $db)
    {
        return self::_getSoapClient(array_merge(array($domainName, $db), array("__method" => "createDatabase")))->createDatabase($domainName, $db);
    }
    public static function modifyDatabase($domainName, $db)
    {
        return self::_getSoapClient(array_merge(array($domainName, $db), array("__method" => "modifyDatabase")))->modifyDatabase($domainName, $db);
    }
    public static function setDatabasePassword($domainName, $db, $newPassword)
    {
        return self::_getSoapClient(array_merge(array($domainName, $db, $newPassword), array("__method" => "setDatabasePassword")))->setDatabasePassword($domainName, $db, $newPassword);
    }
    public static function deleteDatabase($domainName, $db)
    {
        return self::_getSoapClient(array_merge(array($domainName, $db), array("__method" => "deleteDatabase")))->deleteDatabase($domainName, $db);
    }
    public static function createSubdomain($domainName, $subDomain)
    {
        return self::_getSoapClient(array_merge(array($domainName, $subDomain), array("__method" => "createSubdomain")))->createSubdomain($domainName, $subDomain);
    }
    public static function deleteSubdomain($domainName, $subDomain)
    {
        return self::_getSoapClient(array_merge(array($domainName, $subDomain), array("__method" => "deleteSubdomain")))->deleteSubdomain($domainName, $subDomain);
    }
}

?>