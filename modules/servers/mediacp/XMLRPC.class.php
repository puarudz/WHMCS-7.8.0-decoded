<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class IXR_Value
{
    public $data = NULL;
    public $type = NULL;
    public function IXR_Value($data, $type = false)
    {
        $this->data = $data;
        if (!$type) {
            $type = $this->calculateType();
        }
        $this->type = $type;
        if ($type == "struct") {
            foreach ($this->data as $key => $value) {
                $this->data[$key] = new IXR_Value($value);
            }
        }
        if ($type == "array") {
            $i = 0;
            for ($j = count($this->data); $i < $j; $i++) {
                $this->data[$i] = new IXR_Value($this->data[$i]);
            }
        }
    }
    public function calculateType()
    {
        if ($this->data === true || $this->data === false) {
            return "boolean";
        }
        if (is_integer($this->data)) {
            return "int";
        }
        if (is_double($this->data)) {
            return "double";
        }
        if (is_object($this->data) && is_a($this->data, "IXR_Date")) {
            return "date";
        }
        if (is_object($this->data) && is_a($this->data, "IXR_Base64")) {
            return "base64";
        }
        if (is_object($this->data)) {
            $this->data = get_object_vars($this->data);
            return "struct";
        }
        if (!is_array($this->data)) {
            return "string";
        }
        if ($this->isStruct($this->data)) {
            return "struct";
        }
        return "array";
    }
    public function getXml()
    {
        switch ($this->type) {
            case "boolean":
                return "<boolean>" . ($this->data ? "1" : "0") . "</boolean>";
            case "int":
                return "<int>" . $this->data . "</int>";
            case "double":
                return "<double>" . $this->data . "</double>";
            case "string":
                return "<string>" . htmlspecialchars($this->data) . "</string>";
            case "array":
                $return = "<array><data>" . "\n";
                foreach ($this->data as $item) {
                    $return .= "  <value>" . $item->getXml() . "</value>\n";
                }
                $return .= "</data></array>";
                return $return;
            case "struct":
                $return = "<struct>" . "\n";
                foreach ($this->data as $name => $value) {
                    $return .= "  <member><name>" . $name . "</name><value>";
                    $return .= $value->getXml() . "</value></member>\n";
                }
                $return .= "</struct>";
                return $return;
            case "date":
            case "base64":
                return $this->data->getXml();
        }
        return false;
    }
    public function isStruct($array)
    {
        $expected = 0;
        foreach ($array as $key => $value) {
            if ((string) $key != (string) $expected) {
                return true;
            }
            $expected++;
        }
        return false;
    }
}
class IXR_Message
{
    public $message = NULL;
    public $messageType = NULL;
    public $faultCode = NULL;
    public $faultString = NULL;
    public $methodName = NULL;
    public $params = NULL;
    public $_arraystructs = array();
    public $_arraystructstypes = array();
    public $_currentStructName = array();
    public $_param = NULL;
    public $_value = NULL;
    public $_currentTag = NULL;
    public $_currentTagContents = NULL;
    public $_parser = NULL;
    public function IXR_Message($message)
    {
        $this->message = $message;
    }
    public function parse()
    {
        $this->message = preg_replace("/<\\?xml(.*)?\\?" . ">/", "", $this->message);
        if (trim($this->message) == "") {
            return false;
        }
        $this->_parser = xml_parser_create();
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->_parser, "cdata");
        if (!xml_parse($this->_parser, $this->message)) {
            return false;
        }
        xml_parser_free($this->_parser);
        if ($this->messageType == "fault") {
            $this->faultCode = $this->params[0]["faultCode"];
            $this->faultString = $this->params[0]["faultString"];
        }
        return true;
    }
    public function tag_open($parser, $tag, $attr)
    {
        $this->currentTag = $tag;
        switch ($tag) {
            case "methodCall":
            case "methodResponse":
            case "fault":
                $this->messageType = $tag;
                break;
            case "data":
                $this->_arraystructstypes[] = "array";
                $this->_arraystructs[] = array();
                break;
            case "struct":
                $this->_arraystructstypes[] = "struct";
                $this->_arraystructs[] = array();
                break;
        }
    }
    public function cdata($parser, $cdata)
    {
        $this->_currentTagContents .= $cdata;
    }
    public function tag_close($parser, $tag)
    {
        $valueFlag = false;
        switch ($tag) {
            case "int":
            case "i4":
                $value = (int) trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "double":
                $value = (double) trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "string":
                $value = (string) trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "dateTime.iso8601":
                $value = new IXR_Date(trim($this->_currentTagContents));
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "value":
                if (trim($this->_currentTagContents) != "") {
                    $value = (string) $this->_currentTagContents;
                    $this->_currentTagContents = "";
                    $valueFlag = true;
                }
                break;
            case "boolean":
                $value = (bool) trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "base64":
                $value = base64_decode($this->_currentTagContents);
                $this->_currentTagContents = "";
                $valueFlag = true;
                break;
            case "data":
            case "struct":
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;
                break;
            case "member":
                array_pop($this->_currentStructName);
                break;
            case "name":
                $this->_currentStructName[] = trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                break;
            case "methodName":
                $this->methodName = trim($this->_currentTagContents);
                $this->_currentTagContents = "";
                break;
        }
        if ($valueFlag) {
            if (0 < count($this->_arraystructs)) {
                if ($this->_arraystructstypes[count($this->_arraystructstypes) - 1] == "struct") {
                    $this->_arraystructs[count($this->_arraystructs) - 1][$this->_currentStructName[count($this->_currentStructName) - 1]] = $value;
                } else {
                    $this->_arraystructs[count($this->_arraystructs) - 1][] = $value;
                }
            } else {
                $this->params[] = $value;
            }
        }
    }
}
class IXR_Server
{
    public $data = NULL;
    public $callbacks = array();
    public $message = NULL;
    public $capabilities = NULL;
    public function IXR_Server($callbacks = false, $data = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }
    public function serve($data = false)
    {
        if (!$data) {
            global $HTTP_RAW_POST_DATA;
            if (!$HTTP_RAW_POST_DATA) {
                exit("XML-RPC server accepts POST requests only.");
            }
            $data = $HTTP_RAW_POST_DATA;
        }
        $this->message = new IXR_Message($data);
        if (!$this->message->parse()) {
            $this->error(-32700, "parse error. not well formed");
        }
        if ($this->message->messageType != "methodCall") {
            $this->error(-32600, "server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall");
        }
        $result = $this->call($this->message->methodName, $this->message->params);
        if (is_a($result, "IXR_Error")) {
            $this->error($result);
        }
        $r = new IXR_Value($result);
        $resultxml = $r->getXml();
        $xml = "<methodResponse>\n  <params>\n    <param>\n      <value>\n        " . $resultxml . "\n      </value>\n    </param>\n  </params>\n</methodResponse>\n";
        $this->output($xml);
    }
    public function call($methodname, $args)
    {
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, "server error. requested method " . $methodname . " does not exist.");
        }
        $method = $this->callbacks[$methodname];
        if (count($args) == 1) {
            $args = $args[0];
        }
        if (substr($method, 0, 5) == "this:") {
            $method = substr($method, 5);
            if (!method_exists($this, $method)) {
                return new IXR_Error(-32601, "server error. requested class method \"" . $method . "\" does not exist.");
            }
            $result = $this->{$method}($args);
        } else {
            if (!function_exists($method)) {
                return new IXR_Error(-32601, "server error. requested function \"" . $method . "\" does not exist.");
            }
            $result = $method($args);
        }
        return $result;
    }
    public function error($error, $message = false)
    {
        if ($message && !is_object($error)) {
            $error = new IXR_Error($error, $message);
        }
        $this->output($error->getXml());
    }
    public function output($xml)
    {
        $xml = "<?xml version=\"1.0\"?>" . "\n" . $xml;
        $length = strlen($xml);
        header("Connection: close");
        header("Content-Length: " . $length);
        header("Content-Type: text/xml");
        header("Date: " . date("r"));
        echo $xml;
        exit;
    }
    public function hasMethod($method)
    {
        return in_array($method, array_keys($this->callbacks));
    }
    public function setCapabilities()
    {
        $this->capabilities = array("xmlrpc" => array("specUrl" => "http://www.xmlrpc.com/spec", "specVersion" => 1), "faults_interop" => array("specUrl" => "http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php", "specVersion" => 20010516), "system.multicall" => array("specUrl" => "http://www.xmlrpc.com/discuss/msgReader\$1208", "specVersion" => 1));
    }
    public function getCapabilities($args)
    {
        return $this->capabilities;
    }
    public function setCallbacks()
    {
        $this->callbacks["system.getCapabilities"] = "this:getCapabilities";
        $this->callbacks["system.listMethods"] = "this:listMethods";
        $this->callbacks["system.multicall"] = "this:multiCall";
    }
    public function listMethods($args)
    {
        return array_reverse(array_keys($this->callbacks));
    }
    public function multiCall($methodcalls)
    {
        $return = array();
        foreach ($methodcalls as $call) {
            $method = $call["methodName"];
            $params = $call["params"];
            if ($method == "system.multicall") {
                $result = new IXR_Error(-32600, "Recursive calls to system.multicall are forbidden");
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, "IXR_Error")) {
                $return[] = array("faultCode" => $result->code, "faultString" => $result->message);
            } else {
                $return[] = array($result);
            }
        }
        return $return;
    }
}
class IXR_Request
{
    public $method = NULL;
    public $args = NULL;
    public $xml = NULL;
    public function IXR_Request($method, $args)
    {
        $this->method = $method;
        $this->args = $args;
        $this->xml = "<?xml version=\"1.0\"?>\n<methodCall>\n<methodName>" . $this->method . "</methodName>\n<params>\n";
        foreach ($this->args as $arg) {
            $this->xml .= "<param><value>";
            $v = new IXR_Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }
        $this->xml .= "</params></methodCall>";
    }
    public function getLength()
    {
        return strlen($this->xml);
    }
    public function getXml()
    {
        return $this->xml;
    }
}
class IXR_Client
{
    public $server = NULL;
    public $port = NULL;
    public $path = NULL;
    public $useragent = NULL;
    public $response = NULL;
    public $message = false;
    public $debug = false;
    public $error = false;
    public function IXR_Client($server, $path = false, $port = 80)
    {
        if (!$path) {
            $bits = parse_url($server);
            $this->server = $bits["host"];
            $this->port = isset($bits["port"]) ? $bits["port"] : 80;
            $this->path = isset($bits["path"]) ? $bits["path"] : "/";
            if (!$this->path) {
                $this->path = "/";
            }
        } else {
            $this->server = $server;
            $this->path = $path;
            $this->port = $port;
        }
        $this->useragent = "The Incutio XML-RPC PHP Library";
    }
    public function query()
    {
        $args = func_get_args();
        $method = array_shift($args);
        $request = new IXR_Request($method, $args);
        $length = $request->getLength();
        $xml = $request->getXml();
        $r = "\r\n";
        $request = "POST " . $this->path . " HTTP/1.0" . $r;
        $request .= "Host: " . $this->server . $r;
        $request .= "Content-Type: text/xml" . $r;
        $request .= "User-Agent: " . $this->useragent . $r;
        $request .= "Content-length: " . $length . $r . $r;
        $request .= $xml;
        if ($this->debug) {
            echo "<pre>" . htmlspecialchars($request) . "\n</pre>\n\n";
        }
        $fp = @fsockopen($this->server, $this->port);
        if (!$fp) {
            $this->error = new IXR_Error(-32300, "transport error - could not open socket");
            return false;
        }
        fputs($fp, $request);
        $contents = "";
        $gotFirstLine = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (!$gotFirstLine) {
                if (strstr($line, "200") === false) {
                    $this->error = new IXR_Error(-32300, "transport error - HTTP status code was not 200");
                    if ($this->debug) {
                        echo "<pre>" . htmlspecialchars($line) . "\n</pre>\n\n";
                    }
                    return false;
                }
                $gotFirstLine = true;
            }
            if (trim($line) == "") {
                $gettingHeaders = false;
            }
            if (!$gettingHeaders) {
                $contents .= trim($line) . "\n";
            }
        }
        if ($this->debug) {
            echo "<pre>" . htmlspecialchars($contents) . "\n</pre>\n\n";
        }
        $this->message = new IXR_Message($contents);
        if (!$this->message->parse()) {
            $this->error = new IXR_Error(-32700, "parse error. not well formed");
            return false;
        }
        if ($this->message->messageType == "fault") {
            $this->error = new IXR_Error($this->message->faultCode, $this->message->faultString);
            return false;
        }
        return true;
    }
    public function getResponse()
    {
        return $this->message->params[0];
    }
    public function isError()
    {
        return is_object($this->error);
    }
    public function getErrorCode()
    {
        return $this->error->code;
    }
    public function getErrorMessage()
    {
        return $this->error->message;
    }
}
class IXR_Error
{
    public $code = NULL;
    public $message = NULL;
    public function IXR_Error($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }
    public function getXml()
    {
        $xml = "<methodResponse>\n  <fault>\n    <value>\n      <struct>\n        <member>\n          <name>faultCode</name>\n          <value><int>" . $this->code . "</int></value>\n        </member>\n        <member>\n          <name>faultString</name>\n          <value><string>" . $this->message . "</string></value>\n        </member>\n      </struct>\n    </value>\n  </fault>\n</methodResponse> \n";
        return $xml;
    }
}
class IXR_Date
{
    public $year = NULL;
    public $month = NULL;
    public $day = NULL;
    public $hour = NULL;
    public $minute = NULL;
    public $second = NULL;
    public function IXR_Date($time)
    {
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseIso($time);
        }
    }
    public function parseTimestamp($timestamp)
    {
        $this->year = date("Y", $timestamp);
        $this->month = date("Y", $timestamp);
        $this->day = date("Y", $timestamp);
        $this->hour = date("H", $timestamp);
        $this->minute = date("i", $timestamp);
        $this->second = date("s", $timestamp);
    }
    public function parseIso($iso)
    {
        $this->year = substr($iso, 0, 4);
        $this->month = substr($iso, 4, 2);
        $this->day = substr($iso, 6, 2);
        $this->hour = substr($iso, 9, 2);
        $this->minute = substr($iso, 12, 2);
        $this->second = substr($iso, 15, 2);
    }
    public function getIso()
    {
        return $this->year . $this->month . $this->day . "T" . $this->hour . ":" . $this->minute . ":" . $this->second;
    }
    public function getXml()
    {
        return "<dateTime.iso8601>" . $this->getIso() . "</dateTime.iso8601>";
    }
    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}
class IXR_Base64
{
    public $data = NULL;
    public function IXR_Base64($data)
    {
        $this->data = $data;
    }
    public function getXml()
    {
        return "<base64>" . base64_encode($this->data) . "</base64>";
    }
}
class IXR_IntrospectionServer extends IXR_Server
{
    public $signatures = NULL;
    public $help = NULL;
    public function IXR_IntrospectionServer()
    {
        $this->setCallbacks();
        $this->setCapabilities();
        $this->capabilities["introspection"] = array("specUrl" => "http://xmlrpc.usefulinc.com/doc/reserved.html", "specVersion" => 1);
        $this->addCallback("system.methodSignature", "this:methodSignature", array("array", "string"), "Returns an array describing the return type and required parameters of a method");
        $this->addCallback("system.getCapabilities", "this:getCapabilities", array("struct"), "Returns a struct describing the XML-RPC specifications supported by this server");
        $this->addCallback("system.listMethods", "this:listMethods", array("array"), "Returns an array of available methods on this server");
        $this->addCallback("system.methodHelp", "this:methodHelp", array("string", "string"), "Returns a documentation string for the specified method");
    }
    public function addCallback($method, $callback, $args, $help)
    {
        $this->callbacks[$method] = $callback;
        $this->signatures[$method] = $args;
        $this->help[$method] = $help;
    }
    public function call($methodname, $args)
    {
        if ($args && !is_array($args)) {
            $args = array($args);
        }
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, "server error. requested method \"" . $this->message->methodName . "\" not specified.");
        }
        $method = $this->callbacks[$methodname];
        $signature = $this->signatures[$methodname];
        $returnType = array_shift($signature);
        if (count($args) != count($signature)) {
            return new IXR_Error(-32602, "server error. wrong number of method parameters");
        }
        $ok = true;
        $argsbackup = $args;
        $i = 0;
        for ($j = count($args); $i < $j; $i++) {
            $arg = array_shift($args);
            $type = array_shift($signature);
            switch ($type) {
                case "int":
                case "i4":
                    if (is_array($arg) || !is_int($arg)) {
                        $ok = false;
                    }
                    break;
                case "base64":
                case "string":
                    if (!is_string($arg)) {
                        $ok = false;
                    }
                    break;
                case "boolean":
                    if ($arg !== false && $arg !== true) {
                        $ok = false;
                    }
                    break;
                case "float":
                case "double":
                    if (!is_float($arg)) {
                        $ok = false;
                    }
                    break;
                case "date":
                case "dateTime.iso8601":
                    if (!is_a($arg, "IXR_Date")) {
                        $ok = false;
                    }
                    break;
            }
            if (!$ok) {
                return new IXR_Error(-32602, "server error. invalid method parameters");
            }
        }
        return parent::call($methodname, $argsbackup);
    }
    public function methodSignature($method)
    {
        if (!$this->hasMethod($method)) {
            return new IXR_Error(-32601, "server error. requested method \"" . $method . "\" not specified.");
        }
        $types = $this->signatures[$method];
        $return = array();
        foreach ($types as $type) {
            switch ($type) {
                case "string":
                    $return[] = "string";
                    break;
                case "int":
                case "i4":
                    $return[] = 42;
                    break;
                case "double":
                    $return[] = 3.1415;
                    break;
                case "dateTime.iso8601":
                    $return[] = new IXR_Date(time());
                    break;
                case "boolean":
                    $return[] = true;
                    break;
                case "base64":
                    $return[] = new IXR_Base64("base64");
                    break;
                case "array":
                    $return[] = array("array");
                    break;
                case "struct":
                    $return[] = array("struct" => "struct");
                    break;
            }
        }
        return $return;
    }
    public function methodHelp($method)
    {
        return $this->help[$method];
    }
}
class IXR_ClientMulticall extends IXR_Client
{
    public $calls = array();
    public function IXR_ClientMulticall($server, $path = false, $port = 80)
    {
        parent::IXR_Client($server, $path, $port);
        $this->useragent = "The Incutio XML-RPC PHP Library (multicall client)";
    }
    public function addCall()
    {
        $args = func_get_args();
        $methodName = array_shift($args);
        $struct = array("methodName" => $methodName, "params" => $args);
        $this->calls[] = $struct;
    }
    public function query()
    {
        return parent::query("system.multicall", $this->calls);
    }
}

?>