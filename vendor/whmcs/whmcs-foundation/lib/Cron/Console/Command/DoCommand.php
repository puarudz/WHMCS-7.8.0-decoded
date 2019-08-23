<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class DoCommand extends AbstractCronCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName("do")->setDescription("Execute specific automation tasks")->setHelp("This command will perform only the specified automation " . "tasks, regardless if the task is due to run at the time of " . "script execution");
        $this->addOption("--email-report", "", \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Send Daily Cron Digest email. Options are \"0\" and \"1\". Defaults to \"0\" for \"do\" command", 0);
    }
    protected function beforeExecution()
    {
        parent::beforeExecution();
        $dailyCronHelper = new \WHMCS\Cron\Console\Helper\DailyCronHelper($this->io->getInput(), $this->io->getOutput(), new \WHMCS\Cron\Status());
        $this->getHelperSet()->set($dailyCronHelper);
        if ($this->io->hasEmailReportOption()) {
            $dailyCronHelper->getReport()->start();
        }
        return $this;
    }
    protected function afterExecution()
    {
        if ($this->io->hasEmailReportOption()) {
            $dailyCronHelper = $this->getHelper("daily-cron");
            $dailyCronHelper->getReport()->finish();
            $dailyCronHelper->sendDailyCronDigest();
        }
        return parent::afterExecution();
    }
    public function getInputBasedCollection(\Symfony\Component\Console\Input\InputInterface $input)
    {
        return $this->getHelper("task-collection")->getIncludeCollection($input);
    }
    protected function initialize(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->addOption("--force", "-F", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Force run tasks regardless if they are due or currently being run " . "by another process.  This is implied with the do command");
        $input->setOption("force", 1);
        parent::initialize($input, $output);
    }
    protected function getSystemQueue()
    {
        $input = $this->io->getInput();
        if ($input->hasOption("DatabaseBackup") && $input->getOption("DatabaseBackup")) {
            $tasks = array(new \WHMCS\Cron\Task\DatabaseBackup((new \WHMCS\Cron\Task\DatabaseBackup())->getDefaultAttributes()));
        } else {
            $tasks = array();
        }
        return new \WHMCS\Scheduling\Task\Collection($tasks);
    }
}

?>