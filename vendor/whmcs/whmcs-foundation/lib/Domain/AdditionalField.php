<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain;

class AdditionalField extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbldomainsadditionalfields";
    protected $fillable = array("domainid", "name");
    public function domain()
    {
        return $this->belongsTo("WHMCS\\Domain\\Domain", "domainid");
    }
}

?>