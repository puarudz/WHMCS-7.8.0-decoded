<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class RegisterDefaultsCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName("defaults")->setDescription("Reset defaults for automation tasks")->setHelp("This command will reset all default automated tasks");
    }
    public function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $io = new \WHMCS\Cron\Console\Style\TaskStyle($input, $output);
        $io->section("Delete Registered Tasks");
        $proceed = $io->confirm("Delete all current registered tasks?", false);
        if ($proceed) {
            $oldTasks = \WHMCS\Scheduling\Task\AbstractTask::all();
            $oldTasks = $oldTasks->isLevel(\WHMCS\Scheduling\Task\TaskInterface::ACCESS_USER);
            foreach ($oldTasks as $task) {
                $io->text($task->getName());
                $task->getStatus()->delete();
                $task->delete();
            }
        }
        $tasks = array();
        $io->section("Register Tasks");
        $proceed = $io->confirm("Register any all possible tasks?", false);
        if ($proceed) {
            self::registerAnyNonSystemTask($io);
        }
        $io->section("Set Next Due for Tasks");
        $proceed = $io->confirm("Set next due for all tasks to \"now\"?", false);
        if ($proceed) {
            $tasks = \WHMCS\Scheduling\Task\AbstractTask::all();
            self::resetNextRun($tasks, \WHMCS\Carbon::now());
        }
        $io->section("Last Daily Cron");
        $proceed = $io->confirm("Reset Last Daily Cron Invocation?", false);
        if ($proceed) {
            \WHMCS\Config\Setting::setValue("lastDailyCronInvocationTime", "");
        }
        $cronStatus = new \WHMCS\Cron\Status();
        $dailyCronHour = $cronStatus->getDailyCronExecutionHour()->format("H");
        $answer = $io->ask("Daily Cron Execution Hour (00-24)?", $dailyCronHour);
        if (is_numeric($answer)) {
            $cronStatus->setDailyCronExecutionHour($answer);
        }
    }
    public static function registerAnyNonSystemTask(\WHMCS\Cron\Console\Style\TaskStyle $io = NULL)
    {
        $instances = array();
        $finder = (new \Symfony\Component\Finder\Finder())->files()->in(ROOTDIR . "/vendor/whmcs/whmcs-foundation/lib/Cron/Task")->name("*.php");
        foreach ($finder as $item) {
            $filename = $item->getBasename(".php");
            if (strpos($filename, "Abstract") !== false) {
                continue;
            }
            $classname = "WHMCS\\Cron\\Task\\" . $filename;
            if ($io && $io->isDebug()) {
                $io->text("- Attempt to instantiate " . $classname);
            }
            if (!class_exists($classname)) {
                if ($io && $io->isDebug()) {
                    $io->text("- Class " . $classname . " does not exist");
                }
                continue;
            }
            $instance = new $classname();
            if (!$instance instanceof \WHMCS\Scheduling\Task\TaskInterface) {
                if ($io && $io->isDebug()) {
                    $io->text("- Class " . $classname . " is not of TaskInterface");
                }
                continue;
            }
            if ($instance->getAccessLevel() != \WHMCS\Scheduling\Task\TaskInterface::ACCESS_USER) {
                if ($io && $io->isDebug()) {
                    $io->text("- Class " . $classname . " is not TaskInterface::ACCESS_USER");
                }
                continue;
            }
            $instance = $instance::register();
            if ($io) {
                $io->text($instance->getName());
            }
            $instances[] = $instance;
        }
        return new \WHMCS\Scheduling\Task\Collection($instances);
    }
    public static function resetNextRun(\WHMCS\Scheduling\Task\Collection $tasks, \WHMCS\Carbon $nextRunTime)
    {
        foreach ($tasks as $task) {
            $task->getStatus()->setNextDue($nextRunTime)->save();
        }
    }
}

?>