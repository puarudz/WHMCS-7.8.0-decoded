<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Cli;

class AbstractApplication
{
    protected $cli = NULL;
    protected $installer = NULL;
    public function __construct(\League\CLImate\CLImate $cli, \WHMCS\Installer\Installer $installer)
    {
        $this->setCli($cli)->setInstaller($installer);
    }
    public function getCli()
    {
        return $this->cli;
    }
    public function setCli($cli)
    {
        $this->cli = $cli;
        return $this;
    }
    public function getInstaller()
    {
        return $this->installer;
    }
    public function setInstaller($installer)
    {
        $this->installer = $installer;
        return $this;
    }
    public function header($title = "")
    {
        $cli = $this->getCli();
        $this->headerArt();
        $cli->border("*", 72);
        if ($title) {
            $cli->flank($title, "*");
            if ($cli->arguments->defined("non-interactive")) {
                $cli->flank("NON-INTERACTIVE MODE", "*");
            }
            $cli->border("*", 72);
        }
        $cli->out("");
        return $this;
    }
    public function headerArt()
    {
        $cli = $this->getCli();
        $cli->addArt(INSTALLER_DIR);
        $cli->draw("whmcs-ascii-color");
        return $this;
    }
    public function footer()
    {
        $cli = $this->getCli();
        $cli->out("");
        $cli->comment("Program Completed");
        $cli->border("*", 72);
        $cli->border("*", 72);
        return $this;
    }
    public function error($message, $suggestHelp = false)
    {
        $cli = $this->getCli();
        $cli->backgroundRed()->white()->border("#", 72);
        $cli->backgroundRed()->white()->flank("ERROR", "#");
        $cli->backgroundRed()->white()->border("#", 72);
        $cli->white($message);
        $cli->out("");
        if ($suggestHelp) {
            $cli->border("#", 72);
            $cli->usage();
            $cli->out("");
        }
        $cli->backgroundRed()->white()->border("#", 72);
        return $this;
    }
    public function errorException(\Exception $e)
    {
        $this->error(sprintf("%s in %s at %s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
        return $this;
    }
    public function addVerbosity()
    {
        \Log::pushHandler(new \Monolog\Handler\StreamHandler("php://stdout", \Monolog\Logger::DEBUG));
    }
    protected function createProgressBar($totalSteps)
    {
        $progressBar = $this->getCli()->progress($totalSteps);
        $progressHandler = new Log\ProgressHandler(\Monolog\Logger::DEBUG);
        $progressHandler->setProgressBar($progressBar);
        $progressHandler->setOutput($this->getCli()->output());
        \Log::pushHandler($progressHandler);
        return $progressBar;
    }
    public function addProgressBar($totalSteps = 0, $calculateBehind = true)
    {
        if (!$totalSteps) {
            $allVersions = \WHMCS\Updater\Version\IncrementalVersion::$versionIncrements;
            $dbVersion = $this->getInstaller()->getVersion()->getCanonical();
            $totalSteps = count($allVersions);
            if ($calculateBehind && in_array($dbVersion, $allVersions)) {
                $currentVersionIndex = array_keys($allVersions, $dbVersion);
                $versionsToApply = array_slice($allVersions, $currentVersionIndex[0]);
                $totalSteps = count($versionsToApply);
            }
        }
        if ($totalSteps) {
            return $this->createProgressBar($totalSteps);
        }
        return null;
    }
    protected function notImplemented()
    {
        $cli = $this->getCli();
        $cli->shout("--- NOT IMPLEMENTED ---");
        return $this;
    }
    public function eula()
    {
        $cli = $this->getCli();
        if ($cli->arguments->defined("non-interactive")) {
            $cli->comment("** EULA ACCEPTED via NON-INTERACTIVE MODE **");
        } else {
            $input = $cli->confirm("Please confirm you have read and accept the \n" . "<background_blue><white>WHMCS End User License Agreement</white></background_blue>" . " (<underline>https://www.whmcs.com/license/</underline>)?");
            if (!$input->confirmed()) {
                throw new \WHMCS\Exception\Installer\UserBail("EULA agreement required.");
            }
        }
        return $this;
    }
    public function install()
    {
        return $this->notImplemented();
    }
    public function upgrade()
    {
        return $this->notImplemented();
    }
    public function status()
    {
        return $this->notImplemented();
    }
}

?>