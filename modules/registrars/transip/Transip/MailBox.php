<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_MailBox
{
    public $address = NULL;
    public $spamCheckerStrength = NULL;
    public $maxDiskUsage = NULL;
    public $hasVacationReply = NULL;
    public $vacationReplySubject = NULL;
    public $vacationReplyMessage = NULL;
    const SPAMCHECKER_STRENGTH_AVERAGE = "AVERAGE";
    const SPAMCHECKER_STRENGTH_OFF = "OFF";
    const SPAMCHECKER_STRENGTH_LOW = "LOW";
    const SPAMCHECKER_STRENGTH_HIGH = "HIGH";
    public function __construct($address, $spamCheckerStrength = "AVERAGE", $maxDiskUsage = 20, $hasVacationReply = false, $vacationReplySubject = "", $vacationReplyMessage = "")
    {
        $this->address = $address;
        $this->spamCheckerStrength = $spamCheckerStrength;
        $this->maxDiskUsage = $maxDiskUsage;
        $this->hasVacationReply = $hasVacationReply;
        $this->vacationReplySubject = $vacationReplySubject;
        $this->vacationReplyMessage = $vacationReplyMessage;
    }
}

?>