<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class ComposerWrapper
{
    private $environmentErrors = array();
    private $targetDir = "";
    private $updateTempDir = "";
    private $dryRun = false;
    private $commandOutput = "";
    private $commandExecutionTime = 0;
    private $commandStartTime = 0;
    private $commandSuccess = false;
    private $config = array();
    private $previousErrorHandler = NULL;
    private $previousErrorReporting = 0;
    private $lastRunPackageMetadata = array();
    const PACKAGE_NAME = "whmcs/whmcs";
    const COMPOSER_UPDATE_SUBDIR = "composer";
    public function __construct($updateTempPath)
    {
        if (!defined("ROOTDIR")) {
            throw new ComposerUpdateException("Missing ROOTDIR");
        }
        $this->commandExecutionTime = 0;
        $this->commandStartTime = 0;
        $this->commandSuccess = false;
        $this->setTargetDir(ROOTDIR)->setDryRun(false)->setUpdateTempDir($updateTempPath)->initEnvironment();
    }
    protected function getTargetDir()
    {
        return $this->targetDir;
    }
    protected function setTargetDir($value)
    {
        $this->targetDir = $value;
        return $this;
    }
    protected function getUpdateTempDir()
    {
        return $this->updateTempDir;
    }
    protected function setUpdateTempDir($value)
    {
        $this->updateTempDir = $value;
        return $this;
    }
    public function getDryRun()
    {
        return $this->dryRun;
    }
    public function setDryRun($value)
    {
        $this->dryRun = $value;
        return $this;
    }
    public function getCommandOutput()
    {
        return $this->commandOutput;
    }
    public function getCommandExecutionTime()
    {
        return $this->commandExecutionTime;
    }
    public function getCommandSuccess()
    {
        return $this->commandSuccess;
    }
    public function setConfig(array $value)
    {
        $this->config = $value;
        return $this;
    }
    public function initEnvironment()
    {
        $composerDirectory = "";
        $this->environmentErrors = UpdateEnvironment::initEnvironment($this->updateTempDir);
        if (empty($this->environmentErrors)) {
            $composerDirectory = $this->updateTempDir . DIRECTORY_SEPARATOR . self::COMPOSER_UPDATE_SUBDIR;
            if (!is_dir($composerDirectory) && !@mkdir($composerDirectory)) {
                $this->environmentErrors[] = \AdminLang::trans("update.notWritablePath", array(":path" => $composerDirectory));
            }
        }
        if (empty($this->environmentErrors)) {
            putenv("COMPOSER_HOME=" . $composerDirectory);
        }
        return $this;
    }
    public function getEnvironmentErrors()
    {
        return $this->environmentErrors;
    }
    public function isEnvironmentValid()
    {
        return empty($this->environmentErrors) ? true : false;
    }
    protected function validateCommandEnvironment()
    {
        $this->initEnvironment();
        if (!$this->isEnvironmentValid()) {
            throw new ComposerUpdateException(implode("\n", $this->environmentErrors));
        }
        if (empty($this->config)) {
            throw new ComposerUpdateException("Composer configuration has not been set");
        }
    }
    protected function saveErrorHandling()
    {
        $this->previousErrorHandler = set_error_handler("var_dump", 0);
        restore_error_handler();
        $this->previousErrorReporting = error_reporting();
        return $this;
    }
    protected function restoreErrorHandling()
    {
        $currentHandler = set_error_handler("var_dump", 0);
        restore_error_handler();
        if ($currentHandler == $this->previousErrorHandler) {
            return $this;
        }
        while ($currentHandler != $this->previousErrorHandler) {
            restore_error_handler();
            $currentHandler = set_error_handler("var_dump", 0);
            restore_error_handler();
            if (is_null($currentHandler)) {
                if (!is_null($this->previousErrorHandler)) {
                    set_error_handler($this->previousErrorHandler);
                }
                break;
            }
        }
        error_reporting($this->previousErrorReporting);
        return $this;
    }
    protected function startCommandRun()
    {
        $this->validateCommandEnvironment();
        $this->commandOutput = "";
        $this->commandSuccess = false;
        $this->commandExecutionTime = 0;
        $this->commandStartTime = microtime(true);
        $this->saveErrorHandling();
    }
    protected function endCommandRun()
    {
        $this->commandExecutionTime = microtime(true) - $this->commandStartTime;
        $this->commandStartTime = 0;
        $this->deleteInstalledJson();
        $this->deleteComposerLock();
        $this->restoreErrorHandling();
    }
    protected function deleteInstalledJson()
    {
        $installedJsonPath = $this->getTargetDir() . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "composer" . DIRECTORY_SEPARATOR . "installed.json";
        if (file_exists($installedJsonPath)) {
            return @unlink($installedJsonPath);
        }
        return true;
    }
    protected function deleteComposerLock()
    {
        $composerLockPath = $this->getTargetDir() . DIRECTORY_SEPARATOR . "composer.lock";
        if (file_exists($composerLockPath)) {
            return @unlink($composerLockPath);
        }
        return true;
    }
    public function update()
    {
        try {
            $this->startCommandRun();
            $app = new WhmcsComposerApplication();
            if (!empty($this->config)) {
                $app->setOverrideConfig($this->config);
            }
            $params = array("command" => "update", "packages" => array(self::PACKAGE_NAME), "--working-dir" => $this->targetDir, "--dry-run" => $this->getDryRun(), "--prefer-dist" => true, "--no-dev" => true, "--no-interaction" => true, "--no-progress" => true, "--no-autoloader" => true);
            $app->setAutoExit(false);
            $input = new \Symfony\Component\Console\Input\ArrayInput($params);
            $output = new MonologBufferedOutputWrapper();
            try {
                if ($logger = \DI::make("log")) {
                    $output->setLogger($logger);
                }
            } catch (\Exception $e) {
                $output->write("Log Mode: Buffered Only");
            }
            $exitCode = $app->run($input, $output);
        } finally {
            $this->endCommandRun();
        }
    }
    public function getLatestVersion()
    {
        $dryRun = $this->getDryRun();
        $latestVersion = false;
        $this->setDryRun(true);
        try {
            if ($this->update()->getCommandSuccess()) {
                $output = $this->getCommandOutput();
                if (preg_match("|\n\\s*\\-\\s*Installing " . self::PACKAGE_NAME . "\\s+\\(([\\d\\.a-z\\-]+)\\)\\s*\n|i", $output, $matches)) {
                    $latestVersion = $matches[1];
                }
            }
        } finally {
            $this->setDryRun($dryRun);
        }
    }
    public function getLastRunPackageMetadata()
    {
        return $this->lastRunPackageMetadata;
    }
}

?>