<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
class mpgGlobals
{
    public $Globals = array("MONERIS_PROTOCOL" => "https", "MONERIS_HOST" => "esqa.moneris.com", "MONERIS_PORT" => "443", "MONERIS_FILE" => "/gateway2/servlet/MpgRequest", "API_VERSION" => "MpgApi Version 2.03(php)", "CLIENT_TIMEOUT" => "60");
    public function __construct($test_mode = false)
    {
        if (!$test_mode) {
            $this->Globals["MONERIS_HOST"] = "www3.moneris.com";
        }
    }
    public function getGlobals()
    {
        return $this->Globals;
    }
}
class mpgHttpsPost
{
    public $api_token = NULL;
    public $store_id = NULL;
    public $mpgRequest = NULL;
    public $mpgResponse = NULL;
    public function __construct($store_id, $api_token, $mpgRequestOBJ, $test_mode = false)
    {
        $this->store_id = $store_id;
        $this->api_token = $api_token;
        $this->mpgRequest = $mpgRequestOBJ;
        $dataToSend = $this->toXML();
        $g = new mpgGlobals($test_mode);
        $gArray = $g->getGlobals();
        $url = $gArray["MONERIS_PROTOCOL"] . "://" . $gArray["MONERIS_HOST"] . ":" . $gArray["MONERIS_PORT"] . $gArray["MONERIS_FILE"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToSend);
        curl_setopt($ch, CURLOPT_TIMEOUT, $gArray["CLIENT_TIMEOUT"]);
        curl_setopt($ch, CURLOPT_USERAGENT, $gArray["API_VERSION"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            $response = "<?xml version=\"1.0\"?><response><receipt>" . "<ReceiptId>Global Error Receipt</ReceiptId>" . "<ReferenceNum>null</ReferenceNum><ResponseCode>null</ResponseCode>" . "<ISO>null</ISO> <AuthCode>null</AuthCode><TransTime>null</TransTime>" . "<TransDate>null</TransDate><TransType>null</TransType><Complete>false</Complete>" . "<Message>null</Message><TransAmount>null</TransAmount>" . "<CardType>null</CardType>" . "<TransID>null</TransID><TimedOut>null</TimedOut>" . "</receipt></response>";
        }
        $this->mpgResponse = new mpgResponse($response);
    }
    public function getMpgResponse()
    {
        return $this->mpgResponse;
    }
    public function toXML()
    {
        $req = $this->mpgRequest;
        $reqXMLString = $req->toXML();
        $xmlString = "";
        $xmlString .= "<?xml version=\"1.0\"?>" . "<request>" . "<store_id>" . $this->store_id . "</store_id>" . "<api_token>" . $this->api_token . "</api_token>" . $reqXMLString . "</request>";
        return $xmlString;
    }
}
class mpgResponse
{
    public $responseData = NULL;
    public $p = NULL;
    public $currentTag = NULL;
    public $purchaseHash = array();
    public $refundHash = NULL;
    public $correctionHash = array();
    public $isBatchTotals = NULL;
    public $term_id = NULL;
    public $receiptHash = array();
    public $ecrHash = array();
    public $CardType = NULL;
    public $currentTxnType = NULL;
    public $ecrs = array();
    public $cards = array();
    public $cardHash = array();
    public $ACSUrl = NULL;
    public function __construct($xmlString)
    {
        $this->p = xml_parser_create();
        xml_parser_set_option($this->p, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->p, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_set_object($this->p, $this);
        xml_set_element_handler($this->p, "startHandler", "endHandler");
        xml_set_character_data_handler($this->p, "characterHandler");
        xml_parse($this->p, $xmlString);
        xml_parser_free($this->p);
    }
    public function getMpgResponseData()
    {
        return $this->responseData;
    }
    public function getAvsResultCode()
    {
        return $this->responseData["AvsResultCode"];
    }
    public function getCvdResultCode()
    {
        return $this->responseData["CvdResultCode"];
    }
    public function getRecurSuccess()
    {
        return $this->responseData["RecurSuccess"];
    }
    public function getCardType()
    {
        return $this->responseData["CardType"];
    }
    public function getTransAmount()
    {
        return $this->responseData["TransAmount"];
    }
    public function getTxnNumber()
    {
        return $this->responseData["TransID"];
    }
    public function getReceiptId()
    {
        return $this->responseData["ReceiptId"];
    }
    public function getTransType()
    {
        return $this->responseData["TransType"];
    }
    public function getReferenceNum()
    {
        return $this->responseData["ReferenceNum"];
    }
    public function getResponseCode()
    {
        return $this->responseData["ResponseCode"];
    }
    public function getISO()
    {
        return $this->responseData["ISO"];
    }
    public function getBankTotals()
    {
        return $this->responseData["BankTotals"];
    }
    public function getMessage()
    {
        return $this->responseData["Message"];
    }
    public function getAuthCode()
    {
        return $this->responseData["AuthCode"];
    }
    public function getComplete()
    {
        return $this->responseData["Complete"];
    }
    public function getTransDate()
    {
        return $this->responseData["TransDate"];
    }
    public function getTransTime()
    {
        return $this->responseData["TransTime"];
    }
    public function getTicket()
    {
        return $this->responseData["Ticket"];
    }
    public function getTimedOut()
    {
        return $this->responseData["TimedOut"];
    }
    public function getTerminalStatus($ecr_no)
    {
        return $this->ecrHash[$ecr_no];
    }
    public function getPurchaseAmount($ecr_no, $card_type)
    {
        return $this->purchaseHash[$ecr_no][$card_type]["Amount"] == "" ? 0 : $this->purchaseHash[$ecr_no][$card_type]["Amount"];
    }
    public function getPurchaseCount($ecr_no, $card_type)
    {
        return $this->purchaseHash[$ecr_no][$card_type]["Count"] == "" ? 0 : $this->purchaseHash[$ecr_no][$card_type]["Count"];
    }
    public function getRefundAmount($ecr_no, $card_type)
    {
        return $this->refundHash[$ecr_no][$card_type]["Amount"] == "" ? 0 : $this->refundHash[$ecr_no][$card_type]["Amount"];
    }
    public function getRefundCount($ecr_no, $card_type)
    {
        return $this->refundHash[$ecr_no][$card_type]["Count"] == "" ? 0 : $this->refundHash[$ecr_no][$card_type]["Count"];
    }
    public function getCorrectionAmount($ecr_no, $card_type)
    {
        return $this->correctionHash[$ecr_no][$card_type]["Amount"] == "" ? 0 : $this->correctionHash[$ecr_no][$card_type]["Amount"];
    }
    public function getCorrectionCount($ecr_no, $card_type)
    {
        return $this->correctionHash[$ecr_no][$card_type]["Count"] == "" ? 0 : $this->correctionHash[$ecr_no][$card_type]["Count"];
    }
    public function getTerminalIDs()
    {
        return $this->ecrs;
    }
    public function getCreditCardsAll()
    {
        return array_keys($this->cards);
    }
    public function getCreditCards($ecr_no)
    {
        return $this->cardHash[$ecr_no];
    }
    public function characterHandler($parser, $data)
    {
        if ($this->isBatchTotals) {
            switch ($this->currentTag) {
                case "term_id":
                    $this->term_id = $data;
                    array_push($this->ecrs, $this->term_id);
                    $this->cardHash[$data] = array();
                    break;
                case "closed":
                    $ecrHash = $this->ecrHash;
                    $ecrHash[$this->term_id] = $data;
                    $this->ecrHash = $ecrHash;
                    break;
                case "CardType":
                    $this->CardType = $data;
                    $this->cards[$data] = $data;
                    array_push($this->cardHash[$this->term_id], $data);
                    break;
                case "Amount":
                    if ($this->currentTxnType == "Purchase") {
                        $this->purchaseHash[$this->term_id][$this->CardType]["Amount"] = $data;
                    } else {
                        if ($this->currentTxnType == "Refund") {
                            $this->refundHash[$this->term_id][$this->CardType]["Amount"] = $data;
                        } else {
                            if ($this->currentTxnType == "Correction") {
                                $this->correctionHash[$this->term_id][$this->CardType]["Amount"] = $data;
                            }
                        }
                    }
                    break;
                case "Count":
                    if ($this->currentTxnType == "Purchase") {
                        $this->purchaseHash[$this->term_id][$this->CardType]["Count"] = $data;
                    } else {
                        if ($this->currentTxnType == "Refund") {
                            $this->refundHash[$this->term_id][$this->CardType]["Count"] = $data;
                        } else {
                            if ($this->currentTxnType == "Correction") {
                                $this->correctionHash[$this->term_id][$this->CardType]["Count"] = $data;
                            }
                        }
                    }
                    break;
            }
        } else {
            $this->responseData[$this->currentTag] .= $data;
        }
    }
    public function startHandler($parser, $name, $attrs)
    {
        $this->currentTag = $name;
        if ($this->currentTag == "BankTotals") {
            $this->isBatchTotals = 1;
        } else {
            if ($this->currentTag == "Purchase") {
                $this->purchaseHash[$this->term_id][$this->CardType] = array();
                $this->currentTxnType = "Purchase";
            } else {
                if ($this->currentTag == "Refund") {
                    $this->refundHash[$this->term_id][$this->CardType] = array();
                    $this->currentTxnType = "Refund";
                } else {
                    if ($this->currentTag == "Correction") {
                        $this->correctionHash[$this->term_id][$this->CardType] = array();
                        $this->currentTxnType = "Correction";
                    }
                }
            }
        }
    }
    public function endHandler($parser, $name)
    {
        $this->currentTag = $name;
        if ($name == "BankTotals") {
            $this->isBatchTotals = 0;
        }
        $this->currentTag = "/dev/null";
    }
}
class mpgRequest
{
    public $txnTypes = array("purchase" => array("order_id", "cust_id", "amount", "pan", "expdate", "crypt_type"), "refund" => array("order_id", "amount", "txn_number", "crypt_type"), "idebit_purchase" => array("order_id", "cust_id", "amount", "idebit_track2"), "idebit_refund" => array("order_id", "amount", "txn_number"), "ind_refund" => array("order_id", "cust_id", "amount", "pan", "expdate", "crypt_type"), "preauth" => array("order_id", "cust_id", "amount", "pan", "expdate", "crypt_type"), "completion" => array("order_id", "comp_amount", "txn_number", "crypt_type"), "purchasecorrection" => array("order_id", "txn_number", "crypt_type"), "opentotals" => array("ecr_number"), "batchclose" => array("ecr_number"), "cavv_purchase" => array("order_id", "cust_id", "amount", "pan", "expdate", "cavv"), "cavv_preauth" => array("order_id", "cust_id", "amount", "pan", "expdate", "cavv"));
    public $txnArray = NULL;
    public function __construct($txn)
    {
        if (is_array($txn)) {
            $txn = $txn[0];
        }
        $this->txnArray = $txn;
    }
    public function toXML()
    {
        $tmpTxnArray = $this->txnArray;
        $txnArrayLen = count($tmpTxnArray);
        $txnObj = $tmpTxnArray;
        $txn = $txnObj->getTransaction();
        $txnType = array_shift($txn);
        $tmpTxnTypes = $this->txnTypes;
        $txnTypeArray = $tmpTxnTypes[$txnType];
        $txnTypeArrayLen = count($txnTypeArray);
        $txnXMLString = "";
        for ($i = 0; $i < $txnTypeArrayLen; $i++) {
            $txnXMLString .= "<" . $txnTypeArray[$i] . ">" . $txn[$txnTypeArray[$i]] . "</" . $txnTypeArray[$i] . ">";
        }
        $txnXMLString = "<" . $txnType . ">" . $txnXMLString;
        $recur = $txnObj->getRecur();
        if ($recur != NULL) {
            $txnXMLString .= $recur->toXML();
        }
        $avsInfo = $txnObj->getAvsInfo();
        if ($avsInfo != NULL) {
            $txnXMLString .= $avsInfo->toXML();
        }
        $cvdInfo = $txnObj->getCvdInfo();
        if ($cvdInfo != NULL) {
            $txnXMLString .= $cvdInfo->toXML();
        }
        $custInfo = $txnObj->getCustInfo();
        if ($custInfo != NULL) {
            $txnXMLString .= $custInfo->toXML();
        }
        $txnXMLString .= "</" . $txnType . ">";
        $xmlString = "";
        $xmlString .= $txnXMLString;
        return $xmlString;
    }
}
class mpgCustInfo
{
    public $level3template = array("cust_info" => array("email", "instructions", "billing" => array("first_name", "last_name", "company_name", "address", "city", "province", "postal_code", "country", "phone_number", "fax", "tax1", "tax2", "tax3", "shipping_cost"), "shipping" => array("first_name", "last_name", "company_name", "address", "city", "province", "postal_code", "country", "phone_number", "fax", "tax1", "tax2", "tax3", "shipping_cost"), "item" => array("name", "quantity", "product_code", "extended_amount")));
    public $level3data = NULL;
    public $email = NULL;
    public $instructions = NULL;
    public function __construct($custinfo = 0, $billing = 0, $shipping = 0, $items = 0)
    {
        if ($custinfo) {
            $this->setCustInfo($custinfo);
        }
    }
    public function setCustInfo($custinfo)
    {
        $this->level3data["cust_info"] = array($custinfo);
    }
    public function setEmail($email)
    {
        $this->email = $email;
        $this->setCustInfo(array("email" => $email, "instructions" => $this->instructions));
    }
    public function setInstructions($instructions)
    {
        $this->instructions = $instructions;
        $this->setCustinfo(array("email" => $this->email, "instructions" => $instructions));
    }
    public function setShipping($shipping)
    {
        $this->level3data["shipping"] = array($shipping);
    }
    public function setBilling($billing)
    {
        $this->level3data["billing"] = array($billing);
    }
    public function setItems($items)
    {
        if (!isset($this->level3data["item"])) {
            $this->level3data["item"] = array($items);
        } else {
            $index = count($this->level3data["item"]);
            $this->level3data["item"][$index] = $items;
        }
    }
    public function toXML()
    {
        $xmlString = $this->toXML_low($this->level3template, "cust_info");
        return $xmlString;
    }
    public function toXML_low($template, $txnType)
    {
        for ($x = 0; $x < count($this->level3data[$txnType]); $x++) {
            if (0 < $x) {
                $xmlString .= "</" . $txnType . "><" . $txnType . ">";
            }
            $keys = array_keys($template);
            for ($i = 0; $i < count($keys); $i++) {
                $tag = $keys[$i];
                if (is_array($template[$keys[$i]])) {
                    $data = $template[$tag];
                    if (!count($this->level3data[$tag])) {
                        continue;
                    }
                    $beginTag = "<" . $tag . ">";
                    $endTag = "</" . $tag . ">";
                    $xmlString .= $beginTag;
                    if (is_array($data)) {
                        $returnString = $this->toXML_low($data, $tag);
                        $xmlString .= $returnString;
                    }
                    $xmlString .= $endTag;
                } else {
                    $tag = $template[$keys[$i]];
                    $beginTag = "<" . $tag . ">";
                    $endTag = "</" . $tag . ">";
                    $data = $this->level3data[$txnType][$x][$tag];
                    $xmlString .= $beginTag . $data . $endTag;
                }
            }
        }
        return $xmlString;
    }
}
class mpgRecur
{
    public $params = NULL;
    public $recurTemplate = array("recur_unit", "start_now", "start_date", "num_recurs", "period", "recur_amount");
    public function __construct($params)
    {
        $this->params = $params;
        if (!$this->params["period"]) {
            $this->params["period"] = 1;
        }
    }
    public function toXML()
    {
        $xmlString = "";
        foreach ($this->recurTemplate as $tag) {
            $xmlString .= "<" . $tag . ">" . $this->params[$tag] . "</" . $tag . ">";
        }
        return "<recur>" . $xmlString . "</recur>";
    }
}
class mpgTransaction
{
    public $txn = NULL;
    public $custInfo = NULL;
    public $avsInfo = NULL;
    public $cvdInfo = NULL;
    public $recur = NULL;
    public function __construct($txn)
    {
        $this->txn = $txn;
    }
    public function getCustInfo()
    {
        return $this->custInfo;
    }
    public function setCustInfo($custInfo)
    {
        $this->custInfo = $custInfo;
        array_push($this->txn, $custInfo);
    }
    public function getCvdInfo()
    {
        return $this->cvdInfo;
    }
    public function setCvdInfo($cvdInfo)
    {
        $this->cvdInfo = $cvdInfo;
    }
    public function getAvsInfo()
    {
        return $this->avsInfo;
    }
    public function setAvsInfo($avsInfo)
    {
        $this->avsInfo = $avsInfo;
    }
    public function getRecur()
    {
        return $this->recur;
    }
    public function setRecur($recur)
    {
        $this->recur = $recur;
    }
    public function getTransaction()
    {
        return $this->txn;
    }
}
class mpgAvsInfo
{
    public $params = NULL;
    public $avsTemplate = array("avs_street_number", "avs_street_name", "avs_zipcode");
    public function __construct($params)
    {
        $this->params = $params;
    }
    public function toXML()
    {
        $xmlString = "";
        foreach ($this->avsTemplate as $tag) {
            $xmlString .= "<" . $tag . ">" . $this->params[$tag] . "</" . $tag . ">";
        }
        return "<avs_info>" . $xmlString . "</avs_info>";
    }
}
class mpgCvdInfo
{
    public $params = NULL;
    public $cvdTemplate = array("cvd_indicator", "cvd_value");
    public function __construct($params)
    {
        $this->params = $params;
    }
    public function toXML()
    {
        $xmlString = "";
        foreach ($this->cvdTemplate as $tag) {
            $xmlString .= "<" . $tag . ">" . $this->params[$tag] . "</" . $tag . ">";
        }
        return "<cvd_info>" . $xmlString . "</cvd_info>";
    }
}
function moneris_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Moneris"), "store_id" => array("FriendlyName" => "Store ID", "Type" => "text", "Size" => "12", "Description" => "A value that identifies your company when your send a transaction"), "api_token" => array("FriendlyName" => "API token", "Type" => "text", "Size" => "20", "Description" => "A unique key that when matched with your store_id creates a secure method of authenticating your store_id"), "order_id_format" => array("FriendlyName" => "Order ID format", "Type" => "text", "Size" => "20", "Description" => "Enter the format for the Moneris order_id numbers eg. WHMCS-%s. Token will be replaced with actual invoice id."), "testmode" => array("FriendlyName" => "Test Environment", "Type" => "yesno", "Description" => "When set, the transaction will be a test transaction only"));
    return $configarray;
}
function moneris_capture($params)
{
    $txnArray = array("type" => "purchase", "crypt_type" => 7);
    $store_id = $params["testmode"] ? "store1" : $params["store_id"];
    $api_token = $params["testmode"] ? "yesguy" : $params["api_token"];
    $test_mode = $params["testmode"] ? true : false;
    $txnArray["order_id"] = sprintf($params["order_id_format"], uniqid($params["invoiceid"] . "."));
    $txnArray["cust_id"] = $params["clientdetails"]["email"];
    $txnArray["amount"] = $params["amount"];
    $txnArray["pan"] = $params["cardnum"];
    $txnArray["expdate"] = substr($params["cardexp"], 2, 2) . substr($params["cardexp"], 0, 2);
    $mpgTxn = new mpgTransaction($txnArray);
    $mpgRequest = new mpgRequest($mpgTxn);
    $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest, $test_mode);
    $mpgResponse = $mpgHttpPost->getMpgResponse();
    $m_result = array("CardType" => $mpgResponse->getCardType(), "TransAmount" => $mpgResponse->getTransAmount(), "TxnNumber" => $mpgResponse->getTxnNumber(), "ReceiptId" => $mpgResponse->getReceiptId(), "TransType" => $mpgResponse->getTransType(), "ReferenceNum" => $mpgResponse->getReferenceNum(), "ResponseCode" => $mpgResponse->getResponseCode(), "ISO" => $mpgResponse->getISO(), "Message" => $mpgResponse->getMessage(), "AuthCode" => $mpgResponse->getAuthCode(), "Complete" => $mpgResponse->getComplete(), "TransDate" => $mpgResponse->getTransDate(), "TransTime" => $mpgResponse->getTransTime(), "Ticket" => $mpgResponse->getTicket(), "TimedOut" => $mpgResponse->getTimedOut());
    $responseCode = "null" == $mpgResponse->getResponseCode() ? NULL : (int) $mpgResponse->getResponseCode();
    if (NULL === $responseCode) {
        $result = array("status" => "error", "rawdata" => $m_result);
    } else {
        if (0 <= $responseCode && $responseCode < 50) {
            $result = array("status" => "success", "transid" => $m_result["TxnNumber"], "rawdata" => $m_result);
        } else {
            $result = array("status" => "declined", "rawdata" => $m_result);
        }
    }
    return $result;
}
function moneris_refund($params)
{
    $txnArray = array("type" => "ind_refund", "crypt_type" => 7);
    $store_id = $params["testmode"] ? "store1" : $params["store_id"];
    $api_token = $params["testmode"] ? "yesguy" : $params["api_token"];
    $test_mode = $params["testmode"] ? true : false;
    $txnArray["order_id"] = sprintf($params["order_id_format"], uniqid($params["invoiceid"] . "."));
    $txnArray["cust_id"] = $params["clientdetails"]["email"];
    $txnArray["amount"] = $params["amount"];
    $txnArray["pan"] = $params["cardnum"];
    $txnArray["expdate"] = substr($params["cardexp"], 2, 2) . substr($params["cardexp"], 0, 2);
    $mpgTxn = new mpgTransaction($txnArray);
    $mpgRequest = new mpgRequest($mpgTxn);
    $mpgHttpPost = new mpgHttpsPost($store_id, $api_token, $mpgRequest, $test_mode);
    $mpgResponse = $mpgHttpPost->getMpgResponse();
    $m_result = array("CardType" => $mpgResponse->getCardType(), "TransAmount" => $mpgResponse->getTransAmount(), "TxnNumber" => $mpgResponse->getTxnNumber(), "ReceiptId" => $mpgResponse->getReceiptId(), "TransType" => $mpgResponse->getTransType(), "ReferenceNum" => $mpgResponse->getReferenceNum(), "ResponseCode" => $mpgResponse->getResponseCode(), "ISO" => $mpgResponse->getISO(), "Message" => $mpgResponse->getMessage(), "AuthCode" => $mpgResponse->getAuthCode(), "Complete" => $mpgResponse->getComplete(), "TransDate" => $mpgResponse->getTransDate(), "TransTime" => $mpgResponse->getTransTime(), "Ticket" => $mpgResponse->getTicket(), "TimedOut" => $mpgResponse->getTimedOut());
    $responseCode = "null" == $mpgResponse->getResponseCode() ? NULL : intval($mpgResponse->getResponseCode());
    if (NULL === $responseCode) {
        $result = array("status" => "error", "rawdata" => $m_result);
    } else {
        if (0 <= $responseCode && $responseCode < 50) {
            $result = array("status" => "success", "transid" => $m_result["TxnNumber"], "rawdata" => $m_result);
        } else {
            $result = array("status" => "declined", "rawdata" => $m_result);
        }
    }
    return $result;
}

?>