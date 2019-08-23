<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain\Ssl;

class Status extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblsslstatus";
    protected $fillable = array("user_id", "domain_name");
    protected $booleans = array("active");
    protected $dates = array("start_date", "expiry_date", "last_synced_date");
    protected $allowAutoResync = true;
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id")->notNull();
                $table->unsignedInteger("user_id")->default(0);
                $table->string("domain_name", 128)->default("");
                $table->string("subject_name", 128)->default("")->nullable();
                $table->string("subject_org", 128)->default("")->nullable();
                $table->string("issuer_name", 128)->default("")->nullable();
                $table->string("issuer_org", 128)->default("")->nullable();
                $table->dateTime("start_date")->nullable();
                $table->dateTime("expiry_date")->nullable();
                $table->boolean("active")->default(0);
                $table->dateTime("last_synced_date")->nullable();
                $table->timestamps();
                $table->index("domain_name", "domain_name");
            });
        }
    }
    public static function factory($userId, $domainName)
    {
        $status = self::firstOrNew(array("user_id" => $userId, "domain_name" => $domainName));
        return $status;
    }
    public function needsResync()
    {
        return $this->allowAutoResync && !($this->lastSyncedDate instanceof \WHMCS\Carbon && $this->lastSyncedDate->diffInHours() < 24);
    }
    public function disableAutoResync()
    {
        $this->allowAutoResync = false;
        return $this;
    }
    protected function downloadAndSyncCertificate()
    {
        $certificate = (new Downloader())->getCertificate($this->domainName);
        $this->subjectName = $certificate->getSubjectCommonName();
        $this->subjectOrg = $certificate->getSubjectOrg();
        $this->issuerName = $certificate->getIssuerName();
        $this->issuerOrg = $certificate->getIssuerOrg();
        $this->startDate = $certificate->getStartDate();
        $this->expiryDate = $certificate->getExpiryDate();
        $this->active = $certificate->getExpiryDate()->gte(\WHMCS\Carbon::now());
        return $this;
    }
    public function syncAndSave()
    {
        try {
            $this->downloadAndSyncCertificate();
        } catch (\WHMCS\Exception $e) {
            $this->active = false;
        }
        $this->lastSyncedDate = \WHMCS\Carbon::now();
        $this->save();
        return $this;
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "user_id");
    }
    public function isActive()
    {
        return (bool) $this->active;
    }
    public function isInactive()
    {
        if (!$this->exists) {
            return false;
        }
        return !$this->isActive();
    }
    public function getStatus()
    {
        if ($this->isActive()) {
            return "active";
        }
        return $this->isInactive() ? "inactive" : "unknown";
    }
    public function getStatusDisplayLabel()
    {
        if ($this->isActive()) {
            return \Lang::trans("sslState.validSsl");
        }
        if ($this->isInactive()) {
            return \Lang::trans("sslState.noSsl");
        }
        return \Lang::trans("sslState.sslUnknown");
    }
    public function getFormattedExpiryDate()
    {
        $expiry = $this->expiryDate;
        if ($expiry instanceof \WHMCS\Carbon) {
            return defined("ADMINAREA") ? $expiry->toAdminDateFormat() : $expiry->toClientDateFormat();
        }
        return "N/A";
    }
    public function getTooltipContent()
    {
        $langStringKey = "sslState.ssl" . ucfirst($this->getStatus());
        if (defined("ADMINAREA")) {
            if ($this->needsResync()) {
                $langStringKey = "global.loading";
            }
            return \AdminLang::trans($langStringKey, array(":expiry" => $this->getFormattedExpiryDate()));
        }
        if ($this->needsResync()) {
            $langStringKey = "loading";
        }
        return \Lang::trans($langStringKey, array(":expiry" => $this->getFormattedExpiryDate()));
    }
    public function getImagePath()
    {
        if ($this->needsResync()) {
            return $this->getImageFilepath("ssl-loading.gif");
        }
        return $this->getImageFilepath("ssl-" . $this->getStatus() . ".png");
    }
    protected function getImageFilepath($filename)
    {
        $asset = \DI::make("asset");
        return $asset->getImgPath() . "/ssl/" . $filename;
    }
    public function getClass()
    {
        $classes = "ssl-state ssl-" . $this->getStatus();
        if ($this->needsResync()) {
            $classes .= " ssl-sync";
        }
        return $classes;
    }
}

?>