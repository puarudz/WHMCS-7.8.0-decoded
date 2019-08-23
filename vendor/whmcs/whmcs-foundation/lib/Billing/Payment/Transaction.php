<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Payment;

class Transaction extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaccounts";
    protected $dates = array("date");
    protected $columnMap = array("clientId" => "userid", "currencyId" => "currency", "paymentGateway" => "gateway", "exchangeRate" => "rate", "transactionId" => "transid", "amountIn" => "amountin", "amountOut" => "amountout", "invoiceId" => "invoiceid", "refundId" => "refundid");
    public $timestamps = false;
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice", "invoiceid");
    }
}

?>