<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class RunJobsQueue extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 2000;
    protected $defaultFrequency = 5;
    protected $defaultDescription = "Execute queued jobs that are due for execution.";
    protected $defaultName = "Run Jobs Queue";
    protected $systemName = "RunJobsQueue";
    protected $outputs = array("executed" => array("defaultValue" => 0, "identifier" => "executed", "name" => "Jobs Executed"));
    protected $icon = "fas fa-gavel";
    protected $successCountIdentifier = "jobs.queue";
    protected $successKeyword = "Executed";
    public function __invoke()
    {
        $this->output("executed")->write($this->executeQueuedJobs());
        return $this;
    }
    public function executeQueuedJobs()
    {
        $executedCount = 0;
        foreach (\WHMCS\Scheduling\Jobs\Queue::where("available_at", "<=", \WHMCS\Carbon::now()->toDateTimeString())->get() as $job) {
            $className = $job->class_name;
            $methodName = $job->method_name;
            try {
                $job->delete();
                $job->executeJob();
                $executedCount++;
            } catch (\Exception $e) {
                logActivity("Exception thrown in jobs queue execution" . " (" . $className . "::" . $methodName . ")" . " - " . $e->getMessage());
            }
        }
        return $executedCount;
    }
}

?>