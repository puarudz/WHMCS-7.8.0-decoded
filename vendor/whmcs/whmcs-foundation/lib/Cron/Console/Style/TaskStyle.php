<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Style;

class TaskStyle extends \Symfony\Component\Console\Style\SymfonyStyle
{
    protected $originalInput = NULL;
    protected $originalOutput = NULL;
    public function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->originalInput = $input;
        $this->originalOutput = $output;
        parent::__construct($input, $output);
    }
    public function getInput()
    {
        return $this->originalInput;
    }
    public function getOutput()
    {
        return $this->originalOutput;
    }
    public function isQuiet()
    {
        return self::VERBOSITY_QUIET === $this->getVerbosity();
    }
    public function isVerbose()
    {
        return self::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }
    public function isVeryVerbose()
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }
    public function isDebug()
    {
        return self::VERBOSITY_DEBUG <= $this->getVerbosity();
    }
    public function preparingQueue()
    {
        if ($this->isVerbose()) {
            $this->section("Queuing Tasks");
        }
        if ($this->hasForceOption()) {
            $filterMessage = "Force run any tasks: ignore \"in progress\" and \"is due\"";
        } else {
            $filterMessage = "Applying \"in progress\" and \"is due\" state filters";
        }
        if ($this->isVeryVerbose()) {
            $this->text($filterMessage);
        }
    }
    public function queueReady()
    {
        if ($this->isVerbose()) {
            $this->text("Task queues ready");
        }
    }
    public function startQueue($totalQueuedTasks = 0, $name = "")
    {
        if ($this->isVerbose()) {
            $this->newLine();
            if ($name) {
                $name .= " ";
            }
            $this->section("Executing " . $name . "Queue");
            if ($totalQueuedTasks) {
                $this->progressStart($totalQueuedTasks);
            } else {
                $this->text("No tasks to execute in queue");
            }
        }
    }
    public function advanceQueue()
    {
        if ($this->isVerbose()) {
            $this->progressAdvance(1);
        }
    }
    public function endQueue()
    {
        if ($this->isVerbose()) {
            $this->progressFinish();
        }
    }
    public function describeTask(\WHMCS\Scheduling\Task\TaskInterface $task)
    {
        if ($this->isDebug() && $task->getAccessLevel() == \WHMCS\Scheduling\Task\TaskInterface::ACCESS_USER) {
            $this->text(array("", $task->getName()));
        }
    }
    public function errorException(\Exception $e)
    {
        if ($this->isDebug()) {
            $this->error((string) $e);
        } else {
            if ($this->isVeryVerbose()) {
                $this->newLine();
                $this->error($e->getMessage());
            }
        }
    }
    public function hasForceOption()
    {
        $input = $this->getInput();
        if ($input->hasParameterOption(array("--force", "-F"))) {
            return true;
        }
        if ($input->hasOption("force") && $input->getOption("force")) {
            return true;
        }
        return false;
    }
    public function hasEmailReportOption()
    {
        return $this->getInput()->hasOption("email-report") && $this->getInput()->getOption("email-report");
    }
}

?>