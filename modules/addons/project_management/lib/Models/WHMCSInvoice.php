<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement\Models;

class WHMCSInvoice extends \WHMCS\Billing\Invoice
{
    protected $appends = array("balance");
}

?>