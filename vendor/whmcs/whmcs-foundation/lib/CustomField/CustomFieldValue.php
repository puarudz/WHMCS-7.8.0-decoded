<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\CustomField;

class CustomFieldValue extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcustomfieldsvalues";
    protected $columnMap = array("relatedId" => "relid");
    protected $fillable = array("fieldid", "relid");
    public function customField()
    {
        return $this->belongsTo("WHMCS\\CustomField", "fieldid");
    }
    public function addon()
    {
        return $this->belongsTo("WHMCS\\Service\\Addon", "relid");
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "relid");
    }
    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "relid");
    }
}

?>