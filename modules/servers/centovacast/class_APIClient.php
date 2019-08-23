<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

abstract class CCBaseAPIClient
{
    public $debug = false;
    public $encoding = "UTF-8";
    public $debugconsole = false;
    public $raw_response = "";
    public $raw_request = "";
    public $remote_version = false;
    public $message = "";
    public $data = NULL;
    public $success = false;
    public $error = "";
    protected $classname = "";
    protected $methodname = "";
    protected $ccurl = "";
    protected $http_request_error = "";
    public function call()
    {
        $args = func_get_args();
        $name = array_shift($args);
        $this->_call($name, $args);
    }
    public function __call($name, $args)
    {
        $this->_call($name, $args);
    }
    protected function build_request_packet($methodname, $payload)
    {
        return sprintf("<?xml version=\"1.0\" encoding=\"" . $this->encoding . "\"?" . ">" . "<centovacast>" . "<request class=\"%s\" method=\"%s\"%s>" . "%s" . "</request>" . "</centovacast>", htmlentities($this->classname), htmlentities($methodname), $this->debug ? " debug=\"enabled\"" : "" . $this->debugconsole ? " debugconsole=\"" . htmlentities($this->debugconsole) . "\"" : "", $payload);
    }
    protected function http_request($url, $postdata)
    {
        if (function_exists("stream_context_create") && function_exists("stream_get_meta_data")) {
            return $this->http_request_php($url, $postdata);
        }
        if (class_exists("HTTPRetriever")) {
            return $this->http_request_httpretriever($url, $postdata);
        }
        return $this->set_error("Neither HTTPRetriever nor PHP streams support is available");
    }
    public function handle_http_error($errno, $errstr, $errfile, $errline)
    {
        $this->http_request_error = $errstr;
        return true;
    }
    protected function http_request_php($url, $postdata)
    {
        $ctx = stream_context_create(array("http" => array("method" => "POST", "user_agent" => "Centova Cast PHP API Client", "header" => "Connection: close\r\nContent-Length: " . strlen($postdata) . "\r\n", "max_redirects" => "0", "ignore_errors" => "1", "content" => $postdata)));
        $this->http_request_error = "";
        set_error_handler(array($this, "handle_http_error"));
        $fp = @fopen($url, "rb", false, $ctx);
        restore_error_handler();
        if (!is_resource($fp)) {
            $error = "Socket error accessing " . $url;
            if (!empty($this->http_request_error)) {
                $error .= ": " . $this->http_request_error;
            }
            return $this->set_error($error);
        }
        $metadata = stream_get_meta_data($fp);
        $response = stream_get_contents($fp);
        fclose($fp);
        $headers = $metadata["wrapper_data"];
        if (isset($headers["headers"])) {
            $headers = $headers["headers"];
        }
        do {
            list(, $code, $message) = explode(" ", array_shift($headers), 3);
        } while ($code == "100");
        if ($code != "200") {
            return $this->set_error("Received HTTP response code " . $code . " (" . $message . "); " . print_r($metadata, true) . "; " . $response);
        }
        return $response;
    }
    protected function http_request_httpretriever($url, $postdata)
    {
        $http = new HTTPRetriever();
        $http->headers["User-Agent"] = "Centova Cast PHP API Client";
        if (!$http->post($url, $postdata)) {
            $this->set_error("Error contacting server: " . $http->get_error());
            return false;
        }
        return $http->raw_response;
    }
    protected function cc_initialize($ccurl)
    {
        $this->ccurl = $ccurl;
    }
    protected abstract function build_argument_payload($functionargs);
    protected function build_argument_xml($args)
    {
        $payload = "";
        foreach ($args as $name => $value) {
            if (is_array($value)) {
                $value = $this->build_argument_xml($value);
            } else {
                $value = htmlentities($value);
            }
            $payload .= sprintf("<%s>%s</%s>", $name, $value, $name);
        }
        return $payload;
    }
    protected function parse_data($data)
    {
        if (!preg_match("/<data[^\\>]*?>([\\s\\S]+)<\\/data>/i", $data, $matches)) {
            return false;
        }
        $rowxml = $matches[1];
        $xml = new CCAPIXML();
        return $xml->parse($rowxml);
    }
    protected function parse_response_packet($packet)
    {
        $this->raw_response = $packet;
        if (!preg_match("/<centovacast([^\\>]+)>([\\s\\S]+)<\\/centovacast>/i", $packet, $matches)) {
            return $this->set_error("Invalid response packet received from API server");
        }
        $cctags = $matches[1];
        if (preg_match("/version=\"([^\\\"]+)\"/i", $cctags, $tagmatches)) {
            $this->remote_version = $tagmatches[1];
        } else {
            $this->remote_version = false;
        }
        $payload = $matches[2];
        if (!preg_match("/<response.*?type\\s*=\\s*\"([^\"]+)\"[^\\>]*>([\\s\\S]+)<\\/response>/i", $payload, $matches)) {
            return $this->set_error("Empty or unrecognized response packet received from API server");
        }
        list(, $type, $data) = $matches;
        if (preg_match("/<message[^\\>]*>([\\s\\S]+)<\\/message>/i", $data, $matches)) {
            $this->message = CCAPIXML::xml_entity_decode($matches[1]);
        } else {
            $this->message = "(Message not provided by API server)";
        }
        switch (strtolower($type)) {
            case "error":
                return $this->set_error($this->message);
            case "success":
                $this->data = $this->parse_data($data);
                $this->success = true;
                return true;
        }
        return $this->set_error("Invalid response type received from API server");
    }
    protected function api_request($packet)
    {
        $url = $this->ccurl;
        $apiscript = "api.php";
        if (substr($url, 0 - strlen($apiscript) - 1) != "/" . $apiscript) {
            if (substr($url, -1) != "/") {
                $url .= "/";
            }
            $url .= $apiscript;
        }
        $this->success = false;
        $postdata = $packet;
        if (($this->raw_response = $this->http_request($url, $postdata)) === false) {
            return NULL;
        }
        $this->parse_response_packet($this->raw_response);
        $this->raw_request = $packet;
    }
    protected function set_error($msg)
    {
        $this->success = false;
        $this->error = $msg;
        return false;
    }
    protected function _call($name, $args)
    {
        $this->methodname = $name;
        $payload = $this->build_argument_payload($args);
        $packet = $this->build_request_packet($name, $payload);
        $this->api_request($packet);
    }
}
class CCServerAPIClient extends CCBaseAPIClient
{
    protected $classname = "server";
    public function __construct($ccurl)
    {
        parent::cc_initialize($ccurl);
    }
    protected function build_argument_payload($functionargs)
    {
        if (count($functionargs) < 3) {
            trigger_error(sprintf("Function %s requires a minimum of 3 arguments, %d given", $this->methodname, count($functionargs)), 512);
        }
        list($username, $password, $arguments) = $functionargs;
        if (!is_array($arguments)) {
            $arguments = array();
        }
        $arguments = array_merge(array("username" => $username, "password" => $password), $arguments);
        return $this->build_argument_xml($arguments);
    }
}
class CCSystemAPIClient extends CCBaseAPIClient
{
    protected $classname = "system";
    public function __construct($ccurl)
    {
        parent::cc_initialize($ccurl);
    }
    public function build_argument_payload($functionargs)
    {
        if (count($functionargs) < 2) {
            trigger_error(sprintf("Function %s requires a minimum of 2 arguments, %d given", $this->methodname, count($functionargs)), 512);
        }
        list($adminpassword, $arguments) = $functionargs;
        if (!is_array($arguments)) {
            $arguments = array();
        }
        $arguments = array_merge(array("password" => $adminpassword), $arguments);
        return $this->build_argument_xml($arguments);
    }
}
class CCAPIXML
{
    public function parse($xml)
    {
        $multi = array();
        $rows = array();
        $tag = $this->get_first_tag($xml);
        if ($tag === false) {
            return self::xml_entity_decode(trim($xml));
        }
        while ($tag !== false) {
            list($tagoffset, $taglength, $tagname, $tagattr, $tagnocontent) = $tag;
            if ($tagnocontent) {
                $tagcontents = "";
                $tagend = $tagoffset + $taglength;
            } else {
                $xmlcontents = $this->get_xml_tag_contents($xml, $tag);
                if ($xmlcontents === false) {
                    return false;
                }
                list($tagend, $tagcontents) = $xmlcontents;
            }
            if (isset($rows[$tagname]) && !$multi[$tagname]) {
                $rows[$tagname] = array($rows[$tagname]);
                $multi[$tagname] = true;
            }
            $row = $this->parse($tagcontents);
            if ($multi[$tagname]) {
                $rows[$tagname][] = $row;
            } else {
                $rows[$tagname] = $row;
            }
            $xml = substr($xml, $tagend);
            $tag = $this->get_first_tag($xml);
        }
        return $rows;
    }
    public static function xmlentities($string)
    {
        return str_replace(array("&", "\"", "'", "<", ">"), array("&amp;", "&quot;", "&apos;", "&lt;", "&gt;"), $string);
    }
    public static function xml_entity_decode($string)
    {
        return str_replace(array("&amp;", "&quot;", "&apos;", "&lt;", "&gt;"), array("&", "\"", "'", "<", ">"), $string);
    }
    private function get_first_tag($xml)
    {
        if (preg_match("/<\\s*([a-zA-Z0-9_:\\.-]+)([^\\>]*?)(\\/)?\\s*>/", $xml, $matches, PREG_OFFSET_CAPTURE)) {
            $tagoffset = $matches[0][1];
            $taglength = strlen($matches[0][0]);
            $tagname = $matches[1][0];
            $tagattr = $matches[2][0];
            $tagnocontent = $matches[3][0] == "/" || $matches[3][0] == "?";
            return array($tagoffset, $taglength, $tagname, $tagattr, $tagnocontent);
        }
        return false;
    }
    private function get_xml_tag_contents($xml, $tag)
    {
        $tagoffset = $taglength = 0;
        $startoffset = $beginoffset = $tag[0] + $tag[1];
        if ($tag[4]) {
            return array($startoffset, "");
        }
        $nest = 0;
        $iterations = 0;
        $regex = "/<\\s*(\\/)?\\s*" . preg_quote($tag[2], "/") . "(\\s+[^\\>]*)?>/i";
        while (true) {
            if (100 < ++$iterations) {
                return false;
            }
            if (preg_match($regex, $xml, $matches, PREG_OFFSET_CAPTURE, $startoffset)) {
                $tagoffset = $matches[0][1];
                $taglength = strlen($matches[0][0]);
                $startoffset = $tagoffset + $taglength;
                if ($matches[1][0] != "/") {
                    $nest++;
                } else {
                    $nest--;
                    if ($nest < 0) {
                        break;
                    }
                }
            } else {
                break;
            }
        }
        if (0 <= $nest) {
            return false;
        }
        $endoffset = $tagoffset;
        return array($endoffset + $taglength, substr($xml, $beginoffset, $endoffset - $beginoffset));
    }
}

?>