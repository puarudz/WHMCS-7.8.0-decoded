<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class UpdateDomainExpiryStatus extends \WHMCS\Scheduling\Task\AbstractTask
{
    public $description = "Update Domain Expiry Status";
    protected $defaultPriority = 1690;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Update Domain Expiry Status";
    protected $defaultName = "Domain Expiry";
    protected $systemName = "UpdateDomainExpiryStatus";
    protected $outputs = array("expired" => array("defaultValue" => 0, "identifier" => "expired", "name" => "Domains Set to Expired"), "grace" => array("defaultValue" => 0, "identifier" => "grace", "name" => "Domains Set to Grace Period"), "redemption" => array("defaultValue" => 0, "identifier" => "redemption", "name" => "Domains Set to Redemption Grace Period"));
    protected $icon = "fas fa-link";
    protected $isBooleanStatus = false;
    protected $successCountIdentifier = "expired";
    protected $successKeyword = "Expired";
    private $currentDate = NULL;
    private $gracePeriodExtensions = NULL;
    private $redemptionPeriodExtensions = NULL;
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->currentDate = \WHMCS\Carbon::now();
        $definedExtensions = \WHMCS\Domains\Extension::all();
        $this->gracePeriodExtensions = $definedExtensions->filter(function (\WHMCS\Domains\Extension $tld) {
            return (0 <= $tld->gracePeriod || 0 < $tld->defaultGracePeriod) && 0 <= $tld->gracePeriodFee;
        });
        $this->redemptionPeriodExtensions = $definedExtensions->filter(function (\WHMCS\Domains\Extension $tld) {
            return (0 <= $tld->redemptionGracePeriod || 0 < $tld->defaultRedemptionGracePeriod) && 0 <= $tld->redemptionGracePeriodFee;
        });
    }
    private function getGracePeriodDataForTld($tld)
    {
        return $this->gracePeriodExtensions->where("extension", "." . $tld)->first();
    }
    private function getRedemptionPeriodDataForTld($tld)
    {
        return $this->redemptionPeriodExtensions->where("extension", "." . $tld)->first();
    }
    private function getDomainExpiryStatus(\WHMCS\Domain\Domain $domain)
    {
        $tld = $domain->tld;
        $expiryDate = $domain->expiryDate;
        if (!$expiryDate instanceof \WHMCS\Carbon) {
            $expiryDate = \WHMCS\Carbon::createFromFormat("Y-m-d", $expiryDate);
        }
        if (!$expiryDate->isPast()) {
            return $domain->status;
        }
        $expiryDifference = $expiryDate->diff($this->currentDate);
        $gracePeriodData = $this->getGracePeriodDataForTld($tld);
        $gracePeriodDays = 0;
        if ($gracePeriodData) {
            $gracePeriodDays = 0 <= $gracePeriodData->gracePeriod ? $gracePeriodData->gracePeriod : $gracePeriodData->defaultGracePeriod;
        }
        $redemptionPeriodData = $this->getRedemptionPeriodDataForTld($tld);
        $redemptionPeriodDays = 0;
        if ($redemptionPeriodData) {
            $redemptionPeriodDays = 0 <= $redemptionPeriodData->redemptionGracePeriod ? $redemptionPeriodData->redemptionGracePeriod : $redemptionPeriodData->defaultRedemptionGracePeriod;
        }
        $remainingExpiryDays = $expiryDifference->days;
        if (!$gracePeriodData && !$redemptionPeriodData) {
            return "Expired";
        }
        if ($gracePeriodData) {
            if ($remainingExpiryDays <= $gracePeriodDays) {
                return "Grace";
            }
            $remainingExpiryDays -= $gracePeriodDays;
        }
        if ($redemptionPeriodData && $remainingExpiryDays <= $redemptionPeriodDays) {
            return "Redemption";
        }
        return "Expired";
    }
    private function filterDomainsForUpdate(\Illuminate\Database\Eloquent\Collection $domains)
    {
        $domainsToUpdate = $domains->filter(function (\WHMCS\Domain\Domain $domain) {
            $expiryStatus = $this->getDomainExpiryStatus($domain);
            if ($expiryStatus != $domain->status) {
                $domain->status = $expiryStatus;
                return true;
            }
            return false;
        });
        return $domainsToUpdate;
    }
    private function updateDomainsByStatus(\Illuminate\Database\Eloquent\Collection $domains, $status)
    {
        $affectedDomains = $domains->filter(function (\WHMCS\Domain\Domain $domain) use($status) {
            return $domain->status == $status;
        });
        $affectedDomainsCount = $affectedDomains->count();
        if ($affectedDomainsCount) {
            $affectedDomainIds = $affectedDomains->pluck("id");
            \WHMCS\Domain\Domain::whereIn("id", $affectedDomainIds)->update(array("status" => $status));
        }
        $allowedOutputKeys = array("expired", "grace", "redemption");
        $outputKey = strtolower($status);
        if (in_array($outputKey, $allowedOutputKeys)) {
            $this->output($outputKey)->write($affectedDomainsCount);
        }
        return $affectedDomains;
    }
    private function getDomainsToProcess()
    {
        return \WHMCS\Domain\Domain::whereIn("status", array("Active", "Grace", "Redemption"))->where("expirydate", "<", date("Y-m-d"))->where("expirydate", "!=", "00000000")->get();
    }
    public function __invoke()
    {
        $domains = $this->getDomainsToProcess();
        $domainsToUpdate = $this->filterDomainsForUpdate($domains);
        $expiredDomains = $this->updateDomainsByStatus($domainsToUpdate, "Expired");
        $graceDomains = $this->updateDomainsByStatus($domainsToUpdate, "Grace");
        $redemptionDomains = $this->updateDomainsByStatus($domainsToUpdate, "Redemption");
        (new \WHMCS\Billing\Domains\Invoice())->cancelInvoiceForExpiredDomains($expiredDomains)->cancelOrGenerateInvoiceForDomainGraceAndRedemption($graceDomains)->cancelOrGenerateInvoiceForDomainGraceAndRedemption($redemptionDomains, "grace")->cancelOrGenerateInvoiceForDomainGraceAndRedemption($redemptionDomains, "redemption");
        return $this;
    }
}

?>