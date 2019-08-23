<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

abstract class TPPW_API
{
    protected $url = NULL;
    protected $params = NULL;
    protected static $sessionId = false;
    protected function __construct($script, $params)
    {
        $host = gethostbyname(TPPW_APIUtils::$API_HOST);
        if ($host === TPPW_APIUtils::$API_HOST) {
            $host = TPPW_APIUtils::$API_IP;
        }
        $this->url = "https://" . $host . TPPW_APIUtils::$API_PATH . (string) $script;
        $this->params = $params;
        $message = "<h3>" . TPPW_APIUtils::$BRAND_NAME . " API Request</h3>";
        $message .= "<p><strong>Params:</strong></p>";
        $message .= "<p><pre>" . print_r($params, true) . "</pre></p>";
        if (function_exists("logModuleCall")) {
            logModuleCall(TPPW_APIUtils::$MODULE_NAME, $this->url, $this->params, "");
        }
        if ($params["Debug"]) {
            logactivity($message);
        }
    }
    protected function getPostParams($object, $action)
    {
        return array("Object" => $object, "Action" => $action);
    }
    protected function execute($postParams, $existingSession = true)
    {
        if ($existingSession) {
            if (self::$sessionId === false) {
                $api = new TPPW_AuthAPI($this->params);
                $acountId = $this->params["AccountNo"];
                $userId = $this->params["Login"];
                $password = $this->params["Password"];
                $results = $api->authenticate($acountId, $userId, $password);
                if (!$results->isSuccess()) {
                    return $results;
                }
                self::$sessionId = $results->getResponse();
            }
            $postParams["SessionID"] = self::$sessionId;
        }
        $postParams = array_merge(TPPW_APIUtils::$API_COMMON_PARAMS, $postParams);
        $postFields = "";
        foreach ($postParams as $key => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    $postFields .= $key . "=" . urlencode($value) . "&";
                }
            } else {
                $postFields .= $key . "=" . urlencode($values) . "&";
            }
        }
        $conn = curl_init();
        curl_setopt($conn, CURLOPT_URL, $this->url);
        curl_setopt($conn, CURLOPT_POST, 1);
        curl_setopt($conn, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($conn, CURLOPT_HEADER, false);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($conn);
        $error = curl_error($conn);
        $errorNum = curl_errno($conn);
        $stats = curl_getinfo($conn);
        curl_close($conn);
        $message = "<p><strong>API Query:</strong> <pre>" . $this->url . "?" . $postFields . "</pre></p>";
        $message .= "<p><strong>Response:</strong> <pre>" . $response . "</pre></p>";
        $message .= "<p><strong>Stats:</strong> <pre>" . print_r($stats, true) . "</pre></p>";
        if (function_exists("logModuleCall")) {
            logModuleCall(TPPW_APIUtils::$MODULE_NAME, $this->url, $postFields, $response);
        }
        if ($this->params["Debug"]) {
            logactivity($message);
        }
        if ($errorNum) {
            return new TPPW_APIResult("ERR-CURL: " . $errorNum . ", " . $error);
        }
        return new TPPW_APIResult($response);
    }
    protected function getDomain()
    {
        return $this->params["sld"] . "." . $this->params["tld"];
    }
    protected function getTLD()
    {
        return $this->params["tld"];
    }
    protected function getClientId()
    {
        return $this->params["userid"];
    }
}
class TPPW_AuthAPI extends TPPW_API
{
    public function __construct($params)
    {
        parent::__construct("auth.pl", $params);
    }
    public function authenticate($accountNo, $userId, $password)
    {
        $postParams = array("AccountNo" => $accountNo, "UserId" => $userId, "Password" => $password);
        return $this->execute($postParams, false);
    }
}
class TPPW_QueryAPI extends TPPW_API
{
    public static $domainWhois = false;
    public function __construct($params)
    {
        parent::__construct("query.pl", $params);
    }
    public function domainWhois()
    {
        if (!self::$domainWhois instanceof TPPW_APIResult) {
            $postParams = $this->getPostParams("Domain", "Details");
            $postParams["Domain"] = $this->getDomain();
            self::$domainWhois = $this->execute($postParams);
        }
        return self::$domainWhois;
    }
    public function domainSync($domains = NULL)
    {
        $postParams = $this->getPostParams("Domain", "Sync");
        if (!empty($domains)) {
            $postParams["Domain"] = $domains;
        } else {
            $postParams["Domain"] = $this->getDomain();
        }
        return $this->execute($postParams);
    }
    public function domainPassword($domains)
    {
        $postParams = $this->getPostParams("Domain", "SyncPass");
        $postParams["Domain"] = $domains;
        $postParams["resetIfVirgin"] = "true";
        return $this->execute($postParams);
    }
    public function accountInfo()
    {
        $postParams = $this->getPostParams("Account", "Details");
        $postParams["ExternalAccountID"] = $this->getClientId();
        return $this->execute($postParams);
    }
    public function orderStatus($workflow = true)
    {
        $postParams = $this->getPostParams("Order", "OrderStatus");
        if ($workflow) {
            $postParams["OrderID"] = TPPW_APIUtils::getWorkflowID($this->params["domainid"]);
            if ($postParams["OrderID"] === false) {
                return new TPPW_APIResult("ERR: 889");
            }
        } else {
            $postParams["Domain"] = $this->getDomain();
        }
        return $this->execute($postParams);
    }
    public function checkTransferability()
    {
        $postParams = $this->getPostParams("Domain", "Transfer");
        $postParams["Domain"] = $this->getDomain();
        $postParams["DomainPassword"] = TPPW_APIUtils::getValue($this->params, "transfersecret", "");
        return $this->execute($postParams);
    }
}
class TPPW_ResourceAPI extends TPPW_API
{
    public function __construct($params)
    {
        parent::__construct("resource.pl", $params);
    }
    public function domainAdd()
    {
        $postParams = $this->getPostParams("Domain", "AddDomain");
        $postParams["Domain"] = $this->params["domain"];
        $clientDetails = $this->params["clientsdetails"];
        $postParams["OrganisationName"] = $clientDetails["companyname"];
        $postParams["FirstName"] = $clientDetails["firstname"];
        $postParams["LastName"] = $clientDetails["lastname"];
        $postParams["Address1"] = $clientDetails["address1"];
        $postParams["Address2"] = $clientDetails["address2"];
        $postParams["City"] = $clientDetails["city"];
        $postParams["Region"] = $clientDetails["state"];
        $postParams["PostalCode"] = $clientDetails["postcode"];
        $postParams["CountryCode"] = $clientDetails["country"];
        $postParams["Email"] = $clientDetails["email"];
        $postParams["PhoneNumber"] = $clientDetails["phonenumber"];
        $postParams = array_merge($postParams, $this->getDomainCredentials());
        return $this->execute($postParams);
    }
    public function domainUpgrade()
    {
        $postParams = $this->getPostParams("Domain", "UpgradeDomainProduct");
        $postParams["Domain"] = $this->params["domain"];
        $postParams["ProductId"] = $this->params["ProductId"];
        return $this->execute($postParams);
    }
    public function domainSuspendProduct()
    {
        $postParams = $this->getPostParams("Domain", "SuspendDomainProduct");
        $postParams["Domain"] = $this->params["domain"];
        return $this->execute($postParams);
    }
    public function domainUnSuspendProduct()
    {
        $postParams = $this->getPostParams("Domain", "UnSuspendDomainProduct");
        $postParams["Domain"] = $this->params["domain"];
        return $this->execute($postParams);
    }
    public function domainCancelProduct()
    {
        $postParams = $this->getPostParams("Domain", "CancelDomainProduct");
        $postParams["Domain"] = $this->params["domain"];
        $postParams["ProductId"] = $this->params["ProductId"];
        return $this->execute($postParams);
    }
    private function getDomainCredentials()
    {
        if ($accountOption == "1") {
            $accountOption = "EXTERNAL";
            $accountId = $this->getClientId();
        } else {
            if ($accountOption == "2") {
                $accountOption = "CONSOLE";
            } else {
                $accountOption = "DEFAULT";
                $accountId = "";
            }
        }
        return array("AccountOption" => $accountOption, "AccountID" => $accountId);
    }
}
class TPPW_OrderAPI extends TPPW_API
{
    public function __construct($params)
    {
        parent::__construct("order.pl", $params);
    }
    private function saveWorkflowId(TPPW_APIResult $result)
    {
        if ($result->isSuccess()) {
            TPPW_APIUtils::setWorkflowID($this->params["domainid"], $result->getResponse());
        }
        return $result;
    }
    public function domainRegister()
    {
        $contactIds = $this->createContacts();
        if ($contactIds instanceof TPPW_APIResult) {
            return $contactIds;
        }
        $postParams = $this->getPostParams("Domain", "Create");
        $postParams["Domain"] = $this->getDomain();
        $postParams["Period"] = $this->getPeriod();
        $postParams["Host"] = $this->getNameServers();
        $postParams = array_merge($postParams, $this->getDomainCredentials());
        $postParams = array_merge($postParams, $contactIds);
        $postParams = array_merge($postParams, $this->getEligibilityDetails());
        return $this->saveWorkflowId($this->execute($postParams));
    }
    public function domainRenewal()
    {
        $postParams = $this->getPostParams("Domain", "Renewal");
        $postParams["Domain"] = $this->getDomain();
        $postParams["Period"] = $this->getPeriod();
        return $this->saveWorkflowId($this->execute($postParams));
    }
    public function domainTransfer()
    {
        $postParams = $this->getPostParams("Domain", "TransferRequest");
        $postParams["Domain"] = $this->getDomain();
        $postParams["Period"] = $this->getPeriod();
        if (in_array($this->params["tld"], TPPW_APIUtils::$CHECK_PERIOD_BEFORE_XFER)) {
            $queryAPI = new TPPW_QueryAPI($this->params);
            $queryResult = $queryAPI->checkTransferability();
            if ($queryResult->isSuccess()) {
                $postParams["Period"] = $queryResult->get("Maximum");
            } else {
                return $queryResult;
            }
        }
        $contactIds = $this->createContacts();
        if ($contactIds instanceof TPPW_APIResult) {
            return $contactIds;
        }
        $postParams["DomainPassword"] = TPPW_APIUtils::getValue($this->params, "transfersecret", "");
        $postParams = array_merge($postParams, $this->getDomainCredentials());
        $postParams = array_merge($postParams, $contactIds);
        return $this->saveWorkflowId($this->execute($postParams));
    }
    public function domainDelegation()
    {
        $postParams = $this->getPostParams("Domain", "UpdateHosts");
        $postParams["Domain"] = $this->getDomain();
        $postParams["AddHost"] = $this->getNameServers();
        $postParams["RemoveHost"] = "ALL";
        return $this->execute($postParams);
    }
    public function domainLock()
    {
        $postParams = $this->getPostParams("Domain", "UpdateDomainLock");
        $postParams["Domain"] = $this->getDomain();
        $lockEnabled = $this->params["lockenabled"];
        $postParams["DomainLock"] = !$lockEnabled || $lockEnabled == "unlocked" ? "Unlock" : "Lock";
        return $this->execute($postParams);
    }
    public function hostCreate()
    {
        $postParams = $this->getPostParams("Domain", "CreateNameServer");
        $postParams["Domain"] = $this->getDomain();
        $postParams["NameServerPrefix"] = $this->getHost();
        $postParams["NameServerIP"] = $this->getHostIPs("ipaddress");
        return $this->execute($postParams);
    }
    public function hostUpdate()
    {
        $postParams = $this->getPostParams("Domain", "ChangeNameServer");
        $postParams["Domain"] = $this->getDomain();
        $postParams["NameServerPrefix"] = $this->getHost();
        $postParams["NameServerIP"] = $this->getHostIPs("newipaddress");
        return $this->execute($postParams);
    }
    public function hostRemove()
    {
        $postParams = $this->getPostParams("Domain", "DeleteNameServer");
        $postParams["Domain"] = $this->getDomain();
        $postParams["NameServerPrefix"] = $this->getHost();
        return $this->execute($postParams);
    }
    public function contactsUpdate()
    {
        $contactIds = $this->createContacts(false);
        if ($contactIds instanceof TPPW_APIResult) {
            return $contactIds;
        }
        $postParams = $this->getPostParams("Domain", "UpdateContacts");
        $postParams["Domain"] = $this->getDomain();
        $postParams = array_merge($postParams, $contactIds);
        return $this->execute($postParams);
    }
    private function createContacts($all = true)
    {
        $contacts = TPPW_APIUtils::getValue($this->params, "contactdetails", false);
        if ($contacts === false) {
            $contacts = array("Registrant" => $this->getContact(), "Admin" => $this->getContact("admin"));
        }
        $contactIds = array();
        $defaultContactId = false;
        foreach (TPPW_APIUtils::$CONTACT_TYPES as $apiType => $moduleType) {
            $contact = TPPW_APIUtils::getValue($contacts, $moduleType, false);
            if ($contact !== false) {
                if (array_key_exists("First Name", $contact)) {
                    foreach (TPPW_APIUtils::$TRANSFORM_CONTACT_FIELDS as $wrongKeyName => $correctKeyName) {
                        $contact[$correctKeyName] = $contact[$wrongKeyName];
                        unset($contact[$wrongKeyName]);
                    }
                }
                $results = $this->createContact($contact);
                if ($results->isSuccess()) {
                    $key = $apiType . "ContactID";
                    $contactId = $results->getResponse();
                    $contactIds[$key] = $contactId;
                    if ($defaultContactId === false) {
                        $defaultContactId = $contactId;
                    }
                } else {
                    return $results;
                }
            }
        }
        if ($all && $defaultContactId !== false) {
            foreach (array_keys(TPPW_APIUtils::$CONTACT_TYPES) as $apiType) {
                $key = $apiType . "ContactID";
                if (!array_key_exists($key, $contactIds)) {
                    $contactIds[$key] = $defaultContactId;
                }
            }
        }
        return $contactIds;
    }
    private function createContact($contact)
    {
        $postParams = $this->getPostParams("Contact", "Create");
        $postParams = array_merge($postParams, $contact);
        return $this->execute($postParams);
    }
    private function getContact($type = "")
    {
        $contact = array();
        foreach (TPPW_APIUtils::$CONTACT_FIELDS as $apiField => $moduleField) {
            $key = $type . $moduleField;
            $value = TPPW_APIUtils::getValue($this->params, $key, "");
            $contact[$apiField] = $value;
        }
        return $contact;
    }
    private function getProductId()
    {
        return TPPW_APIUtils::getValue($this->params, "ConsoleProductId", "");
    }
    private function getPeriod()
    {
        return TPPW_APIUtils::getValue($this->params, "regperiod", "");
    }
    private function getHost()
    {
        return str_replace("." . $this->getDomain(), NULL, TPPW_APIUtils::getValue($this->params, "nameserver", ""));
    }
    private function getHostIPs($key)
    {
        $ips = TPPW_APIUtils::getValue($this->params, $key, "");
        return preg_split("/\\s*,\\s*/", $ips);
    }
    private function getNameServers()
    {
        $nameServers = array();
        $keys = array("ns1", "ns2", "ns3", "ns4", "ns5", "ns6", "ns7", "ns8", "ns9");
        foreach ($keys as $key) {
            $value = TPPW_APIUtils::getValue($this->params, $key, false);
            if ($value !== false) {
                $nameServers[] = $value;
            }
        }
        return $nameServers;
    }
    private function getEligibilityDetails()
    {
        $eligibilityForm = TPPW_APIUtils::getValue($this->params, "additionalfields", false);
        if ($eligibilityForm) {
            if (strpos($this->getTLD(), ".au") !== false) {
                $registrantName = TPPW_APIUtils::getValue($eligibilityForm, "Registrant Name", "");
                $registrantId = TPPW_APIUtils::getValue($eligibilityForm, "Registrant ID", "");
                $registrantIdType = TPPW_APIUtils::getValueConverted($eligibilityForm, "Registrant ID Type", TPPW_APIUtils::$ELIGIBILITY_ID_TYPES, "");
                $eligibilityName = TPPW_APIUtils::getValue($eligibilityForm, "Eligibility Name", "");
                $eligibilityId = TPPW_APIUtils::getValue($eligibilityForm, "Eligibility ID", "");
                $eligibilityIdType = TPPW_APIUtils::getValueConverted($eligibilityForm, "Eligibility ID Type", TPPW_APIUtils::$ELIGIBILITY_ID_TYPES, "");
                $eligibilityType = TPPW_APIUtils::getValueConverted($eligibilityForm, "Eligibility Type", TPPW_APIUtils::$ELIGIBILITY_TYPES, "");
                $eligibilityReason = TPPW_APIUtils::getValueConverted($eligibilityForm, "Eligibility Reason", TPPW_APIUtils::$ELIGIBILITY_REASONS, "");
                if (!empty($registrantIdType) && empty($eligibilityIdType)) {
                    $eligibilityIdType = $registrantIdType;
                    $eligibilityType = TPPW_APIUtils::$ELIGIBILITY_TYPES["Company"];
                    $eligibilityId = empty($eligibilityId) ? $registrantId : $eligibilityId;
                }
                return array("RegistrantName" => $registrantName, "EligibilityName" => $eligibilityName, "EligibilityID" => $eligibilityId, "EligibilityIDType" => $eligibilityIdType, "EligibilityType" => $eligibilityType, "EligibilityReason" => $eligibilityReason);
            }
            if (strpos($this->getTLD(), "asia") !== false) {
                $country = $this->params["country"];
                $legalType = TPPW_APIUtils::getValueConverted($eligibilityForm, "Legal Type", TPPW_APIUtils::$ASIA_LEGAL_TYPES, "");
                $identityForm = TPPW_APIUtils::getValueConverted($eligibilityForm, "Identity Form", TPPW_APIUtils::$ASIA_IDENTITY_FORMS, "other");
                $identityNumber = TPPW_APIUtils::getValue($eligibilityForm, "Identity Number", "");
                if ($identityForm == "other") {
                    $otherIdentityForm = TPPW_APIUtils::getValue($eligibilityForm, "Identity Form", "");
                }
                return array("Country" => $country, "LegalType" => $legalType, "IdentityForm" => $identityForm, "IdentityNumber" => $identityNumber, "OtherIdentityForm" => $otherIdentityForm);
            }
        }
        return array();
    }
    private function getDomainCredentials()
    {
        $accountSetting = TPPW_APIUtils::getValue($this->params, "DefaultAccount", "");
        $accountSetting = strtoupper($accountSetting);
        $accountId = NULL;
        $accountOption = "CONSOLE";
        switch ($accountSetting) {
            case NULL:
            case "":
                $accountOption = "EXTERNAL";
                $accountId = $this->getClientId();
                break;
            case "DEFAULT":
                $accountOption = "DEFAULT";
                break;
            default:
                $accountId = $accountSetting;
                break;
        }
        return array("AccountOption" => $accountOption, "AccountID" => $accountId);
    }
}
class TPPW_APIResult
{
    private $response = NULL;
    private $success = false;
    private $params = array();
    private $error = NULL;
    private $errorCode = NULL;
    public function __construct($response)
    {
        $this->response = $response;
        $responseTemp = preg_split("/\\s*:\\s*/", $response);
        $index = array_search("OK", $responseTemp);
        if ($index !== false) {
            $this->success = true;
            $responseTemp = array_splice($responseTemp, $index + 1);
            $response = trim(implode(":", $responseTemp));
            $this->response = $response;
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $index = strpos($line, "=");
                if ($index !== false) {
                    $key = trim(substr($line, 0, $index));
                    $value = trim(substr($line, $index + 1));
                    $this->params[$key][] = $value;
                }
            }
        } else {
            $index = array_search("ERR", $responseTemp);
            if ($index !== false) {
                $responseTemp = array_splice($responseTemp, $index + 1);
                $response = implode(":", $responseTemp);
                $this->error = TPPW_APIUtils::$ERROR_UNKNOWN . ": " . $response;
                if ($response && preg_match("/^\\d+/", $response)) {
                    $errorTemp = preg_split("/\\s*,\\s*/", $response);
                    $errorCode = $errorTemp[0];
                    $this->errorCode = $errorCode;
                    $this->error = "[" . $errorCode . "] ";
                    $this->error .= TPPW_APIUtils::getValue(TPPW_APIUtils::$ERROR_CODES, $errorCode, TPPW_APIUtils::$ERROR_UNKNOWN);
                    $this->error .= ": " . implode(", ", array_slice($errorTemp, 1));
                }
            }
        }
        if (!$this->success && !$this->error) {
            $this->error = TPPW_APIUtils::$ERROR_UNKNOWN;
        }
    }
    public function getResponse()
    {
        return $this->response;
    }
    public function isSuccess()
    {
        return $this->success;
    }
    public function getParams($prefix)
    {
        $params = array();
        $keys = array_keys($this->params);
        foreach ($keys as $key) {
            if (preg_match("/^" . $prefix . "/", $key)) {
                $value = $this->get($key);
                $key = substr($key, strlen($prefix));
                $params[$key] = $value;
            }
        }
        return $params;
    }
    public function get($key)
    {
        $value = $this->getArray($key);
        return empty($value) ? "" : $value[0];
    }
    public function getArray($key)
    {
        return TPPW_APIUtils::getValue($this->params, $key, array());
    }
    public function getModuleResults()
    {
        return $this->success ? "" : $this->getModuleError();
    }
    public function getModuleError()
    {
        return array("error" => $this->error);
    }
    public function getModuleErrorCode()
    {
        return (int) $this->errorCode;
    }
}
class TPPW_APIUtils
{
    public static $BRAND_NAME = "TPP Wholesale";
    public static $MODULE_NAME = "tppwregistrar";
    public static $API_IP = "114.141.204.99";
    public static $API_HOST = "theconsole.tppwholesale.com.au";
    public static $API_PATH = "/api/";
    public static $URL_SUPPORT = "http://www.tppwholesale.com.au/support/category/getting-started/whmcs";
    public static $API_COMMON_PARAMS = array("Requester" => "WHMCS", "Version" => "2.4", "Type" => "Domains");
    public static $ERROR_UNKNOWN = "Unknown error occurred";
    public static $ERROR_CODES = array("100" => "Missing parameters", "102" => "Authentication failed", "105" => "Request is coming from incorrect IP address", "202" => "Invalid API Type", "203" => "API call has not been implemented yet", "301" => "Invalid order ID", "302" => "Domain name is either invalid or not supplied", "303" => "Domain prices are not setted up", "304" => "Domain registration failed", "305" => "Domain renewal failed", "306" => "Domain transfer failed", "307" => "Incorrect auth code provided", "309" => "Invalid domain extension", "311" => "Domain does not exist in your reseller account", "312" => "Invalid username/password", "313" => "Account does not exist in your reseller profile", "401" => "Failed to connect to registry, please retry", "500" => "Prepaid account does not have enough funds to cover the cost of this order", "501" => "Invalid credit card type", "502" => "Invalid credit card number", "503" => "Invalid credit card expiry date", "505" => "Credit card transaction failed", "600" => "Failed to create/update contact", "601" => "Failed to create order", "602" => "Invalid hosts supplied", "603" => "Invalid eligibility fields supplied", "604" => "Invalid IP Address", "610" => "Failed to connect to registry, please retry", "611" => "Domain renewal/transfer failed", "612" => "Locking is not available for this domain", "614" => "Failed to lock/unlock domain", "615" => "Domain delegation failed", "700" => "Invalid Product", "701" => "Domain already exists", "888" => "Order is still being processed. Current Status", "889" => "No OrderID for this domain name was found");
    public static $CONTACT_TYPES = array("Owner" => "Registrant", "Administration" => "Admin", "Technical" => "Tech", "Billing" => "Billing");
    public static $CHECK_PERIOD_BEFORE_XFER = array("com.au", "net.au", "org.au", "asn.au", "id.au");
    public static $CONTACT_FIELDS = array("FirstName" => "firstname", "LastName" => "lastname", "Address1" => "address1", "Address2" => "address2", "City" => "city", "Region" => "state", "PostalCode" => "postcode", "CountryCode" => "country", "Email" => "email", "PhoneNumber" => "phonenumber");
    public static $TRANSFORM_CONTACT_FIELDS = array("First Name" => "FirstName", "Last Name" => "LastName", "Address 1" => "Address1", "Address 2" => "Address2", "Postcode" => "PostalCode", "Country" => "CountryCode", "Phone Number" => "PhoneNumber", "Organisation Name" => "OrganisationName", "Email Address" => "Email");
    public static $ASIA_LEGAL_TYPES = array("naturalPerson" => "naturalPerson", "corporation" => "corporation", "cooperative" => "cooperative", "partnership" => "partnership", "government" => "government", "politicalParty" => "politicalParty", "society" => "society", "institution" => "institution");
    public static $ASIA_IDENTITY_FORMS = array("passport" => "passport", "politicalPartyRegistry" => "politicalPartyRegistry", "societyRegistry" => "societiesRegistry", "legislation" => "legislation", "certificate" => "certificate");
    public static $ELIGIBILITY_ID_TYPES = array("ACN" => 1, "ACT BN" => 2, "NSW BN" => 3, "NT BN" => 4, "QLD BN" => 5, "SA BN" => 6, "TAS BN" => 7, "VIC BN" => 8, "WA BN" => 9, "Trademark" => 10, "Other" => 11, "ABN" => 12, "Australian Company Number (ACN)" => 1, "ACT Business Number" => 2, "NSW Business Number" => 3, "NT Business Number" => 4, "QLD Business Number" => 5, "SA Business Number" => 6, "TAS Business Number" => 7, "VIC Business Number" => 8, "WA Business Number" => 9, "Trademark (TM)" => 10, "Other - Used to record an Incorporated Association number" => 11, "Australian Business Number (ABN)" => 12, "Business Registration Number" => 11);
    public static $ELIGIBILITY_TYPES = array("Charity" => 1, "Citizen/Resident" => 2, "Club" => 3, "Commercial Statutory Body" => 4, "Company" => 5, "Incorporated Association" => 6, "Industry Body" => 8, "Non-profit Organisation" => 9, "Other" => 10, "Partnership" => 11, "Pending TM Owner" => 12, "Political Party" => 13, "Registered Business" => 14, "Religious/Church Group" => 15, "Sole Trader" => 16, "Trade Union" => 17, "Trademark Owner" => 18, "Child Care Centre" => 19, "Government School" => 20, "Higher Education Institution" => 21, "National Body" => 22, "Non-Government School" => 23, "Pre-school" => 24, "Research Organisation" => 25, "Training Organisation" => 26);
    public static $ELIGIBILITY_REASONS = array("Domain name is an Exact Match Abbreviation or Acronym of your Entity or Trading Name." => 1, "Close and substantial connection between the domain name and the operations of your Entity." => 2);
    public static function getValue($array, $key, $defaultValue)
    {
        return array_key_exists($key, $array) ? $array[$key] : $defaultValue;
    }
    public static function getValueConverted($array, $key, $valueMap, $defaultValue = false)
    {
        $value = TPPW_APIUtils::getValue($array, $key, false);
        return $value !== false ? TPPW_APIUtils::getValue($valueMap, $value, $defaultValue) : $defaultValue;
    }
    public static function getWorkflowID($domainID)
    {
        $q = full_query("select value from tbldomainsadditionalfields where domainid = '" . (int) $domainID . "' and name = 'TPPOrderID' LIMIT 0,1;");
        $q = mysql_fetch_array($q);
        if (!$q) {
            return false;
        }
        return $q[0];
    }
    public static function setWorkflowID($domainID, $wfID)
    {
        if (TPPW_APIUtils::getWorkflowID($domainID) === false) {
            return full_query("insert into tbldomainsadditionalfields values ('NULL', '" . (int) $domainID . "', 'TPPOrderID', '" . db_escape_string($wfID) . "');");
        }
        return full_query("update tbldomainsadditionalfields set value = '" . db_escape_string($wfID) . "' where domainid = '" . (int) $domainID . "' and name = 'TPPOrderID';");
    }
}
function tppwregistrar_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "TPP Wholesale"), "AccountNo" => array("Type" => "text", "Size" => "20", "Description" => "Enter your " . TPPW_APIUtils::$BRAND_NAME . " API Account Number"), "Login" => array("Type" => "text", "Size" => "20", "Description" => "Enter your " . TPPW_APIUtils::$BRAND_NAME . " API Login"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your " . TPPW_APIUtils::$BRAND_NAME . " API Password"), "DefaultAccount" => array("Type" => "text", "Size" => "20", "Description" => "<ul><li>Leave this field blank if you are happy for WHMCS to create client accounts on the TPP Wholesale systems and group each client's domain registrations within them. </li><li>&nbsp;</li><li>If you'd prefer to have all the domain names simply grouped in one account on the TPP Wholesale systems, enter that Account Reference here.</li></ul>"), "Debug" => array("Type" => "yesno", "Description" => "Tick this box to log API calls to WHMCS' \"Module Log\" section. (Version: " . TPPW_APIUtils::$API_COMMON_PARAMS["Version"] . ")"), "" => array("Description" => "<b>Need Help?</b> For assistance configuring WHMCS at TPP Wholesale, please see this <a target=\"_blank\" href=\"" . TPPW_APIUtils::$URL_SUPPORT . "\">support article</a>."));
    return $configarray;
}
function tppwregistrar_GetNameservers($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainWhois();
    if ($results->isSuccess()) {
        $nameServers = $results->getArray("Nameserver");
        $ns = array();
        $count = 1;
        foreach ($nameServers as $nameServer) {
            $ns["ns" . $count++] = $nameServer;
        }
        return $ns;
    } else {
        return $results->getModuleError();
    }
}
function tppwregistrar_SaveNameservers($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->domainDelegation();
    return $results->getModuleResults();
}
function tppwregistrar_GetRegistrarLock($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainWhois();
    if ($results->isSuccess()) {
        $lockStatus = $results->get("LockStatus");
        return $lockStatus == "2" ? "locked" : "unlocked";
    }
    return $results->getModuleError();
}
function tppwregistrar_SaveRegistrarLock($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->domainLock();
    return $results->getModuleResults();
}
function tppwregistrar_RegisterDomain($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->domainRegister();
    return $results->getModuleResults();
}
function tppwregistrar_TransferDomain($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->domainTransfer();
    return $results->getModuleResults();
}
function tppwregistrar_RenewDomain($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->domainRenewal();
    return $results->getModuleResults();
}
function tppwregistrar_GetContactDetails($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainWhois();
    if ($results->isSuccess()) {
        $contacts = array();
        foreach (TPPW_APIUtils::$CONTACT_TYPES as $apiPrefix => $modulePrefix) {
            $contact = $results->getParams($apiPrefix . "-");
            $contacts[$modulePrefix] = $contact;
        }
        return $contacts;
    } else {
        return $results->getModuleError();
    }
}
function tppwregistrar_SaveContactDetails($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->contactsUpdate();
    return $results->getModuleResults();
}
function tppwregistrar_GetEPPCode($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainWhois();
    if ($results->isSuccess()) {
        $domainPassword = $results->get("DomainPassword");
        return array("eppcode" => $domainPassword);
    }
    return $results->getModuleResults();
}
function tppwregistrar_TransferSync($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainSync();
    if ($results->isSuccess()) {
        return array("expirydate" => $results->get("ExpiryDate"), "active" => true);
    }
    if ($results->getModuleErrorCode() === 311) {
        $orderStatus = $api->orderStatus();
        if ($orderStatus->isSuccess() && stripos($orderStatus->getResponse(), "reject") !== false) {
            return array("failed" => true, "reason" => $orderStatus->getResponse());
        }
        return array();
    }
    return $results->getModuleError();
}
function tppwregistrar_Sync($params)
{
    $api = new TPPW_QueryAPI($params);
    $results = $api->domainSync();
    if ($results->isSuccess()) {
        $expiry = $results->get("ExpiryDate");
        date_default_timezone_set(@date_default_timezone_get());
        $expiryEpoch = strtotime($expiry);
        if ($expiryEpoch !== false) {
            if (time() < $expiryEpoch) {
                return array("expirydate" => $expiry, "active" => true);
            }
            return array("expirydate" => $expiry, "expired" => true);
        }
    } else {
        return $results->getModuleError();
    }
}
function tppwregistrar_checkOrder($params)
{
    $api = new TPPW_QueryAPI($params);
    $result = $api->orderStatus();
    if ($result->isSuccess()) {
        $data = $result->getResponse();
        if ($data === "Complete") {
            return array();
        }
        $res = new TPPW_APIResult("ERR: 888, " . $data . "\r\n");
        return $res->getModuleResults();
    }
    return $result->getModuleError();
}
function tppwregistrar_AdminCustomButtonArray($params)
{
    return array("Check Order Progress" => "checkOrder");
}
function tppwregistrar_RegisterNameserver($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->hostCreate();
    return $results->getModuleResults();
}
function tppwregistrar_ModifyNameserver($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->hostUpdate();
    return $results->getModuleResults();
}
function tppwregistrar_DeleteNameserver($params)
{
    $api = new TPPW_OrderAPI($params);
    $results = $api->hostRemove();
    return $results->getModuleResults();
}

?>