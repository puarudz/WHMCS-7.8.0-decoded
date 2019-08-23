<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\AcceptJs;

class AcceptJsHttpClient extends \net\authorize\util\HttpClient
{
    private $logger = NULL;
    protected $verifyHost = 2;
    protected $verifyPeer = true;
    public function __construct()
    {
        parent::__construct();
        $this->logger = \net\authorize\util\LogFactory::getLog(get_class($this));
    }
    public function setVerifyHost($value = 2)
    {
        $this->verifyHost = $value;
    }
    public function setVerifyPeer($value = true)
    {
        $this->verifyPeer = $value;
    }
    public function _sendRequest($xmlRequest)
    {
        $xmlResponse = "";
        $curl_error = "";
        $post_url = $this->_getPostUrl();
        $curl_request = curl_init($post_url);
        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $xmlRequest);
        curl_setopt($curl_request, CURLOPT_HEADER, 0);
        curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, $this->verifyHost);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        $this->logger->info(sprintf(" Url: %s", $post_url));
        $this->logger->info(sprintf("Request to AnetApi: \n%s", $xmlRequest));
        if ($this->VERIFY_PEER) {
            curl_setopt($curl_request, CURLOPT_CAINFO, ROOTDIR . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "authorizenet" . DIRECTORY_SEPARATOR . "authorizenet" . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "ssl" . DIRECTORY_SEPARATOR . "cert.pem");
            if (preg_match("/xml/", $post_url)) {
                curl_setopt($curl_request, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
                $this->logger->info("Sending 'XML' Request type");
            }
            try {
                $this->logger->info("Sending http request via Curl");
                $xmlResponse = curl_exec($curl_request);
                $curl_error = curl_error($curl_request);
                $this->logger->info("Response from AnetApi: " . $xmlResponse);
                if ($curl_error) {
                    $curlErrorNumber = curl_errno($curl_request);
                    $xmlResponse = "<?xml version=\"1.0\" encoding=\"utf-8\"?> \n<authenticateTestResponse xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">\n  <messages>\n    <resultCode>\n      Error\n    </resultCode>\n    <message>\n      <code>\n        CURL_ERROR_" . $curlErrorNumber . "\n      </code>\n      <text>\n        " . $curl_error . "\n      </text>\n    </message>\n  </messages>\n</authenticateTestResponse>";
                }
            } catch (\Exception $ex) {
                $errorMessage = sprintf("\n%s:Error making http request via curl: " . "Code:'%s', Message:'%s', Trace:'%s', File:'%s':'%s'", $this->now(), $ex->getCode(), $ex->getMessage(), $ex->getTraceAsString(), $ex->getFile(), $ex->getLine());
                $this->logger->error($errorMessage);
            }
            if ($this->logger && $this->logger->getLogFile() && $curl_error) {
                $this->logger->error("CURL ERROR: " . $curl_error);
            }
            curl_close($curl_request);
            return $xmlResponse;
        }
        $this->logger->error("Invalid SSL option for the request");
        return false;
    }
    private function now()
    {
        return date(DATE_RFC2822);
    }
}

?>