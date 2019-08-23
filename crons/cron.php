<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php";
include ROOTDIR . "/includes/clientfunctions.php";
include ROOTDIR . "/includes/modulefunctions.php";
include ROOTDIR . "/includes/gatewayfunctions.php";
include ROOTDIR . "/includes/ccfunctions.php";
include ROOTDIR . "/includes/processinvoices.php";
include ROOTDIR . "/includes/invoicefunctions.php";
include ROOTDIR . "/includes/backupfunctions.php";
include ROOTDIR . "/includes/ticketfunctions.php";
include ROOTDIR . "/includes/currencyfunctions.php";
include ROOTDIR . "/includes/domainfunctions.php";
include ROOTDIR . "/includes/registrarfunctions.php";
$application = new WHMCS\Cron\Console\Application("WHMCS Automation Task Utility", WHMCS\Application::FILES_VERSION);
$application->setAutoExit(false);
if (WHMCS\Environment\Php::isCli()) {
    $input = new WHMCS\Cron\Console\Input\CliInput();
    if ($input->isLegacyInput()) {
        $input = new WHMCS\Cron\Console\Input\CliInput($input->getMutatedLegacyInput());
    }
    $output = new Symfony\Component\Console\Output\ConsoleOutput();
} else {
    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $cmd = trim(trim(WHMCS\Input\Sanitize::decode($request->get("command", $request->get("cmd", ""))), "'\""));
    $options = trim(trim(WHMCS\Input\Sanitize::decode($request->get("options", "")), "'\""));
    $input = array("command" => WHMCS\Input\Sanitize::encode($cmd), "options" => WHMCS\Input\Sanitize::encode($options));
    $input = new WHMCS\Cron\Console\Input\HttpInput($input);
    $stream = fopen("php://output", "w");
    $output = new Symfony\Component\Console\Output\BufferedOutput();
}
if ($input->hasParameterOption("defaults")) {
    $application->add(new WHMCS\Cron\Console\Command\RegisterDefaultsCommand());
}
define("INCRONRUN", true);
define("IN_CRON", true);
$exitCode = $application->run($input, $output);
if ($output instanceof Symfony\Component\Console\Output\BufferedOutput) {
    $config = DI::make("config");
    if (!empty($config["display_errors"])) {
        echo nl2br($output->fetch());
    }
}
exit($exitCode);

?>