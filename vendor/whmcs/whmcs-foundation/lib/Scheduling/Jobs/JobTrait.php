<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling\Jobs;

trait JobTrait
{
    protected $jobName = "";
    protected $jobClassName = "";
    protected $jobMethodName = "";
    protected $jobMethodArguments = array();
    protected $jobDigestHash = "";
    protected $jobAvailableAt = NULL;
    public function jobName($name = "")
    {
        if ($name) {
            $this->jobName = $name;
        }
        return $this->jobName;
    }
    public function jobClassName($className = "")
    {
        if ($className) {
            $this->jobClassName = $className;
        } else {
            if (!$className && !$this->jobClassName) {
                $this->jobClassName = static::class;
            }
        }
        return $this->jobClassName;
    }
    public function jobMethodName($methodName = "")
    {
        if ($methodName) {
            $this->jobMethodName = $methodName;
        }
        return $this->jobMethodName;
    }
    public function jobMethodArguments($arguments = array())
    {
        if ($arguments) {
            $this->jobMethodArguments = $arguments;
        }
        return $this->jobMethodArguments;
    }
    public function jobAvailableAt(\WHMCS\Carbon $date = NULL)
    {
        if ($date) {
            $this->jobAvailableAt = $date;
        } else {
            if (!$date && !$this->jobAvailableAt) {
                $this->jobAvailableAt = \WHMCS\Carbon::now();
            }
        }
        return $this->jobAvailableAt;
    }
    public function jobDigestHash($hash = "")
    {
        if ($hash) {
            $this->jobDigestHash = $hash;
        }
        return $this->jobDigestHash;
    }
}

?>