<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User\Client;

class Affiliate extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaffiliates";
    protected $columnMap = array("visitorCount" => "visitors", "commissionType" => "paytype", "paymentAmount" => "payamount", "isPaidOneTimeCommission" => "onetime", "amountWithdrawn" => "withdrawn");
    protected $dates = array("date");
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "clientid");
    }
}

?>