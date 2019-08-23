<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain\Ssl;

class Downloader
{
    public function getCertificate($domain)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://" . $domain);
        curl_setopt($ch, CURLOPT_CERTINFO, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
        if (curl_errno($ch)) {
            $errorNumber = curl_errno($ch);
            if (in_array($errorNumber, array(CURLE_SSL_PEER_CERTIFICATE, CURLE_SSL_CACERT))) {
                throw new \WHMCS\Exception\Information("No SSL");
            }
        }
        curl_close($ch);
        if (isset($certInfo[0]) && is_array($certInfo[0])) {
            return new Certificate($certInfo[0]);
        }
        throw new \WHMCS\Exception("Unable to retrieve certificate data");
    }
}

?>