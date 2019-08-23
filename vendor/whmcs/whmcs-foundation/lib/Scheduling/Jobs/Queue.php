<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling\Jobs;

final class Queue extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbljobs_queue";
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("name", 255)->default("");
                $table->string("class_name", 255)->default("");
                $table->string("method_name", 255)->default("");
                $table->text("input_parameters");
                $table->timestamp("available_at");
                $table->string("digest_hash", 255)->default("");
                $table->timestamps();
            });
        }
    }
    public static function add($name, $class, $method, array $inputParams = array(), $delay = 0, $replaceExisting = false)
    {
        $queue = new self();
        $job = $queue->factoryJobInstance($name, $class, $method, $inputParams, $delay);
        if (!$job) {
            return false;
        }
        return $queue->addJob($job, $replaceExisting);
    }
    public static function addOrUpdate($name, $class, $method, $inputParams, $delay = 0)
    {
        return self::add($name, $class, $method, $inputParams, $delay, true);
    }
    public static function remove($name)
    {
        self::where("name", $name)->delete();
    }
    public static function exists($name)
    {
        return !is_null(self::where("name", $name)->first());
    }
    public function encryptArguments(array $data)
    {
        $key = sha1(\DI::make("config")->cc_encryption_hash);
        $data = $this->aesEncryptValue(json_encode($data), $key);
        $encrypted = $data;
        return $encrypted;
    }
    public function decryptArguments($data)
    {
        $key = sha1(\DI::make("config")->cc_encryption_hash);
        $decrypted = $this->aesDecryptValue($data, $key);
        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            $data = array();
        }
        return $data;
    }
    public function createDigestHash(\WHMCS\Scheduling\Contract\JobInterface $job)
    {
        $signatureKey = hash("sha256", \DI::make("config")->cc_encryption_hash, true);
        $jobSignatureBase = $job->jobClassName() . $job->jobMethodName() . safe_serialize($job->jobMethodArguments()) . $job->jobAvailableAt()->toDateTimeString();
        return hash_hmac("sha256", $jobSignatureBase, $signatureKey);
    }
    public function verifyDigestHash(\WHMCS\Scheduling\Contract\JobInterface $job)
    {
        $storedHash = $job->jobDigestHash();
        $verifyHash = $this->createDigestHash($job);
        if (empty($storedHash) || empty($verifyHash)) {
            return false;
        }
        return hash_equals($verifyHash, $storedHash);
    }
    protected function factoryJobFromClassName($className)
    {
        if (class_exists($className)) {
            try {
                $class = new $className();
                if ($class instanceof \WHMCS\Scheduling\Contract\JobInterface) {
                    return $class;
                }
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
    protected function factoryJobInstance($name, $class, $method, $inputParams, $delay = 0)
    {
        $job = $this->factoryJobFromClassName($class);
        if (!$job) {
            return null;
        }
        $job->jobName($name);
        $job->jobMethodName($method);
        $job->jobMethodArguments($inputParams);
        if ($delay) {
            $availableAt = \WHMCS\Carbon::now()->addMinutes($delay);
        } else {
            $availableAt = \WHMCS\Carbon::now();
        }
        $job->jobAvailableAt($availableAt);
        return $job;
    }
    public function executeJob()
    {
        $className = $this->class_name;
        $methodName = $this->method_name;
        $encryptedArguments = $this->input_parameters;
        $storedHash = $this->digest_hash;
        $job = $this->factoryJobFromClassName($className);
        if (!$job) {
            throw new \WHMCS\Exception\Scheduling\Jobs\QueueException("An empty job or an unsuitable class queued for execution");
        }
        if (empty($methodName) || !method_exists($job, $methodName)) {
            throw new \WHMCS\Exception\Scheduling\Jobs\QueueException("Method does not exist in a job queued for execution");
        }
        $job->jobName($this->name);
        $job->jobMethodName($methodName);
        $methodArguments = $this->decryptArguments($encryptedArguments);
        $job->jobMethodArguments($methodArguments);
        $job->jobAvailableAt(new \WHMCS\Carbon($this->available_at));
        $job->jobDigestHash($storedHash);
        $this->validateJob($job);
        if (!$this->verifyDigestHash($job)) {
            throw new \WHMCS\Exception\Scheduling\Jobs\QueueException("Job signature validation failed");
        }
        $job->{$methodName}(...$methodArguments);
    }
    public function validateJob(\WHMCS\Scheduling\Contract\JobInterface $job)
    {
        $msg = "";
        if (!$job->jobName()) {
            $msg = "Job missing \"name\" attribute";
        } else {
            if (!$job->jobClassName()) {
                $msg = "Job missing \"ClassName\" attribute";
            } else {
                if (!$job->jobMethodName()) {
                    $msg = "Job missing \"MethodName\" attribute";
                } else {
                    if (!$job->jobAvailableAt()) {
                        $msg = "Job missing \"AvailableAt\" attribute";
                    } else {
                        if (!$job->jobDigestHash()) {
                            $msg = "Job missing \"DigestHash\" attribute";
                        }
                    }
                }
            }
        }
        if ($msg) {
            throw new \WHMCS\Exception\Model\EmptyValue($msg);
        }
        return true;
    }
    public function addJob(\WHMCS\Scheduling\Contract\JobInterface $job, $replaceExisting = false)
    {
        $result = false;
        try {
            $queue = new static();
            if (!$job->jobDigestHash()) {
                $job->jobDigestHash($queue->createDigestHash($job));
            }
            $queue->validateJob($job);
            $queue->name = $job->jobName();
            $queue->class_name = $job->jobClassName();
            $queue->method_name = $job->jobMethodName();
            $queue->input_parameters = $queue->encryptArguments($job->jobMethodArguments());
            $queue->available_at = $job->jobAvailableAt()->toDateTimeString();
            $queue->digest_hash = $job->jobDigestHash();
            if ($replaceExisting) {
                self::where("name", $job->jobName())->delete();
            }
            $result = $queue->save();
        } catch (\Exception $e) {
            logActivity("Exception thrown when adding job to queue" . " - " . $e->getMessage());
        }
        return $result;
    }
}

?>