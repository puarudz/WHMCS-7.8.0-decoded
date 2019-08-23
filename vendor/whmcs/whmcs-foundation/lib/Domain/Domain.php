<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain;

class Domain extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbldomains";
    protected $dates = array("registrationdate", "expirydate", "nextduedate", "nextinvoicedate");
    protected $columnMap = array("clientId" => "userid", "registrarModuleName" => "registrar", "promotionId" => "promoid", "paymentGateway" => "paymentmethod", "hasDnsManagement" => "dnsmanagement", "hasEmailForwarding" => "emailforwarding", "hasIdProtection" => "idprotection", "hasAutoInvoiceOnNextDueDisabled" => "donotrenew", "isSyncedWithRegistrar" => "synced", "isPremium" => "is_premium");
    protected $booleans = array("hasDnsManagement", "hasEmailForwarding", "hasIdProtection", "isPremium", "hasAutoInvoiceOnNextDueDisabled", "isSyncedWithRegistrar");
    protected $characterSeparated = array("|" => array("reminders"));
    protected $appends = array("tld", "extension", "gracePeriod", "gracePeriodFee", "redemptionGracePeriod", "redemptionGracePeriodFee");
    public function scopeOfClient(\Illuminate\Database\Eloquent\Builder $query, $clientId)
    {
        return $query->where("userid", $clientId);
    }
    public function scopeNextDueBefore(\Illuminate\Database\Eloquent\Builder $query, \WHMCS\Carbon $date)
    {
        return $query->whereStatus("Active")->where("nextduedate", "<=", $date);
    }
    public function scopeIsConsideredActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn("status", array(Status::ACTIVE, Status::PENDING_TRANSFER, Status::GRACE));
    }
    public function getTldAttribute()
    {
        $domainParts = explode(".", $this->domain, 2);
        return isset($domainParts[1]) ? $domainParts[1] : "";
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function additionalFields()
    {
        return $this->hasMany("WHMCS\\Domain\\AdditionalField", "domainid");
    }
    public function extra()
    {
        return $this->hasMany("WHMCS\\Domain\\Extra", "domain_id");
    }
    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "orderid");
    }
    public function invoiceItems()
    {
        return $this->hasMany("\\WHMCS\\Billing\\Invoice\\Item", "relid")->whereIn("type", array("DomainRegister", "DomainTransfer", "Domain", "DomainAddonDNS", "DomainAddonEMF", "DomainAddonIDP", "DomainGraceFee", "DomainRedemptionFee"));
    }
    public function setRemindersAttribute($reminders)
    {
        $remindersArray = $this->asArrayFromCharacterSeparatedValue($reminders, "|");
        if (5 < count($remindersArray)) {
            throw new \WHMCS\Exception("You may only store the past 5 domain reminders.");
        }
        foreach ($remindersArray as $reminder) {
            if (!is_numeric($reminder)) {
                throw new \WHMCS\Exception("Domain reminders must be numeric.");
            }
        }
        $this->attributes["reminders"] = $reminders;
    }
    public function failedActions()
    {
        return $this->hasMany("WHMCS\\Module\\Queue", "service_id")->where("service_type", "=", "domain");
    }
    public function isConfiguredTld()
    {
        $tld = $this->getTldAttribute();
        return 0 < (bool) \WHMCS\Database\Capsule::table("tbldomainpricing")->where("extension", "." . $tld)->count();
    }
    public function getAdditionalFields()
    {
        return (new \WHMCS\Domains\AdditionalFields())->setDomainType($this->type)->setDomain($this->domain);
    }
    public function getExtensionAttribute()
    {
        $tld = $this->getTldAttribute();
        static $data = array();
        if ($tld && !array_key_exists($tld, $data)) {
            $data[$tld] = \WHMCS\Domains\Extension::where("extension", "." . $tld)->first();
        }
        return $data[$tld];
    }
    public function getGracePeriodAttribute()
    {
        if (\WHMCS\Config\Setting::getValue("DisableDomainGraceAndRedemptionFees")) {
            return -1;
        }
        static $renewalGracePeriod = array();
        if (!array_key_exists($this->tld, $renewalGracePeriod)) {
            $domainExtensionConfiguration = $this->extension;
            if ($domainExtensionConfiguration) {
                $renewalGracePeriod[$this->tld] = $domainExtensionConfiguration->gracePeriod;
                if ($renewalGracePeriod[$this->tld] == -1) {
                    $renewalGracePeriod[$this->tld] = $domainExtensionConfiguration->defaultGracePeriod;
                }
            } else {
                $renewalGracePeriod[$this->tld] = TopLevel\GracePeriod::getForTld($this->getTldAttribute());
            }
        }
        return $renewalGracePeriod[$this->tld];
    }
    public function getGracePeriodFeeAttribute()
    {
        if (\WHMCS\Config\Setting::getValue("DisableDomainGraceAndRedemptionFees")) {
            return -1;
        }
        static $gracePeriodFee = array();
        if (!array_key_exists($this->tld, $gracePeriodFee)) {
            $domainExtensionConfiguration = $this->extension;
            $gracePeriodFee[$this->tld] = -1;
            if (0 <= $domainExtensionConfiguration->gracePeriodFee) {
                $gracePeriodFee[$this->tld] = $domainExtensionConfiguration->gracePeriodFee;
            }
        }
        return $gracePeriodFee[$this->tld];
    }
    public function getRedemptionGracePeriodAttribute()
    {
        if (\WHMCS\Config\Setting::getValue("DisableDomainGraceAndRedemptionFees")) {
            return -1;
        }
        static $redemptionGracePeriod = array();
        if (!array_key_exists($this->tld, $redemptionGracePeriod)) {
            $domainExtensionConfiguration = $this->extension;
            if ($domainExtensionConfiguration) {
                $redemptionGracePeriod[$this->tld] = $domainExtensionConfiguration->redemptionGracePeriod;
                if ($redemptionGracePeriod[$this->tld] == -1) {
                    $redemptionGracePeriod[$this->tld] = $domainExtensionConfiguration->defaultRedemptionGracePeriod;
                }
            } else {
                $redemptionGracePeriod[$this->tld] = TopLevel\RedemptionGracePeriod::getForTld($this->tld);
            }
        }
        return $redemptionGracePeriod[$this->tld];
    }
    public function getRedemptionGracePeriodFeeAttribute()
    {
        if (\WHMCS\Config\Setting::getValue("DisableDomainGraceAndRedemptionFees")) {
            return -1;
        }
        static $redemptionGracePeriodFee = array();
        if (!array_key_exists($this->tld, $redemptionGracePeriodFee)) {
            $domainExtensionConfiguration = $this->extension;
            $redemptionGracePeriodFee[$this->tld] = -1;
            if (0 <= $domainExtensionConfiguration->redemptionGracePeriodFee) {
                $redemptionGracePeriodFee[$this->tld] = $domainExtensionConfiguration->redemptionGracePeriodFee;
            }
        }
        return $redemptionGracePeriodFee[$this->tld];
    }
}

?>