<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class AllCommand extends AbstractCronCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName("all")->setDescription("Execute all automation tasks")->setHelp("This command will perform all automation tasks that are " . "due to run at the time of script execution");
        $this->addOption("--force", "-F", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Force run tasks regardless if they are due or currently being run " . "by another process");
        $this->addOption("--email-report", "", \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Send Daily Cron Digest email. Options are \"0\" and \"1\". Defaults to \"1\" if performing the Daily Cron routines", 1);
    }
    public function getInputBasedCollection(\Symfony\Component\Console\Input\InputInterface $input)
    {
        return $this->getHelper("task-collection")->allTasks()->isEnabled();
    }
    protected function beforeExecution()
    {
        parent::beforeExecution();
        $dailyCronHelper = new \WHMCS\Cron\Console\Helper\DailyCronHelper($this->io->getInput(), $this->io->getOutput(), new \WHMCS\Cron\Status());
        $this->getHelperSet()->set($dailyCronHelper);
        if ($dailyCronHelper->isDailyCronInvocation()) {
            $dailyCronHelper->startDailyCron();
            if ($this->io->isDebug()) {
                $this->io->text("Daily Cron Automation Mode");
            }
        }
        return $this;
    }
    protected function afterExecution()
    {
        $dailyCronHelper = $this->getHelper("daily-cron");
        if ($dailyCronHelper->isDailyCronInvocation()) {
            $dailyCronHelper->endDailyCron();
        }
        return parent::afterExecution();
    }
    protected function getSystemQueue()
    {
        if ($this->getHelper("daily-cron")->isDailyCronInvocation()) {
            $tasks = array(new \WHMCS\Cron\Task\DataNormalization((new \WHMCS\Cron\Task\DataNormalization())->getDefaultAttributes()), new \WHMCS\Cron\Task\LicenseNotice((new \WHMCS\Cron\Task\LicenseNotice())->getDefaultAttributes()), new \WHMCS\Cron\Task\SystemConfiguration((new \WHMCS\Cron\Task\SystemConfiguration())->getDefaultAttributes()), new \WHMCS\Cron\Task\DatabaseBackup((new \WHMCS\Cron\Task\DatabaseBackup())->getDefaultAttributes()));
        } else {
            $tasks = array();
        }
        return new \WHMCS\Scheduling\Task\Collection($tasks);
    }
}

?>