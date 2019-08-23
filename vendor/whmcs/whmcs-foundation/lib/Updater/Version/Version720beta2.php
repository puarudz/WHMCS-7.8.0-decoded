<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version720beta2 extends IncrementalVersion
{
    protected $updateActions = array("addPaymentReversalChangeSettings");
    protected function addPaymentReversalChangeSettings()
    {
        \WHMCS\Config\Setting::setValue("ReversalChangeInvoiceStatus", 1);
        \WHMCS\Config\Setting::setValue("ReversalChangeDueDates", 1);
        return $this;
    }
}

?>