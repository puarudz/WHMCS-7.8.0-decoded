<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User\Client;

class AlertFactory
{
    protected $client = NULL;
    protected $alerts = array();
    public function __construct(\WHMCS\User\Client $client)
    {
        $this->client = $client;
    }
    public function build()
    {
        $this->checkForExpiringCreditCard()->checkForDomainsExpiringSoon()->checkForUnpaidInvoices()->checkForCreditBalance();
        $alerts = run_hook("ClientAlert", $this->client);
        foreach ($alerts as $response) {
            if ($response instanceof \WHMCS\User\Alert) {
                $this->addAlert($response);
            }
        }
        return new \Illuminate\Support\Collection($this->alerts);
    }
    protected function addAlert(\WHMCS\User\Alert $alert)
    {
        $this->alerts[] = $alert;
        return $this;
    }
    protected function checkForExpiringCreditCard()
    {
        $expiringCard = $this->client->isCreditCardExpiring();
        if ($expiringCard) {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.creditCardExpiring", array(":creditCardType" => $expiringCard["cardtype"], ":creditCardLastFourDigits" => $expiringCard["cardlastfour"], ":days" => 60)), "warning", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=creditcard", \Lang::trans("clientareaupdatebutton")));
        }
        return $this;
    }
    protected function checkForDomainsExpiringSoon()
    {
        if (!\WHMCS\Config\Setting::getValue("EnableDomainRenewalOrders")) {
            return $this;
        }
        $domainsDueWithin7Days = $this->client->domains()->nextDueBefore(\WHMCS\Carbon::now()->addDays(7))->count();
        if (0 < $domainsDueWithin7Days) {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.domainsExpiringSoon", array(":days" => 7, ":numberOfDomains" => $domainsDueWithin7Days)), "danger", routePath("cart-domain-renewals"), \Lang::trans("domainsrenewnow")));
        }
        $domainsDueWithin30Days = $this->client->domains()->nextDueBefore(\WHMCS\Carbon::now()->addDays(30))->count();
        $domainsDueWithin30Days -= $domainsDueWithin7Days;
        if (0 < $domainsDueWithin30Days) {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.domainsExpiringSoon", array(":days" => 30, ":numberOfDomains" => $domainsDueWithin30Days)), "info", routePath("cart-domain-renewals"), \Lang::trans("domainsrenewnow")));
        }
        return $this;
    }
    protected function checkForUnpaidInvoices()
    {
        $clientId = $this->client->id;
        $currency = getCurrency($clientId);
        $invoices = \WHMCS\Database\Capsule::table("tblinvoices")->where("tblinvoices.userid", $clientId)->where("status", "Unpaid")->leftJoin("tblaccounts", "tblaccounts.invoiceid", "=", "tblinvoices.id")->first(array(\WHMCS\Database\Capsule::raw("IFNULL(count(tblinvoices.id), 0) as invoice_count"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(total), 0) as total"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountin), 0) as amount_in"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountout), 0) as amount_out")));
        if (0 < $invoices->invoice_count) {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.invoicesUnpaid", array(":numberOfInvoices" => $invoices->invoice_count, ":balanceDue" => formatCurrency($invoices->total - $invoices->amount_in + $invoices->amount_out))), "info", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=masspay&all=true", \Lang::trans("invoicespaynow")));
        }
        $invoices = \WHMCS\Database\Capsule::table("tblinvoices")->where("tblinvoices.userid", $clientId)->where("status", "Unpaid")->where("duedate", \WHMCS\Carbon::now()->toDateString())->leftJoin("tblaccounts", "tblaccounts.invoiceid", "=", "tblinvoices.id")->first(array(\WHMCS\Database\Capsule::raw("IFNULL(count(tblinvoices.id), 0) as invoice_count"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(total), 0) as total"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountin), 0) as amount_in"), \WHMCS\Database\Capsule::raw("IFNULL(SUM(amountout), 0) as amount_out")));
        if (0 < $invoices->invoice_count) {
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.invoicesOverdue", array(":numberOfInvoices" => $invoices->invoice_count, ":balanceDue" => formatCurrency($invoices->total - $invoices->amount_in + $invoices->amount_out))), "warning", \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php?action=masspay&all=true", \Lang::trans("invoicespaynow")));
        }
        return $this;
    }
    protected function checkForCreditBalance()
    {
        $creditBalance = $this->client->credit;
        if (0 < $creditBalance) {
            $currency = getCurrency($this->client->id);
            $this->addAlert(new \WHMCS\User\Alert(\Lang::trans("clientAlerts.creditBalance", array(":creditBalance" => formatCurrency($creditBalance))), "success", "", ""));
        }
        return $this;
    }
}

?>