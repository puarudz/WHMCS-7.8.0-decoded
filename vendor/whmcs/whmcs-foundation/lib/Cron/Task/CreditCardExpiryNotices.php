<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class CreditCardExpiryNotices extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1650;
    protected $defaultFrequency = 43200;
    protected $defaultDescription = "Sending Credit Card Expiry Reminders";
    protected $defaultName = "Credit Card Expiry Notices";
    protected $systemName = "CreditCardExpiryNotices";
    protected $outputs = array("notices" => array("defaultValue" => 0, "identifier" => "notices", "name" => "Credit Card Expiry Notices"), "deleted" => array("defaultValue" => 0, "identifier" => "deleted", "name" => "Expired Credit Cards Deleted"));
    protected $icon = "fas fa-credit-card";
    protected $isBooleanStatus = false;
    protected $successCountIdentifier = "notices";
    protected $successKeyword = "Sent";
    public function monthlyDayOfExecution()
    {
        $dayForNotices = (int) \WHMCS\Config\Setting::getValue("CCDaySendExpiryNotices");
        $daysInThisMonth = \WHMCS\Carbon::now()->daysInMonth;
        if ($daysInThisMonth < $dayForNotices) {
            $dayForNotices = $daysInThisMonth;
        }
        return \WHMCS\Carbon::now()->startOfDay()->day($dayForNotices);
    }
    public function anticipatedNextRun(\WHMCS\Carbon $date = NULL)
    {
        $correctDayDate = $this->anticipatedNextMonthlyRun((int) \WHMCS\Config\Setting::getValue("CCDaySendExpiryNotices"), $date);
        if ($date) {
            $correctDayDate->hour($date->format("H"))->minute($date->format("i"));
        }
        return $correctDayDate;
    }
    public function __invoke()
    {
        $deletedCardCount = 0;
        if (!\WHMCS\Carbon::now()->isSameDay($this->monthlyDayOfExecution())) {
            return $this;
        }
        $expiryEmailCount = 0;
        $today = \WHMCS\Carbon::today();
        $expiryYear = $today->year;
        $expiryMonth = $today->month;
        $cardClasses = array("WHMCS\\Payment\\PayMethod\\Adapter\\CreditCard", "WHMCS\\Payment\\PayMethod\\Adapter\\RemoteCreditCard");
        $expiringCards = array();
        foreach ($cardClasses as $cardClass) {
            $expiringCards = array_merge($expiringCards, $cardClass::with("payMethod")->whereYear("expiry_date", "=", $expiryYear)->whereMonth("expiry_date", "=", $expiryMonth)->get());
        }
        foreach ($expiringCards as $expiringCard) {
            if ($expiringCard->client->status !== "Active") {
                continue;
            }
            sendMessage("Credit Card Expiring Soon", $expiringCard->client->id, array("card_id" => $expiringCard->id, "card_type" => $expiringCard->card_type, "card_expiry" => $expiringCard->expiry_date->toCreditCard(), "card_last_four" => $expiringCard->last_four, "card_description" => $expiringCard->payMethod->description));
            $expiryEmailCount++;
        }
        if (!\WHMCS\Config\Setting::getValue("CCDoNotRemoveOnExpiry")) {
            $expiredCards = array();
            foreach ($cardClasses as $cardClass) {
                $expiredCards = array_merge($expiredCards, $cardClass::where("expiry_date", "<", $today->firstOfMonth()->toDateString())->get());
            }
            foreach ($expiredCards as $expiredCard) {
                $expiredCard->payMethod->delete();
                $expiredCard->delete();
                $deletedCardCount++;
            }
        }
        logActivity("Cron Job: Sent " . $expiryEmailCount . " Credit Card Expiry Notices");
        logActivity("Cron Job: Sent " . $deletedCardCount . " Expired Credit Cards Deleted");
        $this->output("notices")->write($expiryEmailCount);
        $this->output("deleted")->write($deletedCardCount);
        return $this;
    }
}

?>