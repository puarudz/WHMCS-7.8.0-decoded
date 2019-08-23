<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Eula
{
    protected $eulaText = "";
    protected $effectiveDate = false;
    const SETTING_EULA_ACCEPTED = "EulaAgreementAccepted";
    public function isEulaAccepted()
    {
        $agreement = \WHMCS\Config\Setting::getValue(static::SETTING_EULA_ACCEPTED);
        if (!$agreement) {
            return false;
        }
        $agreement = json_decode($agreement, true);
        if (!is_array($agreement) || empty($agreement["hash"]) || empty($agreement["date"])) {
            return false;
        }
        $eula = md5($this->getEulaText());
        if ($eula != $agreement["hash"]) {
            return false;
        }
        if ($this->getEffectiveDate()) {
            try {
                $date = \WHMCS\Carbon::createFromFormat("Y-m-d", $agreement["date"]);
                if ($date->lt($this->getEffectiveDate())) {
                    return false;
                }
            } catch (\Exception $e) {
            }
        }
        return true;
    }
    public function markAsAccepted(\WHMCS\User\UserInterface $admin)
    {
        $data = array("hash" => md5($this->getEulaText()), "date" => \WHMCS\Carbon::now()->format("Y-m-d"), "admin" => $admin->id . ":" . $admin->getUsernameAttribute());
        \WHMCS\Config\Setting::setValue(static::SETTING_EULA_ACCEPTED, json_encode($data));
        return $this;
    }
    public function markAsNotAccepted()
    {
        \WHMCS\Config\Setting::setValue(static::SETTING_EULA_ACCEPTED, "");
        return $this;
    }
    protected function loadData()
    {
        $data = (include ROOTDIR . "/resources/views/admin/assent/shared/data-eula.php");
        if (!is_array($data)) {
            return false;
        }
        if (!empty($data["eula"]) && is_string($data["eula"])) {
            $this->setEulaText($data["eula"]);
        }
        if (!empty($data["effectiveDate"]) && is_string($data["effectiveDate"])) {
            try {
                $date = \WHMCS\Carbon::createFromFormat("Y-m-d", $data["effectiveDate"]);
                $this->setEffectiveDate($date);
            } catch (\Exception $e) {
            }
        } else {
            $this->setEffectiveDate(null);
        }
        return $this;
    }
    public function getEulaText()
    {
        if (!$this->eulaText) {
            $this->loadData();
        }
        return $this->eulaText;
    }
    public function setEulaText($eulaText)
    {
        $this->eulaText = $eulaText;
        return $this;
    }
    public function getEffectiveDate()
    {
        if ($this->effectiveDate === false) {
            $this->loadData();
        }
        return $this->effectiveDate;
    }
    public function setEffectiveDate($effectiveDate)
    {
        $this->effectiveDate = $effectiveDate;
        return $this;
    }
}

?>