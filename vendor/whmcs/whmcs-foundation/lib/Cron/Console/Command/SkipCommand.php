<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Command;

class SkipCommand extends AllCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName("skip")->setDescription("Execute specific automation tasks")->setHelp("This command will perform all automation tasks that are " . "due to run at the time of script execution, " . "except for those specified");
    }
    public function getInputBasedCollection(\Symfony\Component\Console\Input\InputInterface $input)
    {
        return $this->getHelper("task-collection")->getExcludeCollection($input);
    }
    protected function getSystemQueue()
    {
        $input = $this->io->getInput();
        $queue = parent::getSystemQueue();
        if ($input->hasOption("DatabaseBackup") && $input->getOption("DatabaseBackup")) {
            foreach ($queue->keys() as $key) {
                if ($queue->offsetGet($key) instanceof \WHMCS\Cron\Task\DatabaseBackup) {
                    $queue->offsetUnset($key);
                    break;
                }
            }
        }
        return $queue;
    }
}

?>