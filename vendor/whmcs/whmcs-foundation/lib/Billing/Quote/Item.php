<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Quote;

class Item extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblquoteitems";
    protected $booleans = array("taxable");
    protected $columnMap = array("isTaxable" => "taxable");
    public function quote()
    {
        return $this->belongsTo("WHMCS\\Billing\\Quote", "quoteid");
    }
}

?>