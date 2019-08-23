<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service;

class CancellationRequest extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcancelrequests";
    protected $columnMap = array("serviceId" => "relid", "whenToCancel" => "type");
    protected $dates = array("date");
    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "relid");
    }
}

?>