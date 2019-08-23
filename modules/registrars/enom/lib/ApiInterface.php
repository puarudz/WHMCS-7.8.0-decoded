<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Registrar\Enom;

class ApiInterface
{
    protected $PostString = "";
    protected $RawData = "";
    public $Values = array();
    public function NewRequest()
    {
        $this->PostString = "";
        $this->RawData = "";
        $this->Values = array();
    }
    protected function AddError($error)
    {
        $this->Values["ErrCount"] = "1";
        $this->Values["Err1"] = $error;
    }
    protected function ParseResponse($buffer)
    {
        if (!$buffer || !is_string($buffer)) {
            $errorMsg = "Cannot parse empty response from server - ";
            $errorMsg .= "Please try again later";
            $this->AddError($errorMsg);
            return false;
        }
        $Lines = explode("\r", $buffer);
        $NumLines = count($Lines);
        $i = 0;
        while (!trim($Lines[$i])) {
            $i = $i + 1;
        }
        $StartLine = $i;
        $GotValues = 0;
        for ($i = $StartLine; $i < $NumLines; $i++) {
            if (substr($Lines[$i], 1, 1) != ";") {
                $Result = explode("=", $Lines[$i]);
                if (2 <= count($Result)) {
                    $name = trim($Result[0]);
                    $value = trim($Result[1]);
                    if ($name == "ApproverEmail") {
                        $this->Values[$name][] = $value;
                    } else {
                        $this->Values[$name] = $value;
                    }
                    if ($name == "ErrCount") {
                        $GotValues = 1;
                    }
                }
            }
        }
        if ($GotValues == 0) {
            $this->AddError("Invalid data response from server - Please try again later");
            return false;
        }
        return true;
    }
    public function AddParam($Name, $Value)
    {
        $this->PostString = $this->PostString . $Name . "=" . urlencode($Value) . "&";
    }
    public function DoTransaction(array $params, $processResponse = true)
    {
        if ($params["TestMode"]) {
            $host = "resellertest.enom.com";
        } else {
            $host = "reseller.enom.com";
        }
        $whmcsVersion = \App::getVersion();
        $this->AddParam("Engine", "WHMCS" . $whmcsVersion->getMajor() . "." . $whmcsVersion->getMinor());
        $url = "https://" . $host . "/interface.asp";
        $postFieldQuery = $this->PostString;
        $curlOptions = array("CURLOPT_TIMEOUT" => 60);
        $ch = curlCall($url, $postFieldQuery, $curlOptions, true);
        $response = curl_exec($ch);
        $this->RawData = "";
        if (curl_error($ch)) {
            $responseMsgToPropagate = "CURL Error: " . curl_errno($ch) . " - " . curl_error($ch);
            $this->AddError($responseMsgToPropagate);
        } else {
            if (!$response) {
                $responseMsgToPropagate = "Empty data response from server - Please try again later";
            } else {
                $this->RawData = $responseMsgToPropagate = $response;
            }
        }
        curl_close($ch);
        if ($processResponse && $response) {
            $this->ParseResponse($response);
        }
        if (function_exists("logModuleCall")) {
            $action = $this->getActionFromQuery($this->PostString);
            logModuleCall("enom", $action, $this->PostString, $responseMsgToPropagate, "", array($params["Username"], $params["Password"]));
        }
        return $this->RawData;
    }
    protected function getActionFromQuery($query)
    {
        $action = "Unknown Action";
        if (is_string($query)) {
            $queryParts = explode("command=", $query, 2);
            if (isset($queryParts[1])) {
                $commandQuery = explode("&", $queryParts[1], 2);
                $action = $commandQuery[0];
            }
        }
        return $action;
    }
    public function parseXMLResponseToArray()
    {
        return $this->doParseXMLToArray($this->RawData);
    }
    protected function doParseXMLToArray($contents, $get_attributes = 1, $priority = "value")
    {
        $parser = xml_parser_create("");
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) {
            return array();
        }
        $xml_array = $parents = $opened_tags = $arr = $repeated_tag_index = array();
        $current =& $xml_array;
        foreach ($xml_values as $data) {
            unset($attributes);
            unset($value);
            extract($data);
            $result = $attributes_data = array();
            if (isset($value)) {
                if ($priority == "tag") {
                    $result = $value;
                } else {
                    $result["value"] = $value;
                }
            }
            if (isset($attributes) && $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == "tag") {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result["attr"][$attr] = $val;
                    }
                }
            }
            if ($type == "open") {
                $parent[$level - 1] =& $current;
                if (!is_array($current) || !in_array($tag, array_keys($current))) {
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag . "_attr"] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . "_" . $level] = 1;
                    $current =& $current[$tag];
                } else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag . "_" . $level]] = $result;
                        $repeated_tag_index[$tag . "_" . $level]++;
                    } else {
                        $current[$tag] = array($current[$tag], $result);
                        $repeated_tag_index[$tag . "_" . $level] = 2;
                        if (isset($current[$tag . "_attr"])) {
                            $current[$tag]["0_attr"] = $current[$tag . "_attr"];
                            unset($current[$tag . "_attr"]);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . "_" . $level] - 1;
                    $current =& $current[$tag][$last_item_index];
                }
            } else {
                if ($type == "complete") {
                    if (!isset($current[$tag])) {
                        $current[$tag] = $result;
                        $repeated_tag_index[$tag . "_" . $level] = 1;
                        if ($priority == "tag" && $attributes_data) {
                            $current[$tag . "_attr"] = $attributes_data;
                        }
                    } else {
                        if (isset($current[$tag][0]) && is_array($current[$tag])) {
                            $current[$tag][$repeated_tag_index[$tag . "_" . $level]] = $result;
                            if ($priority == "tag" && $get_attributes && $attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . "_" . $level] . "_attr"] = $attributes_data;
                            }
                            $repeated_tag_index[$tag . "_" . $level]++;
                        } else {
                            $current[$tag] = array($current[$tag], $result);
                            $repeated_tag_index[$tag . "_" . $level] = 1;
                            if ($priority == "tag" && $get_attributes) {
                                if (isset($current[$tag . "_attr"])) {
                                    $current[$tag]["0_attr"] = $current[$tag . "_attr"];
                                    unset($current[$tag . "_attr"]);
                                }
                                if ($attributes_data) {
                                    $current[$tag][$repeated_tag_index[$tag . "_" . $level] . "_attr"] = $attributes_data;
                                }
                            }
                            $repeated_tag_index[$tag . "_" . $level]++;
                        }
                    }
                } else {
                    if ($type == "close") {
                        $current =& $parent[$level - 1];
                    }
                }
            }
        }
        return $xml_array;
    }
}

?>