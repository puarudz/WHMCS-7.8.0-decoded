<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling\Task;

class AbstractTask extends \Illuminate\Database\Eloquent\Model implements \WHMCS\Cron\DecoratorItemInterface, TaskInterface
{
    use DecoratorItemTrait;
    use RegisterTrait;
    protected $table = "tbltask";
    protected $guarded = array();
    protected $casts = array("is_enabled" => "boolean", "is_periodic" => "boolean");
    protected $defaultPriority = 0;
    protected $defaultIsEnabled = true;
    protected $defaultIsPeriodic = true;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "A generic task invoked periodically";
    protected $defaultName = "Generic task";
    protected $accessLevel = TaskInterface::ACCESS_USER;
    protected $preExecuteCallbacks = array();
    protected $postExecuteCallbacks = array();
    protected $outputs = array();
    protected $callback = NULL;
    protected $skipDailyCron = false;
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->integer("priority")->default(0);
                $table->string("class_name", 255)->default("");
                $table->tinyInteger("is_enabled")->default(1);
                $table->tinyInteger("is_periodic")->default(1);
                $table->integer("frequency")->unsigned()->default(0);
                $table->string("name", 255)->default("");
                $table->text("description")->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    protected static function boot()
    {
        $alwaysHaveStatusRelationship = function ($model) {
            if (!$model->status()->count()) {
                $model->status()->create(array());
            }
        };
        static::saved($alwaysHaveStatusRelationship);
    }
    public static function firstOfClassOrNew(array $attributes = array())
    {
        $attributes = array_merge(array("class_name" => static::class), $attributes);
        $model = parent::firstOrNew($attributes);
        if (!$model->exists) {
            $model->fill($model->getDefaultAttributes());
        }
        return $model;
    }
    public static function register($allowDuplicates = false)
    {
        if ($allowDuplicates) {
            $model = (new static())->newInstance();
            $model->fill($model->getDefaultAttributes());
        } else {
            $model = static::firstOfClassOrNew();
        }
        if (!$model->exists) {
            $model->save();
        }
        if (is_null($model->getStatus())) {
            $status = new Status();
            $model->status()->save($status);
        }
        return $model;
    }
    public function getDefaultAttributes()
    {
        return array("priority" => $this->defaultPriority, "class_name" => static::class, "is_enabled" => $this->defaultIsEnabled, "is_periodic" => $this->defaultIsPeriodic, "frequency" => $this->defaultFrequency, "description" => $this->defaultDescription, "name" => $this->defaultName);
    }
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    protected function execute()
    {
        if (is_callable($this)) {
            call_user_func_array($this, func_get_args());
        } else {
            if ($this->callback && is_callable($this->callback)) {
                $func = $this->callback;
                if ($func instanceof \Closure) {
                    $func = $func->bindTo($this);
                }
                call_user_func_array($func, func_get_args());
            }
        }
        return $this;
    }
    public function run()
    {
        if ($this->preExecuteCallbacks) {
            $status = true;
            foreach ($this->preExecuteCallbacks as $event) {
                if (is_callable($event)) {
                    $status = call_user_func($event, $this);
                }
                if (!$status) {
                    return $this;
                }
            }
        }
        $this->execute();
        foreach ($this->postExecuteCallbacks as $event) {
            if (is_callable($event)) {
                call_user_func($event, $this);
            }
        }
        if (!$this->isPeriodic()) {
            $this->is_enabled = 0;
            $this->save();
        }
        return $this;
    }
    public function getPriority()
    {
        return $this->priority;
    }
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    public function getFrequencyMinutes()
    {
        return $this->frequency;
    }
    public function setFrequencyMinutes($minutes)
    {
        $this->frequency = $minutes;
        return $this;
    }
    public function anticipatedNextRun(\WHMCS\Carbon $date = NULL)
    {
        if (is_null($date)) {
            $date = \WHMCS\Carbon::now();
        }
        return $date->copy()->addMinutes($this->getFrequencyMinutes())->second("00");
    }
    protected function anticipatedNextMonthlyRun($checkDay, \WHMCS\Carbon $date = NULL)
    {
        $checkDate = \WHMCS\Carbon::now()->second("00");
        $nextMonth = \WHMCS\Carbon::now()->second("00")->startOfMonth()->addMonth()->hour($checkDate->format("H"))->minute($checkDate->format("i"));
        $daysInMonth = $nextMonth->daysInMonth;
        if ($daysInMonth < $checkDay) {
            $checkDay = $daysInMonth;
        }
        $checkDate->day($checkDay);
        if (is_null($date)) {
            $date = \WHMCS\Carbon::now()->second("00");
        } else {
            $date = $date->copy();
        }
        if ($date->isFuture()) {
            return $date;
        }
        if ($date->format("d") === $checkDate->format("d")) {
            return $date->addMonthNoOverflow();
        }
        return $nextMonth->day($checkDay);
    }
    public function isEnabled()
    {
        return (bool) $this->is_enabled;
    }
    public function setEnabled($state)
    {
        $this->is_enabled = (bool) $state;
        return $this;
    }
    public function isPeriodic()
    {
        return (bool) $this->is_periodic;
    }
    public function setPeriodic($state)
    {
        $this->is_periodic = (bool) $state;
        return $this;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function getStatusAttribute($status)
    {
        if (!$status) {
            $status = Status::firstOrCreate(array("task_id" => $this->id));
        }
        return $status;
    }
    public function status()
    {
        return $this->hasOne("WHMCS\\Scheduling\\Task\\Status", "task_id");
    }
    public function getSystemName()
    {
        if ($this->systemName) {
            return $this->systemName;
        }
        $classname = static::class;
        $namespaces = explode("\\", $classname);
        return strtolower(array_pop($namespaces));
    }
    public function getAccessLevel()
    {
        return $this->accessLevel;
    }
    public function getOutputKeys()
    {
        return $this->outputs;
    }
    public function isDailyTask()
    {
        return $this->getFrequencyMinutes() <= 1440;
    }
    public function monthlyDayOfExecution()
    {
        return \WHMCS\Carbon::now()->startOfDay();
    }
    public function isSkipDailyCron()
    {
        return $this->skipDailyCron;
    }
}

?>