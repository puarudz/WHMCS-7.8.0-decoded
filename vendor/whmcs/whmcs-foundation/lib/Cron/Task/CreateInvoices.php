<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class CreateInvoices extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1520;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Generate Invoices";
    protected $defaultName = "Invoices";
    protected $systemName = "CreateInvoices";
    protected $outputs = array("invoice.created" => array("defaultValue" => 0, "identifier" => "invoice.created", "name" => "Total Invoices"));
    protected $icon = "far fa-file-alt";
    protected $successCountIdentifier = "invoice.created";
    protected $failedCountIdentifier = "";
    protected $successKeyword = "Generated";
    public function __invoke()
    {
        if (!function_exists("createInvoices")) {
            include_once ROOTDIR . "/includes/processinvoices.php";
        }
        createInvoices("", "", "", "", $this);
        return $this;
    }
}

?>