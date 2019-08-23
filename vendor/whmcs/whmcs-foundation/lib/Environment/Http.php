<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment;

class Http
{
    public function siteIsConfiguredForSsl()
    {
        try {
            \App::getSystemSSLURLOrFail();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function siteHasVerifiedSslCert()
    {
        try {
            $url = \App::getSystemSSLURLOrFail();
            $whmcsHeaderVersion = \App::getVersion()->getMajor();
            $request = new \GuzzleHttp\Client(array("verify" => true));
            $request->get($url, array("headers" => array("User-Agent" => "WHMCS/" . $whmcsHeaderVersion)));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>