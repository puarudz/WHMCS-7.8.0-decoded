<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait CreditCardDetailsTrait
{
    use SensitiveDataTrait;
    private $cardCvv = "";
    public function getSensitiveDataAttributeName()
    {
        return "card_data";
    }
    public function getCardCvv()
    {
        return $this->cardCvv;
    }
    public function setCardCvv($value)
    {
        $this->cardCvv = $value;
        return $this;
    }
    public function getCardNumber()
    {
        return (string) $this->getSensitiveProperty("cardNumber");
    }
    public function setCardNumber($value)
    {
        $value = preg_replace("/[^0-9]/", "", $value);
        $this->setSensitiveProperty("cardNumber", $value);
        if ($value) {
            if (!function_exists("getCardTypeByCardNumber")) {
                require_once ROOTDIR . "/includes/ccfunctions.php";
            }
            $this->setCardType(getCardTypeByCardNumber($value));
            $this->setLastFour(substr($value, -4));
        }
        return $this;
    }
    public function getLastFour()
    {
        return (string) $this->last_four;
    }
    public function setLastFour($value)
    {
        $this->last_four = $value;
        return $this;
    }
    public function getMaskedCardNumber()
    {
        $masked = "";
        $lastFour = $this->getLastFour();
        if ($lastFour) {
            $masked = str_pad($lastFour, 16, "*", STR_PAD_LEFT);
        }
        return $masked;
    }
    public function getExpiryDate()
    {
        $date = $this->getRawAttribute("expiry_date");
        if ($date && $date != "0000-00-00 00:00:00") {
            return (new \WHMCS\Carbon($date))->startOfDay();
        }
        return null;
    }
    public function setExpiryDate(\WHMCS\Carbon $value)
    {
        $date = "";
        if (1 < (int) $value->year) {
            $date = $value->startOfDay()->toDateString();
        }
        $this->expiry_date = $date;
        return $this;
    }
    public function isExpired()
    {
        $expiryDate = $this->getExpiryDate();
        return $expiryDate && $expiryDate->isPast();
    }
    public function getCardType()
    {
        return (string) $this->card_type;
    }
    public function setCardType($value)
    {
        $this->card_type = $value;
        return $this;
    }
    public function getStartDate()
    {
        $date = $this->getSensitiveProperty("startDate");
        if ($date) {
            return \WHMCS\Carbon::fromCreditCard($date);
        }
        return null;
    }
    public function setStartDate(\WHMCS\Carbon $value)
    {
        $value = $value->toCreditCard();
        $this->setSensitiveProperty("startDate", $value);
        return $this;
    }
    public function getIssueNumber()
    {
        return (string) $this->getSensitiveProperty("issueNumber");
    }
    public function setIssueNumber($value)
    {
        $this->setSensitiveProperty("issueNumber", $value);
        return $this;
    }
    public function getDisplayName()
    {
        return $this->getType() . "-" . $this->getLastFour();
    }
    public function validateRequiredValuesPreSave()
    {
        if (!$this->getCardNumber()) {
            throw new \RuntimeException("Card number is required");
        }
        if (strlen($this->getCardNumber()) < 13) {
            throw new \RuntimeException("Card number must be at least 13 chars");
        }
        if (!$this->getExpiryDate()) {
            throw new \RuntimeException("Card expiry date is required");
        }
        return $this;
    }
    public function validateRequiredValuesForEditPreSave()
    {
        if (!$this->getExpiryDate()) {
            throw new \RuntimeException("Card expiry date is required");
        }
        return $this;
    }
}

?>