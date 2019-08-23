<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User;

class Client extends AbstractUser implements Contracts\ContactInterface, UserInterface
{
    protected $table = "tblclients";
    protected $columnMap = array("passwordHash" => "password", "twoFactorAuthModule" => "authmodule", "twoFactorAuthData" => "authdata", "currencyId" => "currency", "defaultPaymentGateway" => "defaultgateway", "overrideLateFee" => "latefeeoveride", "overrideOverdueNotices" => "overideduenotices", "disableAutomaticCreditCardProcessing" => "disableautocc", "billingContactId" => "billingcid", "securityQuestionId" => "securityqid", "securityQuestionAnswer" => "securityqans", "creditCardType" => "cardtype", "creditCardLastFourDigits" => "cardlastfour", "creditCardExpiryDate" => "expdate", "storedBankNameCrypt" => "bankname", "storedBankTypeCrypt" => "banktype", "storedBankCodeCrypt" => "bankcode", "storedBankAccountCrypt" => "bankacct", "paymentGatewayToken" => "gatewayid", "lastLoginDate" => "lastlogin", "lastLoginIp" => "ip", "lastLoginHostname" => "host", "passwordResetKey" => "pwresetkey", "passwordResetKeyRequestDate" => "pwresetexpiry", "passwordResetKeyExpiryDate" => "pwresetexpiry");
    public $timestamps = true;
    protected $dates = array("lastLoginDate", "passwordResetKeyRequestDate", "passwordResetKeyExpiryDate");
    protected $booleans = array("taxExempt", "overrideLateFee", "overrideOverdueNotices", "separateInvoices", "disableAutomaticCreditCardProcessing", "emailOptOut", "marketingEmailsOptIn", "overrideAutoClose", "emailVerified");
    public $unique = array("email");
    protected $appends = array("fullName", "countryName", "groupName");
    protected $fillable = array("lastlogin", "ip", "host", "pwresetkey", "pwresetexpiry");
    protected $hidden = array("password", "authdata", "securityqans", "cardnum", "startdate", "expdate", "issuenumber", "bankname", "banktype", "bankcode", "bankacct", "pwresetkey", "pwresetexpiry");
    const STATUS_ACTIVE = "Active";
    const STATUS_INACTIVE = "Inactive";
    const STATUS_CLOSED = "Closed";
    const PAYMENT_DATA_MIGRATED = "--MIGRATED--";
    public function domains()
    {
        return $this->hasMany("WHMCS\\Domain\\Domain", "userid");
    }
    public function services()
    {
        return $this->hasMany("WHMCS\\Service\\Service", "userid");
    }
    public function addons()
    {
        return $this->hasMany("WHMCS\\Service\\Addon", "userid");
    }
    public function contacts()
    {
        return $this->hasMany("WHMCS\\User\\Client\\Contact", "userid");
    }
    public function billingContact()
    {
        return $this->hasOne("WHMCS\\User\\Client\\Contact", "id", "billingcid");
    }
    public function quotes()
    {
        return $this->hasMany("WHMCS\\Billing\\Quote", "userid");
    }
    public function affiliate()
    {
        return $this->hasOne("WHMCS\\User\\Client\\Affiliate", "clientid");
    }
    public function securityQuestion()
    {
        return $this->belongsTo("WHMCS\\User\\Client\\SecurityQuestion", "securityqid");
    }
    public function invoices()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice", "userid");
    }
    public function transactions()
    {
        return $this->hasMany("WHMCS\\Billing\\Payment\\Transaction", "userid");
    }
    public function remoteAccountLinks()
    {
        $relation = $this->hasMany("WHMCS\\Authentication\\Remote\\AccountLink", "client_id");
        $relation->getQuery()->whereNull("contact_id");
        return $relation;
    }
    public function orders()
    {
        return $this->hasMany("WHMCS\\Order\\Order", "userid");
    }
    public function marketingConsent()
    {
        return $this->hasMany("WHMCS\\Marketing\\Consent", "userid");
    }
    public function scopeLoggedIn($query)
    {
        return $query->where("id", (int) \WHMCS\Session::get("uid"));
    }
    public function currencyrel()
    {
        return $this->hasOne("WHMCS\\Billing\\Currency", "id", "currency");
    }
    public static function getStatuses()
    {
        return array(self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_CLOSED);
    }
    public function hasDomain($domainName)
    {
        $domainCount = $this->domains()->where("domain", "=", $domainName)->count();
        if (0 < $domainCount) {
            return true;
        }
        $serviceDomainCount = $this->services()->where("domain", "=", $domainName)->count();
        return 0 < $serviceDomainCount;
    }
    protected function generateCreditCardEncryptionKey()
    {
        $config = \Config::self();
        return md5($config["cc_encryption_hash"] . $this->id);
    }
    public function getAlerts(Client\AlertFactory $factory = NULL)
    {
        static $alerts = NULL;
        if (is_null($alerts)) {
            if (is_null($factory)) {
                $factory = new Client\AlertFactory($this);
            }
            $alerts = $factory->build();
        }
        return $alerts;
    }
    public function isCreditCardExpiring($withinMonths = 2)
    {
        if (!function_exists("getClientDefaultCardDetails")) {
            require_once ROOTDIR . "/includes/ccfunctions.php";
        }
        $cardDetails = getClientDefaultCardDetails($this->id);
        if (empty($cardDetails["expdate"])) {
            return false;
        }
        unset($cardDetails["fullcardnum"]);
        $expiryDate = str_replace("/", "", $cardDetails["expdate"]);
        if (!is_numeric($expiryDate) || strlen($expiryDate) != 4) {
            return false;
        }
        $isExpiring = \WHMCS\Carbon::createFromFormat("dmy", "01" . $expiryDate)->diffInMonths(\WHMCS\Carbon::now()->startOfMonth()) <= $withinMonths;
        if ($isExpiring) {
            return $cardDetails;
        }
        return false;
    }
    public function getFullNameAttribute()
    {
        return (string) $this->firstName . " " . $this->lastName;
    }
    public function getCountryNameAttribute()
    {
        static $countries = NULL;
        if (is_null($countries)) {
            $countries = new \WHMCS\Utility\Country();
        }
        return $countries->getName($this->country);
    }
    public function getSecurityQuestionAnswerAttribute($answer)
    {
        return decrypt($answer);
    }
    public function setSecurityQuestionAnswerAttribute($answer)
    {
        $this->attributes["securityqans"] = encrypt($answer);
    }
    public function generateCreditCardEncryptedField($value)
    {
        return $this->encryptValue($value, $this->generateCreditCardEncryptionKey());
    }
    public function getUsernameAttribute()
    {
        return $this->email;
    }
    public function hasSingleSignOnPermission()
    {
        return (bool) $this->allowSso;
    }
    public function isAllowedToAuthenticate()
    {
        return $this->status != "Closed";
    }
    public function isEmailAddressVerified()
    {
        return (bool) $this->emailVerified;
    }
    public function getEmailVerificationId()
    {
        $transientData = \WHMCS\TransientData::getInstance();
        $transientDataName = $this->id . ":emailVerificationClientKey";
        $verificationId = self::generateEmailVerificationKey();
        $verificationExpiry = 86400;
        $transientData->store($transientDataName, $verificationId, $verificationExpiry);
        return $verificationId;
    }
    public static function generateEmailVerificationKey()
    {
        return sha1(base64_encode(\phpseclib\Crypt\Random::string(64)));
    }
    public function sendEmailAddressVerification()
    {
        $whmcs = \App::self();
        $systemUrl = $whmcs->getSystemURL();
        $templateName = "Client Email Address Verification";
        $verificationId = $this->getEmailVerificationId();
        $verificationLinkPath = (string) $systemUrl . "clientarea.php?verificationId=" . $verificationId;
        $emailVerificationHyperLink = "<a href=\"" . $verificationLinkPath . "\" id=\"hrefVerificationLink\">" . $verificationLinkPath . "</a>";
        sendMessage($templateName, $this->id, array("client_email_verification_id" => $verificationId, "client_email_verification_link" => $emailVerificationHyperLink));
        return $this;
    }
    public function updateLastLogin(\WHMCS\Carbon $time = NULL, $ip = NULL, $host = NULL)
    {
        if (!$time) {
            $time = \WHMCS\Carbon::now();
        }
        if (!$ip) {
            $ip = \WHMCS\Utility\Environment\CurrentUser::getIP();
        }
        if (!$host) {
            $host = \WHMCS\Utility\Environment\CurrentUser::getIPHost();
        }
        $this->update(array("lastlogin" => (string) $time->format("YmdHis"), "ip" => $ip, "host" => $host, "pwresetkey" => "", "pwresetexpiry" => 0));
    }
    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "relid");
    }
    protected function getCustomFieldType()
    {
        return "client";
    }
    protected function getCustomFieldRelId()
    {
        return 0;
    }
    public function hasPermission($permission)
    {
        throw new \RuntimeException("WHMCS\\User\\Client::hasPermission" . " not implemented");
    }
    public function tickets()
    {
        return $this->hasMany("WHMCS\\Support\\Ticket", "userid");
    }
    public function isOptedInToMarketingEmails()
    {
        if (\WHMCS\Marketing\EmailSubscription::isUsingOptInField()) {
            return (bool) $this->marketingEmailsOptIn;
        }
        return !(bool) $this->emailOptOut;
    }
    public function marketingEmailOptIn($userIp = "", $performCurrentSettingCheck = true)
    {
        if ($performCurrentSettingCheck && $this->isOptedInToMarketingEmails()) {
            throw new \WHMCS\Exception\Marketing\AlreadyOptedIn();
        }
        $this->emailOptOut = false;
        $this->marketingEmailsOptIn = true;
        $this->save();
        \WHMCS\Marketing\Consent::logOptIn($this->id, $userIp);
        $this->logActivity("Opted In to Marketing Emails");
        return $this;
    }
    public function marketingEmailOptOut($userIp = "", $performCurrentSettingCheck = true)
    {
        if ($performCurrentSettingCheck && !$this->isOptedInToMarketingEmails()) {
            throw new \WHMCS\Exception\Marketing\AlreadyOptedOut();
        }
        $this->emailOptOut = true;
        $this->marketingEmailsOptIn = false;
        $this->save();
        \WHMCS\Marketing\Consent::logOptOut($this->id, $userIp);
        $this->logActivity("Opted Out from Marketing Emails");
        return $this;
    }
    public function logActivity($message)
    {
        logActivity($message . " - User ID: " . $this->id, $this->id);
        return $this;
    }
    public function deleteEntireClient()
    {
        $userid = $this->id;
        run_hook("PreDeleteClient", array("userid" => $userid));
        delete_query("tblcontacts", array("userid" => $userid));
        $tblhostingIds = \WHMCS\Database\Capsule::table("tblhosting")->where("userid", $userid)->pluck("id");
        if (!empty($tblhostingIds)) {
            \WHMCS\Database\Capsule::table("tblhostingconfigoptions")->whereIn("relid", $tblhostingIds)->delete();
        }
        $result = select_query("tblcustomfields", "id", array("type" => "client"));
        while ($data = mysql_fetch_array($result)) {
            $customfieldid = $data["id"];
            delete_query("tblcustomfieldsvalues", array("fieldid" => $customfieldid, "relid" => $userid));
        }
        $result = select_query("tblcustomfields", "id,relid", array("type" => "product"));
        while ($data = mysql_fetch_array($result)) {
            $customfieldid = $data["id"];
            $customfieldpid = $data["relid"];
            $result2 = select_query("tblhosting", "id", array("userid" => $userid, "packageid" => $customfieldpid));
            while ($data = mysql_fetch_array($result2)) {
                $hostingid = $data["id"];
                delete_query("tblcustomfieldsvalues", array("fieldid" => $customfieldid, "relid" => $hostingid));
            }
        }
        $addonCustomFields = \WHMCS\Database\Capsule::table("tblcustomfields")->where("type", "addon")->get(array("id", "relid"));
        foreach ($addonCustomFields as $addonCustomField) {
            $customFieldId = $addonCustomField->id;
            $customFieldAddonId = $addonCustomField->relid;
            $hostingAddons = \WHMCS\Database\Capsule::table("tblhostingaddons")->where("userid", $userid)->where("addonid", $customFieldAddonId)->pluck("id");
            foreach ($hostingAddons as $hostingAddon) {
                $addonId = $hostingAddon->id;
                \WHMCS\Database\Capsule::table("tblcustomfieldsvalues")->where("fieldid", $customFieldId)->where("relid", $addonId)->delete();
            }
        }
        $result = select_query("tblhosting", "id", array("userid" => $userid));
        while ($data = mysql_fetch_array($result)) {
            $domainlistid = $data["id"];
            delete_query("tblhostingaddons", array("hostingid" => $domainlistid));
        }
        delete_query("tblorders", array("userid" => $userid));
        delete_query("tblhosting", array("userid" => $userid));
        delete_query("tbldomains", array("userid" => $userid));
        delete_query("tblemails", array("userid" => $userid));
        delete_query("tblinvoices", array("userid" => $userid));
        delete_query("tblinvoiceitems", array("userid" => $userid));
        $tickets = \WHMCS\Database\Capsule::table("tbltickets")->where("userid", $userid)->pluck("id");
        foreach ($tickets as $ticketId) {
            try {
                if (!function_exists("deleteTicket")) {
                    require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
                }
                deleteTicket($ticketId);
            } catch (\WHMCS\Exception\Fatal $e) {
                $this->logActivity($e->getMessage());
                \WHMCS\Database\Capsule::table("tblticketreplies")->where("tid", $ticketId)->delete();
                \WHMCS\Database\Capsule::table("tbltickettags")->where("ticketid", $ticketId)->delete();
                \WHMCS\Database\Capsule::table("tblticketnotes")->where("ticketid", $ticketId)->delete();
                \WHMCS\Database\Capsule::table("tblticketlog")->where("tid", $ticketId)->delete();
                \WHMCS\Database\Capsule::table("tbltickets")->delete($ticketId);
            } catch (\Exception $e) {
            }
        }
        delete_query("tblaffiliates", array("clientid" => $userid));
        delete_query("tblnotes", array("userid" => $userid));
        delete_query("tblcredit", array("clientid" => $userid));
        delete_query("tblactivitylog", array("userid" => $userid));
        delete_query("tblsslorders", array("userid" => $userid));
        delete_query("tblauthn_account_links", array("client_id" => $userid));
        foreach ($this->payMethods as $payMethod) {
            $payMethod->forceDelete();
        }
        logActivity("Client Deleted - ID: " . $userid);
        return $this->delete();
    }
    public static function getGroups()
    {
        static $groups = NULL;
        if (is_null($groups)) {
            $groups = \WHMCS\Database\Capsule::table("tblclientgroups")->orderBy("groupname")->pluck("groupname", "id");
        }
        return $groups;
    }
    public function needsCardDetailsMigrated()
    {
        if ($this->creditCardType) {
            return $this->creditCardType !== self::PAYMENT_DATA_MIGRATED;
        }
        return (bool) trim($this->creditCardLastFourDigits) || (bool) trim($this->cardnum);
    }
    public function needsBankDetailsMigrated()
    {
        $migrationMarker = $this->banktype;
        return $migrationMarker && $migrationMarker !== self::PAYMENT_DATA_MIGRATED;
    }
    public function needsNonCardPaymentTokenMigrated()
    {
        $expiryDate = null;
        if ($this->creditCardExpiryDate) {
            $expiryDate = $this->decryptValue($this->creditCardExpiryDate, $this->generateCreditCardEncryptionKey());
        }
        return !$expiryDate && $this->paymentGatewayToken;
    }
    public function needsAnyPaymentDetailsMigrated()
    {
        return $this->needsCardDetailsMigrated() || $this->needsBankDetailsMigrated() || $this->needsNonCardPaymentTokenMigrated();
    }
    public function migratePaymentDetailsIfRequired($forceInCron = false)
    {
        if (defined("IN_CRON") && !$forceInCron) {
            return NULL;
        }
        try {
            if ($this->needsAnyPaymentDetailsMigrated()) {
                $migration = new \WHMCS\Payment\PayMethod\MigrationProcessor();
                $migration->migrateForClient($this);
            }
        } catch (\Exception $e) {
            $this->logActivity("Paymethod migration failed. " . $e->getMessage());
        }
    }
    public function markCardDetailsAsMigrated()
    {
        $this->creditCardType = self::PAYMENT_DATA_MIGRATED;
        $this->save();
        return $this;
    }
    public function markBankDetailsAsMigrated()
    {
        $this->banktype = self::PAYMENT_DATA_MIGRATED;
        $this->save();
        return $this;
    }
    public function markPaymentTokenMigrated()
    {
        $this->paymentGatewayToken = "";
        $this->save();
        return $this;
    }
    public function payMethods()
    {
        return $this->hasMany("WHMCS\\Payment\\PayMethod\\Model", "userid");
    }
    public function defaultBillingContact()
    {
        if ($this->billingContactId) {
            return $this->belongsTo("WHMCS\\User\\Client\\Contact", "billingcid");
        }
        return $this->hasOne(static::class, "id");
    }
    public function getGroupNameAttribute()
    {
        $groupName = "";
        if ($this->groupId) {
            $groups = self::getGroups();
            if (array_key_exists($this->groupId, $groups)) {
                $groupName = $groups[$this->groupId];
            }
        }
        return $groupName;
    }
    public function domainSslStatuses()
    {
        return $this->hasMany("WHMCS\\Domain\\Ssl\\Status", "user_id");
    }
    public function generateUniquePlaceholderEmail()
    {
        return "autogen_" . (new \WHMCS\Utility\Random())->string(6, 0, 2, 0) . "@example.com";
    }
    public function deleteAllCreditCards()
    {
        $this->creditCardType = "";
        $this->creditCardLastFourDigits = "";
        $this->cardnum = "";
        $this->creditCardExpiryDate = "";
        $this->startdate = "";
        $this->issuenumber = "";
        $this->paymentGatewayToken = "";
        $this->save();
        foreach ($this->payMethods as $payMethod) {
            if ($payMethod->isCreditCard()) {
                $payMethod->delete();
            }
        }
    }
    public static function getUsedCardTypes()
    {
        $cardTypes = \WHMCS\Payment\PayMethod\Adapter\CreditCard::where("card_type", "!=", "")->distinct("card_type")->pluck("card_type")->toArray();
        $clientCardTypes = self::where("cardtype", "!=", "")->where("cardtype", "!=", self::PAYMENT_DATA_MIGRATED)->distinct("cardtype")->pluck("cardtype")->toArray();
        asort(array_unique(array_merge($cardTypes, $clientCardTypes)));
        return $cardTypes;
    }
}

?>