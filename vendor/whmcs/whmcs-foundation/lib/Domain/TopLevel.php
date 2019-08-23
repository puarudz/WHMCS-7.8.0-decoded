<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain;

class TopLevel extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltlds";
    public $unique = array("tld");
    protected $fillable = array("tld");
    public function categories()
    {
        return $this->belongsToMany("WHMCS\\Domain\\TopLevel\\Category", "tbltld_category_pivot", "tld_id")->withTimestamps();
    }
}

?>