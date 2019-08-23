<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Update;

class UpdateCheckTask extends \WHMCS\Scheduling\Task\AbstractTask
{
    public $description = "WHMCS Update Check";
    protected $frequency = "0 */8 * * *";
    public function __construct()
    {
        parent::__construct();
        $this->preventOverlapping();
    }
    public function __invoke()
    {
        $this->getOutput()->debug("a debug message", array("PreviousCheck" => \WHMCS\Config\Setting::getValue("UpdatesLastChecked")));
        $this->getOutput()->info("Fetching Update Info");
        $updater = new Updater();
        return $updater->updateRemoteComposerData();
    }
}

?>