<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Affiliate;

class Referrer extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaffiliates_referrers";
    protected $fillable = array("affiliate_id", "referrer");
    public function hits()
    {
        return $this->hasMany("WHMCS\\Affiliate\\Hit", "referrer_id");
    }
}

?>