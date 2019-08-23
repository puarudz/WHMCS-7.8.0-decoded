<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class XmlResponse extends \Zend\Diactoros\Response\TextResponse
{
    use \WHMCS\Http\DataTrait;
    use \Zend\Diactoros\Response\InjectContentTypeTrait;
    use \WHMCS\Http\PriceDataTrait;
    public function __construct($data = "", $status = 200, array $headers = array())
    {
        $charset = \WHMCS\Config\Setting::getValue("Charset");
        $headers = $this->injectContentType("application/xml; charset=" . $charset, $headers);
        if (is_array($data)) {
            $content = $this->convertToBody($data);
        } else {
            if (is_string($data) || $data instanceof \Psr\Http\Message\StreamInterface) {
                $content = $data;
            } else {
                $content = "";
            }
        }
        parent::__construct($content, $status, $headers);
    }
    public function convertToBody($data = array())
    {
        $data = $this->mutatePriceToFull($data);
        $this->setRawData($data);
        $xmlContent = $this->convertToXml($data);
        $version = \App::getVersion()->getCasual();
        if (strpos($xmlContent, "<result>error</result>") !== false) {
            $version = "";
        }
        $content = array("<?xml version=\"1.0\" encoding=\"" . \WHMCS\Config\Setting::getValue("Charset") . "\"?>", "<whmcsapi version=\"" . $version . "\">", trim($xmlContent), "</whmcsapi>");
        return implode("\n", $content);
    }
    protected function convertToXml($val, $lastk = "", $printed = false)
    {
        $output = "";
        foreach ($val as $k => $v) {
            if (is_array($v)) {
                if (empty($v)) {
                    continue;
                }
                if ($lastk !== "" && is_numeric($k)) {
                    if (!$printed) {
                        $output .= "<" . $lastk . ">\n";
                        $output .= $this->convertToXml($v, $lastk, true);
                        $output .= "</" . $lastk . ">\n";
                    } else {
                        $output .= $this->convertToXml($v, $lastk);
                    }
                } else {
                    if ($lastk === "") {
                        $output .= "<" . $k . ">\n";
                        $output .= $this->convertToXml($v, $k, true);
                        $output .= "</" . $k . ">\n";
                    } else {
                        if (!$printed) {
                            $output .= "<" . $lastk . ">\n";
                            $arrayKeys = array_keys($v);
                            $output .= $this->convertToXml($v, $k, false);
                            $output .= "</" . $lastk . ">\n";
                        } else {
                            $output .= $this->convertToXml($v, $k);
                        }
                    }
                }
            } else {
                if (!is_array($v)) {
                    $v = \WHMCS\Input\Sanitize::decode($v);
                    if (strpos($v, "<![CDATA[") === false && htmlspecialchars($v) != $v) {
                        $v = "<![CDATA[" . $v . "]]>";
                    }
                    if (is_numeric($k)) {
                        $output .= "<" . $lastk . ">" . $v . "</" . $lastk . ">\n";
                    } else {
                        $output .= "<" . $k . ">" . $v . "</" . $k . ">\n";
                    }
                }
            }
        }
        return $output;
    }
}

?>