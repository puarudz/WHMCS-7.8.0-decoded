<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Support\Ticket;

class Reply extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblticketreplies";
    protected $columnMap = array("clientId" => "userid", "contactId" => "contactid");
    protected $dates = array("date");
    protected $hidden = array("editor");
    public $timestamps = false;
    const CREATED_AT = "date";
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("ordered", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblticketreplies.date");
        });
    }
    public function ticket()
    {
        return $this->belongsTo("WHMCS\\Support\\Ticket", "tid");
    }
}

?>