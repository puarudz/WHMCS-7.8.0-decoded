<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Affiliate;

class Hit extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblaffiliates_hits";
    public $timestamps = false;
    public $dates = array("created_at");
    protected $fillable = array("affiliate_id", "referrer_id", "created_at");
    public function referrer()
    {
        return $this->belongsTo("WHMCS\\Affiliate\\Referrer", "referrer_id", "id");
    }
}

?>