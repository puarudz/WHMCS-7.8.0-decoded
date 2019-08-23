<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class PollPaymentGateway extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1575;
    protected $defaultDescription = "Runs the poll function for any active payment gateways. Runs before Automation Suspensions in case required.";
    protected $defaultName = "Poll Payment Gateways";
    protected $systemName = "PollPaymentGateway";
    public function __invoke()
    {
        $paymentGateways = new \WHMCS\Module\Gateway();
        $activeGateways = $paymentGateways->getActiveGateways();
        foreach ($activeGateways as $activeGateway) {
            $paymentGateways->load($activeGateway);
            if ($paymentGateways->functionExists("poll")) {
                $paymentGateways->call("poll");
            }
        }
    }
}

?>