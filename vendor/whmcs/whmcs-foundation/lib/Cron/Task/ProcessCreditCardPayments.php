<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class ProcessCreditCardPayments extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1540;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Credit Card Charges";
    protected $defaultName = "Credit Card Charges";
    protected $systemName = "ProcessCreditCardPayments";
    protected $outputs = array("captured" => array("defaultValue" => 0, "identifier" => "captured", "name" => "Captured Payments"), "failures" => array("defaultValue" => 0, "identifier" => "failures", "name" => "Failed Capture Payments"));
    protected $icon = "fas fa-credit-card";
    protected $successCountIdentifier = "captured";
    protected $failureCountIdentifier = "failures";
    protected $successKeyword = "Captured";
    protected $failureKeyword = "Declined";
    protected $failureUrl = "invoices.php?status=Unpaid&last_capture_attempt=";
    public function __invoke()
    {
        if (!function_exists("ccProcessing")) {
            include_once ROOTDIR . "/includes/ccfunctions.php";
        }
        ccProcessing($this);
        return $this;
    }
    public function getFailureUrl()
    {
        $date = \WHMCS\Carbon::now()->toDateString();
        if (\App::isInRequest("date")) {
            $date = \App::getFromRequest("date");
        }
        return parent::getFailureUrl() . fromMySQLDate($date);
    }
}

?>