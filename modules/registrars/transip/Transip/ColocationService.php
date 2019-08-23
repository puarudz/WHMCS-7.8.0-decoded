<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__FILE__) . "/ApiSettings.php";
require_once dirname(__FILE__) . "/DataCenterVisitor.php";
class Transip_ColocationService
{
    protected static $_soapClient = NULL;
    const SERVICE = "ColocationService";
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
            $classMap = array("DataCenterVisitor" => "Transip_DataCenterVisitor");
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
    public static function requestAccess($when, $duration, $visitors, $phoneNumber)
    {
        return self::_getSoapClient(array_merge(array($when, $duration, $visitors, $phoneNumber), array("__method" => "requestAccess")))->requestAccess($when, $duration, $visitors, $phoneNumber);
    }
    public static function requestRemoteHands($coloName, $contactName, $phoneNumber, $expectedDuration, $instructions)
    {
        return self::_getSoapClient(array_merge(array($coloName, $contactName, $phoneNumber, $expectedDuration, $instructions), array("__method" => "requestRemoteHands")))->requestRemoteHands($coloName, $contactName, $phoneNumber, $expectedDuration, $instructions);
    }
    public static function getColoNames()
    {
        return self::_getSoapClient(array_merge(array(), array("__method" => "getColoNames")))->getColoNames();
    }
    public static function getIpAddresses($coloName)
    {
        return self::_getSoapClient(array_merge(array($coloName), array("__method" => "getIpAddresses")))->getIpAddresses($coloName);
    }
    public static function getIpRanges($coloName)
    {
        return self::_getSoapClient(array_merge(array($coloName), array("__method" => "getIpRanges")))->getIpRanges($coloName);
    }
    public static function createIpAddress($ipAddress, $reverseDns)
    {
        return self::_getSoapClient(array_merge(array($ipAddress, $reverseDns), array("__method" => "createIpAddress")))->createIpAddress($ipAddress, $reverseDns);
    }
    public static function deleteIpAddress($ipAddress)
    {
        return self::_getSoapClient(array_merge(array($ipAddress), array("__method" => "deleteIpAddress")))->deleteIpAddress($ipAddress);
    }
    public static function getReverseDns($ipAddress)
    {
        return self::_getSoapClient(array_merge(array($ipAddress), array("__method" => "getReverseDns")))->getReverseDns($ipAddress);
    }
    public static function setReverseDns($ipAddress, $reverseDns)
    {
        return self::_getSoapClient(array_merge(array($ipAddress, $reverseDns), array("__method" => "setReverseDns")))->setReverseDns($ipAddress, $reverseDns);
    }
}

?>