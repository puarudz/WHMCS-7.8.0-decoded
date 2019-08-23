<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class WHMCS_Nominet
{
    private $params = NULL;
    private $socket = NULL;
    private $response = "";
    private $responsearray = "";
    private $errmsg = "";
    private $resultcode = 0;
    public function __construct()
    {
    }
    public static function init($params)
    {
        $obj = new self();
        $obj->params = $params;
        return $obj;
    }
    public function getLastError()
    {
        return $this->errmsg ? $this->errmsg : "An unknown error occurred";
    }
    public function setError($errmsg)
    {
        $this->errmsg = $errmsg;
    }
    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : "";
    }
    public function getDomain()
    {
        return $this->getParam("sld") . "." . $this->getParam("tld");
    }
    public function connect()
    {
        if ($this->getParam("TestMode")) {
            $host = "testbed-epp.nominet.org.uk";
        } else {
            $host = "epp.nominet.org.uk";
        }
        $port = 700;
        $timeout = 10;
        $target = sprintf("tls://%s:%s", $host, $port);
        $context = stream_context_create(array("ssl" => array("crypto_method" => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)));
        if (!($this->socket = stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context))) {
            $this->setError("Connecting to " . $target . ". <p>The error message was '" . $errstr . "' (code " . $errno . ")");
        } else {
            if (@feof($this->socket)) {
                $this->setError("Connection closed by remote server");
            } else {
                $hdr = @fread($this->socket, 4);
                if (empty($hdr) && feof($this->socket)) {
                    $this->setError("Connection closed by remote server");
                } else {
                    if (empty($hdr)) {
                        $this->setError("Reading from server: " . $php_errormsg);
                    } else {
                        $unpacked = unpack("N", $hdr);
                        $length = $unpacked[1];
                        if ($length < 5) {
                            $this->setError("Got a bad frame header length from server");
                        } else {
                            $answer = fread($this->socket, $length - 4);
                            $this->processResponse($answer);
                            $this->logCall("connect", $target . ":" . $port);
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
    private function processResponse($response)
    {
        $this->response = $response;
        $this->responsearray = XMLtoArray($response);
        if (preg_match("%<domain:ns>(.+)</domain:ns>%s", $response, $matches)) {
            $ns = trim($matches[1]);
            $ns = preg_replace("%</?domain:hostObj>%", " ", $ns);
            $ns = preg_split("/\\s+|\n/", $ns, NULL, PREG_SPLIT_NO_EMPTY);
            foreach ($ns as $k => $value) {
                $ns[$k] = chop($value, ".");
            }
            if (0 < count($ns)) {
                $this->responsearray["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:NS"]["DOMAIN:HOSTOBJ"] = $ns;
            }
        }
        return true;
    }
    public function getResponse()
    {
        return $this->response;
    }
    public function getResponseArray()
    {
        return $this->responsearray;
    }
    public function getResultCode()
    {
        $response_code_pattern = "<result code=\"(\\d+)\">";
        $matches = array();
        preg_match($response_code_pattern, $this->response, $matches);
        $resultcode = isset($matches[1]) ? (int) $matches[1] : 0;
        return $resultcode;
    }
    public function isErrorCode()
    {
        $resultcode = $this->getResultCode();
        return $resultcode < 2000 ? false : true;
    }
    public function getErrorDesc()
    {
        $results = $this->getResponseArray();
        $results = $results["EPP"]["RESPONSE"];
        if (isset($results["RESULT"]["EXTVALUE"]["REASON"])) {
            return $results["RESULT"]["EXTVALUE"]["REASON"];
        }
        if (isset($results["RESULT"]["MSG"])) {
            return $results["RESULT"]["MSG"];
        }
    }
    public function call($xml)
    {
        $command = XMLtoArray($xml);
        $command = array_keys($command["COMMAND"]);
        $command = $command[0];
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">" . $xml;
        fwrite($this->socket, pack("N", strlen($xml) + 4) . $xml);
        if (@feof($this->socket)) {
            $this->setError("Connection closed by remote server");
        } else {
            $hdr = @fread($this->socket, 4);
            if (empty($hdr) && feof($this->socket)) {
                $this->setError("Connection closed by remote server");
            } else {
                if (empty($hdr)) {
                    $this->setError("Error: Reading from server: " . $php_errormsg);
                } else {
                    $unpacked = unpack("N", $hdr);
                    $length = $unpacked[1];
                    if ($length < 5) {
                        $this->setError("Got a bad frame header length from server");
                    } else {
                        $answer = fread($this->socket, $length - 4);
                        $this->processResponse($answer);
                        $this->logCall($command, $xml);
                        return true;
                    }
                }
            }
        }
        return false;
    }
    private function logCall($action, $request)
    {
        if (function_exists("logModuleCall")) {
            logModuleCall("nominet", $action, $request, $this->getResponse(), $this->getResponseArray(), array($this->getParam("Username"), $this->getParam("Password")));
        }
        return true;
    }
    public function login()
    {
        $xml = "  <command>\n                <login>\n                  <clID>" . $this->getParam("Username") . "</clID>\n                  <pw>" . $this->getParam("Password") . "</pw>\n                  <options>\n                    <version>1.0</version>\n                    <lang>en</lang>\n                  </options>\n                  <svcs>\n\t\t    <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>\n\t\t    <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>\n\t\t    <objURI>urn:ietf:params:xml:ns:host-1.0</objURI>\n\t\t    ";
        $xml .= "<svcExtension>\n\t\t      <extURI>http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0</extURI>\n\t\t      <extURI>http://www.nominet.org.uk/epp/xml/domain-nom-ext-1.0</extURI>\n\t\t      <extURI>http://www.nominet.org.uk/epp/xml/std-release-1.0</extURI>\n\t\t    </svcExtension>\n                  </svcs>\n                </login>\n                <clTRID>ABC-12345</clTRID>\n              </command>\n            </epp>";
        $res = $this->call($xml);
        if ($res) {
            if ($this->isErrorCode()) {
                $this->setError("Login Failed. Please check details in Setup > Domain Registrars > Nominet");
            } else {
                return true;
            }
        }
        return false;
    }
    public function connectAndLogin()
    {
        if ($this->connect() && $this->login()) {
            return true;
        }
        return false;
    }
    public function escapeParam($param)
    {
        return htmlspecialchars($param);
    }
}

?>