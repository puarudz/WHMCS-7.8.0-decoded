<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Invoice;

class Item extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblinvoiceitems";
    public $timestamps = false;
    protected $booleans = array("taxed");
    protected $dates = array("dueDate");
    protected $columnMap = array("relatedEntityId" => "relid");
    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice", "invoiceid");
    }
    public function addon()
    {
        return $this->belongsTo("WHMCS\\Service\\Addon", "relid");
    }
    public function domain()
    {
        return $this->belongsTo("WHMCS\\Domain\\Domain", "relid");
    }
    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "relid");
    }
}

?>