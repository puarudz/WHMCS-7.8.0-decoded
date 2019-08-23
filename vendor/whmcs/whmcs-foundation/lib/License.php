<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class License
{
    private $licensekey = "";
    private $localkey = false;
    private $keydata = NULL;
    private $salt = "";
    private $postmd5hash = "";
    private $localkeydays = "10";
    private $allowcheckfaildays = "5";
    private $useInternalLicensingMirror = false;
    private $debuglog = array();
    private $lastCurlError = NULL;
    const LICENSE_API_VERSION = "1.1";
    const LICENSE_API_HOSTS = array("a.licensing.whmcs.com", "b.licensing.whmcs.com", "c.licensing.whmcs.com", "d.licensing.whmcs.com", "e.licensing.whmcs.com", "f.licensing.whmcs.com");
    const STAGING_LICENSE_API_HOSTS = array("hou-1.licensing.web.staging.whmcs.com");
    const UNLICENSED_KEY = "LICENSE-REQUIRED";
    public function checkFile($value)
    {
        if ($value != "a896faf2c31f2acd47b0eda0b3fd6070958f1161") {
            throw new Exception\Fatal("File version mismatch. Please contact support.");
        }
        return $this;
    }
    public function setLicenseKey($licenseKey)
    {
        $this->licensekey = $licenseKey;
        return $this;
    }
    public function setLocalKey($localKey)
    {
        $this->decodeLocal($localKey);
        return $this;
    }
    public function setSalt($version, $hash)
    {
        if (empty($version) || empty($hash)) {
            throw new Exception("Unable to generate licensing salt");
        }
        $this->salt = sha1(sprintf("WHMCS%s%s%s", $version, "|-|", $hash));
        return $this;
    }
    public function useInternalValidationMirror()
    {
        $this->useInternalLicensingMirror = true;
        return $this;
    }
    protected function getHosts()
    {
        if ($this->useInternalLicensingMirror) {
            return self::STAGING_LICENSE_API_HOSTS;
        }
        return self::LICENSE_API_HOSTS;
    }
    public function getLicenseKey()
    {
        return $this->licensekey;
    }
    protected function getHostDomain()
    {
        $domain = defined("WHMCS_LICENSE_DOMAIN") ? WHMCS_LICENSE_DOMAIN : "";
        if (empty($domain) || $domain == "-") {
            throw new Exception("Unable to retrieve current server name. Please check PHP/vhost configuration and ensure SERVER_NAME is displaying appropriately via PHP Info.");
        }
        $this->debug("Host Domain: " . $domain);
        return $domain;
    }
    protected function getHostIP()
    {
        $ip = defined("WHMCS_LICENSE_IP") ? WHMCS_LICENSE_IP : "";
        $this->debug("Host IP: " . $ip);
        return $ip;
    }
    protected function getHostDir()
    {
        $directory = defined("WHMCS_LICENSE_DIR") ? WHMCS_LICENSE_DIR : "";
        $this->debug("Host Directory: " . $directory);
        return $directory;
    }
    private function getSalt()
    {
        return $this->salt;
    }
    protected function isLocalKeyValidToUse()
    {
        $licenseKey = $this->getKeyData("key");
        if (empty($licenseKey) || $licenseKey != $this->licensekey) {
            throw new Exception("License Key Mismatch in Local Key");
        }
        $originalcheckdate = $this->getCheckDate();
        $localmax = Carbon::now()->startOfDay()->addDays(2);
        if ($originalcheckdate->gt($localmax)) {
            throw new Exception("Original check date is in the future");
        }
    }
    protected function hasLocalKeyExpired()
    {
        $originalCheckDate = $this->getCheckDate();
        $localExpiryMax = Carbon::now()->startOfDay()->subDays($this->localkeydays);
        if (!$originalCheckDate || $originalCheckDate->lt($localExpiryMax)) {
            throw new Exception("Original check date is outside allowed validity period");
        }
    }
    protected function buildPostData()
    {
        $whmcs = \DI::make("app");
        $stats = json_decode($whmcs->get_config("SystemStatsCache"), true);
        if (!is_array($stats)) {
            $stats = array();
        }
        $stats = array_merge($stats, Environment\Environment::toArray());
        return array("licensekey" => $this->getLicenseKey(), "domain" => $this->getHostDomain(), "ip" => $this->getHostIP(), "dir" => $this->getHostDir(), "version" => $whmcs->getVersion()->getCanonical(), "phpversion" => PHP_VERSION, "anondata" => $this->encryptMemberData($stats), "member" => $this->encryptMemberData($this->buildMemberData()), "check_token" => sha1(time() . $this->getLicenseKey() . mt_rand(1000000000, 9999999999.0)));
    }
    public function isUnlicensed()
    {
        if ($this->getLicenseKey() == static::UNLICENSED_KEY) {
            return true;
        }
        return false;
    }
    public function validate($forceRemote = false)
    {
        if (!$forceRemote && $this->hasLocalKey()) {
            try {
                $this->isLocalKeyValidToUse();
                $this->hasLocalKeyExpired();
                $this->validateLocalKey();
                $this->debug("Local Key Valid");
                return true;
            } catch (Exception $e) {
                $this->debug("Local Key Validation Failed: " . $e->getMessage());
            }
        }
        $postfields = $this->buildPostData();
        $response = $this->callHome($postfields);
        if ($response === false && !is_null($this->lastCurlError)) {
            $this->debug("CURL Error: " . $this->lastCurlError);
        }
        if (!Environment\Php::isFunctionAvailable("base64_decode")) {
            throw new Exception("Required function base64_decode is not available");
        }
        if ($response) {
            try {
                $results = $this->processResponse($response);
                if ($results["hash"] != sha1("WHMCSV5.2SYH" . $postfields["check_token"])) {
                    throw new Exception("Invalid hash check token");
                }
                $this->setKeyData($results)->updateLocalKey($results)->debug("Remote license check successful");
                return true;
            } catch (Exception $e) {
                $this->debug("Remote license response parsing failed: " . $e->getMessage());
            }
        }
        $this->debug("Remote license check failed. Attempting local key fallback.");
        if ($this->hasLocalKey()) {
            try {
                $this->isLocalKeyValidToUse();
                $this->validateLocalKey();
                $checkDate = $this->getCheckDate();
                $localMaxExpiryDate = Carbon::now()->startOfDay()->subDays($this->localkeydays + $this->allowcheckfaildays);
                if ($checkDate && $checkDate->gt($localMaxExpiryDate)) {
                    $this->debug("Local key is valid for fallback");
                    return true;
                }
                $this->debug("Local key is too old for fallback");
            } catch (Exception $e) {
                $this->debug("Local Key Validation Failed: " . $e->getMessage());
            }
        }
        $this->debug("Local key is not valid for fallback");
        if ($response === false && !is_null($this->lastCurlError)) {
            throw new Exception("CURL Error: " . $this->lastCurlError);
        }
        throw new Exception\Http\ConnectionError();
    }
    private function callHomeLoop($query_string, $timeout = 5)
    {
        foreach ($this->getHosts() as $host) {
            try {
                $this->debug("Attempting call home with host: " . $host);
                return $this->makeCall($this->getVerifyUrl($host), $query_string, $timeout);
            } catch (Exception $e) {
                $this->debug("Remote call failed: " . $e->getMessage());
            }
        }
        return false;
    }
    protected function callHome($postfields)
    {
        $this->validateCurlIsAvailable();
        $query_string = build_query_string($postfields);
        $response = $this->callHomeLoop($query_string, 5);
        if ($response) {
            return $response;
        }
        return $this->callHomeLoop($query_string, 30);
    }
    private function getVerifyUrl($host)
    {
        return "https://" . $host . "/1.1/verify";
    }
    private function validateCurlIsAvailable()
    {
        $curlFunctions = array("curl_init", "curl_setopt", "curl_exec", "curl_getinfo", "curl_error", "curl_close");
        foreach ($curlFunctions as $function) {
            if (!Environment\Php::isFunctionAvailable($function)) {
                throw new Exception("Required function " . $function . " is not available");
            }
        }
    }
    protected function makeCall($url, $query_string, $timeout = 5)
    {
        $this->debug("Timeout " . $timeout);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->useInternalLicensingMirror ? 0 : 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->useInternalLicensingMirror ? 0 : 1);
        $response = curl_exec($ch);
        $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_error($ch)) {
            $this->lastCurlError = curl_error($ch) . " - Code " . curl_errno($ch);
            throw new Exception("Curl Error: " . curl_error($ch) . " - Code " . curl_errno($ch));
        }
        curl_close($ch);
        if ($responsecode != 200) {
            throw new Exception("Received Non 200 Response Code");
        }
        return $response;
    }
    private function processResponse($data)
    {
        $publicServerKey = "-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAy62WXeIR+PG/50quF7HD\nHXxrRkBIjazP19mXmcqRnyB/sXl3v5WDqxkS/bttqEseNgs2+WmuXPdHzwFF2IhY\nqoijl6zvVOXiT44rVQvCvfQrMncWbrl6PmTUmP8Ux2Dmttnz+dGJlTz3uaysfPqC\n9pAn19b8zgNwGPNl0cGqiMxruGU4Vzbbjs0zOamvrzUkpKRkD3t8voW78KqQ80A/\nfyP9jfCa4Tax6OfjiZ2EVMQgwNbu4nZeu5hggg/9KWX62O+iDWRw10A4OIzw2mJ+\nL0IDgeSMdrSUYgHlf+AUeW2qZV7cN7OOdt+FMQ3i5lX9LBBNeykqIiypF+voVFgN\nLhKw04EOrj6R511yOvVIrW5d2FO/wA5mydXJ1T31w+fjG3IitRm9F6tSRoPfeSi9\n+hWMpBUa9rg/BuoSOGoHMKbKFAN2hYu0e2ftkZ7KATNfoSf3D5HEVnTPqx+KfQFT\nRdjsYUIIqVX+GsQzzBulf5YhoTmew+N5n9dZGGbhNHZTr7cMa1DT73BjxOyMr2Fq\nW92QUyodlfZmPMfF+JD+MBMY0r74u8/ow1rCrnqu+3Rr/JE/Hjl6c9VsQS/sucP6\nJQfLTfeBjXNWdrXCvhUb+QaV4pMYxhpno5/7jPEkMOR9o7QTCFzbszEzlotwS/yT\ncgD/Aq302svJj2VbSAtyBi0CAwEAAQ==\n-----END PUBLIC KEY-----";
        $results = $this->parseSignedResponse($data, $publicServerKey);
        $this->debug("Remote license response parsed successfully");
        $results["checkdate"] = Carbon::now()->toDateString();
        if (!empty($results["MemberPubKey"])) {
            $this->setMemberPublicKey($results["MemberPubKey"]);
            unset($results["MemberPubKey"]);
        }
        return $results;
    }
    private function parseSignedResponse($response, $publicKey)
    {
        if ($this->useInternalLicensingMirror) {
            $data = json_decode($response, true);
            if (is_null($data) || !is_array($data)) {
                throw new Exception("Internal licensing mirror response could not be decoded");
            }
            return $data;
        }
        $data = explode(":", $response, 2);
        if (empty($data[1])) {
            throw new Exception("No license signature found");
        }
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->setSignatureMode(\phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
        $rsa->loadKey(str_replace(array("\n", " "), array("", ""), $publicKey));
        try {
            if (!$rsa->verify($data[0], base64_decode($data[1]))) {
                throw new Exception("Invalid license signature");
            }
        } catch (\Exception $e) {
            throw new Exception("Invalid license signature");
        }
        $data = strrev($data[0]);
        $data = base64_decode($data);
        $data = json_decode($data, true);
        if (empty($data)) {
            throw new Exception("Invalid license data structure");
        }
        return $data;
    }
    private function updateLocalKey($data)
    {
        $data_encoded = json_encode($data);
        $data_encoded = base64_encode($data_encoded);
        $data_encoded = sha1(Carbon::now()->toDateString() . $this->getSalt()) . $data_encoded;
        $data_encoded = strrev($data_encoded);
        $splpt = strlen($data_encoded) / 2;
        $data_encoded = substr($data_encoded, $splpt) . substr($data_encoded, 0, $splpt);
        $data_encoded = sha1($data_encoded . $this->getSalt()) . $data_encoded . sha1($data_encoded . $this->getSalt() . time());
        $data_encoded = base64_encode($data_encoded);
        $data_encoded = wordwrap($data_encoded, 80, "\n", true);
        \App::self()->set_config("License", $data_encoded);
        return $this->debug("Local Key Updated");
    }
    public function forceRemoteCheck()
    {
        return $this->validate(true);
    }
    private function decodeLocal($localkey = "")
    {
        $this->debug("Decoding local key");
        if (!$localkey) {
            $this->debug("No local key provided");
            return false;
        }
        $localkey = str_replace("\n", "", $localkey);
        $localkey = base64_decode($localkey);
        $localdata = substr($localkey, 40, -40);
        $md5hash = substr($localkey, 0, 40);
        if ($md5hash != sha1($localdata . $this->getSalt())) {
            $this->debug("Local Key MD5 Hash Invalid");
            return false;
        }
        $splpt = strlen($localdata) / 2;
        $localdata = substr($localdata, $splpt) . substr($localdata, 0, $splpt);
        $localdata = strrev($localdata);
        $md5hash = substr($localdata, 0, 40);
        $localdata = substr($localdata, 40);
        $localdata = base64_decode($localdata);
        $localKeyData = json_decode($localdata, true);
        $originalcheckdate = $localKeyData["checkdate"];
        if ($md5hash != sha1($originalcheckdate . $this->getSalt())) {
            $this->debug("Local Key MD5 Hash 2 Invalid");
            return false;
        }
        $this->setKeyData($localKeyData);
        $this->debug("Local Key Decoded Successfully");
        return true;
    }
    protected function isRunningInCLI()
    {
        return php_sapi_name() == "cli" && empty($_SERVER["REMOTE_ADDR"]);
    }
    protected function hasLocalKey()
    {
        return !is_null($this->keydata);
    }
    protected function validateLocalKey()
    {
        if ($this->getKeyData("status") != "Active") {
            throw new Exception("Local Key Status not Active");
        }
        if ($this->isRunningInCLI()) {
            $this->debug("Running in CLI Mode");
        } else {
            $this->debug("Running in Browser Mode");
            if ($this->isValidDomain($this->getHostDomain())) {
                $this->debug("Domain Validated Successfully");
                $ip = $this->getHostIP();
                $this->debug("Host IP Address: " . $ip);
                if (!$ip) {
                    $this->debug("IP Could Not Be Determined - Skipping Local Validation of IP");
                } else {
                    if (!trim($this->getKeyData("validips"))) {
                        $this->debug("No Valid IPs returned by license check - Cloud Based License - Skipping Local Validation of IP");
                    } else {
                        if ($this->isValidIP($ip)) {
                            $this->debug("IP Validated Successfully");
                        } else {
                            throw new Exception("Invalid IP");
                        }
                    }
                }
            } else {
                throw new Exception("Invalid domain");
            }
        }
        if ($this->isValidDir($this->getHostDir())) {
            $this->debug("Directory Validated Successfully");
        } else {
            throw new Exception("Invalid directory");
        }
    }
    private function isValidDomain($domain)
    {
        $validdomains = $this->getArrayKeyData("validdomains");
        return in_array($domain, $validdomains);
    }
    private function isValidIP($ip)
    {
        $validips = $this->getArrayKeyData("validips");
        return in_array($ip, $validips);
    }
    private function isValidDir($dir)
    {
        $validdirs = $this->getArrayKeyData("validdirs");
        return in_array($dir, $validdirs);
    }
    public function getBanner()
    {
        $licenseKeyParts = explode("-", $this->getLicenseKey(), 2);
        $prefix = isset($licenseKeyParts[0]) ? $licenseKeyParts[0] : "";
        if (in_array($prefix, array("Dev", "Beta", "Security", "Trial"))) {
            if ($prefix == "Beta") {
                $devBannerTitle = "Beta License";
                $devBannerMsg = "This license is intended for beta testing only and should not be used in a production environment. Please report any cases of abuse to abuse@whmcs.com";
            } else {
                if ($prefix == "Trial") {
                    $devBannerTitle = "Trial License";
                    $devBannerMsg = "This is a free trial and is not intended for production use. Please <a href=\"https://www.whmcs.com/order/\" target=\"_blank\">purchase a license</a> to remove this notice.";
                } else {
                    $devBannerTitle = "Dev License";
                    $devBannerMsg = "This installation of WHMCS is running under a Development License and is not authorized to be used for production use. Please report any cases of abuse to abuse@whmcs.com";
                }
            }
            return "<strong>" . $devBannerTitle . ":</strong> " . $devBannerMsg;
        }
        return "";
    }
    private function revokeLocal()
    {
        \App::self()->set_config("License", "");
    }
    public function getKeyData($var)
    {
        return isset($this->keydata[$var]) ? $this->keydata[$var] : "";
    }
    private function setKeyData($data)
    {
        $this->keydata = $data;
        return $this;
    }
    protected function getArrayKeyData($var)
    {
        $listData = array();
        $rawData = $this->getKeyData($var);
        if (is_string($rawData)) {
            $listData = explode(",", $rawData);
            foreach ($listData as $k => $v) {
                if (is_string($v)) {
                    $listData[$k] = trim($v);
                } else {
                    throw new Exception("Invalid license data structure");
                }
            }
        } else {
            if (!is_null($rawData)) {
                throw new Exception("Invalid license data structure");
            }
        }
        return $listData;
    }
    public function getRegisteredName()
    {
        return $this->getKeyData("registeredname");
    }
    public function getProductName()
    {
        return $this->getKeyData("productname");
    }
    public function getStatus()
    {
        return $this->getKeyData("status");
    }
    public function getSupportAccess()
    {
        return $this->getKeyData("supportaccess");
    }
    protected function getCheckDate()
    {
        $checkDate = $this->getKeyData("checkdate");
        if (empty($checkDate)) {
            return false;
        }
        return Carbon::createFromFormat("Y-m-d", $checkDate);
    }
    protected function getLicensedAddons()
    {
        $licensedAddons = $this->getKeyData("addons");
        if (!is_array($licensedAddons)) {
            $licensedAddons = array();
        }
        return $licensedAddons;
    }
    public function getActiveAddons()
    {
        $licensedAddons = $this->getLicensedAddons();
        $activeAddons = array();
        foreach ($licensedAddons as $addon) {
            if ($addon["status"] == "Active") {
                $activeAddons[] = $addon["name"];
            }
        }
        return $activeAddons;
    }
    public function isActiveAddon($addon)
    {
        return in_array($addon, $this->getActiveAddons()) ? true : false;
    }
    public function getExpiryDate($showday = false)
    {
        $expiry = $this->getKeyData("nextduedate");
        if (!$expiry) {
            $expiry = "Never";
        } else {
            if ($showday) {
                $expiry = date("l, jS F Y", strtotime($expiry));
            } else {
                $expiry = date("jS F Y", strtotime($expiry));
            }
        }
        return $expiry;
    }
    public function getLatestPublicVersion()
    {
        try {
            $latestVersion = new Version\SemanticVersion($this->getKeyData("latestpublicversion"));
        } catch (Exception\Version\BadVersionNumber $e) {
            $whmcs = \DI::make("app");
            $latestVersion = $whmcs->getVersion();
        }
        return $latestVersion;
    }
    public function getLatestPreReleaseVersion()
    {
        try {
            $latestVersion = new Version\SemanticVersion($this->getKeyData("latestprereleaseversion"));
        } catch (Exception\Version\BadVersionNumber $e) {
            $whmcs = \DI::make("app");
            $latestVersion = $whmcs->getVersion();
        }
        return $latestVersion;
    }
    public function getLatestVersion()
    {
        $whmcs = \DI::make("app");
        $installedVersion = $whmcs->getVersion();
        if (in_array($installedVersion->getPreReleaseIdentifier(), array("beta", "rc"))) {
            $latestVersion = $this->getLatestPreReleaseVersion();
        } else {
            $latestVersion = $this->getLatestPublicVersion();
        }
        return $latestVersion;
    }
    public function isUpdateAvailable()
    {
        $whmcs = \DI::make("app");
        $installedVersion = $whmcs->getVersion();
        $latestVersion = $this->getLatestVersion();
        return Version\SemanticVersion::compare($latestVersion, $installedVersion, ">");
    }
    public function getRequiresUpdates()
    {
        return $this->getKeyData("requiresupdates") ? true : false;
    }
    public function getUpdatesExpirationDate()
    {
        $expirationDates = array();
        $licensedAddons = $this->getLicensedAddons();
        foreach ($licensedAddons as $addon) {
            if ($addon["name"] == "Support and Updates" && $addon["status"] == "Active" && isset($addon["nextduedate"])) {
                try {
                    $expirationDates[] = Carbon::createFromFormat("Y-m-d", $addon["nextduedate"]);
                } catch (\Exception $e) {
                }
            }
        }
        if (!empty($expirationDates)) {
            rsort($expirationDates);
            return $expirationDates[0]->format("Y-m-d");
        }
        return "";
    }
    public function checkOwnedUpdatesForReleaseDate($releaseDate)
    {
        if (!$this->getRequiresUpdates()) {
            return true;
        }
        try {
            $updatesExpirationDate = Carbon::createFromFormat("Y-m-d", $this->getUpdatesExpirationDate());
            $checkDate = Carbon::createFromFormat("Y-m-d", $releaseDate);
            return $checkDate <= $updatesExpirationDate ? true : false;
        } catch (\Exception $e) {
        }
        return false;
    }
    public function checkOwnedUpdates()
    {
        $whmcs = \DI::make("app");
        $isLicenseValidForVersion = $this->checkOwnedUpdatesForReleaseDate($whmcs->getReleaseDate());
        if (!$isLicenseValidForVersion) {
            try {
                $this->forceRemoteCheck();
                $isLicenseValidForVersion = $this->checkOwnedUpdatesForReleaseDate($whmcs->getReleaseDate());
            } catch (\Exception $e) {
            }
        }
        return $isLicenseValidForVersion;
    }
    public function getBrandingRemoval()
    {
        if (in_array($this->getProductName(), array("Owned License No Branding", "Monthly Lease No Branding"))) {
            return true;
        }
        $licensedAddons = $this->getLicensedAddons();
        foreach ($licensedAddons as $addon) {
            if ($addon["name"] == "Branding Removal" && $addon["status"] == "Active") {
                return true;
            }
        }
        return false;
    }
    private function debug($msg)
    {
        $this->debuglog[] = $msg;
        return $this;
    }
    public function getDebugLog()
    {
        return $this->debuglog;
    }
    public function getUpdateValidityDate()
    {
        return new \DateTime();
    }
    public function isClientLimitsEnabled()
    {
        return (bool) $this->getKeyData("ClientLimitsEnabled");
    }
    public function getClientLimit()
    {
        $clientLimit = $this->getKeyData("ClientLimit");
        if ($clientLimit == "") {
            return -1;
        }
        if (!is_numeric($clientLimit)) {
            $this->debug("Invalid client limit value in license");
            return 0;
        }
        return (int) $clientLimit;
    }
    public function getTextClientLimit()
    {
        $clientLimit = $this->getClientLimit();
        $fallbackTranslation = "Unlimited";
        if (0 < $clientLimit) {
            $result = number_format($clientLimit, 0, "", ",");
        } else {
            $translationKey = "global.unlimited";
            $result = \AdminLang::trans($translationKey);
            if ($result == $translationKey) {
                $result = $fallbackTranslation;
            }
        }
        return $result;
    }
    public function getNumberOfActiveClients()
    {
        return (int) get_query_val("tblclients", "count(id)", "status='Active'");
    }
    public function getTextNumberOfActiveClients(Admin $admin = NULL)
    {
        $clientLimit = $this->getNumberOfActiveClients();
        $result = "None";
        if (0 < $clientLimit) {
            $result = number_format($clientLimit, 0, "", ",");
        } else {
            if ($admin && ($text = $admin->lang("global", "none"))) {
                $result = $text;
            }
        }
        return $result;
    }
    public function getClientBoundaryId()
    {
        $clientLimit = $this->getClientLimit();
        if ($clientLimit < 0) {
            return 0;
        }
        return (int) get_query_val("tblclients", "id", "status='Active'", "id", "ASC", (int) $clientLimit . ",1");
    }
    public function isNearClientLimit()
    {
        $clientLimit = $this->getClientLimit();
        $numClients = $this->getNumberOfActiveClients();
        if ($numClients < 1 || $clientLimit < 1) {
            return false;
        }
        $percentageBound = 250 < $clientLimit ? 0.05 : 0.1;
        return $clientLimit * (1 - $percentageBound) <= $numClients;
    }
    public function isClientLimitsAutoUpgradeEnabled()
    {
        return (bool) $this->getKeyData("ClientLimitAutoUpgradeEnabled");
    }
    public function getClientLimitLearnMoreUrl()
    {
        return $this->getKeyData("ClientLimitLearnMoreUrl");
    }
    public function getClientLimitUpgradeUrl()
    {
        return $this->getKeyData("ClientLimitUpgradeUrl");
    }
    protected function getMemberPublicKey()
    {
        $publicKey = Config\Setting::getValue("MemberPubKey");
        if ($publicKey) {
            $publicKey = decrypt($publicKey);
        }
        return $publicKey;
    }
    protected function setMemberPublicKey($publicKey = "")
    {
        if ($publicKey) {
            $publicKey = encrypt($publicKey);
            Config\Setting::setValue("MemberPubKey", $publicKey);
        }
        return $this;
    }
    public function encryptMemberData(array $data = array())
    {
        $publicKey = $this->getMemberPublicKey();
        if (!$publicKey) {
            return "";
        }
        $publicKey = str_replace(array("\n", "\r", " "), array("", "", ""), $publicKey);
        $cipherText = "";
        if (is_array($data)) {
            try {
                $rsa = new \phpseclib\Crypt\RSA();
                $rsa->loadKey($publicKey);
                $rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_OAEP);
                $cipherText = $rsa->encrypt(json_encode($data));
                if (!$cipherText) {
                    throw new Exception("Could not perform RSA encryption");
                }
                $cipherText = base64_encode($cipherText);
            } catch (\Exception $e) {
                $this->debug("Failed to encrypt member data");
            }
        }
        return $cipherText;
    }
    public function getClientLimitNotificationAttributes()
    {
        if (!$this->isClientLimitsEnabled() || !$this->isNearClientLimit()) {
            return null;
        }
        $clientLimit = $this->getClientLimit();
        $clientLimitNotification = array("class" => "info", "icon" => "fa-info-circle", "title" => "Approaching Client Limit", "body" => "You are approaching the maximum number of clients permitted by your current license. Your license will be upgraded automatically when the limit is reached.", "autoUpgradeEnabled" => $this->isClientLimitsAutoUpgradeEnabled(), "upgradeUrl" => $this->getClientLimitUpgradeUrl(), "learnMoreUrl" => $this->getClientLimitLearnMoreUrl(), "numberOfActiveClients" => $this->getNumberOfActiveClients(), "clientLimit" => $clientLimit);
        if ($this->isClientLimitsAutoUpgradeEnabled()) {
            if ($this->getNumberOfActiveClients() < $clientLimit) {
            } else {
                if ($clientLimit == $this->getNumberOfActiveClients()) {
                    $clientLimitNotification["title"] = "Client Limit Reached";
                    $clientLimitNotification["body"] = "You have reached the maximum number of clients permitted by your current license. Your license will be upgraded automatically when the next client is created.";
                } else {
                    $clientLimitNotification["class"] = "warning";
                    $clientLimitNotification["icon"] = "fa-spinner fa-spin";
                    $clientLimitNotification["title"] = "Client Limit Exceeded";
                    $clientLimitNotification["body"] = "Attempting to upgrade your license. Communicating with license server...";
                    $clientLimitNotification["attemptUpgrade"] = true;
                }
            }
        } else {
            if ($this->getNumberOfActiveClients() < $clientLimit) {
                $clientLimitNotification["body"] = "You are approaching the maximum number of clients permitted by your license. As you have opted out of automatic license upgrades, you should upgrade now to avoid interuption in service.";
            } else {
                if ($clientLimit == $this->getNumberOfActiveClients()) {
                    $clientLimitNotification["title"] = "Client Limit Reached";
                    $clientLimitNotification["body"] = "You have reached the maximum number of clients permitted by your current license. As you have opted out of automatic license upgrades, you must upgrade now to avoid interuption in service.";
                } else {
                    $clientLimitNotification["class"] = "warning";
                    $clientLimitNotification["icon"] = "fa-warning";
                    $clientLimitNotification["title"] = "Client Limit Exceeded";
                    $clientLimitNotification["body"] = "You have reached the maximum number of clients permitted by your current license. As automatic license upgrades have been disabled, you must upgrade now.";
                }
            }
        }
        return $clientLimitNotification;
    }
    protected function buildMemberData()
    {
        return array("licenseKey" => $this->getLicenseKey(), "activeClientCount" => $this->getNumberOfActiveClients());
    }
    public function getEncryptedMemberData()
    {
        return $this->encryptMemberData($this->buildMemberData());
    }
    protected function getUpgradeUrl($host)
    {
        return "https://" . $host . "/" . self::LICENSE_API_VERSION . "/upgrade";
    }
    public function makeUpgradeCall()
    {
        $checkToken = sha1(time() . $this->getLicenseKey() . mt_rand(1000000000, 9999999999.0));
        $query_string = build_query_string(array("check_token" => $checkToken, "license_key" => $this->getLicenseKey(), "member_data" => $this->encryptMemberData($this->buildMemberData())));
        $timeout = 30;
        foreach ($this->getHosts() as $host) {
            try {
                $response = $this->makeCall($this->getUpgradeUrl($host), $query_string, $timeout);
                $data = $this->processResponse($response);
                if ($data["hash"] != sha1("WHMCSV5.2SYH" . $checkToken)) {
                    return false;
                }
                if ($data["status"] == "Success" && is_array($data["new"])) {
                    unset($data["status"]);
                    $this->keydata = array_merge($this->keydata, $data["new"]);
                    $this->updateLocalKey($this->keydata);
                    return true;
                }
                return false;
            } catch (Exception $e) {
            }
        }
        return false;
    }
    public function isValidLicenseKey($licenseKey)
    {
        if (is_string($licenseKey) || is_numeric($licenseKey)) {
            $pattern = "/^[0-9a-zA-Z\\-_]{10,}\$/";
            return (bool) preg_match($pattern, $licenseKey);
        }
        return false;
    }
    private function getWhmcsNetKey()
    {
        $key = $this->getKeyData("whmcsnetkey");
        if (!$key) {
            $key = "f4e0cdeba94d4fd5377d20d895ee5600dfc03776";
        }
        return $key;
    }
    public function hashMessage($value)
    {
        $hashKey = $this->getWhmcsNetKey();
        $obfuscatedLicenseKey = sha1($this->getLicenseKey());
        $hashable = $obfuscatedLicenseKey . $value . $hashKey;
        $hmac = hash_hmac("sha256", $hashable, $hashKey);
        return $obfuscatedLicenseKey . "|" . $value . "|" . $hmac;
    }
    public function getValueFromHashMessage($message)
    {
        if (!$this->isValidHashMessage($message)) {
            return null;
        }
        $parts = explode("|", $message);
        return $parts[1];
    }
    public function isValidHashMessage($message)
    {
        $parts = explode("|", $message);
        if (count($parts) < 3) {
            return false;
        }
        $hashKey = $this->getWhmcsNetKey();
        $obfuscatedLicenseKey = array_shift($parts);
        $hmacGiven = array_pop($parts);
        $hashable = $obfuscatedLicenseKey . implode("", $parts) . $hashKey;
        $hmacCalculated = hash_hmac("sha256", $hashable, $hashKey);
        if ($hmacGiven !== $hmacCalculated) {
            return false;
        }
        return true;
    }
}

?>