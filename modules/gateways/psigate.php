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
$GATEWAYMODULE["psigatename"] = "psigate";
$GATEWAYMODULE["psigatevisiblename"] = "PSIGate";
$GATEWAYMODULE["psigatetype"] = "CC";
define("PSIGATE_PORT_TEST_HIGH_SEC", 27989);
define("PSIGATE_PORT_PRODUCTION_HIGH_SEC", 27934);
define("PSIGATE_CURL_ERROR_OFFSET", 1000);
define("PSIGATE_XML_ERROR_OFFSET", 2000);
define("PSIGATE_TRANSACTION_OK", "APPROVED");
define("PSIGATE_TRANSACTION_DECLINED", "DECLINED");
define("PSIGATE_TRANSACTION_ERROR", "ERROR");
class PsiGatePayment
{
    public $parser = NULL;
    public $xmlData = NULL;
    public $currentTag = NULL;
    public $myGatewayURL = NULL;
    public $myStoreID = NULL;
    public $myPassphrase = NULL;
    public $myPaymentType = NULL;
    public $myCardAction = NULL;
    public $mySubtotal = NULL;
    public $myTaxTotal1 = NULL;
    public $myTaxTotal2 = NULL;
    public $myTaxTotal3 = NULL;
    public $myTaxTotal4 = NULL;
    public $myTaxTotal5 = NULL;
    public $myShipTotal = NULL;
    public $myCardNumber = NULL;
    public $myCardExpMonth = NULL;
    public $myCardExpYear = NULL;
    public $myCardIDCode = NULL;
    public $myCardIDNumber = NULL;
    public $myTestResult = NULL;
    public $myOrderID = NULL;
    public $myUserID = NULL;
    public $myBname = NULL;
    public $myBcompany = NULL;
    public $myBaddress1 = NULL;
    public $myBaddress2 = NULL;
    public $myBcity = NULL;
    public $myBprovince = NULL;
    public $myBpostalcode = NULL;
    public $myBcountry = NULL;
    public $mySname = NULL;
    public $myScompany = NULL;
    public $mySaddress1 = NULL;
    public $mySaddress2 = NULL;
    public $myScity = NULL;
    public $mySprovince = NULL;
    public $mySpostalcode = NULL;
    public $myScountry = NULL;
    public $myPhone = NULL;
    public $myFax = NULL;
    public $myEmail = NULL;
    public $myComments = NULL;
    public $myCustomerIP = NULL;
    public $myRecurring = NULL;
    public $myIteration = NULL;
    public $myResultTrxnTransTime = NULL;
    public $myResultTrxnOrderID = NULL;
    public $myResultTrxnApproved = NULL;
    public $myResultTrxnReturnCode = NULL;
    public $myResultTrxnErrMsg = NULL;
    public $myResultTrxnTaxTotal = NULL;
    public $myResultTrxnShipTotal = NULL;
    public $myResultTrxnSubTotal = NULL;
    public $myResultTrxnFullTotal = NULL;
    public $myResultTrxnPaymentType = NULL;
    public $myResultTrxnCardNumber = NULL;
    public $myResultTrxnCardExpMonth = NULL;
    public $myResultTrxnCardExpYear = NULL;
    public $myResultTrxnTransRefNumber = NULL;
    public $myResultTrxnCardIDResult = NULL;
    public $myResultTrxnAVSResult = NULL;
    public $myResultTrxnCardAuthNumber = NULL;
    public $myResultTrxnCardRefNumber = NULL;
    public $myResultTrxnCardType = NULL;
    public $myResultTrxnIPResult = NULL;
    public $myResultTrxnIPCountry = NULL;
    public $myResultTrxnIPRegion = NULL;
    public $myResultTrxnIPCity = NULL;
    public $myError = NULL;
    public $myErrorMessage = NULL;
    public function ElementStart($parser, $tag, $attributes)
    {
        $this->currentTag = $tag;
    }
    public function ElementEnd($parser, $tag)
    {
        $this->currentTag = "";
    }
    public function charachterData($parser, $cdata)
    {
        $this->xmlData[$this->currentTag] = $cdata;
    }
    public function setGatewayURL($GatewayURL)
    {
        $this->myGatewayURL = $GatewayURL;
    }
    public function setStoreID($StoreID)
    {
        $this->myStoreID = $StoreID;
    }
    public function setPassphrase($Passphrase)
    {
        $this->myPassphrase = $Passphrase;
    }
    public function setPaymentType($PaymentType)
    {
        $this->myPaymentType = $PaymentType;
    }
    public function setCardAction($CardAction)
    {
        $this->myCardAction = $CardAction;
    }
    public function setSubtotal($Subtotal)
    {
        $this->mySubtotal = $Subtotal;
    }
    public function setTaxTotal1($TaxTotal1)
    {
        $this->myTaxTotal1 = $TaxTotal1;
    }
    public function setTaxTotal2($TaxTotal2)
    {
        $this->myTaxTotal2 = $TaxTotal2;
    }
    public function setTaxTotal3($TaxTotal3)
    {
        $this->myTaxTotal3 = $TaxTotal3;
    }
    public function setTaxTotal4($TaxTotal4)
    {
        $this->myTaxTotal4 = $TaxTotal4;
    }
    public function setTaxTotal5($TaxTotal5)
    {
        $this->myTaxTotal5 = $TaxTotal5;
    }
    public function setShiptotal($Shiptotal)
    {
        $this->myShiptotal = $Shiptotal;
    }
    public function setCardNumber($CardNumber)
    {
        $this->myCardNumber = $CardNumber;
    }
    public function setCardExpMonth($CardExpMonth)
    {
        $this->myCardExpMonth = $CardExpMonth;
    }
    public function setCardExpYear($CardExpYear)
    {
        $this->myCardExpYear = $CardExpYear;
    }
    public function setCardIDCode($CardIDCode)
    {
        $this->myCardIDCode = $CardIDCode;
    }
    public function setCardIDNumber($CardIDNumber)
    {
        $this->myCardIDNumber = $CardIDNumber;
    }
    public function setTestResult($TestResult)
    {
        $this->myTestResult = $TestResult;
    }
    public function setOrderID($OrderID)
    {
        $this->myOrderID = $OrderID;
    }
    public function setUserID($UserID)
    {
        $this->myUserID = $UserID;
    }
    public function setBname($Bname)
    {
        $this->myBname = $Bname;
    }
    public function setBcompany($Bcompany)
    {
        $this->myBcompany = $Bcompany;
    }
    public function setBaddress1($Baddress1)
    {
        $this->myBaddress1 = $Baddress1;
    }
    public function setBaddress2($Baddress2)
    {
        $this->myBaddress2 = $Baddress2;
    }
    public function setBcity($Bcity)
    {
        $this->myBcity = $Bcity;
    }
    public function setBprovince($Bprovince)
    {
        $this->myBprovince = $Bprovince;
    }
    public function setBpostalcode($Bpostalcode)
    {
        $this->myBpostalcode = $Bpostalcode;
    }
    public function setBcountry($Bcountry)
    {
        $this->myBcountry = $Bcountry;
    }
    public function setSname($Sname)
    {
        $this->mySname = $Sname;
    }
    public function setScompany($Scompany)
    {
        $this->myScompany = $Scompany;
    }
    public function setSaddress1($Saddress1)
    {
        $this->mySaddress1 = $Saddress1;
    }
    public function setSaddress2($Saddress2)
    {
        $this->mySaddress2 = $Saddress2;
    }
    public function setScity($Scity)
    {
        $this->myScity = $Scity;
    }
    public function setSprovince($Sprovince)
    {
        $this->mySprovince = $Sprovince;
    }
    public function setSpostalcode($Spostalcode)
    {
        $this->mySpostalcode = $Spostalcode;
    }
    public function setScountry($Scountry)
    {
        $this->myScountry = $Scountry;
    }
    public function setPhone($Phone)
    {
        $this->myPhone = $Phone;
    }
    public function setFax($Fax)
    {
        $this->myFax = $Fax;
    }
    public function setEmail($Email)
    {
        $this->myEmail = $Email;
    }
    public function setComments($Comments)
    {
        $this->myComments = $Comments;
    }
    public function setCustomerIP($CustomerIP)
    {
        $this->myCustomerIP = $CustomerIP;
    }
    public function setRecurring($Recurring)
    {
        $this->myRecurring = $Recurring;
    }
    public function setIteration($Iteration)
    {
        $this->myIteration = $Iteration;
    }
    public function getTrxnTransTime()
    {
        return $this->myResultTrxnTransTime;
    }
    public function getTrxnOrderID()
    {
        return $this->myResultTrxnOrderID;
    }
    public function getTrxnApproved()
    {
        return $this->myResultTrxnApproved;
    }
    public function getTrxnReturnCode()
    {
        return $this->myResultTrxnReturnCode;
    }
    public function getTrxnErrMsg()
    {
        return $this->myResultTrxnErrMsg;
    }
    public function getTrxnTaxTotal()
    {
        return $this->myResultTrxnTaxTotal;
    }
    public function getTrxnShipTotal()
    {
        return $this->myResultTrxnShipTotal;
    }
    public function getTrxnSubTotal()
    {
        return $this->myResultTrxnSubTotal;
    }
    public function getTrxnFullTotal()
    {
        return $this->myResultTrxnFullTotal;
    }
    public function getTrxnPaymentType()
    {
        return $this->myResultTrxnPaymentType;
    }
    public function getTrxnCardNumber()
    {
        return $this->myResultTrxnCardNumber;
    }
    public function getTrxnCardExpMonth()
    {
        return $this->myResultTrxnCardExpMonth;
    }
    public function getTrxnCardExpYear()
    {
        return $this->myResultTrxnCardExpYear;
    }
    public function getTrxnTransRefNumber()
    {
        return $this->myResultTrxnTransRefNumber;
    }
    public function getTrxnCardIDResult()
    {
        return $this->myResultTrxnCardIDResult;
    }
    public function getTrxnAVSResult()
    {
        return $this->myResultTrxnAVSResult;
    }
    public function getTrxnCardAuthNumber()
    {
        return $this->myResultTrxnCardAuthNumber;
    }
    public function getTrxnCardRefNumber()
    {
        return $this->myResultTrxnCardRefNumber;
    }
    public function getTrxnCardType()
    {
        return $this->myResultTrxnCardType;
    }
    public function getTrxnIPResult()
    {
        return $this->myResultTrxnIPResult;
    }
    public function getTrxnIPCountry()
    {
        return $this->myResultTrxnIPCountry;
    }
    public function getTrxnIPRegion()
    {
        return $this->myResultTrxnIPRegion;
    }
    public function getTrxnIPCity()
    {
        return $this->myResultTrxnIPCity;
    }
    public function getError()
    {
        if ($this->myError != 0) {
            return $this->myError;
        }
        if ($this->getTrxnApproved() == "APPROVED") {
            return PSIGATE_TRANSACTION_OK;
        }
        if ($this->getTrxnApproved() == "DECLINED") {
            return PSIGATE_TRANSACTION_DECLINED;
        }
        return PSIGATE_TRANSACTION_ERROR;
    }
    public function getErrorMessage()
    {
        if ($this->myError != 0) {
            return $this->myErrorMessage;
        }
        return $this->getTrxnError();
    }
    public function __construct()
    {
    }
    public function doPayment()
    {
        $recurringFields = "";
        if ($this->myRecurring) {
            $recurringFields .= "<Recurring>" . htmlentities($this->myRecurring) . "</Recurring>";
            if ($this->myIteration) {
                $recurringFields .= "<Iteration>" . htmlentities($this->myIteration) . "</Iteration>";
            }
        }
        $xmlRequest = "<Order>" . "<StoreID>" . htmlentities($this->myStoreID) . "</StoreID>" . "<Passphrase>" . htmlentities($this->myPassphrase) . "</Passphrase>" . "<Tax1>" . htmlentities($this->myTaxTotal1) . "</Tax1>" . "<Tax2>" . htmlentities($this->myTaxTotal2) . "</Tax2>" . "<Tax3>" . htmlentities($this->myTaxTotal3) . "</Tax3>" . "<Tax4>" . htmlentities($this->myTaxTotal4) . "</Tax4>" . "<Tax5>" . htmlentities($this->myTaxTotal5) . "</Tax5>" . "<ShippingTotal>" . htmlentities($this->myShippingtotal) . "</ShippingTotal>" . "<Subtotal>" . htmlentities($this->mySubtotal) . "</Subtotal>" . "<PaymentType>" . htmlentities($this->myPaymentType) . "</PaymentType>" . "<CardAction>" . htmlentities($this->myCardAction) . "</CardAction>" . "<CardNumber>" . htmlentities($this->myCardNumber) . "</CardNumber>" . "<CardExpMonth>" . htmlentities($this->myCardExpMonth) . "</CardExpMonth>" . "<CardExpYear>" . htmlentities($this->myCardExpYear) . "</CardExpYear>" . "<CardIDCode>" . htmlentities($this->myCardIDCode) . "</CardIDCode>" . "<CardIDNumber>" . htmlentities($this->myCardIDNumber) . "</CardIDNumber>" . "<TestResult>" . htmlentities($this->myTestResult) . "</TestResult>" . "<OrderID>" . htmlentities($this->myOrderID) . "</OrderID>" . "<UserID>" . htmlentities($this->myUserID) . "</UserID>" . "<Bname>" . htmlentities($this->myBname) . "</Bname>" . "<Bcompany>" . htmlentities($this->myBcompany) . "</Bcompany>" . "<Baddress1>" . htmlentities($this->myBaddress1) . "</Baddress1>" . "<Baddress2>" . htmlentities($this->myBaddress2) . "</Baddress2>" . "<Bcity>" . htmlentities($this->myBcity) . "</Bcity>" . "<Bprovince>" . htmlentities($this->myBprovince) . "</Bprovince>" . "<Bpostalcode>" . htmlentities($this->myBpostalcode) . "</Bpostalcode>" . "<Bcountry>" . htmlentities($this->myBcountry) . "</Bcountry>" . "<Sname>" . htmlentities($this->mySname) . "</Sname>" . "<Scompany>" . htmlentities($this->myScompany) . "</Scompany>" . "<Saddress1>" . htmlentities($this->mySaddress1) . "</Saddress1>" . "<Saddress2>" . htmlentities($this->mySaddress2) . "</Saddress2>" . "<Scity>" . htmlentities($this->myScity) . "</Scity>" . "<Sprovince>" . htmlentities($this->mySprovince) . "</Sprovince>" . "<Spostalcode>" . htmlentities($this->mySpostalcode) . "</Spostalcode>" . "<Scountry>" . htmlentities($this->myScountry) . "</Scountry>" . "<Phone>" . htmlentities($this->myPhone) . "</Phone>" . "<Email>" . htmlentities($this->myEmail) . "</Email>" . "<Comments>" . htmlentities($this->myComments) . "</Comments>" . "<CustomerIP>" . htmlentities($this->myCustomerIP) . "</CustomerIP>" . $recurringFields . "</Order>";
        $ch = curl_init($this->myGatewayURL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $xmlResponse = curl_exec($ch);
        if (curl_errno($ch) == CURLE_OK) {
            $this->parser = xml_parser_create();
            xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
            xml_set_object($this->parser, $this);
            xml_set_element_handler($this->parser, "ElementStart", "ElementEnd");
            xml_set_character_data_handler($this->parser, "charachterData");
            xml_parse($this->parser, $xmlResponse, true);
            if (xml_get_error_code($this->parser) == XML_ERROR_NONE) {
                $this->myResultTrxnTransTime = $this->xmlData["TransTime"];
                $this->myResultTrxnOrderID = $this->xmlData["OrderID"];
                $this->myResultTrxnApproved = $this->xmlData["Approved"];
                $this->myResultTrxnReturnCode = $this->xmlData["ReturnCode"];
                $this->myResultTrxnErrMsg = $this->xmlData["ErrMsg"];
                $this->myResultTrxnTaxTotal = $this->xmlData["TaxTotal"];
                $this->myResultTrxnShipTotal = $this->xmlData["ShipTotal"];
                $this->myResultTrxnSubTotal = $this->xmlData["SubTotal"];
                $this->myResultTrxnFullTotal = $this->xmlData["FullTotal"];
                $this->myResultTrxnPaymentType = $this->xmlData["PaymentType"];
                $this->myResultTrxnCardNumber = $this->xmlData["CardNumber"];
                $this->myResultTrxnCardExpMonth = $this->xmlData["CardExpMonth"];
                $this->myResultTrxnCardExpYear = $this->xmlData["CardExpYear"];
                $this->myResultTrxnTransRefNumber = $this->xmlData["TransRefNumber"];
                $this->myResultTrxnCardIDResult = $this->xmlData["CardIDResult"];
                $this->myResultTrxnAVSResult = $this->xmlData["AVSResult"];
                $this->myResultTrxnCardAuthNumber = $this->xmlData["CardAuthNumber"];
                $this->myResultTrxnCardRefNumber = $this->xmlData["CardRefNumber"];
                $this->myResultTrxnCardType = $this->xmlData["CardType"];
                $this->myResultTrxnIPResult = $this->xmlData["IPResult"];
                $this->myResultTrxnIPCountry = $this->xmlData["IPCountry"];
                $this->myResultTrxnIPRegion = $this->xmlData["IPRegion"];
                $this->myResultTrxnIPCity = $this->xmlData["IPCity"];
                $this->myError = 0;
                $this->myErrorMessage = "";
            } else {
                $errorCode = xml_get_error_code($this->parser);
                $this->myError = $errorCode + PSIGATE_XML_ERROR_OFFSET;
                $this->myErrorMessage = xml_error_string($errorCode);
            }
            xml_parser_free($this->parser);
        } else {
            $this->myError = curl_errno($ch) + PSIGATE_CURL_ERROR_OFFSET;
            $this->myErrorMessage = curl_error($ch);
        }
        curl_close($ch);
        return $this->getError();
    }
}
function psigate_activate()
{
    defineGatewayField("psigate", "text", "storeid", "", "Store ID", "20", "");
    defineGatewayField("psigate", "text", "passphrase", "", "Pass Phrase", "20", "");
    defineGatewayField("psigate", "yesno", "testmode", "", "Test Mode", "", "");
}
function psigate_capture($params)
{
    global $remote_ip;
    $psi = new PsiGatePayment();
    if ($params["testmode"] == "on") {
        $psi->setGatewayURL("https://dev.psigate.com:" . PSIGATE_PORT_TEST_HIGH_SEC . "/Messenger/XMLMessenger");
    } else {
        $psi->setGatewayURL("https://secure.psigate.com:" . PSIGATE_PORT_PRODUCTION_HIGH_SEC . "/Messenger/XMLMessenger");
    }
    $psi->setStoreID($params["storeid"]);
    $psi->setPassPhrase($params["passphrase"]);
    $psi->setOrderID($params["invoiceid"]);
    $psi->setPaymentType("CC");
    $psi->setCardAction("0");
    $psi->setSubTotal($params["amount"]);
    $psi->setCardNumber($params["cardnum"]);
    $psi->setCardExpMonth(substr($params["cardexp"], 0, 2));
    $psi->setCardExpYear(substr($params["cardexp"], 2, 2));
    $psi->setUserID($params[""]);
    $psi->setBname($params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"]);
    $psi->setBcompany($params["clientdetails"]["companyname"]);
    $psi->setBaddress1($params["clientdetails"]["address1"]);
    $psi->setBaddress2($params["clientdetails"]["address2"]);
    $psi->setBcity($params["clientdetails"]["city"]);
    $psi->setBprovince($params["clientdetails"]["state"]);
    $psi->setBpostalCode($params["clientdetails"]["postcode"]);
    $psi->setBcountry($params["clientdetails"]["country"]);
    $psi->setSname($params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"]);
    $psi->setScompany($params["clientdetails"]["companyname"]);
    $psi->setSaddress1($params["clientdetails"]["address1"]);
    $psi->setSaddress2($params["clientdetails"]["address2"]);
    $psi->setScity($params["clientdetails"]["city"]);
    $psi->setSprovince($params["clientdetails"]["state"]);
    $psi->setSpostalCode($params["clientdetails"]["postcode"]);
    $psi->setScountry($params["clientdetails"]["country"]);
    $psi->setPhone($params["clientdetails"]["phonenumber"]);
    $psi->setEmail($params["clientdetails"]["email"]);
    $psi->setComments("");
    $psi->setCustomerIP($remote_ip);
    if ($params["cccvv"]) {
        $psi->setCardIDCode("1");
        $psi->setCardIDNumber($params["cccvv"]);
        $psi->setRecurring("C");
        $psi->setIteration("1");
    } else {
        $psi->setRecurring("Y");
        $psi->setIteration("2");
    }
    $psi_xml_error = $psi->doPayment() != PSIGATE_TRANSACTION_OK;
    $desc = "Action => Capture\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\n";
    $desc .= "Transaction Time => " . $psi->myResultTrxnTransTime . "\n";
    $desc .= "Order ID => " . $psi->myResultTrxnOrderID . "\n";
    $desc .= "Approved => " . $psi->myResultTrxnApproved . "\n";
    $desc .= "Return Code => " . $psi->myResultTrxnReturnCode . "\n";
    $desc .= "Error Message => " . $psi->myResultTrxnErrMsg . "\n";
    $desc .= "Total => " . $psi->myResultTrxnFullTotal . "\n";
    $desc .= "Payment Type => " . $psi->myResultTrxnPaymentType . "\n";
    $desc .= "Card Number => " . $psi->myResultTrxnCardNumber . "\n";
    $desc .= "Expiry Month => " . $psi->myResultTrxnCardExpMonth . "\n";
    $desc .= "Expiry Year => " . $psi->myResultTrxnCardExpYear . "\n";
    $desc .= "Reference Number => " . $psi->myResultTrxnTransRefNumber . "\n";
    $desc .= "Card ID Result => " . $psi->myResultTrxnCardIDResult . "\n";
    $desc .= "AVS Result => " . $psi->myResultTrxnAVSResult . "\n";
    $desc .= "Card Auth Number => " . $psi->myResultTrxnCardAuthNumber . "\n";
    $desc .= "Card Ref Number => " . $psi->myResultTrxnCardRefNumber . "\n";
    $desc .= "Card Type => " . $psi->myResultTrxnCardType . "\n";
    $desc .= "IP Result => " . $psi->myResultTrxnIPResult . "\n";
    $desc .= "IP Country => " . $psi->myResultTrxnIPCountry . "\n";
    $desc .= "IP Region => " . $psi->myResultTrxnIPRegion . "\n";
    $desc .= "IP City => " . $psi->myResultTrxnIPCity . "\n";
    $desc .= "Error => " . $psi->myError . "\n";
    $desc .= "Error Message => " . $psi->myErrorMessage . "\n";
    if ($psi->myResultTrxnApproved == "APPROVED") {
        return array("status" => "success", "transid" => $psi->myResultTrxnTransRefNumber, "rawdata" => $desc);
    }
    if ($psi->myResultTrxnApproved == "DECLINED") {
        return array("status" => "declined", "rawdata" => $desc);
    }
    return array("status" => "error", "rawdata" => $desc);
}
function psigate_refund($params)
{
    global $remote_ip;
    $psi = new PsiGatePayment();
    if ($params["testmode"] == "on") {
        $psi->setGatewayURL("https://dev.psigate.com:" . PSIGATE_PORT_TEST_HIGH_SEC . "/Messenger/XMLMessenger");
    } else {
        $psi->setGatewayURL("https://secure.psigate.com:" . PSIGATE_PORT_PRODUCTION_HIGH_SEC . "/Messenger/XMLMessenger");
    }
    $psi->setStoreID($params["storeid"]);
    $psi->setPassPhrase($params["passphrase"]);
    $psi->setOrderID($params["invoiceid"]);
    $psi->setPaymentType("CC");
    $psi->setCardAction("3");
    $psi->setSubTotal($params["amount"]);
    $psi_xml_error = $psi->doPayment() != PSIGATE_TRANSACTION_OK;
    $desc = "Action => Refund\nClient => " . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\n";
    $desc .= "Transaction Time => " . $psi->myResultTrxnTransTime . "\n";
    $desc .= "Order ID => " . $psi->myResultTrxnOrderID . "\n";
    $desc .= "Approved => " . $psi->myResultTrxnApproved . "\n";
    $desc .= "Return Code => " . $psi->myResultTrxnReturnCode . "\n";
    $desc .= "Error Message => " . $psi->myResultTrxnErrMsg . "\n";
    $desc .= "Total => " . $psi->myResultTrxnFullTotal . "\n";
    $desc .= "Payment Type => " . $psi->myResultTrxnPaymentType . "\n";
    $desc .= "Card Number => " . $psi->myResultTrxnCardNumber . "\n";
    $desc .= "Expiry Month => " . $psi->myResultTrxnCardExpMonth . "\n";
    $desc .= "Expiry Year => " . $psi->myResultTrxnCardExpYear . "\n";
    $desc .= "Reference Number => " . $psi->myResultTrxnTransRefNumber . "\n";
    $desc .= "IP Result => " . $psi->myResultTrxnIPResult . "\n";
    $desc .= "IP Country => " . $psi->myResultTrxnIPCountry . "\n";
    $desc .= "IP Region => " . $psi->myResultTrxnIPRegion . "\n";
    $desc .= "IP City => " . $psi->myResultTrxnIPCity . "\n";
    $desc .= "Error => " . $psi->myError . "\n";
    $desc .= "Error Message => " . $psi->myErrorMessage . "\n";
    if ($psi->myResultTrxnApproved == "APPROVED") {
        return array("status" => "success", "transid" => $psi->myResultTrxnTransRefNumber, "rawdata" => $desc);
    }
    if ($psi->myResultTrxnApproved == "DECLINED") {
        return array("status" => "declined", "rawdata" => $desc);
    }
    return array("status" => "error", "rawdata" => $desc);
}

?>