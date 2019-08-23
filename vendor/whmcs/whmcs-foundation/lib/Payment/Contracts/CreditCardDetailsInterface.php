<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface CreditCardDetailsInterface
{
    public function getCardNumber();
    public function setCardNumber($value);
    public function getCardCvv();
    public function setCardCvv($value);
    public function getLastFour();
    public function setLastFour($value);
    public function getMaskedCardNumber();
    public function getExpiryDate();
    public function setExpiryDate(\WHMCS\Carbon $value);
    public function getCardType();
    public function setCardType($value);
    public function getStartDate();
    public function setStartDate(\WHMCS\Carbon $value);
    public function getIssueNumber();
    public function setIssueNumber($value);
}

?>