<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Queue extends \WHMCS\Model\AbstractModel
{
    protected $columnMap = array("lastAttempt" => "last_attempt", "lastAttemptError" => "last_attempt_error");
    protected $table = "tblmodulequeue";
    protected $primaryKey = "id";
    protected $casts = array("last_attempt" => "datetime");
    protected $dates = array("last_attempt");
    protected $fillable = array("service_type", "service_id", "module_name", "module_action", "completed");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("service_type", 20)->default("");
                $table->unsignedInteger("service_id")->default(0);
                $table->string("module_name", 64)->default("");
                $table->string("module_action", 64)->default("");
                $table->timestamp("last_attempt")->default("0000-00-00 00:00:00");
                $table->text("last_attempt_error");
                $table->unsignedSmallInteger("num_retries")->default(0);
                $table->boolean("completed")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    public function scopeIncomplete(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereCompleted(0);
    }
    public static function add($serviceType, $serviceId, $module, $moduleAction, $lastAttemptError)
    {
        if (defined("NO_QUEUE") && NO_QUEUE == true) {
            return true;
        }
        if (is_null($lastAttemptError)) {
            $lastAttemptError = "";
        }
        $queue = self::firstOrNew(array("service_type" => $serviceType, "service_id" => $serviceId, "module_name" => $module, "module_action" => $moduleAction, "completed" => 0));
        $queue->lastAttempt = \WHMCS\Carbon::now();
        $queue->lastAttemptError = $lastAttemptError;
        if ($queue->exists) {
            $queue->numRetries++;
        } else {
            $queue->numRetries = 0;
        }
        return $queue->save();
    }
    public function getLastAttemptErrorAttribute()
    {
        $value = $this->attributes["last_attempt_error"];
        if (!$value) {
            $value = \AdminLang::trans("moduleQueue.unknownError");
        }
        return $value;
    }
    public function setLastAttemptErrorAttribute($value)
    {
        $this->attributes["last_attempt_error"] = $value;
    }
    public static function resolve($serviceType, $serviceId, $module, $moduleAction)
    {
        $queue = self::whereServiceType($serviceType)->whereServiceId($serviceId)->whereModuleName($module)->whereModuleAction($moduleAction)->whereCompleted(0)->first();
        if ($queue) {
            $queue->completed = 1;
            $queue->lastAttempt = \WHMCS\Carbon::now();
            return $queue->save();
        }
        return true;
    }
    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "service_id");
    }
    public function domain()
    {
        return $this->belongsTo("WHMCS\\Domain\\Domain", "service_id");
    }
    public function addon()
    {
        return $this->belongsTo("WHMCS\\Service\\Addon", "service_id");
    }
}

?>