<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Input;

class CliInput extends \Symfony\Component\Console\Input\ArgvInput
{
    use LegacyOptionsTrait;
    protected $originalData = NULL;
    public function __construct(array $argv = NULL)
    {
        if (null === $argv) {
            $argv = $_SERVER["argv"];
        }
        $this->originalData = $argv;
        parent::__construct($argv);
    }
    public function supportedCommands()
    {
        return array("all", "do", "skip", "defaults", "list", "help");
    }
    public function isLegacyInput()
    {
        $isLegacyInputOptions = false;
        $firstArgument = $this->getFirstArgument();
        if (empty($firstArgument) || in_array($firstArgument, $this->supportedCommands())) {
            $isLegacyInputOptions = false;
        } else {
            if (strpos($firstArgument, "do_") === 0 || strpos($firstArgument, "skip_") === 0 || strpos($firstArgument, "escalations") === 0) {
                $isLegacyInputOptions = true;
            }
        }
        return $isLegacyInputOptions;
    }
    public function getMutatedLegacyInput()
    {
        $argv = $this->originalData;
        $file = array_shift($argv);
        $isDoCommand = false;
        $options = array();
        $verbosity = "";
        $force = "";
        $originalEscalationSyntax = array_keys($argv, "escalations");
        if (!empty($originalEscalationSyntax)) {
            $argv[$originalEscalationSyntax[0]] = "do_escalations";
        }
        foreach ($argv as $argument) {
            if ($argument == "debug") {
                $verbosity = "-vvv";
            } else {
                if (in_array($argument, array("-vvv", "-vv", "-v")) || strpos($argument, "--verbose") !== false) {
                    $verbosity = $argument;
                } else {
                    if ($argument == "force" || $argument == "--force" || $argument == "-F") {
                        $force = "--force";
                    } else {
                        if (strpos($argument, "do") === 0 && strpos($argument, "_") !== false) {
                            $isDoCommand = true;
                            if ($argument != "do_report") {
                                $options[] = "--" . explode("_", $argument, 2)[1];
                            }
                        }
                    }
                }
            }
        }
        if (!$isDoCommand) {
            foreach ($argv as $argument) {
                if (strpos($argument, "skip") === 0 && strpos($argument, "_") !== false && $argument != "skip_report") {
                    $options[] = "--" . explode("_", $argument, 2)[1];
                }
            }
        }
        if (empty($options)) {
            array_unshift($options, "all");
        } else {
            if ($isDoCommand) {
                array_unshift($options, "do");
            } else {
                array_unshift($options, "skip");
            }
        }
        if (in_array("skip_report", $argv)) {
            $options[] = "--email-report=0";
        } else {
            if (in_array("do_report", $argv)) {
                $options[] = "--email-report=1";
            }
        }
        if ($verbosity) {
            $options[] = $verbosity;
        }
        if ($force) {
            $options[] = $force;
        }
        $options = $this->convertLegacyOptions($options);
        array_unshift($options, $file);
        return $options;
    }
}

?>